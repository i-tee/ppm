<!-- resources/js/components/dashboard/UserProfile.vue -->
<template>

  <div v-if="user && !user.email_verified_at">

    <VaAlert color="warning" icon="warning" class="m-6">
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

    <div class="m-6 mt-0 items-center space-x-2 w-auto inline-flex">
      <VaButton icon-right="mail" :loading="isSendingVerification" @click="sendVerificationEmail" size="small"></VaButton>
      <a
      href="#"
      @click.prevent="sendVerificationEmail"
      class="underline text-primary cursor-pointer whitespace-nowrap"
      >
      {{ $t('vuestic.profile.resend_verification') }}
      </a>
    </div>

  </div>

  <div class="min-h-screen bg-gray-100 p-6">
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div>
        <VaInput v-model="form.name" :label="t('vuestic.profile.name')" class="font-inter" :error="!!errors.name"
          :error-message="errors.name" :disabled="!authStore.user?.email_verified_at" />
      </div>
      <div>
        <VaInput v-model="form.email" :label="t('vuestic.profile.email')" type="email" class="font-inter"
          :error="!!errors.email" :error-message="errors.email" :disabled="true" />
      </div>
      <div class="flex space-x-4">
        <VaButton type="submit" preset="primary"
          class="flex-1 bg-primary text-white hover:bg-secondary px-4 py-2 rounded transition font-inter"
          :disabled="authStore.loading || !authStore.user?.email_verified_at">
          {{ authStore.loading ? t('vuestic.profile.saving') : t('vuestic.profile.save') }}
        </VaButton>
        <VaButton preset="secondary" class="flex-1 px-4 py-2 rounded font-inter" @click="handleLogout"
          :disabled="authStore.loading">
          {{ t('vuestic.profile.logout') }}
        </VaButton>
      </div>
    </form>

    <!-- Кнопка для открытия попапа -->
    <div class="mt-6">
      <VaButton preset="primary"
        class="bg-primary text-white hover:bg-secondary px-4 py-2 rounded transition font-inter"
        @click="showPasswordModal = true" :disabled="!authStore.user?.email_verified_at">
        {{ t('vuestic.profile.change_password') }}
      </VaButton>
    </div>

    <!-- Попап для изменения пароля -->
    <VaModal v-model="showPasswordModal" :title="t('vuestic.profile.change_password')" size="small" no-outside-dismiss
      no-esc-dismiss :hide-default-actions="true">
      <template #default>
        <form @submit.prevent="changePassword" class="space-y-4">
          <VaInput v-model="passwordForm.newPassword" :label="t('vuestic.profile.new_password')" type="password"
            class="font-inter" :error="!!passwordErrors.newPassword" :error-message="passwordErrors.newPassword"
            :toggle-password="true" />
          <VaInput v-model="passwordForm.confirmPassword" :label="t('vuestic.profile.confirm_password')" type="password"
            class="font-inter" :error="!!passwordErrors.confirmPassword" :error-message="passwordErrors.confirmPassword"
            :toggle-password="true" />
        </form>
      </template>
      <template #footer>
        <VaButton preset="secondary" class="mr-2" @click="showPasswordModal = false" :disabled="passwordLoading">
          {{ t('vuestic.profile.cancel') }}
        </VaButton>
        <VaButton preset="primary" class="bg-primary text-white hover:bg-secondary" @click="changePassword"
          :loading="passwordLoading" type="submit">
          {{ t('vuestic.profile.set_new_password') }}
        </VaButton>
      </template>
    </VaModal>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { useRouter } from 'vue-router'; // Импортируем router
import axios from 'axios';

