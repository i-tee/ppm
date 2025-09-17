<template>
  <!-- Основной контейнер компонента с отступами -->
  <div class="agent-layout">
    <!-- Показываем контент, если данные загружены (apiData не null) -->
    <div v-if="apiData">
      <!-- Заголовок с локализацией для типа сотрудничества "агент" -->
      <h1 class="va-h4 my-1">{{ $t('partners.cooperation_types.agent.title') }}</h1>
      <!-- Описание пользовательского соглашения с ссылкой на контракт -->
      <p>
        {{ $t('user_agreement.description') }}
        <a target="_blank" :href="selectedCooperationType.contract_url">
          {{ $t('user_agreement.link_name') }}
        </a>
      </p>

      <!-- Карточка Vuestic UI для отображения списка купонов -->
      <VaCard class="my-4 pa-4">
        <div>
          <!-- Заголовок для списка купонов -->
          <h2 class="va-h4">{{ $t('coupons.title') }}</h2>
        </div>

        <!-- Компонент списка купонов -->
        <div class="my-2">
          <CouponsList :apiData="apiData" :bData="bData" :refresh="refreshKey"/>
        </div>

        <!-- Кнопка для создания нового купона, вызывает fetchAllData при создании -->
        <div class="text-end my-2">
          <CreateCoupon :apiData="apiData" :bData="bData" @coupon-created="handleCouponCreated" />
        </div>
      </VaCard>
    </div>
    <!-- Показываем предупреждение, если данные загружаются -->
    <div v-else-if="loading">
      <VaAlert color="warning">{{ $t('loading_data') }}</VaAlert>
    </div>
    <!-- Показываем ошибку, если загрузка не удалась -->
    <div v-else-if="error">{{ error }}</div>
  </div>
</template>

<script setup>
// Импорты: подключаем необходимые зависимости
import { ref, onMounted, computed } from 'vue' // Основные функции Vue 3
import axios from 'axios' // Библиотека для HTTP-запросов
import { useAuthStore } from '@/stores/auth' // Хранилище Pinia для авторизации
import { useToast } from 'vuestic-ui' // Уведомления Vuestic UI
import CouponsList from './Agent/CouponsList.vue' // Компонент списка купонов
import CreateCoupon from './Agent/CreateCoupon.vue' // Компонент создания купона
import { getBusinessData } from '@/api/coupons' // Функция для загрузки бизнес-данных

// Реактивные переменные: данные, которые Vue отслеживает для автоматического обновления UI
const apiData = ref(null) // Данные с API /api/ps (например, типы сотрудничества)
const bData = ref(null) // Бизнес-данные, загружаемые через getBusinessData (объект)
const loading = ref(true) // Состояние загрузки (true, пока данные не загружены)
const error = ref(null) // Хранит сообщение об ошибке, если загрузка не удалась
const refreshKey = ref(0) // Добавляем реактивную переменную для принудительного обновления CouponsList

// Хранилище авторизации и уведомления
const authStore = useAuthStore() // Получаем токен авторизации из Pinia
const { init: initToast } = useToast() // Инициализация уведомлений Vuestic UI

// Вычисляемые свойства: реактивные вычисления на основе данных
const selectedCooperationType = computed(() => {
  // Ищем объект с id = 2 в массиве cooperation_types
  if (!apiData.value?.cooperation_types) return null
  return apiData.value.cooperation_types.find(type => type.id === 2)
})


// Добавляем обработчик для события coupon-created
function handleCouponCreated(response) {
  console.log('Событие coupon-created сработало:', response);
  fetchAllData();
  fetchApiData();
  loadBusinessData();
  refreshKey.value++; // Принудительно обновляем CouponsList (watch сработает)
}

// Функции загрузки данных
const fetchApiData = async () => {
  try {
    // Выполняем GET-запрос к /api/ps с токеном авторизации
    const response = await axios.get('/api/ps', {
      headers: {
        Authorization: `Bearer ${authStore.token}`, // Токен для аутентификации
        'Accept': 'application/json' // Указываем, что ожидаем JSON
      },
    })
    // Сохраняем полученные данные в реактивную переменную
    apiData.value = response.data
  } catch (err) {
    // При ошибке выбрасываем исключение с локализованным сообщением
    throw new Error(err.response?.data?.message || err.message || $t('errors.data_loading'))
  }
}

const loadBusinessData = async () => {
  try {
    // Вызываем функцию getBusinessData из @/api/coupons
    const response = await getBusinessData()
    // Проверяем успешность ответа
    if (response.success) {
      // Сохраняем данные в реактивную переменную bData
      bData.value = response
      //console.log('Business Data:', response) // Логируем для отладки
    } else {
      // Если ответ не успешен, выбрасываем ошибку
      throw new Error($t('errors.business_data_loading'))
    }
  } catch (err) {
    // Перехватываем и выбрасываем ошибку с локализованным сообщением
    throw new Error(err.message || $t('errors.business_data_loading'))
  }
}

const fetchAllData = async () => {
  try {
    // Устанавливаем состояние загрузки
    loading.value = true
    error.value = null
    // Выполняем оба запроса параллельно с помощью Promise.all
    await Promise.all([fetchApiData(), loadBusinessData()])
  } catch (err) {
    // Сохраняем сообщение об ошибке и показываем уведомление
    error.value = err.message
    initToast({
      message: err.message,
      color: 'danger'
    })
    console.error('Ошибка загрузки данных:', err)
  } finally {
    // Сбрасываем состояние загрузки
    loading.value = false
  }
}

// Хук монтирования: вызывается один раз, когда компонент добавляется в DOM
onMounted(fetchAllData)
</script>

<style scoped>
/* Стили для контейнера компонента */
.agent-layout {
  padding: 20px;
}
</style>