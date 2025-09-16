<template>
  <div>
    <p class="my-2">{{ $t('coupons.name_placeholder') }}</p>

    <VaInput
      v-model="formDiscountData.name"
      :placeholder="$t('coupons.name_placeholder')"
      class="w-full rounded-md custom-block-discount-box"
      @input="handleInput"
      input-class="p-4 !text-lg !font-bold text-center id-i11 custom-input-coupon"
    />

    <p class="my-4">{{ $t('coupons.discountDescr') }} (%)</p>

    <div class="custom-block-discount my-4">
      <div>
        <VaSlider
          v-model="formDiscountData.value"
          :min="0"
          :max="props.apiData?.cooperation_types?.[1]?.max_discount ?? 10"
          :step="1"
          size="2rem"
        />
      </div>
      <div class="my-4"></div>
      <div>
        <!-- контейнер-ряд -->
        <div class="flex flex-row gap-4">
          <!-- колонка 1/2 -->
          <div class="text-white w-1/2 p-4 rounded-md" style="background: var(--va-primary)">
            <div class="text-center">
              <div>{{ $t('coupons.discountClient') }}</div>
              <div class="text-lg font-bold mt-1">{{ formDiscountData.value }}%</div>
            </div>
          </div>
          <!-- колонка 1/2 -->
          <div class="w-1/2 p-4 rounded-md" style="background-color: rgba(21, 78, 193, 0.2)">
            <div class="text-center" style="color: var(--va-primary)">
              <div>{{ $t('coupons.discountAgent') }}</div>
              <div class="text-lg font-bold mt-1">
                {{ (props.apiData?.cooperation_types?.[1]?.max_discount ?? 10) - formDiscountData.value }}%
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted } from 'vue'
import { transliterate } from '@/utils/transliterate'

// Объявляем пропсы
const props = defineProps({
  modelDiscountValue: { type: Object, default: () => ({}) },
  apiData: { type: Object, default: () => ({}) },
  bData: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:modelDiscountValue'])

// Локальная реактивная копия данных формы
const formDiscountData = reactive({
  name: props.modelDiscountValue.name ?? '',
  value: props.modelDiscountValue.value ?? 15
})

// Обработчик ввода для транслитерации
const handleInput = (event) => {
  formDiscountData.name = transliterate(event.target.value)
}

onMounted(() => console.log('DiscountPercentageCode bData:', props.bData.data.joomlaUser))

// Отслеживаем изменения apiData для отладки
watch(() => props.apiData, (newValue) => {
  console.log('DiscountPercentageCode apiData:', newValue)
  // Пример: доступ к cooperation_types
  if (newValue?.cooperation_types) {
    console.log('Cooperation Types:', newValue.cooperation_types)
  }
})

// Эмитим изменения formDiscountData наружу
watch(
  formDiscountData,
  (newVal) => emit('update:modelDiscountValue', newVal),
  { deep: true }
)

// Синхронизируем изменения из родительского компонента
watch(
  () => props.modelDiscountValue,
  (newExt) => {
    formDiscountData.name = newExt.name ?? ''
    formDiscountData.value = newExt.value ?? 15
  },
  { deep: true }
)
</script>