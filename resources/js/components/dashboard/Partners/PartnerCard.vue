<template>
  <div class="partner-card">
    <div class="d-head">
      <div class="flex items-center gap-3 flex-wrap mt-4">
        <VaButton preset="secondary" size="small" icon="arrow_back" :to="{ name: 'Partners' }">
          {{ $t('admin.partners.back_to_list') }}
        </VaButton>
        <p class="va-h4 my-0">{{ partner?.name || $t('admin.partners.card_title') }}</p>
        <VaButton
          v-if="partner && currentUserId !== partner.id"
          preset="plain"
          size="small"
          icon="login"
          @click="impersonatePartner"
        >
          {{ $t('admin.users.impersonate') }}
        </VaButton>
      </div>
      <p v-if="partner" class="my-2 text-secondary">
        {{ partner.email }} · {{ $t('admin.partners.col_registered') }}: {{ formatDate(partner.created_at) }}
      </p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="loading">
      <VaSkeleton variant="table" :rows="8" />
    </div>

    <VaAlert v-else-if="notFound" color="danger" icon="error">
      {{ $t('admin.partners.not_found') }}
    </VaAlert>

    <div v-else class="space-y-6">
      <!-- Деньги не догрузились: цифр не показываем вообще. -->
      <VaCard v-if="failed" class="p-4">
        <p class="va-h6 text-danger mb-2">{{ $t('admin.partners.card_error') }}</p>
        <VaButton preset="secondary" :loading="loading" @click="load({ refresh: true })">
          {{ $t('common.refresh') }}
        </VaButton>
      </VaCard>

      <!-- Партнёр ещё ни разу не покупал и не создавал промокоды. -->
      <VaCard v-else-if="!hasJoomlaUser" class="p-4">
        <p class="va-h6 mb-1">{{ $t('admin.partners.activity_not_started') }}</p>
        <p class="text-secondary">{{ $t('admin.partners.not_started_hint') }}</p>
      </VaCard>

      <template v-else-if="businessData">
        <!-- Сводка -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.current_balance') }}</p>
            <p class="text-2xl font-bold text-success">{{ formatPrice(businessData.balance) }}</p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.total_accruals') }}</p>
            <p class="text-2xl font-bold text-info">{{ formatPrice(businessData.credits.total_accruals) }}</p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.orders_count') }}</p>
            <p class="text-2xl font-bold text-primary">{{ businessData.credits.orders_count }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.expense_summary') }}</p>
            <p class="text-xl font-bold">{{ formatPrice(businessData.expenseSummary) }}</p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.old_withdrawals') }}</p>
            <p class="text-xl font-bold">{{ formatPrice(businessData.withdrawals.debit) }}</p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('dashboard.bonus_codes_expense') }}</p>
            <p class="text-xl font-bold">{{ formatPrice(businessData.trueBonusCode.totalBonusCodesCost) }}</p>
          </div>
          <div class="text-center p-4 bg-gray-50 rounded-lg shadow-sm">
            <p class="text-sm text-gray-600 mb-1">{{ $t('admin.partners.old_balance') }}</p>
            <p class="text-xl font-bold">
              {{ businessData.oldPromocodBalance.be ? formatPrice(businessData.oldPromocodBalance.summ) : '–' }}
            </p>
          </div>
        </div>

        <!-- График начислений -->
        <VaCard class="p-4">
          <div class="flex flex-wrap gap-4 items-end mb-4">
            <p class="va-h6 my-0 mr-auto">{{ $t('dashboard.statistics') }}</p>
            <VaSelect
              v-model="periodPreset"
              :options="periodOptions"
              value-by="value"
              text-by="text"
              class="min-w-56"
            />
          </div>

          <div v-if="!periodOrders.length" class="p-4 my-2 rounded-lg bg-gray-200 text-center">
            {{ $t('statistics.empty_period') }}
          </div>
          <StatisticsChart
            v-else
            :labels="chart.labels"
            :accruals="chart.accruals"
            :ordersCount="chart.ordersCount"
          />
        </VaCard>

        <!-- Промокоды, включая скрытые -->
        <VaCard class="p-4">
          <p class="va-h6 mb-3">{{ $t('dashboard.promocodes') }}</p>
          <VaDataTable :items="coupons" :columns="couponColumns">
            <template #cell(coupon_code)="{ rowData }">
              {{ rowData.coupon_code }}
              <VaBadge
                v-if="rowData.is_hidden"
                class="ml-2"
                :text="$t('admin.partners.coupon_hidden')"
                color="secondary"
              />
            </template>
            <template #cell(coupon_type)="{ rowData }">
              {{ rowData.coupon_type == 1 ? $t('dashboard.bonus_coupons') : $t('dashboard.percent_coupons') }}
            </template>
            <template #cell(used)="{ rowData }">
              <VaIcon
                :name="(rowData.used == 1 || rowData.backend_used == 1) ? 'check_circle' : 'remove'"
                :color="(rowData.used == 1 || rowData.backend_used == 1) ? 'success' : 'secondary'"
              />
            </template>
          </VaDataTable>
        </VaCard>

        <!-- Выплаты старой партнёрки и списания -->
        <VaCard class="p-4">
          <p class="va-h6 mb-3">{{ $t('dashboard.withdrawals_total') }}</p>
          <VaDataTable
            v-if="businessData.withdrawals.withdrawals.length"
            :items="businessData.withdrawals.withdrawals"
            :columns="legacyPayoutColumns"
          >
            <template #cell(summ)="{ rowData }">{{ formatPrice(rowData.summ) }}</template>
          </VaDataTable>
          <p v-else class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>

          <template v-if="businessData.trueBonusCode.trueBonusCodes.length">
            <VaDivider class="my-4" />
            <p class="va-h6 mb-3">{{ $t('dashboard.bonus_codes_expense') }}</p>
            <VaDataTable :items="businessData.trueBonusCode.trueBonusCodes" :columns="bonusCodeColumns">
              <template #cell(bonus_code_cost)="{ rowData }">{{ formatPrice(rowData.bonus_code_cost) }}</template>
              <template #cell(created_at)="{ rowData }">{{ formatDate(rowData.created_at) }}</template>
            </VaDataTable>
          </template>
        </VaCard>
      </template>

      <!-- История заявок на вывод: показываем всегда, деньгам бэкенда не подчинена -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.withdrawal_requests') }}</p>
        <VaDataTable
          v-if="payoutRequests.length"
          :items="payoutRequests"
          :columns="payoutColumns"
        >
          <template #cell(withdrawal_amount)="{ rowData }">{{ formatPrice(rowData.withdrawal_amount) }}</template>
          <template #cell(received_amount)="{ rowData }">{{ formatPrice(rowData.received_amount) }}</template>
          <template #cell(status)="{ rowData }">{{ rowData.status_text }}</template>
          <template #cell(created_at)="{ rowData }">{{ formatDate(rowData.created_at) }}</template>
        </VaDataTable>
        <p v-else class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>
      </VaCard>

      <!-- Анкеты партнёра целиком, включая старые поля -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.partner_applications') }}</p>
        <p v-if="!applications.length" class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>
        <div v-for="application in applications" :key="application.id" class="mb-4">
          <VaDivider v-if="applications.length > 1" class="my-2" />
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
            <div v-for="field in applicationFields(application)" :key="field.label" class="flex gap-2">
              <span class="text-secondary">{{ field.label }}:</span>
              <span>{{ field.value }}</span>
            </div>
          </div>
          <div v-if="applicationLinks(application).length" class="mt-2">
            <span class="text-secondary">{{ $t('admin.partners.col_links') }}:</span>
            <a
              v-for="(link, i) in applicationLinks(application)"
              :key="i"
              :href="link"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary underline ml-2"
            >{{ link }}</a>
          </div>
        </div>
      </VaCard>

      <!-- Реквизиты со статусом проверки -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.requisites') }}</p>
        <VaDataTable v-if="requisites.length" :items="requisites" :columns="requisiteColumns">
          <template #cell(is_verified)="{ rowData }">
            <VaBadge
              :text="rowData.is_verified ? $t('admin.partners.requisite_verified') : $t('admin.partners.requisite_unverified')"
              :color="rowData.is_verified ? 'success' : 'warning'"
            />
          </template>
          <template #cell(is_active)="{ rowData }">
            <VaIcon
              :name="rowData.is_active ? 'check_circle' : 'remove'"
              :color="rowData.is_active ? 'success' : 'secondary'"
            />
          </template>
        </VaDataTable>
        <p v-else class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>
      </VaCard>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useBase } from '@/composables/useBase';
