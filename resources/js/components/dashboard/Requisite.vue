<template>
  <div>

    <div class="d-head">
      <p class="va-h4 my-2 mt-4">{{ $t('dashboard.requisites') }}</p>
      <p>{{ $t('dashboard.requisites_descr') }}</p>
      <VaDivider class="my-4" />
    </div>

    <div class="my-3">

      <div v-if="requisites && requisites.length">
        <div>
          <div v-for="(req, idx) in requisites" :key="req.id" class="p-4 mb-4 bg-white" :class="{
            'is-first': idx === 0,              // первый
            'is-last': idx === requisites.length - 1, // последний
            'is-even': idx % 2 === 0,           // чётный
            'is-odd': idx % 2 !== 0             // нечётный
          }">

            <div class="p-4">
              <!-- ЗАГОЛОВОК -->
              <div class="va-card-title mb-2 va-h2 mt-4">
                {{ $t(`partners.partner_types.${getPartnerType(req.partner_type_id)?.name || 'unknown'}`) }}
              </div>

              <div v-if="req.is_verified">
                <div>
                  <VaBadge :text="$t('requisites.active')" color="primary" />
                </div>
                <div>
                  <span class="text-secondary">{{ $t('requisites.contract') }} #{{ req.id }}</span>
                </div>
              </div>
              <div v-else>
                <div>
                  <VaBadge :text="$t('requisites.validate_requisites')" color="secondary" />
                </div>
                <div>
                  <span class="text-secondary">{{ $t('requisites.prepare_contract') }}</span>
                </div>
              </div>

              <!-- СОДЕРЖИМОЕ -->
              <div class="va-card-content space-y-2">
                <template v-for="({ key, value }, i) in visibleFields(req)" :key="key">
                  <!-- пропускаем полностью is_active -->
                  <div v-if="key !== 'is_active'" class="m-0">
                    <p v-if="i === 0" class="va-h5 mb-3">
                      <span>{{ value }}</span>
                    </p>
                    <p v-else>
                      <strong>{{ $t(`requisites.${key}`) }}:</strong>
                      <span class="ml-1">{{ value }}</span>
                    </p>
                  </div>
                </template>
              </div>

              <!-- КНОПКИ -->
              <VaDivider class="mt-4" />
              <div class="va-card-actions mt-4 justify-end text-end">
                <VaButton preset="secondary" @click="deleteRequisite(req.id)">
                  {{ $t('requisites.delete') }}
                </VaButton>
              </div>

            </div>

          </div>
        </div>
      </div>

      <div v-else-if="requisites">
        <p>{{ $t('requisites.no_requisites') }}</p>
      </div>

      <div v-else>
        <VaSkeleton />
        <br>
        <VaSkeleton />
      </div>

      <VaButton color="primary" class="mt-4" @click="openDialog" :disabled="!isDataLoaded">
        {{ $t('requisites.add') }}
      </VaButton>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ РЕКВИЗИТОВ -->
    <VaModal v-model="showDialog" :loading="submitting" :hide-default-actions="true" :close-button="true" size="medium">
      <VaProgressBar v-if="submitting" indeterminate color="primary" class="mb-4" />

      <VaForm ref="formRef" class="p-4 space-y-4">
        <h3 class="va-h5 mb-2">{{ $t('requisites.create_title') }}</h3>

        <VaSelect v-model="form.partner_type_id" :label="$t('requisites.partner_type')" :options="filteredPartnerTypes"
          :rules="[(v) => !!v || $t('validation.required')]" value-by="value" text-by="text" class="w-full" />

        <VaDivider />

        <!-- ДИНАМИЧЕСКИЕ ПОЛЯ РЕКВИЗИТОВ -->
        <div v-if="requisiteFieldsForm && requisiteFieldsForm.length > 0">
          <div v-for="field in requisiteFieldsForm" :key="field.name" class="mb-4">

            <!-- ТЕКСТОВОЕ ПОЛЕ -->
            <VaInput v-if="field.type === 'text'" v-model="form[field.name]" :label="$t(field.label)"
              :rules="getFieldRules(field)" class="w-full" />

            <!-- ЧИСЛОВОЕ ПОЛЕ -->
            <VaInput v-else-if="field.type === 'number'" v-model.number="form[field.name]" type="number"
              :label="$t(field.label)" :rules="getFieldRules(field)" class="w-full" />

            <!-- EMAIL ПОЛЕ -->
            <VaInput v-else-if="field.type === 'email'" v-model="form[field.name]" type="email" :label="$t(field.label)"
              :rules="getFieldRules(field)" class="w-full" />

            <!-- ДАТА -->
            <VaDateInput v-else-if="field.type === 'date'" v-model="form[field.name]" manual-input
              :label="$t(field.label)" class="w-full" />

            <!-- ЧЕКБОКС -->
            <VaCheckbox v-else-if="field.type === 'checkbox'" v-model="form[field.name]" :label="$t(field.label)"
              class="w-full" />

            <!-- СЕЛЕКТ -->
            <VaSelect v-else-if="field.type === 'select'" v-model="form[field.name]" :label="$t(field.label)"
              :options="field.options || []" :rules="getFieldRules(field)" class="w-full" />

            <!-- ТЕКСТОВАЯ ОБЛАСТЬ -->
            <VaTextarea v-else-if="field.type === 'textarea'" v-model="form[field.name]" :label="$t(field.label)"
              :rules="getFieldRules(field)" class="w-full" />

            <!-- НЕИЗВЕСТНЫЙ ТИП ПОЛЯ -->
            <div v-else class="text-red-500">
              Неизвестный тип поля: {{ field.type }} для {{ field.name }}
            </div>
          </div>
        </div>

        <div v-else-if="form.partner_type_id" class="text-center text-gray-500 py-4">
          Нет доступных полей для выбранного типа партнера
        </div>

      </VaForm>

      <template #footer>
        <div class="flex justify-end space-x-4">
          <VaButton @click="resetForm" color="secondary">{{ $t('modal.cancel') }}</VaButton>
          <VaButton @click="validateAndSubmit" color="primary" :disabled="!canSubmit">
            {{ $t('modal.submit') }}
          </VaButton>
        </div>
      </template>

      <VaProgressBar v-if="submitting" indeterminate color="primary" class="my-2" />

    </VaModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from 'vuestic-ui';
