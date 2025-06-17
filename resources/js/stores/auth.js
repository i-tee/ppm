// resources/js/stores/auth.js
import { defineStore } from 'pinia';
import api from '@/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
    loading: false,
    error: null,
  }),

  actions: {
    async login(credentials) {
      if (this.loading) return;
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/login', credentials);
        const { user, token } = response.data;
        this.user = user;
        this.token = token;
        localStorage.setItem('auth_token', token);
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка авторизации';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser() {
      if (!this.token) {
        this.user = null;
        return;
      }
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/user');
        this.user = response.data;
      } catch (error) {
        this.user = null;
        this.token = null;
        localStorage.removeItem('auth_token');
      } finally {
        this.loading = false;
      }
    },

    async updateUser(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put('/user', data);
        this.user = response.data;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка обновления данных';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async logout(router) { // Принимаем router как параметр
      this.loading = true;
      this.error = null;
      try {
        await api.post('/logout');
        this.user = null;
        this.token = null;
        localStorage.removeItem('auth_token');
        if (router) {
          router.push('/login'); // Редирект через переданный router
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при выходе';
      } finally {
        this.loading = false;
      }
    },

    async requestPasswordReset(email) {
      this.loading = true;
      try {
        const response = await api.post('/forgot-password', { email });
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async resetPassword(token, newPassword, email, passwordConfirmation) {
      this.loading = true;
      try {
        console.log('Sending reset request with:', { token, email, password: newPassword, password_confirmation: passwordConfirmation });
        const response = await api.post('/reset-password', {
          token: token,
          email: email,
          password: newPassword,
          password_confirmation: passwordConfirmation, // Добавляем подтверждение
        });
        const { user, token: newToken } = response.data;
        this.user = user;
        this.token = newToken;
        localStorage.setItem('auth_token', newToken);
        return true;
      } catch (error) {
        console.log('Reset error on server:', error.response?.data || error.message);
        throw error;
      } finally {
        this.loading = false;
      }
    },

  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
  },
});