<template>
  <div>
    <!-- Текстовое поле -->
    <VaInput v-model="formBonusData.name" :label="$t('coupons.name')" :placeholder="$t('coupons.name_placeholder')"
      class="mb-4" ref="promoInputBonus" />

    <!-- Прогресс-бар -->
    <VaSlider v-model="formBonusData.value" :min="0" :max="100" :step="1" track-label-visible
      :track-label="`${formBonusData.value}%`" />
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelBonusValue: { type: Object, default: () => ({}) },
  apiData: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:modelBonusValue'])

// Данные формы
const formBonusData = reactive({
  name: props.modelBonusValue.name || '',
  value: props.modelBonusValue.value || 0
})

// Следим за изменениями и эмитим обновления
watch(formBonusData, (newVal) => {
  emit('update:modelBonusValue', newVal)
}, { deep: true })
</script>