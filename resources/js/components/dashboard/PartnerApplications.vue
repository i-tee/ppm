<template>
    <template v-if="isAdmin">
      <VaCard>
        <p>{{ isAdmin }}</p>
      </VaCard>
    </template>
    <!-- если нет доступа к разделу -->
    <template v-else>
      <VaAlert color="danger" class="mt-4">
        {{ $t('errors.no_access') }}
      </VaAlert>
    </template>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui'; // Импорт useToast из Vuestic UI
import { useI18n } from 'vue-i18n'; // Импорт useI18n для доступа к $t

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const { t } = useI18n(); // Получаем функцию перевода

const isAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1) || props.user.effective_access_levels.includes(2));
});

const toast = useToast(); // Инициализация toast

onMounted(() => {
  if (!isAdmin.value) {
    toast.init({
      message: t('errors.no_access'), // Используем t() для перевода в скрипте
      color: 'danger',
    });
  }
});
</script>