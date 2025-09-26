<template>
  <div class="space-y-4">

    <!-- ⏳ Скелетон -->
    <div v-if="loading" class="animate-pulse space-y-3">
      <div v-for="i in 3" :key="i" class="h-24 bg-gray-200 rounded" />
    </div>

    <!-- ❌ Ошибка -->
    <div v-else-if="error" class="text-red-600 bg-red-50 p-4 rounded-lg">
      ⚠️ {{ error }}
    </div>

    <!-- 📭 Нет заказов -->
    <div v-else-if="!orders.length" class="text-gray-500 bg-gray-50 p-4 rounded-lg">
      {{ t('errors.no_orders_found') }}
    </div>

    <!-- 🎉 Есть заказы -->
    <div v-else class="space-y-4">
      <div
        v-for="order in orders"
        :key="order.order_id"
        class="bg-white p-4 space-y-3"
      >
        <VaDivider/>
        <!-- шапка заказа -->
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-800">
              {{ t('orders.title') }} №{{ order.order_number }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ t('orders.date') }}: {{ formatDate(order.order_date) }}
            </p>
          </div>
          <span
            class="px-3 py-1 rounded-full text-center text-xs font-medium"
            :class="isPaid(order.order_status)
              ? 'bg-green-100 text-green-800'
              : 'bg-gray-100 text-gray-800'"
          >
            {{ isPaid(order.order_status)
              ? t('orders.status_paid')
              : t('orders.status_other') }}
          </span>
        </div>

        <!-- суммы -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
          <div
            v-for="(item, idx) in moneyBoxesOrder(order)"
            :key="idx"
            class="p-2 rounded-lg"
            :class="item.color"
          >
            <div class="text-xs opacity-70">{{ item.label }}</div>
            <div class="font-semibold">
              {{ formatPrice(item.value) }}
            </div>
          </div>
        </div>

        <!-- доставка / покупатель -->
        <div class="grid sm:grid-cols-2 gap-3 text-xs">
          <div>
            <div class="text-gray-500 mb-1">{{ t('shipping') }}</div>
            <div class="flex justify-between">
              <span class="text-gray-500">{{ t('city') }}</span>
              <span>{{ order.city || '—' }}</span>
            </div>
          </div>
          <div>
            <div class="text-gray-500 mb-1">{{ t('buyer') }}</div>
            <div class="flex justify-between">
              <span class="text-gray-500">{{ t('firstName') }}</span>
              <span>{{ order.f_name || '—' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <VaDivider />
    <div class="text-right">
      <VaButton @click="$emit('close')">{{ t('close') }}</VaButton>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { useBase } from '@/composables/useBase';
import axios from 'axios';

const authStore = useAuthStore();
const { t } = useI18n();
const { init: toast } = useToast();
const { formatPrice, formatDate } = useBase();

const props = defineProps({
    coupon: { type: Object, required: true }
});

const orders = ref([]);
const loading = ref(false);
const error = ref(null);

// Функция проверки статуса заказа (6 или 7 - оплачен)
const isPaid = (status) => [6, 7].includes(status);

// Запрос на получение информации о заказах
const fetchOrderInfo = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.post('/api/user/coupon/orders',
            { coupon_id: props.coupon.coupon_id },
            { headers: { 'Authorization': `Bearer ${authStore.token}` } }
        );

        if (response.data.success) {
            orders.value = response.data.data;
            if (orders.value.length === 0) {
                toast({
                    message: t('errors.no_orders_found'),
                    color: 'warning',
                });
            } else {
                toast({
                    message: t('orders.retrieved_successfully'),
                    color: 'success',
                });
            }
        } else {
            throw new Error(t(response.data.message));
        }
    } catch (err) {
        error.value = t('errors.get_orders_failed');
        toast({
            message: error.value,
            color: 'danger',
        });
    } finally {
        loading.value = false;
    }
};

onMounted(fetchOrderInfo);

// удаляем старый moneyBoxes
const moneyBoxesOrder = (order) => [
  { label: t('orders.subtotal'), value: order.order_subtotal, color: 'bg-gray-100 text-gray-800' },
  { label: t('orders.discount'), value: order.order_discount, color: 'bg-red-50 text-red-800' },
  { label: t('orders.total'),    value: order.order_total,    color: 'bg-blue-50 text-blue-800' },
  { label: t('orders.cashback'), value: order.cashback <= 0 ? 0 : order.cashback, color: 'bg-green-50 text-green-800' }
]

</script>