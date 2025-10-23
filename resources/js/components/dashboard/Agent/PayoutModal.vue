<template>
  <div class="space-y-4">

    <!-- Заголовок модального окна -->
    <h3 class="va-h3 mb-3">{{ $t('payoutRequest.create.title') }}</h3>

    <!-- Форма вывода средств -->
    <!-- VaForm — это компонент Vuestic UI, который умеет работать с валидацией и submit -->
    <VaForm @submit="handleSubmit" v-slot="{ isSubmitting, errors }" :validation-warnings="false">

      <!-- Показываем баланс пользователя -->
      <div class="bg-gray-100 p-4 text-center my-2 rounded">
        <p>
          {{ $t('coupons.bonusBalance') }}:
          <span class="font-bold">{{ formatPrice(computedBalance) }}</span>
        </p>
      </div>

      <!-- Поле выбора реквизита -->
      <div class="my-3">
        <p class="my-3">{{ $t('payoutRequest.create.description_1') }}</p>
        <!-- v-model связывает выбранное значение с form.requisite_id -->
        <VaSelect class="w-full" v-model="form.requisite_id" :label="$t('payoutRequest.fields.requisite')"
          :options="requisiteOptions" :error="errors?.requisite_id || ''" :disabled="isSubmitting || loadingRequisites"
          text-attribute="text" value-attribute="value" />
      </div>

      <div class="my-3">
        <p class="my-3">{{ $t('payoutRequest.create.description_3') }}</p>
        <div class="flex flex-wrap gap-2">
          <div v-for="partner in apiData.partner_types" class="flex-1 bg-gray-100 rounded-md text-center">
            <div :class="selectedType === partner.id ? 'avi-changed bg-primary text-white' : 'avi-nochanged'"
              class="p-4 rounded-md">
              <p class="text-xs truncate">{{ $t('partners.partner_types.' + partner.name) }}</p>
              <p class="text-xl">{{ partner.tax }}%</p>
            </div>
          </div>
        </div>
      </div>

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

        <div class="my-3">
          <!-- контейнер-ряд -->
          <div class="flex flex-row gap-4">
            <!-- колонка 1/2 -->
            <div class="text-white w-1/2 p-4 rounded-md" style="background: var(--va-primary)">
              <div class="text-center">
                <div>{{ $t('payoutRequest.create.will_be_paid') }}</div>
                <div class="text-lg font-bold mt-1">{{ formatPrice(receivedAmount) }}</div>
              </div>
            </div>
            <!-- колонка 1/2 -->
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

      <!-- Кнопка отправки формы -->
      <VaButton type="submit" color="primary" class="w-full mt-3" :loading="isSubmitting">
        {{ $t('payoutRequest.create.submit') }}
      </VaButton>

    </VaForm>
  </div>
</template>

<script setup>
// Импортируем нужные модули
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n' // для переводов
import { useToast } from 'vuestic-ui' // для всплывающих уведомлений
import { VaForm, VaInput, VaSelect, VaButton } from 'vuestic-ui' // UI-компоненты
import axios from 'axios' // HTTP-запросы
import { useAuthStore } from '@/stores/auth' // доступ к авторизационным данным
import { useBase } from '@/composables/useBase' // общие утилиты (например, форматирование цены)

// === ИНИЦИАЛИЗАЦИЯ ===
const { t } = useI18n()
const { init: initToast } = useToast()
const authStore = useAuthStore()
const { formatPrice } = useBase()

// Разрешаем компоненту принимать данные извне (через props)
const props = defineProps({
  bData: { type: Object, required: true }, // содержит баланс
  apiData: { type: Object, required: true }, // содержит типы партнёров и налоги
})

// Для возможности закрыть модалку (emit → событие наружу)
const emit = defineEmits(['close'])

// === РЕАКТИВНЫЕ ДАННЫЕ ===

// Данные формы (реактивный объект)

// 'requisite_id',
// 'withdrawal_amount',
// 'received_amount',
// 'commission_percentage',
// 'commission_amount',
// 'status',
// 'note',

const form = ref({
  withdrawal_amount: null, // сумма для вывода
  received_amount: null, // сумма к получению
  commission_percentage: null, // процент коммиссии
  commission_amount: null, // размер коммиссии
  requisite_id: null, // выбранный реквизит
  partner_type_id: null, // тип партнёра (нужен для налога)
  note: '', // заметка
})

// Список реквизитов, загруженных с сервера
const requisites = ref([])

