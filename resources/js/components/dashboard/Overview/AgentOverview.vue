<template>
    <!-- Индикатор загрузки -->
    <div v-if="loading" class="m-2">
        <div class="p-4">
            <VaSkeleton tag="h1" variant="text" class="va-h1 mb-2" />
            <VaSkeleton tag="p" variant="text" class="mb-3" />
            <VaSkeleton tag="button" variant="rectangular" class="w-40 h-12" />
        </div>
    </div>

    <div v-else-if="error" class="m-2">
        <VaCard class="p-4">
            <p class="va-h4 text-danger">{{ $t('errors.data_loading') }}</p>
            <VaButton @click="fetchAllData" preset="secondary">
                {{ $t('common.retry') }}
            </VaButton>
        </VaCard>
    </div>

    <!-- Основной контент: расширенная сводка -->
    <div v-else class="m-2 space-y-6">
        <!-- Первый ряд: основные метрики -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.current_balance') }}</p>
                <p class="text-2xl font-bold text-success">{{ formatPrice(balance) }}</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.total_accruals') }}</p>
                <p class="text-2xl font-bold text-info">{{ formatPrice(totalAccruals) }}</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.orders_count') }}</p>
                <p class="text-2xl font-bold text-primary">{{ ordersCount }}</p>
            </div>
        </div>

        <!-- Второй ряд: статистика по промокодам -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Бонусные промокоды (coupon_type: 1) -->
            <div class="text-center p-4 bg-green-50 rounded-lg shadow-sm">
                <div class="flex items-center justify-center mb-2">
                    <span class="text-green-600 font-semibold">{{ $t('dashboard.bonus_coupons') }}</span>
                </div>
                <p class="text-2xl font-bold text-green-600">{{ bonusPromoCodesCount }}</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">
                        {{ $t('dashboard.used') }}: {{ usedBonusPromoCodes }}
                    </p>
                </div>
            </div>

            <!-- Процентные промокоды (coupon_type: 0) -->
            <div class="text-center p-4 bg-blue-50 rounded-lg shadow-sm">
                <div class="flex items-center justify-center mb-2">
                    <span class="text-blue-600 font-semibold">{{ $t('dashboard.percent_coupons') }}</span>
                </div>
                <p class="text-2xl font-bold text-blue-600">{{ percentPromoCodesCount }}</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">
                        {{ $t('dashboard.active') }}: {{ activePercentPromoCodes }}
                    </p>
                </div>
            </div>

            <!-- Общая статистика промокодов -->
            <div class="text-center p-4 bg-purple-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.total_coupons') }}</p>
                <p class="text-2xl font-bold text-purple-600">{{ totalPromoCodes }}</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">
                        {{ $t('dashboard.with_cashback') }}: {{ promoCodesWithCashback }}
                    </p>
                </div>
            </div>

            <!-- Затраты на бонусные коды -->
            <div class="text-center p-4 bg-amber-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.bonus_codes_expense') }}</p>
                <p class="text-2xl font-bold text-amber-600">{{ formatPrice(bonusCodesCost) }}</p>
                <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">
                        {{ $t('dashboard.bonus_codes_count') }}: {{ bonusCodesCount }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $t('dashboard.avg_cost') }}: {{ formatPrice(averageBonusCodeCost) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Третий ряд: выводы средств -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Объединённые выводы средств -->
            <div class="text-center p-4 bg-indigo-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.withdrawals_total') }}</p>
                <p class="text-2xl font-bold text-indigo-600">{{ formatPrice(totalWithdrawn) }}</p>
            </div>

            <!-- Статистика эффективности -->
            <div class="text-center p-4 bg-emerald-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.avg_order_with_coupon') }}</p>
                <p class="text-2xl font-bold text-emerald-600">{{ formatPrice(averageOrderWithPromoCode) }}</p>
            </div>
        </div>

        <!-- Четвертый ряд: сумма заказов -->
        <div class="grid grid-cols-1 gap-4">
            <div class="text-center p-4 bg-purple-50 rounded-lg shadow-sm">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.total_order_sum') }}</p>
                <p class="text-2xl font-bold text-purple-600">{{ formatPrice(totalOrderSum) }}</p>
            </div>
        </div>

        <!-- Блок с реквизитами (оставляем как есть) -->
        <div v-if="!isVerified" class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div>
                    <p class="text-yellow-800 font-medium">{{ $t('requisites.no_requisites') }}</p>
                    <p class="text-yellow-600 text-sm">{{ $t('requisites.create_for_withdrawal') }}</p>
                </div>
                <VaButton @click="goToRequisites" preset="primary" class="mt-3 md:mt-0">
                    {{ $t('requisites.myrequisites') }}
                </VaButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useRequisitesHelper } from '@/composables/requisitesHelper'
import { getBusinessData } from '@/api/coupons'
import { useBase } from '@/composables/useBase'

const router = useRouter()
const authStore = useAuthStore()
const { hasVerifiedRequisite } = useRequisitesHelper()
const { formatPrice } = useBase()

const isVerified = ref(true)
const apiData = ref(null)
const bData = ref(null)

const loading = ref(true)
const error = ref(null)

const goToRequisites = () => {
    router.push({ name: 'Requisite' })
}

const fetchApiData = async () => {
    try {
        const response = await axios.get('/api/ps', {
            headers: {
                Authorization: `Bearer ${authStore.token}`,
                'Accept': 'application/json',
            },
        })
        apiData.value = response.data
    } catch (err) {
        throw new Error(err.response?.data?.message || t('errors.data_loading'))
    }
}

const loadBusinessData = async () => {
    try {
        const response = await getBusinessData()
        if (response.success) {
            bData.value = response
        } else {
            throw new Error(t('errors.business_data_loading'))
        }
    } catch (err) {
        throw new Error(err.message || t('errors.business_data_loading'))
    }
}

const fetchAllData = async () => {
    try {
        loading.value = true
        error.value = null
        await Promise.all([fetchApiData(), loadBusinessData()])

        console.log('apiData:', apiData.value)
        console.log('bData:', bData.value)

    } catch (err) {
        error.value = err.message
        console.log('fetchAllData error:', error)
    } finally {
        loading.value = false
    }
}

// Основные вычисляемые свойства
const balance = computed(() => bData.value?.data?.balance || 0)
const totalAccruals = computed(() => bData.value?.data?.credits?.total_accruals || 0)
const ordersCount = computed(() => bData.value?.data?.credits?.orders_count || 0)

// Статистика по промокодам
const promoCodesFull = computed(() => bData.value?.data?.coupons_full || [])

// Процентные промокоды (coupon_type: 0) - процент от заказа, приносят доход
const percentPromoCodes = computed(() => promoCodesFull.value.filter(promo => promo.coupon_type === 0))
const percentPromoCodesCount = computed(() => percentPromoCodes.value.length)
const activePercentPromoCodes = computed(() => percentPromoCodes.value.filter(promo => promo.coupon_publish === 1).length)
const usedPercentPromoCodes = computed(() => percentPromoCodes.value.filter(promo => promo.used !== 0).length)
const percentPromoCodesAverageValue = computed(() => {
    if (percentPromoCodesCount.value === 0) return 0
    const sum = percentPromoCodes.value.reduce((total, promo) => total + parseFloat(promo.coupon_value || 0), 0)
    return Math.round(sum / percentPromoCodesCount.value)
})

// Бонусные промокоды (coupon_type: 1) - одноразовые, создаются за бонусные средства
const bonusPromoCodes = computed(() => promoCodesFull.value.filter(promo => promo.coupon_type === 1))
const bonusPromoCodesCount = computed(() => bonusPromoCodes.value.length)
const activeBonusPromoCodes = computed(() => bonusPromoCodes.value.filter(promo => promo.coupon_publish === 1).length)
const usedBonusPromoCodes = computed(() => bonusPromoCodes.value.filter(promo => promo.used !== 0).length)
const unusedBonusPromoCodes = computed(() => bonusPromoCodes.value.filter(promo => promo.used === 0).length)
const bonusPromoCodesAverageValue = computed(() => {
    if (bonusPromoCodesCount.value === 0) return 0
    const sum = bonusPromoCodes.value.reduce((total, promo) => total + parseFloat(promo.coupon_value || 0), 0)
    return sum / bonusPromoCodesCount.value
})

// Общая статистика промокодов
const totalPromoCodes = computed(() => promoCodesFull.value.length)
const activePromoCodes = computed(() => promoCodesFull.value.filter(promo => promo.coupon_publish === 1).length)
const promoCodesWithCashback = computed(() => promoCodesFull.value.filter(promo => promo.cashback > 0).length)

// Истекающие скоро промокоды (в течение 30 дней)
const expiringSoonCount = computed(() => {
    const now = new Date()
    const thirtyDaysFromNow = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000)

    return promoCodesFull.value.filter(promo => {
        if (!promo.coupon_expire_date || promo.coupon_expire_date === '0000-00-00') return false
        const expireDate = new Date(promo.coupon_expire_date)
        return expireDate <= thirtyDaysFromNow && expireDate > now
    }).length
})

