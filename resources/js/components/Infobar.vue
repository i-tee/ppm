<!-- resources/js/components/Infobar.vue -->
<template>
    <div class="h-screen bg-gradient-to-br from-blue-500 to-blue-900 text-white flex items-center justify-center p-6">
        <div class="text-center space-y-10 max-w-lg">
            <!-- Логотип -->
            <!-- Чтобы управлять иконками:
            - Используйте va-icon с правильным именем иконки.
            - Можете динамически задавать имя иконки через :name.
            - Стилизуйте иконки с помощью size, color и class. -->
            <div>
                <p>{{ t('infobar.ooo') }}</p>
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
                <p>
                    <a :href="`https://${t('infobar.main_contacts')}`" target="_blank" rel="noopener"
                        class="underline hover:text-white">
                        {{ t('infobar.ooo') }}
                    </a>
                    <span> </span>
                    <span>©</span>
                    <span> </span>
                    <span>{{ new Date().getFullYear() }}</span>
                    <span> </span>
                    <span class="mx-1">|</span>
                    <span> </span>
                    <span>{{ t('infobar.brand_1') }}</span>
                </p>
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