<template>
  <VaCard gradient class="rounded-xl shadow-lg overflow-hidden transform transition-all">
    <VaCardTitle>
      <span class="font-bold va-h4">{{ coupon.coupon_code }}</span>
    </VaCardTitle>
    <VaCardContent>
      <div class="flex">
        <div class="grid grid-cols-2 gap-2 font-hairline text-sm">
          <div class="text-secondary">{{ t('discount') }}</div>
          <div class="text-end text-primary">{{ t('cashback') }}</div>
        </div>
      </div>
      <div class="avi-coupon-ratio-area">
        <div class="avi-coupon-ratio-box">
          <div class="avi-coupon-ratio-discuont bg-primary" :style="{ width: `${couponDiscountPercent}%` }">
            <b>{{ Math.ceil(coupon.coupon_value) }}%</b>
          </div>
          <div class="avi-coupon-ratio-cachback bg-x-color text-primary"
            :style="{ width: `${couponCashbackPercent}%` }">
            <b>{{ Math.ceil(coupon.cashback == 0 ? coupon.coupon_value : coupon.cashback) }}%</b>
          </div>
        </div>
      </div>
      <br class="my-4" />
      <div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p>{{ t('coupons.usage_count') }}: <span class="font-bold">{{
              bData.data.couponsSummary[coupon.coupon_code].usage_count }}</span></p>
            <p>{{ t('coupons.total_cashback') }}: <span class="font-bold">{{
              formatPrice(bData.data.couponsSummary[coupon.coupon_code].total_cashback) }}</span></p>
          </div>
        </div>
      </div>
    </VaCardContent>
    <VaCardActions>
      <code>{{ couponUrl }}</code>
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
const maxDiscount = ref(20) // Твой fallback
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
    console.log('maxDiscount:', maxDiscount.value)

    // Рассчитываем доли в процентах от maxDiscount
    couponDiscountPercent.value = newCoupon?.coupon_value
      ? (newCoupon.coupon_value / maxDiscount.value) * 100
      : 0
    couponCashbackPercent.value = newCoupon
      ? ((newCoupon.cashback == 0 ? newCoupon.coupon_value : newCoupon.cashback) / maxDiscount.value) * 100
      : 0
    console.log('couponDiscountPercent:', couponDiscountPercent.value)
    console.log('couponCashbackPercent:', couponCashbackPercent.value)

    // Очищаем coupon_base_url от лишних кавычек
    let baseUrl = newApiData?.cooperation_types?.[1]?.coupon_base_url ?? 'https://avicenna.com.ru?'
    if (baseUrl.startsWith("'") && baseUrl.endsWith("'")) {
      baseUrl = baseUrl.slice(1, -1) // Убираем кавычки
    }
    // Формируем couponUrl
    couponUrl.value = newCoupon?.coupon_code
      ? `${baseUrl}${newCoupon.coupon_code}`
      : 'https://avicenna.com.ru?'
    console.log('couponUrl:', couponUrl.value)
  },
  { immediate: true, deep: true }
)
</script>