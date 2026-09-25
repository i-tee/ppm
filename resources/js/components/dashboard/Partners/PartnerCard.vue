<template>
  <div class="partner-card">
    <!-- Шапка: «К списку» над именем, имя слева, справа – вход под партнёром -->
    <div class="card-head">
      <VaButton
        class="mb-2"
        preset="plain"
        size="small"
        icon="arrow_back"
        :to="{ name: 'Partners' }"
      >{{ $t('admin.partners.back_to_list') }}</VaButton>

      <div class="head-row">
        <div class="min-w-0">
          <p class="va-h4 my-0">{{ partner?.name || $t('admin.partners.card_title') }}</p>
          <p v-if="partner" class="head-sub">
            {{ partner.email }}
            <span class="head-dot">·</span>
            {{ $t('admin.partners.col_registered') }}: {{ formatDate(partner.created_at) }}
          </p>
        </div>

        <VaButton
          v-if="partner && currentUserId !== partner.id"
          preset="secondary"
          icon="login"
          @click="impersonatePartner"
        >{{ $t('admin.users.impersonate') }}</VaButton>
      </div>

      <VaDivider class="my-4" />
    </div>

    <div v-if="loading">
      <VaSkeleton variant="table" :rows="8" />
    </div>

    <VaAlert v-else-if="notFound" color="danger" icon="error">
      {{ $t('admin.partners.not_found') }}
    </VaAlert>

    <div v-else class="space-y-6">
      <!-- 1. Анкета – сразу под шапкой -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.application') }}</p>

        <p v-if="!applications.length" class="text-secondary">{{ $t('admin.partners.no_application') }}</p>

        <div v-for="(application, index) in applications" :key="application.id">
          <div v-if="index > 0" class="mt-4">
            <VaDivider class="my-2" />
            <p class="text-secondary text-sm mb-2">{{ $t('admin.partners.older_application') }}</p>
          </div>

          <div class="flex items-center gap-2 mb-3">
            <VaBadge
              :text="application.status_name ? $t('status.' + application.status_name) : $t('status.unknown')"
              :color="applicationStatusColor(application)"
            />
            <span class="text-secondary text-sm">{{ formatDate(application.created_at) }}</span>
          </div>

          <div class="app-grid">
            <div v-for="field in applicationFields(application)" :key="field.label" class="app-field">
              <span class="app-label">{{ field.label }}</span>
              <span class="app-value">{{ field.value }}</span>
            </div>
          </div>

          <div v-if="applicationLinks(application).length" class="app-field mt-2">
            <span class="app-label">{{ $t('admin.partners.col_links') }}</span>
            <span class="app-value">
              <a
                v-for="(link, i) in applicationLinks(application)"
                :key="i"
                :href="link"
                target="_blank"
                rel="noopener noreferrer"
                class="app-link"
              >{{ link }}</a>
            </span>
          </div>

          <!-- Старые анкеты (до этапа 1.7) – отдельным блоком, чтобы не
               путать со свежими полями -->
          <div v-if="legacyFields(application).length" class="legacy-block">
            <p class="legacy-title">{{ $t('admin.partners.legacy_block') }}</p>
            <div class="app-grid">
              <div v-for="field in legacyFields(application)" :key="field.label" class="app-field">
                <span class="app-label">{{ field.label }}</span>
                <span class="app-value">{{ field.value }}</span>
              </div>
            </div>
          </div>
        </div>
      </VaCard>

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

        <!-- График начислений: по умолчанию – всё время -->
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
          <VaDataTable class="readable-table" :items="coupons" :columns="couponColumns">
            <template #cell(coupon_code)="{ rowData }">
              <span class="font-semibold">{{ rowData.coupon_code }}</span>
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
              <span :class="isCouponUsed(rowData) ? 'text-success font-semibold' : 'text-secondary'">
                {{ isCouponUsed(rowData) ? $t('admin.partners.yes') : $t('admin.partners.no') }}
              </span>
            </template>
          </VaDataTable>
        </VaCard>

        <!-- Выплаты старой партнёрки и списания -->
        <VaCard class="p-4">
          <p class="va-h6 mb-3">{{ $t('dashboard.withdrawals_total') }}</p>
          <VaDataTable
            v-if="businessData.withdrawals.withdrawals.length"
            class="readable-table"
            :items="businessData.withdrawals.withdrawals"
            :columns="legacyPayoutColumns"
          >
            <template #cell(summ)="{ rowData }">{{ formatPrice(rowData.summ) }}</template>
          </VaDataTable>
          <p v-else class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>

          <template v-if="businessData.trueBonusCode.trueBonusCodes.length">
            <VaDivider class="my-4" />
            <p class="va-h6 mb-3">{{ $t('dashboard.bonus_codes_expense') }}</p>
            <VaDataTable
              class="readable-table"
              :items="businessData.trueBonusCode.trueBonusCodes"
              :columns="bonusCodeColumns"
            >
              <template #cell(bonus_code_cost)="{ rowData }">{{ formatPrice(rowData.bonus_code_cost) }}</template>
              <template #cell(created_at)="{ rowData }">{{ formatDate(rowData.created_at) }}</template>
            </VaDataTable>
          </template>
        </VaCard>
      </template>

      <!-- История заявок на вывод -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.withdrawal_requests') }}</p>
        <VaDataTable
          v-if="payoutRequests.length"
          class="readable-table"
          :items="payoutRequests"
          :columns="payoutColumns"
        >
          <template #cell(withdrawal_amount)="{ rowData }">{{ formatPrice(rowData.withdrawal_amount) }}</template>
          <template #cell(received_amount)="{ rowData }">{{ formatPrice(rowData.received_amount) }}</template>
          <template #cell(status)="{ rowData }">
            <VaBadge :text="getStatusText(rowData.status)" :color="getStatusColor(rowData.status)" />
            <!-- У отменённых причина лежит в note (её дописывает отмена заявки) -->
            <div v-if="rowData.status === 50 && rowData.note" class="cancel-reason">{{ rowData.note }}</div>
          </template>
          <template #cell(created_at)="{ rowData }">{{ formatDate(rowData.created_at) }}</template>
        </VaDataTable>
        <p v-else class="text-secondary">{{ $t('admin.partners.empty_section') }}</p>
      </VaCard>

      <!-- Реквизиты со статусом проверки -->
      <VaCard class="p-4">
        <p class="va-h6 mb-3">{{ $t('dashboard.requisites') }}</p>
        <VaDataTable v-if="requisites.length" class="readable-table" :items="requisites" :columns="requisiteColumns">
          <template #cell(is_verified)="{ rowData }">
            <VaBadge
              :text="rowData.is_verified ? $t('admin.partners.requisite_verified') : $t('admin.partners.requisite_unverified')"
              :color="rowData.is_verified ? 'success' : 'warning'"
            />
          </template>
          <template #cell(is_active)="{ rowData }">
            <span :class="rowData.is_active ? 'text-success' : 'text-secondary'">
              {{ rowData.is_active ? $t('admin.partners.yes') : $t('admin.partners.no') }}
            </span>
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
import { useSettingsStore } from '@/stores/settings';
import { useBase } from '@/composables/useBase';
import { usePayoutStatus } from '@/composables/usePayoutStatus';
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
const settingsStore = useSettingsStore();
const { formatPrice, formatDate } = useBase();
const { getStatusText, getStatusColor } = usePayoutStatus();

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
// Админу важна вся история партнёра, поэтому по умолчанию – «Всё время».

