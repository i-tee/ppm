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

        <DiscountPercentageCode
          :apiData="apiData"
          v-model:modelDiscountValue="discountObject"
          v-if="activeTab === 'discountForm'"
        />

        <BonusRedemptionCode
          :apiData="apiData"
          v-else-if="activeTab === 'bonusForm'"
          v-model:modelBonusValue="bonusObject"
        />

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
import CheckCode from './CheckCode.vue'

const discountObject = ref({ name: '', value: 15 })
const bonusObject = ref({ name: '', value: 0 })
const error = ref(null) // ⬅️ Объяви error
const authStore = useAuthStore()
const apiData = ref(null)
const couponModal = ref(false)
const activeTab = ref('discountForm')

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

// Создание купона
function createCoupon() {
  console.log('Создание купона:')
  // Здесь будет логика создания купона
  //couponModal.value = false
}
</script>

<style scoped>
.tab-content {
  min-height: 200px;
}
</style>