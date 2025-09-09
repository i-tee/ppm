<template>
  <div class="overview-wrapper">

    <div v-if="hasApprovedApplications">
      
      <h1 class="va-h4 my-4">Заявка есть, включаем иную логику</h1>

    </div>

    <div v-else>
      <h1 class="va-h4 my-4">{{ $t('welcomes.short_head') }}</h1>
      <p>{{ $t('welcomes.short') }}</p>
      <hr class="my-4">
      <p>{{ $t('welcomes.choose_text') }}</p>
      <va-button
        class="mt-4"
        color="primary"
        :to="{ name: 'Types' }"
      >
        {{ $t('welcomes.choose') }}
      </va-button>
    </div>

  </div>
</template>

<script setup>
import { usePartnerApplications } from '@/composables/usePartnerApplications';
const { hasApplicationsWithStatus } = usePartnerApplications();
// Проверяем, есть ли заявки со статусом 2
const hasApprovedApplications = hasApplicationsWithStatus(2);
</script>