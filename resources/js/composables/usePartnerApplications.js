// resources/js/composables/usePartnerApplications.js

import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

/**
 * Composable для работы с заявками партнёра
 */
export function usePartnerApplications() {
  const authStore = useAuthStore();

  /**
   * Проверяет, есть ли у пользователя заявка с определённым статусом и типом
   * @param {number} statusId - ID статуса заявки
   * @param {number} typeId - ID типа заявки
   * @returns {boolean}
   */
  const hasApplication = (statusId, typeId) => {
    if (!authStore.currentUser?.partner_applications?.length) return false;

    return authStore.currentUser.partner_applications.some(app =>
      app.status_id === statusId && app.cooperation_type_id === typeId
    );
  };

  /**
   * Получает конкретную заявку по статусу и типу
   * @param {number} statusId - ID статуса заявки
   * @param {number} typeId - ID типа заявки
   * @returns {Object|null}
   */
  const getApplication = (statusId, typeId) => {
    if (!authStore.currentUser?.partner_applications?.length) return null;

    return authStore.currentUser.partner_applications.find(app =>
      app.status_id === statusId && app.partner_type_id === typeId
    );
  };

  /**
   * Проверяет, есть ли у пользователя хотя бы одна заявка
   * @returns {boolean}
   */
  const hasAnyApplications = computed(() => {
    return !!authStore.currentUser?.partner_applications?.length;
  });

  /**
   * Возвращает количество заявок партнёра
   * @returns {number}
   */
  const applicationsCount = computed(() => {
    return authStore.currentUser?.partner_applications?.length || 0;
  });

  /**
   * Возвращает все заявки партнёра
   * @returns {Array}
   */
  const partnerApplications = computed(() => {
    return authStore.currentUser?.partner_applications || [];
  });

  /**
   * Возвращает все заявки, за которые пользователь отвечает
   * @returns {Array}
   */
  const responsibleApplications = computed(() => {
    return authStore.currentUser?.responsible_applications || [];
  });

  /**
   * Проверяет, есть ли у пользователя заявки с определённым статусом
   * @param {number} statusId - ID статуса
   * @returns {boolean}
   */
  const hasApplicationsWithStatus = (statusId) => {
    if (!authStore.currentUser?.partner_applications?.length) return false;

    return authStore.currentUser.partner_applications.some(app => 
      app.status_id === statusId
    );
  };

  /**
   * Проверяет, есть ли у пользователя активные заявки (например, со статусом 0 или 1)
   * @returns {boolean}
   */
  const hasActiveApplications = computed(() => {
    if (!authStore.currentUser?.partner_applications?.length) return false;

    return authStore.currentUser.partner_applications.some(app => 
      [0, 1].includes(app.status_id)
    );
  });

  return {
    // Методы
    hasApplication,
    getApplication,
    hasApplicationsWithStatus,
    
    // Вычисляемые свойства
    hasAnyApplications,
    applicationsCount,
    partnerApplications,
    responsibleApplications,
    hasActiveApplications,
  };
}