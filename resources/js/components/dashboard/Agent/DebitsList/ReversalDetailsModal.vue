<template>
  <div class="space-y-3">
    <div class="text-lg font-semibold">{{ $t('orders.title') }} №{{ reversal.order_number }}</div>

    <VaDivider />

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-500">{{ $t('orders.subtotal_no_discount') }}:</span> {{ formatPrice(reversal.order?.subtotal) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.customer_discount') }}:</span> -{{ formatPrice(reversal.order?.discount) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.actual_total') }}:</span> {{ formatPrice(reversal.order?.total) }}</div>
      <div><span class="text-gray-500">{{ $t('coupons.reversal_amount') }}:</span> -{{ formatPrice(Math.abs(reversal.amount || 0)) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.date') }}:</span> {{ formatDate(reversal.date) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.coupon') }}:</span> {{ reversal.coupon_code }}</div>
    </div>

    <VaDivider />

    <div class="text-right">
      <VaButton @click="$emit('close')">{{ $t('orders.close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { useBase } from '@/composables/useBase'
import { VaDivider, VaButton } from 'vuestic-ui'

defineProps({
  reversal: { type: Object, required: true },
})

const { formatPrice, formatDate } = useBase()
</script>
