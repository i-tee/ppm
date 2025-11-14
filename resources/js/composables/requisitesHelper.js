import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useAuthStore } from "@/stores/auth";
import { useToast } from "vuestic-ui";

export function useRequisitesHelper() {
  const authStore = useAuthStore();
  const toast = useToast();

  // Реактивные данные
  const requisiteSettings = ref(null); // Основные настройки реквизитов из API
  const error = ref(null); // Ошибки загрузки
  const loading = ref(false); // Статус загрузки

  /**
   * Загрузка настроек реквизитов с сервера
   */
  async function fetchRequisiteSettings() {
    loading.value = true;
    try {
      // console.log('🔄 Загрузка реквизитов...');
      const response = await axios.get("/api/rs", {
        headers: { Authorization: `Bearer ${authStore.token}` },
      });
      requisiteSettings.value = response.data;
      // console.log('✅ Реквизиты загружены:', response.data);
      error.value = null;
    } catch (err) {
      // console.error('❌ Ошибка загрузки реквизитов:', err);
      error.value = err.response ? err.response.data : err.message;
      toast.init({ message: "Ошибка загрузки реквизитов", color: "danger" });
    } finally {
      loading.value = false;
    }
  }

  // ===========================================================================
  // ОСНОВНЫЕ ФУНКЦИИ ДЛЯ РАБОТЫ С ПОЛЯМИ РЕКВИЗИТОВ
  // ===========================================================================

  /**
   * Проверка наличия хотя бы одного верифицированного реквизита у пользователя
   * @returns {Promise<boolean>} true если есть верифицированный реквизит, иначе false
   */
  async function hasVerifiedRequisite() {
    try {

      const response = await axios.get("/api/user/requisites", {
        // Подставь свой эндпоинт, если другой
        headers: { Authorization: `Bearer ${authStore.token}` },
      });

      const requisites = response.data; // Предполагаем массив реквизитов
      const hasVerified = Array.isArray(requisites)
        ? requisites.some((req) => req.is_verified === true)
        : false;

      return hasVerified;
    } catch (err) {
      console.error('❌ Ошибка проверки реквизитов:', err);
      return false; // На ошибке возвращаем false, чтоб не блокировало UI
    }
  }

  /**
   * Получить все поля реквизитов для указанного типа партнера
   * @param {number} partnerTypeId - ID типа партнера (1-физлицо, 2-самозанятый, 3-ИП, 4-ООО)
   * @returns {Array} Отсортированный массив полей, видимых для данного типа партнера
   */
  const getFieldsByPartnerType = (partnerTypeId) => {
    // console.log("🔍 Поиск полей для типа партнера:", partnerTypeId);

    if (!requisiteSettings.value) {
      // console.log("❌ requisiteSettings не загружены");
      return [];
    }

    // Пробуем разные возможные структуры данных
    let fields = [];

    // Вариант 1: прямая структура
    if (requisiteSettings.value.requisite_fields) {
      fields = requisiteSettings.value.requisite_fields;
    }
    // Вариант 2: вложенная структура
    else if (requisiteSettings.value.data?.requisite_fields) {
      fields = requisiteSettings.value.data.requisite_fields;
    }
    // Вариант 3: массив на верхнем уровне
    else if (Array.isArray(requisiteSettings.value)) {
      fields = requisiteSettings.value;
    }

    // console.log('📋 Найдено полей всего:', fields.length);

    const filteredFields = fields
      .filter((field) => {
        const isVisible =
          field.visible && field.visible.includes(Number(partnerTypeId));
        // console.log(`🔎 Поле "${field.name}": visible=${JSON.stringify(field.visible)}, includes=${isVisible}`);
        return isVisible;
      })
      .sort((a, b) => (a.order || 0) - (b.order || 0));

    // console.log(`✅ Отфильтровано полей для типа ${partnerTypeId}:`, filteredFields.length);
    // console.log('📝 Поля:', filteredFields);

    return filteredFields;
  };

  /**
   * Получить только обязательные поля для указанного типа партнера
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {Array} Массив обязательных полей
   */
  const getRequiredFieldsByPartnerType = (partnerTypeId) => {
    return getFieldsByPartnerType(partnerTypeId).filter(
      (field) => field.required
    );
  };

  /**
   * Получить поля определенной группы для типа партнера
   * @param {number} partnerTypeId - ID типа партнера
   * @param {string} groupName - Название группы ('basic', 'passport', 'bank', 'organization')
   * @returns {Array} Поля указанной группы
   */
  const getFieldsByGroup = (partnerTypeId, groupName) => {
    return getFieldsByPartnerType(partnerTypeId).filter(
      (field) => field.group === groupName
    );
  };

  /**
   * Найти поле реквизита по его имени
   * @param {string} fieldName - Название поля (например, 'full_name', 'org_inn')
   * @returns {Object|null} Объект поля или null если не найдено
   */
  const getFieldByName = (fieldName) => {
    if (!requisiteSettings.value) return null;

    let fields = [];
    if (requisiteSettings.value.requisite_fields) {
      fields = requisiteSettings.value.requisite_fields;
    } else if (requisiteSettings.value.data?.requisite_fields) {
      fields = requisiteSettings.value.data.requisite_fields;
    } else if (Array.isArray(requisiteSettings.value)) {
      fields = requisiteSettings.value;
    }

    return fields.find((field) => field.name === fieldName);
  };

  /**
   * Получить перевод для названия поля
   * @param {string} fieldName - Название поля
   * @returns {string} Локализованная метка поля или исходное название если перевод не найден
   */
  const getFieldLabel = (fieldName) => {
    const field = getFieldByName(fieldName);
    return field ? field.label : fieldName;
  };

  /**
   * Проверить, видимо ли поле для указанного типа партнера
   * @param {string} fieldName - Название поля
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {boolean} true если поле видимо
   */
  const isFieldVisibleForPartner = (fieldName, partnerTypeId) => {
    const field = getFieldByName(fieldName);
    return field ? field.visible.includes(Number(partnerTypeId)) : false;
  };

  /**
   * Проверить, обязательно ли поле для указанного типа партнера
   * @param {string} fieldName - Название поля
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {boolean} true если поле обязательно и видимо
   */
  const isFieldRequiredForPartner = (fieldName, partnerTypeId) => {
    const field = getFieldByName(fieldName);
    return field
      ? field.required && field.visible.includes(Number(partnerTypeId))
      : false;
  };

  // ===========================================================================
  // ФУНКЦИИ ДЛЯ ВАЛИДАЦИИ И ОБРАБОТКИ ДАННЫХ
  // ===========================================================================

  /**
   * Валидация данных реквизитов для указанного типа партнера
   * Проверяет заполнение обязательных полей
   * @param {Object} data - Данные формы реквизитов
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {Object} Объект с результатом валидации {isValid: boolean, errors: Array}
   */
  const validateRequisitesData = (data, partnerTypeId) => {
    const errors = [];
    const requiredFields = getRequiredFieldsByPartnerType(partnerTypeId);

    // console.log("🔍 Валидация данных:", data);
    // console.log("📋 Обязательные поля:", requiredFields);

    requiredFields.forEach((field) => {
      const value = data[field.name];
      // console.log(
      //   `🔎 Проверка поля "${field.name}":`,
      //   value,
      //   "required:",
      //   field.required
      // );

      // Проверяем что значение не пустое (учитываем разные типы)
      let isEmpty = false;

      if (field.type === "checkbox") {
        // Для чекбоксов проверяем что значение true
        isEmpty = value !== true;
      } else if (field.type === "number") {
        // Для чисел проверяем что значение не null/undefined и не пустая строка
        isEmpty = value === null || value === undefined || value === "";
      } else {
        // Для остальных типов стандартная проверка
        isEmpty = !value && value !== 0 && value !== false;
      }

      if (isEmpty) {
        // console.log(`❌ Поле "${field.name}" не заполнено`);
        errors.push({
          field: field.name,
          message: `Поле "${getFieldLabel(
            field.name
          )}" обязательно для заполнения`,
        });
      } else {
        // console.log(`✅ Поле "${field.name}" заполнено:`, value);
      }
    });

    // console.log("📊 Результат валидации:", {
    //   isValid: errors.length === 0,
    //   errors,
    // });
    return {
      isValid: errors.length === 0,
      errors,
    };
  };

  /**
   * Фильтрация данных - оставляем только поля, видимые для указанного типа партнера
   * Полезно перед отправкой на сервер чтобы убрать лишние поля
   * @param {Object} data - Исходные данные формы
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {Object} Отфильтрованные данные
   */
  const filterRequisitesData = (data, partnerTypeId) => {
    const filteredData = {};
    const visibleFields = getFieldsByPartnerType(partnerTypeId);

    visibleFields.forEach((field) => {
      // Добавляем поле только если оно есть в данных и не undefined/null
      if (data[field.name] !== undefined && data[field.name] !== null) {
        filteredData[field.name] = data[field.name];
      }
    });

    return filteredData;
  };

  /**
   * Получить объект с дефолтными значениями для всех полей типа партнера
   * Используется для инициализации формы
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {Object} Объект с дефолтными значениями {fieldName: defaultValue}
   */
  const getDefaultValuesForPartner = (partnerTypeId) => {
    const defaultValues = {};
    const fields = getFieldsByPartnerType(partnerTypeId);

    fields.forEach((field) => {
      // Используем значение по умолчанию из конфига если есть
      if (field.default !== undefined) {
        defaultValues[field.name] = field.default;
      } else if (field.type === "checkbox") {
        // Для чекбоксов по умолчанию false
        defaultValues[field.name] = false;
      } else if (
        field.type === "select" &&
        field.options &&
        field.options.length > 0
      ) {
        // Для селектов берем первую опцию
        defaultValues[field.name] = field.options[0];
      } else if (field.type === "date") {
        // Для дат используем null вместо пустой строки
        defaultValues[field.name] = null;
      } else {
        // Для остальных типов пустая строка
        defaultValues[field.name] = "";
      }
    });

    return defaultValues;
  };

  /**
   * Группировка полей по группам для удобного отображения в форме
   * @param {number} partnerTypeId - ID типа партнера
   * @returns {Object} Объект с группами полей {groupName: [field1, field2, ...]}
   */
  const getGroupedFields = (partnerTypeId) => {
    const fields = getFieldsByPartnerType(partnerTypeId);
    const groups = {};

    // Группируем поля по названию группы
    fields.forEach((field) => {
      if (!groups[field.group]) {
        groups[field.group] = [];
      }
      groups[field.group].push(field);
    });

    // Сортируем группы по порядку первого поля в группе
    return Object.keys(groups)
      .sort((a, b) => {
        const orderA = Math.min(...groups[a].map((f) => f.order));
        const orderB = Math.min(...groups[b].map((f) => f.order));
        return orderA - orderB;
      })
      .reduce((acc, groupName) => {
        // Сортируем поля внутри группы по порядку
        acc[groupName] = groups[groupName].sort((a, b) => a.order - b.order);
        return acc;
      }, {});
  };

  // ===========================================================================
  // COMPUTED СВОЙСТВА ДЛЯ УДОБСТВА И РЕАКТИВНОСТИ
  // ===========================================================================

  /**
   * Реактивный массив всех полей реквизитов
   */
  const requisiteFields = computed(() => {
    if (!requisiteSettings.value) return [];

    if (requisiteSettings.value.requisite_fields) {
      return requisiteSettings.value.requisite_fields;
    } else if (requisiteSettings.value.data?.requisite_fields) {
      return requisiteSettings.value.data.requisite_fields;
    } else if (Array.isArray(requisiteSettings.value)) {
      return requisiteSettings.value;
    }

    return [];
  });

  /**
   * Реактивный массив всех уникальных групп полей
   */
  const fieldGroups = computed(() => {
    const fields = requisiteFields.value;
    if (!fields.length) return [];

    const groups = new Set();
    fields.forEach((field) => {
      groups.add(field.group);
    });
    return Array.from(groups);
  });

  // ===========================================================================
  // ИНИЦИАЛИЗАЦИЯ
  // ===========================================================================

  /**
   * Автоматическая загрузка настроек при первом использовании хелпера
   */
  onMounted(() => {
    fetchRequisiteSettings();
  });

  // ===========================================================================
  // ЭКСПОРТ ФУНКЦИЙ И ДАННЫХ
  // ===========================================================================

  return {
    // === ДАННЫЕ ===
    requisiteSettings, // Основные настройки реквизитов
    requisiteFields, // Реактивный массив всех полей
    fieldGroups, // Реактивный массив групп полей
    error, // Ошибки загрузки
    loading, // Статус загрузки

    // === ОСНОВНЫЕ ФУНКЦИИ ===
    fetchRequisiteSettings, // Принудительная перезагрузка настроек
    getFieldsByPartnerType, // Поля для типа партнера
    getRequiredFieldsByPartnerType, // Обязательные поля
    getFieldsByGroup, // Поля по группе
    getFieldByName, // Найти поле по имени
    getFieldLabel, // Получить метку поля
    isFieldVisibleForPartner, // Проверить видимость поля
    isFieldRequiredForPartner, // Проверить обязательность поля
    hasVerifiedRequisite, // Проверить наличие хоть одного верифицированного реквизита

    // === ФУНКЦИИ ДЛЯ РАБОТЫ С ДАННЫМИ ===
    validateRequisitesData, // Валидация данных формы
    filterRequisitesData, // Фильтрация данных перед отправкой
    getDefaultValuesForPartner, // Дефолтные значения для формы
    getGroupedFields, // Группировка полей для отображения

    // === АЛИАСЫ ДЛЯ УДОБСТВА ===
    getPartnerTypeFields: getFieldsByPartnerType, // Короткий алиас
  };
}
