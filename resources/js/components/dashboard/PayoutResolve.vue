<template>
  <div class="va-table-responsive">
    <table class="va-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Name</th>
          <th>Name</th>
          <th>Name</th>
          <th>Name</th>
          <th>Name</th>
          <th>Name</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="payout in payouts" :key="payout.id">
          <td>{{ payout.user_id }}</td>
          <td>{{ payout.requisite_id }}</td>
          <td>{{ formatPrice(payout.withdrawal_amount) }}</td>
          <td>{{ formatPrice(payout.received_amount) }}</td>
          <td>{{ formatPrice(payout.commission_amount) }}</td>
          <td>{{ payout.commission_percentage }}%</td>
          <td>{{ formatDate(payout.created_at) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <hr>
  <pre>
    {{ payouts }}
  </pre>

</template>

<script setup>
import { useBase } from '@/composables/useBase';
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const {formatPrice, formatDate} = useBase();

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

    const response = await axios.get('/api/admin/payout-requests', {
      headers: {
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
