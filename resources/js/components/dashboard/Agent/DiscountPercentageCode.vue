<template>
  <div>
    <VaInput
      v-model="formData.name"
      :label="$t('coupons.name')"
      :placeholder="$t('coupons.name_placeholder')"
      class="mb-4"
    />
    
    <VaInput
      v-model="formData.code"
      :label="$t('coupons.code')"
      :placeholder="$t('coupons.code_placeholder')"
      class="mb-4"
    />
    
    <VaSelect
      v-model="formData.type"
      :label="$t('coupons.type')"
      :options="discountTypes"
      class="mb-4"
    />
    
    <VaInput
      v-model="formData.value"
      :label="$t('coupons.value')"
      type="number"
      :placeholder="$t('coupons.value_placeholder')"
      class="mb-4"
    />
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue'])

// Данные формы
const formData = reactive({
  name: props.modelValue.name || '',
  code: props.modelValue.code || '',
  type: props.modelValue.type || 'percent',
  value: props.modelValue.value || ''
})

// Типы скидок
const discountTypes = [
  { text: 'coupons.types.percent', value: 'percent' },
  { text: 'coupons.types.fixed', value: 'fixed' }
]

// Следим за изменениями и эмитим обновления
watch(formData, (newVal) => {
  emit('update:modelValue', newVal)
}, { deep: true })
</script>