<template>

  <VaLayout :top="{ fixed: true, order: 2 }"
    :left="{ fixed: true, absolute: breakpoints.smDown, order: 1, overlay: breakpoints.smDown && isSidebarVisible }"
    @left-overlay-click="isSidebarVisible = false">

    <template #top>
      <VaNavbar shadowed>
        <template #left>
          <VaButton preset="secondary" :icon="isSidebarVisible ? 'menu_open' : 'menu'"
            @click="isSidebarVisible = !isSidebarVisible" />
        </template>
        <template #center>
          <router-link to="/dashboard" class="flex items-center cursor-pointer">
            <Logo class="max-h-6" />
          </router-link>
        </template>
        <template #right>
          <User v-if="!isLoading" :user="currentUser" />
        </template>
      </VaNavbar>
    </template>

    <template #left>
      <VaSidebar v-model="isSidebarVisible">
        <Sidebar :user="currentUser" />
      </VaSidebar>
    </template>

    <template #content>
      <div class="p-4 dashboard-layout" style="min-height: unset; height: 100%;">
        <main style="min-height: unset;">
          <router-view v-slot="{ Component }">
            <component :is="Component" v-if="!isLoading" :user="currentUser" />
          </router-view>
        </main>
      </div>
    </template>

  </VaLayout>
</template>

<script>
import { defineComponent, onMounted, computed, ref, watchEffect } from 'vue';
import { useBreakpoint } from 'vuestic-ui';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import Sidebar from './dashboard/Sidebar.vue';
import User from './parts/User.vue';
import Logo from './parts/Logo.vue';

export default defineComponent({
  name: 'Dashboard',
  components: {
    Sidebar,
    User,
    Logo,
  },
  setup() {
    const breakpoints = useBreakpoint();
    const isSidebarVisible = ref(breakpoints.smUp);

    watchEffect(() => {
      isSidebarVisible.value = breakpoints.smUp;
    });

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
      isSidebarVisible,
      breakpoints,
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


<!-- https://ui.vuestic.dev/ui-elements/layout -->