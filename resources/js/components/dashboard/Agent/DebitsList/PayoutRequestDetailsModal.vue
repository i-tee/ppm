<template>
  <div class="space-y-4">
    <div class="text-lg font-semibold">{{ $t('payoutRequest.details.title') }} ID: {{ payoutRequest.id }}</div>

    <VaDivider />

    <!-- Основные суммы и статус -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="space-y-2">
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.amount') }}: </span> {{ formatPrice(payoutRequest.withdrawal_amount) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.received') }}: </span> {{ formatPrice(payoutRequest.received_amount || 0) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.commission_amount') }}: </span> {{ formatPrice(payoutRequest.commission_amount || 0) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.commission_percentage') }}: </span> {{ payoutRequest.commission_percentage || 0 }}%</div>
      </div>
      <div class="space-y-2">
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.status') }}: </span> <VaBadge :color="getStatusColor(payoutRequest.status)" small rounded>{{ getStatusText(payoutRequest.status) }}</VaBadge></div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.created') }}: </span> {{ formatDate(payoutRequest.created_at) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.fields.updated') }}: </span> {{ formatDate(payoutRequest.updated_at) }}</div>
        <div v-if="payoutRequest.approver"><span class="text-gray-500">{{ $t('payoutRequest.fields.approver') }}: </span> {{ payoutRequest.approver.name || $t('common.n_a') }}</div>
      </div>
    </div>

    <!-- Реквизиты -->
    <VaDivider />
    <div class="bg-gray-50 p-3 rounded">
      <h6 class="font-medium mb-2">{{ $t('payoutRequest.sections.requisite') }}</h6>
      <div class="text-sm space-y-1">
        <div>{{ $t('payoutRequest.fields.bank_name') }}: {{ payoutRequest.requisite?.bank_name || $t('common.n_a') }}</div>
        <div v-if="payoutRequest.requisite?.bank_account_number">{{ $t('payoutRequest.fields.account') }}: {{ payoutRequest.requisite.bank_account_number }}</div>
        <div v-if="payoutRequest.requisite?.full_name">{{ $t('payoutRequest.fields.full_name') }}: {{ payoutRequest.requisite.full_name }}</div>
      </div>
    </div>

    <!-- Заметка и чек -->
    <VaDivider v-if="payoutRequest.note || payoutRequest.proof_link" />
    <div v-if="payoutRequest.note || payoutRequest.proof_link" class="space-y-2">
      <div v-if="payoutRequest.note"><span class="text-gray-500">{{ $t('payoutRequest.fields.note') }}: </span> {{ payoutRequest.note }}</div>
      <div v-if="payoutRequest.proof_link">
        <span class="text-gray-500">{{ $t('payoutRequest.fields.proof') }}: </span>
        <a :href="payoutRequest.proof_link" target="_blank" class="text-blue-500 underline hover:text-blue-700">
          {{ $t('payoutRequest.actions.view_proof') }}
        </a>
      </div>
    </div>

    <!-- Кнопка закрытия -->
    <div class="flex justify-end">
      <VaButton @click="$emit('close')" color="primary">{{ $t('common.close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { VaDivider, VaButton, VaBadge } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'

const { t } = useI18n()
const { formatPrice, formatDate } = useBase()

defineProps({
  payoutRequest: { type: Object, required: true },
  bData: { type: Object, default: null },
})

defineEmits(['close'])

const getStatusText = (status) => {
  const statuses = {
    0: t('payoutRequest.status.created'),
    1: t('payoutRequest.status.approved'),
    2: t('payoutRequest.status.paid'),
    99: t('payoutRequest.status.deleted'),
  }
  return statuses[status] || t('payoutRequest.status.unknown')
}

const getStatusColor = (status) => {
  const colors = {
    0: 'warning',
    1: 'info',
    2: 'success',
    99: 'danger',
  }
  return colors[status] || 'gray'
}
</script>