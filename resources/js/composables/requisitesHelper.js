import { ref, onMounted } from "vue";
import axios from "axios";
import { useAuthStore } from "@/stores/auth";
import { useToast } from "vuestic-ui";

export function useRequisitesHelper() {
  const authStore = useAuthStore();
  const toast = useToast();
  const requisiteSettings = ref(null);
  const error = ref(null);
  const loading = ref(false);

  async function fetchRequisiteSettings() {
    loading.value = true;
    try {
      const response = await axios.get("/api/rs", {
        headers: { Authorization: `Bearer ${authStore.token}` },
      });
      requisiteSettings.value = response.data;
      error.value = null;
    } catch (err) {
      error.value = err.response ? err.response.data : err.message;
      toast.init({ message: "Ошибка загрузки реквизитов", color: "danger" });
    } finally {
      loading.value = false;
    }
  }

  onMounted(() => {
    fetchRequisiteSettings();
  });

  return {
    requisiteSettings,
    error,
    loading,
    fetchRequisiteSettings, // Для принудительного обновления, если нужно
  };
}
