<template>
  <div class="space-y-3">
    <div class="text-lg font-semibold">{{ $t('payoutRequest.details.title') }} ID: {{ payoutRequest.id }}</div>

    <VaDivider />

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.amount') }}: </span> {{ formatPrice(payoutRequest.withdrawal_amount) }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.received') }}: </span> {{ formatPrice(payoutRequest.received_amount || 0) }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.commission') }}: </span> {{ formatPrice(payoutRequest.commission_amount || 0) }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.status') }}: </span> {{ getStatusText(payoutRequest.status) }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.created') }}: </span> {{ formatDate(payoutRequest.created_at) }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.requisite') }}: </span> {{ payoutRequest.requisite?.bank_name || 'N/A' }}</div>
      <div><span class="text-gray-500">{{ $t('payoutRequest.fields.note') }}: </span> {{ payoutRequest.note || 'N/A' }}</div>
      <div v-if="payoutRequest.proof_link"><span class="text-gray-500">{{ $t('payoutRequest.fields.proof') }}: </span> <a :href="payoutRequest.proof_link" target="_blank">Ссылка</a></div>
    </div>

    <VaDivider />

    <div class="text-right">
      <VaButton @click="$emit('close')">{{ $t('common.close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VaDivider, VaButton } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'

defineProps({
  payoutRequest: { type: Object, required: true },
  bData: { type: Object, required: true },
})

const { formatPrice, formatDate } = useBase()

// Map статусов (добавь в i18n: "status": { "created": "Создана", "approved": "Одобрена", "paid": "Выплачена" })
const getStatusText = (status) => {
  const statuses = {
    0: $t('payoutRequest.status.created'),
    1: $t('payoutRequest.status.approved'),
    2: $t('payoutRequest.status.paid'),
  }
  return statuses[status] || 'Неизвестно'
}
</script>