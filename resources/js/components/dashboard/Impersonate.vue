<template>
  <div class="admin-users">
    <div v-if="loading">{{ $t('common.loading') }}</div>

    <div v-else>
      <div v-if="users.data && users.data.length">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th style="border: 1px solid #ccc; padding: 8px;">ID</th>
              <th style="border: 1px solid #ccc; padding: 8px;">{{ $t('admin.users.name') }}</th>
              <th style="border: 1px solid #ccc; padding: 8px;">{{ $t('admin.users.email') }}</th>
              <th style="border: 1px solid #ccc; padding: 8px;">{{ $t('admin.users.status') }}</th>
              <th style="border: 1px solid #ccc; padding: 8px;">{{ $t('admin.users.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td style="border: 1px solid #ccc; padding: 8px;">{{ user.id }}</td>
              <td style="border: 1px solid #ccc; padding: 8px;">{{ user.name }}</td>
              <td style="border: 1px solid #ccc; padding: 8px;">{{ user.email }}</td>
              <td style="border: 1px solid #ccc; padding: 8px;">
                <span v-if="user.email_verified_at" style="color: green;">
                  {{ $t('admin.users.verified') }}
                </span>
                <span v-else style="color: orange;">
                  {{ $t('admin.users.unverified') }}
                </span>
              </td>
              <td style="border: 1px solid #ccc; padding: 8px;">
                <button 
                  @click="impersonateUser(user.id)" 
                  :disabled="currentUserId === user.id"
                  style="padding: 5px 10px; background: #4CAF50; color: white; border: none; border-radius: 3px;"
                >
                  {{ currentUserId === user.id ? 'Это вы' : $t('admin.users.impersonate') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Пагинация -->
        <div class="pagination-wrapper" v-if="users.last_page > 1">
          <va-pagination
            v-model="currentPage"
            :pages="users.last_page"
            :visible-pages="7"
            buttons-preset="secondary"
          />
        </div>
      </div>

      <div v-else>{{ $t('admin.users.empty') }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vuestic-ui';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import api from '@/api';

const { t } = useI18n();
const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();

const users = ref({ data: [], last_page: 1 });
const loading = ref(false);
const currentPage = ref(1);

const currentUserId = computed(() => authStore.currentUser?.id);
const isImpersonating = computed(() => authStore.isImpersonating);
const impersonatedUser = computed(() => {
  const data = localStorage.getItem('impersonated_user');
  return data ? JSON.parse(data) : null;
});

onMounted(async () => {
  if (!authStore.currentUser) {
    await authStore.fetchUser();
  }

  if (!authStore.isAdmin) {
    toast.init({ type: 'danger', message: t('admin.accessDenied') });
    router.push('/dashboard');
    return;
  }

  fetchUsers();
});

const fetchUsers = async (page = currentPage.value) => {
  loading.value = true;
  try {
    const response = await api.get('/admin/users', {
      params: { page }
    });
    users.value = response.data;
    currentPage.value = response.data.current_page; // синхронизируем с ответом сервера
  } catch (error) {
    toast.init({ type: 'danger', message: t('errors.fetchUsers') });
  } finally {
    loading.value = false;
  }
};

// При смене страницы в пагинации — подгружаем новую
watch(currentPage, (newPage) => {
  fetchUsers(newPage);
});

const impersonateUser = async (userId) => {
  const user = users.value.data.find(u => u.id === userId);
  if (!confirm(t('admin.impersonation.confirm', { user: user.name }) || `Вы точно хотите войти как "${user.name}"?`)) {
    return;
  }

  loading.value = true;
  try {
    const success = await authStore.impersonate(userId);
    if (success) {
      toast.init({ 
        type: 'success', 
        message: t('admin.impersonation.success', { user: user.name }) 
      });
      location.href = '/dashboard';
    } else {
      toast.init({ type: 'danger', message: authStore.error || t('admin.impersonation.error') });
    }
  } catch (error) {
    toast.init({ type: 'danger', message: t('admin.impersonation.error') });
  } finally {
    loading.value = false;
  }
};

const stopImpersonation = async () => {
  loading.value = true;
  try {
    const success = await authStore.stopImpersonation();
    if (success) {
      toast.init({ type: 'info', message: t('admin.impersonation.stopped') });
      await authStore.fetchUser();
      router.push('/dashboard');
    } else {
      toast.init({ type: 'danger', message: authStore.error || t('errors.stopImpersonation') });
    }
  } catch (error) {
    toast.init({ type: 'danger', message: t('errors.stopImpersonation') });
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.admin-users {
  padding: 20px;
}

.pagination-wrapper {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
</style>