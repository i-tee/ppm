<template>
    <div class="home">
        <va-card class="mb-4">
            <va-card-title>{{ $t('welcome') }}</va-card-title>
            <va-card-content>
                <p>{{ $t('description') }}</p>
                <div class="language-switcher mb-4">
                    <va-select
                        v-model="selectedLanguage"
                        :label="$t('language')"
                        :options="languages"
                        @update:modelValue="changeLanguage"
                    />
                </div>
                <va-button :to="{ name: 'login' }" color="primary">{{ $t('login') }}</va-button>
                <va-button :to="{ name: 'register' }" color="secondary" class="ml-2">{{ $t('register') }}</va-button>
            </va-card-content>
        </va-card>
    </div>
</template>

<script>
export default {
    name: 'Home',
    data() {
        return {
            selectedLanguage: { text: 'Русский', value: this.$i18n.locale || 'ru' }, // Инициализируем как объект
            languages: [
                { text: 'Русский', value: 'ru' },
                { text: 'English', value: 'en' },
            ],
        };
    },
    methods: {
        changeLanguage(newLanguage) {
            this.selectedLanguage = newLanguage; // Обновляем объект
            this.$i18n.locale = newLanguage.value; // Устанавливаем локаль
            localStorage.setItem('locale', newLanguage.value); // Сохраняем значение
        },
    },
};
</script>

<style scoped>
.home {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
}
.language-switcher {
    max-width: 200px;
}
</style>