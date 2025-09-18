<template>
  <div>
    <p class="va-h1">{{ t('debits.title') }}</p>
    <VaDataTable
      :items="withdrawals"
      :columns="columns"
      :hoverable="true"
      :loading="loading"
      :no-data-html="t('debits.no_data')"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable } from 'vuestic-ui'

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
const withdrawals = ref([])
const loading = ref(false)
const error = ref(null)

// Определение колонок для таблицы
const columns = computed(() => [
  { key: 'id', label: t('debits.columns.id'), sortable: true },
  { key: 'recipient', label: t('debits.columns.recipient'), sortable: true },
  { key: 'phone', label: t('debits.columns.phone'), sortable: true },
  { key: 'date_exec', label: t('debits.columns.date_exec'), sortable: true },
  { key: 'summ', label: t('debits.columns.summ'), sortable: true },
  { key: 'comment', label: t('debits.columns.comment'), sortable: true },
  { key: 'coupons', label: t('debits.columns.coupons'), sortable: true },
])

// Загрузка данных
const loadData = () => {
  try {
    loading.value = true
    const data = props.bData?.data?.withdrawals?.withdrawals || []
    if (!data.length) {
      console.warn('No withdrawals found in bData.data.withdrawals.withdrawals')
      initToast({
        message: t('debits.no_data'),
        color: 'warning',
      })
    }
    withdrawals.value = data
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

// Хук монтирования
onMounted(loadData)

// Отслеживание изменений в props.refresh для обновления данных
watch(() => props.refresh, loadData)
</script>