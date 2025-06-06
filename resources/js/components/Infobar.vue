<!-- resources/js/components/Infobar.vue -->
<template>
    <div
        class="h-screen bg-gradient-to-br from-indigo-900 to-purple-900 text-white flex items-center justify-center p-6">
        <div class="text-center space-y-10 max-w-lg">
            <!-- Плейсхолдер для логотипа или изображения -->
            <div class="w-48 h-48 bg-gray-300 rounded-full flex items-center justify-center mx-auto">
                <span class="text-gray-600 font-inter">Логотип</span>
            </div>
            <!-- Заголовок -->
            <h1 class="text-4xl md:text-5xl font-bold font-inter">
                {{ t('infobar.title') }}
            </h1>
            <!-- Описание -->
            <p class="text-lg md:text-xl font-inter text-gray-200 max-w-md mx-auto">
                {{ t('infobar.description') }}
            </p>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';

export default {
    name: 'Infobar',
    setup() {
        const { t } = useI18n();
        return { t };
    },
};
</script>