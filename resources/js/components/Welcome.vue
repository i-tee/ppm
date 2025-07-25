<!-- resources/js/components/Welcome.vue -->
<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Форма входа -->
    <div class="w-full md:w-1/2 bg-gray-50 flex justify-center items-center p-6">
      <div class="w-full max-w-md">
        <!-- Логотип -->
        <Logo />
        <br>
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2 text-center">
          {{ $t('login') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-8">
          {{ $t('login_description') }}
        </p>

        <!-- Форма -->
        <div class="space-y-6">
          <VaInput v-model="form.email" type="email" :label="$t('email')" placeholder="hello@epicmax.co"
            class="w-full font-inter" prepend-inner-icon="email" :rules="[validateEmail]"
            :error="formErrors.email.length > 0" :error-messages="formErrors.email" />
          <VaInput v-model="form.password" :type="isPasswordVisible ? 'text' : 'password'" :label="$t('password')"
            placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :rules="[validatePassword]" :error="formErrors.password.length > 0"
            :error-messages="formErrors.password">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке от сервера -->
          <div v-if="serverError" class="text-center text-red-600 font-inter">
            {{ serverError }}
          </div>

          <!-- Кнопка входа -->
          <VaButton @click="handleLogin" color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading" :disabled="!isFormValid || authStore.loading">
            {{ $t('login') }}
          </VaButton>

          <!-- Ссылка для авторизации через Google -->
          <div class="text-center mt-4">
            <a href="/auth/google/redirect" class="text-blue-600 hover:text-blue-800 font-inter">Google</a>
            <span> | </span>
            <a href="/auth/yandex/redirect" class="text-blue-600 hover:text-blue-800 font-inter">Яндекс</a>
          </div>

          <!-- Навигация между входом и регистрацией -->
          <div class="flex text-center space-x-4 mb-8">
            <router-link to="/register">
              {{ $t('register') }}
            </router-link>
          </div>

          <!-- Ссылка для сброса пароля и попап -->
          <div class="text-center mt-4">
            <a href="#" @click.prevent="showResetModal = true, mailTransport()"
              class="text-gray-300 hover:text-gray-800 font-inter">
              {{ $t('forgot_password') }}
            </a>
          </div>

          <!-- Попап для сброса пароля -->
          <VaModal v-model="showResetModal" title="Сброс пароля" size="small" :hide-default-actions="true">
            <template #default>
              <VaInput v-model="resetEmail" type="email" :label="$t('email')" :placeholder="$t('enter_your_email')"
                class="w-full font-inter mb-4" prepend-inner-icon="email" :rules="[validateEmail]"
                :error="resetError.length > 0" :error-messages="resetError" />
              <VaButton @click="requestPasswordReset" color="primary"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-2 rounded-lg"
                :loading="authStore.loading" :disabled="!resetEmail || authStore.loading">
                {{ $t('send_reset_instructions') }}
              </VaButton>
            </template>
          </VaModal>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';
import Infobar from './Infobar.vue';
import Logo from './parts/Logo.vue';

export default {
  name: 'Welcome',
  components: { Infobar, Logo },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const { t } = useI18n();

    return { authStore, router, t };
  },

  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      formErrors: {
        email: [],
        password: [],
      },
      isPasswordVisible: false,
      serverError: null,
      showResetModal: false,
      resetEmail: '',
      resetError: '',
    };
  },

  computed: {
    isFormValid() {
      const emailValid = this.form.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email);
      const passwordValid = !!this.form.password;
      const formErrorsEmpty = this.formErrors.email.length === 0 && this.formErrors.password.length === 0;
      return emailValid && passwordValid && formErrorsEmpty;
    },
  },

  watch: {
    'form.email'(newValue) {
      this.resetErrors();
      this.validateField('email', newValue);
    },
    'form.password'(newValue) {
      this.resetErrors();
      this.validateField('password', newValue);
    },
  },

  methods: {
    mailTransport() {
      this.resetEmail = this.form.email;
    },
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    resetErrors() {
      this.serverError = null;
      this.formErrors.email = [];
      this.formErrors.password = [];
    },
    validateField(field, value) {
      this.formErrors[field] = [];
      if (field === 'email') {
        if (!value) this.formErrors.email.push(this.$t('email_required'));
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) this.formErrors.email.push(this.$t('invalid_email'));
      } else if (field === 'password') {
        if (!value) this.formErrors.password.push(this.$t('password_required'));
      }
    },
    validateEmail(value) {
      this.validateField('email', value);
      return this.formErrors.email.length === 0 || this.formErrors.email[0];
    },
    validatePassword(value) {
      this.validateField('password', value);
      return this.formErrors.password.length === 0 || this.formErrors.password[0];
    },
    async handleLogin() {
      this.resetErrors();

      this.validateField('email', this.form.email);
      this.validateField('password', this.form.password);

      if (!this.isFormValid) return;

      try {
        await this.authStore.login(this.form);
        this.router.push('/dashboard');
      } catch (error) {
        if (error.response?.status === 401) {
          this.formErrors.email = [this.$t('invalid_credentials')];
          this.formErrors.password = [this.$t('invalid_credentials')];
          this.serverError = this.$t('invalid_credentials');
        } else if (error.response?.status === 422) {
          const serverErrors = error.response?.data?.errors || {};
          this.formErrors.email = serverErrors.email || [];
          this.formErrors.password = serverErrors.password || [];
          this.serverError = error.response?.data?.message || this.$t('login_error');
        } else {
          this.serverError = error.response?.data?.message || this.$t('login_error');
        }
      } finally {
        if (this.authStore.loading) this.authStore.loading = false;
      }
    },

    async requestPasswordReset() {
      this.resetError = '';

      if (!this.resetEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.resetEmail)) {
        this.resetError = this.$t('invalid_email');
        return;
      }

      try {
        await this.authStore.requestPasswordReset(this.resetEmail);
        this.showResetModal = false;
        this.serverError = this.$t('reset_instructions_sent');
      } catch (error) {
        this.resetError = error.response?.data?.message || this.$t('reset_error');
      }
    },

    mounted() {
      const urlParams = new URLSearchParams(window.location.search);
      const token = urlParams.get('token');
      const email = urlParams.get('email');
      if (token && email) {
        const authStore = useAuthStore();
        authStore.token = token;
        authStore.user = { email }; // Минимальные данные пользователя
        localStorage.setItem('auth_token', token);
        this.router.push('/dashboard');
      }
    },
  },
};
</script>