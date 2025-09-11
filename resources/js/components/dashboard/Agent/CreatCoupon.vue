<template>
  <div class="create-coupon">
    <va-button @click="couponModal = true">{{ $t('coupons.create') }}</va-button>
  </div>

  <VaModal v-model="couponModal" size="small" close-button :hide-default-actions="true">
    <template #default>
      <h3 class="va-h3 text-center mb-0">{{ $t('coupons.create') }}</h3>

      <!-- Табы -->
      <VaTabs v-model="activeTab" grow>
        <VaTab name="form">{{ $t('coupons.form') }}</VaTab>
        <VaTab name="info">{{ $t('coupons.info') }}</VaTab>
        <VaTab name="preview">{{ $t('coupons.preview') }}</VaTab>
      </VaTabs>

      <!-- Содержимое табов -->
      <div class="tab-content mt-4">
        <DiscountPercentageCode 
          v-if="activeTab === 'form'" 
          v-model="formData"
        />
        
        <BonusRedemptionCode 
          v-else-if="activeTab === 'info'" 
        />
        
        <CheckCode 
          v-else-if="activeTab === 'preview'" 
          :coupon-data="formData"
        />
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
import { ref, reactive } from 'vue'
import DiscountPercentageCode from './DiscountPercentageCode.vue'
import BonusRedemptionCode from './BonusRedemptionCode.vue'
import CheckCode from './CheckCode.vue'

const couponModal = ref(false)
const activeTab = ref('form')

// Данные формы
const formData = reactive({
  name: '',
  code: '',
  type: 'percent',
  value: ''
})

// Создание купона
function createCoupon() {
  console.log('Создание купона:', formData)
  // Здесь будет логика создания купона
  couponModal.value = false
}
</script>

<style scoped>
.tab-content {
  min-height: 200px;
}
</style>