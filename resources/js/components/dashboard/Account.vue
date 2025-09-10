<!-- resources/js/components/dashboard/UserProfile.vue -->
<template>

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

  <div class="max-w-sm main-account bg-white p-4 rounded-md">
    <!-- Место для аватара -->
    <div class="flex flex-col items-center mb-6">
      <div class="relative mt-4">
        <div
          class="w-28 h-28 rounded-full overflow-hidden border-2 border-gray-200 flex items-center justify-center bg-gray-100">
          <img :src="avatarUrl" alt="Avatar" class="w-full h-full object-cover">
        </div>
        <va-button icon="edit" size="small" class="absolute top-1 -right-3 !rounded-full shadow-md"
          @click="openAvatarUpload" :disabled="!authStore.user?.email_verified_at" />
      </div>
      <div class="mt-2 text-center">
        <form @submit.prevent="handleSubmit">
          <div>
            <VaValue v-slot="v">
              <input ref="nameInput" class="item__input text-center -js-focusMe" v-if="v.value" v-model="form.name"
                :disabled="!authStore.user?.email_verified_at">
              <span v-else>
                {{ form.name }}
              </span>
              <VaButton :icon="v.value ? 'save' : 'edit'" preset="plain" size="small" class="absolute -js-buttonName"
                :disabled="!authStore.user?.email_verified_at" @click="
                if (v.value && form.name !== authStore.user?.name) {
                  handleSubmit();
                }
                  v.value = !v.value;
                  // дождаться рендера input и потом сфокусироваться
                  $nextTick(() => {
                    focusFunction();
                  });
                  " />
            </VaValue>
            <div v-if="errors.name" class="text-danger text-xs mt-4">{{ errors.name }}</div>
          </div>
          <div>
            <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
          </div>
        </form>
      </div>
    </div>

    <div class="flex flex-row justify-center gap-4">
      <div class="flex flex-col items-center">
        <!-- Кнопка для открытия попапа смены пароля -->
        <VaButton preset="secondary" class="flex-1 px-4 py-2 rounded font-inter" @click="showPasswordModal = true"
          :disabled="!authStore.user?.email_verified_at">
          {{ t('vuestic.profile.change_password') }}
        </VaButton>
      </div>
      <div class="flex flex-col items-center">
        <!-- Кнопка для логаута -->
        <VaButton preset="secondary" class="flex-1 px-4 py-2 rounded font-inter" @click="handleLogout"
          :disabled="authStore.loading">
          {{ t('vuestic.profile.logout') }}
        </VaButton>
      </div>
    </div>

  </div>

  <!-- Модальное окно для загрузки аватара -->
  <VaModal v-model="showAvatarModal" size="small" close-button :hide-default-actions="true">
    <template #default>
      <h3 class="va-h3 mb-4">{{ t('vuestic.profile.upload_avatar') }}</h3>
      <div class="flex flex-col">
        <div class="items-center flex text-center justify-center mb-4">
          <div v-if="avatarPreview">
            <img :src="avatarPreview" alt="Preview" class="max-w-28 max-h-28 rounded-md m-auto">
          </div>
        </div>
        <va-file-upload v-model="avatarFile" dropzone file-types="image/*" :max-files="1" :max-size="2048"
          :disabled="avatarLoading" @file-added="handleFileAdded" />
        <div class="text-end justify-end mt-4">
          <va-button preset="secondary" style="color: #6B7280;" class="mr-2" @click="showAvatarModal = false">
            {{ t('vuestic.profile.cancel') }}
          </va-button>
          <va-button @click="uploadAvatar" :disabled="!avatarFile.length || avatarLoading" :loading="avatarLoading">
            {{ t('vuestic.profile.upload_button') }}
          </va-button>
        </div>
      </div>
    </template>
  </VaModal>

  <!-- Модальное окно для изменения пароля -->
  <VaModal v-model="showPasswordModal" size="small" close-button :hide-default-actions="true">
    <template #default>
      <h3 class="va-h3 mb-4">{{ t('vuestic.profile.change_password') }}</h3>
      <form @submit.prevent="changePassword" class="flex flex-col gap-4 mb-4">
        <VaInput v-model="passwordForm.newPassword" :label="t('vuestic.profile.new_password')" type="password"
          class="font-inter" :error="!!passwordErrors.newPassword" :error-message="passwordErrors.newPassword"
          :toggle-password="true" />
        <VaInput v-model="passwordForm.confirmPassword" :label="t('vuestic.profile.confirm_password')" type="password"
          class="font-inter" :error="!!passwordErrors.confirmPassword" :error-message="passwordErrors.confirmPassword"
          :toggle-password="true" />
      </form>
    </template>
    <template #footer>
      <VaButton preset="secondary" style="color: #6B7280;" class="mr-2" @click="showPasswordModal = false"
        :disabled="passwordLoading">
        {{ t('vuestic.profile.cancel') }}
      </VaButton>
      <VaButton @click="changePassword" :loading="passwordLoading" type="submit">
        {{ t('vuestic.profile.set_new_password') }}
      </VaButton>
    </template>
  </VaModal>

</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { ref, reactive, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  data() {
    return {
      showModalPass: false,
    };
  },
  name: 'UserProfile',
  props: {
    user: {
      type: Object,
      default: null,
    },
  },
  methods: {
    async uploadAvatar() {
      try {
        const formData = new FormData();
        formData.append('avatar', this.avatarFile[0].file);

        const response = await this.authStore.uploadAvatar(formData);

        // Автоматически обновится через реактивность Pinia
        this.$toast.success('Аватар успешно обновлен!');
      } catch (error) {
        this.$toast.error(error.message);
      }
    },
    focusFunction() {
      this.$nextTick(() => {
        // Vue 3: $refs is now a proxy, so access .value for DOM elements
        const input = this.$refs.nameInput;
        if (input && input.focus) {
          input.focus();
        } else if (input && input.$el && input.$el.focus) {
          input.$el.focus();
        }
      });
      console.log('Функция вызвана!');
    }
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
      //console.log('User profile mounted:', authStore.user);
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