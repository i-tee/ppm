<template>
  <va-sidebar class="dashboard-sidebar">
    <va-sidebar-item
      :to="{ name: 'Overview' }"
      :active="$route.name === 'Overview'"
    >
      <va-sidebar-item-content>
        <va-icon name="dashboard" />
        <va-sidebar-item-title>{{ $t('_dashboard') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>
    <va-sidebar-item
      :to="{ name: 'Promocodes' }"
      :active="$route.name === 'Promocodes'"
    >
      <va-sidebar-item-content>
        <va-icon name="confirmation_number" />
        <va-sidebar-item-title>{{ $t('dashboard.promocodes') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>
    <va-sidebar-item
      :to="{ name: 'ReferralLinks' }"
      :active="$route.name === 'ReferralLinks'"
    >
      <va-sidebar-item-content>
        <va-icon name="link" />
        <va-sidebar-item-title>{{ $t('dashboard.referral_links') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>
  </va-sidebar>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Sidebar',
});
</script>

<style scoped>
.dashboard-sidebar {
  width: 250px;
  background-color: #f8f9fa;
  padding-top: 20px;
}
</style>