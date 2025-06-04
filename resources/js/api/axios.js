import axios from 'axios';

const api = axios.create({
  baseURL: 'https://partner.avicenna.com.ru/api', // Укажи URL твоего API
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;