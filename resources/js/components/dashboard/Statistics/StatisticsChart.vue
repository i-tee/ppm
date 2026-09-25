<template>
  <div v-if="!labels.length" class="p-4 my-4 rounded-lg bg-gray-200 text-center">
    {{ $t('statistics.empty_period') }}
  </div>
  <div v-else class="avi-statistics-chart">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  BarElement,
  BarController,
  LineElement,
  LineController,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js'
import { useBase } from '@/composables/useBase'

// Регистрируем только то, что реально используем (bar + line на общем
// графике) - не registerables целиком, чтобы не тащить лишнее в чанк.
ChartJS.register(BarElement, BarController, LineElement, LineController, PointElement, CategoryScale, LinearScale, Tooltip, Legend)

const { t } = useI18n()
const { formatPrice } = useBase()

const props = defineProps({
  labels: {
    type: Array,
    default: () => [],
  },
  accruals: {
    type: Array,
    default: () => [],
  },
  ordersCount: {
    type: Array,
    default: () => [],
  },
})

// Цвета - из палитры Vuestic (primary + спокойный второй), не кислотные.
// Canvas не умеет var(...) напрямую - читаем computed style один раз.
const primaryColor = ref('#154ec1')
const secondaryColor = ref('#64748b')

onMounted(() => {
  const styles = getComputedStyle(document.documentElement)
  primaryColor.value = styles.getPropertyValue('--va-primary').trim() || primaryColor.value
  const slate = styles.getPropertyValue('--va-slate-500').trim()
  secondaryColor.value = slate || primaryColor.value
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      type: 'bar',
      label: t('statistics.chart_accruals'),
      data: props.accruals,
      backgroundColor: primaryColor.value,
      borderRadius: 4,
      yAxisID: 'y',
      order: 2,
    },
    {
      type: 'line',
      label: t('statistics.chart_orders'),
      data: props.ordersCount,
      borderColor: secondaryColor.value,
      backgroundColor: secondaryColor.value,
      pointRadius: 3,
      tension: 0.3,
      yAxisID: 'y1',
      order: 1,
    },
  ],
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { position: 'top' },
    tooltip: {
      callbacks: {
        label(ctx) {
          if (ctx.dataset.yAxisID === 'y') {
            return `${ctx.dataset.label}: ${formatPrice(ctx.parsed.y)}`
          }
          return `${ctx.dataset.label}: ${ctx.parsed.y}`
        },
      },
    },
  },
  scales: {
    y: {
      type: 'linear',
      position: 'left',
      beginAtZero: true,
      ticks: {
        callback: (value) => formatPrice(value),
      },
    },
    y1: {
      type: 'linear',
      position: 'right',
      beginAtZero: true,
      grid: { drawOnChartArea: false },
      ticks: { precision: 0 },
    },
  },
}))
</script>

<style scoped>
.avi-statistics-chart {
  position: relative;
  width: 100%;
  height: 320px;
}
</style>
