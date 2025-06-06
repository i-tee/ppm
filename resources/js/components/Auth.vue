<!-- resources/js/components/Auth.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-900 to-purple-800 flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-6 sm:p-8">
      <!-- Навигация -->
      <div class="flex justify-center space-x-4 mb-6 border-b border-gray-200 pb-4">
        <router-link
          to="/"
          class="text-lg font-inter font-semibold px-4 py-2 rounded-t-lg"
          :class="{ 'text-indigo-600 bg-indigo-50': $route.path === '/', 'text-gray-600 hover:text-indigo-600': $route.path !== '/' }"
        >
          {{ $t('login') }}
        </router-link>
        <router-link
          to="/register"
          class="text-lg font-inter font-semibold px-4 py-2 rounded-t-lg"
          :class="{ 'text-indigo-600 bg-indigo-50': $route.path === '/register', 'text-gray-600 hover:text-indigo-600': $route.path !== '/register' }"
        >
          {{ $t('register') }}
        </router-link>
      </div>

      <!-- Форма -->
      <div v-if="$route.path === '/'" class="space-y-6">
        <h2 class="text-2xl font-bold font-inter text-center text-gray-900 flex items-center justify-center">
          <VaIcon name="mdi-login" size="24" color="primary" class="mr-2" />
          {{ $t('login') }}
        </h2>
        <p class="text-center text-gray-600 font-inter text-sm">{{ $t('login_description') }}</p>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <VaInput
            v-model="form.email"
            type="email"
            :label="$t('email')"
            placeholder="hello@epicmax.co"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-email"
            :error="!!error && error.includes('email')"
            :error-message="error"
          />
          <VaInput
            v-model="form.password"
            type="password"
            :label="$t('password')"
            placeholder="*********"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-lock"
            :error="!!error && error.includes('password')"
            :error-message="error"
          >
            <template #appendInner>
              <VaIcon
                name="mdi-eye-off"
                size="small"
                color="gray"
                @click="togglePasswordVisibility"
                class="cursor-pointer"
              />
            </template>
          </VaInput>
          <VaButton
            type="submit"
            color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-2.5 rounded-lg transition transform hover:scale-105"
            :loading="loading"
          >
            {{ $t('login') }}
          </VaButton>
        </form>
        <p v-if="error" class="text-center text-red-600 font-inter text-sm">{{ error }}</p>
      </div>

      <div v-else-if="$route.path === '/register'" class="space-y-6">
        <h2 class="text-2xl font-bold font-inter text-center text-gray-900 flex items-center justify-center">
          <VaIcon name="mdi-account-plus" size="24" color="primary" class="mr-2" />
          {{ $t('register') }}
        </h2>
        <p class="text-center text-gray-600 font-inter text-sm">{{ $t('register_description') }}</p>

        <form @submit.prevent="handleRegister" class="space-y-4">
          <VaInput
            v-model="form.name"
            type="text"
            :label="$t('name')"
            placeholder="John Doe"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-account"
          />
          <VaInput
            v-model="form.email"
            type="email"
            :label="$t('email')"
            placeholder="hello@epicmax.co"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-email"
          />
          <VaInput
            v-model="form.password"
            type="password"
            :label="$t('password')"
            placeholder="*********"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-lock"
          >
            <template #appendInner>
              <VaIcon
                name="mdi-eye-off"
                size="small"
                color="gray"
                @click="togglePasswordVisibility"
                class="cursor-pointer"
              />
            </template>
          </VaInput>
          <VaInput
            v-model="form.password_confirmation"
            type="password"
            :label="$t('confirm_password')"
            placeholder="*********"
            class="w-full font-inter text-sm"
            prepend-inner-icon="mdi-lock"
          >
            <template #appendInner>
              <VaIcon
                name="mdi-eye-off"
                size="small"
                color="gray"
                @click="togglePasswordVisibility"
                class="cursor-pointer"
              />
            </template>
          </VaInput>
          <VaButton
            type="submit"
            color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-2.5 rounded-lg transition transform hover:scale-105"
            :loading="loading"
          >
            {{ $t('register') }}
          </VaButton>
        </form>
        <p v-if="error" class="text-center text-red-600 font-inter text-sm">{{ error }}</p>
      </div>

      <!-- Копирайт -->
      <p class="text-center text-xs text-gray-500 mt-6">
        {{ $t('infobar.footer') }}
        <a href="https://tee.su/" class="underline hover:text-gray-700">tee.su</a> &
        <a href="https://github.com/i-tee" class="underline hover:text-gray-700">GitHub</a>
      </p>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

export default {
  name: 'Auth',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const { t } = useI18n();
    const form = ref({
      email: '',
      password: '',
      name: '',
      password_confirmation: '',
    });
    const error = ref(null);
    const loading = ref(false);
    const showPassword = ref(false);

    const togglePasswordVisibility = () => {
      showPassword.value = !showPassword.value;
    };

    const handleLogin = async () => {
      loading.value = true;
      error.value = null;
      try {
        await authStore.login({ email: form.value.email, password: form.value.password });
        router.push('/dashboard');
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка входа';
      } finally {
        loading.value = false;
      }
    };

    const handleRegister = async () => {
      loading.value = true;
      error.value = null;
      try {
        const response = await api.post('/register', form.value);
        await authStore.login({ email: form.value.email, password: form.value.password });
        router.push('/dashboard');
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка регистрации';
      } finally {
        loading.value = false;
      }
    };

    return { authStore, router, t, form, error, loading, togglePasswordVisibility, handleLogin, handleRegister };
  },
};
</script>

<style scoped>
.v-input__wrapper {
  @apply border border-gray-300 rounded-md transition-all duration-200;
}
.v-input__wrapper:hover {
  @apply border-indigo-400 shadow-md;
}
</style>