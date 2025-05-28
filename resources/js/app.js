import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createVuestic } from 'vuestic-ui';
import i18n from './i18n'; // Подключаем i18n
import 'vuestic-ui/styles/essential.css';
import 'vuestic-ui/styles/grid.css';

const app = createApp(App);
app.use(router);
app.use(createVuestic({
    config: {
        i18n: (key, ...args) => i18n.global.t(`vuestic.${key}`, ...args), // Передаём переводы Vuestic
    },
}));
app.use(i18n); // Подключаем i18n
app.mount('#app');