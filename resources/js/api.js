// resources/js/api.js
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const api = axios.create({
  baseURL: 'https://partner.avicenna.com.ru/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

let isRefreshing = false;

api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response) {
      // console.error('API Error Details:', {
      //   status: error.response.status,
      //   data: error.response.data,
      //   headers: error.response.headers,
      // });

      if (error.response.status === 401 && !isRefreshing) {
        const authStore = useAuthStore();
        isRefreshing = true;
        try {
          // Не вызываем logout с редиректом, а просто очищаем состояние
          authStore.user = null;
          authStore.token = null;
          localStorage.removeItem('auth_token');
        } catch (logoutError) {
          //console.error('Error clearing auth state:', logoutError);
        } finally {
          isRefreshing = false;
        }
      }

      throw error.response.data || new Error('Server error');
    } else {
      //console.error('Network Error:', error.message);
      throw new Error('Network error');
    }
  }
);

export default api;