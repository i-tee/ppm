import '@fontsource/inter/400.css'; // Regular weight
import '@fontsource/inter/700.css'; // Bold weight
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createVuestic } from 'vuestic-ui';
import i18n from './i18n';

import 'vuestic-ui/styles/grid.css';
import 'material-design-icons-iconfont/dist/material-design-icons.css';

const app = createApp(App);

app.use(router);

// Конфигурация Vuestic с явным указанием шрифта
app.use(createVuestic({
  config: {
    i18n: (key, ...args) => i18n.global.t(`vuestic.${key}`, ...args),
    components: {
      VaConfig: {
        fonts: {
          fontFamily: 'Inter, sans-serif', // Важно указать fallback
        }
      }
    }
  }
}));

app.use(i18n);
app.mount('#app');