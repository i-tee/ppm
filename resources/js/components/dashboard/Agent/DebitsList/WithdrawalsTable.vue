<template>
  <div>
    <p class="va-h1">{{ $t('payoutRequest.title') }}</p>
    <p>{{ $t('payoutRequest.description') }}</p>
    <p class="va-h5">{{ $t('total') }}: <b>{{ formatPrice(bData.data?.payoutRequests?.totalAmount || 0) }}</b></p>
    <hr class="mt-4">

    <VaDataTable ref="tableRef" :key="`table-${payoutRequests.length}-${currentPage}`" :items="pagedPayoutRequests"
      :columns="columns" :hoverable="true" :loading="loading" :no-data-html="$t('payoutRequest.no_data')"
      class="cursor-pointer va-table--modern" @row:click="({ item }) => openModal(item)">
      <!-- Слот для суммы вывода -->
      <template #cell(withdrawal_amount)="{ rowData }">
        {{ formatPrice(rowData.withdrawal_amount) }}
      </template>
      <!-- Слот для полученной суммы -->
      <template #cell(received_amount)="{ rowData }">
        {{ formatPrice(rowData.received_amount || 0) }}
      </template>
      <!-- Слот для статуса -->
      <template #cell(status)="{ rowData }">
        {{ getStatusText(rowData.status) }}
      </template>
      <!-- Слот для даты создания -->
      <template #cell(created_at)="{ rowData }">
        {{ formatDate(rowData.created_at) }}
      </template>
      <template #footer>
        <div class="flex justify-end items-center mt-4">
          <VaPagination v-model="currentPage" :pages="totalPages" :visible-pages="5" color="primary" />
        </div>
      </template>
    </VaDataTable>
  </div>

  <!-- Модалка -->
  <VaModal v-model="showModal" :title="$t('payoutRequest.details.title')" close-button hide-default-actions max-width="700px">
    <PayoutRequestDetailsModal :bData="bData" :payoutRequest="selectedPayoutRequest" @close="showModal = false" />
  </VaModal>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable, VaPagination, VaModal } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'
import PayoutRequestDetailsModal from './PayoutRequestDetailsModal.vue' // В той же папке

const { formatPrice, formatDate } = useBase()

const showModal = ref(false)
const selectedPayoutRequest = ref(null)

const openModal = (item) => {
  selectedPayoutRequest.value = item
  showModal.value = true
}

// Инициализация локализации и уведомлений
const { t } = useI18n()
const { init: initToast } = useToast()

// Объявляем и получаем пропсы
const props = defineProps({
  apiData: {
    type: Object,
    default: null,
  },
  bData: {
    type: Object,
    default: null,
  },
  refresh: {
    type: Number,
    default: 0,
  },
})

// Реактивные переменные
const payoutRequests = ref([])
const loading = ref(false)
const error = ref(null)
const perPage = ref(20)
const currentPage = ref(1)
const tableRef = ref(null)

// Вычисляемый массив с пагинацией
const pagedPayoutRequests = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return payoutRequests.value.slice(start, end)
})

// Общее количество страниц
const totalPages = computed(() => Math.ceil(payoutRequests.value.length / perPage.value))

// Определение колонок для таблицы (добавь sortable, если нужно)
const columns = computed(() => [
  { key: 'withdrawal_amount', label: t('payoutRequest.columns.amount'), sortable: true },
  { key: 'received_amount', label: t('payoutRequest.columns.received'), sortable: true },
  { key: 'status', label: t('payoutRequest.columns.status'), sortable: true },
  { key: 'created_at', label: t('payoutRequest.columns.created'), sortable: true },
])

// Map статусов (аналогично модалке)
const getStatusText = (status) => {
  const statuses = {
    0: t('payoutRequest.status.created'),
    1: t('payoutRequest.status.approved'),
    2: t('payoutRequest.status.paid'),
  }
  return statuses[status] || 'Неизвестно'
}

// Сохранение позиции скролла
const maintainScrollPosition = () => {
  if (tableRef.value) {
    tableRef.value.$el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
}

// Загрузка данных из bData (аналогично Olders.vue)
const loadData = () => {
  try {
    loading.value = true
    const data = props.bData?.data?.payoutRequests?.payoutRequests || [] // Из API
    if (!data.length) {
      initToast({
        message: t('payoutRequest.no_data'),
        color: 'warning',
      })
    }
    payoutRequests.value = data
    currentPage.value = 1
  } catch (err) {
    error.value = t('errors.data_loading')
    initToast({
      message: error.value,
      color: 'danger',
    })
  } finally {
    loading.value = false
  }
}

// Watch'ы
watch(currentPage, maintainScrollPosition)
watch(() => props.refresh, loadData)

onMounted(loadData)
</script>