<template>

  <!-- Основной контейнер компонента с отступами -->
  <div v-if="hasAgent" class="agent-layout min-w-full">

    <!-- Показываем контент, если данные загружены (apiData и bData не null) -->
    <div v-if="apiData && bData">

      <!-- Заголовок с локализацией для типа сотрудничества "агент" -->
      <div class="va-h5 my-1">
        <RouterLink :to="{ name: 'Account' }">
          <span>
            <VaAvatar class="w-10 h-10" :src="props.user.avatar_url" /> 
          </span>
        </RouterLink>
        <span @click="openPayoutModal" class="master-color" :title="$t('balance')">
          {{ formatPrice(bData.data?.balance) ?? $t('common.no_data') }}
        </span>
        <VaButton class="ml-2" preset="secondary" icon="chevron_right" @click="openPayoutModal">{{ $t('coupons.pullMoney') }}</VaButton>
      </div>

      <!-- Описание пользовательского соглашения с ссылкой на контракт -->
      <p class="text-gray-400">
        <i>
          <a class="ml-1 avi-link avi-link-out" preset="secondary" @click="showConditions_Agent = true">{{ $t('rules') }}</a> | 
          {{ $t('user_agreement.description') }}
          <a class="avi-link avi-link-out" target="_blank" :href="selectedCooperationType?.contract_url">
            {{ $t('user_agreement.link_name') }}
          </a>
        </i>
      </p>

      <!-- Контейнер для табов, прижат к левому краю -->
      <div class="tabs-container">
        <VaTabs v-model="activeTab">
          <VaTab name="coupons">{{ $t('coupons.coupons') }}</VaTab>
          <VaTab name="credits">{{ $t('coupons.credits') }}</VaTab>
          <VaTab name="debits">{{ $t('coupons.debits') }}</VaTab>
        </VaTabs>
      </div>

      <!-- Содержимое выбранной вкладки -->
      <div class="tab-content mt-4">
        <div v-if="activeTab === 'coupons'">

          <!-- Компонент списка купонов -->
          <div class="my-2">
            <CouponsList :apiData="apiData" :bData="bData" :refresh="refreshKey" />
          </div>

          <!-- Кнопка для создания нового купона, вызывает fetchAllData при создании -->
          <div class="my-2">
            <CreateCoupon :apiData="apiData" :bData="bData" @coupon-created="handleCouponCreated" />
          </div>

        </div>
        <div v-else-if="activeTab === 'credits'">
          <CreditsList :apiData="apiData" :bData="bData" :refresh="refreshKey" />
        </div>
        <div v-else-if="activeTab === 'debits'">
          <DebitsList :apiData="apiData" :bData="bData" :refresh="refreshKey" />
        </div>
      </div>
    </div>

    <!-- Показываем предупреждение, пока данные загружаются -->
    <div v-else-if="loading">
      <div class="flex">
        <VaSkeleton variant="circle" height="4rem" />
        <VaSkeleton tag="h1" variant="text" class="va-h1 ml-4" />
      </div>
      <div class="w-full">
        <!-- Показываем индикатор загрузки, если loading = true -->
        <div v-if="loading" class="mt-4 pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <VaSkeleton v-for="i in 3" :key="i" variant="rounded" height="200px" />
        </div>
      </div>
    </div>

    <!-- Показываем ошибку, если загрузка не удалась -->
    <div v-else-if="error">{{ error }}</div>

  </div>

  <div v-else>
    <div class="p-4 my-4 rounded-lg bg-gray-200">
      <p class="my-2">{{ $t('welcomes.apps.rejected') }}</p>
      <VaDivider class="my-2" />
      <p class="my-2">{{ $t('welcomes.apps.rejected_contact') }}</p>
    </div>
  </div>

  <!-- Модалка для создания заявки -->
  <VaModal v-model="showPayoutModal" close-button hide-default-actions max-width="600px">
    <PayoutModal :bData="bData" :apiData="apiData" @close="showPayoutModal = false" @created="handlePayoutCreated" />
  </VaModal>

  <VaModal v-model="showConditions_Agent" :close-button="true" :hide-default-actions="true">
    <Conditions_Agent :apiData="apiData" />
    <template #footer>
      <VaButton @click="showConditions_Agent = false">OK</VaButton>
    </template>
  </VaModal>

