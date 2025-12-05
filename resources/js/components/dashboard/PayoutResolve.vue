<template>
  <div class="va-table-responsive">
    <table v-if="!LoadindTable" class="va-table va-table-payoutRequest">
      <thead>
        <tr>
          <th>{{ $t('payoutRequest.amount_receiv') }}</th> <!-- Сумма к выводу -->
          <th>{{ $t('payoutRequest.user_name') }}</th> <!-- Имя пользователя -->
          <th>{{ $t('payoutRequest.amount_bonus_withdrawal') }}</th> <!-- Сумма к списанию -->
          <th>{{ $t('payoutRequest.date') }}</th> <!-- Дата создания -->
          <th>{{ $t('payoutRequest.requisit_and_go') }}</th> <!-- Тип партнера -->
        </tr>
      </thead>
      <tbody>
        <tr v-for="payout in payouts" :key="payout.id" class="align-middle">
          <td>{{ formatPrice(payout.received_amount) }}</td>
          <td>
            <VaAvatar :src="payout.user.avatar" class="mr-1 bg-gray-200" size="small"></VaAvatar>
            <span>{{ payout.user.name }}</span>
          </td>
          <td>
            <VaBadge color="secondary" :offset="[16, 0]" text-color="#fff" overlap
              :title="formatPrice(payout.commission_amount)"
              :text="'-' + Math.round(payout.commission_percentage) + '%'">
              <span>{{ formatPrice(payout.withdrawal_amount) }}</span>
            </VaBadge>
          </td>
          <td>{{ formatDate(payout.created_at) }}</td>
          <td>
            <div>
              <VaButton icon="assignment" icon-color="#ffffff50"
                @click="openRequisitFullModal(payout);">
                <span>{{t('partners.partner_types.' + partnerTypes.find(item => item.id ===
                  payout.requisite?.partner_type_id)?.name || 'error')}}</span>
              </VaButton>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <VaSkeleton v-else />

  </div>

  <VaModal v-model="showRequisitFullModal" :hide-default-actions="true" :close-button="true" size="medium" :mobile-fullscreen="false">
    <RequisitFullModal :checkedPayout="checkedPayout" @payoutUpdated="showRequisitFullModal = false; fetchPayoutRequests()" />
    <template #footer>
      <div class="flex justify-end space-x-4">
        <VaButton @click="showRequisitFullModal = false" preset="secondary" color="secondary">{{ $t('modal.cancel') }}
        </VaButton>
      </div>
    </template>
  </VaModal>

</template>

<script setup>
import RequisitFullModal from '@/components/parts/RequisitFullModal.vue';
import { useBase } from '@/composables/useBase';
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

function openRequisitFullModal(payout) {

  checkedPayout.value = payout;
  showRequisitFullModal.value = true;
}

const { formatPrice, formatDate } = useBase();

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

const checkedPayout = ref(false);
const showRequisitFullModal = ref(false);
const payoutRequests = ref([]);
const payouts = ref([]);
const partnerTypes = ref([]);
const LoadindTable = ref(true);

const fetchPayoutRequests = async () => {

  if (!isAdmin.value) return;

  try {

    LoadindTable.value = true

    const params = {
      statu_id: ''
    };

    const response = await axios.get('/api/admin/payout-requests-prepared', {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      },
      params
    });

    payoutRequests.value = response.data
    partnerTypes.value = response.data?.partnerTypes
    payouts.value = payoutRequests.value.data?.data

    LoadindTable.value = false;

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

<style>
table.va-table.va-table-payoutRequest td {
  padding: 16px 20px;
}
</style>
