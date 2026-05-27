<template>
  <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Line } from 'vue-chartjs'

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

export default {
  name: 'TrendChart',
  components: { Line },
  props: {
    labels: { type: Array, required: true }, // Array of Years
    datasets: { type: Array, required: true }, // Array of data objects
    title: { type: String, default: '' },
    suggestedMax: { type: Number, default: null } // Optional Y-axis cap
  },
  computed: {
    chartData() {
      return {
        labels: this.labels,
        datasets: this.datasets
      }
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          datalabels: { display: false, },
          tooltip: { enabled: true, }, // ensures they still SHOW when you hover
          legend: { position: 'bottom' },
          title: { display: !!this.title, text: this.title }
        },
        scales: {
          y: {
            beginAtZero: false, // Set true if you want charts starting at 0
            suggestedMax: this.suggestedMax
          }
        }
      }
    }
  }
}
</script>