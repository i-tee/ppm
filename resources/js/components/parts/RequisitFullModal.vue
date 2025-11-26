<template>
  <p class="va-h4">{{ $t('payout') }}</p>
  <p class="va-h2 my-3">{{ formatPrice(props.checkedPayout.received_amount) }}</p>

  <div class="p-4 rounded-md bg-gray-100 my-2">
    <div>
      <VaAvatar :src="props.checkedPayout.user.avatar"></VaAvatar>
      <span class="m-2 font-bold">{{ props.checkedPayout.user?.name }}</span>
      <span class="m-2">{{ props.checkedPayout.user?.email }}</span>
    </div>
  </div>

  <div>
    <table class="va-table">
      <tr v-for="({ key, value }, i) in filledFields" :key="key">
        <td>
          <span>{{ $t(`requisites.${key}`) }}:</span>
        </td>
        <td>
          <strong class="ml-1">{{ value }}</strong>
        </td>
      </tr>
    </table>
  </div>

  <VaButton @click="showReceived = true" color="secondary">{{ $t('payoutRequest.confirm_payout') }}</VaButton> <!-- Добавь ключ -->

  <VaModal v-model="showReceived" :hide-default-actions="true" :close-button="true" size="small">
    <template #header>
      <p class="va-h4">{{ $t('payoutRequest.need_proof') }}</p> <!-- "Нужен чек!" -->
      <p>{{ $t('payoutRequest.proof_description') }}</p> <!-- "Документ от операции... Яндекс.Диск" -->
    </template>
    <div>
      <div class="my-3">
        <p>{{ $t('payoutRequest.proof_link_label') }}</p> <!-- "Ссылка на чек:" -->
        <VaInput
          v-model="proofLink"
          :placeholder="$t('payoutRequest.proof_link_placeholder')"
          type="url"
        />
        <p class="mt-2">{{ $t('payoutRequest.note_label') }}</p> <!-- "Тут можно написать комментарий к платежу:" -->
        <VaInput
          v-model="note"
          :placeholder="$t('payoutRequest.note_placeholder')"
          type="textarea"
        />
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end space-x-4">
        <VaButton @click="showReceived = false" preset="secondary" color="secondary">{{ $t('modal.cancel') }}</VaButton>
        <VaButton color="primary" @click="handleSaveProof">
          {{ $t('modal.save_received') }}
        </VaButton>
      </div>
    </template>
  </VaModal>
</template>

<script setup>
import { useBase } from '@/composables/useBase'
import { ref, computed } from 'vue'
import { useToast } from 'vuestic-ui'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const { formatPrice } = useBase()
const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()

const emit = defineEmits(['payoutUpdated']) // Emit для обновления родителя

const showReceived = ref(false)
const proofLink = ref('')
const note = ref('')

const props = defineProps({
  checkedPayout: { type: Object, required: true }
})

const filledFields = computed(() => {
  const blackList = new Set([
    'is_active',
    'id',
    'updated_at',
    'created_at',
    'user_id',
    'partner_type_id'
  ])

  return Object.entries(props.checkedPayout.requisite)
    .filter(([k, v]) => {
      if (blackList.has(k)) return false
      return v !== null && v !== undefined && v !== '' && v !== false
    })
    .map(([key, value]) => ({ key, value }))
})

const handleSaveProof = async () => {

    console.log('step 1:: ', proofLink.value)

  if (!proofLink.value) {
    toast.init({ message: t('payoutRequest.validate.proof_link_required'), color: 'danger' })
    console.log('step 2:: ', proofLink.value)
    return
  }

  console.log('step 3:: ', proofLink.value)

  try {
    const response = await axios.put(`/api/admin/payout-requests-received/${props.checkedPayout.id}`, {
      proof_link: proofLink.value,
      note: note.value || null
    }, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })

    if (response.data.success) {
      toast.init({ message: t('payoutRequest.received.success'), color: 'success' })
      emit('payoutUpdated') // Уведомляем родителя об обновлении
      showReceived.value = false
      proofLink.value = ''
      note.value = ''
    } else {
      toast.init({ message: response.data.message || t('errors.update_failed'), color: 'danger' })
    }
  } catch (error) {
    toast.init({ message: t('errors.update_failed'), color: 'danger' })
  }
}
</script>