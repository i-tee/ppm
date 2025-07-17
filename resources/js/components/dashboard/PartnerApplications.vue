<template>
  <template v-if="isAdmin">
    <div>
      <p class="va-h4 my-4">{{ $t('partnerApplications.title') }}</p>

      <!-- Фильтры -->
      <div class="filters mb-4 flex gap-4">
        <VaSelect v-model="filters.status_id" :options="statusOptions" :label="$t('partnerApplications.status')" />
        <VaSelect v-model="filters.cooperation_type_id" :options="cooperationTypeOptions"
          :label="$t('partnerApplications.cooperation_type')" />
        <VaSelect v-model="filters.partner_type_id" :options="partnerTypeOptions"
          :label="$t('partnerApplications.partner_type')" />
      </div>

      <VaCard>
        <!-- Таблица -->
        <VaDataTable :items="applications" :columns="columns" :sort-by.sync="sortBy" :sort-desc.sync="sortDesc"
          :per-page="perPage" :current-page="currentPage" @sorted="fetchApplications"
          @page-selected="fetchApplications">
          <template #cell(actions)="slotProps">
            <VaButton flat small @click="editApplication(slotProps.item)">{{ $t('actions.edit') }}</VaButton>
            <VaButton flat small color="danger" @click="deleteApplication(slotProps.item.id)">{{ $t('actions.delete') }}
            </VaButton>
          </template>
        </VaDataTable>
      </VaCard>

      <!-- Пагинация -->
      <VaPagination v-model="currentPage" :pages="totalPages" class="mt-4" />

      <!-- Кнопка для создания -->
      <VaButton @click="openCreateModal" class="mt-4">{{ $t('partnerApplications.create') }}</VaButton>

      <!-- Модальное окно -->
      <VaModal v-model="showModal" :title="modalTitle" :hide-default-actions="true">
        <VaForm ref="formRef" class="p-4 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <VaInput v-model="form.full_name" :label="$t('form.full_name')"
                :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
            </div>
            <div>
              <VaInput v-model="form.email" :label="$t('form.email')" type="email"
                :rules="[(v) => !v || /.+@.+\..+/.test(v) || $t('validation.email')]" class="w-full" />
            </div>
            <div>
              <VaInput v-model="form.phone" type="tel" :label="$t('form.phone')"
                :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
            </div>
            <div>
              <VaInput v-model="form.city" :label="$t('form.city')" class="w-full" />
            </div>
            <div class="col-span-1 md:col-span-2 border-2 border-gray-200 p-6 rounded-lg my-2">
              <p class="mb-4">{{ $t('form.links_prehead') }}</p>
              <div v-for="(link, index) in form.links" :key="index" class="mb-2 flex items-end">
                <VaInput v-model="form.links[index]" :label="$t('form.link') + ' #' + (index + 1)" type="url"
                  class="flex-grow" />
                <VaButton @click="removeLink(index)" color="danger" icon="close"
                  class="ml-2 !w-8 !h-8 !min-w-8 !min-h-8 !p-0 flex-shrink-0" preset="plain" />
              </div>
              <VaButton @click="addLink" color="secondary" size="small">{{ $t('form.add_link') }}</VaButton>
            </div>
            <div>
              <VaSelect v-model="form.partner_type_id" :label="$t('business_form')" :options="partnerTypeOptions"
                :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
            </div>
            <div>
              <VaInput v-model="form.experience" :label="$t('form.experience')" class="w-full" />
            </div>
            <div class="col-span-1 md:col-span-2 border-2 border-gray-200 p-6 rounded-md my-2">
              <VaSwitch v-model="isCompanyEnabled" :label="$t('partners.affiliation')"
                @update:modelValue="toggleCompanyInput" class="mb-2" />
              <VaInput v-model="form.company_name" :label="$t('form.company_name')" :disabled="!isCompanyEnabled"
                class="w-full transition-opacity duration-300 bg-white-50"
                :class="{ 'opacity-50': !isCompanyEnabled }" />
            </div>
            <div class="col-span-1 md:col-span-2">
              <VaTextarea v-model="form.comment" :label="$t('form.comment')" rows="3" class="w-full" />
            </div>
            <div>
              <VaSelect v-model="form.cooperation_type_id" :options="cooperationTypeOptions"
                :label="$t('partnerApplications.cooperation_type')" :rules="[(v) => !!v || $t('validation.required')]"
                class="w-full" />
            </div>
            <div>
              <VaSelect v-model="form.status_id" :options="statusOptions" :label="$t('partnerApplications.status')"
                :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
            </div>
          </div>
        </VaForm>
        <template #footer>
          <div class="flex justify-end space-x-4">
            <VaButton @click="showModal = false" color="secondary">{{ $t('modal.cancel') }}</VaButton>
            <VaButton @click="saveApplication" color="primary">{{ $t('modal.submit') }}</VaButton>
          </div>
        </template>
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
  status_id: null,
  company_name: '',
  experience: '',
  comment: '',
  city: '',
  links: [],
});
const isCompanyEnabled = ref(false);

