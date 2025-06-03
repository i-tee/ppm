import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';
import Register from './components/Register.vue'; // Импортируем Register.vue

const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/login', component: { template: '<div>Страница логина (пока заглушка)</div>' }, name: 'login' },
    { path: '/register', component: Register, name: 'register' }, // Используем Register.vue
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;