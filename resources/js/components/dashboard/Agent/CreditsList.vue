<template>

  <div v-if="bData.data?.oldPromocodBalance?.be" class="rounded-md p-4 avi-bg-sb">
    <p>{{ t('coupons.oldbalance') }} {{ formatPrice(bData.data?.oldPromocodBalance?.summ) }}</p>
    <p class="text-gray-400 text-light-sm">{{ t('coupons.oldbalance_descr') }}</p>
  </div>

  <div>
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
      <VaDataTable ref="tableRef" :key="`table-${orders.length}-${currentPage}`" :items="pagedOrders" :columns="columns"
        :hoverable="true" :sortable="true" :no-data-html="t('coupons.credits-no_orders')" class="va-table--modern">
        <!-- Кастомный слот для кешбека -->
        <template #cell(cashback)="{ rowData }">
          {{ formatNumber(rowData.cashback || 0) }} ₽
        </template>
        <!-- Кастомный слот для даты -->
        <template #cell(order_date)="{ rowData }">
          {{ formatDate(rowData.order_date) }}
        </template>
        <!-- Слот для футера с пагинацией -->
        <template #footer>
          <div class="flex justify-end items-center mt-4">
            <!-- Только пагинация -->
            <VaPagination v-model="currentPage" :pages="totalPages" :visible-pages="5" color="primary" />
          </div>
        </template>
      </VaDataTable>
    </div>
    <!-- Показываем сообщение, если заказов нет -->
    <div v-else class="mt-4">
      {{ t('coupons.credits-no_orders') }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { useBase } from '@/composables/useBase';
import { VaDataTable, VaSkeleton, VaPagination } from 'vuestic-ui'

const { formatPrice } = useBase();

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
const perPage = ref(10) // Фиксированное количество строк на странице
const currentPage = ref(1) // Текущая страница
const tableRef = ref(null) // Ref для таблицы, чтобы управлять скроллом

// Вычисляемый массив с пагинцией
const pagedOrders = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return orders.value.slice(start, end)
})

// Общее количество страниц
const totalPages = computed(() => Math.ceil(orders.value.length / perPage.value))

// Определение колонок для таблицы
const columns = computed(() => [
  { key: 'cashback', label: t('coupons.credit'), sortable: true },
  { key: 'order_date', label: t('date.date'), sortable: true },
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
    const ordersData = props.bData?.data?.credits?.orders || []
    console.log('CreditsList orders:', ordersData.length, 'items loaded')
    if (!ordersData.length) {
      console.warn('No orders found in bData.data.credits.orders')
      initToast({
        message: t('coupons.credits-no_orders'),
        color: 'warning',
      })
    }
    orders.value = ordersData
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
