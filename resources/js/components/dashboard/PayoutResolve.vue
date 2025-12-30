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
            <div v-if="payout?.status <= 10">
              <VaButton icon="assignment" icon-color="#ffffff50" @click="openRequisitFullModal(payout);">
                <span>{{ t('dashboard.payout_resolve_go') }}: {{t('partners.partner_types.' + partnerTypes.find(item =>
                  item.id ===
                  payout.requisite?.partner_type_id)?.name || 'error')}}</span>
              </VaButton>
            </div>
            <div v-else-if="payout?.status == 14">
              <!-- Выплачено, ждем тикет -->
              <VaButton :disabled="sendingReminder" color="warning" icon="assignment"
                @click="adminTicketReminder(payout?.id);">
                <span>{{ t('payoutRequest.paid_wait_ticket') }}</span>
              </VaButton>
            </div>
            <div v-else-if="payout?.status == 16">
              <!-- Тикет загружен -->
              <VaButton color="success" icon="assignment" @click="approveTicketModal = true; checkedPayout = payout;">
                <span>{{ t('payoutRequest.ticket_uploaded') }}</span>
              </VaButton>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <VaSkeleton v-else />

  </div>

  <VaModal v-model="showRequisitFullModal" :hide-default-actions="true" :close-button="true" size="medium"
    :mobile-fullscreen="false">
    <RequisitFullModal :checkedPayout="checkedPayout"
      @payoutUpdated="showRequisitFullModal = false; fetchPayoutRequests()" />
    <template #footer>
      <d iv class="flex justify-end space-x-4">
        <VaButton @click="showRequisitFullModal = false" preset="secondary" color="secondary">{{ $t('modal.cancel') }}
        </VaButton>
      </d>
    </template>
  </VaModal>

  <VaModal v-model="approveTicketModal" :hide-default-actions="true" :close-button="true" size="medium">
    <div>

      <h3 class="text-lg font-medium mb-4">{{ $t('payoutRequest.approve_ticket_title') }}</h3>

      <pre>
        {{ checkedPayout?.ticket_proof }}
      </pre>

      <div class="text-center p-6 m-4 rounded-m bg-gray-100">
        <a class="va-link my-4" target="_blank" :href="`/storage/${checkedPayout?.ticket_proof}`">
          <div>
            <p>{{ $t('payoutRequest.ticket.view_file') }}</p>
          </div>
        </a>
      </div>

      <VaDivider class="my-4" />

      <div class="flex justify-end space-x-4 mt-6">
        <VaButton @click="abortTicket(checkedPayout?.id)" :disabled="sendingReminder" preset="secondary"
          color="secondary">{{
            $t('payoutRequest.abortTicket') }}
        </VaButton>
        <VaButton @click="approveTicketSend(checkedPayout?.id)" :disabled="sendingReminder">{{
          $t('payoutRequest.approveTicketSend') }}
        </VaButton>
      </div>

    </div>
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
const sendingReminder = ref(false);
const approveTicketModal = ref(false);

const abortTicket = async (payoutId) => {

  let confirmation = window.confirm(t('payoutRequest.confirm_abort_ticket'));

  if (confirmation) {

    sendingReminder.value = true;

    try {

      const response = await axios.put(`/api/admin/payout-requests-ticket-abort/${payoutId}`, {}, {
        headers: {
          Authorization: `Bearer ${authStore.token}`
        }
      });

      toast.init({ message: t('payoutRequest.ticket_aborted'), color: 'success' })
      sendingReminder.value = false
      approveTicketModal.value = false
      fetchPayoutRequests()

    } catch (error) {

      toast.init({ message: t('payoutRequest.unexpected_error'), color: 'danger' })
      sendingReminder.value = false
      approveTicketModal.value = false
      fetchPayoutRequests()

    }
  } else {
    approveTicketModal.value = false
    sendingReminder.value = false;
  }

};

const approveTicketSend = async (payoutId) => {

  sendingReminder.value = true;

  try {

    const response = await axios.put(`/api/admin/payout-requests/${payoutId}/20`, {}, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    });

    toast.init({ message: t('payoutRequest.ticket_approved'), color: 'success' })
    sendingReminder.value = false
    approveTicketModal.value = false
    fetchPayoutRequests()

  } catch (error) {

    toast.init({ message: t('payoutRequest.unexpected_error'), color: 'danger' })
    sendingReminder.value = false
    approveTicketModal.value = false
    fetchPayoutRequests()

  }

};

const adminTicketReminder = async (payoutId) => {

  sendingReminder.value = true;

  try {

    const response = await axios.post(`/api/admin/payout-ticked-reminder/${payoutId}`, {}, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    });

    toast.init({ message: t('payoutRequest.ticket_reminder_sent'), color: 'warning' })
    sendingReminder.value = false

  } catch (error) {

    toast.init({ message: t('payoutRequest.ticket_reminder_failed'), color: 'danger' })
    sendingReminder.value = false

  }

};

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
