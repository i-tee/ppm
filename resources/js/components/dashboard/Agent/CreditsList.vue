<template>
  <div>
    <!-- Заголовок компонента -->
    <p class="va-h1">{{ t('credits.title') }}</p>
    <!-- Показываем индикатор загрузки, если loading = true -->
    <div v-if="loading" class="mt-4 pb-4">
      <VaSkeleton variant="table" :rows="5" />
    </div>
    <!-- Показываем ошибку, если она есть -->
    <div v-else-if="error" class="mt-4 text-danger">
      {{ error }}
    </div>
    <!-- Показываем таблицу заказов, если они есть -->
    <div v-else-if="orders.length" class="mt-4">
      <VaDataTable
        :items="orders"
        :columns="columns"
        :hoverable="true"
        :per-page="10"
        :sortable="true"
        class="va-table--modern"
      >
        <!-- Кастомный слот для имени -->
        <template #cell(name)="{ rowData }">
          {{ rowData.f_name }} {{ rowData.l_name }}
        </template>
        <!-- Кастомный слот для города -->
        <template #cell(city)="{ rowData }">
          {{ rowData.city || t('common.unknown') }}
        </template>
        <!-- Кастомный слот для суммы заказа -->
        <template #cell(order_total)="{ rowData }">
          {{ formatNumber(rowData.order_total) }} ₽
        </template>
        <!-- Кастомный слот для кешбека -->
        <template #cell(cashback)="{ rowData }">
          {{ formatNumber(rowData.cashback || 0) }} ₽
        </template>
        <!-- Кастомный слот для даты -->
        <template #cell(order_date)="{ rowData }">
          {{ formatDate(rowData.order_date) }}
        </template>
      </VaDataTable>
    </div>
    <!-- Показываем сообщение, если заказов нет -->
    <div v-else class="mt-4">
      {{ t('credits.no_orders') }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable, VaSkeleton } from 'vuestic-ui'

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
const orders = ref([])
const loading = ref(false)
const error = ref(null)

// Определение колонок для таблицы
const columns = computed(() => [
  { key: 'name', label: t('credits.columns.name'), sortable: true },
  { key: 'city', label: t('credits.columns.city'), sortable: true },
  { key: 'order_total', label: t('credits.columns.total'), sortable: true },
  { key: 'cashback', label: t('credits.columns.cashback'), sortable: true },
  { key: 'order_date', label: t('credits.columns.date'), sortable: true },
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

// Загрузка данных
const loadData = () => {
  try {
    loading.value = true
    const ordersData = props.bData?.data?.credits?.orders || []
    console.log('CreditsList orders:', ordersData)
    if (!ordersData.length) {
      console.warn('No orders found in bData.data.credits.orders')
      initToast({
        message: t('credits.no_orders'),
        color: 'warning',
      })
    }
    orders.value = ordersData
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