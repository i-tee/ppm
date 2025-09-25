<template>
  <VaCard gradient class="rounded-xl shadow-lg overflow-hidden transform transition-all">
    <VaCardTitle class="flex w-100">
      <span class="font-bold va-h4">{{ coupon.coupon_code }}</span>
      <VaChip class="float-right" color="primary">{{ formatPrice(coupon.coupon_value) }}</VaChip>
    </VaCardTitle>
    <VaCardContent>
      <div class="flex flex-col">
        <div>
          <div>
            <p>{{ t('coupons.date_init') }}: <span>{{ coupon.coupon_start_date }}</span></p>
          </div>
        </div>
        <div>
          <div>
            <p>{{ t('coupons.status') }}: <b class="text-success">{{ t('coupons.active') }}</b></p>
          </div>
        </div>
      </div>
      <br class="mt-4"/>
      <div>

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