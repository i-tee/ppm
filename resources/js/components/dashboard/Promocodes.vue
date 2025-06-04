<template>
  <div>
    <va-card class="mb-4">
      <va-card-title>{{ $t('dashboard.promocodes') }}</va-card-title>
      <va-card-content>
        <va-list>
          <va-list-item v-for="(promo, index) in promocodes" :key="index">
            <span>{{ promo.code }}</span>
            <va-button size="small" color="secondary" class="ml-2" @click="copyPromo(promo.code)">
              {{ $t('dashboard.copy') }}
            </va-button>
          </va-list-item>
          <va-list-item>
            <va-button color="primary" @click="generatePromoCode">
              {{ $t('dashboard.generate_promo') }}
            </va-button>
          </va-list-item>
        </va-list>
      </va-card-content>
    </va-card>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Promocodes',
  data() {
    return {
      promocodes: [
        { code: 'PROMO2025' },
        { code: 'SAVE10' },
      ],
    };
  },
  methods: {
    copyPromo(code) {
      navigator.clipboard.writeText(code).then(() => {
        this.$vaToast.init({
          message: this.$t('dashboard.copied'),
          color: 'success',
        });
      });
    },
    generatePromoCode() {
      const newCode = `PROMO${Math.floor(Math.random() * 10000)}`;
      this.promocodes.push({ code: newCode });
      this.$vaToast.init({
        message: this.$t('dashboard.promo_generated'),
        color: 'success',
      });
    },
  },
});
</script>