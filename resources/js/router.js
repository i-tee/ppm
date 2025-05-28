import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';

const routes = [
    { path: '/', component: Home },
    { path: '/login', component: { template: '<div>Страница логина (пока заглушка)</div>' } },
    { path: '/register', component: { template: '<div>Страница регистрации (пока заглушка)</div>' } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;