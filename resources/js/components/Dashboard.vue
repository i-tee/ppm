<template>
  <div class="dashboard-wrapper">
    <!-- Заголовок и приветствие -->
    <va-card class="mb-4">
      <va-card-title class="text-primary text-2xl">
        {{ $t('dashboard.title') }} {{ user && user.name ? user.name : 'Партнёр' }}
      </va-card-title>
      <va-card-content>
        <p class="text-secondary">{{ $t('dashboard.welcome') }}</p>
      </va-card-content>
    </va-card>

    <!-- Метрики -->
    <va-row :gutter="16" class="mb-4">
      <va-col :sm="12" :md="4">
        <va-card class="metric-card">
          <va-card-content class="text-center">
            <va-icon name="account_balance_wallet" size="large" color="primary" />
            <h3 class="mt-2">{{ totalEarnings }} ₽</h3>
            <p class="text-secondary">{{ $t('dashboard.total_earnings') }}</p>
          </va-card-content>
        </va-card>
      </va-col>
      <va-col :sm="12" :md="4">
        <va-card class="metric-card">
          <va-card-content class="text-center">
            <va-icon name="mouse" size="large" color="primary" />
            <h3 class="mt-2">{{ clicks }}</h3>
            <p class="text-secondary">{{ $t('dashboard.clicks') }}</p>
          </va-card-content>
        </va-card>
      </va-col>
      <va-col :sm="12" :md="4">
        <va-card class="metric-card">
          <va-card-content class="text-center">
            <va-icon name="trending_up" size="large" color="primary" />
            <h3 class="mt-2">{{ conversions }}</h3>
            <p class="text-secondary">{{ $t('dashboard.conversions') }}</p>
          </va-card-content>
        </va-card>
      </va-col>
    </va-row>

    <!-- График доходов -->
    <va-card class="mb-4">
      <va-card-title>{{ $t('dashboard.earnings_chart') }}</va-card-title>
      <va-card-content>
        <va-chart type="line" :data="chartData" :options="chartOptions" style="height: 300px;" />
      </va-card-content>
    </va-card>

    <!-- Промокоды -->
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
            <va-button color="primary" @click="generatePromo">
              {{ $t('dashboard.generate_promo') }}
            </va-button>
          </va-list-item>
        </va-list>
      </va-card-content>
    </va-card>

    <!-- Партнёрские ссылки -->
    <va-card>
      <va-card-title>{{ $t('dashboard.referral_links') }}</va-card-title>
      <va-card-content>
        <va-list>
          <va-list-item v-for="(link, index) in referralLinks" :key="index">
            <span>{{ link.url }}</span>
            <va-button size="small" color="secondary" class="ml-2" @click="copyLink(link.url)">
              {{ $t('dashboard.copy') }}
            </va-button>
          </va-list-item>
          <va-list-item>
            <va-button color="primary" @click="generateLink">
              {{ $t('dashboard.generate_link') }}
            </va-button>
          </va-list-item>
        </va-list>
      </va-card-content>
    </va-card>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Dashboard',
  setup() {
    const authStore = useAuthStore();
    return { user: authStore.user };
  },
  data() {
    return {
      totalEarnings: 12500,
      clicks: 450,
      conversions: 25,
      chartData: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
        datasets: [
          {
            label: this.$t('dashboard.earnings'),
            data: [2000, 3000, 2500, 4000, 3500, 3500],
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.2)',
            fill: true,
          },
        ],
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
      promocodes: [
        { code: 'PROMO2025' },
        { code: 'SAVE10' },
      ],
      referralLinks: [
        { url: 'https://example.com/ref/12345' },
        { url: 'https://example.com/ref/4567890' },
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
    copyLink(url) {
      navigator.clipboard.writeText(url).then(() => {
        this.$vaToast.init({
          message: this.$t('dashboard.copied'),
          color: 'success',
        });
      });
    },
    generatePromo() {
      const newCode = `PROMO${Math.floor(Math.random() * 10000)}`;
      this.promocodes.push({ code: newCode });
      this.$vaToast.init({
        message: this.$t('dashboard.promo_generated'),
        color: 'success',
      });
    },
    generateLink() {
      const newLink = `https://example.com/ref/${Math.floor(Math.random() * 100000)}`;
      this.referralLinks.push({ url: newLink });
      this.$vaToast.init({
        message: this.$t('dashboard.link_generated'),
        color: 'success',
      });
    },
  },
});
</script>

<style scoped>
.dashboard-wrapper {
  padding: 20px;
}

.metric-card {
  text-align: center;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.metric-card:hover {
  transform: scale(1.02);
}

h3 {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
}

.text-secondary {
  color: #888;
}
</style>