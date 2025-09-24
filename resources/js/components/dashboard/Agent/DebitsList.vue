<template>

  <p class="va-h1">{{ t('coupons.debits') }}</p>
  <p>{{ t('coupons.debits_descr') }}</p>
  <p class="va-h5">{{ t('total') }}: <b>{{ formatPrice(bData.data?.expenseSummary) }}</b></p>

  <div class="tabs-container">
    <VaTabs v-model="activeTab" grow>
      <VaTab name="WithdrawalsTable">{{ $t('coupons.tWithdrawalsTable') }}</VaTab>
      <VaTab name="TrueBonusCodesTable">{{ $t('coupons.tTrueBonusCodesTable') }}</VaTab>
      <VaTab v-if="bData.data?.withdrawals?.debit" name="Olders">{{ $t('coupons.tOlders') }}</VaTab>
    </VaTabs>
  </div>

  <!-- Содержимое выбранной вкладки -->
  <div class="tab-content mt-4">
    <div v-if="activeTab === 'WithdrawalsTable'">
      <WithdrawalsTable :apiData="apiData" :bData="bData" :refresh="refresh" />
    </div>
    <div v-else-if="activeTab === 'TrueBonusCodesTable'">
      <TrueBonusCodesTable :apiData="apiData" :bData="bData" :refresh="refresh" />
    </div>
    <div v-else-if="activeTab === 'Olders'">
      <Olders :apiData="apiData" :bData="bData" :refresh="refresh" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import WithdrawalsTable from './DebitsList/WithdrawalsTable.vue'
import TrueBonusCodesTable from './DebitsList/TrueBonusCodesTable.vue'
import Olders from './DebitsList/Olders.vue'
import { useI18n } from 'vue-i18n'
import { useBase } from '@/composables/useBase'

const { formatPrice } = useBase();
const { t } = useI18n()
const activeTab = ref('WithdrawalsTable')

// Объявляем и получаем пропсы
const props = defineProps({
  apiData: {
    type: Object,
    default: null,
  },
  bData: {
    type: Object,
    default: null,
  },
  refresh: {
    type: Number,
    default: 0,
  },
})
</script>

<style scoped>
/* Стили для контейнера табов */
.tabs-container {
  display: inline-block;
  /* Контейнер занимает только ширину содержимого */
  text-align: left;
  /* Выравнивание табов по левому краю */
}
</style>