import { useAuthStore } from '@/stores/auth';
import { usePartnersHelper } from '@/composables/partnersHelper';
import { useRequisitesHelper } from '@/composables/requisitesHelper';

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();
const { partnerSettings } = usePartnersHelper();

// Получаем ВСЕ данные из хелпера реквизитов
const {
  requisiteSettings,
  loading: requisitesLoading,
  getFieldsByPartnerType,
  validateRequisitesData,
  getDefaultValuesForPartner,
  fetchRequisiteSettings,
  filterRequisitesData
} = useRequisitesHelper();

// Реактивные данные компонента
const requisites = ref(null);
const showDialog = ref(false);
const submitting = ref(false);
const formRef = ref(null);
const isDataLoaded = ref(false);
const requisiteFieldsForm = ref(null);

// Данные формы - инициализируем как пустой объект
const form = ref({});

// ===========================================================================
// COMPUTED СВОЙСТВА
// ===========================================================================

/**
 * Доступные типы партнеров для выбора
 */
const filteredPartnerTypes = computed(() => {
  if (!partnerSettings.value?.partner_types) {
    return [];
  }
  return partnerSettings.value.partner_types
    .filter(type => type.active)
    .map(type => ({
      value: type.id,
      text: t(`partners.partner_types.${type.name}`),
    }));
});

/**
 * Можно ли отправлять форму
 */
const canSubmit = computed(() => {
  return form.value.partner_type_id && requisiteFieldsForm.value && requisiteFieldsForm.value.length > 0;
});

// ===========================================================================
// ФУНКЦИИ
// ===========================================================================

/**
 * Получить правила валидации для поля
 */
const getFieldRules = (field) => {
  const rules = [];

  if (field.required) {
    // Для разных типов полей разные правила обязательности
    if (field.type === 'checkbox') {
      // Для чекбоксов проверяем что значение true
      rules.push((v) => v === true || t('validation.required'));
    } else if (field.type === 'select') {
      // Для селектов проверяем что значение не пустое
      rules.push((v) => !!v || t('validation.required'));
    } else {
      // Для остальных полей стандартная проверка
      rules.push((v) => !!v || t('validation.required'));
    }
  }

  return rules;
};

/**
 * Получить видимые поля реквизита для отображения
 */
const visibleFields = (req) =>
  Object.entries(req)
    .filter(
      ([k, v]) =>
        !['id', 'partner_type_id', 'is_verified', 'tax_check_required', 'partner_type_name', 'user_id', 'created_at', 'updated_at'].includes(k) &&
        v !== null &&
        v !== undefined &&
        v !== ''
    )
    .map(([k, v]) => ({ key: k, value: v }));

/**
 * Найти тип партнера по ID
 */
const getPartnerType = (id) => {
  return partnerSettings.value?.partner_types?.find(item => item.id === id) || null;
};

/**
 * Открыть модальное окно добавления реквизитов
 */
function openDialog() {
  form.value.partner_type_id = filteredPartnerTypes.value[0]?.value || null;
  showDialog.value = true;
}

/**
 * Сбросить форму
 */
function resetForm() {
  form.value = {};
  showDialog.value = false;
}

/**
 * Валидация и отправка формы
 */
