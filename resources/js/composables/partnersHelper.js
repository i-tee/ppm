import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vuestic-ui';

export function usePartnersHelper() {
  const authStore = useAuthStore();
  const toast = useToast();
  const partnerSettings = ref(null);
  const error = ref(null);

  async function fetchPartnerSettings() {
    try {
      const response = await axios.get('/api/ps', {
        headers: { Authorization: `Bearer ${authStore.token}` },
      });
      partnerSettings.value = response.data;
    } catch (err) {
      error.value = err.response ? err.response.data : err.message;
      toast.init({ message: 'Ошибка загрузки настроек партнеров', color: 'danger' });
    }
  }

  onMounted(() => {
    fetchPartnerSettings();
  });

  return {
    partnerSettings,
    error,
    fetchPartnerSettings, // Для принудительного обновления, если нужно
  };
}