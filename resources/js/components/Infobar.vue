<!-- resources/js/components/Infobar.vue -->
<template>
    <div
        class="h-screen bg-gradient-to-br from-blue-500 to-blue-900 text-white flex items-center justify-center p-6">
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
            <!-- Сноска о разработчике -->
            <div class="mt-8 text-xs text-gray-200 font-inter opacity-70">
                {{ t('infobar.developer') }}:
                <a href="https://tee.su" target="_blank" rel="noopener" class="underline hover:text-white">
                    {{ t('infobar.author') }}
                </a>
                <span class="mx-1">|</span>
                {{ t('infobar.project') }}:
                <a href="https://github.com/i-tee/base-vue-app" target="_blank" rel="noopener" class="underline hover:text-white">
                    {{ t('infobar.project_name') }}
                </a>
            </div>
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