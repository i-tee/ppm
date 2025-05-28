// Импортируем функцию для конфигурации Vite
import { defineConfig } from 'vite';
// Плагин для интеграции Vite с Laravel
import laravel from 'laravel-vite-plugin';
// Плагин для Tailwind CSS
import tailwindcss from '@tailwindcss/vite';
// Плагин для поддержки Vue файлов
import vue from '@vitejs/plugin-vue';
// Импортируем модули для работы с файлами и путями
import fs from 'fs';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: {
                paths: [
                    'resources/views/**',
                    'resources/js/**',
                    'resources/css/**',
                    'app/**',
                    'routes/**',
                ],
                exclude: ['vendor/**'],
            },
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            protocol: 'wss',
            host: 'partner.avicenna.com.ru',
            port: 5173,
        },
        https: {
            key: fs.readFileSync(path.resolve('/home/dev-user/conf/web/partner.avicenna.com.ru/ssl/partner.avicenna.com.ru.key')),
            cert: fs.readFileSync(path.resolve('/home/dev-user/conf/web/partner.avicenna.com.ru/ssl/partner.avicenna.com.ru.pem')),
        },
        cors: {
            origin: '*',
            methods: 'GET,HEAD,PUT,PATCH,POST,DELETE',
            credentials: true,
        },
    },
});