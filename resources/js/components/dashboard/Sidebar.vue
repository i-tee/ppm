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

</template>

<script setup>
import { computed } from 'vue';

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