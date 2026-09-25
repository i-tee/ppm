<template>
  <div class="admin-partners">
    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.partners') }}</p>
      <p class="my-2">{{ $t('dashboard.partners_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <!-- Часть данных не догрузилась: честно говорим об этом и даём
         пересобрать список в обход кеша. Без деталей инфраструктуры. -->
    <VaAlert v-if="partial" color="warning" icon="warning" class="mb-4">
      <div class="flex items-center gap-4 flex-wrap">
        <span>{{ $t('admin.partners.partial_notice') }}</span>
        <VaButton size="small" preset="secondary" :loading="loading" @click="load({ refresh: true })">
          {{ $t('common.refresh') }}
        </VaButton>
      </div>
    </VaAlert>

    <!-- Поиск и фильтры -->
    <div class="flex flex-wrap gap-3 items-end mb-4">
      <VaInput
        v-model="search"
        :label="$t('admin.partners.search')"
        :placeholder="$t('admin.partners.search_placeholder')"
        clearable
        style="max-width: 280px;"
      >
        <template #prepend>
          <VaIcon name="search" />
        </template>
      </VaInput>

      <VaSelect
        v-model="statusId"
        :options="statusOptions"
        value-by="value"
        text-by="text"
        :label="$t('admin.partners.filter_status')"
        style="width: 200px;"
      />

      <VaSelect
        v-model="activity"
        :options="activityOptions"
        value-by="value"
        text-by="text"
        :label="$t('admin.partners.filter_activity')"
        style="width: 180px;"
      />

      <VaButton preset="secondary" icon="refresh" :loading="loading" @click="load({ refresh: true })">
        {{ $t('common.refresh') }}
      </VaButton>
    </div>

    <div v-if="loading && !allRows.length">
      <VaSkeleton variant="table" :rows="6" />
    </div>

    <div v-else-if="error">
      <VaAlert color="danger" icon="error">{{ $t('admin.partners.load_error') }}</VaAlert>
      <VaButton class="mt-2" preset="secondary" @click="load({ refresh: true })">
        {{ $t('common.refresh') }}
      </VaButton>
    </div>

    <template v-else>
      <VaCard>
        <VaDataTable
          class="partners-table"
          :items="pageRows"
          :columns="columns"
          :loading="loading"
          disable-client-side-sorting
          :sort-by="sortBy"
          :sorting-order="sortingOrder"
          hoverable
          @update:sortBy="onSortBy"
          @update:sortingOrder="onSortingOrder"
          @row:click="onRowClick"
        >
          <!-- Партнёр: имя-ссылка, под ним мелко email и специальность -->
          <template #cell(partner)="{ rowData }">
            <div class="flex items-start gap-2 flex-nowrap">
              <VaAvatar :src="rowData.avatar" size="small" class="bg-gray-200 flex-none mt-1">
                <VaIcon v-if="!rowData.avatar" name="person" size="small" class="text-gray-500" />
              </VaAvatar>
              <div class="min-w-0">
                <div class="flex items-center gap-1 flex-wrap">
                  <RouterLink
                    class="partner-name"
                    :to="cardLink(rowData.id)"
                    :title="$t('admin.partners.col_registered') + ': ' + (rowData.created_at ? formatDate(rowData.created_at) : '–')"
                  >{{ rowData.name }}</RouterLink>

                  <!-- Бейдж статуса анкеты — только если она не «Принято» -->
                  <VaBadge
                    v-if="showStatusBadge(rowData)"
                    :text="applicationStatusText(rowData)"
                    :color="applicationStatusColor(rowData)"
                  />

                  <!-- Ссылки из анкеты — иконка со счётчиком, список в поповере -->
                  <VaPopover v-if="partnerLinks(rowData).length" placement="bottom">
                    <template #body>
                      <div class="flex flex-col gap-1">
                        <a
                          v-for="(link, i) in partnerLinks(rowData)"
                          :key="i"
                          :href="link"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="partner-link"
                        >{{ link }}</a>
                      </div>
                    </template>
                    <span class="links-chip" :title="$t('admin.partners.col_links')">
                      <VaIcon name="link" size="small" />{{ partnerLinks(rowData).length }}
                    </span>
                  </VaPopover>
                </div>
                <div class="partner-sub" :title="rowData.email">{{ rowData.email }}</div>
                <div v-if="rowData.specialty" class="partner-sub" :title="rowData.specialty">
                  {{ rowData.specialty }}
                </div>
              </div>
            </div>
          </template>

          <!-- Активность: бейдж, под ним дата последнего оплаченного заказа -->
          <template #cell(activity)="{ rowData }">
            <template v-if="rowData.activity">
              <VaBadge
                :text="$t('admin.partners.activity_' + rowData.activity)"
                :color="activityColor(rowData.activity)"
              />
              <div v-if="rowData.last_order_at" class="partner-sub mt-1">
                {{ formatDate(rowData.last_order_at) }}
              </div>
            </template>
            <span v-else class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
          </template>

          <template #cell(balance)="{ rowData }">
            <span v-if="rowData.failed" class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
            <span v-else class="font-semibold">{{ formatMoney(rowData.balance) }}</span>
          </template>

          <template #cell(pending_payout_sum)="{ rowData }">
            <span v-if="rowData.failed" class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
            <span
              v-else-if="rowData.pending_payout_count"
              class="payout-pending"
              :title="$t('admin.partners.pending_payout_hint', { count: rowData.pending_payout_count })"
            >{{ formatMoney(rowData.pending_payout_sum) }}</span>
            <span v-else class="text-secondary">–</span>
          </template>

          <template #cell(total_accruals)="{ rowData }">
            <span v-if="rowData.failed" class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
            <span v-else>{{ formatMoney(rowData.total_accruals) }}</span>
          </template>

          <template #cell(orders_count)="{ rowData }">
            <span v-if="rowData.failed" class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
            <span v-else :title="$t('admin.partners.coupons_hint', { count: rowData.coupons_count })">
              {{ rowData.orders_count }}
            </span>
          </template>

          <template #cell(withdrawn_total)="{ rowData }">
            <span v-if="rowData.failed" class="cell-error">{{ $t('admin.partners.cell_error') }}</span>
            <span v-else :title="lastPayoutHint(rowData)">{{ formatMoney(rowData.withdrawn_total) }}</span>
          </template>

          <template #cell(has_verified_requisites)="{ rowData }">
            <VaIcon
              :name="rowData.has_verified_requisites ? 'check_circle' : 'cancel'"
              :color="rowData.has_verified_requisites ? 'success' : 'secondary'"
              :title="rowData.has_verified_requisites
                ? $t('admin.partners.requisite_verified')
                : $t('admin.partners.requisite_unverified')"
            />
          </template>

          <template #cell(actions)="{ rowData }">
            <div class="flex gap-1 flex-nowrap justify-center">
              <VaButton
                preset="secondary"
                size="small"
                icon="badge"
                :to="cardLink(rowData.id)"
                :title="$t('admin.partners.open_card')"
                :aria-label="$t('admin.partners.open_card')"
              />
              <VaButton
                v-if="currentUserId !== rowData.id"
                preset="plain"
                size="small"
                icon="login"
                :title="$t('admin.users.impersonate')"
                :aria-label="$t('admin.users.impersonate')"
                @click="impersonateUser(rowData)"
              />
            </div>
          </template>
        </VaDataTable>
      </VaCard>

      <div v-if="!filteredRows.length" class="not-found-wrapper">
        <span>{{ $t('admin.users.notFound') }}</span>
        <VaButton preset="secondary" @click="resetFilters">{{ $t('admin.users.resetSearch') }}</VaButton>
      </div>

      <div class="pagination-wrapper" v-if="lastPage > 1">
        <VaPagination v-model="currentPage" :pages="lastPage" :visible-pages="7" buttons-preset="secondary" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useBase } from '@/composables/useBase';
import api from '@/api';

const { t } = useI18n();
const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();
const { formatDate } = useBase();

const PER_PAGE = 20;

// Порядок бейджей активности: сначала те, кто приносит заказы.
const ACTIVITY_RANK = { active: 0, quiet: 1, not_started: 2 };

// Компактные деньги: в таблице 9 колонок, копейки съедают ширину и почти
// всегда нулевые (баланс округляется вверх ещё на сервере).
const MONEY = new Intl.NumberFormat('ru-RU', {
  style: 'currency',
  currency: 'RUB',
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
});
function formatMoney(value) {
  return MONEY.format(Number(value) || 0);
}

const allRows = ref([]);
const loading = ref(false);
const error = ref(false);
const partial = ref(false);
const currentPage = ref(1);

const search = ref('');
const statusId = ref('');
const activity = ref('');

// Пустой sortBy – сортировка по умолчанию (активность, затем заработок).
const sortBy = ref('');
const sortingOrder = ref('desc');

const currentUserId = computed(() => authStore.currentUser?.id);

const statusOptions = computed(() => [
  { text: t('admin.partners.filter_any'), value: '' },
  { text: t('status.new'), value: 0 },
  { text: t('status.in_progress'), value: 1 },
  { text: t('status.accepted'), value: 2 },
  { text: t('status.rejected'), value: 3 },
  { text: t('status.blocked'), value: 9 },
]);

const activityOptions = computed(() => [
  { text: t('admin.partners.filter_any'), value: '' },
  { text: t('admin.partners.activity_active'), value: 'active' },
  { text: t('admin.partners.activity_quiet'), value: 'quiet' },
  { text: t('admin.partners.activity_not_started'), value: 'not_started' },
]);

// Рабочая область при окне 1440 px: 1440 − 256 (сайдбар 16rem) − 32 (p-4
// у контента) − 40 (padding экрана) ≈ 1112 px. Сумма ширин ниже – 1055 px,
// то есть таблица укладывается без горизонтальной прокрутки даже с
// вертикальным скроллбаром. Раскладка ещё и `table-layout: fixed`, так что
// длинный email не может растянуть колонку.
const columns = computed(() => [
  { key: 'partner', label: t('admin.partners.col_partner'), sortable: true, width: '245px' },
  { key: 'activity', label: t('admin.partners.col_activity'), sortable: true, width: '120px' },
  { key: 'balance', label: t('admin.partners.col_balance'), sortable: true, width: '105px', tdAlign: 'right', thAlign: 'right' },
  { key: 'pending_payout_sum', label: t('admin.partners.col_pending_payout'), sortable: true, width: '115px', tdAlign: 'right', thAlign: 'right' },
  { key: 'total_accruals', label: t('admin.partners.col_earned'), sortable: true, width: '115px', tdAlign: 'right', thAlign: 'right' },
  { key: 'orders_count', label: t('admin.partners.col_orders'), sortable: true, width: '75px', tdAlign: 'right', thAlign: 'right' },
  { key: 'withdrawn_total', label: t('admin.partners.col_withdrawn'), sortable: true, width: '110px', tdAlign: 'right', thAlign: 'right' },
  { key: 'has_verified_requisites', label: t('admin.partners.col_requisites'), sortable: true, width: '90px', tdAlign: 'center', thAlign: 'center' },
  { key: 'actions', label: '', sortable: false, width: '80px', tdAlign: 'center' },
]);

// --- Фильтры, сортировка и страницы – на клиенте ---
//
// Сервер и так собирает и кеширует список целиком, а порядок по умолчанию
// (активность → заработок) одним полем `sort` не выражается. Поэтому
// забираем список целиком и режем на страницы здесь.

const filteredRows = computed(() => {
  let rows = allRows.value;

  const needle = search.value.trim().toLowerCase();
  if (needle) {
    rows = rows.filter((row) => String(row.name || '').toLowerCase().includes(needle)
      || String(row.email || '').toLowerCase().includes(needle));
  }

  if (statusId.value !== '') {
    rows = rows.filter((row) => row.application_status_id === statusId.value);
  }

  if (activity.value !== '') {
    rows = rows.filter((row) => row.activity === activity.value);
  }

  return rows;
});

function sortValue(row, key) {
  if (key === 'partner') return String(row.name || '').toLowerCase();
  if (key === 'activity') return ACTIVITY_RANK[row.activity] ?? 99;
  if (key === 'has_verified_requisites') return row.has_verified_requisites ? 1 : 0;
  return row[key];
}

const sortedRows = computed(() => {
  const rows = [...filteredRows.value];

  if (!sortBy.value) {
    // По умолчанию: «Активен» → «Затих» → «Не начал», внутри – по
    // заработку по убыванию. Строки со сбоем загрузки – в самом конце.
    return rows.sort((a, b) => {
      if (a.failed !== b.failed) return a.failed ? 1 : -1;
      const rankA = ACTIVITY_RANK[a.activity] ?? 99;
      const rankB = ACTIVITY_RANK[b.activity] ?? 99;
      if (rankA !== rankB) return rankA - rankB;
      return (Number(b.total_accruals) || 0) - (Number(a.total_accruals) || 0);
    });
  }

  const desc = sortingOrder.value !== 'asc';

  return rows.sort((a, b) => {
    // Партнёры, по которым данные не догрузились, – всегда в конце,
    // в каком бы направлении ни сортировали.
    if (a.failed !== b.failed) return a.failed ? 1 : -1;

    const left = sortValue(a, sortBy.value);
    const right = sortValue(b, sortBy.value);

    if (left == null && right == null) return 0;
    if (left == null) return 1;
    if (right == null) return -1;

    const result = (typeof left === 'number' && typeof right === 'number')
      ? left - right
      : String(left).localeCompare(String(right), 'ru');

    return desc ? -result : result;
  });
});

const lastPage = computed(() => Math.max(Math.ceil(sortedRows.value.length / PER_PAGE), 1));

const pageRows = computed(() => {
  const page = Math.min(currentPage.value, lastPage.value);
  return sortedRows.value.slice((page - 1) * PER_PAGE, page * PER_PAGE);
});

// Фильтры меняют число страниц – возвращаемся на первую.
watch([search, statusId, activity], () => {
  currentPage.value = 1;
});

function cardLink(id) {
  return { name: 'PartnerCard', params: { id } };
}

function activityColor(value) {
  if (value === 'active') return 'success';
  if (value === 'quiet') return 'warning';
  return 'secondary';
}

// Бейдж статуса анкеты показываем только когда он требует внимания:
// «Принято» – норма и место в таблице не занимает.
function showStatusBadge(row) {
  return row.application_status_name !== 'accepted';
}

function applicationStatusText(row) {
  return row.application_status_name
    ? t('status.' + row.application_status_name)
    : t('admin.partners.no_application');
}

function applicationStatusColor(row) {
  if (!row.application_status_name) return 'secondary';
  if (row.application_status_name === 'rejected' || row.application_status_name === 'blocked') return 'danger';
  return 'warning';
}

// Анкета хранит ссылки как массив строк либо объектов {url}.
function partnerLinks(row) {
  return (row.links || [])
    .map((link) => (typeof link === 'string' ? link : (link.url || link.link || '')))
    .filter(Boolean);
}

function lastPayoutHint(row) {
  return row.last_payout_at
    ? t('admin.partners.col_last_payout') + ': ' + formatDate(row.last_payout_at)
    : t('admin.partners.no_payouts');
}

function onSortBy(value) {
  sortBy.value = value || '';
  currentPage.value = 1;
}

function onSortingOrder(value) {
  // Vuestic отдаёт null на третьем клике – возвращаемся к порядку по умолчанию.
  if (!value) {
    sortBy.value = '';
    sortingOrder.value = 'desc';
  } else {
    sortingOrder.value = value;
  }
  currentPage.value = 1;
}

// Клик по строке открывает карточку – но не когда кликнули по ссылке,
// кнопке или поповеру со ссылками внутри строки.
// VaDataTable отдаёт (DOM-событие, строка), строка – с `source`/`rowData`.
function onRowClick(domEvent, row) {
  const target = domEvent?.target;
  if (target?.closest && target.closest('a, button, .va-popover, .links-chip')) {
    return;
  }

  const id = row?.source?.id ?? row?.rowData?.id;
  if (id) {
    router.push(cardLink(id));
  }
}

async function load({ refresh = false } = {}) {
  loading.value = true;
  error.value = false;

  try {
    // Сервер отдаёт максимум 100 строк за раз – добираем остальные страницы.
    // refresh просим только на первой: иначе список пересобирался бы заново
    // на каждой странице.
    const first = await api.get('/admin/partners', {
      params: { page: 1, per_page: 100, ...(refresh ? { refresh: 1 } : {}) },
    });

    const rows = [...first.data.data];
    partial.value = first.data.partial;

    for (let page = 2; page <= first.data.last_page; page++) {
      const next = await api.get('/admin/partners', { params: { page, per_page: 100 } });
      rows.push(...next.data.data);
      partial.value = partial.value || next.data.partial;
    }

    allRows.value = rows;
    currentPage.value = 1;
  } catch (e) {
    error.value = true;
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

function resetFilters() {
  search.value = '';
  statusId.value = '';
  activity.value = '';
  currentPage.value = 1;
}

const impersonateUser = async (row) => {
  if (!confirm(t('admin.impersonation.confirm', { user: row.name }))) {
    return;
  }

  loading.value = true;
  try {
    const success = await authStore.impersonate(row.id);
    if (success) {
      toast.init({ type: 'success', message: t('admin.impersonation.success', { user: row.name }) });
      location.href = '/dashboard';
    } else {
      toast.init({ type: 'danger', message: authStore.error || t('admin.impersonation.error') });
    }
  } catch (e) {
    toast.init({ type: 'danger', message: t('admin.impersonation.error') });
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.admin-partners {
  padding: 20px;
}

/* Таблица должна укладываться в рабочую область при окне 1440 px.
   Фиксированная раскладка не даёт длинному email растянуть колонку. */
.partners-table {
  --va-data-table-cell-padding: 8px 10px;
  width: 100%;
  table-layout: fixed;
}

.partners-table :deep(td),
.partners-table :deep(th) {
  font-size: 13px;
}

/* Узкие колонки: заголовок переносится по словам, а не распирает таблицу. */
.partners-table :deep(th) {
  white-space: normal;
  line-height: 1.25;
}

.partners-table :deep(tbody tr) {
  cursor: pointer;
}

.partner-name {
  font-weight: 600;
  color: var(--va-primary);
  text-decoration: underline;
  overflow-wrap: anywhere;
}

/* Мелкая строка под именем: email, специальность, дата заказа.
   Контраст держим читаемым, не «серым по белому». */
.partner-sub {
  font-size: 11px;
  line-height: 1.3;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.links-chip {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: 11px;
  color: var(--va-primary);
  cursor: pointer;
}

.partner-link {
  color: var(--va-primary);
  text-decoration: underline;
  font-size: 12px;
  max-width: 320px;
  overflow-wrap: anywhere;
}

/* Заявка на вывод – заметно, но без крика. */
.payout-pending {
  font-weight: 700;
  color: var(--va-warning);
}

.cell-error {
  color: var(--va-danger);
  font-size: 11px;
}

.not-found-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 0;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
</style>
