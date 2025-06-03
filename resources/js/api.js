import axios from 'axios';

const api = axios.create({
    baseURL: 'https://partner.avicenna.com.ru/api',
    headers: {
        'Content-Type': 'application/json',
    },
});

export default api;