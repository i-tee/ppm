import { createI18n } from 'vue-i18n';
import ru from './locales/ru.json';
import en from './locales/en.json';

const messages = {
    ru,
    en,
};

const i18n = createI18n({
    legacy: false, // Используем Composition API для Vue 3
    locale: 'ru', // Язык по умолчанию
    fallbackLocale: 'en', // Резервный язык
    messages,
});

export default i18n;