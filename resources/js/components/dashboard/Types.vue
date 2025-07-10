<template>
    <p class="va-h4 my-4">{{ $t('partners.cooperation_types.cooperation_types') }}</p>
    <div>
        <div v-if="apiData">
            <div>
                <div v-if="apiData.cooperation_types && apiData.cooperation_types.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="type in apiData.cooperation_types" :key="'coop-' + type.id" class="mb-4">
                        <VaCard class="p-4 max-w-md h-full">
                            <div class="va-card-title mb-2 va-h6">
                                {{ $t('partners.cooperation_types.' + type.name + '.title') }}
                            </div>
                            <div class="va-card-content">
                                <p>{{ $t('partners.cooperation_types.' + type.name + '.' + type.description) }}</p>
                            </div>
                            <div class="va-card-actions mt-4 flex justify-between">
                                <VaButton :disabled="!type.active" color="primary" class="w-40">
                                    {{ $t('partners.apply') }}
                                </VaButton>
                                <span class="p-2 flex-inline">{{ $t('partners.cooperation_types.' + type.name + '.status') }}</span>
                            </div>
                        </VaCard>
                    </div>
                </div>
                <div v-else>
                    <VaAlert color="info">{{ $t('dashboard.no_cooperation_types') }}</VaAlert>
                </div>
            </div>
        </div>
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
            } catch (err) {
                error.value = err.response ? err.response.data : err.message;
            }
        });

        return {
            apiData,
            error,
        };
    }
};
</script>
