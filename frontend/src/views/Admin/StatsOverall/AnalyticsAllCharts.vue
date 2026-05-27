<template>
  <div class="analytics-charts">
    <div class="header-row">
      <h2>Health Statistics Charts</h2>
    </div>

    <div class="section-container">
      <div class="section-header">
        <h3>1. Historical Trends (Over Time)</h3>
        <div class="controls">
          <label>Range:</label>
          <select v-model="trendRange" @change="loadTrendData">
            <option :value="3">Last 3 Years</option>
            <option :value="5">Last 5 Years</option>
            <option :value="10">Last 10 Years</option>
          </select>
        </div>
      </div>

      <div class="grid-trends">
        <div class="card">
          <div class="card-title">BMI Trend (Yearly Avg)</div>
          <div class="chart-wrapper">
            <canvas ref="bmiCanvas"></canvas>
          </div>
        </div>
        
        <div class="card">
          <div class="card-title">Blood Pressure Trend (Yearly Avg)</div>
          <div class="chart-wrapper">
            <canvas ref="bpCanvas"></canvas>
          </div>
        </div>
        
        <div class="card">
          <div class="card-title">Pulse Trend (Yearly Avg)</div>
          <div class="chart-wrapper">
            <canvas ref="pulseCanvas"></canvas>
          </div>
        </div>
      </div>
    </div>

    <hr class="divider" />

    <div class="section-container">
      <div class="section-header">
        <h3>2. Yearly Snapshot (Cohort View)</h3>
        <div class="controls">
          <label>Select Year:</label>
          <select v-model="selectedYear" @change="loadSnapshotData" :disabled="loadingYears">
            <option v-for="y in availableYears" :key="y.year" :value="y.year">
              {{ y.year }} ({{ y.count }} sessions)
            </option>
          </select>
        </div>
      </div>

      <div class="band-controls">
        <span class="ctrl-label">BMI Standards:</span>
        <div class="band-item">
          <label>Underweight Max:</label>
          <input type="number" step="0.1" v-model.number="bmiThresholds.lowMax" @change="onThresholdChange" />
        </div>
        <div class="band-item">
          <label>Ideal Max:</label>
          <input type="number" step="0.1" v-model.number="bmiThresholds.fitMax" @change="onThresholdChange" />
        </div>
      </div>

      <div class="grid-snapshots">
        
        <div class="card wide">
          <div class="card-title">BMI Breakdown ({{ selectedYear }})</div>
          <div class="chart-wrapper wide-chart">
            <canvas ref="bmiBarCanvas"></canvas>
          </div>
        </div>

        <!-- <div class="card wide">
          <div class="card-title">Cholesterol Status ({{ selectedYear }})</div>
          <div class="chart-wrapper wide-chart">
            <canvas ref="cholBarCanvas"></canvas>
          </div>
        </div> -->

        <div class="card wide">
          <div class="card-title">BMI Distribution Top 30 ({{ selectedYear }})</div>
          <div class="chart-wrapper wide-chart">
            <canvas ref="bmiScatterCanvas"></canvas>
          </div>
          <div class="muted" v-if="!bmiPoints.length" style="margin-top:8px;">No BMI data found for this year.</div>
        </div>

        <div class="card">
          <div class="card-title">Smoker Status ({{ selectedYear }})</div>
          <div class="chart-wrapper">
            <canvas ref="smokerStatusCanvas"></canvas>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import {
  Chart, LineController, BarController, LineElement, BarElement, PointElement,
  LinearScale, CategoryScale, Tooltip, Legend, Filler, Title, ScatterController
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(
  LineController, BarController, LineElement, BarElement, PointElement, 
  LinearScale, CategoryScale, Tooltip, Legend, Filler, Title, ScatterController,
  ChartDataLabels
);

export default {
  name: 'AnalyticsAllCharts',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      
      // Filter State
      trendRange: 5,         // Years for trend history
      selectedYear: null,    // Selected Yearly Snapshot
      availableYears: [],    // List from backend
      loadingYears: false,

      // Dynamic Thresholds
      bmiThresholds: { lowMax: 20.0, fitMax: 24.9 },

      // Data Buckets
      trendSeries: [],       // Vitals (BMI, BP, Pulse)
      
      snapshotBMI: [],       // BMI Bar Data
      snapshotChol: [],      // Cholesterol Bar Data
      snapshotSmoker: [],    // Smoker Bar Data
      bmiPoints: [],         // Scatter Plot Data

      charts: {
        bmi: null, bp: null, pulse: null, // Trends
        bmiBar: null, cholBar: null, smoker: null, bmiScatter: null // Snapshots
      },

      abortController: null,
      scatterDebounce: null
    };
  },
  async mounted() {
    await this.loadAvailableYears(); 
    this.loadTrendData();            
    window.addEventListener('resize', this.resizeCharts);
  },
  beforeUnmount() {
    this.destroyCharts();
    window.removeEventListener('resize', this.resizeCharts);
    if (this.abortController) this.abortController.abort();
  },
  methods: {
    // ---------------------------------------------------------
    // 1. DATA LOADING
    // ---------------------------------------------------------
    
    // A. Fetch Available Years
    async loadAvailableYears() {
      this.loadingYears = true;
      try {
        const res = await fetch(`${this.baseUrl}/admin/stats/available-years`, {
          headers: this.authHeader()
        });
        if (handleUnauthorized(res)) return;
        const json = await res.json();
        this.availableYears = json.years || [];
        
        // Auto-select most recent year
        if (this.availableYears.length > 0) {
          this.selectedYear = this.availableYears[0].year;
          this.loadSnapshotData();
        }
      } catch(e) { console.error("Failed to load years", e); } 
      finally { this.loadingYears = false; }
    },

    // B. Fetch Historical Trends (Line Charts)
    async loadTrendData() {
      const pastDate = new Date();
      pastDate.setFullYear(new Date().getFullYear() - this.trendRange);
      const fromStr = pastDate.toISOString().slice(0, 10);
      
      try {
        const res = await fetch(`${this.baseUrl}/admin/stats/vitals?from=${fromStr}`, { 
          headers: this.authHeader() 
        });
        const json = await res.json();
        this.trendSeries = json.series || [];

        this.$nextTick(() => {
          this.renderBMI(); 
          this.renderBP(); 
          this.renderPulse();
        });
      } catch (e) { console.error("Trend Load Error", e); }
    },

    // C. Fetch Snapshot Data (Bars & Scatter)
    async loadSnapshotData() {
      if (!this.selectedYear) return;
      
      if (this.abortController) this.abortController.abort();
      this.abortController = new AbortController();
      const signal = this.abortController.signal;

      // Helper to fetch and render independently
      const fetchAndRender = async (promise, dataKey, renderMethod) => {
        try {
          const res = await promise;
          // Check for HTTP errors (404/500) manually
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          
          const json = await res.json();
          this[dataKey] = json.series || [];
          
          // Render immediately on next tick
          this.$nextTick(() => {
            if (this[renderMethod]) this[renderMethod]();
          });
        } catch (e) {
          if (e.name !== 'AbortError') console.error(`Failed to load ${dataKey}`, e);
        }
      };

      // 1. Trigger Independent Fetches (Do not await them all together)
      const bmiParams = `year=${this.selectedYear}&max_under=${this.bmiThresholds.lowMax}&max_ideal=${this.bmiThresholds.fitMax}`;
      const yearParam = `year=${this.selectedYear}`;

      // Fire and forget - they run in parallel but fail independently
      fetchAndRender(
        fetch(`${this.baseUrl}/admin/stats/vitals-2?groupBy=year_latest&${bmiParams}`, { headers: this.authHeader(), signal }),
        'snapshotBMI',
        'renderBMIBar'
      );

      fetchAndRender(
        fetch(`${this.baseUrl}/admin/stats/vitals-2?groupBy=year_latest_chol&${yearParam}`, { headers: this.authHeader(), signal }),
        'snapshotChol',
        'renderCholBar'
      );

      fetchAndRender(
        fetch(`${this.baseUrl}/admin/stats/lifestyle-smokerstatus?${yearParam}`, { headers: this.authHeader(), signal }),
        'snapshotSmoker',
        'renderSmoker'
      );

      // 2. Handle Scatter separately (it's complex)
      this.loadScatterPoints().then(() => {
        this.$nextTick(() => this.renderScatter());
      }).catch(e => console.error("Scatter failed", e));
    },

    // D. Fetch Scatter Points
    async loadScatterPoints() {
      try {
        // Reuse summary endpoint to get list of staff
        const summaryRes = await fetch(`${this.baseUrl}/admin/stats/summary?groupBy=month`, { headers: this.authHeader() });
        const summaryJson = await summaryRes.json();
        
        const MAX_SCATTER_STAFF = 30;
        const top = (summaryJson?.top_staff_by_sessions || []).slice(0, MAX_SCATTER_STAFF);
        const emails = Array.from(new Set(top.map(t => t.staff_email)));

        const chunkArray = (arr, size) => {
          const results = [];
          while (arr.length) { results.push(arr.splice(0, size)); }
          return results;
        };
        
        const emailChunks = chunkArray([...emails], 5);
        let validPoints = [];

        for (const chunk of emailChunks) {
          const chunkResults = await Promise.all(chunk.map(async (email) => {
            const staffXYZ = email.replace(/\./g, 'XYZ');
            // NOTE: Ideally, make a bulk endpoint for this. For now, fetch individual.
            const res = await fetch(`${this.baseUrl}/stats/vitals/${staffXYZ}`, { headers: this.authHeader() });
            const rows = await res.json();
            
            if (!Array.isArray(rows) || !rows.length) return null;

            // Strict Filter: Must be in the Selected Year
            // We convert session_date (YYYY-MM-DD) to string and check strict year match
            const yearRows = rows.filter(r => String(r.session_date).startsWith(String(this.selectedYear)));
            if (!yearRows.length) return null;

            // Get Latest in that year
            yearRows.sort((a,b) => new Date(a.session_date) - new Date(b.session_date));
            const latest = yearRows.at(-1);

            const bmi = Number(latest.bmi);
            if (!bmi) return null;

            return { email, bmi, date: latest.session_date };
          }));
          validPoints = [...validPoints, ...chunkResults.filter(Boolean)];
        }

        this.bmiPoints = validPoints.map((p, i) => ({
          x: i + 1, 
          y: p.bmi,
          email: p.email,
          date: p.date
        }));

      } catch (e) { console.error("Scatter Load Error", e); }
    },

    // ---------------------------------------------------------
    // 2. HELPERS
    // ---------------------------------------------------------
    authHeader() {
      return { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };
    },
    
    onThresholdChange() {
      this.loadSnapshotData();
    },

    labelFromBucket(bucket) {
      return String(bucket || '').slice(0, 7);
    },

    destroyCharts() {
      Object.keys(this.charts).forEach(k => {
        if (this.charts[k]) { this.charts[k].destroy(); this.charts[k] = null; }
      });
    },
    resizeCharts() {
      Object.values(this.charts).forEach(c => c && c.resize());
    },

    // ---------------------------------------------------------
    // 3. CHART RENDERERS (SNAPSHOTS)
    // ---------------------------------------------------------

    renderBMIBar() {
      const ctx = this.$refs.bmiBarCanvas?.getContext('2d');
      if (!ctx) return;
      if (this.charts.bmiBar) this.charts.bmiBar.destroy();

      // SAFETY CHECK: Ensure we have data before trying to access properties
      const row = (this.snapshotBMI && this.snapshotBMI.length > 0) 
                  ? this.snapshotBMI[0] 
                  : { count_low: 0, count_mid: 0, count_high: 0 }; 

      const rawUnder = Number(row.count_low || 0);
      const rawIdeal = Number(row.count_mid || 0);
      const rawOver  = Number(row.count_high || 0);
      const total = rawUnder + rawIdeal + rawOver;

      const getPct = (val) => total > 0 ? (val / total) * 100 : 0;

      this.charts.bmiBar = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [this.selectedYear],
          datasets: [
            { 
              label: `Underweight (≤ ${this.bmiThresholds.lowMax})`, 
              data: [getPct(rawUnder)], 
              backgroundColor: '#63b3ed', rawCount: rawUnder 
            },
            { 
              label: `Ideal (${this.bmiThresholds.lowMax} - ${this.bmiThresholds.fitMax})`, 
              data: [getPct(rawIdeal)], 
              backgroundColor: '#48bb78', rawCount: rawIdeal 
            },
            { 
              label: `Overweight (> ${this.bmiThresholds.fitMax})`, 
              data: [getPct(rawOver)], 
              backgroundColor: '#f56565', rawCount: rawOver 
            }
          ]
        },
        options: this.getBarOptions('Percentage of Staff (%)')
      });
    },

    renderCholBar() {
      const ctx = this.$refs.cholBarCanvas?.getContext('2d'); if (!ctx) return;
      if (this.charts.cholBar) this.charts.cholBar.destroy();

      const row = this.snapshotChol[0] || {};
      const rawNormal = Number(row.count_low || 0); // < 5.2
      const rawHigh = Number(row.count_high || 0);  // >= 5.2
      const total = rawNormal + rawHigh;
      
      const getPct = (val) => total > 0 ? (val / total) * 100 : 0;

      this.charts.cholBar = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [this.selectedYear],
          datasets: [
            { label: 'Desirable (< 5.2)', data: [getPct(rawNormal)], backgroundColor: '#48bb78', rawCount: rawNormal },
            { label: 'High (≥ 5.2)', data: [getPct(rawHigh)], backgroundColor: '#f56565', rawCount: rawHigh }
          ]
        },
        options: this.getBarOptions('Percentage of Staff (%)')
      });
    },

    renderSmoker() {
      const ctx = this.$refs.smokerStatusCanvas?.getContext('2d'); if (!ctx) return;
      if (this.charts.smoker) this.charts.smoker.destroy();

      const statuses = ['never smoked', 'ex-smoker', 'current smoker'];
      const counts = statuses.map(s => {
        const r = this.snapshotSmoker.find(row => row.status === s);
        return r ? Number(r.count) : 0;
      });

      this.charts.smoker = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: statuses,
          datasets: [{
            label: 'Count', data: counts,
            backgroundColor: ['#34d399', '#fbbf24', '#ef4444'],
            borderWidth: 1
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'end', color: '#444', font: { weight: 'bold' } } },
          scales: { x: { display: false }, y: { grid: { display: false } } }
        }
      });
    },

    renderScatter() {
      const ctx = this.$refs.bmiScatterCanvas?.getContext('2d'); if (!ctx) return;
      if (this.charts.bmiScatter) this.charts.bmiScatter.destroy();

      const { lowMax, fitMax } = this.bmiThresholds;
      const low = [], fit = [], exceed = [];
      const labels = [];

      this.bmiPoints.forEach((p, i) => {
        labels[i] = p.email;
        const pt = { x: i + 1, y: p.y };
        if (p.y <= lowMax) low.push(pt);
        else if (p.y <= fitMax) fit.push(pt);
        else exceed.push(pt);
      });

      // Threshold Lines Plugin
      const thresholdLines = {
        id: 'thresholdLines',
        afterDraw: (chart) => {
          const { ctx, chartArea, scales } = chart;
          if (!scales.y) return;
          const drawLine = (val) => {
            const y = scales.y.getPixelForValue(val);
            if (y < chartArea.top || y > chartArea.bottom) return;
            ctx.save(); ctx.setLineDash([6, 4]); ctx.strokeStyle = '#718096'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(chartArea.left, y); ctx.lineTo(chartArea.right, y); ctx.stroke(); ctx.restore();
          };
          drawLine(lowMax);
          drawLine(fitMax);
        }
      };

      this.charts.bmiScatter = new Chart(ctx, {
        type: 'scatter',
        data: {
          datasets: [
            { label: 'Underweight', data: low, backgroundColor: '#63b3ed' },
            { label: 'Ideal', data: fit, backgroundColor: '#48bb78' },
            { label: 'Overweight', data: exceed, backgroundColor: '#f56565' }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { 
            legend: { display: true }, datalabels: { display: false },
            tooltip: { callbacks: { title: (items) => labels[(items[0].parsed.x) - 1], label: (item) => `BMI: ${item.parsed.y}` } }
          },
          scales: { 
            x: { title: { display: true, text: 'Staff Index' }, ticks: { stepSize: 1 } }, 
            y: { title: { display: true, text: 'BMI' } } 
          }
        },
        plugins: [thresholdLines]
      });
    },

    // ---------------------------------------------------------
    // 4. CHART RENDERERS (TRENDS)
    // ---------------------------------------------------------

    renderBMI() { this.renderLineChart('bmi', this.trendSeries, 'avg_bmi', 'BMI (avg)', '#3b82f6'); },
    renderPulse() { this.renderLineChart('pulse', this.trendSeries, 'avg_pulse_bpm', 'Pulse (bpm)', '#06b6d4'); },
    
    renderBP() {
      const ctx = this.$refs.bpCanvas?.getContext('2d'); if (!ctx) return;
      if (this.charts.bp) this.charts.bp.destroy();
      
      const labels = this.trendSeries.map(v => v.bucket);
      const sys = this.trendSeries.map(v => v.avg_bp_sys);
      const dia = this.trendSeries.map(v => v.avg_bp_dia);

      this.charts.bp = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Systolic', data: sys, borderColor: '#3b82f6', tension: 0.3, spanGaps: true },
            { label: 'Diastolic', data: dia, borderColor: '#ef4444', tension: 0.3, spanGaps: true }
          ]
        },
        options: this.getLineOptions('mmHg')
      });
    },

    // --- GENERIC HELPERS ---
    
    renderLineChart(refName, series, key, label, color) {
      const ctx = this.$refs[refName + 'Canvas']?.getContext('2d'); if (!ctx) return;
      if (this.charts[refName]) this.charts[refName].destroy();
      
      this.charts[refName] = new Chart(ctx, {
        type: 'line',
        data: {
          labels: series.map(v => v.bucket),
          datasets: [{ 
            label, 
            data: series.map(v => v[key]), 
            borderColor: color, 
            tension: 0.3, 
            fill: false,
            spanGaps: true // <--- FIX FOR NULL DATA GAPS
          }]
        },
        options: this.getLineOptions(label)
      });
    },

    getBarOptions(yTitle) {
      return {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'bottom' },
          datalabels: { 
            formatter: (val, ctx) => ctx.dataset.rawCount > 0 ? ctx.dataset.rawCount : '',
            color: '#333', anchor: 'center', align: 'center', font: { weight: 'bold' }
          }
        },
        scales: { y: { beginAtZero: true, title: { display: true, text: yTitle } }, x: { grid: { display: false } } }
      };
    },

    getLineOptions(yTitle) {
      return {
        responsive: true, maintainAspectRatio: false, layout: { padding: { top: 20 } },
        plugins: { 
          legend: { display: true }, 
          datalabels: { align: 'top', anchor: 'end', offset: 4, color: '#666', font: { size: 10 }, formatter: v => v ? Number(v).toFixed(1) : '' } 
        },
        scales: { y: { beginAtZero: false, title: { display: true, text: yTitle } } }
      };
    }
  }
};
</script>

