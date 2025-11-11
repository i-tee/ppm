<template>

  <div>

    <div v-if="user && !user.email_verified_at" class="mb-6">

      <div class="row">
        <VaAlert class="w-full" color="warning" icon="warning">
          <div>
            <span>
              {{ $t('vuestic.profile.email_not_verified') }}!
            </span>
            <span> </span>
            <span>
              {{ $t('vuestic.profile.resend_verification_description') }}
            </span>
          </div>
        </VaAlert>
      </div>

      <div class="row mt-2">
        <VaButton icon-right="mail" :loading="isSendingVerification" @click="sendVerificationEmail" size="small">
        </VaButton>
        <VaDivider vertical />
        <a href="#" @click.prevent="sendVerificationEmail"
          class="underline text-primary cursor-pointer whitespace-nowrap">
          {{ $t('vuestic.profile.resend_verification') }}
        </a>
      </div>

    </div>
  </div>

  <div v-if="hasApprovedApplications">
    <h1 class="va-h4 my-4">Заявка есть, включаем иную логику</h1>
  </div>

  <div v-else>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('welcomes.short_head') }}</p>
      <p class="my-2">{{ $t('welcomes.short') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div v-if="user && user.email_verified_at" class="mb-6">
      <p>{{ $t('welcomes.choose_text') }}</p>
      <br>
      <VaButton :to="{ name: 'Types' }">{{ $t('welcomes.choose') }}</VaButton>
    </div>

  </div>

</template>

<script setup>
import { usePartnerApplications } from '@/composables/usePartnerApplications';
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

// Проверяем, есть ли заявки со статусом 2
const { hasApplicationsWithStatus } = usePartnerApplications();
const hasApprovedApplications = hasApplicationsWithStatus(2);

// Состояние загрузки
const isSendingVerification = ref(false);

// Метод отправки письма
const sendVerificationEmail = async () => {
  isSendingVerification.value = true;
  try {
    const response = await axios.post(
      '/api/email/resend',
      {},
      {
        headers: { Authorization: `Bearer ${authStore.token}` },
      }
    );

    toast.init({
      message: t('vuestic.profile.verification_email_sent'),
      color: 'success',
      duration: 3000,
    });

    // Обновляем данные пользователя
    await authStore.fetchUser();
  } catch (error) {
    console.error('Ошибка при отправке письма:', error.response?.data || error.message);

    toast.init({
      message:
        error.response?.data?.message ||
        error.message ||
        t('vuestic.profile.verification_email_failed'),
      color: 'danger',
      duration: 5000,
    });
  } finally {
    isSendingVerification.value = false;
  }
};
</script>