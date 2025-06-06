<!-- resources/js/components/dashboard/UserProfile.vue -->
<template>
  <VaCard class="p-6 bg-white shadow-lg rounded-lg max-w-lg mx-auto">
    <VaCardTitle class="text-xl font-bold text-primary font-inter">
      {{ t('vuestic.profile.title') }}
    </VaCardTitle>
    <VaCardContent>
      <!-- Форма редактирования -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <VaInput
            v-model="form.name"
            :label="t('vuestic.profile.name')"
            class="font-inter"
            :error="!!errors.name"
            :error-message="errors.name"
          />
        </div>
        <div>
          <VaInput
            v-model="form.email"
            :label="t('vuestic.profile.email')"
            type="email"
            class="font-inter"
            :error="!!errors.email"
            :error-message="errors.email"
          />
        </div>
        <div class="flex space-x-4">
          <VaButton
            type="submit"
            preset="primary"
            class="flex-1 bg-primary text-white hover:bg-secondary px-4 py-2 rounded transition font-inter"
            :disabled="authStore.loading"
          >
            {{ authStore.loading ? t('vuestic.profile.saving') : t('vuestic.profile.save') }}
          </VaButton>
          <VaButton
            preset="secondary"
            class="flex-1 px-4 py-2 rounded font-inter"
            @click="authStore.logout"
            :disabled="authStore.loading"
          >
            {{ t('vuestic.profile.logout') }}
          </VaButton>
        </div>
      </form>
      <!-- Сообщение об ошибке -->
      <div v-if="authStore.error" class="mt-4 text-danger font-inter">
        {{ authStore.error }}
      </div>
      <!-- Сообщение об успехе -->
      <VaAlert v-if="success" color="success" class="mt-4 font-inter">
        {{ t('vuestic.profile.success') }}
      </VaAlert>
    </VaCardContent>
  </VaCard>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

export default {
  name: 'UserProfile',
  setup() {
    const { t } = useI18n();
    const authStore = useAuthStore();
    const success = ref(false);
    const form = reactive({
      name: '',
      email: '',
    });
    const errors = reactive({
      name: '',
      email: '',
    });

    onMounted(async () => {
      await authStore.fetchUser();
      form.name = authStore.user?.name || '';
      form.email = authStore.user?.email || '';
    });

    const handleSubmit = async () => {
      errors.name = '';
      errors.email = '';
      success.value = false;

      if (!form.name) errors.name = t('vuestic.profile.errors.name_required');
      if (!form.email) errors.email = t('vuestic.profile.errors.email_required');
      else if (!/\S+@\S+\.\S+/.test(form.email)) errors.email = t('vuestic.profile.errors.email_invalid');

      if (errors.name || errors.email) return;

      const result = await authStore.updateUser({
        name: form.name,
        email: form.email,
      });
      if (result) {
        success.value = true;
        setTimeout(() => (success.value = false), 3000);
      }
    };

    return { t, authStore, form, errors, success, handleSubmit };
  },
};
</script>