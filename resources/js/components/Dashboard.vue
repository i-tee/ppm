<template>

  <VaLayout :top="{ fixed: true, order: 2 }"
    :left="{ fixed: true, absolute: breakpoints.smDown, order: 1, overlay: breakpoints.smDown && isSidebarVisible }"
    @left-overlay-click="isSidebarVisible = false">

    <template #top>
      <div v-if="authStore.isImpersonating" class="impersonation-banner">
        <span>{{ $t('admin.impersonation.bannerText', { user: currentUser?.name }) }}</span>
        <VaButton size="small" preset="primary" @click="authStore.stopImpersonation()">
          {{ $t('admin.impersonation.returnToAdmin') }}
        </VaButton>
      </div>
      <VaNavbar shadowed>
        <template #left>
          <VaButton preset="secondary" :icon="isSidebarVisible ? 'menu_open' : 'menu'"
            @click="isSidebarVisible = !isSidebarVisible" />
        </template>
        <template #center>
          <a href="/dashboard" class="flex items-center cursor-pointer">
            <Logo class="max-h-6" />
          </a>
        </template>
        <template #right>
          <User v-if="!isLoading" :user="currentUser" />
        </template>
      </VaNavbar>
    </template>

    <template #left>
      <VaSidebar v-model="isSidebarVisible" :isSidebarVisible="isSidebarVisible">
        <Sidebar :user="currentUser" @close="breakpoints.smDown && (isSidebarVisible = false)" />
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

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, watchEffect } from 'vue'
import { useBreakpoint } from 'vuestic-ui'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'

import Sidebar from './dashboard/Sidebar.vue'
import User from './parts/User.vue'
import Logo from './parts/Logo.vue'

// Breakpoints
const breakpoints = useBreakpoint()

// Sidebar visibility
const isSidebarVisible = ref(breakpoints.smUp)
watchEffect(() => {
  isSidebarVisible.value = breakpoints.smUp
})

// Auth + Router
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const isLoading = ref(true)
const currentUser = computed(() => authStore.currentUser)

onMounted(() => {
  // Профиль на этот момент уже загружен guard'ом роутера (router.js) -
  // повторный fetchUser здесь не нужен, это и был двойной запрос.
  if (!authStore.isAuthenticated) {
    router.push({ name: 'welcome' })
  }

  isLoading.value = false
})

// Свежий профиль: тихо (без спиннера, throttle 30с - см. authStore.refreshUser)
// перезапрашиваем /api/user при возврате на вкладку, фокусе окна и переходах
// между экранами дашборда - иначе статус заявки/доступа виден устаревшим,
// пока не будет жёсткой перезагрузки страницы.
const handleVisibilityRefresh = () => {
  if (document.visibilityState === 'visible') {
    authStore.refreshUser()
  }
}

onMounted(() => {
  document.addEventListener('visibilitychange', handleVisibilityRefresh)
  window.addEventListener('focus', handleVisibilityRefresh)
})

onUnmounted(() => {
  document.removeEventListener('visibilitychange', handleVisibilityRefresh)
  window.removeEventListener('focus', handleVisibilityRefresh)
})

watch(() => route.path, () => {
  authStore.refreshUser()
})
</script>

<style scoped>
.dashboard-layout {
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

.impersonation-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 8px 16px;
  background-color: var(--va-warning);
  color: #fff;
  font-weight: 500;
  text-align: center;
}
</style>


<!-- https://ui.vuestic.dev/ui-elements/layout -->