async function validateAndSubmit() {
  // console.log('🔄 Начало валидации формы...');

  // Валидация Vuestic формы
  if (formRef.value) {
    const isValid = await formRef.value.validate();
    if (!isValid) {
      // console.log('❌ Vuestic валидация не пройдена');
      toast.init({ message: t('validation.form_invalid'), color: 'warning' });
      return;
    }
  }

  // console.log('✅ Vuestic валидация пройдена');

  // Валидация бизнес-логики через наш хелпер
  const validationResult = validateRequisitesData(form.value, form.value.partner_type_id);
  // console.log('🔍 Результат бизнес-валидации:', validationResult);

  if (!validationResult.isValid) {
    validationResult.errors.forEach(error => {
      // console.log(`❌ Ошибка валидации: ${error.message}`);
      toast.init({ message: error.message, color: 'danger' });
    });
    return;
  }

  // console.log('✅ Все валидации пройдены');
  // console.log('📝 Данные формы:', form.value);

  // Фильтруем данные перед отправкой
  const payload = filterRequisitesData(form.value, form.value.partner_type_id);
  payload.partner_type_id = form.value.partner_type_id;
  // console.log('📤 Отправляемые данные:', payload);

  submitting.value = true;

  try {
    const response = await axios.post('/api/user/requisites', payload, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    toast.init({ message: t('requisites.requisite_created'), color: 'success' });
    showDialog.value = false;
    await loadRequisites();
    resetForm();
  }
  catch (e) {
    submitting.value = false;

    if (e.response?.status === 422) {
      const errors = e.response.data.errors || {};

      // Собираем все ошибки в один список
      const errorList = Object.values(errors).flat(); // все сообщения в массив

      // Основное сообщение
      toast.init({
        message: t('requisites.validation_failed'),
        color: 'danger',
        duration: 5000,
      });

      // Детальный список ошибок
      if (errorList.length > 0) {
        const detailed = errorList.map(err => `• ${err}`).join('\n');
        toast.init({
          message: detailed,
          color: 'danger',
          duration: 10000,
        });
      }
    } else {
      toast.init({ message: t('requisites.unierror'), color: 'danger' });
    }
  }
  finally {
    submitting.value = false;
  }

}

/**
 * Загрузка реквизитов пользователя
 */
async function loadRequisites() {
  try {
    const response = await axios.get('/api/user/requisites', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    requisites.value = response.data.map(req => {
      const type = partnerSettings.value?.partner_types.find(t => t.id === req.partner_type_id);
      req.partner_type_name = type ? type.name : 'unknown';
      return req;
    });
  } catch (err) {
    // console.error('❌ Ошибка загрузки реквизитов:', err);
    toast.init({ message: t('errors.load_error'), color: 'danger' });
  }
}

/**
 * Удаление реквизита
 */
async function deleteRequisite(id) {
  if (!confirm(t('requisites.confirm_delete'))) return;

  try {
    await axios.delete(`/api/user/requisites/${id}`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    toast.init({ message: t('success.requisite_deleted'), color: 'success' });
    await loadRequisites();
  } catch (e) {
    // console.error('❌ Ошибка при удалении реквизитов:', e);
    toast.init({ message: t('errors.delete_error'), color: 'danger' });
  }
}

// ===========================================================================
// WATCHERS И HOOKS
// ===========================================================================

/**
 * Отслеживаем загрузку данных из обоих хелперов
 */
watch([partnerSettings, requisiteSettings], ([partnerData, requisiteData]) => {
  // console.log('🔍 Отслеживание загрузки данных:');
  // console.log('   - partnerSettings:', partnerData);
  // console.log('   - requisiteSettings:', requisiteData);

  if (partnerData?.partner_types && requisiteData) {
    isDataLoaded.value = true;
    // console.log('✅ Все данные загружены!');
  }
}, { immediate: true });

/**
 * Отслеживаем изменение типа партнера в форме
 */
watch(
  () => form.value.partner_type_id,
  (newValue) => {
    // console.log('🔄 Изменен тип партнера:', newValue);

    if (!requisiteSettings.value) {
      // console.log('❌ requisiteSettings не загружены, не могу получить поля');
      return;
    }

    if (newValue) {
      // Получаем поля для выбранного типа партнера
      requisiteFieldsForm.value = getFieldsByPartnerType(newValue);
      // console.log('✅ Поля для типа', newValue, ':', requisiteFieldsForm.value);

      // Инициализируем дефолтные значения для новых полей
      if (requisiteFieldsForm.value.length > 0) {
        const defaultValues = getDefaultValuesForPartner(newValue);

        // Для полей даты устанавливаем корректные значения
        requisiteFieldsForm.value.forEach(field => {
          if (field.type === 'date') {
            // Для дат используем null или конкретную дату по умолчанию
            if (defaultValues[field.name] === '' || defaultValues[field.name] === undefined) {
              defaultValues[field.name] = null;
            }
          }
        });

        // Сохраняем partner_type_id и добавляем дефолтные значения
        form.value = {
          partner_type_id: newValue,
          ...defaultValues
        };

        // console.log('🎯 Дефолтные значения установлены:', defaultValues);
        // console.log('📋 Текущая форма:', form.value);
      }
    } else {
      requisiteFieldsForm.value = null;
      form.value = {};
    }
  }
);

/**
 * Инициализация компонента
 */
onMounted(() => {
  // console.log('🚀 Компонент Requisite mounted');
  loadRequisites();

  // Принудительно загружаем настройки реквизитов если нужно
  if (!requisiteSettings.value) {
    fetchRequisiteSettings();
  }
});
</script>