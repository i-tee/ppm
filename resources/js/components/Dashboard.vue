<template>
  <div class="dashboard-layout">
    <!-- Боковое меню -->
    <Sidebar />

    <!-- Основной контент -->
    <div class="dashboard-content">

      <!-- Шапка -->
      <Header />

      <router-view />
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { defineComponent } from 'vue';
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
    return { user: authStore.user };
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