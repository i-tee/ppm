<template>
  <div class="space-y-3">
    <div class="text-lg font-semibold">{{ $t('orders.title') }} №{{ order.order_number }}</div>

    <VaDivider />

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-500">{{ $t('orders.subtotal_no_discount') }}:</span> {{ formatPrice(order.order_subtotal) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.customer_discount') }}:</span> -{{ formatPrice(order.order_discount) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.actual_total') }}:</span> {{ formatPrice(order.order_total) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.agent_cashback') }}:</span> {{ formatPrice(order.cashback || 0) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.date') }}:</span> {{ formatDate(order.order_date) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.coupon') }}:</span> {{ bData.data.coupons_full.find(item => item.coupon_id === order.coupon_id).coupon_code }}</div>
    </div>

    <VaDivider />

    <div class="text-sm">
      <div class="text-gray-500 mb-1">{{ $t('orders.recipient') }}:</div>
      <div>{{ order.f_name ?? '-' }} | {{ order.city ?? '-' }}</div>
      <div>{{ $t('orders.email') }}: ***@***</div>
      <div>{{ $t('orders.phone') }}: ***</div>
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
  order: { type: Object, required: true },
  bData: { type: Object, required: true },
})

const { formatPrice, formatDate } = useBase()
</script>