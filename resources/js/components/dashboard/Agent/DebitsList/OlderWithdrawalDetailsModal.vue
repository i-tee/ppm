<template>
  <div class="space-y-3">
    <div class="text-lg font-semibold">{{ $t('payout') }} ID:{{ withdrawal.id }}</div>

    <VaDivider />

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-500">{{ $t('summ') }}: </span> {{ formatPrice(withdrawal.summ) }}</div>
      <div><span class="text-gray-500">{{ $t('date.date') }}: </span> {{ formatDate(withdrawal.date_exec) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.recipient') }}: </span> {{ (withdrawal.recipient) }}</div>
      <div><span class="text-gray-500">{{ $t('orders.phone') }}: </span> {{ (withdrawal.phone) }}</div>
    </div>

    <VaDivider />

    <div class="text-right">
      <VaButton @click="$emit('close')">{{ $t('close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { useBase } from '@/composables/useBase'
import { VaDivider, VaButton } from 'vuestic-ui'

defineProps({
  withdrawal: { type: Object, required: true },
  bData: { type: Object, required: true },
})

const { formatPrice, formatDate } = useBase()
</script>