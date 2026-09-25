<template>
  <div>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.statistics') }}</p>
      <p class="my-2">{{ $t('dashboard.statistics_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="hasAgent">
      <div v-if="apiData && bData">

        <!-- Фильтры: период + промокод -->
        <div class="flex flex-wrap gap-4 items-end mb-4">
          <div>
            <p class="text-sm text-secondary mb-1">{{ $t('statistics.period') }}</p>
            <VaSelect v-model="periodPreset" :options="periodOptions" value-by="value" text-by="text" class="min-w-64" />
          </div>

          <template v-if="periodPreset === 'custom'">
            <VaDateInput v-model="customStart" manual-input :label="$t('statistics.period_from')" class="w-40" />
            <VaDateInput v-model="customEnd" manual-input :label="$t('statistics.period_to')" class="w-40" />
          </template>

          <div v-if="couponOptions.length > 1">
            <p class="text-sm text-secondary mb-1">{{ $t('statistics.coupon_filter') }}</p>
            <VaSelect v-model="selectedCouponId" :options="couponOptions" value-by="value" text-by="text" class="min-w-48" />
          </div>
        </div>

        <!-- Итоги за период -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('statistics.accrued') }}</p>
            <p class="text-2xl font-bold text-primary">{{ formatPrice(totals.accrued) }}</p>
            <p v-if="accruedChangeDisplay" class="text-xs mt-1" :class="accruedChangeDisplay.cls">
              {{ accruedChangeDisplay.text }}
            </p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('statistics.orders_count') }}</p>
            <p class="text-2xl font-bold">{{ totals.ordersCount }}</p>
            <p v-if="ordersChangeDisplay" class="text-xs mt-1" :class="ordersChangeDisplay.cls">
              {{ ordersChangeDisplay.text }}
            </p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('statistics.avg_order') }}</p>
            <p class="text-2xl font-bold">{{ formatPrice(totals.avgOrder) }}</p>
            <p v-if="avgOrderChangeDisplay" class="text-xs mt-1" :class="avgOrderChangeDisplay.cls">
              {{ avgOrderChangeDisplay.text }}
            </p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('statistics.paid_out') }}</p>
            <p class="text-2xl font-bold">{{ formatPrice(paidOut) }}</p>
            <p v-if="paidOutChangeDisplay" class="text-xs mt-1" :class="paidOutChangeDisplay.cls">
              {{ paidOutChangeDisplay.text }}
            </p>
          </div>
        </div>

        <!-- Пустой период - заглушка вместо пустого графика с нулевой линией -->
        <div v-if="isPeriodEmpty" class="p-4 my-4 rounded-lg bg-gray-200 text-center">
          {{ $t('statistics.empty_period') }}
        </div>
        <template v-else>
          <!-- Шаг графика -->
          <div class="flex justify-end mb-2">
            <VaButtonToggle v-model="chartStep" :options="chartStepOptions" size="small" />
          </div>

          <StatisticsChart :labels="chart.labels" :accruals="chart.accruals" :ordersCount="chart.ordersCount" />
        </template>

        <VaDivider class="my-4" />

        <CreditsList :apiData="apiData" :bData="bData" :orders="periodOrders" :total="totals.accrued" />
      </div>

      <div v-else-if="loading" class="mt-4 pb-4">
        <VaSkeleton variant="table" :rows="5" />
      </div>

      <div v-else-if="error">{{ error }}</div>
    </div>

    <div v-else>
      <div class="p-4 my-4 rounded-lg bg-gray-200">
        <p class="my-2">{{ $t('welcomes.apps.rejected') }}</p>
        <VaDivider class="my-2" />
        <p class="my-2">{{ $t('welcomes.apps.rejected_contact') }}</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { onMounted, computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '@/stores/settings'
import { useBusinessStore } from '@/stores/business'
import CreditsList from './Agent/CreditsList.vue'
import StatisticsChart from './Statistics/StatisticsChart.vue'
import { usePartnerApplications } from '@/composables/usePartnerApplications'
import { useBase } from '@/composables/useBase'
import {
  PERIOD_PRESETS,
  getPeriodRange,
  getPreviousPeriodRange,
  filterOrdersByPeriod,
  filterOrdersByCoupon,
  computeTotals,
  computePayout,
  describeChange,
  autoChartStep,
  buildChartBuckets,
  getOrdersDateBounds,
} from '@/utils/statistics'

const { t } = useI18n()
const { hasApplication } = usePartnerApplications();
const { formatPrice } = useBase()

const settingsStore = useSettingsStore()
const businessStore = useBusinessStore()

const apiData = computed(() => settingsStore.data)
const bData = computed(() => businessStore.data ? { success: true, data: businessStore.data } : null)
const loading = computed(() => settingsStore.loading || businessStore.loading)
const error = computed(() => settingsStore.error || businessStore.error)

const hasAgent = computed(() => hasApplication(2, 2))

onMounted(() => {
  settingsStore.load()
  businessStore.load()
})

// --- Период ---

const STORAGE_KEY = 'statistics.period_preset'

function loadStoredPreset() {
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)
    return PERIOD_PRESETS.includes(stored) ? stored : '30d'
  } catch (e) {
    return '30d'
  }
}

const periodPreset = ref(loadStoredPreset())
const customStart = ref(null)
const customEnd = ref(null)

watch(periodPreset, (value) => {
  try {
    window.localStorage.setItem(STORAGE_KEY, value)
  } catch (e) {
    // localStorage недоступен (приватный режим и т.п.) - просто не запоминаем
  }
})

