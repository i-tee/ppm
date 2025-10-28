<template>

  <div>

    <p class="va-h4 my-1">{{ $t('dashboard.requisites') }}</p>

    <div class="my-3">
      <div v-if="requisites && requisites.length">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
          <div v-for="req in requisites" :key="req.id" class="mb-4">

            <VaCard class="p-4 max-w-lg h-full">
              <!-- ЗАГОЛОВОК -->
              <div class="va-card-title mb-2 va-h6">
                {{ $t(`partners.partner_types.${getPartnerType(req.partner_type_id)?.name || 'unknown'}`) }}
                <span class="text-secondary">#{{ req.id }}</span>
              </div>

              <!-- СОДЕРЖИМОЕ -->
              <div class="va-card-content space-y-2">
                <p v-for="{ key, value } in visibleFields(req)" :key="key" class="m-0">
                  <strong>{{ $t(`requisites.${key}`) }}:</strong>
                  <span class="ml-1">{{ value }}</span>
                </p>
              </div>

              <!-- КНОПКИ -->
              <div class="va-card-actions mt-4 flex justify-end">
                <VaButton color="danger" @click="deleteRequisite(req.id)">
                  {{ $t('requisites.delete') }}
                </VaButton>
              </div>
            </VaCard>

          </div>
        </div>
      </div>
      <div v-else-if="requisites">
        <VaAlert color="info">{{ $t('requisites.no_requisites') }}</VaAlert>
      </div>
      <div v-else>
        <VaAlert color="warning">{{ $t('loading_data') }}</VaAlert>
      </div>
      <VaButton color="primary" class="mt-4" @click="openDialog" :disabled="!isDataLoaded">
        {{ $t('requisites.add') }}
      </VaButton>
    </div>

    <VaModal v-model="showDialog" :loading="submitting" :hide-default-actions="true">
      <VaProgressBar v-if="submitting" indeterminate color="primary" class="mb-4" />

      <VaForm ref="formRef" class="p-4 space-y-4">
        <h3 class="va-h5 mb-2">{{ $t('requisites.create_title') }}</h3>

        <VaSelect v-model="form.partner_type_id" :label="$t('requisites.partner_type')" :options="filteredPartnerTypes"
          :rules="[(v) => !!v || $t('validation.required')]" value-by="value" text-by="text" class="w-full" />

        <VaDivider />

        <div v-if="requisiteFieldsForm">
          <VaInput v-for="(field, key) in requisiteFieldsForm" :key="key" v-model="form[key]"
            :type="field.type === 'number' ? 'number' : 'text'" :label="$t(field.label)"
            :rules="[(v) => !field.required || !!v || $t('validation.required')]" class="w-full mb-2" />
        </div>
      </VaForm>

      <template #footer>
        <div class="flex justify-end space-x-4">
          <VaButton @click="resetForm" color="secondary">{{ $t('modal.cancel') }}</VaButton>
          <VaButton @click="validateAndSubmit" color="primary">{{ $t('modal.submit') }}</VaButton>
        </div>
      </template>
    </VaModal>
  </div>
  
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from 'vuestic-ui';
import { useAuthStore } from '@/stores/auth';
import { usePartnersHelper } from '@/composables/partnersHelper';

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();
const { partnerSettings } = usePartnersHelper();

const requisites = ref(null);
const showDialog = ref(false);
const submitting = ref(false);
const formRef = ref(null);
const isDataLoaded = ref(false);
const requisiteFieldsForm = ref(null);

const form = ref({
  partner_type_id: null,
});

const filteredPartnerTypes = computed(() => {
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

const visibleFields = (req) =>
  Object.entries(req)
    .filter(
      ([k, v]) =>
        !['id', 'partner_type_id','is_verified','tax_check_required', 'partner_type_name', 'user_id', 'created_at', 'updated_at'].includes(k) &&
        v !== null &&
        v !== undefined &&
        v !== ''
    )
    .map(([k, v]) => ({ key: k, value: v }));

const getPartnerType = (id) => {
  return partnerSettings.value?.partner_types?.find(item => item.id === id) || null;
};

watch(partnerSettings, (newVal) => {
  if (newVal?.partner_types) {
    console.log(newVal?.partner_types);
    isDataLoaded.value = true;
  }
}, { immediate: true });

watch(
  () => form.value.partner_type_id,
  (newValue) => {
    const selectedType = partnerSettings.value?.partner_types.find(item => item.id === newValue);
    requisiteFieldsForm.value = selectedType?.requisite_fields || null;
  }
);

function openDialog() {
  form.value.partner_type_id = filteredPartnerTypes.value[0]?.value || null;
  showDialog.value = true;
}

function resetForm() {
  form.value = { partner_type_id: null };
  showDialog.value = false;
}

async function validateAndSubmit() {
  const isValid = await formRef.value.validate();

  // Выводим все данные формы для отладки
  console.log('Данные формы:', form.value);

  if (!isValid) {
    toast.init({ message: t('validation.form_invalid'), color: 'warning' });
    return;
  }

  // Преобразуем данные для отправки
  const payload = {
    ...form.value,
    inn: form.value.inn ? String(form.value.inn) : null, // Гарантируем, что inn — строка
  };

  submitting.value = true;

  try {
    const response = await axios.post('/api/user/requisites', payload, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    toast.init({ message: t('success.requisite_created'), color: 'success' });
    showDialog.value = false;
    await loadRequisites();
    resetForm();
  } catch (e) {
    toast.init({ message: e.response?.data?.message || t('errors.submit_error'), color: 'danger' });
  } finally {
    submitting.value = false;
  }
}

async function loadRequisites() {
  try {
    const response = await axios.get('/api/user/requisites', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    requisites.value = response.data.map(req => {
      const type = partnerSettings.value?.partner_types.find(t => t.id === req.partner_type_id);
      req.partner_type_name = type ? type.name : 'unknown';
      return req;
    });
  } catch (err) {
    toast.init({ message: t('errors.load_error'), color: 'danger' });
  }
}

async function deleteRequisite(id) {
  if (!confirm(t('requisites.confirm_delete'))) return;

  try {
    await axios.delete(`/api/user/requisites/${id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    toast.init({ message: t('success.requisite_deleted'), color: 'success' });
    await loadRequisites();
  } catch (e) {
    toast.init({ message: t('errors.delete_error'), color: 'danger' });
  }
}

onMounted(() => {
  loadRequisites();
});
</script>