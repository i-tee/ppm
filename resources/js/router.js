import { createRouter, createWebHistory } from 'vue-router';
import Welcome from './components/Welcome.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Overview from './components/dashboard/Overview.vue';
import Promocodes from './components/dashboard/Promocodes.vue';
import ReferralLinks from './components/dashboard/ReferralLinks.vue';
import UserProfile from './components/dashboard/UserProfile.vue';
import ResetPassword from './components/ResetPassword.vue';
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
    path: '/reset-password',
    name: 'reset-password',
    component: ResetPassword,
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

  if (to.meta.requiresAuth && !authStore.token) {
    return next({ name: 'welcome' });
  }

  if (authStore.isAuthenticated) {
    if (to.name === 'welcome' || to.name === 'register') {
      return next({ name: 'dashboard' });
    }
    if (to.name === 'reset-password' && !to.query.token) {
      return next({ name: 'not-found' });
    }
    try {
      if (!authStore.user) {
        await authStore.fetchUser();
      }
    } catch (error) {
      authStore.logout();
      return next({ name: 'welcome' });
    }
    return next();
  }

  if (to.name !== 'welcome' && to.name !== 'register' && to.name !== 'reset-password') {
    return next({ name: 'welcome' });
  }

  return next();
});

export default router;