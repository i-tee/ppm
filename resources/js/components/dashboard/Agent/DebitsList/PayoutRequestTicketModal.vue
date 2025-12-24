<template>
  <div class="space-y-6">
    <div class="text-lg font-semibold">{{ $t('payoutRequest.ticket.title') }}</div>
    <p class="text-sm text-gray-600">{{ $t('payoutRequest.ticket.description') }}</p>

    <!-- Уже загружен чек -->
    <div v-if="hasTicket">
      <p class="text-success">{{ $t('payoutRequest.ticket.already_uploaded') }}</p>
      <div class="my-6 border rounded-lg overflow-hidden">
        <img
          v-if="isImage"
          :src="ticketUrl"
          alt="Чек"
          class="max-w-full max-h-screen"
        />
        <iframe
          v-else-if="fileExtension === 'pdf'"
          :src="ticketUrl"
          class="w-full h-96"
        />
        <a
          v-else
          :href="ticketUrl"
          target="_blank"
          class="va-button va-button--normal va-button--primary"
        >
          {{ $t('payoutRequest.ticket.view_file') }}
        </a>
      </div>
    </div>

    <!-- Нужно загрузить (status = 20 и ещё нет чека) -->
    <div v-else-if="needsUpload">
      <VaFileUpload
        v-model="files"
        type="single"
        :file-types="['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx']"
        class="mb-4"
      />
      <VaButton
        @click="upload"
        :loading="uploading"
        :disabled="files.length === 0"
        color="primary"
      >
        {{ $t('payoutRequest.ticket.upload_button') }}
      </VaButton>
    </div>

    <!-- Не требуется -->
    <div v-else>
      <p class="text-gray-500">{{ $t('payoutRequest.ticket.not_required') }}</p>
    </div>

    <div class="flex justify-end mt-6">
      <VaButton @click="$emit('close')">{{ $t('common.close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'vuestic-ui'
import axios from 'axios'

const props = defineProps({
  payoutRequest: { type: Object, required: true },
})

const emit = defineEmits(['close'])

const toast = useToast()
const files = ref([])
const uploading = ref(false)

const needsUpload = computed(() => props.payoutRequest.status === 20)
const hasTicket = computed(() => !!props.payoutRequest.ticket_proof)

const ticketUrl = computed(() => 
  props.payoutRequest.ticket_proof ? `/storage/${props.payoutRequest.ticket_proof}` : ''
)

const fileExtension = computed(() => {
  if (!props.payoutRequest.ticket_proof) return ''
  const parts = props.payoutRequest.ticket_proof.split('.')
  return parts.length > 1 ? parts.pop().toLowerCase() : ''
})

const isImage = computed(() => ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension.value))

const upload = async () => {
  if (files.value.length === 0) return

  uploading.value = true
  const formData = new FormData()
  formData.append('ticket', files.value[0])

  try {
    const { data } = await axios.post(
      `/api/payout-requests/${props.payoutRequest.id}/ticket`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    if (data.success) {
      // Обновляем локальный объект — это автоматически обновит таблицу и детали
      props.payoutRequest.ticket_proof = data.data.ticket_proof
      props.payoutRequest.status = data.data.status
      props.payoutRequest.status_text = data.data.status_text

      toast.init({
        message: data.message || $t('payoutRequest.ticket.upload_success'),
        color: 'success',
      })
      files.value = []
    }
  } catch (err) {
    const msg = err.response?.data?.message || $t('errors.unknown')
    toast.init({ message: msg, color: 'danger' })
  } finally {
    uploading.value = false
  }
}
</script>