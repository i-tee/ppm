// resources/js/api.js
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

// Интерцептор для обработки ошибок ответа
api.interceptors.response.use(
  (response) => {
    // Успешный ответ (2xx) просто возвращаем
    return response;
  },
  (error) => {
    // Проверяем, есть ли ответ от сервера
    if (error.response) {
      // Если статус 401, просто передаём ошибку дальше без логирования
      if (error.response.status === 401) {
        return Promise.reject(error);
      }
      // Для других ошибок (например, 500) логируем в консоль
      console.error('API Error:', error.response.data || error.message);
    } else {
      // Ошибки без ответа (например, сетевые ошибки)
      console.error('Network Error:', error.message);
    }
    return Promise.reject(error);
  }
);

export default api;