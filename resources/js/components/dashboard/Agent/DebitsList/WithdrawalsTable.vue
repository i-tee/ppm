<template>
  <div>
    <p class="va-h1">WithdrawalsTable</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { watch } from 'vue'

// Инициализация локализации
const { t } = useI18n()

// Объявление пропсов
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

// Наблюдение за изменением props.refresh
watch(() => props.refresh, () => {
  console.log('props.refresh changed, triggering data reload')
})
</script>