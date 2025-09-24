<template>
    <div class="space-y-3">
        <div class="text-lg font-semibold">ID: {{ bonusCode.bonus_code_id }}</div>

        <VaDivider />

        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">{{ t('coupons.name') }}: </span>
                {{bData.data.coupons_full.find(item => item.coupon_id === bonusCode.bonus_code_id).coupon_code}}
            </div>
            <div><span class="text-gray-500">{{ t('coupons.bonus_code_cost') }}: </span>
                {{ formatPrice(bonusCode.bonus_code_cost) }}
            </div>
            <div><span class="text-gray-500">{{ t('coupons.created_at') }}: </span>
                {{ formatDate(bonusCode.created_at) }}
            </div>
            <div><span class="text-gray-500">{{ t('coupons.bonus_code_value') }}: </span>
                {{ formatPrice(bonusCode.bonus_code_value) }}
            </div>
        </div>

        <VaDivider />

        <div class="text-right">
            <VaButton @click="$emit('close')">{{ t('close') }}</VaButton>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useBase } from '@/composables/useBase'
import { VaDivider, VaButton } from 'vuestic-ui'

defineProps({
    bonusCode: { type: Object, required: true },
    bData: { type: Object, required: true },
})

const { t } = useI18n()
const { formatPrice, formatDate } = useBase()
</script>