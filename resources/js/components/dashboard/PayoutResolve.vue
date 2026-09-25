<template>
  <template v-if="!canManageFinance">
    <VaAlert color="danger" class="mt-4">
      {{ $t('errors.no_access') }}
    </VaAlert>
  </template>

  <template v-else>
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
            <div class="flex items-center justify-start gap-2">

              <div class="h-8 w-8 flex-none">
                <VaAvatar :src="payout.user.avatar" class="bg-gray-200 flex items-center justify-center shrink-0"
                  size="small">
                  <VaIcon v-if="!payout.user.avatar" name="person" size="small" class="text-gray-500" />
                </VaAvatar>
              </div>

              <div class="flex flex-col flex-none">
                <span class="font-medium">
                  {{ payout.user.name }}
                </span>

                <span class="text-xs text-gray-500 cursor-pointer hover:text-primary"
                  @click="copyEmail(payout.user.email)" title="Нажмите, чтобы скопировать">
                  {{ payout.user.email }}
                </span>
              </div>

            </div>
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
            <div v-if="payout?.status <= 10" class="flex items-center gap-2">
              <VaButton icon="assignment" icon-color="#ffffff50" @click="openRequisitFullModal(payout);">
                <span>{{ t('dashboard.payout_resolve_go') }}: {{t('partners.partner_types.' + partnerTypes.find(item =>
                  item.id ===
                  payout.requisite?.partner_type_id)?.name || 'error')}}</span>
              </VaButton>
              <VaButton preset="secondary" size="small" color="secondary" icon="cancel"
                @click="openCancelModal(payout)">
                <span>{{ t('payoutRequest.cancel.button') }}</span>
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
      <div class="flex justify-end space-x-4">
        <VaButton @click="showRequisitFullModal = false" preset="secondary" color="secondary">{{ $t('modal.cancel') }}
        </VaButton>
      </div>
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

  <VaModal v-model="cancelModal" :title="$t('payoutRequest.cancel.modal_title')" :hide-default-actions="true"
    :close-button="true" size="small">
    <div v-if="cancelPayout">
      <div class="text-sm space-y-1 mb-4">
        <div><span class="text-gray-500">{{ $t('payoutRequest.cancel.confirm_partner') }}: </span>
          <strong>{{ cancelPayout.user?.name }}</strong> ({{ cancelPayout.user?.email }})
        </div>
        <div><span class="text-gray-500">{{ $t('payoutRequest.cancel.confirm_amount') }}: </span>
          <strong>{{ formatPrice(cancelPayout.withdrawal_amount) }}</strong>
        </div>
      </div>

      <p class="font-bold">{{ $t('payoutRequest.cancel.reason_label') }}</p>
      <VaInput v-model="cancelReason" :placeholder="$t('payoutRequest.cancel.reason_placeholder')" type="textarea"
        class="w-full" maxlength="500" />
    </div>

    <template #footer>
      <div class="flex justify-end space-x-4">
        <VaButton @click="cancelModal = false" preset="secondary" color="secondary" :disabled="cancelling">
          {{ $t('payoutRequest.cancel.cancel_button') }}
        </VaButton>
        <VaButton color="danger" :disabled="cancelling" @click="submitCancelPayout">
          {{ $t('payoutRequest.cancel.confirm_button') }}
        </VaButton>
      </div>
    </template>
  </VaModal>
  </template>

</template>

<script setup>
import RequisitFullModal from '@/components/parts/RequisitFullModal.vue';
import { useBase } from '@/composables/useBase';
import { useAuthStore } from '@/stores/auth';
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'vuestic-ui';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import api from '@/api';

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

const copyEmail = async (email) => {
  try {
    await navigator.clipboard.writeText(email)
    toast.init({ message: t('payoutRequest.email_copied'), color: 'success' });
  } catch (e) {
    toast.init({ message: t('errors.email_copied_failed'), color: 'danger' });
  }
}

const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

const canManageFinance = computed(() => authStore.canManageFinance);

const checkedPayout = ref(false);
const showRequisitFullModal = ref(false);
const payoutRequests = ref([]);
const payouts = ref([]);
const partnerTypes = ref([]);
const LoadindTable = ref(true);
const sendingReminder = ref(false);
const approveTicketModal = ref(false);

const cancelModal = ref(false);
const cancelPayout = ref(null);
const cancelReason = ref('');
const cancelling = ref(false);

const openCancelModal = (payout) => {
  cancelPayout.value = payout;
  cancelReason.value = '';
  cancelModal.value = true;
};

const submitCancelPayout = async () => {

  if (!cancelReason.value.trim()) {
    toast.init({ message: t('payoutRequest.cancel.reason_required'), color: 'warning' });
    return;
  }

  cancelling.value = true;

  try {

    const response = await api.put(`/admin/payout-requests/${cancelPayout.value.id}/cancel`, {
      reason: cancelReason.value.trim(),
    });

    toast.init({ message: response.data?.message || t('payoutRequest.cancel.success'), color: 'success' });

    payouts.value = payouts.value.filter((p) => p.id !== cancelPayout.value.id);

    cancelModal.value = false;
    cancelPayout.value = null;

  } catch (error) {

    toast.init({
      message: error.response?.data?.message || t('errors.unexpected_error'),
      color: 'danger',
    });

  } finally {
    cancelling.value = false;
  }

};

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

  if (!canManageFinance.value) return;

  try {

    LoadindTable.value = true

    const params = {
      status_id: ''
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
  if (canManageFinance.value) {
    await fetchPayoutRequests();
  }
});

</script>

<style>
table.va-table.va-table-payoutRequest td {
  padding: 16px 20px;
}
</style>