const periodPreset = ref('all');

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

// Бонусник, погашённый на новом сайте, в Joomla остаётся used=0 – учитываем оба признака.
function isCouponUsed(coupon) {
  return coupon.used == 1 || coupon.backend_used == 1;
}

const couponColumns = computed(() => [
  { key: 'coupon_code', label: t('coupons.code') },
  { key: 'coupon_type', label: t('admin.partners.coupon_type') },
  { key: 'coupon_value', label: t('coupons.value') },
  { key: 'used', label: t('coupons.used') },
]);

const legacyPayoutColumns = computed(() => [
  { key: 'date_exec', label: t('date.date') },
  { key: 'summ', label: t('summ') },
  { key: 'comment', label: t('admin.partners.comment') },
]);

const bonusCodeColumns = computed(() => [
  { key: 'bonus_code_value', label: t('coupons.bonus_code_value') },
  { key: 'bonus_code_cost', label: t('coupons.bonus_code_cost') },
  { key: 'created_at', label: t('date.date') },
]);

const payoutColumns = computed(() => [
  { key: 'created_at', label: t('date.date'), width: '110px' },
  { key: 'withdrawal_amount', label: t('summ'), width: '130px' },
  { key: 'received_amount', label: t('admin.partners.received_amount'), width: '130px' },
  { key: 'status', label: t('payoutRequest.status.status') },
]);

