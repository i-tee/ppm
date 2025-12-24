<template>
    <div class="space-y-6">
        <div class="text-lg font-semibold">{{ $t('payoutRequest.ticket.title') }}</div>
        <p class="text-sm text-gray-600">{{ $t('payoutRequest.ticket.description') }}</p>

        <!-- Уже загружен чек -->
        <div v-if="hasTicket">
            <p class="text-success">{{ $t('payoutRequest.ticket.already_uploaded') }}</p>
            <div class="my-6 border rounded-lg overflow-hidden">
                <img v-if="isImage" :src="ticketUrl" alt="Чек пользователя" class="max-w-full" />
                <iframe v-else-if="isPdf" :src="ticketUrl" class="w-full h-96 border-0" frameborder="0" />
                <a v-else :href="ticketUrl" target="_blank" class="va-button va-button--normal va-button--primary">
                    {{ $t('payoutRequest.ticket.view_file') }}
                </a>
            </div>
        </div>

        <!-- Форма загрузки -->
        <div v-else-if="needsUpload">
            <VaFileUpload v-model="files" type="single" file-types=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                :max-size="10240 * 1024" @update:modelValue="onFileChange" class="mb-4" />
            <VaButton @click="upload" :loading="uploading" :disabled="!canUpload" color="primary">
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
import { useAuthStore } from '@/stores/auth' // Хранилище Pinia для авторизации

const props = defineProps({
    payoutRequest: { type: Object, required: true },
    bData: { type: Object, default: null },
})

const emit = defineEmits(['close', 'updated'])

const toast = useToast()
const authStore = useAuthStore() // Хранилище Pinia для доступа к токену авторизации

const files = ref([])
const uploading = ref(false)
const canUpload = ref(false)

// Отладочный обработчик — выводит всё, что приходит в v-model
const onFileChange = (newValue) => {
    console.log('=== VaFileUpload update:modelValue ===')
    console.log('newValue:', newValue)
    console.log('typeof newValue:', typeof newValue)
    console.log('Array.isArray(newValue):', Array.isArray(newValue))
    console.log('newValue instanceof File:', newValue instanceof File)
    if (newValue) {
        if (Array.isArray(newValue)) {

            console.log('Длина массива:', newValue.length)
            if (newValue.length > 0) canUpload.value = newValue[0].size > 0 ? true : false

        } else {
            canUpload.value = newValue.size > 0 ? true : false
        }
    } else {
        canUpload.value = false
    }

    console.log('=====================================')

}

const needsUpload = computed(() => props.payoutRequest.status === 20 && !props.payoutRequest.ticket_proof)
const hasTicket = computed(() => !!props.payoutRequest.ticket_proof)

const ticketUrl = computed(() =>
    props.payoutRequest.ticket_proof ? `/storage/${props.payoutRequest.ticket_proof}` : ''
)

const fileExtension = computed(() => {
    if (!props.payoutRequest.ticket_proof) return ''
    const parts = props.payoutRequest.ticket_proof.split('.')
    return parts.length > 1 ? parts.pop().toLowerCase() : ''
})

const isImage = computed(() => ['jpg', 'jpeg', 'png'].includes(fileExtension.value))
const isPdf = computed(() => fileExtension.value === 'pdf')

const upload = async () => {
    // Если окажется, что files — это не массив, а объект File, поменяй здесь тоже
    const fileToUpload = Array.isArray(files.value) ? files.value[0] : files.value
    if (!fileToUpload) return

    uploading.value = true
    const formData = new FormData()
    formData.append('ticket', fileToUpload)

    try {

        console.log('Uploading file:', fileToUpload)
        console.log('formData:', formData)

        const { data } = await axios.post(
            `/api/payout-requests/${props.payoutRequest.id}/ticket`,
            formData,
            {
                headers:
                {
                    Authorization: `Bearer ${authStore.token}`, // Токен авторизации
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json' // Ожидаем JSON в ответе
                }
            }
        )

        if (data.success) {
            Object.assign(props.payoutRequest, data.data)

            toast.init({
                message: data.message || $t('payoutRequest.ticket.upload_success'),
                color: 'success',
            })

            files.value = []   // или files.value = null — в зависимости от того, что увидишь
            emit('updated')
        }
    } catch (err) {
        const msg = err.response?.data?.message || $t('errors.unknown')
        toast.init({ message: msg, color: 'danger' })
    } finally {
        uploading.value = false
    }
}
</script>