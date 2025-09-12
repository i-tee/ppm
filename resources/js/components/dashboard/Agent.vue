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

            <hr class="my-4" />

            <CreateCoupon />

        </div>
        <div v-else-if="loading">
            <VaAlert color="warning">{{ $t('loading_data') }}</VaAlert>
        </div>
        <div v-else-if="error">{{ error }}</div>

        <!-- Новый компонент со списком промокодов -->
        <!-- <UserCoupons class="mt-6" /> -->

    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import UserCoupons from './Agent/UserCoupons.vue'
import CreateCoupon from './Agent/CreatCoupon.vue'

const authStore = useAuthStore()

// Определяем реактивные переменные
const apiData = ref(null)
const loading = ref(true)
const error = ref(null)

// Вычисляем свойство для элемента с id = 2
const selectedCooperationType = computed(() => {
    if (!apiData.value?.cooperation_types) return null
    return apiData.value.cooperation_types.find(type => type.id === 2)
})

onMounted(async () => {
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
        error.value = err.response?.data?.message || err.message || 'Ошибка загрузки данных'
        console.error('Ошибка загрузки:', error.value)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.agent-layout {
    padding: 20px;
}
</style>