<template>
  <div class="overview-wrapper">
    <!-- Метрики -->
    
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Overview',
  data() {
    return {
      totalEarnings: 12500,
      clicks: 450,
      conversions: 25,
      chartData: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май'],
        datasets: [
          {
            label: this.$t('dashboard.earnings'),
            data: [2000, 3000, 2500],
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
    };
  },
});
</script>

<style scoped>
.overview-wrapper {
  padding: 10px;
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