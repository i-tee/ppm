<template>

  <div class="create-coupon">
    <va-button @click="couponModal = true">{{ $t('coupons.create') }}</va-button>
  </div>

  <VaModal v-model="couponModal" size="small" close-button :hide-default-actions="true">
    <template #default>
      <h3 class="va-h3 text-center mb-0">{{ $t('coupons.create') }}</h3>

      <!-- Табы -->
      <VaTabs v-model="activeTab" grow>
        <VaTab name="discountForm">{{ $t('coupons.discountForm') }}</VaTab>
        <VaTab name="bonusForm">{{ $t('coupons.bonusForm') }}</VaTab>
        <!-- <VaTab name="checkCode">{{ $t('coupons.checkCode') }}</VaTab> -->
      </VaTabs>

      <!-- Содержимое табов -->
      <div class="tab-content mt-4">

        <DiscountPercentageCode :apiData="apiData" v-model:modelDiscountValue="discountObject"
          v-if="activeTab === 'discountForm'" />

        <BonusRedemptionCode :apiData="apiData" v-else-if="activeTab === 'bonusForm'"
          v-model:modelBonusValue="bonusObject" />

        <!-- <CheckCode
          :apiData="apiData"
          v-else-if="activeTab === 'checkCode'"
        /> -->
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end space-x-2">
        <VaButton preset="secondary" @click="couponModal = false">
          {{ $t('modal.cancel') }}
        </VaButton>
        <VaButton @click="createCoupon">
          {{ $t('coupons.create_button') }}
        </VaButton>
      </div>
    </template>
  </VaModal>

</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import DiscountPercentageCode from './DiscountPercentageCode.vue'
import BonusRedemptionCode from './BonusRedemptionCode.vue'
import { useToast } from 'vuestic-ui';
// import CheckCode from './CheckCode.vue'

const discountObject = ref({ name: '', value: 15 })
const bonusObject = ref({ name: '', value: 0 })
const error = ref(null) // ⬅️ Объяви error
const authStore = useAuthStore()
const apiData = ref(null)
const couponModal = ref(false)
const activeTab = ref('discountForm')
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
const toast = useToast();

// Загрузка данных API
onMounted(async () => {
  try {
    const response = await axios.get('/api/ps', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    apiData.value = response.data
  } catch (err) {
    error.value = err.response?.data || err.message
    console.error('Ошибка загрузки:', error.value)
  }
})

function createCoupon() {
  let creatCouponData = {}

  if (activeTab.value === 'discountForm') {
    creatCouponData = { ...discountObject.value, type: 0 }
  } else if (activeTab.value === 'bonusForm') {
    creatCouponData = { ...bonusObject.value, type: 1 }
  }

  if (!creatCouponData.name || !isValidPromoCode(creatCouponData.name)) {
    toast.init({
      message: t('errors.coupon_noValid'),
      color: 'warning',
    });
    document.getElementsByClassName("id-i11")[0].focus()
    return
  }

  console.log('Тип купона:', creatCouponData)
  // couponModal.value = false
}

function isValidPromoCode(code) {
  // Проверка на минимальную длину
  if (typeof code !== 'string' || code.length < 6) {
    return false;
  }

  // Регулярное выражение для проверки допустимых символов
  const regex = /^[A-Za-zА-Яа-яЁё0-9_-]+$/;

  // Возвращаем true только если прошли обе проверки
  return regex.test(code);
}

</script>

<style scoped>
.tab-content {
  min-height: 200px;
}
</style>