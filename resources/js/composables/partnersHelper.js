import { computed, onMounted } from 'vue';
import { useSettingsStore } from '@/stores/settings';

export function usePartnersHelper() {
  const settingsStore = useSettingsStore();

  const partnerSettings = computed(() => settingsStore.data);
  const error = computed(() => settingsStore.error);

  onMounted(() => {
    settingsStore.load();
  });

  return {
    partnerSettings,
    error,
    fetchPartnerSettings: () => settingsStore.load(), // Для принудительного обновления, если нужно
  };
}
