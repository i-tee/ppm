<template>
  <!-- Основной контейнер компонента -->
  <div>
    <!-- Показываем индикатор загрузки, если loading = true -->
    <div v-if="loading" class="mt-4 pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
      <VaSkeleton v-for="i in 3" :key="i" variant="rounded" height="200px" />
    </div>

    <!-- Показываем ошибку, если она есть -->
    <div v-else-if="error" class="mt-4 text-danger">
      {{ error }}
    </div>

    <!-- Показываем список купонов, если они есть -->
    <div v-else-if="coupons.length" class="mt-4">

      <!-- Раздел для процентных промокодов (тип 0) -->
      <div v-if="visiblePercentageCoupons.length">
        <VaDivider orientation="left" class="my-4">
          <span class="px-2 text-secondary">{{ t('coupons.discount_codes') }}</span>
        </VaDivider>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="coupon in visiblePercentageCoupons" :key="coupon.coupon_id">
            <CouponDiscount :coupon="coupon" :bData="bData" :apiData="apiData"
              :hiding="hidingCode === normalizeCode(coupon.coupon_code)" @open-order-info="handleOrderInfo"
              @hide="confirmHide(coupon)" />
          </div>
        </div>
      </div>

      <!-- Раздел для бонусных промокодов (тип 1) -->
      <div v-if="visibleBonusCoupons.length">
        <VaDivider orientation="left" class="my-4">
          <span class="px-2 text-secondary">{{ t('coupons.bonus_codes') }}</span>
        </VaDivider>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="coupon in visibleBonusCoupons" :key="coupon.coupon_id">
            <CouponBonus :coupon="coupon" :bData="bData" :apiData="apiData"
              :hiding="hidingCode === normalizeCode(coupon.coupon_code)" @open-order-info="handleOrderInfo"
              @hide="confirmHide(coupon)" />
          </div>
        </div>
      </div>

      <!-- Архив скрытых промокодов -->
      <div v-if="hiddenCoupons.length" class="mt-6">
        <VaButton preset="secondary" size="small" @click="showArchive = !showArchive">
          {{ t('coupons.archive') }} ({{ hiddenCoupons.length }})
        </VaButton>

        <div v-if="showArchive" class="mt-4">
          <p class="text-secondary text-sm mb-3">{{ t('coupons.archive_hint') }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <VaCard v-for="coupon in hiddenCoupons" :key="coupon.coupon_id" outlined class="rounded-xl">
              <VaCardContent class="flex items-center justify-between gap-2">
                <span class="font-bold">{{ coupon.coupon_code?.toUpperCase() }}</span>
                <VaButton preset="secondary" size="small" icon="restore"
                  :loading="restoringCode === normalizeCode(coupon.coupon_code)" @click="restoreCoupon(coupon)">
                  {{ t('coupons.restore') }}
                </VaButton>
              </VaCardContent>
            </VaCard>
          </div>
        </div>
      </div>

    </div>

    <!-- Показываем сообщение, если купонов нет -->
    <div v-else class="mt-4">
      {{ $t('coupons.no_coupons') }}
    </div>

    <!-- Модалка -->
    <VaModal v-model="showModal" :title="t('coupons.orders')" hide-default-actions max-width="700px" close-button :mobile-fullscreen="false">
      <OrderInfoModal :coupon="selectedCoupon" @close="showModal = false" />
    </VaModal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useBusinessStore } from '@/stores/business';
import CouponDiscount from '@/components/parts/CouponDiscount.vue';
import CouponBonus from '@/components/parts/CouponBonus.vue';
import OrderInfoModal from '@/components/parts/OrderInfoModal.vue';

const { t } = useI18n();
const { init: initToast } = useToast();
const authStore = useAuthStore();
const businessStore = useBusinessStore();

const props = defineProps({
  apiData: {
    type: Object,
    default: null
  },
  bData: {
    type: Object,
    default: null
  },
  refresh: {
    type: Number,
    default: 0
  }
});

// coupons_full в business-data - те же данные, что отдаёт /api/user/coupons
// (обе ручки берут их из JoomlaCoupon::getUserCoupons()), отдельный запрос
// не нужен (stage 1.2).
const coupons = computed(() => props.bData?.data?.coupons_full || []);
const loading = computed(() => !props.bData);
const error = ref(null);
const showModal = ref(false);
const selectedCoupon = ref(null);
const showArchive = ref(false);
const hidingCode = ref(null);
const restoringCode = ref(null);

const normalizeCode = (code) => (code || '').toLowerCase();

// hidden_coupon_codes - коды в нижнем регистре (этап 1.6). Скрытие - только
// отображение, сам промокод продолжает работать на сайте и начисляться.
const hiddenCodes = computed(() => props.bData?.data?.hidden_coupon_codes || []);
const isHidden = (coupon) => hiddenCodes.value.includes(normalizeCode(coupon.coupon_code));

const percentageCoupons = computed(() => coupons.value.filter(c => c.coupon_type === 0));
const visiblePercentageCoupons = computed(() => percentageCoupons.value.filter(c => !isHidden(c)));

// Активен = не использован НИ в Joomla (used), НИ на новом сайте
// (backend_used, этап 4) — та же логика, что в карточке CouponBonus.
const isBonusActive = (c) => c.used === 0 && !c.backend_used;

const bonusCoupons = computed(() => {
  return coupons.value
    .filter(c => c.coupon_type === 1)
    .sort((a, b) => {
      // Активные (не использованные ни в одной системе) идут первыми
      if (isBonusActive(a) && !isBonusActive(b)) return -1;
      if (!isBonusActive(a) && isBonusActive(b)) return 1;
      return 0; // Остальные купоны сохраняют порядок
    });
});
const visibleBonusCoupons = computed(() => bonusCoupons.value.filter(c => !isHidden(c)));

const hiddenCoupons = computed(() => coupons.value.filter(isHidden));

const handleOrderInfo = (coupon) => {
  selectedCoupon.value = coupon;
  showModal.value = true;
};

const authHeaders = () => ({
  Authorization: `Bearer ${authStore.token}`,
  'Content-Type': 'application/json',
  Accept: 'application/json',
});

const confirmHide = (coupon) => {
  const code = coupon.coupon_code;
  if (!confirm(t('coupons.hide_confirm', { code }))) {
    return;
  }
  hideCoupon(coupon);
};

const hideCoupon = async (coupon) => {
  const code = normalizeCode(coupon.coupon_code);
  hidingCode.value = code;
  try {
    await axios.post('/api/user/coupons/hide', { code: coupon.coupon_code }, { headers: authHeaders() });
    await businessStore.load({ force: true });
  } catch (err) {
    initToast({
      message: err.response?.data?.message ? t(err.response.data.message) : t('coupons.hide_failed'),
      color: 'danger',
    });
  } finally {
    hidingCode.value = null;
  }
};

const restoreCoupon = async (coupon) => {
  const code = normalizeCode(coupon.coupon_code);
  restoringCode.value = code;
  try {
    await axios.post('/api/user/coupons/restore', { code: coupon.coupon_code }, { headers: authHeaders() });
    await businessStore.load({ force: true });
  } catch (err) {
    initToast({
      message: err.response?.data?.message ? t(err.response.data.message) : t('coupons.restore_failed'),
      color: 'danger',
    });
  } finally {
    restoringCode.value = null;
  }
};
</script>