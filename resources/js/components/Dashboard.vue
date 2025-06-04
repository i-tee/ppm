<template>
  <div class="dashboard-wrapper">
    <va-card class="dashboard-card" outlined>
      <va-card-title class="justify-center text-center text-primary text-2xl">
        <VaIcon name="dashboard" class="mr-2" /> {{ $t('dashboard') }}
      </va-card-title>

      <va-divider />

      <va-card-content>
        <p class="text-center text-secondary mb-4">Добро пожаловать, {{ currentUser?.name }}!</p>
        <div class="flex justify-center mt-6">
          <va-button @click="handleLogout" color="danger">
            {{ $t('logout') }}
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
  name: 'Dashboard',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();

    return { authStore, router };
  },

  computed: {
    currentUser() {
      return this.authStore.currentUser;
    },
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
.dashboard-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
  padding: 20px;
}

.dashboard-card {
  width: 100%;
  max-width: 400px;
  padding: 20px;
  border-radius: 20px;
  box-shadow: var(--va-shadow);
  background-color: var(--va-background-element);
}
</style>