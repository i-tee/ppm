<!-- resources/js/components/dashboard/CouponsList.vue -->
<template>
  <div>
    <h3 class="va-h3">{{ $t('coupons.title') }}</h3>
    <va-button @click="loadCoupons">{{ $t('coupons.load') }}</va-button>
    
    <div v-if="loading" class="mt-4">
      <va-spinner />
    </div>
    <div v-else-if="error" class="mt-4 text-danger">
      {{ error }}
    </div>
    <div v-else-if="coupons.length" class="mt-4">
      <va-list>
        <va-list-item v-for="coupon in coupons" :key="coupon.coupon_id">
          {{ coupon.coupon_code }} ({{ coupon.coupon_value }} {{ coupon.coupon_type === 0 ? '%' : 'бонусов' }})
        </va-list-item>
      </va-list>
    </div>
    <div v-else class="mt-4">
      {{ $t('coupons.no_coupons') }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import { getUserCoupons } from '@/api/coupons';

const { t } = useI18n();
const toast = useToast();

const coupons = ref([]);
const loading = ref(false);
const error = ref(null);

const loadCoupons = async () => {
  loading.value = true;
  error.value = null;

  const response = await getUserCoupons();

  if (response.success) {
    coupons.value = response.coupons;
    if (response.count === 0) {
      toast.init({
        message: t('coupons.no_coupons'),
        color: 'warning',
      });
    }
  } else {
    error.value = response.error || t('errors.load_failed');
    toast.init({
      message: error.value,
      color: 'danger',
    });
  }

  loading.value = false;
};

onMounted(loadCoupons);
</script>