const periodOptions = computed(() => [
  { text: t('statistics.period_7d'), value: '7d' },
  { text: t('statistics.period_30d'), value: '30d' },
  { text: t('statistics.period_3m'), value: '3m' },
  { text: t('statistics.period_this_month'), value: 'this_month' },
  { text: t('statistics.period_last_month'), value: 'last_month' },
  { text: t('statistics.period_year'), value: 'year' },
  { text: t('statistics.period_all'), value: 'all' },
  { text: t('statistics.period_custom'), value: 'custom' },
])

const periodRange = computed(() => getPeriodRange(periodPreset.value, {
  customStart: customStart.value,
  customEnd: customEnd.value,
}))

const previousPeriodRange = computed(() => getPreviousPeriodRange(periodRange.value))

// --- Промокод ---

const selectedCouponId = ref('all')

const couponOptions = computed(() => {
  const coupons = bData.value?.data?.coupons_full || []
  return [
    { text: t('statistics.coupon_all'), value: 'all' },
    ...coupons.map((c) => ({ text: c.coupon_code, value: c.coupon_id })),
  ]
})

// --- Заказы и итоги ---

// Скрытые промокоды из статистики не исключаем (только из списка на "Промокодах").
const allOrders = computed(() => bData.value?.data?.credits?.orders || [])
const couponOrders = computed(() => filterOrdersByCoupon(allOrders.value, selectedCouponId.value))
const periodOrders = computed(() => filterOrdersByPeriod(couponOrders.value, periodRange.value))
const previousOrders = computed(() => previousPeriodRange.value
  ? filterOrdersByPeriod(couponOrders.value, previousPeriodRange.value)
  : [])

const totals = computed(() => computeTotals(periodOrders.value))
const previousTotals = computed(() => computeTotals(previousOrders.value))

const payoutRequests = computed(() => bData.value?.data?.payoutRequests?.payoutRequests || [])
const paidOut = computed(() => computePayout(payoutRequests.value, periodRange.value))
const previousPaidOut = computed(() => previousPeriodRange.value
  ? computePayout(payoutRequests.value, previousPeriodRange.value)
  : null)

// Нет заказов в периоде - показываем заглушку вместо графика/шага.
const isPeriodEmpty = computed(() => periodOrders.value.length === 0)

// Сравнение с прошлым периодом (null - нет предыдущего периода, «Всё время»).
const hasPrevious = computed(() => !!previousPeriodRange.value)
function changeFor(current, previous) {
  return describeChange(current, hasPrevious.value ? previous : null)
}

const accruedChange = computed(() => changeFor(totals.value.accrued, previousTotals.value.accrued))
const ordersChange = computed(() => changeFor(totals.value.ordersCount, previousTotals.value.ordersCount))
const avgOrderChange = computed(() => changeFor(totals.value.avgOrder, previousTotals.value.avgOrder))
const paidOutChange = computed(() => changeFor(paidOut.value, previousPaidOut.value ?? 0))

// Проценты - в русском формате (запятая, без лишнего знака у целых чисел),
// независимо от языка интерфейса - так попросил мастер-чат.
const percentFormatter = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 1 })
function formatPercent(value) {
  return `${percentFormatter.format(Math.abs(value))}%`
}

// Текст + цвет плитки сравнения: 'value' - обычный процент, 'new' - было 0,
// стало больше 0 (без процента), 'neutral' - оба периода 0 или разница
// округляется к 0 - «–» без стрелки, 'none' - сравнивать не с чем (скрыто).
function changeDisplay(change) {
  if (!change || change.kind === 'none') return null
  if (change.kind === 'new') return { text: t('statistics.change_new'), cls: 'text-success' }
  if (change.kind === 'neutral') return { text: `– ${t('statistics.vs_previous_period')}`, cls: 'text-secondary' }
  const arrow = change.percent >= 0 ? '↑' : '↓'
  const cls = change.percent >= 0 ? 'text-success' : 'text-danger'
  return { text: `${arrow} ${formatPercent(change.percent)} ${t('statistics.vs_previous_period')}`, cls }
}

const accruedChangeDisplay = computed(() => changeDisplay(accruedChange.value))
const ordersChangeDisplay = computed(() => changeDisplay(ordersChange.value))
const avgOrderChangeDisplay = computed(() => changeDisplay(avgOrderChange.value))
const paidOutChangeDisplay = computed(() => changeDisplay(paidOutChange.value))

// --- График ---

// Для «Всё время» period.start/end - null: графику нужен реальный
// диапазон - берём его по самим заказам (min/max order_date).
const chartRange = computed(() => {
  if (periodRange.value.start && periodRange.value.end) return periodRange.value
  return getOrdersDateBounds(periodOrders.value) || periodRange.value
})

const chartStep = ref(autoChartStep(chartRange.value))

// Пересчитываем шаг по умолчанию при смене периода (пользователь может
// переключить его вручную сразу после).
watch(chartRange, () => {
  chartStep.value = autoChartStep(chartRange.value)
})

const chartStepOptions = computed(() => [
  { label: t('statistics.chart_step_day'), value: 'day' },
  { label: t('statistics.chart_step_week'), value: 'week' },
  { label: t('statistics.chart_step_month'), value: 'month' },
])

const chart = computed(() => buildChartBuckets(periodOrders.value, chartRange.value, chartStep.value))
</script>
