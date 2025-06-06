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
          <VaInput v-model="form.name" type="text" :label="$t('name')" placeholder="John Doe" class="w-full font-inter"
            prepend-inner-icon="person" :error="!!error && error.includes('name')" :error-message="error" />
          <VaInput v-model="form.email" type="email" :label="$t('email')" placeholder="hello@epicmax.co"
            class="w-full font-inter" prepend-inner-icon="email" :error="!!error && error.includes('email')"
            :error-message="error" />
          <VaInput v-model="form.password" :type="isPasswordVisible ? 'text' : 'password'" :label="$t('password')"
            placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :error="!!error && error.includes('password')" :error-message="error">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>
          <VaInput v-model="form.password_confirmation" :type="isPasswordVisible ? 'text' : 'password'"
            :label="$t('confirm_password')" placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :error="!!error && error.includes('password')" :error-message="error">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке -->
          <div v-if="error" class="text-center text-red-600 font-inter">
            {{ error }}
          </div>

          <!-- Кнопка регистрации -->
          <VaButton @click="register" color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading">
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
import { useI18n } from 'vue-i18n'; // Добавляем useI18n
import Infobar from './Infobar.vue'; // Проверяем путь

export default {
  name: 'Register',
  components: { Infobar },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const { t } = useI18n(); // Используем useI18n для локализации

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
      isPasswordVisible: false,
      error: null,
    };
  },
  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    async register() {
      try {
        this.error = null;
        const response = await api.post('/register', this.form);
        await this.authStore.login({
          email: this.form.email,
          password: this.form.password,
        });
        this.form = { name: '', email: '', password: '', password_confirmation: '' };
        this.router.push('/dashboard');
      } catch (error) {
        this.error = error.response?.data?.message || 'Registration failed';
      }
    },
  },
};
</script>