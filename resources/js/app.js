import '@fontsource/inter/400.css'; // Шрифт Inter, вес 400
import '@fontsource/inter/700.css'; // Шрифт Inter, вес 700
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { createVuestic } from 'vuestic-ui';
import i18n from './i18n';
import '../css/app.css'; // Подключаем объединенные стили Tailwind и Vuestic
import 'material-design-icons-iconfont/dist/material-design-icons.css'; // Иконки Material Design

const app = createApp(App);
const pinia = createPinia();

app.use(router);
app.use(pinia);

// Конфигурация Vuestic с явным указанием шрифта
app.use(createVuestic({
  config: {
    i18n: (key, ...args) => i18n.global.t(`vuestic.${key}`, ...args), // Поддержка i18n для Vuestic
    components: {
      VaConfig: {
        fonts: {
          fontFamily: 'Inter, sans-serif', // Шрифт Inter с fallback
        }
      }
    }
  }
}));

app.use(i18n);
app.mount('#app');