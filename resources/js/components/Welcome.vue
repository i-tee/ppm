<!-- resources/js/components/Welcome.vue -->
<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Форма входа -->
    <div class="w-full md:w-1/2 bg-gray-50 flex flex-col justify-center items-center p-6">
      <div class="w-full max-w-md">
        <!-- Навигация между входом и регистрацией -->
        <div class="flex justify-center space-x-4 mb-8">
          <router-link
            to="/"
            class="text-lg font-inter font-semibold text-indigo-600 border-b-2 border-indigo-600 pb-2"
          >
            {{ $t('login') }}
          </router-link>
          <router-link
            to="/register"
            class="text-lg font-inter font-semibold text-gray-600 hover:text-indigo-600 pb-2"
          >
            {{ $t('register') }}
          </router-link>
        </div>

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
            :error="!!error && error.includes('email')"
            :error-message="error"
          />
          <VaInput
            v-model="form.password"
            :type="isPasswordVisible ? 'text' : 'password'"
            :label="$t('password')"
            placeholder="*********"
            class="w-full font-inter"
            prepend-inner-icon="lock"
            @click-append-inner="togglePassword"
            :error="!!error && error.includes('password')"
            :error-message="error"
          >
            <template #appendInner>
              <VaIcon
                :name="isPasswordVisible ? 'visibility_off' : 'visibility'"
                size="small"
                color="primary"
              />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке -->
          <div v-if="error" class="text-center text-red-600 font-inter">
            {{ error }}
          </div>

          <!-- Кнопка входа -->
          <VaButton
            @click="handleLogin"
            color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading"
          >
            {{ $t('login') }}
          </VaButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n'; // Добавляем useI18n для $t
import Infobar from './Infobar.vue'; // Проверяем путь

export default {
  name: 'Welcome',
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
        email: '',
        password: '',
      },
      isPasswordVisible: false,
      error: null,
    };
  },

  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    async handleLogin() {
      try {
        this.error = null;
        await this.authStore.login(this.form);
        this.router.push('/dashboard');
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка входа';
      }
    },
  },
};
</script>