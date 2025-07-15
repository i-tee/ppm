<template>
  <div>
    <p class="va-h4 my-4">{{ $t('partners.cooperation_types.cooperation_types') }}</p>

    <div>
      <div v-if="apiData">
        <div>
          <div v-if="apiData.cooperation_types && apiData.cooperation_types.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="type in apiData.cooperation_types" :key="'coop-' + type.id" class="mb-4">
              <VaCard class="p-4 max-w-md h-full">
                <div class="va-card-title mb-2 va-h6">
                  {{ $t('partners.cooperation_types.' + type.name + '.title') }}
                </div>
                <div class="va-card-content">
                  <p>{{ $t('partners.cooperation_types.' + type.name + '.' + type.description) }}</p>
                </div>
                <div class="va-card-actions mt-4 flex justify-between">
                  <VaButton :disabled="!type.active" color="primary" class="w-40" @click="openDialog(type)">
                    {{ $t('partners.apply') }}
                  </VaButton>
                </div>
              </VaCard>
            </div>
          </div>
          <div v-else>
            <VaAlert color="info">{{ $t('dashboard.no_cooperation_types') }}</VaAlert>
          </div>
        </div>
      </div>
      <div v-else>
        <VaAlert color="warning">{{ $t('loading_data') }}</VaAlert>
      </div>
    </div>

    <VaModal v-model="showDialog" :loading="submitting" :hide-default-actions="true">

      <VaForm ref="formRef" class="p-4 space-y-4">
        <h3 class="va-h5 mb-2">{{ $t('partners.cooperation_types.' + selectedType?.name + '.title') }}</h3>
        <p class="mb-2">{{ $t('partners.cooperation_types.' + selectedType?.name + '.description') }}</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <VaInput v-model="form.full_name" :label="$t('form.full_name')" :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
          </div>
          <div>
            <VaInput v-model="form.phone" type="tel" :label="$t('form.phone')" :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
          </div>
          <div>
            <VaInput v-model="form.email" :label="$t('form.email')" type="email" :rules="[(v) => !v || /.+@.+\..+/.test(v) || $t('validation.email')]" class="w-full" />
          </div>
          <div class="col-span-1 md:col-span-2 bg-gray-50 p-2 rounded-lg">
            <VaSwitch v-model="isCompanyEnabled" :label="$t('partners.affiliation')" @update:modelValue="toggleCompanyInput" class="mb-2" />
            <VaInput v-model="form.company_name" :label="$t('form.company_name')" :disabled="!isCompanyEnabled" class="w-full transition-opacity duration-300" :class="{ 'opacity-50': !isCompanyEnabled }" />
          </div>
          <div>
            <VaInput v-model="form.experience" :label="$t('form.experience')" class="w-full" />
          </div>
          <div>
            <VaSelect v-model="form.partner_type_id" :label="$t('business_form')" :options="filteredPartnerTypes" :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
          </div>
          <div class="col-span-1 md:col-span-2">
            <VaTextarea v-model="form.comment" :label="$t('form.comment')" rows="3" class="w-full" />
          </div>
        </div>
        <input type="hidden" v-model="form.cooperation_type_id" />
        <input type="hidden" v-model="form.status_id" />
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
import { useAuthStore } from '@/stores/auth';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

const apiData = ref(null);
const error = ref(null);
const showDialog = ref(false);
const selectedType = ref(null);
const submitting = ref(false);
const formRef = ref(null);

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const form = ref({
  full_name: authStore.user.name || '',
  phone: '',
  email: authStore.user.email || '',
  cooperation_type_id: null,
  partner_type_id: null,
  status_id: 0,
  company_name: '',
  experience: '',
  comment: '',
});

const isCompanyEnabled = ref(false);

const filteredPartnerTypes = computed(() => {
  if (!apiData.value?.partner_types) return [];
  const options = apiData.value.partner_types
    .filter((type) => type.active)
    .map((type) => ({
      value: type.id,
      text: t(`partners.partner_types.${type.name}`),
    }));
  console.log('Filtered Partner Types:', options);
  return options;
});

function openDialog(type) {
  if (!apiData.value || !apiData.value.partner_types) {
    toast.init({
      message: 'Данные ещё загружаются, подождите...',
      color: 'warning',
    });
    return;
  }
  selectedType.value = type;
  form.value.cooperation_type_id = type.id;
  form.value.partner_type_id = null;
  showDialog.value = true;
}

function toggleCompanyInput(value) {
  if (!value) {
    form.value.company_name = '';
  }
}

function resetForm() {
  form.value = {
    full_name: authStore.user.name || '',
    phone: '',
    email: authStore.user.email || '',
    cooperation_type_id: null,
    partner_type_id: null,
    status_id: 0,
    company_name: '',
    experience: '',
    comment: '',
  };
  isCompanyEnabled.value = false;
  showDialog.value = false;
}

async function validateAndSubmit() {
  const isValid = await formRef.value.validate();
  if (!isValid) {
    toast.init({
      message: t('validation.errors_found'),
      color: 'warning',
    });
    return;
  }

  submitting.value = true;
  try {
    const resp = await axios.post('/api/partner-applications', form.value, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    toast.init({
      message: t('success.application_submitted'),
      color: 'success',
    });
    showDialog.value = false;
    resetForm();
  } catch (e) {
    console.error(e);
    toast.init({
      message: t('errors.submit_error'),
      color: 'danger',
    });
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  try {
    const response = await axios.get('/api/ps', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    apiData.value = response.data;
    console.log('API Data Loaded:', apiData.value);
  } catch (err) {
    error.value = err.response ? err.response.data : err.message;
    console.error('Error loading API data:', error.value);
  }
});
</script>