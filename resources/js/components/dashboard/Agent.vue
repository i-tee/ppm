<template>
  <!-- Основной контейнер компонента с отступами -->
  <div class="agent-layout min-w-full">
    <!-- Показываем контент, если данные загружены (apiData и bData не null) -->
    <div v-if="apiData && bData">
      <!-- Заголовок с локализацией для типа сотрудничества "агент" -->
      <h1 class="va-h4 my-1">{{ bData.data?.user?.name }} | <span class="master-color">{{ formatPrice(bData.data?.balance) ?? $t('common.no_data') }}</span></h1>
      <!-- Описание пользовательского соглашения с ссылкой на контракт -->
      <p class="text-gray-400">
        <i>{{ $t('user_agreement.description') }}
          <a class="avi-link avi-link-out" target="_blank" :href="selectedCooperationType?.contract_url">
            {{ $t('user_agreement.link_name') }}
          </a></i>
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
          <div class="text-end my-2">
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
    <!-- Показываем предупреждение, если данные загружаются -->
    <div v-else-if="loading">

      <div class="w-full">

        <VaSkeleton variant="squared" width="100%" height="6rem" />
      </div>
    </div>
    <!-- Показываем ошибку, если загрузка не удалась -->
    <div v-else-if="error">{{ error }}</div>
  </div>
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
import { getBusinessData } from '@/api/coupons'
import { useBase } from '@/composables/useBase';

const { formatPrice } = useBase();

// Реактивные переменные
const apiData = ref(null)
const bData = ref(null)
const loading = ref(true)
const error = ref(null)
const refreshKey = ref(0)
const activeTab = ref('coupons')

// Хранилище и уведомления
const authStore = useAuthStore()
const { init: initToast } = useToast()

// Вычисляемые свойства
const selectedCooperationType = computed(() => {
  if (!apiData.value?.cooperation_types) return null
  return apiData.value.cooperation_types.find(type => type.id === 2)
})

// Обработчик создания купона
const handleCouponCreated = async () => {
  try {
    refreshKey.value++ // Обновляем CouponsList
    await fetchAllData() // Перезагружаем данные
    initToast({
      message: $t('coupons.created_success'),
      color: 'success',
    })
  } catch (err) {
    initToast({
      message: $t('errors.coupon_creation_failed'),
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
    throw new Error(err.response?.data?.message || $t('errors.data_loading'))
  }
}

const loadBusinessData = async () => {
  try {
    const response = await getBusinessData()
    if (response.success) {
      bData.value = response
    } else {
      throw new Error($t('errors.business_data_loading'))
    }
  } catch (err) {
    throw new Error(err.message || $t('errors.business_data_loading'))
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
      message: err.message,
      color: 'danger',
    })
    console.error('Ошибка загрузки данных:', err)
  } finally {
    loading.value = false
  }
}

// Загружаем данные при монтировании
onMounted(fetchAllData)
</script>

<style scoped>
.agent-layout {
  padding: 20px;
  min-width: 100%;
}
/* Стили для контейнера табов */
.tabs-container {
  display: inline-block; /* Контейнер занимает только ширину содержимого */
  text-align: left; /* Выравнивание табов по левому краю */
}
</style>