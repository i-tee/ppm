<template>
    <div>
        <h1>{{ $t('dashboard.dev') }}</h1>
        <p>{{ $t('dashboard.dev') }}</p>
        <hr>
        <pre>
            {{ apiData }}
        </pre>
    </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'Dev',
    setup() {
        
        const apiData = ref(null);
        const error = ref(null);
        const authStore = useAuthStore();

        onMounted(async () => {
            try {
                const response = await axios.get('/api/ps', {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });
                apiData.value = response.data;
                console.log('API response:', response.data);
            } catch (err) {
                error.value = err.response ? err.response.data : err.message;
                console.error('API error:', error.value);
            }
        });

        return {
            apiData,
            error,
        };
    }
};
</script>