const requisiteColumns = computed(() => [
  { key: 'id', label: 'ID', width: '70px' },
  { key: 'full_name', label: t('form.full_name') },
  { key: 'bank_name', label: t('admin.partners.bank_name') },
  { key: 'is_verified', label: t('admin.partners.requisite_status') },
  { key: 'is_active', label: t('dashboard.active') },
]);

function applicationStatusColor(application) {
  if (application.status_name === 'accepted') return 'success';
  if (application.status_name === 'rejected' || application.status_name === 'blocked') return 'danger';
  return 'warning';
}

// Форма занятости – из настроек программы (GET /ps), как на экране заявок.
function partnerTypeText(application) {
  const types = settingsStore.data?.partner_types || [];
  const type = types.find((item) => item.id === application.partner_type_id);
  return type ? t('partners.partner_types.' + type.name) : null;
}

// Актуальные поля анкеты (этап 1.7 и позже).
function applicationFields(application) {
  const fullName = [application.last_name, application.first_name, application.middle_name]
    .filter(Boolean)
    .join(' ');

  const fields = [
    ['form.full_name', fullName],
    ['form.phone', application.phone],
    ['email', application.email],
    ['city', application.city],
    ['form.specialty', application.specialty],
    ['form.experience_years', application.experience_years],
    ['business_form', partnerTypeText(application)],
    ['form.company_name', application.company_name],
    ['form.comment', application.comment],
  ];

  return fields
    .filter(([, value]) => value !== null && value !== undefined && value !== '')
    .map(([key, value]) => ({ label: t(key), value }));
}

// Поля старых анкет – отдельным блоком.
function legacyFields(application) {
  const fields = [
    ['form.full_name', application.full_name],
    ['form.experience', application.experience],
  ];

  return fields
    .filter(([, value]) => value !== null && value !== undefined && value !== '')
    .map(([key, value]) => ({ label: t(key), value }));
}

function applicationLinks(application) {
  return (application.links || [])
    .map((link) => (typeof link === 'string' ? link : (link.url || link.link || '')))
    .filter(Boolean);
}

async function load({ refresh = false } = {}) {
  loading.value = true;
  notFound.value = false;
  failed.value = false;

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

  settingsStore.load();
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

.card-head {
  margin-top: 16px;
}

.head-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.head-sub {
  margin: 4px 0 0;
  font-size: 13px;
  color: #6b7280;
}

.head-dot {
  margin: 0 4px;
}

/* Таблицы карточки: текст по умолчанию слишком бледный на белом фоне. */
.readable-table :deep(td) {
  color: #1f2937;
  font-size: 13px;
}

.readable-table :deep(th) {
  color: #374151;
  font-weight: 600;
}

.app-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 4px 24px;
}

@media (min-width: 768px) {
  .app-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.app-field {
  display: flex;
  gap: 8px;
  align-items: baseline;
}

.app-label {
  color: #6b7280;
  font-size: 13px;
  flex: 0 0 auto;
  min-width: 130px;
}

.app-value {
  color: #1f2937;
  overflow-wrap: anywhere;
}

.app-link {
  color: var(--va-primary);
  text-decoration: underline;
  margin-right: 12px;
  overflow-wrap: anywhere;
}

.legacy-block {
  margin-top: 12px;
  padding: 12px;
  border-radius: 8px;
  background: #f3f4f6;
}

.legacy-title {
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 6px;
}

.cancel-reason {
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
  white-space: pre-line;
}
</style>
