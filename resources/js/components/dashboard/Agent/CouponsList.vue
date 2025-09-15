<template>
  <div>
    <div v-if="loading" class="mt-4">
      ...
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
import { getUserCoupons, getBusinessData } from '@/api/coupons';

const { t } = useI18n();
const toast = useToast();

const bData = ref([]);
const coupons = ref([]);
const loading = ref(false);
const error = ref(null);

const loadBusinessData = async () => {
  try {
    const response = await getBusinessData();

    if (response.success) {
      bData.value = response;
      // Логирование данных
      console.log('Business Data:', response);
    } else {
      toast.init({
        color: 'danger',
        message: 'HZ'
      });
    }
  } catch (e) {
    console.error('Error loading business data:', e);
    toast.init({
      message: 'Error loading business data',
      color: 'danger',
    });
  }
};

const loadCoupons = async () => {
  loading.value = true;
  error.value = null;

  try {
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
  } catch (e) {
    console.error('Error loading coupons:', e);
    toast.init({
      message: 'Error loading coupons',
      color: 'danger',
    });
  }

  loading.value = false;
};

onMounted(async () => {
  try {
    await Promise.all([loadCoupons(), loadBusinessData()]);
  } catch (e) {
    console.error('Error loading data:', e);
    toast.init({
      message: 'Error loading data',
      color: 'danger',
    });
  }
});
</script>