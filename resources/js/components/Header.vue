<template>
  <header class="header">
    <nav class="nav">
      <router-link to="/" class="nav-link">{{ $t('home') }}</router-link>
      <router-link v-if="!isAuthenticated" to="/login" class="nav-link">{{ $t('login') }}</router-link>
      <router-link v-if="!isAuthenticated" to="/register" class="nav-link">{{ $t('register') }}</router-link>
      <router-link v-if="isAuthenticated" to="/dashboard" class="nav-link">{{ $t('dashboard') }}</router-link>
      <va-button v-if="isAuthenticated" @click="handleLogout" color="danger" size="small">
        {{ $t('logout') }}
      </va-button>
    </nav>
  </header>
</template>

<script>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

export default {
  name: 'Header',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();

    return { authStore, router };
  },

  computed: {
    isAuthenticated() {
      return this.authStore.isAuthenticated;
    },
  },

  methods: {
    async handleLogout() {
      await this.authStore.logout();
      this.router.push('/login');
    },
  },
};
</script>

<style scoped>
.header {
  background-color: var(--va-background-element);
  padding: 1rem;
  box-shadow: var(--va-shadow);
}

.nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.nav-link {
  color: var(--va-primary);
  text-decoration: none;
  font-weight: 500;
}

.nav-link:hover {
  text-decoration: underline;
}
</style>