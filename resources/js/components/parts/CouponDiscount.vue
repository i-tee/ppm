<template>
  <VaCard gradient class="rounded-xl shadow-lg overflow-hidden transform transition-all">
    <VaCardTitle>
      <span icon="content_copy" class="font-bold va-h4 cursor-copy" @click="copyCouponCode">{{ coupon.coupon_code
      }}</span>
    </VaCardTitle>
    <VaCardContent>
      <div class="flex">
        <div class="grid grid-cols-2 gap-2 font-hairline text-sm">
          <div class="text-secondary">
            <span v-if="Math.ceil(coupon.coupon_value)">{{ t('discount') }}</span>
          </div>
          <div v-if="Math.ceil(coupon.cashback)" class="text-end text-primary">
            <span>{{ t('cashback') }}</span>
          </div>
        </div>
      </div>
      <div class="avi-coupon-ratio-area">
        <div class="avi-coupon-ratio-box">
          <div v-if="Math.ceil(coupon.coupon_value)"
            :class="Math.ceil(coupon.cashback) == 0 ? 'avi-border-important-10' : '-avi-'"
            class="avi-coupon-ratio-discuont bg-primary" :style="{ width: `${couponDiscountPercent}%` }">
            <b>{{ Math.ceil(coupon.coupon_value) }}%</b>
          </div>
          <div v-if="Math.ceil(coupon.cashback)" class="avi-coupon-ratio-cachback bg-x-color text-primary"
            :class="Math.ceil(coupon.coupon_value) == 0 ? 'avi-border-important-10' : '-avi-'"
            :style="{ width: `${couponCashbackPercent}%` }">
            <b>{{ Math.ceil(coupon.cashback ?? 0) }}%</b>
          </div>
        </div>
      </div>
      <br class="my-4" />
      <div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p>
              {{ t('coupons.usage_count') }}:
              <span class="font-bold">{{ bData.data.couponsSummary[coupon.coupon_code]?.usage_count || 0 }}</span>
            </p>
            <p>
              {{ t('coupons.total_cashback') }}:
              <span class="font-bold">{{ formatPrice(bData.data.couponsSummary[coupon.coupon_code]?.total_cashback || 0)
              }}</span>
            </p>
          </div>
        </div>
      </div>
    </VaCardContent>
    <!-- <VaDivider/> -->
    <VaCardActions class="flex justify-rihgt gap-2">
      <VaButton preset="secondary" class="mr-6 mb-2 cursor-copy" icon="content_copy" @click="copyCouponCode">
        {{ t('coupons.copy_code') }}
      </VaButton>
      <VaButton preset="secondary" class="mr-6 mb-2 cursor-copy" icon="content_copy" @click="copyCouponUrl">
        {{ t('coupons.copy_url') }}
      </VaButton>
      <VaButton preset="secondary" class="mr-6 mb-2" icon="info" @click="orderInfo">
        {{ t('coupons.orders') }}
      </VaButton>
    </VaCardActions>
  </VaCard>
</template>

<script setup>
import { watch, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'

const { formatPrice } = useBase()
const { t } = useI18n()
const { init: initToast } = useToast()

const emit = defineEmits(['open-order-info']);

const props = defineProps({
  coupon: {
    type: Object,
    required: true,
  },
  bData: {
    type: Object,
    default: null,
  },
  apiData: {
    type: Object,
    default: null,
  },
})

// Реактивные значения
const maxDiscount = ref(20)
const couponDiscountPercent = ref(0)
const couponCashbackPercent = ref(0)
const couponUrl = ref('https://avicenna.com.ru?')

// Отслеживаем изменения apiData и coupon
watch(
  [() => props.apiData, () => props.coupon],
  ([newApiData, newCoupon]) => {
    // Обновляем maxDiscount
    const newMaxDiscount = newApiData?.cooperation_types?.[1]?.max_discount ?? 20
    maxDiscount.value = newMaxDiscount

    // Рассчитываем доли в процентах от maxDiscount
    couponDiscountPercent.value = (newCoupon?.coupon_value ?? 0) / maxDiscount.value * 100
    couponCashbackPercent.value = (newCoupon?.cashback ?? 0) / maxDiscount.value * 100

    // Очищаем coupon_base_url от лишних кавычек
    let baseUrl = newApiData?.cooperation_types?.[1]?.coupon_base_url ?? 'https://avicenna.com.ru?'
    if (baseUrl.startsWith("'") && baseUrl.endsWith("'")) {
      baseUrl = baseUrl.slice(1, -1) // Убираем кавычки
    }
    // Формируем couponUrl
    couponUrl.value = newCoupon?.coupon_code ? `${baseUrl}${newCoupon.coupon_code}` : 'https://avicenna.com.ru?'
  },
  { immediate: true, deep: true }
)

// Копирование ссылки
const copyCouponUrl = () => {
  if (!couponUrl.value) {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    })
    return
  }
  navigator.clipboard.writeText(couponUrl.value).then(() => {
    initToast({
      message: t('coupons.url_copied'),
      color: 'success',
    })
  }).catch(() => {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    })
  })
}

// Копирование промокода
const copyCouponCode = () => {
  if (!props.coupon?.coupon_code) {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    })
    return
  }
  navigator.clipboard.writeText(props.coupon.coupon_code).then(() => {
    initToast({
      message: t('coupons.code_copied', { code: props.coupon.coupon_code }),
      color: 'success',
    })
  }).catch(() => {
    initToast({
      message: t('coupons.copy_failed'),
      color: 'danger',
    })
  })
}

// Открытие модалки с информацией о заказе
const orderInfo = () => {
  emit('open-order-info', props.coupon)
}
</script>