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
        <VaButton size="small" preset="secondary" :loading="loading" @click="reload({ refresh: true })">
          {{ $t('common.refresh') }}
        </VaButton>
      </div>
    </VaAlert>

    <!-- Поиск и фильтры -->
    <div class="flex flex-wrap gap-4 items-end mb-4">
      <VaInput
        v-model="search"
        :label="$t('admin.partners.search')"
        :placeholder="$t('admin.partners.search_placeholder')"
        clearable
        style="max-width: 320px;"
        @keyup.enter="applySearch"
        @clear="applySearch"
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
        class="min-w-56"
      />

      <VaSelect
        v-model="activity"
        :options="activityOptions"
        value-by="value"
        text-by="text"
        :label="$t('admin.partners.filter_activity')"
        class="min-w-56"
      />

      <VaButton preset="secondary" :loading="loading" @click="applySearch">
        {{ $t('admin.users.search') }}
      </VaButton>
    </div>

    <div v-if="loading && !rows.length">
      <VaSkeleton variant="table" :rows="6" />
    </div>

    <div v-else-if="error">
      <VaAlert color="danger" icon="error">{{ $t('admin.partners.load_error') }}</VaAlert>
      <VaButton class="mt-2" preset="secondary" @click="reload({ refresh: true })">
        {{ $t('common.refresh') }}
      </VaButton>
    </div>

    <template v-else>
      <VaCard>
        <div class="partners-table-scroll">
          <VaDataTable
            :items="rows"
            :columns="columns"
            :loading="loading"
            disable-client-side-sorting
            :sort-by="sortBy"
            :sorting-order="sortingOrder"
            @update:sortBy="onSortBy"
            @update:sortingOrder="onSortingOrder"
          >
            <template #cell(name)="{ rowData }">
              <div class="flex items-center gap-2 flex-nowrap">
                <VaAvatar :src="rowData.avatar" size="small" class="bg-gray-200 flex-none">
                  <VaIcon v-if="!rowData.avatar" name="person" size="small" class="text-gray-500" />
                </VaAvatar>
                <div class="min-w-0">
                  <RouterLink class="font-semibold text-primary underline" :to="cardLink(rowData.id)">
                    {{ rowData.name }}
                  </RouterLink>
                  <div class="text-xs text-secondary truncate">{{ rowData.email }}</div>
                </div>
              </div>
            </template>

            <template #cell(created_at)="{ rowData }">
              {{ rowData.created_at ? formatDate(rowData.created_at) : '–' }}
            </template>

            <template #cell(application_status_id)="{ rowData }">
              <span v-if="rowData.application_status_name">
                {{ $t('status.' + rowData.application_status_name) }}
              </span>
              <span v-else class="text-secondary">{{ $t('admin.partners.no_application') }}</span>
            </template>

            <template #cell(specialty)="{ rowData }">
              <div>{{ rowData.specialty || '–' }}</div>
              <div v-if="experienceText(rowData)" class="text-xs text-secondary">
                {{ experienceText(rowData) }}
              </div>
            </template>

            <template #cell(links)="{ rowData }">
              <div v-if="rowData.links && rowData.links.length" class="flex flex-col">
                <a
                  v-for="(link, i) in rowData.links"
                  :key="i"
                  :href="linkHref(link)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary underline truncate max-w-48"
                >{{ linkHref(link) }}</a>
              </div>
              <span v-else class="text-secondary">–</span>
            </template>

            <template #cell(balance)="{ rowData }">
              <MoneyCell :row="rowData" :value="rowData.balance" />
            </template>

            <template #cell(pending_payout_sum)="{ rowData }">
              <template v-if="rowData.failed">
                <span class="text-danger">{{ $t('admin.partners.cell_error') }}</span>
              </template>
              <template v-else-if="rowData.pending_payout_count">
                {{ formatPrice(rowData.pending_payout_sum) }}
                <span class="text-xs text-secondary">({{ rowData.pending_payout_count }})</span>
              </template>
              <span v-else class="text-secondary">–</span>
            </template>

            <template #cell(total_accruals)="{ rowData }">
              <MoneyCell :row="rowData" :value="rowData.total_accruals" />
            </template>

            <template #cell(orders_count)="{ rowData }">
              <CountCell :row="rowData" :value="rowData.orders_count" />
            </template>

            <template #cell(coupons_count)="{ rowData }">
              <CountCell :row="rowData" :value="rowData.coupons_count" />
            </template>

            <template #cell(withdrawn_total)="{ rowData }">
              <MoneyCell :row="rowData" :value="rowData.withdrawn_total" />
            </template>

            <template #cell(has_verified_requisites)="{ rowData }">
              <VaIcon
                :name="rowData.has_verified_requisites ? 'check_circle' : 'remove'"
                :color="rowData.has_verified_requisites ? 'success' : 'secondary'"
              />
            </template>

            <template #cell(last_payout_at)="{ rowData }">
              <span v-if="rowData.failed" class="text-danger">{{ $t('admin.partners.cell_error') }}</span>
              <span v-else>{{ rowData.last_payout_at ? formatDate(rowData.last_payout_at) : '–' }}</span>
            </template>

            <template #cell(activity)="{ rowData }">
              <VaBadge
                v-if="rowData.activity"
                :text="$t('admin.partners.activity_' + rowData.activity)"
                :color="activityColor(rowData.activity)"
              />
              <span v-else class="text-danger">{{ $t('admin.partners.cell_error') }}</span>
            </template>

            <template #cell(actions)="{ rowData }">
              <div class="flex gap-1 flex-nowrap">
                <VaButton preset="secondary" size="small" icon="badge" :to="cardLink(rowData.id)">
                  {{ $t('admin.partners.open_card') }}
                </VaButton>
                <VaButton
                  v-if="currentUserId !== rowData.id"
                  preset="plain"
                  size="small"
                  icon="login"
                  @click="impersonateUser(rowData)"
                >
                  {{ $t('admin.users.impersonate') }}
                </VaButton>
              </div>
            </template>
          </VaDataTable>
        </div>
      </VaCard>

      <div v-if="!rows.length" class="not-found-wrapper">
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
import { ref, computed, onMounted, watch, h } from 'vue';
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
const { formatPrice, formatDate } = useBase();

