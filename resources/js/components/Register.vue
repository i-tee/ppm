<!-- resources/js/components/Register.vue -->
<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Форма регистрации -->
    <div class="w-full md:w-1/2 bg-gray-50 flex justify-center items-center p-6">
      <div class="w-full max-w-md">
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2 text-center">
          {{ $t('register') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-8">
          {{ $t('register_description') }}
        </p>

        <!-- Форма -->
        <div class="space-y-6">
          <VaInput
            v-model="form.name"
            type="text"
            :label="$t('name')"
            placeholder="John Doe"
            class="w-full font-inter"
            prepend-inner-icon="person"
            :rules="[value => !!value || $t('name_required')]"
            :error-messages="formErrors.name"
          />
          <VaInput
            v-model="form.email"
            type="email"
            :label="$t('email')"
            placeholder="hello@epicmax.co"
            class="w-full font-inter"
            prepend-inner-icon="email"
            :rules="[value => !!value || $t('email_required'), value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || $t('invalid_email')]"
            :error-messages="formErrors.email"
          />
          <VaInput
            v-model="form.password"
            :type="isPasswordVisible ? 'text' : 'password'"
            :label="$t('password')"
            placeholder="*********"
            class="w-full font-inter"
            prepend-inner-icon="lock"
            @click-append-inner="togglePassword"
            :rules="[value => !!value || $t('password_required'), value => (value && value.length >= 8) || $t('password_min_length')]"
            :error-messages="formErrors.password"
          >
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>
          <VaInput
            v-model="form.password_confirmation"
            :type="isPasswordVisible ? 'text' : 'password'"
            :label="$t('confirm_password')"
            placeholder="*********"
            class="w-full font-inter"
            prepend-inner-icon="lock"
            @click-append-inner="togglePassword"
            :rules="[value => !!value || $t('confirm_password_required'), value => value === form.password || $t('passwords_mismatch')]"
            :error-messages="formErrors.password_confirmation"
          >
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке от сервера -->
          <div v-if="serverError" class="text-center text-red-600 font-inter">
            {{ serverError }}
          </div>

          <!-- Кнопка регистрации -->
          <VaButton
            @click="register"
            color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading"
            :disabled="!isFormValid"
          >
            {{ $t('register') }}
          </VaButton>

          <div class="flex text-center space-x-4 mb-8">
            <router-link to="/">
              {{ $t('login') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../api';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Infobar from './Infobar.vue';

export default {
  name: 'Register',
  components: { Infobar },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const { t } = useI18n();

    return { authStore, router, t };
  },
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
      },
      formErrors: {
        name: [],
        email: [],
        password: [],
        password_confirmation: [],
      },
      isPasswordVisible: false,
      serverError: null,
    };
  },

  computed: {
    isFormValid() {
      const emailValid = this.form.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email);
      const passwordValid = this.form.password && this.form.password.length >= 8;
      const passwordsMatch = this.form.password === this.form.password_confirmation;
      const formErrorsEmpty =
        this.formErrors.name.length === 0 &&
        this.formErrors.email.length === 0 &&
        this.formErrors.password.length === 0 &&
        this.formErrors.password_confirmation.length === 0;

      return (
        this.form.name &&
        emailValid &&
        passwordValid &&
        this.form.password_confirmation &&
        passwordsMatch &&
        formErrorsEmpty
      );
    },
  },

  watch: {
    'form.email'() {
      this.validateEmail();
    },
    'form.password'() {
      this.validatePassword();
    },
    'form.password_confirmation'() {
      this.validatePasswordConfirmation();
    },
    'form.name'() {
      this.validateName();
    },
  },

  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    validateName() {
      this.formErrors.name = this.form.name ? [] : [this.$t('name_required')];
    },
    validateEmail() {
      this.formErrors.email = [];
      if (!this.form.email) {
        this.formErrors.email.push(this.$t('email_required'));
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        this.formErrors.email.push(this.$t('invalid_email'));
      }
    },
    validatePassword() {
      this.formErrors.password = [];
      if (!this.form.password) {
        this.formErrors.password.push(this.$t('password_required'));
      } else if (this.form.password.length < 8) {
        this.formErrors.password.push(this.$t('password_min_length'));
      }
    },
    validatePasswordConfirmation() {
      this.formErrors.password_confirmation = [];
      if (!this.form.password_confirmation) {
        this.formErrors.password_confirmation.push(this.$t('confirm_password_required'));
      } else if (this.form.password !== this.form.password_confirmation) {
        this.formErrors.password_confirmation.push(this.$t('passwords_mismatch'));
      }
    },
    async register() {
      // Сбрасываем ошибки сервера
      this.serverError = null;

      // Вызываем валидацию всех полей
      this.validateName();
      this.validateEmail();
      this.validatePassword();
      this.validatePasswordConfirmation();

      if (!this.isFormValid) {
        return;
      }

      try {
        await api.post('/register', this.form);
        await this.authStore.login({
          email: this.form.email,
          password: this.form.password,
        });
        this.form = { name: '', email: '', password: '', password_confirmation: '' };
        this.router.push('/dashboard');
      } catch (error) {
        if (error.response?.status === 422 && error.response?.data?.errors) {
          const serverErrors = error.response.data.errors;
          this.formErrors.name = serverErrors.name || [];
          this.formErrors.email = serverErrors.email || [];
          this.formErrors.password = serverErrors.password || [];
          this.formErrors.password_confirmation = serverErrors.password_confirmation || [];

          if (error.response.data.message && !this.formErrors.email.length && !this.formErrors.name.length && !this.formErrors.password.length && !this.formErrors.password_confirmation.length) {
            this.serverError = error.response.data.message;
          }
        } else {
          this.serverError = error.response?.data?.message || this.$t('register_error');
        }
      }
    },
  },
};
</script>