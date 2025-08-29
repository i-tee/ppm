<template>

  <va-sidebar-item :to="{ name: 'Overview' }" :active="$route.name === 'Overview'">
    <va-sidebar-item-content>
      <va-icon name="dashboard" />
      <va-sidebar-item-title>{{ $t('_dashboard') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'Types' }" :active="$route.name === 'Types'" :disabled="!isActive">
    <va-sidebar-item-content>
      <va-icon name="work" />
      <va-sidebar-item-title>{{ $t('dashboard.types') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <!-- <va-sidebar-item :to="{ name: 'Promocodes' }" :active="false" disabled>
    <va-sidebar-item-content>
      <va-icon name="confirmation_number" />
      <va-sidebar-item-title>{{ $t('dashboard.promocodes') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'ReferralLinks' }" :active="false" disabled>
    <va-sidebar-item-content>
      <va-icon name="link" />
      <va-sidebar-item-title>{{ $t('dashboard.referral_links') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item> -->

  <!-- <va-sidebar-item :to="{ name: 'Account' }" :active="$route.name === 'Account'">
    <va-sidebar-item-content>
      <va-icon name="account_circle" />
      <va-sidebar-item-title>{{ $t('account') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item> -->

  <va-sidebar-item :to="{ name: 'PartnerApplications' }" :active="$route.name === 'PartnerApplications'" v-if="isAdmin">
    <va-sidebar-item-content>
      <va-icon name="business" />
      <va-sidebar-item-title>{{ $t('dashboard.partner_applications') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'Dev' }" :active="$route.name === 'Dev'" v-if="isSuperAdmin">
    <va-sidebar-item-content>
      <va-icon name="code" />
      <va-sidebar-item-title>{{ $t('dashboard.dev') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <!-- Динамические пункты: перебираем cooperation_types -->
  <hr>

  <div v-for="type in apiData?.cooperation_types" :key="type.id">
    <va-sidebar-item v-if="!!getApplication(2, type.id)" :to="{ name: 'Overview' }">
      <va-sidebar-item-content>
        <va-icon name="work" />
        <va-sidebar-item-title>
          {{ $t('partners.cooperation_types.' + type.name + '.title') }}
        </va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>
  </div>

</template>

<script setup>

import { usePartnerApplications } from '@/composables/usePartnerApplications';
const { getApplication } = usePartnerApplications();
import { computed } from 'vue';

import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

// Определяем реактивные переменные
const apiData = ref(null);
const error = ref(null);
const authStore = useAuthStore();

onMounted(async () => {
  try {
    const response = await axios.get('/api/ps', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    });
    apiData.value = response.data;

  } catch (err) {
    error.value = err.response?.data || err.message;
    console.error('Ошибка загрузки:', error.value);
  }
});

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

// const isVerified = computed(() => !!props.user.email_verified_at);
const isSuperAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1));
});
const isAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1) || props.user.effective_access_levels.includes(2));
});
const isActive = computed(() => {
  return props.user.effective_access_levels && props.user.effective_access_levels.some(level => level >= 0);
});

</script>