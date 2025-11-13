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

    <!-- Основной контент: упрощённая сводка -->
    <div v-else class="m-2">
        <!-- Карточки с тремя метриками -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.current_balance') }}</p>
                <p class="text-2xl font-bold text-success">{{ formatCurrency(balance) }}</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.total_accruals') }}</p>
                <p class="text-2xl font-bold text-info">{{ formatCurrency(totalAccruals) }}</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.orders_count') }}</p>
                <p class="text-2xl font-bold text-primary">{{ ordersCount }}</p>
            </div>
        </div>

        <!-- Блок с реквизитами (оставляем как есть) -->
        <div v-if="!isVerified" class="m-2">
            <div class="py-4">
                <p class="text-primary">{{ $t('requisites.no_requisites') }}</p>
                <p>{{ $t('requisites.create_for_withdrawal') }}</p>
                <VaButton @click="goToRequisites" preset="primary" class="my-3">
                    {{ $t('requisites.myrequisites') }}
                </VaButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vuestic-ui'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useRequisitesHelper } from '@/composables/requisitesHelper'
import { getBusinessData } from '@/api/coupons'

const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()
const { hasVerifiedRequisite } = useRequisitesHelper()

const isVerified = ref(false)
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
        toast.init({
            message: t('errors.data_loading'),
            color: 'danger',
        })
    } finally {
        loading.value = false
    }
}

// Вычисляемые свойства для упрощённой сводки
const balance = computed(() => bData.value?.data?.balance || 0)
const totalAccruals = computed(() => bData.value?.data?.credits?.total_accruals || 0)
const ordersCount = computed(() => bData.value?.data?.credits?.orders_count || 0)

// Утилита для форматирования валюты (₽ с пробелами)
const formatCurrency = (value) => {
    return new Intl.NumberFormat('ru-RU').format(value) + ' ₽'
}

// ЕДИНЫЙ onMounted
onMounted(async () => {
    await fetchAllData()

    try {
        const result = await hasVerifiedRequisite()
        isVerified.value = result
        if (result) {
            toast.init({
                message: t('requisites.verified_success'),
                color: 'success',
                timeout: 3000
            })
        }
    } catch (err) {
        toast.init({
            message: t('errors.verification_check'),
            color: 'danger'
        })
    }
})
</script>