<template>
  <!-- Основной контейнер компонента -->
  <div>
    <h1>Hello!</h1>
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

</script>