</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vuestic-ui'
import CouponsList from './Agent/CouponsList.vue'
import CreateCoupon from './Agent/CreateCoupon.vue'
import CreditsList from './Agent/CreditsList.vue'
import DebitsList from './Agent/DebitsList.vue'
import PayoutModal from './Agent/PayoutModal.vue'
import Conditions_Agent from '@/components/parts/Conditions/Agent.vue';
import { getBusinessData } from '@/api/coupons'
import { useBase } from '@/composables/useBase';
import { useI18n } from 'vue-i18n'
import { usePartnerApplications } from '@/composables/usePartnerApplications';
const { hasApplication } = usePartnerApplications();

const { formatPrice } = useBase();
const { t } = useI18n()  // ← t для скрипта

// Реактивные переменные
const apiData = ref(null)
const bData = ref(null)
const loading = ref(true)
const showConditions_Agent = ref(false)
const error = ref(null)
const refreshKey = ref(0)
const activeTab = ref('coupons')

// Хранилище и уведомления
const authStore = useAuthStore()
const { init: initToast } = useToast()  // ← useToast для уведомлений

// Вычисляемые свойства
const selectedCooperationType = computed(() => {
  if (!apiData.value?.cooperation_types) return null
  return apiData.value.cooperation_types.find(type => type.id === 2)
})

const showPayoutModal = ref(false)

const props = defineProps({
  user: { type: Object, default: null },  // Фикс: наследует user prop
})

const openPayoutModal = () => {
  showPayoutModal.value = true
}

const handlePayoutCreated = async () => {
  try {
    refreshKey.value++ // Обновляем списки (debits и т.д.)
    await fetchAllData() // Перезагружаем bData (баланс обновится)
    initToast({
      message: t('payoutRequest.create.success'),
      color: 'success',
    })
  } catch (err) {
    initToast({
      message: t('errors.unexpected_error'),
      color: 'danger',
    })
  }
}

// Обработчик создания купона
const handleCouponCreated = async () => {
  try {
    refreshKey.value++ // Обновляем CouponsList
    await fetchAllData() // Перезагружаем данные
    initToast({
      message: t('coupons.created_success'),
      color: 'success',
    })
  } catch (err) {
    initToast({
      message: t('errors.coupon_creation_failed'),
      color: 'danger',
    })
  }
}

// Функции загрузки данных
const fetchApiData = async () => {
  try {
    const response = await axios.get('/api/ps', {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Accept': 'application/json',
      },
    })
    apiData.value = response.data
  } catch (err) {
    throw new Error(err.response?.data?.message || t('errors.data_loading'))  // ← t вместо $t
  }
}

const loadBusinessData = async () => {
  try {
    const response = await getBusinessData()  // ← ФИКС: Добавь await здесь!
    if (response.success) {
      bData.value = response  // Теперь response = {success: true, data: {...}}, bData.data?.user сработает
    } else {
      throw new Error(t('errors.business_data_loading'))
    }
  } catch (err) {
    throw new Error(err.message || t('errors.business_data_loading'))
  }
}

const fetchAllData = async () => {
  try {
    loading.value = true
    error.value = null
    await Promise.all([fetchApiData(), loadBusinessData()])
  } catch (err) {
    error.value = err.message
    initToast({
      message: t('errors.data_loading'),
      color: 'danger',
    })
  } finally {
    loading.value = false
  }
}

const hasAgent = computed(() => {
  return hasApplication(2, 2);
});

// Загружаем данные при монтировании
onMounted(fetchAllData)
</script>

<style scoped>
/* Стили для контейнера табов */
.tabs-container {
  display: inline-block;
  /* Контейнер занимает только ширину содержимого */
  text-align: left;
  /* Выравнивание табов по левому краю */
}
</style>