<style scoped>
.analytics-charts { padding: 24px; }
.header-row { margin-bottom: 24px; }

/* Section Containers */
.section-container {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 30px;
}

.section-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e2e8f0;
}
.section-header h3 { margin: 0; color: #2d3748; font-size: 18px; }

/* Filter Controls */
.controls label { font-size: 14px; font-weight: 600; color: #64748b; margin-right: 8px; }
.controls select { padding: 6px 12px; border: 1px solid #cbd5e0; border-radius: 6px; min-width: 140px; }

/* Divider */
.divider { border: 0; border-top: 2px dashed #cbd5e0; margin: 40px 0; }

/* Grids */
.grid-trends { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.grid-snapshots { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; }

/* Cards & Charts */
.card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; }
.wide { grid-column: span 2; }
@media (max-width: 1000px) { .wide { grid-column: span 1; } }

.card-title { font-weight: 700; color: #4a5568; margin-bottom: 15px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }

/* --- THE INFINITE HEIGHT FIX --- */
.chart-wrapper {
  position: relative;
  width: 100%;
  height: 200px;
}
.wide-chart {
  height: 250px;
}

/* Band Controls */
.band-controls {
  display: flex; gap: 20px; align-items: center; margin-bottom: 20px;
  background: white; padding: 12px 20px; border-radius: 8px; border: 1px solid #e2e8f0; width: fit-content;
}
.ctrl-label { font-weight: 700; color: #2d3748; font-size: 14px; }
.band-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.band-item input { width: 70px; padding: 6px; border: 1px solid #cbd5e0; border-radius: 4px; text-align: center; font-weight: 600; }
.muted { color: #94a3b8; font-size: 13px; font-style: italic; }
</style>