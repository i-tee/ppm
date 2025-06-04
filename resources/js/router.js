// Импортируем необходимые функции и компоненты из Vue Router и нашего приложения
import { createRouter, createWebHistory } from 'vue-router';
import Welcome from './components/Welcome.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Overview from './components/dashboard/Overview.vue';
import Promocodes from './components/dashboard/Promocodes.vue';
import ReferralLinks from './components/dashboard/ReferralLinks.vue';
import { useAuthStore } from './stores/auth';

// Описываем маршруты приложения
const routes = [
  {
    path: '/',
    name: 'welcome',
    component: Welcome,
    meta: { requiresAuth: false },
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
    meta: { requiresAuth: false },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Overview',
        component: Overview,
      },
      {
        path: 'promocodes',
        name: 'Promocodes',
        component: Promocodes,
      },
      {
        path: 'referral-links',
        name: 'ReferralLinks',
        component: ReferralLinks,
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: { template: '<div>404 - Страница не найдена</div>' },
    meta: { requiresAuth: false },
  },
];

// Создаём экземпляр роутера с историей браузера и нашими маршрутами
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Глобальный перехватчик навигации для проверки аутентификации и перенаправлений
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Попробуем получить пользователя, если не авторизован
  if (!authStore.isAuthenticated) {
    await authStore.fetchUser().catch(() => {});
  }

  // Если пользователь авторизован
  if (authStore.isAuthenticated) {
    // Если он пытается попасть на welcome или register, перенаправляем на dashboard
    if (to.name === 'welcome' || to.name === 'register') {
      return next({ name: 'dashboard' });
    }
    return next();
  }

  // Если пользователь не авторизован
  // Разрешаем только welcome и register, остальные — на welcome
  if (to.name !== 'welcome' && to.name !== 'register') {
    return next({ name: 'welcome' });
  }

  return next();
});

// Экспортируем роутер для использования в приложении
export default router;