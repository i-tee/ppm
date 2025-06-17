<!-- resources/js/components/dashboard/UserProfile.vue -->
<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div>
        <VaInput
          v-model="form.name"
          :label="t('vuestic.profile.name')"
          class="font-inter"
          :error="!!errors.name"
          :error-message="errors.name"
          :disabled="!authStore.user?.email_verified_at"
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
          :disabled="true"
        />
      </div>
      <div class="flex space-x-4">
        <VaButton
          type="submit"
          preset="primary"
          class="flex-1 bg-primary text-white hover:bg-secondary px-4 py-2 rounded transition font-inter"
          :disabled="authStore.loading || !authStore.user?.email_verified_at"
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
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';

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

    const toast = useToast();

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
        toast.init({
          message: t('vuestic.profile.success'),
          color: 'success',
          duration: 3000, // 3 секунды
        });
        setTimeout(() => (success.value = false), 3000);
      }
    };

    return {
      t,
      authStore,
      form,
      errors,
      success,
      handleSubmit,
    };
  },
};
</script>

<style scoped>
.min-h-screen {
  display: flex;
  flex-direction: column;
}
</style>