import StatisticsChart from '../Statistics/StatisticsChart.vue';
import {
  PERIOD_PRESETS,
  getPeriodRange,
  filterOrdersByPeriod,
  autoChartStep,
  buildChartBuckets,
  getOrdersDateBounds,
} from '@/utils/statistics';
import api from '@/api';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();
const { formatPrice, formatDate } = useBase();

const loading = ref(true);
const notFound = ref(false);
const failed = ref(false);
const hasJoomlaUser = ref(false);
const partner = ref(null);
const applications = ref([]);
const requisites = ref([]);
const payoutRequests = ref([]);
const businessData = ref(null);

const currentUserId = computed(() => authStore.currentUser?.id);

// --- График: переиспользуем утилиты экрана «Статистика» ---

const periodPreset = ref('30d');

const periodOptions = computed(() => PERIOD_PRESETS
  .filter((preset) => preset !== 'custom')
  .map((preset) => ({ text: t('statistics.period_' + preset), value: preset })));

const allOrders = computed(() => businessData.value?.credits?.orders || []);
const periodRange = computed(() => getPeriodRange(periodPreset.value));
const periodOrders = computed(() => filterOrdersByPeriod(allOrders.value, periodRange.value));

const chartRange = computed(() => {
  if (periodRange.value.start && periodRange.value.end) return periodRange.value;
  return getOrdersDateBounds(periodOrders.value) || periodRange.value;
});

