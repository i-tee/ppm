<template>
  <div class="space-y-4">
    <div class="text-lg font-semibold">{{ $t('payoutRequest.details.title') }} ID: {{ payoutRequest.id }}</div>

    <VaDivider />

    <!-- Основные суммы и статус -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="space-y-2">
        <div><span class="text-gray-500">{{ $t('payoutRequest.amount') }}: </span> {{
          formatPrice(payoutRequest.withdrawal_amount) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.received') }}: </span> {{
          formatPrice(payoutRequest.received_amount || 0) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.commission_amount') }}: </span> {{
          formatPrice(payoutRequest.commission_amount || 0) }}</div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.commission_percentage') }}: </span> {{
          payoutRequest.commission_percentage || 0 }}%</div>
      </div>
      <div class="space-y-2">
        <div>
          <span class="text-gray-500">{{ $t('payoutRequest.status.status') }}: </span>
          <VaBadge :text="getStatusText(payoutRequest.status)" :color="getStatusColor(payoutRequest.status)"
            class="mr-2" />
        </div>
        <div><span class="text-gray-500">{{ $t('date.created') }}: </span> {{ formatDate(payoutRequest.created_at) }}
        </div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.updated') }}: </span> {{
          formatDate(payoutRequest.updated_at) }}</div>
        <div v-if="payoutRequest.approver"><span class="text-gray-500">{{ $t('payoutRequest.approver') }}: </span> {{
          payoutRequest.approver.name || $t('common.n_a') }}</div>
      </div>
    </div>

    <!-- Реквизиты -->
    <VaDivider />
    <div class="bg-gray-50 p-3 rounded">
      <h6 class="font-medium mb-2">{{ $t('dashboard.requisite') }}</h6>
      <div class="text-sm space-y-1">
        <div>{{ $t('payoutRequest.bank_name') }}: {{ payoutRequest.requisite?.bank_name || $t('common.n_a') }}</div>
        <div v-if="payoutRequest.requisite?.bank_account_number">{{ $t('payoutRequest.account') }}: {{
          payoutRequest.requisite.bank_account_number }}</div>
        <div v-if="payoutRequest.requisite?.full_name">{{ $t('payoutRequest.full_name') }}: {{
          payoutRequest.requisite.full_name }}</div>
      </div>
    </div>

    <!-- Заметка и пруф -->
    <VaDivider v-if="payoutRequest.note || payoutRequest.proof_link" />
    <div v-if="payoutRequest.note || payoutRequest.proof_link" class="space-y-2">
      <div v-if="payoutRequest.note"><span class="text-gray-500">{{ $t('payoutRequest.note') }}: </span> {{
        payoutRequest.note }}</div>
      <div v-if="payoutRequest.proof_link">
        <span class="text-gray-500">{{ $t('payoutRequest.view_proof') }}: </span>
        <a :href="payoutRequest.proof_link" target="_blank" class="text-blue-500 underline hover:text-blue-700">
          {{ $t('payoutRequest.proof_link_label') }}
        </a>
      </div>
    </div>

    <!-- чек -->
    <VaDivider v-if="payoutRequest.ticket_proof" />
    <div v-if="payoutRequest.ticket_proof" class="space-y-2">
      <div>
        <span class="text-gray-500">{{ $t('payoutRequest.user_ticket') }}: </span>
        <a :href="`/storage/${payoutRequest.ticket_proof}`" target="_blank" class="text-blue-500 underline">
          {{ $t('payoutRequest.view_user_proof') }}
        </a>
      </div>
    </div>

    <!-- Кнопка закрытия -->
    <div class="flex justify-end">
      <VaButton @click="$emit('close')" color="primary">{{ $t('close') }}</VaButton>
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
    10: t('payoutRequest.status.approved'),
    14: t('payoutRequest.status.paid_whait_ticket'),
    16: t('payoutRequest.status.ticket_uploaded'),
    20: t('payoutRequest.status.paid'),
    50: t('payoutRequest.status.cancelled'),
    99: t('payoutRequest.status.deleted'),
  }
  return statuses[status] || t('payoutRequest.status.unknown')
}

const getStatusColor = (status) => {
  const colors = {
    0: 'warning',
    10: 'info',
    14: 'primary',
    16: 'primary',
    20: 'success',
    50: 'danger',
    99: 'danger',
  }
  return colors[status] || 'gray'
}
</script>