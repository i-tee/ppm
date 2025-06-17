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
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/login', credentials);
        if (response.status === 401) {
          this.error = response.data?.message || 'Ошибка авторизации';
          throw new Error('Unauthorized');
        }
        const { user, token } = response.data;
        this.user = user;
        this.token = token;
        localStorage.setItem('auth_token', token);
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка авторизации';
        this.loading = false;
        throw error; // Передаем ошибку дальше для Welcome.vue
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

    async logout() {
      this.loading = true;
      this.error = null;
      try {
        await api.post('/logout');
        this.user = null;
        this.token = null;
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при выходе';
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