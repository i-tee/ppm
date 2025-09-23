<template>
  <div class="space-y-3">
    <div class="text-lg font-semibold">Заказ №{{ order.order_number }}</div>

    <VaDivider />

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-500">Сумма без скидки:</span> {{ formatPrice(order.order_subtotal) }}</div>
      <div><span class="text-gray-500">Скидка покупателя:</span> -{{ formatPrice(order.order_discount) }}</div>
      <div><span class="text-gray-500">Фактическая сумма:</span> {{ formatPrice(order.order_total) }}</div>
      <div><span class="text-gray-500">Кешбэк агента:</span> {{ formatPrice(order.cashback || 0) }}</div>
      <div><span class="text-gray-500">Дата:</span> {{ formatDate(order.order_date) }}</div>
      <div><span class="text-gray-500">Промокод:</span> {{ bData.data.coupons_full.find(item => item.coupon_id === order.coupon_id).coupon_code }}</div>
    </div>

    <VaDivider />

    <div class="text-sm">
      <div class="text-gray-500 mb-1">Получатель:</div>
      <div>{{ order.l_name }} {{ order.f_name }} {{ order.m_name }}</div>
      <div>{{ order.city }}</div>
      <div>E-mail: ***@***</div>
      <div>Телефон: ***</div>
    </div>

    <VaDivider />

    <div class="text-right">
      <VaButton @click="$emit('close')">Закрыть</VaButton>
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