<template>

  <VaTabs v-model="activeTab" grow>
    <VaTab name="WithdrawalsTable">{{ $t('coupons.tWithdrawalsTable') }}</VaTab>
    <VaTab name="TrueBonusCodesTable">{{ $t('coupons.tTrueBonusCodesTable') }}</VaTab>
    <VaTab name="Olders">{{ $t('coupons.tOlders') }}</VaTab>
  </VaTabs>

  <!-- Содержимое выбранной вкладки -->
  <div class="tab-content mt-4">
    <div v-if="activeTab === 'WithdrawalsTable'">
      <WithdrawalsTable :apiData="apiData" :bData="bData" :refresh="refreshKey" />
    </div>
    <div v-else-if="activeTab === 'TrueBonusCodesTable'">
      <TrueBonusCodesTable :apiData="apiData" :bData="bData" :refresh="refreshKey" />
    </div>
    <div v-else-if="activeTab === 'Olders'">
      <Olders :apiData="apiData" :bData="bData" :refresh="refreshKey" />
    </div>
  </div>

</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'

import WithdrawalsTable from './DebitsList/WithdrawalsTable.vue'
import TrueBonusCodesTable from './DebitsList/TrueBonusCodesTable.vue'
import Olders from './DebitsList/Olders.vue'

const activeTab = ref('WithdrawalsTable')
const { t } = useI18n()
const { init: initToast } = useToast()

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

// Загрузка данных
const loadData = () => {

}

// Хук монтирования
onMounted(loadData)

// Отслеживание изменений в props.refresh для обновления данных
watch(() => props.refresh, loadData)
</script>