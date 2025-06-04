import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';
import Register from './components/Register.vue';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import { useAuthStore } from './stores/auth';

const routes = [
  { path: '/', component: Home, name: 'home' },
  { path: '/register', component: Register, name: 'register' },
  { path: '/login', component: Login, name: 'login' },
  { path: '/dashboard', component: Dashboard, name: 'dashboard', meta: { requiresAuth: true } },
  { path: '/:pathMatch(.*)*', component: { template: '<div>404 - Страница не найдена</div>' }, name: 'not-found' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Проверяем только для защищённых маршрутов
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    await authStore.fetchUser().catch(() => {}); // Игнорируем ошибку 401
    if (!authStore.isAuthenticated) {
      next({ name: 'login' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;