const chart = computed(() => buildChartBuckets(
  periodOrders.value,
  chartRange.value,
  autoChartStep(chartRange.value),
));

// --- Промокоды, включая скрытые ---

const coupons = computed(() => {
  const hidden = (businessData.value?.hidden_coupon_codes || []).map((code) => String(code).toLowerCase());
  return (businessData.value?.coupons_full || []).map((coupon) => ({
    ...coupon,
    is_hidden: hidden.includes(String(coupon.coupon_code).toLowerCase()),
  }));
});

const couponColumns = computed(() => [
  { key: 'coupon_code', label: t('coupons.code') },
  { key: 'coupon_type', label: t('dashboard.income_type') },
  { key: 'coupon_value', label: t('dashboard.avg_value') },
  { key: 'used', label: t('dashboard.used') },
]);

const legacyPayoutColumns = computed(() => [
  { key: 'date_exec', label: t('date.date') },
  { key: 'summ', label: t('summ') },
  { key: 'comment', label: t('admin.partners.comment') },
]);

const bonusCodeColumns = computed(() => [
  { key: 'bonus_code_value', label: t('dashboard.avg_value') },
  { key: 'bonus_code_cost', label: t('dashboard.bonus_codes_cost') },
  { key: 'created_at', label: t('date.date') },
]);

const payoutColumns = computed(() => [
  { key: 'created_at', label: t('date.date') },
  { key: 'withdrawal_amount', label: t('summ') },
  { key: 'received_amount', label: t('admin.partners.received_amount') },
  { key: 'status', label: t('admin.users.status') },
]);

const requisiteColumns = computed(() => [
  { key: 'id', label: 'ID' },
  { key: 'full_name', label: t('form.full_name') },
  { key: 'bank_name', label: t('admin.partners.bank_name') },
  { key: 'is_verified', label: t('admin.partners.requisite_status') },
  { key: 'is_active', label: t('dashboard.active') },
]);

// Анкета целиком — вместе со старыми полями (full_name, experience).
function applicationFields(application) {
  const fields = [
    ['admin.partners.app_status', application.status_name ? t('status.' + application.status_name) : null],
    ['form.last_name', application.last_name],
    ['form.first_name', application.first_name],
    ['form.middle_name', application.middle_name],
    ['form.full_name', application.full_name],
    ['form.specialty', application.specialty],
    ['admin.partners.experience', application.experience_years
      ? t('admin.partners.experience_years', { years: application.experience_years })
      : application.experience],
    ['form.phone', application.phone],
    ['email', application.email],
    ['city', application.city],
    ['form.company_name', application.company_name],
    ['form.comment', application.comment],
  ];

  return fields
    .filter(([, value]) => value !== null && value !== undefined && value !== '')
    .map(([key, value]) => ({ label: t(key), value }));
}

function applicationLinks(application) {
  const links = application.links || [];
  return links
    .map((link) => (typeof link === 'string' ? link : (link.url || link.link || '')))
    .filter(Boolean);
}

async function load({ refresh = false } = {}) {
  loading.value = true;
  notFound.value = false;

  try {
    const response = await api.get(`/admin/partners/${route.params.id}`, {
      params: refresh ? { refresh: 1 } : {},
    });

    partner.value = response.data.partner;
    applications.value = response.data.applications;
    requisites.value = response.data.requisites;
    payoutRequests.value = response.data.payoutRequests;
    businessData.value = response.data.businessData;
    hasJoomlaUser.value = response.data.hasJoomlaUser;
    failed.value = response.data.failed;
  } catch (e) {
    if (e.response?.status === 404) {
      notFound.value = true;
    } else {
      failed.value = true;
    }
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  if (!authStore.currentUser) {
    await authStore.fetchUser();
  }

  if (!authStore.isAdmin) {
    toast.init({ type: 'danger', message: t('admin.accessDenied') });
    router.push('/dashboard');
    return;
  }

  load();
});

const impersonatePartner = async () => {
  if (!confirm(t('admin.impersonation.confirm', { user: partner.value.name }))) {
    return;
  }

  try {
    const success = await authStore.impersonate(partner.value.id);
    if (success) {
      toast.init({ type: 'success', message: t('admin.impersonation.success', { user: partner.value.name }) });
      location.href = '/dashboard';
    } else {
      toast.init({ type: 'danger', message: authStore.error || t('admin.impersonation.error') });
    }
  } catch (e) {
    toast.init({ type: 'danger', message: t('admin.impersonation.error') });
  }
};
</script>

<style scoped>
.partner-card {
  padding: 20px;
}
</style>
