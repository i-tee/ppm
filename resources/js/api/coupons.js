// resources/js/api/coupons.js

import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

// Функция для получения списка промокодов пользователя
export async function getUserCoupons() {
  const authStore = useAuthStore();

  try {
    const response = await axios.get('/api/user/coupons', {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Accept': 'application/json',
      },
    });

    return {
      success: true,
      coupons: response.data.coupons || [],
      count: response.data.count || 0,
    };
  } catch (error) {
    return {
      success: false,
      error: error.response?.data?.message || error.message,
    };
  }
}

// Функция для получения списка заказов с использованием промокодов пользователя
export async function getUserOrders() {
  const authStore = useAuthStore();

  try {
    const response = await axios.get('/api/user/orders', {
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        'Accept': 'application/json',
      },
    });

    return {
      success: true,
      orders: response.data.orders || [],
      count: response.data.count || 0,
    };
  } catch (error) {
    return {
      success: false,
      error: error.response?.data?.message || error.message,
    };
  }
}

