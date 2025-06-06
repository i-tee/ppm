import { defineStore } from 'pinia';
import api from '@/api'; // Используем настроенный Axios из api.js

export const useUserStore = defineStore('user', {
  state: () => ({
    user: {
      name: '',
      email: '',
    },
    loading: false,
    error: null,
  }),
  actions: {
    // Загрузка данных пользователя
    async fetchUser() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/user');
        this.user = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка загрузки данных';
      } finally {
        this.loading = false;
      }
    },
    // Обновление данных пользователя
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
    // Выход из системы
    async logout() {
      try {
        await api.post('/logout');
        this.user = { name: '', email: '' };
        window.location.href = '/login'; // Редирект на страницу логина
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка при выходе';
      }
    },
  },
});