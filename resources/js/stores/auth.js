import { defineStore } from 'pinia';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
  }),

  actions: {
    async login(credentials) {
      const { user, token } = await authService.login(credentials);
      this.user = user;
      this.token = token;
      localStorage.setItem('auth_token', token);
    },

    async logout() {
      await authService.logout();
      this.user = null;
      this.token = null;
      localStorage.removeItem('auth_token');
    },

    async fetchUser() {
      try {
        this.user = await authService.getUser();
      } catch (error) {
        this.user = null;
        this.token = null;
        localStorage.removeItem('auth_token');
      }
    },
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUser: (state) => state.user,
  },
});