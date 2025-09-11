<template>
  <div class="user-coupons">
    <h2 class="va-h4 mb-4">{{ $t('agent.coupons.title') }}</h2>
    
    <div v-if="loading" class="text-center py-4">
      <va-progress-circle indeterminate />
      <p class="mt-2">{{ $t('loading') }}</p>
    </div>

    <div v-else-if="error" class="text-center py-4">
      <va-alert color="danger">{{ error }}</va-alert>
    </div>

    <div v-else-if="coupons.length === 0" class="text-center py-4">
      <va-alert color="info">{{ $t('agent.coupons.no_coupons') }}</va-alert>
    </div>

    <div v-else>
      <va-table :items="coupons" :columns="columns" striped>
        <template #cell(actions)="{ item }">
          <va-button 
            size="small" 
            preset="secondary"
            @click="copyCoupon(item.coupon_code)"
          >
            {{ $t('agent.coupons.copy') }}
          </va-button>
        </template>
      </va-table>
      
      <va-alert v-if="copiedCoupon" color="success" class="mt-4">
        {{ $t('agent.coupons.copied') }}: <strong>{{ copiedCoupon }}</strong>
      </va-alert>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const authStore = useAuthStore()

// Реактивные переменные
const coupons = ref([])
const loading = ref(true)
const error = ref(null)
const copiedCoupon = ref(null)

// Колонки таблицы
const columns = [
  { key: 'coupon_code', label: t('agent.coupons.code') },
  { key: 'coupon_value', label: t('agent.coupons.value') },
  { key: 'status', label: t('agent.coupons.status') },
  { key: 'created_at', label: t('agent.coupons.created') },
  { key: 'actions', label: t('agent.coupons.actions') }
]

// Загрузка промокодов
onMounted(async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await axios.get('/api/user/coupons', {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    
    coupons.value = response.data.coupons || []
  } catch (err) {
    error.value = err.response?.data?.message || err.message || t('errors.load_failed')
    console.error('Ошибка загрузки промокодов:', error.value)
  } finally {
    loading.value = false
  }
})

// Копирование кода промокода
function copyCoupon(code) {
  navigator.clipboard.writeText(code).then(() => {
    copiedCoupon.value = code
    setTimeout(() => {
      copiedCoupon.value = null
    }, 3000)
  }).catch(err => {
    console.error('Ошибка копирования:', err)
    error.value = t('errors.copy_failed')
  })
}
</script>

<style scoped>
.user-coupons {
  padding: 20px;
}
</style>