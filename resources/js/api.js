import axios from 'axios';
import { useAuthStore } from '@/stores/auth'; // Импортируем store для доступа к токену

const api = axios.create({
  baseURL: 'https://partner.avicenna.com.ru/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Интерцептор для добавления токена в заголовки
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

export default api;