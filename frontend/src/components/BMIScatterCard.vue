<template>
  <div class="chart-card wide">
    <div class="card-title">BMI Distribution (Top 50 Staff)</div>
    <div class="canvas-wrapper">
      <canvas ref="scatterCanvas"></canvas>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  name: 'BMIScatterCard',
  props: {
    points: { type: Array, required: true } 
  },
  data() { return { chart: null }; },
  mounted() { this.renderChart(); },
  watch: { points() { this.renderChart(); } },
  methods: {
    renderChart() {
      if (!this.$refs.scatterCanvas) return;
      if (this.chart) this.chart.destroy();

      const ctx = this.$refs.scatterCanvas.getContext('2d');

      // 1. Separate Data into Buckets (for color coding the dots)
      const underweight = [];
      const normal = [];
      const overweight = [];

      this.points.forEach((p, index) => {
        // x-axis is just the index (1, 2, 3...) to spread them out
        const point = { x: index + 1, y: Number(p.bmi), email: p.staff_email };
        
        if (point.y < 18.5) underweight.push(point);
        else if (point.y <= 24.9) normal.push(point);
        else overweight.push(point);
      });

      // 2. Background Zone Plugin (The colored bands)
      const bgPlugin = {
        id: 'bgPlugin',
        beforeDraw: (chart) => {
          const { ctx, chartArea, scales } = chart;
          const { top, bottom, left, width } = chartArea;
          
          if (!scales.y) return;

          // Get pixel positions for the BMI thresholds
          const y18 = scales.y.getPixelForValue(18.5);
          const y25 = scales.y.getPixelForValue(25.0);

          ctx.save();
          
          // Red Zone (Overweight > 25) - Top of chart down to 25 line
          // Note: In Canvas, 0 is at the top. 
          // So "Top to y25" covers the high numbers.
          ctx.fillStyle = 'rgba(245, 101, 101, 0.08)'; 
          ctx.fillRect(left, top, width, y25 - top);
          
          // Green Zone (Normal 18.5 - 25)
          ctx.fillStyle = 'rgba(72, 187, 120, 0.08)';
          ctx.fillRect(left, y25, width, y18 - y25);
          
          // Blue Zone (Underweight < 18.5)
          ctx.fillStyle = 'rgba(66, 153, 225, 0.08)';
          ctx.fillRect(left, y18, width, bottom - y18);
          
          ctx.restore();
        }
      };

      this.chart = new Chart(ctx, {
        type: 'scatter',
        data: {
          datasets: [
            { label: 'Underweight', data: underweight, backgroundColor: '#4299e1' },
            { label: 'Normal', data: normal, backgroundColor: '#48bb78' },
            { label: 'Overweight/Obese', data: overweight, backgroundColor: '#f56565' }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.raw.email}: BMI ${ctx.raw.y}`
              }
            }
          },
          scales: {
            x: { display: false }, // Hide X axis (staff names/indices)
            y: { 
              title: { display: true, text: 'BMI Value' },
              min: 10,  // Start at 10 to keep chart focused
              suggestedMax: 35 
            }
          }
        },
        plugins: [bgPlugin] // Register our custom background
      });
    }
  }
};
</script>

<style scoped>
.chart-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
.card-title { font-weight: 700; color: #4a5568; margin-bottom: 15px; text-transform: uppercase; font-size: 13px; }
.canvas-wrapper { position: relative; height: 300px; width: 100%; }
.wide { grid-column: span 2; } 
@media (max-width: 800px) { .wide { grid-column: span 1; } }
</style>