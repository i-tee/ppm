<template>
  <!-- Основной контейнер компонента -->
  <div>

    <!-- Показываем индикатор загрузки, если loading = true -->
    <div v-if="loading" class="mt-4 pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <VaSkeleton v-for="i in 3" :key="i" variant="rounded" height="200px" />
    </div>
    
    <!-- Показываем ошибку, если она есть -->
    <div v-else-if="error" class="mt-4 text-danger">
      {{ error }}
    </div>

    <!-- Показываем список купонов, если они есть -->
    <div v-else-if="coupons.length" class="mt-4">
      <!-- Раздел для процентных промокодов (тип 0) -->
      <div v-if="percentageCoupons.length">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <CouponDiscount
            v-for="coupon in percentageCoupons"
            :key="coupon.coupon_id"
            :coupon="coupon"
            :bData="bData"
            :apiData="apiData"
          />
        </div>
      </div>

      <!-- Раздел для бонусных промокодов (тип 1) -->
      <div v-if="bonusCoupons.length" class="mt-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <CouponBonus
            v-for="coupon in bonusCoupons"
            :key="coupon.coupon_id"
            :coupon="coupon"
            :bData="bData"
            :apiData="apiData"
          />
        </div>
      </div>
    </div>

    <!-- Показываем сообщение, если купонов нет -->
    <div v-else class="mt-4">
      {{ $t('coupons.no_coupons') }}
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { getUserCoupons } from '@/api/coupons'
import CouponDiscount from '@/components/parts/CouponDiscount.vue'
import CouponBonus from '@/components/parts/CouponBonus.vue'

const { t } = useI18n()
const { init: initToast } = useToast()

const props = defineProps({
  apiData: {
    type: Object,
    default: null
  },
  bData: {
    type: Object,
    default: null
  },
  refresh: {
    type: Number,
    default: 0
  }
})

const coupons = ref([])
const loading = ref(false)
const error = ref(null)

const percentageCoupons = computed(() => coupons.value.filter(c => c.coupon_type === 0))
const bonusCoupons = computed(() => coupons.value.filter(c => c.coupon_type === 1))

onMounted(() => {
  console.log('bData:', props.bData)
  console.log('apiData:', props.apiData)
})

watch(() => props.bData, (newValue) => {
  console.log('bData:', newValue.data)
})

watch(() => props.refresh, () => {
  console.log('Refresh triggered, reloading coupons')
  loadAllData()
})

const loadCoupons = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await getUserCoupons()
    if (response.success) {
      coupons.value = response.coupons_full || response.coupons
      if (response.count === 0) {
        initToast({
          message: t('coupons.no_coupons'),
          color: 'warning'
        })
      }
    } else {
      throw new Error(response.error || t('errors.load_failed'))
    }
  } catch (err) {
    error.value = err.message
    initToast({
      message: err.message,
      color: 'danger'
    })
    console.error('Ошибка загрузки купонов:', err)
  } finally {
    loading.value = false
  }
}

const loadAllData = async () => {
  try {
    await Promise.all([loadCoupons()])
  } catch (err) {
    console.error('Ошибка загрузки данных:', err)
    initToast({
      message: t('errors.load_failed'),
      color: 'danger'
    })
  }
}

onMounted(loadAllData)
</script>