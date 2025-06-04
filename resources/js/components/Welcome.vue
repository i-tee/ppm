<template>
  <div class="auth-wrapper">
    <va-card class="auth-card" outlined>
      <va-card-title class="justify-center text-center text-primary text-2xl">
        <VaIcon name="lock" class="mr-2" /> {{ $t('login') }}
      </va-card-title>

      <va-divider />

      <va-card-content>
        <p class="text-center text-secondary mb-4">{{ $t('login_description') }}</p>

        <va-input
          v-model="form.email"
          type="email"
          :label="$t('email')"
          placeholder="hello@epicmax.co"
          class="mb-4"
          prepend-inner-icon="email"
        />

        <va-input
          v-model="form.password"
          :type="isPasswordVisible ? 'text' : 'password'"
          :label="$t('password')"
          placeholder="*********"
          class="mb-4"
          prepend-inner-icon="lock"
          @click-append-inner="togglePassword"
        >
          <template #appendInner>
            <VaIcon
              :name="isPasswordVisible ? 'visibility_off' : 'visibility'"
              size="small"
              color="primary"
            />
          </template>
        </va-input>

        <div v-if="error" class="text-center text-danger mb-4">
          {{ error }}
        </div>

        <div class="flex justify-between mt-6">
          <va-button @click="handleLogin" color="primary" class="flex-grow mr-2">
            {{ $t('login') }}
          </va-button>

          <va-button :to="{ name: 'register' }" color="secondary" class="flex-grow ml-2">
            {{ $t('register') }}
          </va-button>
        </div>
        
      </va-card-content>
    </va-card>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

export default {
  name: 'Login',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();

    return { authStore, router };
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
        await this.authStore.login(this.form);
        this.router.push('/dashboard');
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка входа';
      }
    },
  },
};
</script>

<style scoped>
.auth-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
  padding: 20px;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  padding: 20px;
  border-radius: 20px;
  box-shadow: var(--va-shadow);
  background-color: var(--va-background-element);
}

.text-danger {
  color: red;
}
</style>