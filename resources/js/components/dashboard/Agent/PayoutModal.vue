<template>
  <div class="space-y-4">
    <!-- Заголовок модального окна -->
    <h3 class="va-h3 mb-3">{{ $t('payoutRequest.create.title') }}</h3>

    <!-- Форма вывода средств -->
    <!-- VaForm — компонент Vuestic UI с встроенной валидацией и обработкой submit -->
    <VaForm @submit="handleSubmit" v-slot="{ isSubmitting, errors }" :validation-warnings="false">
      <!-- Отображение текущего бонусного баланса пользователя -->
      <div class="bg-gray-100 p-4 text-center my-2 rounded">
        <p>
          {{ $t('coupons.bonusBalance') }}:
          <span class="font-bold">{{ formatPrice(computedBalance) }}</span>
        </p>
      </div>

      <!-- Выбор реквизита для вывода -->
      <div class="my-3">
        <p class="my-3">{{ $t('payoutRequest.create.description_1') }}</p>
        <VaSelect class="w-full" v-model="form.requisite_id" :label="$t('payoutRequest.fields.requisite')"
          :options="requisiteOptions" :error="errors?.requisite_id || ''" :disabled="isSubmitting || loadingRequisites"
          text-attribute="text" value-attribute="value" />
      </div>

      <!-- Отображение типов партнёров и соответствующих налогов -->
      <div class="my-3">
        <p class="my-3">{{ $t('payoutRequest.create.description_3') }}</p>
        <div class="flex flex-wrap gap-2">
          <div v-for="partner in apiData.partner_types" :key="partner.id"
            class="flex-1 bg-gray-100 rounded-md text-center">
            <div :class="selectedType === partner.id ? 'avi-changed bg-primary text-white' : 'avi-nochanged'"
              class="p-4 rounded-md">
              <p class="text-xs truncate">{{ $t('partners.partner_types.' + partner.name) }}</p>
              <p class="text-xl">{{ partner.tax }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Блок ввода суммы и расчёта комиссии (показывается только при выборе реквизита) -->
      <div v-if="form?.requisite_id">
        <p class="my-3">{{ $t('payoutRequest.fields.amount') }}</p>
        <div class="flex flex-nowrap items-center gap-2 w-full">
          <div class="w-1/4">
            <VaInput v-model="form.withdrawal_amount" type="number" :error="errors?.withdrawal_amount || ''"
              :disabled="isSubmitting" />
          </div>
          <div class="w-3/4 px-2">
            <VaSlider v-model="form.withdrawal_amount" :min="0" :max="props.bData.data.balance" :step="50" />
          </div>
        </div>

        <!-- Отображение итоговой суммы и комиссии -->
        <div class="my-3">
          <div class="flex flex-row gap-4">
            <div class="text-white w-1/2 p-4 rounded-md" style="background: var(--va-primary)">
              <div class="text-center">
                <div>{{ $t('payoutRequest.create.will_be_paid') }}</div>
                <div class="text-lg font-bold mt-1">{{ formatPrice(receivedAmount) }}</div>
              </div>
            </div>
            <div class="w-1/2 p-4 rounded-md" style="background-color: rgba(21, 78, 193, 0.2)">
              <div class="text-center" style="color: var(--va-primary)">
                <div>{{ $t('payoutRequest.commission') }}</div>
                <div class="text-lg font-bold mt-1">
                  {{ formatPrice(commissionAmount) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <VaDivider />

      <!-- Кнопки управления модальным окном -->
      <div class="flex justify-end space-x-2 mt-4 mb-1">
        <VaButton preset="secondary" @click="emit('close')">
          {{ $t('modal.cancel') }}
        </VaButton>
        <VaButton type="submit" color="primary" :loading="isSubmitting">
          {{ $t('payoutRequest.create.submit') }}
        </VaButton>
      </div>
    </VaForm>
  </div>
</template>

<script setup>
// Импорты
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaForm, VaInput, VaSelect, VaButton, VaDivider } from 'vuestic-ui'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useBase } from '@/composables/useBase'

// Инициализация
const { t } = useI18n()
const { init: initToast } = useToast()
const authStore = useAuthStore()
const { formatPrice } = useBase()

// Props и emits
const props = defineProps({
  bData: { type: Object, required: true }, // данные о балансе
  apiData: { type: Object, required: true }, // данные о типах партнёров и налогах
})
const emit = defineEmits(['close'])

// Реактивные данные формы
const form = ref({
  withdrawal_amount: null,
  received_amount: null,
  commission_percentage: null,
  commission_amount: null,
  requisite_id: null,
  partner_type_id: null,
  note: '',
})

const requisites = ref([])
const loadingRequisites = ref(true)
const selectedType = ref(null)

// Вычисляемые свойства
const computedBalance = computed(() => props.bData.data?.balance || 0)

const requisiteOptions = computed(() =>
  requisites.value.map(r => ({
    value: r.id,
    text: `${r.full_name || r.organization_name} (${r.bank_name || 'N/A Bank'})`,
  }))
)

// Налог (процент комиссии) для выбранного типа партнёра
const commissionPercentage = computed(() => {
  const partner = props.apiData.partner_types.find(p => p.id === selectedType.value)
  return partner?.tax ?? 0 // защита от undefined
})

// Сумма комиссии
const commissionAmount = computed(() => {
  if (!form.value.withdrawal_amount) return 0
  return Math.round((form.value.withdrawal_amount * commissionPercentage.value) / 100)
})

// Сумма к получению
const receivedAmount = computed(() => {
  const amount = form.value.withdrawal_amount || 0
  const result = Math.round(amount - commissionAmount.value)
  form.value.received_amount = result
  return result
})

// Остаток на балансе после вывода (не используется напрямую, но может пригодиться)
const remainAmount = computed(() => {
  return Math.round(computedBalance.value - (form.value.withdrawal_amount || 0))
})

// Загрузка реквизитов
const fetchRequisites = async () => {
  try {
    const response = await axios.get('/api/user/requisites', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    requisites.value = response.data || []

    if (requisites.value.length === 0) {
      initToast({ message: t('payoutRequest.create.no_requisites'), color: 'warning' })
      emit('close')
    }
  } catch (err) {
    initToast({ message: t('errors.requisites_loading'), color: 'danger' })
  } finally {
    loadingRequisites.value = false
  }
}

// Валидация формы (возвращает payload или false)
const validateForm = () => {
  const payload = {
    withdrawal_amount: form.value.withdrawal_amount,
    requisite_id: form.value?.requisite_id?.value ?? form.value?.requisite_id,
    received_amount: form.value.received_amount,
    commission_percentage: commissionPercentage.value,
    commission_amount: commissionAmount.value,
  }

  const fieldsToValidate = [
    { key: 'requisite_id', required: true },
    { key: 'withdrawal_amount', required: true },
    { key: 'received_amount', required: true },
    { key: 'commission_percentage', required: true },
    { key: 'commission_amount', required: true },
  ]

  for (const { key, required } of fieldsToValidate) {
    const value = payload[key]

    if (required && (value === null || value === undefined || value === '')) {
      const messageKey = `payoutRequest.validateFail.${key}`
      initToast({ message: t(messageKey), color: 'warning' })
      return false
    }

    if (['withdrawal_amount', 'received_amount', 'commission_percentage', 'commission_amount'].includes(key)) {
      const numValue = Number(value)
      if (isNaN(numValue) || numValue < 0) {
        const messageKey = `payoutRequest.validateFail.${key}_invalid`
        initToast({ message: t(messageKey), color: 'warning' })
        return false
      }
    }
  }

  // Проверка: сумма вывода не должна превышать баланс
  if (payload.withdrawal_amount > computedBalance.value) {
    initToast({
      message: t('payoutRequest.validateFail.insufficient_balance'),
      color: 'warning'
    })
    return false
  }

  return payload
}

// Отправка данных на сервер
const handleSubmit = async () => {
  const payload = validateForm()
  if (!payload) return

  try {
    loadingRequisites.value = true

    console.log(payload)

    const response = await axios.post('/api/payout-requests', payload, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })

    if (response.data.success) {
      initToast({ message: t('payoutRequest.create.success'), color: 'success' })
      emit('created')  // Триггер refresh в Agent
      emit('close')
      form.value = { ...form.value, withdrawal_amount: '', requisite_id: null, note: '' }  // Reset
    }
  } catch (err) {
    initToast({
      message: t('payoutRequest.create.error') || err.response?.data?.message || t('errors.unexpected_error'),
      color: 'danger'
    })
  } finally {
    loadingRequisites.value = false
  }
}

// Отслеживаем выбор реквизита для определения типа партнёра и налога
watch(() => form.value.requisite_id, (newId) => {
  selectedType.value = null
  if (!newId) return

  const id = typeof newId === 'object' ? newId.value : newId
  const selectedRequisite = requisites.value.find(r => r.id === id)
  if (selectedRequisite?.partner_type_id) {
    selectedType.value = selectedRequisite.partner_type_id
  }
})

// Загрузка реквизитов при монтировании компонента
onMounted(fetchRequisites)
</script>