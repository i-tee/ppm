<template>
  <!-- Основной контейнер компонента -->
  <div>
    <!-- Показываем индикатор загрузки, если loading = true -->
    <div v-if="loading" class="mt-4">
      <VaAlert color="secondary">{{ $t('loading_data') }}</VaAlert>
    </div>
    <!-- Показываем ошибку, если она есть -->
    <div v-else-if="error" class="mt-4 text-danger">
      {{ error }}
    </div>
    <!-- Показываем список купонов, если они есть -->
    <div v-else-if="coupons.length" class="mt-4">
      <va-list>
        <!-- Перебираем массив coupons, отображая каждый купон -->
        <va-list-item v-for="coupon in coupons" :key="coupon.coupon_id">
          <!-- Показываем код купона и его значение (процент или бонусы) -->
          {{ coupon.coupon_code }} ({{ coupon.coupon_value }} {{ coupon.coupon_type === 0 ? '%' : $t('coupons.currency')
          }})
        </va-list-item>
      </va-list>
    </div>
    <!-- Показываем сообщение, если купонов нет -->
    <div v-else class="mt-4">
      {{ $t('coupons.no_coupons') }}
    </div>
  </div>
</template>

<script setup>
// Импорты: подключаем зависимости Vue и проекта
import { ref, onMounted, watch } from 'vue' // ref для реактивности, onMounted для хука, watch для отслеживания изменений
import { useI18n } from 'vue-i18n' // Для локализации текстов
import { useToast } from 'vuestic-ui' // Для уведомлений Vuestic UI
import { getUserCoupons } from '@/api/coupons' // Функция API для загрузки купонов

// Инициализация локализации и уведомлений
const { t } = useI18n() // Функция t для получения переведённых строк
const { init: initToast } = useToast() // Инициализация уведомлений

// Объявляем и получаем пропсы
const { apiData, bData, refresh } = defineProps({
  apiData: {
    type: Object, // Тип пропса — объект или null
    default: null // Значение по умолчанию
  },
  bData: {
    type: Object, // Тип пропса — объект или null
    default: null // Значение по умолчанию
  },
  refresh: {
    type: Number, // Пропс для принудительного обновления
    default: 0
  }
})

// Реактивные переменные: данные, отслеживаемые Vue для обновления интерфейса
const coupons = ref([]) // Массив купонов, загружаемых из API
const loading = ref(false) // Состояние загрузки (true во время запроса)
const error = ref(null) // Сообщение об ошибке, если запрос не удался

// Выводим apiData и bData в консоль при монтировании
onMounted(() => {
  console.log('apiData:', apiData)
})

// Отслеживаем изменения bData и выводим в консоль
watch(
  () => bData, (newValue) => {
    console.log('bData:', newValue.data)
  }
)

// Отслеживаем изменения refresh для перезагрузки купонов
watch(() => refresh, () => {
  console.log('Refresh triggered, reloading coupons')
  loadAllData()
})

// Функция загрузки купонов
const loadCoupons = async () => {
  try {
    // Устанавливаем состояние загрузки
    loading.value = true
    error.value = null

    // Выполняем запрос к API для получения купонов
    const response = await getUserCoupons()

    // Проверяем успешность ответа
    if (response.success) {
      // Сохраняем купоны в реактивную переменную
      coupons.value = response.coupons
      // Если купонов нет, показываем уведомление
      if (response.count === 0) {
        initToast({
          message: t('coupons.no_coupons'),
          color: 'warning'
        })
      }
    } else {
      // Если ответ не успешен, выбрасываем ошибку
      throw new Error(response.error || t('errors.load_failed'))
    }
  } catch (err) {
    // Сохраняем ошибку и показываем уведомление
    error.value = err.message
    initToast({
      message: err.message,
      color: 'danger'
    })
    console.error('Ошибка загрузки купонов:', err)
  } finally {
    // Сбрасываем состояние загрузки
    loading.value = false
  }
}

// Основная функция для загрузки всех данных
const loadAllData = async () => {
  try {
    // Выполняем загрузку купонов
    await Promise.all([loadCoupons()])
  } catch (err) {
    // Обрабатываем ошибки, если Promise.all не удался
    console.error('Ошибка загрузки данных:', err)
    initToast({
      message: t('errors.load_failed'),
      color: 'danger'
    })
  }
}

// Хук монтирования: вызывается, когда компонент добавляется в DOM
onMounted(loadAllData)
</script>