// Импортируем функцию defineConfig из Vite для создания конфигурации
import { defineConfig } from 'vite';
// Импортируем плагин laravel-vite-plugin для интеграции с Laravel
import laravel from 'laravel-vite-plugin';
// Импортируем плагин vue для поддержки Vue 3 компонентов
import vue from '@vitejs/plugin-vue';
// Импортируем модули fs и path для работы с файлами и путями (для HTTPS)
import fs from 'fs';
import path from 'path';
// Импортируем Tailwind CSS и Autoprefixer как ES-модули
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  // Плагины Vite для обработки Laravel и Vue
  plugins: [
    // Плагин для интеграции с Laravel, определяет входные файлы и автообновление
    laravel({
      input: [
        'resources/css/app.css', // Основной CSS-файл (Vuestic UI + Tailwind)
        'resources/js/app.js',   // Основной JS-файл (Vue приложение)
      ],
      refresh: {
        // Папки и файлы для автообновления при изменении
        paths: [
          'resources/views/**', // Blade-шаблоны Laravel
          'resources/js/**',    // Vue и JS файлы, включая компоненты дашборда
          'resources/css/**',   // CSS файлы, включая app.css
          'app/**',             // Laravel файлы (модели, контроллеры)
          'routes/**',          // Маршруты Laravel
        ],
        exclude: ['vendor/**'], // Исключение vendor
      },
    }),
    // Плагин для поддержки Vue 3, обрабатывает .vue файлы
    vue({
      template: {
        transformAssetUrls: {
          base: null,           // Отключение базового пути для активов
          includeAbsolute: false, // Игнорировать абсолютные URL
        },
      },
    }),
  ],
  // Настройки обработки CSS с PostCSS
  css: {
    postcss: {
      // Плагины PostCSS для Tailwind CSS и вендорных префиксов
      plugins: [
        tailwindcss, // Обработка утилит Tailwind CSS (p-4, bg-primary и т.д.)
        autoprefixer, // Добавление вендорных префиксов (-webkit-, -moz-)
      ],
    },
  },
  // Настройки dev-сервера Vite
  server: {
    host: '0.0.0.0', // Слушать все сетевые интерфейсы (для доступа извне)
    port: 5173,      // Порт dev-сервера
    strictPort: true, // Ошибка, если порт занят
    hmr: {
      protocol: 'wss',                     // WebSocket Secure для HMR
      host: 'partner.avicenna.com.ru',     // Домен для HMR
      port: 5173,                          // Порт для HMR
    },
    // HTTPS настройки для безопасного соединения
    https: {
      key: fs.readFileSync(path.resolve('/home/dev-user/conf/web/partner.avicenna.com.ru/ssl/partner.avicenna.com.ru.key')), // SSL-ключ
      cert: fs.readFileSync(path.resolve('/home/dev-user/conf/web/partner.avicenna.com.ru/ssl/partner.avicenna.com.ru.pem')), // SSL-сертификат
    },
    // Настройки CORS
    cors: {
      origin: '*',                          // Разрешить запросы с любых источников
      methods: 'GET,HEAD,PUT,PATCH,POST,DELETE', // Разрешенные HTTP-методы
      credentials: true,                    // Поддержка учетных данных
    },
  },
  // Настройки production-сборки
  build: {
    chunkSizeWarningLimit: 1000, // Лимит размера чанка (1000 кБ)
  },
});