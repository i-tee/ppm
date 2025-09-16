<template>
  <!-- Основной контейнер для кнопки создания купона -->
  <div class="create-coupon">
    <!-- Кнопка Vuestic UI для открытия модального окна -->
    <va-button @click="couponModal = true">{{ $t('coupons.create') }}</va-button>
  </div>

  <!-- Модальное окно Vuestic UI для создания купона -->
  <VaModal
    v-model="couponModal"
    size="small"
    close-button
    :hide-default-actions="true"
  >
    <!-- Содержимое модального окна -->
    <template #default>
      <!-- Заголовок модального окна с локализацией -->
      <h3 class="va-h3 text-center mb-0">{{ $t('coupons.create') }}</h3>

      <!-- Табы для выбора типа купона (скидка или бонус) -->
      <VaTabs v-model="activeTab" grow>
        <VaTab name="discountForm">{{ $t('coupons.discountForm') }}</VaTab>
        <VaTab name="bonusForm">{{ $t('coupons.bonusForm') }}</VaTab>
      </VaTabs>

      <!-- Содержимое выбранной вкладки -->
      <div class="tab-content mt-4">
        <!-- Компонент для формы скидочного купона -->
        <DiscountPercentageCode
          :apiData="apiData"
          :bData="bData"
          v-model:modelDiscountValue="discountObject"
          v-if="activeTab === 'discountForm'"
        />
        <!-- Компонент для формы бонусного купона -->
        <BonusRedemptionCode
          :apiData="apiData"
          :bData="bData"
          v-model:modelBonusValue="bonusObject"
          v-else-if="activeTab === 'bonusForm'"
        />
      </div>
    </template>

    <!-- Нижняя часть модального окна (кнопки) -->
    <template #footer>
      <div class="flex justify-end space-x-2">
        <!-- Кнопка отмены -->
        <VaButton preset="secondary" @click="couponModal = false">
          {{ $t('modal.cancel') }}
        </VaButton>
        <!-- Кнопка создания купона -->
        <VaButton @click="createCoupon">
          {{ $t('coupons.create_button') }}
        </VaButton>
      </div>
    </template>
  </VaModal>
</template>

<script setup>
// Импорты: подключаем зависимости Vue и проекта
import { ref, watch } from 'vue' // ref для реактивности, watch для отслеживания изменений
import { useI18n } from 'vue-i18n' // Для локализации текстов
import { useToast } from 'vuestic-ui' // Для уведомлений Vuestic UI
import { useAuthStore } from '@/stores/auth' // Хранилище Pinia для авторизации
import axios from 'axios' // Библиотека для HTTP-запросов
import DiscountPercentageCode from './DiscountPercentageCode.vue' // Компонент формы скидочного купона
import BonusRedemptionCode from './BonusRedemptionCode.vue' // Компонент формы бонусного купона

// Определение пользовательского события
const emit = defineEmits(['couponCreated']) // Объявляем событие couponCreated для родительского компонента

// Инициализация локализации и уведомлений
const { t } = useI18n() // Функция t для получения переведённых строк
const { init: initToast } = useToast() // Инициализация уведомлений Vuestic UI
const authStore = useAuthStore() // Хранилище Pinia для доступа к токену авторизации

// Реактивные переменные: данные, отслеживаемые Vue для обновления интерфейса
const discountObject = ref({ name: '', value: 15 }) // Данные формы скидочного купона
const bonusObject = ref({ name: '', value: 0 }) // Данные формы бонусного купона
const couponModal = ref(false) // Состояние видимости модального окна
const activeTab = ref('discountForm') // Текущая активная вкладка (по умолчанию скидка)

// Объявляем и получаем пропсы
const { apiData, bData } = defineProps({
  apiData: {
    type: Object, // Тип пропса — объект или null
    default: null // Значение по умолчанию
  },
  bData: {
    type: Object, // Тип пропса — объект или null
    default: null // Значение по умолчанию
  }
})

// Отслеживаем изменения apiData для отладки
watch(() => apiData, (newValue) => {
  console.log('CreateCoupon apiData:', newValue)
})

// Функция создания купона
async function createCoupon() {
  // Создаём объект данных купона в зависимости от активной вкладки
  let creatCouponData = {}
  if (activeTab.value === 'discountForm') {
    creatCouponData = { ...discountObject.value, type: 0 } // Скидочный купон (type: 0)
  } else if (activeTab.value === 'bonusForm') {
    creatCouponData = { ...bonusObject.value, type: 1 } // Бонусный купон (type: 1)
  }

  creatCouponData.joomlaUser = bData.data.joomlaUser.id;

  console.log('createCouponData:', creatCouponData);

  // Проверяем валидность кода купона
  if (!creatCouponData.name || !isValidPromoCode(creatCouponData.name)) {
    initToast({
      message: t('errors.coupon_noValid'),
      color: 'warning'
    })
    // Фокусируемся на поле ввода с классом id-i11 (предположительно, поле кода купона)
    document.getElementsByClassName('id-i11')[0]?.focus()
    return
  }

  try {
    // Выполняем POST-запрос для создания купона
    const response = await axios.post('/api/coupons', creatCouponData, {
      headers: {
        Authorization: `Bearer ${authStore.token}`, // Токен авторизации
        'Content-Type': 'application/json', // Указываем тип данных
        'Accept': 'application/json' // Ожидаем JSON в ответе
      }
    })

    // Показываем уведомление об успехе
    initToast({
      message: t('coupons.create_success'),
      color: 'success'
    })

    // Отправляем событие couponCreated родительскому компоненту
    emit('couponCreated', response.data)

    // Сбрасываем состояние формы и закрываем модальное окно
    couponModal.value = false
    discountObject.value = { name: '', value: 15 }
    bonusObject.value = { name: '', value: 0 }
    activeTab.value = 'discountForm'
  } catch (err) {
    // Обрабатываем ошибку и показываем уведомление
    const errorMessage = err.response?.data?.message || t('errors.coupon_creation')
    initToast({
      message: errorMessage,
      color: 'danger'
    })
    console.error('Ошибка создания купона:', err)
  }
}

// Функция проверки валидности кода купона
function isValidPromoCode(code) {
  // Проверяем, что код — строка и длина от 6 до 36 символов
  if (typeof code !== 'string' || code.length < 6 || code.length > 36) {
    return false
  }

  // Регулярное выражение для допустимых символов (буквы, цифры, _, -)
  const regex = /^[A-Za-zА-Яа-яЁё0-9_-]+$/;

  // Возвращаем true, если код соответствует требованиям
  return regex.test(code)
}
</script>

<style scoped>
/* Стили для содержимого вкладок */
.tab-content {
  min-height: 400px;
}
</style>