<template>
  <template v-if="isAdmin">
    <div>
      <p class="va-h4 my-4">{{ $t('requisites.title') }}</p>

      <VaCard>
        <VaDataTable :items="paginatedRequisites" :columns="columns">
          <!-- СЛОТ ДЛЯ partner_type_name -->
          <template #cell(partner_type_name)="slotProps">
            {{ getPartnerTypeText[slotProps.rowData.partner_type_id] || slotProps.rowData.partner_type_id }}
          </template>

          <!-- СЛОТ ДЛЯ created_at -->
          <template #cell(created_at)="slotProps">
            {{ formatDate(slotProps.rowData.created_at) }}
          </template>

          <!-- СЛОТ ДЛЯ actions (кнопки) -->
          <template #cell(actions)="slotProps">
            <VaButton flat small @click="openModal(slotProps.rowData)">
              <va-icon name="visibility" />
            </VaButton>
          </template>
        </VaDataTable>
      </VaCard>

      <!-- Пагинация -->
      <VaPagination v-model="currentPage" :pages="totalPages" class="mt-4" @update:model-value="handlePageChange" />
    </div>
  </template>

  <template v-else>
    <VaAlert color="danger" class="mt-4">
      {{ $t('errors.no_access') }}
    </VaAlert>
  </template>

  <!-- МОДАЛЬНОЕ ОКНО ПРОСМОТРА РЕКВИЗИТА -->
  <VaModal v-model="showModal" :title="modalTitle" :hide-default-actions="true" size="large" :mobile-fullscreen="false">
    <div v-if="selectedRequisite" class="p-4">
      <!-- ЗАГОЛОВОК -->
      <div class="va-card-title mb-2 va-h2 mt-4">
        {{ $t(`partners.partner_types.${getPartnerType(selectedRequisite.partner_type_id)?.name || 'unknown'}`) }}
      </div>

      <div class="va-card-content space-y-2">
        <template v-for="({ key, value }, i) in visibleFields(selectedRequisite)" :key="key">
          <!-- пропускаем полностью is_active и is_verified -->
          <div v-if="key !== 'is_active' && key !== 'is_verified'" class="m-0">
            <p v-if="i === 0" class="va-h5 mb-3">
              <span>{{ value }}</span>
            </p>
            <p v-else>
              <strong>{{ $t(`requisites.${key}`) }}:</strong>
              <span class="ml-1">{{ value }}</span>
            </p>
          </div>
        </template>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-4">
        <VaButton @click="deleteRequisite(selectedRequisite.id)" color="danger" preset="plain">
          {{ $t('requisites.delete') }}
        </VaButton>
        <VaButton @click="showModal = false" color="secondary">
          {{ $t('modal.cancel') }}
        </VaButton>
        <VaButton @click="approveRequisite" color="primary">
          {{ $t('requisites.approve') }}
        </VaButton>
      </div>
    </template>
  </VaModal>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { usePartnersHelper } from '@/composables/partnersHelper';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();
const { partnerSettings } = usePartnersHelper();

axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`;

const isAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1) || props.user.effective_access_levels.includes(2));
});

const allRequisites = ref([]); // Все данные с бэка
const requisites = ref([]); // Обработанные (с partner_type_name)
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(15);

const showModal = ref(false);
const modalTitle = ref('');
const selectedRequisite = ref(null);

const partnerTypeOptions = computed(() => {
  if (!partnerSettings.value?.partner_types) {
    return [];
  }
  return partnerSettings.value.partner_types
    .filter(type => type.active)
    .map(type => ({
      value: type.id,
      text: t(`partners.partner_types.${type.name}`),
    }));
});

// Вычисляемое свойство для получения текстовых значений по ID
const getPartnerTypeText = computed(() => {
  const map = {};
  partnerTypeOptions.value.forEach(opt => {
    map[opt.value] = opt.text;
  });
  return map;
});

const columns = computed(() => [
  { key: 'id', label: t('requisites.id'), sortable: true },
  { key: 'full_name', label: t('requisites.full_name'), sortable: true },
  { key: 'partner_type_name', label: t('requisites.partner_type'), sortable: false },
  { key: 'created_at', label: t('requisites.created_at'), sortable: true },
  { key: 'actions', label: t('actions.title') },
]);

// Клиентская пагинация
const paginatedRequisites = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return requisites.value.slice(start, end);
});

// Обновление totalPages при изменении данных
watch(requisites, () => {
  totalPages.value = Math.ceil(requisites.value.length / perPage.value) || 1;
});

const fetchUnverifiedRequisites = async () => {
  try {
    console.log('User levels:', props.user.effective_access_levels); // ДЕБАГ: уровни юзера
    console.log('Is Admin:', isAdmin.value); // ДЕБАГ: isAdmin

    const response = await axios.get('/api/user/requisites-all');
    console.log('Full API Response:', response); // ДЕБАГ: полный response
    console.log('Response.data:', response.data); // ДЕБАГ: данные ответа

    // Чек на HTML (ошибка роута/proxy)
    if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
      console.error('API returned HTML instead of JSON — check route or Vite proxy for /api/*');
      toast.init({ message: t('errors.fetch_failed'), color: 'danger' });
      return;
    }

    allRequisites.value = response.data || [];
    requisites.value = allRequisites.value.map(req => {
      const type = partnerSettings.value?.partner_types?.find(t => t.id === req.partner_type_id);
      req.partner_type_name = type ? type.name : 'unknown';
      return req;
    });
    console.log('Processed requisites:', requisites.value); // ДЕБАГ: обработанные данные
  } catch (error) {
    console.error('Full API Error:', {
      message: error.message,
      status: error.response?.status,
      data: error.response?.data,
      config: error.config?.url // ДЕБАГ: полный error
    });
    requisites.value = [];
    toast.init({ message: t('errors.fetch_failed'), color: 'danger' });
  }
};

const handlePageChange = (page) => {
  currentPage.value = page;
};

const openModal = (requisite) => {
  selectedRequisite.value = { ...requisite };
  modalTitle.value = `${t('requisites.view')} #${requisite.id}`;
  showModal.value = true;
};

const approveRequisite = async () => {
  if (!selectedRequisite.value?.id) return;

  try {
    await axios.put(`/api/user/requisites/${selectedRequisite.value.id}/verify`);
    showModal.value = false;
    await fetchUnverifiedRequisites(); // Refetch всех данных
    toast.init({ message: t('requisites.approved'), color: 'success' });
  } catch (error) {
    console.error('Approve Error:', error.response?.data || error);
    toast.init({ message: error.response?.data?.message || t('errors.approve_failed'), color: 'danger' });
  }
};

const deleteRequisite = async (id) => {
  if (!confirm(t('requisites.confirm_delete'))) return;

  try {
    await axios.delete(`/api/user/requisites/${id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });

    if (showModal.value && selectedRequisite.value?.id === id) {
      showModal.value = false;
      selectedRequisite.value = null;
    }
    await fetchUnverifiedRequisites(); // Refetch всех данных
    toast.init({ message: t('success.deleted'), color: 'success' });
  } catch (error) {
    console.error('Delete Error:', error.response?.data || error);
    toast.init({ message: t('errors.delete_failed'), color: 'danger' });
  }
};

/**
 * Получить видимые поля реквизита для отображения
 */
const visibleFields = (req) =>
  Object.entries(req)
    .filter(
      ([k, v]) =>
        !['id', 'partner_type_id', 'is_verified', 'tax_check_required', 'partner_type_name', 'user_id', 'created_at', 'updated_at', 'deleted_at'].includes(k) &&
        v !== null &&
        v !== undefined &&
        v !== ''
    )
    .map(([k, v]) => ({ key: k, value: v }));

/**
 * Найти тип партнера по ID
 */
const getPartnerType = (id) => {
  return partnerSettings.value?.partner_types?.find(item => item.id === id) || null;
};

// Функция форматирования даты без библиотек
const formatDate = (dateString) => {
  if (!dateString) return '';

  try {
    const date = new Date(dateString);

    // Получаем компоненты даты
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Месяцы с 0
    const year = date.getFullYear();

    return `${hours}:${minutes} ${day}.${month}.${year}`;
  } catch (e) {
    return dateString; // если ошибка — вернём как есть
  }
};

onMounted(async () => {
  console.log('onMounted: User props:', props.user); // ДЕБАГ: полный user
  if (isAdmin.value) {
    // Ждём загрузки partnerSettings
    if (!partnerSettings.value) {
      console.log('Waiting for partnerSettings...'); // ДЕБАГ: ожидание
      const unwatch = watch(() => partnerSettings.value, (newVal) => {
        if (newVal) {
          console.log('partnerSettings loaded:', newVal); // ДЕБАГ: загружено
          unwatch();
          fetchUnverifiedRequisites();
        }
      });
    } else {
      console.log('partnerSettings already loaded'); // ДЕБАГ: уже есть
      await fetchUnverifiedRequisites();
    }
  } else {
    console.log('Not admin, skipping fetch'); // ДЕБАГ: не админ
  }
});
</script>