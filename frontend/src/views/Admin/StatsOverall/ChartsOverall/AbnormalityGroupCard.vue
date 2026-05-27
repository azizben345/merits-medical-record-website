<template>
  <div class="group-card">
    <div class="card-header">
      <div class="card-title">{{ title }}</div>
      <div class="card-meta">{{ data?.checked || 0 }} Staff Tested</div>
    </div>

    <div class="kpi-row">
      <div class="kpi-value" :style="{ color: color }">
        {{ data?.abnormal_unique || 0 }}
      </div>
      <div class="kpi-label">
        Unique Staff with<br>Abnormal Results
      </div>
    </div>

    <div class="sub-chart-wrapper">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

export default {
  name: 'AbnormalityGroupCard',
  props: {
    title: { type: String, required: true },
    data: { type: Object, required: true }, // { checked, abnormal_unique, details: [] }
    color: { type: String, default: '#3b82f6' }
  },
  data() { return { chart: null }; },
  mounted() { this.renderChart(); },
  // Important: Re-render if data changes (e.g. user changes year)
  watch: { 
    data: { deep: true, handler() { this.renderChart(); } } 
  },
  beforeUnmount() { if (this.chart) this.chart.destroy(); },
  methods: {
    renderChart() {
      if (!this.$refs.chartCanvas) return;
      if (this.chart) this.chart.destroy();
      if (!this.data || !this.data.details) return;

      const labels = this.data.details.map(d => d.label);
      const counts = this.data.details.map(d => Number(d.count));

      this.chart = new Chart(this.$refs.chartCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data: counts,
            backgroundColor: this.color,
            borderRadius: 4,
            barThickness: 24
          }]
        },
        options: {
          indexAxis: 'y', // Horizontal Bars for readability
          responsive: true, 
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }, // Disable tooltip, rely on datalabels
            datalabels: {
              anchor: 'end', align: 'end',
              color: '#334155', font: { weight: 'bold', size: 11 },
              formatter: (val) => val > 0 ? val : '' // Hide 0s
            }
          },
          scales: {
            x: { display: false, max: Math.max(...counts, 5) * 1.2 }, // Add headroom for labels
            y: { 
              grid: { display: false },
              ticks: { font: { size: 11 }, color: '#475569', autoSkip: false }
            }
          }
        }
      });
    }
  }
};
</script>

<style scoped>
.group-card {
  background: white; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  display: flex; flex-direction: column;
}

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.card-title { font-weight: 700; color: #1e293b; font-size: 15px; }
.card-meta { font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 2px 8px; border-radius: 12px; }

/* KPI Styling */
.kpi-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
.kpi-value { font-size: 36px; font-weight: 800; line-height: 1; }
.kpi-label { font-size: 12px; color: #64748b; line-height: 1.3; font-weight: 600; text-transform: uppercase; }

.sub-chart-wrapper { position: relative; height: 160px; width: 100%; }
</style>