export default {
  name: 'UserProfile',
  props: {
    user: {
      type: Object,
      default: null,
    },
  },
  setup(props, { emit }) { // Добавляем context для $emit
    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter(); // Получаем router
    const success = ref(false);
    const passwordLoading = ref(false);
    const showPasswordModal = ref(false);
    const isSendingVerification = ref(false);
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
    const form = reactive({
      name: '',
      email: '',
    });
    const errors = reactive({
      name: '',
      email: '',
    });
    const passwordForm = reactive({
      newPassword: '',
      confirmPassword: '',
    });
    const passwordErrors = reactive({
      newPassword: '',
      confirmPassword: '',
    });

    const toast = useToast();

    onMounted(async () => {
      await authStore.fetchUser();
      form.name = authStore.user?.name || '';
      form.email = authStore.user?.email || '';
    });

    const handleSubmit = async () => {
      errors.name = '';
      errors.email = '';
      success.value = false;

      if (!form.name) errors.name = t('vuestic.profile.errors.name_required');
      if (!form.email) errors.email = t('vuestic.profile.errors.email_required');
      else if (!/\S+@\S+\.\S+/.test(form.email)) errors.email = t('vuestic.profile.errors.email_invalid');

      if (errors.name || errors.email) return;

      const result = await authStore.updateUser({
        name: form.name,
        email: form.email,
      });
      if (result) {
        success.value = true;
        toast.init({
          message: t('vuestic.profile.success'),
          color: 'success',
          duration: 3000,
        });
        setTimeout(() => (success.value = false), 3000);
      }
    };

    const handleLogout = async () => {
      await authStore.logout(router); // Передаем router в logout
    };

    const changePassword = async () => {
      passwordErrors.newPassword = '';
      passwordErrors.confirmPassword = '';

      if (!passwordForm.newPassword) {
        passwordErrors.newPassword = t('vuestic.profile.errors.new_password_required');
        toast.init({
          message: t('vuestic.profile.errors.new_password_required'),
          color: 'danger',
          duration: 3000,
        });
      } else if (passwordForm.newPassword.length < 8) {
        passwordErrors.newPassword = t('vuestic.profile.errors.password_min_length');
        toast.init({
          message: t('vuestic.profile.errors.password_min_length'),
          color: 'danger',
          duration: 3000,
        });
      }
      if (!passwordForm.confirmPassword) {
        passwordErrors.confirmPassword = t('vuestic.profile.errors.confirm_password_required');
        toast.init({
          message: t('vuestic.profile.errors.confirm_password_required'),
          color: 'danger',
          duration: 3000,
        });
      } else if (passwordForm.newPassword !== passwordForm.confirmPassword) {
        passwordErrors.confirmPassword = t('vuestic.profile.errors.passwords_mismatch');
        toast.init({
          message: t('vuestic.profile.errors.passwords_mismatch'),
          color: 'danger',
          duration: 3000,
        });
      }

      if (passwordErrors.newPassword || passwordErrors.confirmPassword) {
        return;
      }

      passwordLoading.value = true;
      try {
        const response = await axios.put('/api/user/change-password', {
          new_password: passwordForm.newPassword,
          confirm_password: passwordForm.confirmPassword,
        }, {
          headers: { 'Authorization': `Bearer ${authStore.token}` }
        });
        toast.init({
          message: t('vuestic.profile.password_changed'),
          color: 'success',
          duration: 3000,
        });
        showPasswordModal.value = false;
        passwordForm.newPassword = '';
        passwordForm.confirmPassword = '';
      } catch (error) {
        const errorMessage = error.response?.data?.message || t('vuestic.profile.password_change_error');
        toast.init({
          message: errorMessage,
          color: 'danger',
          duration: 5000,
        });
      } finally {
        passwordLoading.value = false;
      }
    };

    return {
      t,
      authStore,
      form,
      errors,
      success,
      handleSubmit,
      showPasswordModal,
      passwordLoading,
      passwordForm,
      passwordErrors,
      changePassword,
      isSendingVerification,
      sendVerificationEmail,
      handleLogout, // Добавляем метод
    };
  },
};
</script>

<style scoped>
.min-h-screen {
  display: flex;
  flex-direction: column;
}
</style>