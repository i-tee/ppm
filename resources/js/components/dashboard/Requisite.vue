<template>
  <div>
    <p class="va-h4 my-1">{{ $t('dashboard.requisites') }}</p>
    <div class="my-3">
      <div v-if="requisites && requisites.length">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="req in requisites" :key="req.id" class="mb-4">
            <VaCard class="p-4 max-w-md h-full">
              <div class="va-card-title mb-2 va-h6">
                {{ $t(`partners.partner_types.${req.partner_type_name}`) }} #{{ req.id }}
              </div>
              <div class="va-card-content">
                <p v-for="(value, field) in req" :key="field" v-if="value && field !== 'id' && field !== 'partner_type_id' && field !== 'partner_type_name'">
                  <strong>{{ $t(`requisites.${field}`) }}:</strong> {{ value }}
                </p>
              </div>
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
      <VaButton color="primary" class="mt-4" @click="openDialog" :disabled="!partnerSettings || filteredPartnerTypes.length === 0">
        {{ $t('requisites.add') }}
      </VaButton>
    </div>

    <VaModal v-model="showDialog" :loading="submitting" :hide-default-actions="true">
      <VaProgressBar v-if="submitting" indeterminate color="primary" class="mb-4" />

      <VaForm ref="formRef" class="p-4 space-y-4">
        <h3 class="va-h5 mb-2">{{ $t('requisites.create_title') }}</h3>

        <VaSelect v-model="form.partner_type_id" :label="$t('requisites.partner_type')" :options="filteredPartnerTypes"
          :rules="[(v) => !!v || $t('validation.required')]" class="w-full" @update:modelValue="onPartnerTypeChange" />

        <template v-if="form.partner_type_id && selectedRequisiteFields">
          <VaInput v-for="(config, field) in selectedRequisiteFields" :key="field" v-model="form[field]"
            :label="$t(config.label)" :rules="config.required ? [(v) => !!v || $t('validation.required')] : []"
            :type="config.type === 'number' ? 'number' : 'text'" class="w-full mt-2" />
        </template>
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

const form = ref({
  partner_type_id: null,
  full_name: '',
  organization_name: '',
  inn: '',
  ogrn: '',
  kpp: '',
  legal_address: '',
  postal_address: '',
  bank_name: '',
  bik: '',
  correspondent_account: '',
  payment_account: '',
  card_number: '',
  phone_for_sbp: '',
  additional_info: '',
});

const filteredPartnerTypes = computed(() => {
  if (!partnerSettings.value?.partner_types) {
    console.log('partnerSettings.value.partner_types is undefined');
    return [];
  }
  console.log('partnerSettings.value.partner_types:', partnerSettings.value.partner_types);
  return partnerSettings.value.partner_types
    .filter(type => type.active)
    .map(type => ({
      value: type.id,
      text: type.name,
    }));
});

const selectedRequisiteFields = computed(() => {
  if (!form.value.partner_type_id || !partnerSettings.value?.partner_types) return {};
  const type = partnerSettings.value.partner_types.find(t => t.id === form.value.partner_type_id);
  return type?.requisite_fields || {};
});

const defaultType = computed(() => filteredPartnerTypes.value[0]?.value || null);

function openDialog() {
  form.value.partner_type_id = defaultType.value;
  showDialog.value = true;
}

function onPartnerTypeChange() {
  // Сбрасываем только те поля, которые не входят в новый набор
  const newFields = selectedRequisiteFields.value;
  Object.keys(form.value).forEach(key => {
    if (key !== 'partner_type_id' && !newFields[key]) {
      form.value[key] = '';
    }
  });
}

function resetForm() {
  form.value.partner_type_id = null;
  Object.keys(form.value).forEach(key => {
    if (key !== 'partner_type_id') form.value[key] = '';
  });
  showDialog.value = false;
}

async function validateAndSubmit() {
  const isValid = await formRef.value.validate();
  if (!isValid) {
    toast.init({ message: t('validation.form_invalid'), color: 'warning' });
    return;
  }

  submitting.value = true;

  try {
    const response = await axios.post('/api/user/requisites', form.value, {
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