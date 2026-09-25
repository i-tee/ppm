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
// --va-slate-500 в этой теме не существует (Vuestic инжектит CSS-переменные
// только для именованных цветов пресета - primary/secondary/success/...,
// не для палитры variables из vuestic.config.js) - берём --va-secondary,
// реально существующую и заметно отличимую от primary.
const primaryColor = ref('#154ec1')
const secondaryColor = ref('#767c88')

onMounted(() => {
  const styles = getComputedStyle(document.documentElement)
  primaryColor.value = styles.getPropertyValue('--va-primary').trim() || primaryColor.value
  secondaryColor.value = styles.getPropertyValue('--va-secondary').trim() || secondaryColor.value
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
      // Прямые отрезки между точками - без сглаживания, чтобы линия не
      // рисовала «горбы» между реальными значениями (0.3 давало заметный
      // перегиб выше/ниже точек).
      tension: 0,
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
    x: {
      // Отступ по краям категорий - иначе крайние столбцы (первый/последний)
      // обрезаются по краю области графика.
      offset: true,
    },
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
