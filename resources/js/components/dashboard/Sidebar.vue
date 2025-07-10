<template>

  <va-sidebar-item :to="{ name: 'Overview' }" :active="$route.name === 'Overview'">
    <va-sidebar-item-content>
      <va-icon name="dashboard" />
      <va-sidebar-item-title>{{ $t('_dashboard') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'Types' }" :active="$route.name === 'Types'" :disabled="!isVerified">
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

  <va-sidebar-item :to="{ name: 'Account' }" :active="$route.name === 'Account'">
    <va-sidebar-item-content>
      <va-icon name="account_circle" />
      <va-sidebar-item-title>{{ $t('account') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

  <va-sidebar-item :to="{ name: 'Dev' }" :active="$route.name === 'Dev'">
    <va-sidebar-item-content>
      <va-icon name="code" />
      <va-sidebar-item-title>{{ $t('dashboard.dev') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item>

</template>

<script>
import { defineComponent, computed } from 'vue';

export default defineComponent({
  name: 'Sidebar',
  props: {
    user: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const isVerified = computed(() => !!props.user.email_verified_at);
    return { isVerified };
  },
});
</script>