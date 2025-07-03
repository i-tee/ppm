<!-- resources/js/components/dashboard/UserProfile.vue -->
<template>
  <!-- Место для аватара -->
  <div class="flex flex-col items-center mb-6">
    <div class="relative">
      <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-200 flex items-center justify-center bg-gray-100">
        <img v-if="authStore.user?.avatar" :src="avatarUrl" alt="Avatar" class="w-full h-full object-cover">
        <va-icon v-else name="account_circle" size="xx-large" class="text-gray-400" />
      </div>
      <va-button icon="edit" size="small" class="absolute -bottom-2 -right-2 !rounded-full shadow-md"
        @click="openAvatarUpload" :disabled="!authStore.user?.email_verified_at" />
    </div>
    <div class="mt-2 text-center">
      <h3 class="text-lg font-semibold">{{ authStore.user?.name }}</h3>
      <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
    </div>
  </div>

  <!-- Модальное окно для загрузки аватара -->
  <VaModal v-model="showAvatarModal" :title="t('vuestic.profile.upload_avatar')" size="small" no-outside-dismiss>
    <template #default>
      <div class="flex flex-col items-center">
        <div v-if="avatarPreview" class="mb-4">
          <img :src="avatarPreview" alt="Preview" class="max-w-[200px] max-h-[200px] rounded-md">
        </div>
        <va-file-upload
          v-model="avatarFile"
          dropzone
          file-types="image/*"
          :max-files="1"
          :max-size="2048"
          :disabled="avatarLoading"
          @file-added="handleFileAdded"
        />
        <va-button class="mt-4" @click="uploadAvatar" :disabled="!avatarFile.length || avatarLoading" :loading="avatarLoading">
          {{ t('vuestic.profile.upload_button') }}
        </va-button>
      </div>
    </template>
  </VaModal>

  <!-- ... (остальной шаблон остается без изменений) ... -->

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
      <VaButton icon-right="mail" :loading="isSendingVerification" @click="sendVerificationEmail" size="small">
      </VaButton>
      <a href="#" @click.prevent="sendVerificationEmail"
        class="underline text-primary cursor-pointer whitespace-nowrap">
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
import { ref, reactive, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'UserProfile',
  props: {
    user: {
      type: Object,
      default: null,
    },
  },
  setup(props, { emit }) {
    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();
    const toast = useToast();

    // Avatar logic
    const showAvatarModal = ref(false);
    const avatarFile = ref([]);
    const avatarLoading = ref(false);
    const avatarPreview = ref(null);

    const avatarUrl = computed(() => {
      return authStore.user?.avatar_url || '';
    });

    const openAvatarUpload = () => {
      avatarFile.value = [];
      avatarPreview.value = null;
      showAvatarModal.value = true;
    };

    const handleFileAdded = (files) => {
      if (files.length > 0) {
        const file = files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
          avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
      }
      // Ограничиваем до одного файла
      if (files.length > 1) {
        avatarFile.value = [files[0]];
        toast.init({
          message: t('vuestic.profile.only_one_file_allowed'),
          color: 'warning',
          duration: 3000,
        });
      } else {
        avatarFile.value = files;
      }
    };

    const uploadAvatar = async () => {
      if (!avatarFile.value.length) return;

      avatarLoading.value = true;
      try {
        const formData = new FormData();
        formData.append('avatar', avatarFile.value[0]);

        await authStore.uploadAvatar(formData);
        toast.init({
          message: t('vuestic.profile.avatar_uploaded'),
          color: 'success',
          duration: 3000,
        });
        showAvatarModal.value = false;
      } catch (error) {
        let errorMessage = error.response?.data?.error
          || error.response?.data?.message
          || error.message
          || t('vuestic.profile.avatar_upload_error');

        toast.init({
          message: errorMessage,
          color: 'danger',
          duration: 5000,
        });
      } finally {
        avatarLoading.value = false;
      }
    };

    // Existing logic
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
          duration: 3000,
        });
        await authStore.fetchUser();
        emit('user-updated', authStore.user);
      } catch (error) {
        console.log('Error response:', error.response ? error.response.data : error.message);
        toast.init({
          message: error.response?.data?.message || error.message || t('vuestic.profile.verification_email_failed'),
          color: 'danger',
          duration: 5000,
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
      await authStore.logout(router);
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
      handleLogout,
      // Avatar logic
      showAvatarModal,
      avatarFile,
      avatarLoading,
      avatarPreview,
      avatarUrl,
      openAvatarUpload,
      uploadAvatar,
      handleFileAdded,
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