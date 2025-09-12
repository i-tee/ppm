<template>

  <div>

    <VaInput 
      v-model="formDiscountData.name" 
      :placeholder="$t('coupons.name_placeholder')"
      class="w-full rounded-md"
      input-class="p-4 !text-lg !font-bold text-center"
    />

    <p class="my-4">{{ $t('coupons.discountDescr') }}</p>

    <div class="custom-block-discount my-4">
      <div>
        <VaSlider v-model="formDiscountData.value" :min="0" :max="apiData.cooperation_types?.[1]?.max_discount ?? 10"
          :step="1" size="2rem" />
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
              <div class="text-lg font-bold mt-1">{{ (apiData.cooperation_types?.[1]?.max_discount ?? 10) -
                formDiscountData.value }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelDiscountValue: { type: Object, default: () => ({}) },
  apiData: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:modelDiscountValue'])

// локальная копия
const formDiscountData = reactive({
  name: props.modelDiscountValue.name ?? '',
  value: props.modelDiscountValue.value ?? 0
})

/* --- эмитим наружу при каждом изменении --- */
watch(
  formDiscountData,
  newVal => emit('update:modelDiscountValue', newVal),
  { deep: true }
)

/* --- подтягиваем изменения из родителя --- */
watch(
  () => props.modelDiscountValue,
  newExt => {
    formDiscountData.name = newExt.name ?? ''
    formDiscountData.value = newExt.value ?? 15
  },
  { deep: true }
)
</script>