const statusOptions = ref([]);
const cooperationTypeOptions = ref([]);
const partnerTypeOptions = ref([]);
const apiData = ref(null);

const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'full_name', label: t('partnerApplications.full_name'), sortable: true },
  { key: 'phone', label: t('partnerApplications.phone'), sortable: true },
  { key: 'email', label: t('partnerApplications.email'), sortable: true },
  { key: 'status_name', label: t('partnerApplications.status'), sortable: true },
  { key: 'comment', label: t('partnerApplications.comment'), sortable: true },
  { key: 'created_at', label: t('partnerApplications.created_at'), sortable: true },
  { key: 'updated_at', label: t('partnerApplications.updated_at'), sortable: true },
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
  form.value = {
    id: null,
    full_name: '',
    phone: '',
    email: '',
    cooperation_type_id: null,
    partner_type_id: null,
    status_id: statusOptions.value.length > 0 ? statusOptions.value[0] : null,
    company_name: '',
    experience: '',
    comment: '',
    city: '',
    links: []
  };
  isCompanyEnabled.value = false;
  modalTitle.value = t('partnerApplications.create');
  showModal.value = true;
};

const editApplication = (application) => {
  form.value = {
    ...application,
    links: application.links ? [...application.links] : [],
    cooperation_type_id: application.cooperation_type_id,
    partner_type_id: application.partner_type_id,
    status_id: application.status_id
  };
  isCompanyEnabled.value = !!application.company_name;
  modalTitle.value = t('partnerApplications.edit');
  showModal.value = true;
};

const saveApplication = async () => {
  try {
    // Преобразуем объекты в целые числа перед отправкой и проверяем обязательные поля
    const sendData = {
      ...form.value,
      cooperation_type_id: form.value.cooperation_type_id?.value || null,
      partner_type_id: form.value.partner_type_id?.value || null,
      status_id: form.value.status_id?.value || (statusOptions.value.length > 0 ? statusOptions.value[0].value : null)
    };
    console.log('Отправляемые данные:', sendData); // Логируем перед отправкой

    // Проверка на null для обязательных полей
    if (!sendData.full_name || !sendData.phone || !sendData.email || !sendData.cooperation_type_id || !sendData.partner_type_id || !sendData.status_id) {
      toast.init({ message: t('validation.errors_found'), color: 'danger' });
      return;
    }

    if (form.value.id) {
      await axios.put(`/api/partner-applications/${form.value.id}`, sendData);
    } else {
      const response = await axios.post('/api/partner-applications', sendData);
      console.log('Ответ сервера:', response.data); // Логируем успешный ответ
    }
    showModal.value = false;
    fetchApplications();
    toast.init({ message: t('success.saved'), color: 'success' });
  } catch (error) {
    console.error('Ошибка:', error.response ? error.response.data : error.message); // Логируем детали ошибки
    toast.init({ message: error.response?.data?.message || t('errors.save_failed'), color: 'danger' });
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

function addLink() {
  if (!form.value.links) form.value.links = [];
  form.value.links.push('');
}

function removeLink(index) {
  form.value.links.splice(index, 1);
}

function toggleCompanyInput(value) {
  if (!value) form.value.company_name = '';
}

onMounted(async () => {
  if (isAdmin.value) {
    try {
      const response = await axios.get('/api/ps', {
        headers: { Authorization: `Bearer ${authStore.token}` },
      });
      apiData.value = response.data;

      statusOptions.value = [
        { value: 0, text: t('status.new') },
        { value: 1, text: t('status.in_progress') },
        { value: 2, text: t('status.accepted') },
        { value: 3, text: t('status.rejected') },
      ];

      cooperationTypeOptions.value = apiData.value.cooperation_types.map(type => ({
        value: type.id,
        text: t(`partners.cooperation_types.${type.name}.title`)
      }));

      partnerTypeOptions.value = apiData.value.partner_types.map(type => ({
        value: type.id,
        text: t(`partners.partner_types.${type.name}`)
      }));

      // Инициализируем fetch после загрузки опций
      await fetchApplications();
    } catch (err) {
      toast.init({ message: t('errors.fetch_failed'), color: 'danger' });
    }
  } else {
    toast.init({ message: t('errors.no_access'), color: 'danger' });
  }
});
</script>