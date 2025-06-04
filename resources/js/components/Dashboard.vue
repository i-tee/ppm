<template>
  <div class="dashboard-layout">
    <!-- Боковое меню -->
    <Sidebar />

    <!-- Основной контент -->
    <div class="dashboard-content">
      <!-- Шапка -->
      <Header v-if="!isLoading" :user="currentUser" />
      <div v-else class="text-secondary">Загрузка...</div>

      <router-view />
    </div>
  </div>
</template>

<script>
import { defineComponent, onMounted, computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import Sidebar from './dashboard/Sidebar.vue';
import Header from './dashboard/Header.vue';

export default defineComponent({
  name: 'Dashboard',
  components: {
    Sidebar,
    Header,
  },
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const isLoading = ref(true);

    const currentUser = computed(() => authStore.currentUser);

    onMounted(async () => {
      if (authStore.isAuthenticated && !authStore.currentUser) {
        await authStore.fetchUser();
      }
      if (!authStore.isAuthenticated) {
        router.push('/login');
      }
      isLoading.value = false;
    });

    return {
      currentUser,
      isLoading,
    };
  },
});
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
}

.dashboard-content {
  flex: 1;
  padding: 20px;
  background-color: #fff;
}

.text-secondary {
  color: #888;
}
</style>