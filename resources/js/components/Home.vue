<template>
    <div class="home">
        <va-card class="mb-4">
            <va-card-title>Добро пожаловать в партнерский кабинет Авиценна</va-card-title>
            <va-card-content>
                <p>Это ваш личный кабинет для управления партнерской программой.</p>
                <va-button to="/login" color="primary">Войти</va-button>
                <va-button to="/register" color="secondary" class="ml-2">Зарегистрироваться</va-button>
            </va-card-content>
        </va-card>
    </div>
</template>

<script>
export default {
    name: 'Home',
};
</script>

<style scoped>
.home {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
}
</style>