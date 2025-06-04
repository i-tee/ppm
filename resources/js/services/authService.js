import api from '../api/axios';

export default {
  async login(credentials) {
    const response = await api.post('/login', credentials);
    return response.data;
  },

  async logout() {
    const response = await api.post('/logout');
    return response.data;
  },

  async getUser() {
    const response = await api.get('/user');
    return response.data;
  },
};