// @/composables/usePartnerApplications.js

import { computed, ref, onMounted } from 'vue'; // ✅ Импорты добавлены
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

/**
 * Composable для работы с заявками партнёра
 */
export function usePartnerApplications() {
  const authStore = useAuthStore();

  // Локальное хранилище заявок (если нужно отдельно от user)
  const applications = ref([]);

  // Функция для загрузки заявок с API
  const loadApplications = async () => {
    try {
      const response = await axios.get('/api/partner-applications', {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
        },
      });
      applications.value = response.data;
    } catch (e) {
      console.error('Failed to load partner applications', e);
    }
  };

  // Загружаем при инициализации
  onMounted(() => {
    loadApplications();
  });

  // === Вычисляемые свойства ===

  /**
   * Проверяет, есть ли заявка с определённым статусом и типом
   * Использует данные из currentUser (если они есть)
   */
  const hasApplication = (statusId, typeId) => {
    const apps = authStore.currentUser?.partner_applications;
    if (!Array.isArray(apps)) return false;

    return apps.some(app =>
      app.status_id === statusId && app.cooperation_type_id === typeId
    );
  };

  /**
   * Возвращает заявку по статусу и типу
   */
  const getApplication = (statusId, typeId) => {
    const apps = authStore.currentUser?.partner_applications;
    if (!Array.isArray(apps)) return null;

    return apps.find(app =>
      app.status_id === statusId && app.cooperation_type_id === typeId
    ) || null;
  };

  /**
   * Есть ли у пользователя любые заявки?
   */
  const hasAnyApplications = computed(() => {
    const apps = authStore.currentUser?.partner_applications;
    return Array.isArray(apps) && apps.length > 0;
  });

  /**
   * Количество заявок
   */
  const applicationsCount = computed(() => {
    const apps = authStore.currentUser?.partner_applications;
    return Array.isArray(apps) ? apps.length : 0;
  });

  /**
   * Все заявки пользователя
   */
  const partnerApplications = computed(() => {
    return authStore.currentUser?.partner_applications || [];
  });

  /**
   * Заявки, за которые пользователь отвечает (если есть)
   */
  const responsibleApplications = computed(() => {
    return authStore.currentUser?.responsible_applications || [];
  });

  /**
   * Есть ли заявки с определённым статусом?
   */
  const hasApplicationsWithStatus = (statusId) => {
    const apps = authStore.currentUser?.partner_applications;
    if (!Array.isArray(apps)) return false;

    return apps.some(app => app.status_id === statusId);
  };

  /**
   * Есть ли активные заявки (статус 0 или 1)?
   */
  const hasActiveApplications = computed(() => {
    const apps = authStore.currentUser?.partner_applications;
    if (!Array.isArray(apps)) return false;

    return apps.some(app => [0, 1].includes(app.status_id));
  });

  // === Возвращаем всё необходимое ===
  return {
    // Функции
    loadApplications, // ✅ Чтобы можно было обновить вручную
    hasApplication,
    getApplication,
    hasApplicationsWithStatus,

    // Вычисляемые свойства
    hasAnyApplications,
    applicationsCount,
    partnerApplications,
    responsibleApplications,
    hasActiveApplications,

    // Опционально: если хочешь использовать локальный массив
    applications,
  };
}