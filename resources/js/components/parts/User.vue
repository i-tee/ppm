<template>
  <div class="flex items-center space-x-4">
    <RouterLink :to="{ name: 'Account' }" class="flex items-center space-x-2 cursor-pointer">
      <span class="text-end">
        <span v-if="user && !user.email_verified_at" class="text-warning-500 cursor-help"
          :title="$t('vuestic.profile.email_not_verified')">
          ⚠️
        </span>
        <span class="hidden sm:block">
          {{ user?.name || user.mail }}
        </span>
      </span>
      <span>
        <VaAvatar class="w-10 h-10" :src="user.avatar_url" />
      </span>
    </RouterLink>
  </div>
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