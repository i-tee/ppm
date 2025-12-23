// resources/js/api.js
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

let isRefreshing = false;

api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    if (authStore.token || sessionStorage.getItem('social_token')) {
      config.headers.Authorization = `Bearer ${authStore.token || sessionStorage.getItem('social_token')}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response) {
      if (error.response.status === 401 && !isRefreshing) {
        const authStore = useAuthStore();
        isRefreshing = true;
        try {
          authStore.user = null;
          authStore.token = null;
          localStorage.removeItem('auth_token');
          sessionStorage.removeItem('social_token');
        } catch (logoutError) {
          console.error('Error clearing auth state:', logoutError);
        } finally {
          isRefreshing = false;
        }
      }
      throw error.response.data || new Error('Server error');
    } else {
      throw new Error('Network error');
    }
  }
);

export default api;