<template>
  <div>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.statistics') }}</p>
      <p class="my-2">{{ $t('dashboard.statistics_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="hasAgent">
      <div v-if="apiData && bData">
        <CreditsList :apiData="apiData" :bData="bData" />
      </div>

      <div v-else-if="loading" class="mt-4 pb-4">
        <VaSkeleton variant="table" :rows="5" />
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

  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useBusinessStore } from '@/stores/business'
import CreditsList from './Agent/CreditsList.vue'
import { usePartnerApplications } from '@/composables/usePartnerApplications'

const { hasApplication } = usePartnerApplications();

const settingsStore = useSettingsStore()
const businessStore = useBusinessStore()

const apiData = computed(() => settingsStore.data)
const bData = computed(() => businessStore.data ? { success: true, data: businessStore.data } : null)
const loading = computed(() => settingsStore.loading || businessStore.loading)
const error = computed(() => settingsStore.error || businessStore.error)

const hasAgent = computed(() => hasApplication(2, 2))

onMounted(() => {
  settingsStore.load()
  businessStore.load()
})
</script>
