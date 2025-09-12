<template>
  <div>

    <p class="my-2">{{ $t('coupons.name_placeholder') }}</p>

    <VaInput v-model="formBonusData.name" :placeholder="$t('coupons.name_placeholder')"
      class="w-full rounded-md custom-block-discount-box" @input="handleInput"
      input-class="p-4 !text-lg !font-bold text-center id-i11 custom-input-coupon" />

    <p class="my-4">{{ $t('coupons.bonusDescr') }}</p>

    <!-- Прогресс-бар -->
    <VaSlider v-model="formBonusData.value" :min="0" :max="100" :step="1" track-label-visible
      :track-label="`${formBonusData.value}%`" />

  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { transliterate } from '@/utils/transliterate'

const props = defineProps({
  modelBonusValue: { type: Object, default: () => ({}) },
  apiData: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:modelBonusValue'])

const handleInput = (event) => {
  formBonusData.name = transliterate(event.target.value)
}

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