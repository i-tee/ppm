<template>
  <div>

    <p class="va-h1">{{ $t('payoutRequest.title') }}</p>
    <p>{{ $t('payoutRequest.description') }}</p>
    <p class="va-h5">{{ $t('total') }}: <b>{{ formatPrice(bData.data?.payoutRequests?.debit || 0) }}</b></p>
    <hr class="mt-4">

    <VaDataTable ref="tableRef" :key="`table-${filteredPayoutRequests.length}-${currentPage}`"
      :items="pagedPayoutRequests" :columns="columns" :hoverable="true" :loading="loading"
      :no-data-html="$t('payoutRequest.no_data')" class="cursor-pointer va-table--compact va-table--modern"
      @row:click="({ item }) => openModal(item)">
      <!-- Слот для суммы вывода -->
      <template #cell(withdrawal_amount)="{ rowData }">
        {{ formatPrice(rowData.withdrawal_amount) }}
      </template>
      <!-- Слот для полученной суммы -->
      <template #cell(received_amount)="{ rowData }">
        {{ formatPrice(rowData.received_amount || 0) }}
      </template>
      <!-- Слот для статуса с цветом -->
      <template #cell(status)="{ rowData }">
        {{ getStatusText(rowData.status) }}
      </template>
      <!-- Слот для даты создания -->
      <template #cell(created_at)="{ rowData }">
        {{ formatDate(rowData.created_at) }}
      </template>
      <!-- Слот для банка -->
      <template #cell(bank_name)="{ rowData }">
        {{ rowData.requisite?.bank_name || $t('common.n_a') }}
      </template>
      <template #footer>
        <div class="flex justify-end items-center mt-4">
          <VaPagination v-model="currentPage" :pages="totalPages" :visible-pages="3" color="primary" />
        </div>
      </template>
    </VaDataTable>
  </div>

  <!-- Модалка -->
  <VaModal v-model="showModal" :title="$t('payoutRequest.details.title')" close-button hide-default-actions
    max-width="700px">
    <PayoutRequestDetailsModal :bData="bData" :payoutRequest="selectedPayoutRequest" @close="showModal = false" />
  </VaModal>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable, VaPagination, VaModal, VaBadge } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'
import PayoutRequestDetailsModal from './PayoutRequestDetailsModal.vue'

const { t } = useI18n()
const { formatPrice, formatDate } = useBase()
const toast = useToast()

const showModal = ref(false)
const selectedPayoutRequest = ref(null)

const openModal = (item) => {
  selectedPayoutRequest.value = item
  showModal.value = true
}

// Пропсы (добавил userId и userName для мини-режима)
const props = defineProps({
  apiData: { type: Object, default: null },
  bData: { type: Object, default: null },
  refresh: { type: Number, default: 0 },
  userId: { type: Number, default: null }, // Для фильтра по партнёру
  userName: { type: String, default: '' }, // Для заголовка
})

// Реактивные переменные
const payoutRequests = ref([])
const loading = ref(false)
const error = ref(null)
const perPage = ref(10) // Меньше для мини
const currentPage = ref(1)
const tableRef = ref(null)

// Фильтр по userId (если передан — только его выплаты)
const filteredPayoutRequests = computed(() => {
  let data = props.bData?.data?.payoutRequests?.payoutRequests || props.apiData?.data || []
  if (props.userId) {
    data = data.filter(pr => pr.user_id === props.userId)
  }
  return data
})

// Пагинация
const pagedPayoutRequests = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredPayoutRequests.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredPayoutRequests.value.length / perPage.value))

// Колонки (добавил банк)
const columns = computed(() => [
  { key: 'withdrawal_amount', label: t('payoutRequest.columns.amount'), sortable: true },
  // { key: 'received_amount', label: t('payoutRequest.columns.received'), sortable: true },
  { key: 'status', label: t('payoutRequest.columns.status'), sortable: true },
  // { key: 'created_at', label: t('payoutRequest.columns.created'), sortable: true },
  // { key: 'bank_name', label: t('payoutRequest.bank_name'), sortable: true },
])

// Статусы (синхронизировано с модалкой)
const getStatusText = (status) => {
  const statuses = {
    0: t('payoutRequest.status.created'),
    10: t('payoutRequest.status.approved'),
    20: t('payoutRequest.status.paid'),
    99: t('payoutRequest.status.deleted'),
  }
  return statuses[status] || t('payoutRequest.status.unknown')
}

const getStatusColor = (status) => {
  const colors = { 0: 'warning', 10: 'info', 20: 'success', 99: 'danger' }
  return colors[status] || 'primary'
}

const maintainScrollPosition = () => {
  if (tableRef.value) {
    tableRef.value.$el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
}

const loadData = () => {
  try {
    loading.value = true
    payoutRequests.value = filteredPayoutRequests.value // Уже отфильтровано
    currentPage.value = 1
    if (!payoutRequests.value.length) {
      toast.init({
        message: props.userId ? t('payoutRequest.no_user_data') : t('payoutRequest.no_data'),
        color: 'warning',
      })
    }
  } catch (err) {
    error.value = t('errors.data_loading')
    toast.init({ message: error.value, color: 'danger' })
  } finally {
    loading.value = false
  }
}

watch(currentPage, maintainScrollPosition)
watch(() => props.refresh, loadData)
watch(filteredPayoutRequests, loadData) // Для авто-перезагрузки при смене userId

onMounted(loadData)
</script>