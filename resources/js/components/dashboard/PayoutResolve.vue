<template>
  <pre>
    {{ payouts }}
  </pre>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

const isAdmin = computed(() => {
  return props.user.effective_access_levels && (props.user.effective_access_levels.includes(1) || props.user.effective_access_levels.includes(2));
});

const payoutRequests = ref([]);
const payouts = ref([]);

const fetchPayoutRequests = async () => {
  if (!isAdmin.value) return;
  try {

    const params = {
      statu_id: ''
    };

    const response = await axios.get('/api/admin/payout-requests', { headers: {
        Authorization: `Bearer ${authStore.token}`
      },
      params
    });

    payoutRequests.value = response.data
    toast.init({ message: 'Complate', color: 'danger' })
    payouts.value = payoutRequests.value.data.data

  } catch (error) {
    toast.init({ message: t('errors.fetch_failed'), color: 'danger' });
  }

};

onMounted(async () => {
  if (isAdmin.value) {
    await fetchPayoutRequests();
  }
});

</script>
