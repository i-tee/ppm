<template>
    <div>
        <p class="va-h1">{{ t('coupons.tTrueBonusCodesTable') }}</p>
        <p>{{ t('coupons.tTrueBonusCodesTable_descr') }}</p>
        <p class="va-h5">{{ t('total') }}: <b>{{ formatPrice(bData.data?.trueBonusCode?.totalBonusCodesCost || 0) }}</b>
        </p>
        <hr class="mt-4">

        <VaDataTable ref="tableRef" :key="`table-${trueBonusCodes.length}-${currentPage}`" :items="pagedTrueBonusCodes"
            :columns="columns" :hoverable="true" :loading="loading" :no-data-html="t('coupons.no_data')"
            class="cursor-pointer va-table--modern" @row:click="({ item }) => openModal(item)">
            <!-- Кастомный слот для стоимости бонусного кода -->
            <template #cell(bonus_code_cost)="{ rowData }">
                {{ formatPrice(rowData.bonus_code_cost) }}
            </template>
            <!-- Кастомный слот для значения бонусного кода -->
            <template #cell(bonus_code_value)="{ rowData }">
                {{ formatPrice(rowData.bonus_code_value) }}
            </template>
            <!-- Кастомный слот для значения бонусного кода -->
            <template #cell(bonus_code_id)="{ rowData }">
                {{ bData.data.coupons_full.find(item => item.coupon_id === rowData.bonus_code_id).coupon_code }}
            </template>
            <!-- Кастомный слот для даты создания -->
            <template #cell(created_at)="{ rowData }">
                {{ formatDate(rowData.created_at) }}
            </template>
            <template #footer>
                <div class="flex justify-end items-center mt-4">
                    <VaPagination v-model="currentPage" :pages="totalPages" :visible-pages="5" color="primary" />
                </div>
            </template>
        </VaDataTable>

        <!-- Модалка -->
        <VaModal v-model="showModal" :title="t('coupons.bonus_code_details')" hide-default-actions max-width="700px">
            <TrueBonusCodeDetailsModal :bData="bData" :bonusCode="selectedBonusCode" @close="showModal = false" />
        </VaModal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vuestic-ui'
import { VaDataTable, VaPagination, VaModal } from 'vuestic-ui'
import { useBase } from '@/composables/useBase'
import TrueBonusCodeDetailsModal from './TrueBonusCodeDetailsModal.vue'

const { formatPrice, formatDate } = useBase()

// Инициализация локализации и уведомлений
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

// Реактивные переменные
const trueBonusCodes = ref([])
const loading = ref(false)
const error = ref(null)
const perPage = ref(20) // Фиксированное количество строк на страницу
const currentPage = ref(1) // Текущая страница
const tableRef = ref(null) // Ref для таблицы, чтобы управлять скроллом
const showModal = ref(false)
const selectedBonusCode = ref(null)

// Открытие модалки
const openModal = (bonusCode) => {
    selectedBonusCode.value = bonusCode
    showModal.value = true
}

// Вычисляемый массив с пагинацией
const pagedTrueBonusCodes = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    const end = start + perPage.value
    return trueBonusCodes.value.slice(start, end)
})

// Общее количество страниц
const totalPages = computed(() => Math.ceil(trueBonusCodes.value.length / perPage.value))

// Определение колонок для таблицы
const columns = computed(() => [
    { key: 'bonus_code_cost', label: t('coupons.bonus_code_cost'), sortable: true },
    { key: 'bonus_code_value', label: t('coupons.bonus_code_value'), sortable: true },
    { key: 'bonus_code_id', label: t('coupons.name'), sortable: true },
    { key: 'created_at', label: t('coupons.created_at'), sortable: true },
])

// Сохранение позиции скролла при смене страницы
const maintainScrollPosition = () => {
    if (tableRef.value) {
        tableRef.value.$el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }
}

// Загрузка данных
const loadData = () => {
    try {
        loading.value = true
        const data = props.bData?.data?.trueBonusCode?.trueBonusCodes || []
        if (!data.length) {
            initToast({
                message: t('coupons.no_data'),
                color: 'warning',
            })
        }
        trueBonusCodes.value = data
        // Сброс пагинации после загрузки
        currentPage.value = 1
    } catch (err) {
        error.value = t('errors.data_loading')
        initToast({
            message: error.value,
            color: 'danger',
        })
    } finally {
        loading.value = false
    }
}

// Отслеживание изменения currentPage
watch(currentPage, () => {
    maintainScrollPosition() // Сохраняем позицию скролла
})

// Отслеживание изменений в props.refresh для обновления данных
watch(() => props.refresh, loadData)

// Хук монтирования
onMounted(loadData)
</script>