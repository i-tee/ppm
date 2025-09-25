<template>
  <VaCard gradient class="rounded-xl shadow-lg overflow-hidden transform transition-all" stripe stripe-position="top">
    <VaCardTitle class="">
      <span class="font-bold va-h4">{{ coupon.coupon_code }}</span>
    </VaCardTitle>
    <VaCardContent>
      <div class="flex flex-col gap-3">
        <div>
          <div>
            <p>{{ t('discount') }}: <b>{{ Math.ceil(coupon.coupon_value) }}%</b></p>
          </div>
          <div>
            <VaProgressBar max="30" :model-value="Math.ceil(coupon.coupon_value)"/>
          </div>
        </div>
        <div>
          <div>
            <p>{{ t('cashback') }}: <b>{{ Math.ceil(coupon.cashback == 0 ? coupon.coupon_value : coupon.cashback) }}%</b></p>
          </div>
          <div>
            <VaProgressBar max="30" :model-value="Math.ceil(coupon.cashback == 0 ? coupon.coupon_value : coupon.cashback)" />
          </div>
        </div>
      </div>
      <br class="mt-4"/>
      <div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p>{{ t('coupons.usage_count') }}: <span class="font-bold">{{ bData.data.couponsSummary[coupon.coupon_code].usage_count }}</span></p>
            <p>{{ t('coupons.total_cashback') }}: <span class="font-bold">{{ formatPrice(bData.data.couponsSummary[coupon.coupon_code].total_cashback) }}</span></p>
          </div>
        </div>
      </div>
    </VaCardContent>
    <VaCardActions>
      <p></p>
    </VaCardActions>
  </VaCard>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { useBase } from '@/composables/useBase';

const { formatPrice } = useBase();
const { t } = useI18n()
const { init: initToast } = useToast()

const props = defineProps({
  coupon: {
    type: Object,
    required: true
  },
  bData: {
    type: Object,
    default: null
  }
})

const ordersCount = computed(() => {
  // Реальное количество заказов из bData для купона
  if (props.coupon.coupon_code === 'IVLEVA' && props.bData?.data?.credits?.orders) {
    return props.bData.data.credits.orders.filter(order => order.coupon_id === props.coupon.coupon_id).length
  }
  return Math.floor(Math.random() * 10) // Демоданные для других купонов
})

const showDetails = () => {
  initToast({
    message: `${t('coupons.details_for')} ${props.coupon.coupon_code}: ${t('coupons.value')} ${props.coupon.coupon_value}%`,
    color: 'info'
  })
}
</script>