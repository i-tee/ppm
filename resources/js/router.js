import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';

const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/login', component: { template: '<div>Страница логина (пока заглушка)</div>' }, name: 'login' },
    { path: '/register', component: { template: '<div>Страница регистрации (пока заглушка)</div>' }, name: 'register' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;