// Флаг загрузки реквизитов
const loadingRequisites = ref(true)

// === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===

// Текущий баланс пользователя
const computedBalance = computed(() => props.bData.data?.balance || 0)

// Формируем список опций для выпадающего списка реквизитов
const requisiteOptions = computed(() =>
  requisites.value.map(r => ({
    value: r.id,
    text: `${r.full_name || r.organization_name} (${r.bank_name || 'N/A Bank'})`,
  }))
)

// Получаем процент комиссии (tax/commission_percentage) для выбранного реквизита
const commissionPercentage = computed(() => {

  let tax = props.apiData.partner_types.find(r => r.id === selectedType.value)
  return tax.tax

})

// Рассчитываем резмер комиссии (commission_amount) для выбранного реквизита и указанной суммы вывода
const commissionAmount = computed(() => {

  return Math.round((form.value.withdrawal_amount * commissionPercentage.value) / 100);

})

// Рассчитываем сумму к получению на счёт
const receivedAmount = computed(() => {

  let result = Math.round(form.value.withdrawal_amount - commissionAmount.value)
  form.value.receivedAmount = result
  return result

})

// Рассчитываем остаток на балансе после оставления такой заявки
const remainAmount = computed(() => {

  return Math.round(computedBalance.value - form.value.withdrawal_amount);

})

// === ФУНКЦИИ ===

// 1️⃣ Загрузка реквизитов с сервера
const fetchRequisites = async () => {

  try {

    const response = await axios.get('/api/user/requisites', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })

    requisites.value = response.data || []

    console.log('fetchRequisites()::', requisites.value)

    if (requisites.value.length === 0) {
      initToast({ message: t('payoutRequest.create.no_requisites'), color: 'warning' })
      emit('close')
      return
    }

  } catch (err) {
    console.error(err)
    initToast({ message: t('errors.requisites_loading'), color: 'danger' })
  } finally {
    loadingRequisites.value = false
  }

}

// 2️⃣ Валидация данных перед отправкой
const validateForm = () => {
  if (!form.value.requisite_id) {
    initToast({ message: t('errors.no_requisite_selected'), color: 'warning' })
    return false
  }

  if (!form.value.withdrawal_amount || form.value.withdrawal_amount <= 0) {
    initToast({ message: t('errors.invalid_amount'), color: 'warning' })
    return false
  }

  // Всё в порядке
  return true
}

// 3️⃣ Отправка формы
const handleSubmit = async () => {
  // Проверяем данные перед отправкой
  if (!validateForm()) return

  try {
    loadingRequisites.value = true

    // Формируем тело запроса
    const payload = {
      withdrawal_amount: form.value.withdrawal_amount,
      requisite_id: form.value.requisite_id,
      partner_type_id: form.value.partner_type_id,
      tax: form.value.tax,
      note: form.value.note,
    }

    console.log('Отправляем запрос на вывод:', payload)

    // POST-запрос к API
    await axios.post('/api/payout-requests', payload, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })

    // Успешно
    initToast({ message: t('payoutRequest.create.success'), color: 'success' })
    emit('close') // закрываем модалку
  } catch (err) {
    console.error(err)
    initToast({ message: t('payoutRequest.create.error'), color: 'danger' })
  } finally {
    loadingRequisites.value = false
  }
}

const selectedType = ref(null)

// 4️⃣ Следим за изменением выбранного реквизита
watch(() => form.value.requisite_id, (newId) => {

  selectedType.value = null // сброс при смене

  if (newId) {
    // если newId — это объект { value, text }
    const id = typeof newId === 'object' ? newId.value : newId

    const selectedRequisite = requisites.value.find(r => r.id === id)

    if (selectedRequisite?.partner_type_id) {
      selectedType.value = selectedRequisite.partner_type_id
    }

    console.log('requisites.value:', requisites.value)
    console.log('Выбран реквизит новый:', id)
    console.log('All data:', selectedRequisite)
    console.log('selectedType:', selectedType)

  } else {

    console.log('requisites.value:', requisites.value)
    console.log('Выбран реквизит прошлый:', form.value.requisite_id)
    const id = typeof form.value.requisite_id === 'object' ? form.value.requisite_id.value : form.value.requisite_id
    const previousRequisite = requisites.value.find(r => r.id === id)
    console.log('All data:', previousRequisite)

  }
})


// При загрузке компонента автоматически получаем реквизиты
onMounted(fetchRequisites)
</script>
