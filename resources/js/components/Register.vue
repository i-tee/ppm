<template>
  <div class="auth-wrapper">
    <va-card class="auth-card" outlined>
      <va-card-title class="justify-center text-center text-primary text-2xl">
        <VaIcon name="person_add" class="mr-2" /> {{ $t('register') }}
      </va-card-title>

      <va-divider />

      <va-card-content>
        <p class="text-center text-secondary mb-4">{{ $t('register_description') }}</p>

        <va-input
          v-model="form.name"
          type="text"
          :label="$t('name')"
          placeholder="John Doe"
          class="mb-4"
          prepend-inner-icon="person"
        />

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

        <va-input
          v-model="form.password_confirmation"
          :type="isPasswordVisible ? 'text' : 'password'"
          :label="$t('confirm_password')"
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

        <div v-if="success" class="text-center text-success mb-4">
          {{ success }}
        </div>

        <div class="flex justify-between mt-6">
          <va-button @click="register" color="primary" class="flex-grow mr-2">
            {{ $t('register') }}
          </va-button>

          <va-button :to="{ name: 'login' }" color="secondary" class="flex-grow ml-2">
            {{ $t('login') }}
          </va-button>
        </div>
      </va-card-content>
    </va-card>
  </div>
</template>

<script>
import api from '../api'; // Импортируем настроенный axios

export default {
  name: 'Register',
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
      success: null,
    };
  },
  methods: {
    togglePassword() {
      this.isPasswordVisible = !this.isPasswordVisible;
    },
    async register() {
      try {
        this.error = null;
        this.success = null;
        const response = await api.post('/register', this.form);
        this.success = 'Registration successful! Please log in.';
        this.form = { name: '', email: '', password: '', password_confirmation: '' };
        setTimeout(() => {
          this.$router.push({ name: 'login' });
        }, 2000);
      } catch (error) {
        this.error = error.response?.data?.message || 'Registration failed';
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

.text-success {
  color: green;
}
</style>