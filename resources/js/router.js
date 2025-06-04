// Импортируем необходимые функции и компоненты из Vue Router и нашего приложения
import { createRouter, createWebHistory } from 'vue-router'; // Для создания роутера и истории браузера
import Register from './components/Register.vue'; // Компонент страницы регистрации
import Welcome from './components/Welcome.vue'; // Компонент приветственной страницы
import Dashboard from './components/Dashboard.vue'; // Компонент защищённой страницы "Dashboard"
import { useAuthStore } from './stores/auth'; // Хук для доступа к хранилищу аутентификации

// Описываем маршруты приложения
const routes = [
  { 
    path: '/', // Корневой путь
    component: Welcome, // Привязываем компонент Welcome
    name: 'welcome' // Имя маршрута
  },
  { 
    path: '/register', // Путь для регистрации
    component: Register, // Привязываем компонент Register
    name: 'register' // Имя маршрута
  },
  { 
    path: '/dashboard', // Путь для dashboard
    component: Dashboard, // Привязываем компонент Dashboard
    name: 'dashboard', // Имя маршрута
    meta: { requiresAuth: true } // Маршрут требует аутентификации
  },
  { 
    path: '/:pathMatch(.*)*', // Любой несуществующий путь (404)
    component: { template: '<div>404 - Страница не найдена</div>' }, // Встроенный компонент для 404
    name: 'not-found' // Имя маршрута
  },
];

// Создаём экземпляр роутера с историей браузера и нашими маршрутами
const router = createRouter({
  history: createWebHistory(), // Используем HTML5 history mode
  routes, // Передаём массив маршрутов
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