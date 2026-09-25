<template>

  <!-- Партнёр без одобренной заявки: Главная, Анкета, Профиль -->
  <template v-if="!isStaff">

    <va-sidebar-item :user="user" :to="{ name: 'Overview' }" :active="$route.name === 'Overview'"
      @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="dashboard" />
        <va-sidebar-item-title>{{ $t('_dashboard') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

    <va-sidebar-item v-if="!hasApprovedApplications" :to="{ name: 'Application' }"
      :active="$route.name === 'Application'" :disabled="!isActive" @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="assignment" />
        <va-sidebar-item-title>{{ $t('dashboard.application') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

    <!-- Одобренная заявка: работа с промокодами, статистикой и выплатами -->
    <template v-if="isActive && hasApprovedApplications">

      <div class="sidebar-section-title">{{ $t('dashboard.section_work') }}</div>

      <va-sidebar-item :to="{ name: 'Promocodes' }" :active="$route.name === 'Promocodes'" @click="emit('close')">
        <va-sidebar-item-content>
          <va-icon name="confirmation_number" />
          <va-sidebar-item-title>{{ $t('dashboard.promocodes') }}</va-sidebar-item-title>
        </va-sidebar-item-content>
      </va-sidebar-item>

      <va-sidebar-item :to="{ name: 'Statistics' }" :active="$route.name === 'Statistics'" @click="emit('close')">
        <va-sidebar-item-content>
          <va-icon name="bar_chart" />
          <va-sidebar-item-title>{{ $t('dashboard.statistics') }}</va-sidebar-item-title>
        </va-sidebar-item-content>
      </va-sidebar-item>

      <va-sidebar-item :to="{ name: 'Payouts' }" :active="$route.name === 'Payouts'" @click="emit('close')">
        <va-sidebar-item-content>
          <va-icon name="payments" />
          <va-sidebar-item-title>{{ $t('dashboard.payouts') }}</va-sidebar-item-title>
        </va-sidebar-item-content>
      </va-sidebar-item>

      <div class="sidebar-section-title">{{ $t('dashboard.section_settings') }}</div>

      <va-sidebar-item :to="{ name: 'Requisite' }" :active="$route.name === 'Requisite'" @click="emit('close')">
        <va-sidebar-item-content>
          <va-icon name="note" />
          <va-sidebar-item-title>{{ $t('dashboard.requisite') }}</va-sidebar-item-title>
        </va-sidebar-item-content>
      </va-sidebar-item>

    </template>

    <va-sidebar-item :to="{ name: 'Account' }" :active="$route.name === 'Account'" @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="account_circle" />
        <va-sidebar-item-title>{{ $t('account') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

  </template>

  <!-- Сотрудники: админ видит все 4 пункта, бухгалтер - только Реквизиты и Выплаты -->
  <template v-if="isStaff">

    <va-sidebar-item :to="{ name: 'PartnerApplications' }" :active="$route.name === 'PartnerApplications'"
      v-if="isAdmin" @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="assignment" />
        <va-sidebar-item-title>{{ $t('dashboard.partner_applications') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

    <va-sidebar-item :to="{ name: 'RequisiteVerification' }" :active="$route.name === 'RequisiteVerification'"
      @click="emit('close')" v-if="canManageFinance">
      <va-sidebar-item-content>
        <va-icon name="fact_check" />
        <va-sidebar-item-title>{{ $t('dashboard.requisite_verification') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

    <va-sidebar-item :to="{ name: 'PayoutResolve' }" :active="$route.name === 'PayoutResolve'" v-if="canManageFinance"
      @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="payments" />
        <va-sidebar-item-title>{{ $t('dashboard.payout_resolve') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

    <va-sidebar-item :to="{ name: 'Partners' }" :active="$route.name === 'Partners'" v-if="isAdmin"
      @click="emit('close')">
      <va-sidebar-item-content>
        <va-icon name="group" />
        <va-sidebar-item-title>{{ $t('dashboard.partners') }}</va-sidebar-item-title>
      </va-sidebar-item-content>
    </va-sidebar-item>

  </template>

  <!-- <va-sidebar-item :to="{ name: 'Dev' }" :active="$route.name === 'Dev'" v-if="isSuperAdmin">
    <va-sidebar-item-content>
      <va-icon name="code" />
      <va-sidebar-item-title>{{ $t('dashboard.dev') }}</va-sidebar-item-title>
    </va-sidebar-item-content>
  </va-sidebar-item> -->

</template>

<script setup>

import { usePartnerApplications } from '@/composables/usePartnerApplications';
const { hasApplicationsWithStatus } = usePartnerApplications();
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['close'])

// computed - пересчитывается при обновлении authStore.user (свежий профиль
// после refreshUser), иначе статус «заявка не одобрена» держался бы до
// перезагрузки страницы.
const hasApprovedApplications = computed(() => hasApplicationsWithStatus(2));

const authStore = useAuthStore();

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
const isAdmin = computed(() => authStore.isAdmin);
const canManageFinance = computed(() => authStore.canManageFinance);
const isStaff = computed(() => authStore.isStaff);
const isActive = computed(() => {
  return props.user.effective_access_levels && props.user.effective_access_levels.some(level => level >= 0);
});

</script>

<style scoped>
.sidebar-section-title {
  padding: 1rem 1rem 0.25rem;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--va-secondary);
  opacity: 0.7;
}
</style>