// Бонусные коды (trueBonusCode)
const bonusCodes = computed(() => bData.value?.data?.trueBonusCode?.trueBonusCodes || [])
const bonusCodesCount = computed(() => bonusCodes.value.length)
const bonusCodesCost = computed(() => bData.value?.data?.trueBonusCode?.totalBonusCodesCost || 0)
const averageBonusCodeCost = computed(() => {
    if (bonusCodesCount.value === 0) return 0
    return bonusCodesCost.value / bonusCodesCount.value
})

// Объединённые выводы средств
const oldWithdrawals = computed(() => bData.value?.data?.withdrawals?.debit || 0)
const newWithdrawals = computed(() => bData.value?.data?.payoutRequests?.debit || 0)
const totalWithdrawn = computed(() => oldWithdrawals.value + newWithdrawals.value)

const oldWithdrawalsCount = computed(() => bData.value?.data?.withdrawals?.withdrawals_count || 0)
const newWithdrawalsCount = computed(() => bData.value?.data?.payoutRequests?.payoutRequests?.length || 0)
const totalWithdrawalRequests = computed(() => oldWithdrawalsCount.value + newWithdrawalsCount.value)

const approvedWithdrawalsCount = computed(() => {
    const payouts = bData.value?.data?.payoutRequests?.payoutRequests || []
    return payouts.filter(p => p.status === 20).length // статус 20, вероятно, "одобрено"
})

