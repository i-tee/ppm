import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createVuestic } from 'vuestic-ui';
import 'vuestic-ui/styles/essential.css'; // Основные стили Vuestic
import 'vuestic-ui/styles/grid.css'; // Сетка (опционально)

const app = createApp(App);
app.use(router);
app.use(createVuestic());
app.mount('#app');