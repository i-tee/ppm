<template>
  <div>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.payouts') }}</p>
      <p class="my-2">{{ $t('dashboard.payouts_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="hasAgent">
      <div v-if="apiData && bData">

        <div class="va-h5 my-1">
          <span class="master-color" :title="$t('balance')">
            {{ formatPrice(bData.data?.balance) ?? $t('common.no_data') }}
          </span>
          <VaButton class="ml-2" preset="secondary" icon="chevron_right" @click="showPayoutModal = true">
            {{ $t('coupons.pullMoney') }}
          </VaButton>
        </div>

        <p class="text-gray-400">
          <i>
            <a class="ml-1 avi-link avi-link-out" preset="secondary" @click="showConditions_Agent = true">{{ $t('rules') }}</a> |
            {{ $t('user_agreement.description') }}
            <a class="avi-link avi-link-out" target="_blank" :href="selectedCooperationType?.contract_url">
              {{ $t('user_agreement.link_name') }}
            </a>
          </i>
        </p>

        <VaDivider class="my-4" />

        <DebitsList :apiData="apiData" :bData="bData" :refresh="refreshKey" @ticket-updated="handleTicketUpdated" />
      </div>

      <div v-else-if="loading">
        <div class="flex">
          <VaSkeleton variant="circle" height="4rem" />
          <VaSkeleton tag="h1" variant="text" class="va-h1 ml-4" />
        </div>
        <div class="mt-4 pb-4">
          <VaSkeleton variant="table" :rows="5" />
        </div>
      </div>

      <div v-else-if="error">{{ error }}</div>
    </div>

    <div v-else>
      <div class="p-4 my-4 rounded-lg bg-gray-200">
        <p class="my-2">{{ $t('welcomes.apps.rejected') }}</p>
        <VaDivider class="my-2" />
        <p class="my-2">{{ $t('welcomes.apps.rejected_contact') }}</p>
      </div>
    </div>

    <VaModal v-model="showPayoutModal" close-button hide-default-actions max-width="600px" :mobile-fullscreen="false">
      <PayoutModal :bData="bData" :apiData="apiData" @close="showPayoutModal = false" @created="handlePayoutCreated" />
    </VaModal>

    <VaModal v-model="showConditions_Agent" :close-button="true" :hide-default-actions="true" :mobile-fullscreen="false">
      <Conditions_Agent :apiData="apiData" />
      <template #footer>
        <VaButton @click="showConditions_Agent = false">OK</VaButton>
      </template>
    </VaModal>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useBusinessStore } from '@/stores/business'
import { useToast } from 'vuestic-ui'
import DebitsList from './Agent/DebitsList.vue'
import PayoutModal from './Agent/PayoutModal.vue'
import Conditions_Agent from '@/components/parts/Conditions/Agent.vue'
import { useBase } from '@/composables/useBase'
import { useI18n } from 'vue-i18n'
import { usePartnerApplications } from '@/composables/usePartnerApplications'

const { hasApplication } = usePartnerApplications();
const { formatPrice } = useBase();
const { t } = useI18n()
const { init: initToast } = useToast()

const settingsStore = useSettingsStore()
const businessStore = useBusinessStore()

const apiData = computed(() => settingsStore.data)
const bData = computed(() => businessStore.data ? { success: true, data: businessStore.data } : null)
const loading = computed(() => settingsStore.loading || businessStore.loading)
const error = computed(() => settingsStore.error || businessStore.error)

const selectedCooperationType = computed(() => {
  if (!apiData.value?.cooperation_types) return null
  return apiData.value.cooperation_types.find(type => type.id === 2)
})

const showPayoutModal = ref(false)
const showConditions_Agent = ref(false)
const refreshKey = ref(0)

const hasAgent = computed(() => hasApplication(2, 2))

const handlePayoutCreated = async () => {
  try {
    refreshKey.value++
    await businessStore.load({ force: true })
    initToast({ message: t('payoutRequest.create.success'), color: 'success' })
  } catch (err) {
    initToast({ message: t('errors.unexpected_error'), color: 'danger' })
  }
}

const handleTicketUpdated = async () => {
  refreshKey.value++
  await businessStore.load({ force: true })
}

onMounted(() => {
  settingsStore.load()
  businessStore.load()
})
</script>