// Денежная ячейка: у партнёра, по которому данные не догрузились, вместо
// суммы – «Ошибка загрузки» (неполные цифры не показываем).
const MoneyCell = (props) => props.row.failed
  ? h('span', { class: 'text-danger' }, t('admin.partners.cell_error'))
  : h('span', formatPrice(props.value));
MoneyCell.props = ['row', 'value'];

const CountCell = (props) => props.row.failed
  ? h('span', { class: 'text-danger' }, t('admin.partners.cell_error'))
  : h('span', String(props.value ?? 0));
CountCell.props = ['row', 'value'];

const rows = ref([]);
const loading = ref(false);
const error = ref(false);
const partial = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);

const search = ref('');
const statusId = ref('');
const activity = ref('');

const sortBy = ref('created_at');
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

const columns = computed(() => [
  { key: 'name', label: t('admin.partners.col_partner'), sortable: true },
  { key: 'created_at', label: t('admin.partners.col_registered'), sortable: true },
  { key: 'application_status_id', label: t('admin.partners.col_application'), sortable: true },
  { key: 'specialty', label: t('admin.partners.col_specialty'), sortable: false },
  { key: 'links', label: t('admin.partners.col_links'), sortable: false },
  { key: 'activity', label: t('admin.partners.col_activity'), sortable: true },
  { key: 'balance', label: t('admin.partners.col_balance'), sortable: true },
  { key: 'pending_payout_sum', label: t('admin.partners.col_pending_payout'), sortable: false },
  { key: 'total_accruals', label: t('admin.partners.col_earned'), sortable: true },
  { key: 'orders_count', label: t('admin.partners.col_orders'), sortable: true },
  { key: 'coupons_count', label: t('admin.partners.col_coupons'), sortable: true },
  { key: 'withdrawn_total', label: t('admin.partners.col_withdrawn'), sortable: true },
  { key: 'has_verified_requisites', label: t('admin.partners.col_requisites'), sortable: false },
  { key: 'last_payout_at', label: t('admin.partners.col_last_payout'), sortable: true },
  { key: 'actions', label: t('admin.users.actions'), sortable: false },
]);

function cardLink(id) {
  return { name: 'PartnerCard', params: { id } };
}

function activityColor(value) {
  if (value === 'active') return 'success';
  if (value === 'quiet') return 'warning';
  return 'secondary';
}

// Анкета хранит ссылки как массив строк либо объектов {url}.
function linkHref(link) {
  if (!link) return '';
  return typeof link === 'string' ? link : (link.url || link.link || '');
}

function experienceText(row) {
  if (row.experience_years) return t('admin.partners.experience_years', { years: row.experience_years });
  return row.experience || '';
}

async function reload({ refresh = false } = {}) {
  loading.value = true;
  error.value = false;

  try {
    const params = {
      page: currentPage.value,
      per_page: 20,
      sort: sortBy.value,
      dir: sortingOrder.value,
    };
    if (search.value.trim()) params.q = search.value.trim();
    if (statusId.value !== '') params.status_id = statusId.value;
    if (activity.value !== '') params.activity = activity.value;
    if (refresh) params.refresh = 1;

    const response = await api.get('/admin/partners', { params });
    rows.value = response.data.data;
    lastPage.value = response.data.last_page;
    currentPage.value = response.data.current_page;
    partial.value = response.data.partial;
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function applySearch() {
  currentPage.value = 1;
  reload();
}

function resetFilters() {
  search.value = '';
  statusId.value = '';
  activity.value = '';
  currentPage.value = 1;
  reload();
}

function onSortBy(value) {
  sortBy.value = value;
  currentPage.value = 1;
  reload();
}

function onSortingOrder(value) {
  // Vuestic отдаёт null на третьем клике – возвращаемся к сортировке по убыванию.
  sortingOrder.value = value || 'desc';
  currentPage.value = 1;
  reload();
}

watch(currentPage, () => reload());

onMounted(async () => {
  if (!authStore.currentUser) {
    await authStore.fetchUser();
  }

  if (!authStore.isAdmin) {
    toast.init({ type: 'danger', message: t('admin.accessDenied') });
    router.push('/dashboard');
    return;
  }

  reload();
});

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

/* Колонок много (решение владельца) – таблицу прокручиваем по горизонтали,
   чтобы не жать текст. */
.partners-table-scroll {
  overflow-x: auto;
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
