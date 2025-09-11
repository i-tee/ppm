<template>
  <div class="preview-card p-4 bg-gray-100 rounded">
    <h4 class="va-h4 mb-2">{{ $t('coupons.preview_title') }}</h4>
    
    <div v-if="couponData.name">
      <p><strong>{{ $t('coupons.name') }}:</strong> {{ couponData.name }}</p>
      <p><strong>{{ $t('coupons.code') }}:</strong> {{ couponData.code }}</p>
      <p><strong>{{ $t('coupons.type') }}:</strong> {{ getDiscountTypeName(couponData.type) }}</p>
      <p><strong>{{ $t('coupons.value') }}:</strong> {{ couponData.value }}</p>
    </div>
    
    <div v-else>
      <p class="text-gray-500">{{ $t('coupons.fill_form_preview') }}</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  couponData: {
    type: Object,
    default: () => ({})
  }
})

// Получение названия типа скидки
function getDiscountTypeName(type) {
  const types = {
    'percent': 'coupons.types.percent',
    'fixed': 'coupons.types.fixed'
  }
  return props.$t(types[type]) || type
}
</script>

<style scoped>
.preview-card {
  min-height: 150px;
}
</style>