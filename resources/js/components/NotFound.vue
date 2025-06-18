<template>
  <div class="flex h-screen">
    <!-- Левая часть: Infobar -->
    <div class="w-1/2 hidden md:block">
      <Infobar />
    </div>
    <!-- Правая часть: Сообщение 404 -->
    <div class="w-full md:w-1/2 bg-gray-50 flex justify-center items-center p-6">
      <div class="w-full max-w-md text-center">
        <!-- Заголовок -->
        <h2 class="text-3xl font-bold font-inter text-gray-900 mb-2">
          {{ $t('page_not_found') }}
        </h2>
        <p class="text-center text-gray-600 font-inter mb-6">
          {{ $t('page_not_found_description') }}
        </p>

        <!-- Кнопка возврата -->
        <VaButton @click="goHome" color="primary"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-inter py-3 rounded-lg transition">
          {{ $t('back_to_home') }}
        </VaButton>
      </div>
    </div>
  </div>
</template>

<script>
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Infobar from './Infobar.vue';

export default {
  name: 'NotFound',
  components: { Infobar },
  setup() {
    const router = useRouter();
    const { t } = useI18n();

    return { router, t };
  },
  methods: {
    goHome() {
      this.router.push('/');
    },
  },
};
</script>