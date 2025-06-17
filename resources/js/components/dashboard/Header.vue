<template>
  <va-card class="mb-4">
    <va-card-title class="text-primary text-2xl">
      {{ $t('dashboard.title') }} {{ user && user.name ? user.name : 'Не загрузилось имя' }}
    </va-card-title>
    <va-card-content>
      <p class="text-secondary">{{ $t('dashboard.welcome') }}</p>
      <div v-if="user && !user.email_verified_at" class="mt-2">
        <VaButton
          preset="secondary"
          class="ml-4"
          :loading="isSendingVerification"
          @click="sendVerificationEmail"
        >
          {{ $t('vuestic.profile.resend_verification') }}
        </VaButton>
        <span class="text-warning ml-2">{{ $t('vuestic.profile.email_not_verified') }}</span>
      </div>
    </va-card-content>
  </va-card>
</template>

<script>
import { defineComponent, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

export default defineComponent({
  name: 'Header',
  props: {
    user: {
      type: Object,
      default: null,
    },
  },
  setup(props, { emit }) { // Добавляем context для $emit
    const { t } = useI18n();
    const authStore = useAuthStore();
    const isSendingVerification = ref(false);
    const toast = useToast();

    const sendVerificationEmail = async () => {
      isSendingVerification.value = true;
      try {
        const response = await axios.post('/api/email/resend', {}, {
          headers: { 'Authorization': `Bearer ${authStore.token}` }
        });
        toast.init({
          message: t('vuestic.profile.verification_email_sent'),
          color: 'success',
          duration: 3000, // 3 секунды
        });
        await authStore.fetchUser();
        // Отправляем событие родителю
        emit('user-updated', authStore.user); // Используем emit вместо window.$emit
      } catch (error) {
        console.log('Error response:', error.response ? error.response.data : error.message);
        toast.init({
          message: error.response?.data?.message || error.message || t('vuestic.profile.verification_email_failed'),
          color: 'danger',
          duration: 5000, // 5 секунд
        });
      } finally {
        isSendingVerification.value = false;
      }
    };

    return {
      isSendingVerification,
      sendVerificationEmail,
    };
  },
});
</script>