<template>
    <div>
        <p class="va-h1">{{ t('coupons.tReversalsTable') }}</p>
        <p>{{ t('coupons.tReversalsTable_descr') }}</p>
        <p class="va-h5">{{ t('total') }}: <b>-{{ formatPrice(bData.data?.backendReversals?.debit || 0) }}</b></p>
        <hr class="mt-4">

        <VaDataTable ref="tableRef" :key="`rev-${reversals.length}-${currentPage}`" :items="pagedReversals"
            :columns="columns" :hoverable="true" :loading="loading" :no-data-html="t('coupons.no_data')"
            class="cursor-pointer va-table--modern" @row:click="({ item }) => openModal(item)">
            <!-- Номер заказа -->
            <template #cell(order_number)="{ rowData }">№{{ rowData.order_number }}</template>
            <!-- Сумма корректировки (отрицательная) -->
            <template #cell(amount)="{ rowData }">-{{ formatPrice(Math.abs(rowData.amount)) }}</template>
            <!-- Дата -->
            <template #cell(date)="{ rowData }">{{ formatDate(rowData.date) }}</template>
            <template #footer>
                <div class="flex justify-end items-center mt-4">
                    <VaPagination v-model="currentPage" :pages="totalPages" :visible-pages="5" color="primary" />
                </div>
            </template>
        </VaDataTable>

        <!-- Модалка деталей корректировки -->
        <VaModal v-model="showModal" :title="t('coupons.reversal_details')" close-button hide-default-actions
            max-width="700px" :mobile-fullscreen="false">
            <ReversalDetailsModal v-if="selectedReversal" :reversal="selectedReversal" @close="showModal = false" />
        </VaModal>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { VaDataTable, VaPagination, VaModal } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'
import ReversalDetailsModal from './ReversalDetailsModal.vue'

const { formatPrice, formatDate } = useBase()
const { t } = useI18n()

const props = defineProps({
    apiData: { type: Object, default: null },
    bData: { type: Object, default: null },
    refresh: { type: Number, default: 0 },
})

const reversals = ref([])
const loading = ref(false)
const perPage = ref(20)
const currentPage = ref(1)
const tableRef = ref(null)
const showModal = ref(false)
const selectedReversal = ref(null)

const openModal = (r) => {
    selectedReversal.value = r
    showModal.value = true
}

const pagedReversals = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return reversals.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => Math.ceil(reversals.value.length / perPage.value))

const columns = computed(() => [
    { key: 'order_number', label: t('orders.title'), sortable: true },
    { key: 'coupon_code', label: t('orders.coupon'), sortable: true },
    { key: 'amount', label: t('coupons.reversal_amount'), sortable: true },
    { key: 'date', label: t('orders.date'), sortable: true },
])

const loadData = () => {
    loading.value = true
    reversals.value = props.bData?.data?.backendReversals?.items || []
    currentPage.value = 1
    loading.value = false
}

watch(() => props.refresh, loadData)
onMounted(loadData)
</script>
