<template>
    <div class="agent-layout">
        <!-- Существующий контент Agent.vue -->
        <div v-if="apiData">
            <h1 class="va-h4 my-1">{{ $t('partners.cooperation_types.agent.title') }}</h1>
            <p>{{ $t('user_agreement.description') }}
                <a target="_blank" :href="selectedCooperationType.contract_url">
                    {{ $t('user_agreement.link_name') }}
                </a>
            </p>

            <VaCard class="my-4 pa-4">
                <div>
                    <h2 class="va-h4">{{ $t('coupons.title') }}</h2>
                </div>

                <div class="my-2">
                    <CouponsList />
                </div>

                <div class="text-end">
                    <CreateCoupon @coupon-created="fetchData" />
                </div>
            </VaCard>
        </div>
        <div v-else-if="loading">
            <VaAlert color="warning">{{ $t('loading_data') }}</VaAlert>
        </div>
        <div v-else-if="error">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vuestic-ui'
import CouponsList from './Agent/CouponsList.vue'
import CreateCoupon from './Agent/CreateCoupon.vue'

const authStore = useAuthStore()
const { init: initToast } = useToast()

// Определяем реактивные переменные
const apiData = ref(null)
const loading = ref(true)
const error = ref(null)

// Вычисляем свойство для элемента с id = 2
const selectedCooperationType = computed(() => {
    if (!apiData.value?.cooperation_types) return null
    return apiData.value.cooperation_types.find(type => type.id === 2)
})

// Функция для загрузки данных
const fetchData = async () => {
    try {
        loading.value = true
        error.value = null
        const response = await axios.get('/api/ps', {
            headers: {
                Authorization: `Bearer ${authStore.token}`,
                'Accept': 'application/json'
            },
        })
        apiData.value = response.data
    } catch (err) {
        error.value = err.response?.data?.message || err.message || $t('errors.data_loading')
        initToast({
            message: error.value,
            color: 'danger'
        })
    } finally {
        loading.value = false
    }
}

// Вызов функции при монтировании
onMounted(fetchData)
</script>

<style scoped>
.agent-layout {
    padding: 20px;
}
</style>