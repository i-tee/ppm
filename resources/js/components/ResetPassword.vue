<!-- resources/js/components/ResetPassword.vue -->
<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Форма сброса пароля -->
    <div class="w-full md:w-1/2 bg-gray-50 flex justify-center items-center p-6">
      <div class="w-full max-w-md">
        <!-- Логотип -->
        <Logo />
        <br>
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2 text-center">
          {{ $t('reset_password') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-2">
          {{ $t('set_new_password_description') }}
        </p>
        <p class="text-center text-gray-800 font-inter mb-6">
          {{ email }}
        </p>

        <!-- Форма -->
        <div class="space-y-6">
          <VaInput ref="emailInput" v-model="email" type="email" :label="$t('email')" placeholder="example@email.com"
            class="w-full font-inter" prepend-inner-icon="email" :rules="[validateEmail]"
            :error="formErrors.email.length > 0" :error-messages="formErrors.email" />

          <VaInput v-model="newPassword" :type="isPasswordVisible ? 'text' : 'password'" :label="$t('new_password')"
            placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :rules="[validatePassword]" :error="formErrors.password.length > 0"
            :error-messages="formErrors.password">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <VaInput v-model="confirmPassword" :type="isPasswordVisible ? 'text' : 'password'"
            :label="$t('confirm_password')" placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :rules="[validateConfirmPassword]"
            :error="formErrors.confirm.length > 0" :error-messages="formErrors.confirm">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке от сервера -->
          <div v-if="serverError" class="text-center text-red-600 font-inter">
            {{ serverError }}
          </div>

          <!-- Кнопка сброса пароля -->
          <VaButton @click="handleResetPassword" color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading" :disabled="!isFormValid || authStore.loading">
            {{ $t('save_new_password') }}
          </VaButton>

          <div class="flex text-center space-x-4 mb-8">
            <router-link to="/">
              {{ $t('back_to_login') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';
import Infobar from './Infobar.vue';
import Logo from './parts/Logo.vue';

export default {
  name: 'ResetPassword',
  components: { Infobar, Logo },
  setup() {
    const authStore = useAuthStore();
    const route = useRoute();
    const router = useRouter();
    const { t } = useI18n();

    return { authStore, route, router, t };
  },

  data() {
    return {
      email: '',
      newPassword: '',
      confirmPassword: '',
      formErrors: { email: [], password: [], confirm: [] },
      isPasswordVisible: false,
      serverError: null,
    };
  },

  computed: {
    isFormValid() {
      return (
        this.formErrors.email.length === 0 &&
        this.formErrors.password.length === 0 &&
        this.formErrors.confirm.length === 0 &&
        this.email.trim() !== '' && // Убедимся, что email не пустой
        this.newPassword.trim() !== '' &&
        this.confirmPassword.trim() !== ''
      );
    },
  },

  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const encodedEmail = urlParams.get('email');

    if (encodedEmail) {
      const decodedEmail = decodeURIComponent(encodedEmail);
      this.email = decodedEmail; // Устанавливаем email из URL
      // Скрываем поле email, если оно уже заполнено
      this.$refs.emailInput.$el.style.display = 'none';
    }
  },

  watch: {
    email(value) {
      this.formErrors.email = [];
      if (!value || value.trim() === '') this.formErrors.email.push(this.$t('email_required'));
      else if (!/^\S+@\S+\.\S+$/.test(value)) this.formErrors.email.push(this.$t('invalid_email'));
    },
    newPassword(value) {
      this.validatePassword(value);
      this.validateConfirmPassword(this.confirmPassword);
    },
    confirmPassword(value) {
      this.validateConfirmPassword(value);
    },
  },

  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    validatePassword(value) {
      this.formErrors.password = [];
      if (!value || value.trim() === '') this.formErrors.password.push(this.$t('password_required'));
      else if (value.length < 8) this.formErrors.password.push(this.$t('password_min_length'));
      return this.formErrors.password.length === 0;
    },
    validateConfirmPassword(value) {
      this.formErrors.confirm = [];
      if (!value || value.trim() === '') this.formErrors.confirm.push(this.$t('confirm_password_required'));
      else if (value !== this.newPassword) this.formErrors.confirm.push(this.$t('passwords_mismatch'));
      return this.formErrors.confirm.length === 0;
    },
    validateEmail(value) {
      this.formErrors.email = [];
      if (!value || value.trim() === '') this.formErrors.email.push(this.$t('email_required'));
      else if (!/^\S+@\S+\.\S+$/.test(value)) this.formErrors.email.push(this.$t('invalid_email'));
      return this.formErrors.email.length === 0;
    },
    async handleResetPassword() {
      //console.log('Handle Reset Password triggered');
      this.serverError = null;
      if (!this.validateEmail(this.email) || !this.validatePassword(this.newPassword) || !this.validateConfirmPassword(this.confirmPassword)) {
        //console.log('Validation failed:', this.formErrors);
        return;
      }

      try {
        const token = this.route.query.token;
        //console.log('Token:', token, 'Email:', this.email, 'New Password:', this.newPassword, 'Confirm Password:', this.confirmPassword);
        if (!token) throw new Error(this.$t('invalid_reset_token'));
        await this.authStore.resetPassword(token, this.newPassword, this.email, this.confirmPassword); // Убедимся, что все поля передаются
        //console.log('Reset successful, redirecting to /dashboard');
        this.router.push('/dashboard');
      } catch (error) {
        //console.log('Reset error:', error.response?.data || error.message);
        this.serverError = error.response?.data?.message || this.$t('reset_error');
        if (error.response?.status === 422) {
          this.serverError = error.response?.data?.errors?.password?.[0] || this.$t('reset_error');
          this.formErrors.email = error.response?.data?.errors?.email || [];
          this.formErrors.password = error.response?.data?.errors?.password || [];
          this.formErrors.confirm = error.response?.data?.errors?.password_confirmation || [];
        }
      }
    },
  },
};
</script>