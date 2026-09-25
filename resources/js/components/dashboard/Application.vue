<template>
  <div>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.application') }}</p>
      <p class="my-2">{{ $t('dashboard.application_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="!apiData" class="grid grid-cols-2 gap-4">
      <p class="text-gray-400">{{ $t('loading_data') }}</p>
      <br>
      <VaSkeleton tag="h1" variant="text" class="va-h1" />
      <VaSkeleton tag="h1" variant="text" class="va-h1" />
    </div>

    <!-- Заявка одобрена: анкету заполнять больше не нужно -->
    <div v-else-if="isApproved" class="p-4 my-4 rounded-lg bg-gray-200">
      <p class="my-2">{{ $t('status.accepted_user') }}</p>
      <VaButton class="mt-2" :to="{ name: 'Promocodes' }">{{ $t('dashboard.promocodes') }}</VaButton>
    </div>

    <!-- Заявка на рассмотрении -->
    <div v-else-if="isPending" class="p-4 my-4 rounded-lg bg-gray-200">
      <p class="my-2">{{ $t('welcomes.apps.received') }}</p>
      <p class="my-2">{{ $t('welcomes.apps.responseTime') }}</p>
      <p class="my-2">{{ $t('welcomes.apps.notification') }}</p>
    </div>

    <!-- Заявка отклонена -->
    <div v-else-if="isRejected" class="p-4 my-4 rounded-lg bg-gray-200">
      <p class="my-2">{{ $t('welcomes.apps.rejected') }}</p>
      <VaDivider class="my-2" />
      <p class="my-2">{{ $t('welcomes.apps.rejected_contact') }}</p>
    </div>

    <!-- Заявки ещё нет: форма -->
    <VaForm v-else ref="formRef" class="my-3 space-y-4">

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
          <VaButton @click="addLink" color="secondary" size="small">
            {{ $t('form.add_link') }}
          </VaButton>
        </div>

        <div>
          <VaSelect v-model="form.partner_type_id" :label="$t('business_form')" :options="filteredPartnerTypes"
            :rules="[(v) => !!v || $t('validation.required')]" class="w-full" />
        </div>

        <div>
          <VaInput v-model="form.experience" :label="$t('partnerApplications.specialty')" class="w-full" />
        </div>

        <div class="col-span-1 md:col-span-2">
          <VaTextarea v-model="form.comment" :label="$t('form.comment')" rows="3" class="w-full" />
        </div>

        <div class="col-span-1 md:col-span-2 p-4 text-sm bg-gray-100 rounded-md text-gray-600">
          <p>{{ $t('disclaimer.agent') }}</p>
        </div>

      </div>
      <input type="hidden" v-model="form.cooperation_type_id" />
      <input type="hidden" v-model="form.status_id" />

      <div class="flex justify-end">
        <VaButton :loading="submitting" @click="validateAndSubmit" color="primary">
          {{ $t('modal.submit') }}
        </VaButton>
      </div>

    </VaForm>

  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useSettingsStore } from '@/stores/settings';
import { usePartnerApplications } from '@/composables/usePartnerApplications';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';

// Тип сотрудничества "Агент" - единственный доступный на этом этапе
// (см. docs/prompts/stage-1.4-menu.md, п.1). Форма и её поля не меняем -
// анкету по составу полей переделает этап 1.7.
const AGENT_COOPERATION_TYPE_ID = 2;

const { hasApplication } = usePartnerApplications();

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();
const settingsStore = useSettingsStore();

const apiData = computed(() => settingsStore.data);
const submitting = ref(false);
const formRef = ref(null);

const isApproved = computed(() => hasApplication(2, AGENT_COOPERATION_TYPE_ID));
const isPending = computed(() => hasApplication(0, AGENT_COOPERATION_TYPE_ID) || hasApplication(1, AGENT_COOPERATION_TYPE_ID));
const isRejected = computed(() => hasApplication(3, AGENT_COOPERATION_TYPE_ID) || hasApplication(9, AGENT_COOPERATION_TYPE_ID));

const form = ref({
  full_name: authStore.user.name || '',
  phone: '',
  email: authStore.user.email || '',
  cooperation_type_id: AGENT_COOPERATION_TYPE_ID,
  partner_type_id: null,
  status_id: 0,
  company_name: '',
  experience: '',
  comment: '',
  city: '',
  links: [],
});

const filteredPartnerTypes = computed(() => {
  if (!apiData.value?.partner_types) return [];
  return apiData.value.partner_types
    .filter((type) => type.active)
    .map((type) => ({
      value: type.id,
      text: t(`partners.partner_types.${type.name}`),
    }));
});

function addLink() {
  if (!form.value.links) {
    form.value.links = [];
  }
  form.value.links.push('');
}

function removeLink(index) {
  form.value.links.splice(index, 1);
}

async function validateAndSubmit() {
  const isValid = await formRef.value.validate();
  if (!isValid) return;

  submitting.value = true;
  const sendData = { ...form.value };

  if (typeof sendData.partner_type_id === 'object') {
    sendData.partner_type_id = sendData.partner_type_id.value;
  }

  try {
    await axios.post('/api/partner-applications', sendData, {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
      },
    });
    toast.init({ message: t('success.application_submitted'), color: 'success' });

    // Заявка отправлена - подтягиваем свежий профиль, статус на этой же
    // странице переключится сам (реактивные isPending/isApproved).
    await authStore.fetchUser();
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

onMounted(() => {
  settingsStore.load();
});
</script>
