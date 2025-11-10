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

          <!-- Ссылка для авторизации через Google -->
          <div class="text-center mt-4">
            <a href="/auth/google/redirect" class="text-blue-600 hover:text-blue-800 font-inter">Google</a>
            <span> | </span>
            <a href="/auth/yandex/redirect" class="text-blue-600 hover:text-blue-800 font-inter">Яндекс</a>
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