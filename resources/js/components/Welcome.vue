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
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2 text-center">
          {{ $t('login') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-8">
          {{ $t('login_description') }}
        </p>

        <!-- Форма -->
        <div class="space-y-6">
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
            :rules="[value => !!value || $t('password_required')]"
            :error-messages="formErrors.password"
          >
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке от сервера -->
          <div v-if="serverError" class="text-center text-red-600 font-inter">
            {{ serverError }}
          </div>

          <!-- Кнопка входа -->
          <VaButton
            @click="handleLogin"
            color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading"
            :disabled="!isFormValid"
          >
            {{ $t('login') }}
          </VaButton>

          <!-- Навигация между входом и регистрацией -->
          <div class="flex text-center space-x-4 mb-8">
            <router-link to="/register">
              {{ $t('register') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Infobar from './Infobar.vue';

export default {
  name: 'Welcome',
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
        email: '',
        password: '',
      },
      formErrors: {
        email: [],
        password: [],
      },
      isPasswordVisible: false,
      serverError: null,
    };
  },

  computed: {
    isFormValid() {
      const emailValid = this.form.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email);
      const passwordValid = this.form.password;
      const formErrorsEmpty =
        this.formErrors.email.length === 0 &&
        this.formErrors.password.length === 0;

      console.log('isFormValid:', { emailValid, passwordValid, formErrorsEmpty, form: this.form, formErrors: this.formErrors });
      return emailValid && passwordValid && formErrorsEmpty;
    },
  },

  watch: {
    'form.email'() {
      this.validateEmail();
    },
    'form.password'() {
      this.validatePassword();
    },
  },

  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
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
      }
    },
    async handleLogin() {
      console.log('Начало handleLogin, form:', this.form);
      // Сбрасываем ошибки сервера
      this.serverError = null;

      // Вызываем валидацию всех полей
      this.validateEmail();
      this.validatePassword();

      if (!this.isFormValid) {
        console.log('Форма невалидна, выход из handleLogin');
        return;
      }

      try {
        console.log('Перед вызовом authStore.login');
        await this.authStore.login(this.form);
        console.log('После успешного логина, переход на /dashboard');
        this.router.push('/dashboard');
      } catch (error) {
        console.log('Ошибка в handleLogin:', error);
        console.log('Полный ответ сервера:', error.response);
        console.log('Данные ответа:', error.response?.data);
        console.log('Статус:', error.response?.status);

        if (error.response?.status === 401 || error.response?.status === 422) {
          const serverErrors = error.response?.data?.errors || {};
          this.formErrors.email = serverErrors.email || [];
          this.formErrors.password = serverErrors.password || [];

          if (!this.formErrors.email.length && !this.formErrors.password.length) {
            if (error.response?.status === 401) {
              console.log('Устанавливаем serverError для 401');
              this.serverError = this.$t('invalid_credentials');
            } else if (error.response?.data?.message) {
              console.log('Устанавливаем serverError из message');
              this.serverError = error.response.data.message;
            } else {
              console.log('Устанавливаем serverError по умолчанию');
              this.serverError = this.$t('login_error');
            }
          }
        } else {
          console.log('Устанавливаем serverError для других ошибок');
          this.serverError = error.response?.data?.message || this.$t('login_error');
        }
        console.log('После обработки ошибки, serverError:', this.serverError);
      }
    },
  },
};
</script>