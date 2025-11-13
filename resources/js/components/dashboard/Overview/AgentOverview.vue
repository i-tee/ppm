<template>

  <!-- блок с реквизитами -->
  <div v-if="!isVerified" class="m-2 inline-block">
    <VaCard class="p-4">
      <p class="va-h4">{{ $t('requisites.no_requisites') }}</p>
      <p>{{ $t('requisites.create_for_withdrawal') }}</p>
      <VaButton @click="goToRequisites" preset="primary" class="my-3">
        {{ $t('requisites.myrequisites') }}
      </VaButton>
    </VaCard>
  </div>

</template>

<script setup>
import { ref, onMounted } from 'vue'
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
    // Убрал дубликат — теперь чисто на response
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

// ЕДИНЫЙ onMounted: теперь с await — ждём данные, потом чекаем реквизиты
onMounted(async () => {
  // Сначала грузим данные (скелетон на весь период)
  await fetchAllData()

  // Потом чекаем реквизиты (loading уже false)
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
    console.error('Ошибка проверки реквизитов:', err)
    toast.init({
      message: t('errors.verification_check'),
      color: 'danger'
    })
  }
})
</script>