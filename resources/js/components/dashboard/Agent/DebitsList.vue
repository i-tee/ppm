<template>
  <div>
    <p class="va-h1">{{ t('debits.title') }}</p>
    <VaDataTable
      ref="tableRef"
      :key="`table-${withdrawals.length}-${currentPage}`"
      :items="pagedWithdrawals"
      :columns="columns"
      :hoverable="true"
      :loading="loading"
      :no-data-html="t('debits.no_data')"
      class="va-table--modern"
    >
      <template #footer>
        <div class="flex justify-end items-center mt-4">
          <VaPagination
            v-model="currentPage"
            :pages="totalPages"
            :visible-pages="5"
            color="primary"
          />
        </div>
      </template>
    </VaDataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable, VaPagination } from 'vuestic-ui'

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
const perPage = ref(20) // Фиксированное количество строк на страницу (20)
const currentPage = ref(1) // Текущая страница
const tableRef = ref(null) // Ref для таблицы, чтобы управлять скроллом

// Вычисляемый массив с пагинцией
const pagedWithdrawals = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return withdrawals.value.slice(start, end)
})

// Общее количество страниц
const totalPages = computed(() => Math.ceil(withdrawals.value.length / perPage.value))

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

// Функция форматирования даты
const formatDate = (dateString) => {
  if (!dateString) return t('common.unknown')
  return new Date(dateString).toLocaleDateString('ru-RU')
}

// Функция форматирования числа
const formatNumber = (value) => {
  return Number(value).toLocaleString('ru-RU')
}

// Сохранение позиции скролла при смене страницы
const maintainScrollPosition = () => {
  if (tableRef.value) {
    tableRef.value.$el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
}

// Загрузка данных
const loadData = () => {
  try {
    loading.value = true
    const data = props.bData?.data?.withdrawals?.withdrawals || []
    console.log('DebitsList withdrawals:', data.length, 'items loaded')
    if (!data.length) {
      console.warn('No withdrawals found in bData.data.withdrawals.withdrawals')
      initToast({
        message: t('debits.no_data'),
        color: 'warning',
      })
    }
    withdrawals.value = data
    // Сброс пагинации после загрузки
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

// Отслеживание изменения currentPage
watch(currentPage, (newVal, oldVal) => {
  console.log('currentPage changed:', newVal, 'from', oldVal)
  maintainScrollPosition() // Сохраняем позицию скролла
})

// Хук монтирования
onMounted(loadData)

// Отслеживание изменений в props.refresh для обновления данных
watch(() => props.refresh, loadData)
</script>