<!-- resources/js/components/Welcome.vue -->
<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Форма входа -->
    <div class="w-full md:w-1/2 bg-gray-50 flex justify-center items-center p-6">
      <div class="w-full max-w-md">
        <!-- Логотип -->
        <Logo />
        <br>
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2 text-center">
          {{ $t('login') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-8">
          {{ $t('login_description') }}
        </p>

        <!-- Форма -->
        <div class="space-y-6">

          <VaInput v-model="form.email" type="email" :label="$t('email')" placeholder="hello@epicmax.co"
            class="w-full font-inter" prepend-inner-icon="email" :rules="[validateEmail]"
            :error="formErrors.email.length > 0" :error-messages="formErrors.email" />
          <VaInput v-model="form.password" :type="isPasswordVisible ? 'text' : 'password'" :label="$t('password')"
            placeholder="*********" class="w-full font-inter" prepend-inner-icon="lock"
            @click-append-inner="togglePassword" :rules="[validatePassword]" :error="formErrors.password.length > 0"
            :error-messages="formErrors.password">
            <template #appendInner>
              <VaIcon :name="isPasswordVisible ? 'visibility_off' : 'visibility'" size="small" color="primary" />
            </template>
          </VaInput>

          <!-- Сообщение об ошибке от сервера -->
          <div v-if="serverError" class="text-center text-red-600 font-inter">
            {{ serverError }}
          </div>

          <!-- Кнопка входа -->
          <VaButton @click="handleLogin" color="primary"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition"
            :loading="authStore.loading" :disabled="!isFormValid || authStore.loading">
            {{ $t('login') }}
          </VaButton>

          <!-- Разделитель -->
          <VaDivider orientation="center" class="my-5">
            <span class="px-2">{{ $t('or_continue_with') }}</span>
          </VaDivider>

          <!-- Кнопки социальной авторизации -->
          <div class="grid grid-cols-2 gap-3">
            
            <!-- Кнопка Google -->
            <a href="/auth/google/redirect"
              class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md font-inter font-medium">
              <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4"
                  d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853"
                  d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05"
                  d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335"
                  d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
              </svg>
              <span>{{ $t('google') }}</span>
            </a>

            <!-- Кнопка Яндекс -->
            <a href="/auth/yandex/redirect"
              class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md font-inter font-medium">
              <img src="https://upload.wikimedia.org/wikipedia/commons/5/58/Yandex_icon.svg" alt="{{ $t('yandex') }}">
              <span>{{ $t('yandex') }}</span>
            </a>
          </div>

          <!-- Навигация между входом и регистрацией -->
          <div class="flex text-center space-x-4 mb-8">
            <router-link to="/register">
              {{ $t('register') }}
            </router-link>
          </div>

          <!-- Ссылка для сброса пароля и попап -->
          <div class="text-center mt-4">
            <a href="#" @click.prevent="showResetModal = true, mailTransport()"
              class="text-gray-300 hover:text-gray-800 font-inter">
              {{ $t('forgot_password') }}
            </a>
          </div>

          <!-- Попап для сброса пароля -->
          <VaModal v-model="showResetModal" :close-button="true" title="Сброс пароля" size="small"
            :hide-default-actions="true">
            <template #default>
              <p>{{ $t('register_reset_description') }}</p>
              <br>
              <VaInput v-model="resetEmail" type="email" :label="$t('email')" :placeholder="$t('enter_your_email')"
                class="w-full font-inter mb-4" prepend-inner-icon="email" :rules="[validateEmail]"
                :error="resetError.length > 0" :error-messages="resetError" />
              <VaButton @click="requestPasswordReset" color="primary"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-2 rounded-lg"
                :loading="authStore.loading" :disabled="!resetEmail || authStore.loading">
                {{ $t('send_reset_instructions') }}
              </VaButton>
            </template>
          </VaModal>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import Infobar from './Infobar.vue'
import Logo from './parts/Logo.vue'
import { useToast } from 'vuestic-ui' // Для уведомлений Vuestic UI

const { init: initToast } = useToast() // Инициализация уведомлений Vuestic UI
const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

// Данные
const form = ref({
  email: '',
  password: ''
})
const formErrors = ref({
  email: [],
  password: []
})
const isPasswordVisible = ref(false)
const serverError = ref(null)
const showResetModal = ref(false)
const resetEmail = ref('')
const resetError = ref('')

// Вычисляемые свойства
const isFormValid = computed(() => {
  const emailValid = form.value.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
  const passwordValid = !!form.value.password
  const formErrorsEmpty = formErrors.value.email.length === 0 && formErrors.value.password.length === 0
  return emailValid && passwordValid && formErrorsEmpty
})

// Watchers
watch(() => form.value.email, (newValue) => {
  resetErrors()
  validateField('email', newValue)
})
watch(() => form.value.password, (newValue) => {
  resetErrors()
  validateField('password', newValue)
})

// <-- НОВЫЙ WATCH: Мониторинг serverError -->
watch(serverError, (newValue, oldValue) => {
  if (newValue && newValue !== oldValue) { // Только если появилась новая ошибка

    initToast({
      message: newValue,
      color: 'warning'
    })

    // setTimeout(() => { serverError.value = null }, 5000)
  }
})

// Методы
const mailTransport = () => {
  resetEmail.value = form.value.email
}

const togglePassword = () => {
  isPasswordVisible.value = !isPasswordVisible.value
}

const resetErrors = () => {
  serverError.value = null
  formErrors.value.email = []
  formErrors.value.password = []
}

const validateField = (field, value) => {
  formErrors.value[field] = []
  if (field === 'email') {
    if (!value) formErrors.value.email.push(t('email_required'))
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) formErrors.value.email.push(t('invalid_email'))
  } else if (field === 'password') {
    if (!value) formErrors.value.password.push(t('password_required'))
  }
}

const validateEmail = (value) => {
  validateField('email', value)
  return formErrors.value.email.length === 0 || formErrors.value.email[0]
}

const validatePassword = (value) => {
  validateField('password', value)
  return formErrors.value.password.length === 0 || formErrors.value.password[0]
}

const handleLogin = async () => {
  resetErrors()

  validateField('email', form.value.email)
  validateField('password', form.value.password)

  if (!isFormValid.value) return

  try {
    await authStore.login(form.value)
    router.push('/dashboard')
  } catch (error) {
    if (error.response?.status === 401) {
      formErrors.value.email = [t('invalid_credentials')]
      formErrors.value.password = [t('invalid_credentials')]
      serverError.value = t('invalid_credentials')
    } else if (error.response?.status === 422) {
      const serverErrors = error.response?.data?.errors || {}
      formErrors.value.email = serverErrors.email || []
      formErrors.value.password = serverErrors.password || []
      serverError.value = error.response?.data?.message || t('login_error')
    } else {
      serverError.value = error.response?.data?.message || t('login_error')
    }
  } finally {
    if (authStore.loading) authStore.loading = false
  }
}

const requestPasswordReset = async () => {
  resetError.value = ''

  if (!resetEmail.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(resetEmail.value)) {
    resetError.value = t('invalid_email')

    initToast({
      message: t('auth_forgotPassword_error'),
      color: 'warning'
    })
    return
  }

  try {
    await authStore.requestPasswordReset(resetEmail.value)
    showResetModal.value = false
    serverError.value = t('auth_forgotPassword_success')

  } catch (error) {
    resetError.value = error.response?.data?.message || t('reset_error')

    initToast({
      message: t('auth_forgotPassword_noAccount'),
      color: 'warning'
    })
  }
}

// Mounted логика
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const token = urlParams.get('token')
  const email = urlParams.get('email')
  if (token && email) {
    authStore.token = token
    authStore.user = { email } // Минимальные данные пользователя
    localStorage.setItem('auth_token', token)
    router.push('/dashboard')
  }
})
</script>