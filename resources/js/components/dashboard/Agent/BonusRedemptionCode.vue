<template>
  <div>
    <div>
      {{ $t('coupons.bonusPromoText', { percent: bonus_percent }) }}
    </div>


    <div class="bg-gray-100 p-4 text-center my-2">
      <p>{{ $t('coupons.bonusBalance') }}: <span class="font-bold">{{ formatPrice(computedBalance) }}</span></p>
    </div>

    <!-- контейнер-ряд -->
    <div class="flex flex-row gap-4 my-4">
      <!-- колонка 1/2 -->
      <div>
        <div class="text-center">
          <div>
            <p>{{ $t('coupons.withdrawLabelonBalance') }}</p>
            <VaInput v-model="formBonusData.value" type="number"
              :rules="[value => validateValue(value) || t('coupons.errorBalance')]" @input="validateValueInput" />
          </div>
        </div>
      </div>
      <!-- колонка 1/2 -->
      <div class="w-1/2 p-4 rounded-md" style="background: var(--va-primary)">
        <div class="text-center" style="color: #fff">
          <div>{{ $t('coupons.creditLabelonCoupon') }}</div>
          <div class="text-lg font-bold mt-1">
            {{ formatPrice(computedBonus) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Прогресс-бар -->
    <VaSlider class="my-4" v-model="formBonusData.value" :min="0" :max="props.bData.data.balance" :step="50"
      :size="50" />

    <p class="my-2">{{ $t('coupons.name_placeholder') }}</p>

    <VaInput v-model="formBonusData.name" :placeholder="$t('coupons.name_placeholder')"
      class="mb-4 w-full rounded-md custom-block-discount-box" @input="handleInput"
      input-class="p-4 !text-lg !font-bold text-center id-i11 custom-input-coupon" />
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { transliterate } from '@/utils/transliterate';
import { useBase } from '@/composables/useBase';

const { formatPrice } = useBase();
const { t } = useI18n();
const { init: toast } = useToast();

const props = defineProps({
  modelBonusValue: { type: Object, default: () => ({}) },
  apiData: { type: Object, default: () => ({}) },
  bData: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelBonusValue']);

// Реактивные данные формы
const formBonusData = reactive({
  name: props.modelBonusValue.name || '',
  value: props.modelBonusValue.value || 0,
});

// Вычисляем бонус
const computedBonus = computed(() =>
  Math.ceil((props.apiData.cooperation_types?.[1]?.bonus_koef || 1) * formBonusData.value)
);

// Вычисляем баланс
const computedBalance = computed(() =>
  Math.ceil((props.bData.data.balance || 0) - formBonusData.value)
);

// Процент бонуса (оставил как в твоём коде, но можно сделать computed)
let bonus_percent = Math.ceil((props.apiData.cooperation_types?.[1]?.bonus_koef || 1) * 100 - 100);

// Валидация значения
const validateValue = (value) => {
  const maxBalance = props.bData.data.balance || 0;
  return value >= 0 && value <= maxBalance;
};

// Обработчик ввода для VaInput
const validateValueInput = (event) => {
  const value = Number(event.target.value);
  if (!validateValue(value)) {
    toast({ message: t('coupons.errorBalance'), type: 'error' });
    formBonusData.value = Math.min(Math.max(0, value), props.bData.data.balance || 0);
  }
};

// Обработчик ввода имени
const handleInput = (event) => {
  formBonusData.name = transliterate(event.target.value);
};

// Эмит изменений
watch(
  formBonusData,
  (newVal) => {
    emit('update:modelBonusValue', {
      ...newVal,
      bonus: computedBonus.value,
    });
  },
  { deep: true }
);
</script>