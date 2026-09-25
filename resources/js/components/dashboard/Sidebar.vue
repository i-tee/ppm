<template>

  <va-sidebar-item v-if="!isAdmin" :user="user" :to="{ name: 'Overview' }" :active="$route.name === 'Overview'"
    @click="emit('close')">
    <va-sidebar-item-content>
      <va-icon name="dashboard" />
      <va-sidebar-item-title>{{ $t('_dashboard') }}</va-sidebar-item-title>
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

  <va-sidebar-item :to="{ name: 'Impersonate' }" :active="$route.name === 'Impersonate'" v-if="isAdmin"
    @click="emit('close')">
    <va-sidebar-item-content>
      <va-icon name="person" />
      <va-sidebar-item-title>{{ $t('dashboard.impersonate') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'PartnerApplications' }" :active="$route.name === 'PartnerApplications'" v-if="isAdmin"
    @click="emit('close')">
    <va-sidebar-item-content>
      <va-icon name="business" />
      <va-sidebar-item-title>{{ $t('dashboard.partner_applications') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'RequisiteVerification' }" :active="$route.name === 'RequisiteVerification'"
    @click="emit('close')" v-if="isAdmin">
    <va-sidebar-item-content>
      <va-icon name="business" />
      <va-sidebar-item-title>{{ $t('dashboard.requisite_verification') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'PayoutResolve' }" :active="$route.name === 'PayoutResolve'" v-if="isAdmin"
    @click="emit('close')">
    <va-sidebar-item-content>
      <va-icon name="business" />
      <va-sidebar-item-title>{{ $t('dashboard.payout_resolve') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <!-- <va-sidebar-item :to="{ name: 'Dev' }" :active="$route.name === 'Dev'" v-if="isSuperAdmin">
    <va-sidebar-item-content>
      <va-icon name="code" />
      <va-sidebar-item-title>{{ $t('dashboard.dev') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item> -->

  <va-sidebar-item v-if="!isAdmin" :to="{ name: 'Types' }" :active="$route.name === 'Types'" :disabled="!isActive"
    @click="emit('close')">
    <va-sidebar-item-content>
      <va-icon name="work" />
      <va-sidebar-item-title>{{ $t('dashboard.types') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <div v-for="type in apiData?.cooperation_types" :key="type.id">
    <va-sidebar-item v-if="!!getApplication(2, type.id)" :to="{ name: type.route }" @click="emit('close')"
      :active="$route.name === type.route">
      <va-sidebar-item-content>
        <va-icon name="person" />
        <va-sidebar-item-title>{{ $t('partners.cooperation_types.' + type.name + '.title') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>
  </div>

  <VaDivider v-if="isActive && hasApprovedApplications" class="my-4" />

  <va-sidebar-item :to="{ name: 'Requisite' }" :active="$route.name === 'Requisite'" @click="emit('close')"
    v-if="isActive && hasApprovedApplications">
    <va-sidebar-item-content>
      <va-icon name="note" />
      <va-sidebar-item-title>{{ $t('dashboard.requisite') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

</template>

<script setup>

import { usePartnerApplications } from '@/composables/usePartnerApplications';
const { getApplication, hasApplicationsWithStatus } = usePartnerApplications();
import { computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingsStore } from '@/stores/settings';

const emit = defineEmits(['close'])

// computed - пересчитывается при обновлении authStore.user (свежий профиль
// после refreshUser), иначе статус «заявка не одобрена» держался бы до
// перезагрузки страницы.
const hasApprovedApplications = computed(() => hasApplicationsWithStatus(2));

const authStore = useAuthStore();
const settingsStore = useSettingsStore();
const apiData = computed(() => settingsStore.data);

onMounted(() => {
  settingsStore.load();
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