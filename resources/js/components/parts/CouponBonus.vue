<template>
  <VaCard gradient class="rounded-xl shadow-lg overflow-hidden transform transition-all">
    <VaCardTitle class="flex w-100">
      <span @click="copyCouponCode" :class="!couponActive ? 'text-secondary' : '-avi-'"
        class="font-bold va-h4 cursor-copy">{{
          coupon.coupon_code || t('coupons.no_code') }}</span>
      <span v-if="couponActive" class="float-right text-primary bg-x-color avi-chip text-base">{{
        formatPrice(coupon.coupon_value) }}</span>
      <span v-else class="float-right text-secondary avi-chip text-base">{{ formatPrice(coupon.coupon_value) }}</span>
    </VaCardTitle>
    <VaCardContent>
      <div class="flex flex-col">
        <div>
          <div>
            <p>{{ t('coupons.date_init') }}: <span>{{ formatDate(coupon.coupon_start_date) }}</span></p>
          </div>
        </div>
        <div>
          <div v-if="couponActive">
            <p>{{ t('coupons.status') }}: <b class="text-success">{{ t('coupons.active') }}</b></p>
          </div>
          <div v-else>
            <p>{{ t('coupons.status') }}: <b class="text-secondary">{{ t('coupons.used') }}</b></p>
          </div>
        </div>
      </div>
      <br class="mt-2" />
      <div>
      </div>
    </VaCardContent>
    <VaCardActions v-if="couponActive" class="flex justify-right gap-2">
      <VaButton preset="secondary" class="mr-6 mb-2 cursor-copy" icon="content_copy" @click="copyCouponCode">
        {{ t('coupons.copy_code') }}
      </VaButton>
      <VaButton preset="secondary" class="mr-6 mb-2 cursor-copy" icon="content_copy" @click="copyCouponUrl">
        {{ t('coupons.copy_url') }}
      </VaButton>
    </VaCardActions>
    <VaCardActions v-else class="flex justify-right gap-2">
      <VaButton preset="secondary" class="mr-6 mb-2" icon="info" @click="orderInfo">
        {{ t('coupons.order_info') }}
      </VaButton>
    </VaCardActions>
  </VaCard>
</template>

<script setup>
import { watch, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { useBase } from '@/composables/useBase';

const { formatPrice, formatDate } = useBase();
const { t } = useI18n();
const { init: initToast } = useToast();

const props = defineProps({
  coupon: {
    type: Object,
    required: true
  },
  bData: {
    type: Object,
    default: null
  },
  apiData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['open-order-info']);

// Реактивные значения
const maxDiscount = ref(20);
const couponActive = ref(false);
const couponDiscountPercent = ref(0);
const couponCashbackPercent = ref(0);
const couponUrl = ref('https://avicenna.com.ru?');

// Отслеживаем изменения apiData и coupon
watch(
  [() => props.apiData, () => props.coupon],
  ([newApiData, newCoupon]) => {
    // Проверка на использование
    couponActive.value = newCoupon.used === 0;

    // Обновляем maxDiscount
    const newMaxDiscount = newApiData?.cooperation_types?.[1]?.max_discount ?? 20;
    maxDiscount.value = newMaxDiscount;

    // Рассчитываем доли в процентах от maxDiscount
    couponDiscountPercent.value = (newCoupon?.coupon_value ?? 0) / maxDiscount.value * 100;
    couponCashbackPercent.value = (newCoupon?.cashback ?? 0) / maxDiscount.value * 100;

    // Очищаем coupon_base_url от лишних кавычек
    let baseUrl = newApiData?.cooperation_types?.[1]?.coupon_base_url ?? 'https://avicenna.com.ru?';
    if (baseUrl.startsWith("'") && baseUrl.endsWith("'")) {
      baseUrl = baseUrl.slice(1, -1); // Убираем кавычки
    }
    // Формируем couponUrl
    couponUrl.value = newCoupon?.coupon_code ? `${baseUrl}${newCoupon.coupon_code}` : 'https://avicenna.com.ru?';
  },
  { immediate: true, deep: true }
);

// Копирование ссылки
const copyCouponUrl = () => {
  if (!couponUrl.value || couponUrl.value === 'https://avicenna.com.ru?') {
    initToast({
      message: t('coupons.copy_failed_no_code'),
      color: 'danger',
    });
    return;
  }
  navigator.clipboard.writeText(couponUrl.value).then(() => {
    initToast({
      message: t('coupons.url_copied'),
      color: 'success',
    });
  }).catch(() => {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    });
  });
};

// Копирование промокода
const copyCouponCode = () => {
  if (!props.coupon?.coupon_code) {
    initToast({
      message: t('coupons.copy_failed_no_code'),
      color: 'danger',
    });
    return;
  }
  navigator.clipboard.writeText(props.coupon.coupon_code).then(() => {
    initToast({
      message: t('coupons.code_copied', { code: props.coupon.coupon_code }),
      color: 'success',
    });
  }).catch(() => {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    });
  });
};

// Открытие модалки с информацией о заказе
const orderInfo = () => {
  emit('open-order-info', props.coupon);
};
</script>