const pendingWithdrawals = computed(() => {
    const payouts = bData.value?.data?.payoutRequests?.payoutRequests || []
    const pending = payouts.filter(p => p.status !== 20) // все неодобренные
    return pending.reduce((sum, p) => sum + parseFloat(p.withdrawal_amount || 0), 0)
})

// Статистика эффективности
const ordersWithPromoCodesCount = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    return orders.filter(order => order.coupon_id && order.coupon_id > 0).length
})

const conversionRate = computed(() => {
    if (totalPromoCodes.value === 0) return 0
    return Math.round((usedBonusPromoCodes.value / totalPromoCodes.value) * 100)
})

const averageOrderWithPromoCode = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    const ordersWithPromoCodes = orders.filter(order => order.coupon_id && order.coupon_id > 0)
    if (ordersWithPromoCodes.length === 0) return 0
    const sum = ordersWithPromoCodes.reduce((total, order) => total + parseFloat(order.order_total || 0), 0)
    return sum / ordersWithPromoCodes.length
})

const incomeFromPromoCodes = computed(() => {
    // Доход от процентных промокодов (coupon_type: 0) можно рассчитать на основе заказов
    const orders = bData.value?.data?.credits?.orders || []
    return orders.reduce((total, order) => {
        const promo = promoCodesFull.value.find(c => c.coupon_id === order.coupon_id)
        if (promo && promo.coupon_type === 0) {
            // Процентные промокоды: доход = сумма заказа * процент промокода / 100
            return total + (parseFloat(order.order_total) * parseFloat(promo.coupon_value) / 100)
        }
        return total
    }, 0)
})

const efficiencyPercentage = computed(() => {
    if (totalAccruals.value === 0) return 0
    // Эффективность = (доход от промокодов / общие начисления) * 100
    return Math.round((incomeFromPromoCodes.value / totalAccruals.value) * 100)
})

// Статистика по заказам
const successfulOrdersCount = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    return orders.filter(order => order.order_status === 6).length // статус 6, вероятно, "завершен"
})

const totalOrderSum = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    return orders.reduce((sum, order) => sum + parseFloat(order.order_total || 0), 0)
})

const averageOrderValue = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    if (orders.length === 0) return 0
    return totalOrderSum.value / orders.length
})

const totalDiscount = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    return orders.reduce((sum, order) => sum + parseFloat(order.order_discount || 0), 0)
})

// Бонусная статистика
const totalCashback = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    return orders.reduce((sum, order) => sum + parseFloat(order.cashback || 0), 0)
})

const averageCashback = computed(() => {
    const orders = bData.value?.data?.credits?.orders || []
    if (orders.length === 0) return 0
    return totalCashback.value / orders.length
})

const expenseSummary = computed(() => bData.value?.data?.expenseSummary || 0)

// ЕДИНЫЙ onMounted
onMounted(async () => {
    await fetchAllData()

    try {
        const result = await hasVerifiedRequisite()
        isVerified.value = result

    } catch (err) {
        console.log('hasVerifiedRequisite() error:', err)
    }
})
</script>