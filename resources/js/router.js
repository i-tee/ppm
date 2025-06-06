import { createRouter, createWebHistory } from 'vue-router';
import Welcome from './components/Welcome.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Overview from './components/dashboard/Overview.vue';
import Promocodes from './components/dashboard/Promocodes.vue';
import ReferralLinks from './components/dashboard/ReferralLinks.vue';
import UserProfile from './components/dashboard/UserProfile.vue';
import { useAuthStore } from './stores/auth';

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
      {
        path: 'profile',
        name: 'UserProfile',
        component: UserProfile,
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

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  if (!authStore.isAuthenticated && to.meta.requiresAuth) {
    // Если пользователь не авторизован и маршрут требует авторизации
    await authStore.fetchUser().catch(() => {}); // Ловим ошибку, чтобы не попадала в консоль
  }

  if (authStore.isAuthenticated) {
    if (to.name === 'welcome' || to.name === 'register') {
      return next({ name: 'dashboard' });
    }
    return next();
  }

  if (to.name !== 'welcome' && to.name !== 'register') {
    return next({ name: 'welcome' });
  }

  return next();
});

export default router;