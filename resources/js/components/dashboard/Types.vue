<template>
  <div>
    <p class="va-h4 my-1">{{ $t('dashboard.types') }}</p>
    <div class="my-3">
      <div v-if="apiData">
        <div>
          <div v-if="apiData.cooperation_types && apiData.cooperation_types.length"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="type in apiData.cooperation_types" :key="'coop-' + type.id" class="mb-4">
              <VaCard class="p-4 max-w-md h-full">
                <div class="va-card-title mb-2 va-h6">
                  {{ $t('partners.cooperation_types.' + type.name + '.title') }}
                </div>
                <div class="va-card-content">
                  <p>{{ $t('partners.cooperation_types.' + type.name + '.' + type.description) }}</p>
                </div>
                <div class="va-card-actions mt-4 flex justify-between">

                  <div v-if="hasApplication(0, type.id) || hasApplication(1, type.id)" class="row">
                    <div class="col">
                      <VaButton color="secondary" class="w-40" @click="iAlert($t('partners.onvalidate_alert'))">
                        {{ $t('partners.onvalidate') }}
                      </VaButton>
                    </div>
                    <div class="col flex flex-col justify-center p-2">
                      <p class="text-xs text-gray-500 mt-1 text-center">{{ $t('partners.onvalidate_descr') }}</p>
                    </div>
                  </div>

                  <div v-else-if="hasApplication(2, type.id)" class="row">
                    <div class="col">
                      <VaButton :to="{ name: type.route }" color="success" class="w-40" @click="iAlert($t('partners.actived_alert'))">
                        {{ $t('partners.actived') }}
                      </VaButton>
                    </div>
                  </div>

                  <div v-else-if="hasApplication(3, type.id)" class="row">
                    <div class="col">
                      <VaButton color="warning" class="w-40" @click="iAlert($t('partners.rejected_alert'))">
                        {{ $t('partners.rejected') }}
                      </VaButton>
                    </div>
                  </div>

                  <div v-else class="row">
                    <div class="col">
                      <VaButton :disabled="!type.active" color="primary" class="w-40" @click="openDialog(type)">
                        {{ $t('partners.apply') }}
                      </VaButton>
                    </div>
                    <div v-if="!type.active" class="col flex flex-col justify-center p-2">
                      <p class="text-xs text-gray-500 mt-1 text-center">{{ $t('partners.ondevelopment') }}</p>
                    </div>
                  </div>

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

      <VaProgressBar v-if="submitting" indeterminate color="primary" class="mb-4" />

      <VaForm ref="formRef" class="p-4 space-y-4">

        <h3 class="va-h5 mb-2">{{ $t('partners.cooperation_types.' + selectedType?.name + '.title') }}</h3>
        <p class="mb-2">{{ $t('partners.cooperation_types.' + selectedType?.name + '.description') }}</p>

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
              <!-- Изменили на items-end -->
              <VaInput v-model="form.links[index]" :label="$t('form.link') + ' #' + (index + 1)" type="url"
                class="flex-grow" />
              <VaButton @click="removeLink(index)" color="danger" icon="close"
                class="ml-2 !w-8 !h-8 !min-w-8 !min-h-8 !p-0 flex-shrink-0" preset="plain" />
            </div>
            <VaButton @click="addLink" color="secondary" size="small">
              {{ $t('form.add_link') }}
            </VaButton>

          </div>

          <div>
            <VaSelect v-model="form.partner_type_id" :label="$t('business_form')" :options="filteredPartnerTypes"
              :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
          </div>

          <div>
            <VaInput v-model="form.experience" :label="$t('form.experience')" class="w-full" />
          </div>

          <div class="col-span-1 md:col-span-2 border-2 border-gray-200 p-6 rounded-md my-2">
            <VaSwitch v-model="isCompanyEnabled" :label="$t('partners.affiliation')"
              @update:modelValue="toggleCompanyInput" class="mb-2" />
            <VaInput v-model="form.company_name" :label="$t('form.company_name')" :disabled="!isCompanyEnabled"
              class="w-full transition-opacity duration-300 bg-white-50" :class="{ 'opacity-50': !isCompanyEnabled }" />
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
import { usePartnerApplications } from '@/composables/usePartnerApplications';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';

// Подключаем composable
const {
  // hasAnyApplications,
  // applicationsCount,
  // partnerApplications,
  // responsibleApplications,
  // hasActiveApplications,
  hasApplication,
  // getApplication,
  loadApplications,
  // hasApplicationsWithStatus
} = usePartnerApplications();

// console.log('hasAnyApplications:', hasAnyApplications.value);
// console.log('applicationsCount:', applicationsCount.value);
// console.log('partnerApplications:', partnerApplications.value);
// console.log('hasActiveApplications:', hasActiveApplications.value);

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
  city: '',
  links: [],
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

function iAlert(message) {
  toast.init({
    message,
    color: 'info',
  });
}

function toggleCompanyInput(value) {
  if (!value) {
    form.value.company_name = '';
  }
}

function addLink() {
  if (!form.value.links) {
    form.value.links = [];
  }
  form.value.links.push('');
}

function removeLink(index) {
  form.value.links.splice(index, 1);
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
  if (!isValid) return;

  submitting.value = true;
  const sendData = { ...form.value };

  // Преобразуем partner_type_id в число (если нужно)
  if (typeof sendData.partner_type_id === 'object') {
    sendData.partner_type_id = sendData.partner_type_id.value;
  }

  //console.log('Отправка данных:', sendData);

  try {
    const resp = await axios.post('/api/partner-applications', sendData, {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
      },
    });
    toast.init({ message: t('success.application_submitted'), color: 'success' });
    showDialog.value = false;

    // Перезагружаем данные
    await authStore.fetchUser();
    await loadApplications(); // ← Обновляем заявки
    await loadData();         // ← Обновляем типы сотрудничества
    resetForm();

  } catch (e) {
    console.error('Ошибка:', e.response?.data);
    toast.init({
      message: e.response?.data?.message || t('errors.submit_error'),
      color: 'danger',
    });
  } finally {
    submitting.value = false;
  }
}

async function loadData() {
  try {
    const response = await axios.get('/api/ps', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    apiData.value = response.data;
  } catch (err) {
    error.value = err.response ? err.response.data : err.message;
  }
}

onMounted(() => {
  loadData();
});

</script>