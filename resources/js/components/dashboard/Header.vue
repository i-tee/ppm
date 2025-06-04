<template>
    <va-card class="mb-4">
        <va-card-title class="text-primary text-2xl">
          {{ $t('dashboard.title') }} {{ user && user.name ? user.name : 'Партнёр' }}
        </va-card-title>
        <va-card-content>
          <p class="text-secondary">{{ $t('dashboard.welcome') }}</p>
        </va-card-content>
      </va-card>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Header',
});
</script>