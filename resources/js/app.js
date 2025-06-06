import '@fontsource/inter/400.css';
import '@fontsource/inter/700.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { createVuestic } from 'vuestic-ui';
import i18n from './i18n';
import '../css/app.css';
import 'material-design-icons-iconfont/dist/material-design-icons.css';

const app = createApp(App);
const pinia = createPinia();

app.use(router);
app.use(pinia);

app.use(createVuestic({
  config: {
    i18n: (key, ...args) => i18n.global.t(`vuestic.${key}`, ...args),
    components: {
      VaConfig: {
        fonts: {
          fontFamily: 'Inter, sans-serif',
        }
      }
    }
  }
}));

app.use(i18n);
app.mount('#app');