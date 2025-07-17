<template>
  <template v-if="isAdmin">
    <div>
      <h2>{{ $t('partnerApplications.title') }}</h2>

      <!-- Фильтры -->
      <div class="filters mb-4 flex gap-4">
        <VaSelect v-model="filters.status_id" :options="statusOptions" :label="$t('partnerApplications.status')" />
        <VaSelect v-model="filters.cooperation_type_id" :options="cooperationTypeOptions" :label="$t('partnerApplications.cooperation_type')" />
        <VaSelect v-model="filters.partner_type_id" :options="partnerTypeOptions" :label="$t('partnerApplications.partner_type')" />
      </div>

      <!-- Таблица -->
      <VaDataTable
        :items="applications"
        :columns="columns"
        :sort-by.sync="sortBy"
        :sort-desc.sync="sortDesc"
        :per-page="perPage"
        :current-page="currentPage"
        @sorted="fetchApplications"
        @page-selected="fetchApplications"
      >
        <template #cell(actions)="slotProps">
          <VaButton flat small @click="editApplication(slotProps.item)">{{ $t('actions.edit') }}</VaButton>
          <VaButton flat small color="danger" @click="deleteApplication(slotProps.item.id)">{{ $t('actions.delete') }}</VaButton>
        </template>
      </VaDataTable>

      <!-- Пагинация -->
      <VaPagination v-model="currentPage" :pages="totalPages" class="mt-4" />

      <!-- Кнопка для создания -->
      <VaButton @click="openCreateModal" class="mt-4">{{ $t('partnerApplications.create') }}</VaButton>

      <!-- Модальное окно -->
      <VaModal v-model="showModal" :title="modalTitle">
        <form @submit.prevent="saveApplication">
          <VaInput v-model="form.full_name" :label="$t('partnerApplications.full_name')" required />
          <VaInput v-model="form.phone" :label="$t('partnerApplications.phone')" required />
          <VaInput v-model="form.email" :label="$t('partnerApplications.email')" type="email" />
          <VaSelect v-model="form.cooperation_type_id" :options="cooperationTypeOptions" :label="$t('partnerApplications.cooperation_type')" required />
          <VaSelect v-model="form.partner_type_id" :options="partnerTypeOptions" :label="$t('partnerApplications.partner_type')" />
          <VaInput v-model="form.company_name" :label="$t('partnerApplications.company_name')" />
          <VaInput v-model="form.city" :label="$t('partnerApplications.city')" />
          <VaTextarea v-model="form.experience" :label="$t('partnerApplications.experience')" />
          <VaTextarea v-model="form.comment" :label="$t('partnerApplications.comment')" />
          <VaSelect v-model="form.status_id" :options="statusOptions" :label="$t('partnerApplications.status')" />
          <VaButton type="submit" class="mt-4">{{ $t('actions.save') }}</VaButton>
        </form>
      </VaModal>
    </div>
  </template>
  <template v-else>
    <VaAlert color="danger" class="mt-4">
      {{ $t('errors.no_access') }}
    </VaAlert>
  </template>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`;

const isAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1) || props.user.effective_access_levels.includes(2));
});

const applications = ref([]);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(15);
const sortBy = ref('id');
const sortDesc = ref(false);
const filters = ref({
  status_id: null,
  cooperation_type_id: null,
  partner_type_id: null,
});

const showModal = ref(false);
const modalTitle = ref('');
const form = ref({
  id: null,
  full_name: '',
  phone: '',
  email: '',
  cooperation_type_id: null,
  partner_type_id: null,
  status_id: 0,
  company_name: '',
  experience: '',
  comment: '',
  city: '',
  links: [],
});

const statusOptions = [
  { value: 0, text: 'New' },
  { value: 1, text: 'In Progress' },
  { value: 2, text: 'Accepted' },
  { value: 3, text: 'Rejected' },
];

// Пример данных, замените на реальные из Partners::getSettings()
const cooperationTypeOptions = [
  { value: 1, text: 'Type 1' },
  { value: 2, text: 'Type 2' },
];
const partnerTypeOptions = [
  { value: 1, text: 'Partner 1' },
  { value: 2, text: 'Partner 2' },
];

const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'full_name', label: t('partnerApplications.full_name'), sortable: true },
  { key: 'phone', label: t('partnerApplications.phone'), sortable: true },
  { key: 'email', label: t('partnerApplications.email'), sortable: true },
  { key: 'status_name', label: t('partnerApplications.status'), sortable: true },
  { key: 'actions', label: t('actions.title') },
];

const fetchApplications = async () => {
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      sort_by: sortBy.value,
      sort_direction: sortDesc.value ? 'desc' : 'asc',
      ...filters.value,
    };
    const response = await axios.get('/api/partner-applications', { params });
    applications.value = response.data.data;
    totalPages.value = response.data.last_page;
  } catch (error) {
    toast.init({ message: t('errors.fetch_failed'), color: 'danger' });
  }
};

const openCreateModal = () => {
  form.value = { id: null, full_name: '', phone: '', email: '', cooperation_type_id: null, partner_type_id: null, status_id: 0, company_name: '', experience: '', comment: '', city: '', links: [] };
  modalTitle.value = t('partnerApplications.create');
  showModal.value = true;
};

const editApplication = (application) => {
  form.value = { ...application, links: application.links || [] };
  modalTitle.value = t('partnerApplications.edit');
  showModal.value = true;
};

const saveApplication = async () => {
  try {
    if (form.value.id) {
      await axios.put(`/api/partner-applications/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/partner-applications', form.value);
    }
    showModal.value = false;
    fetchApplications();
    toast.init({ message: t('success.saved'), color: 'success' });
  } catch (error) {
    toast.init({ message: t('errors.save_failed'), color: 'danger' });
  }
};

const deleteApplication = async (id) => {
  if (confirm(t('confirm.delete'))) {
    try {
      await axios.delete(`/api/partner-applications/${id}`);
      fetchApplications();
      toast.init({ message: t('success.deleted'), color: 'success' });
    } catch (error) {
      toast.init({ message: t('errors.delete_failed'), color: 'danger' });
    }
  }
};

onMounted(() => {
  if (isAdmin.value) {
    fetchApplications();
  } else {
    toast.init({ message: t('errors.no_access'), color: 'danger' });
  }
});
</script>

<style scoped>
.filters {
  display: flex;
  gap: 1rem;
}
</style>