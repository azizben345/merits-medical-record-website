<template>
  <div class="trend-section">
    <div class="section-header">
      <div class="header-left">
        <h3>Historical Risk Trends</h3>
        <span class="subtext">Count of abnormal parameters among staff over time (%)</span>
      </div>
      
      <div class="header-right range-selector">
        <div class="field">
          <label>From:</label>
          <select v-model="startYear" @change="onRangeChange">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="field">
          <label>To:</label>
          <select v-model="endYear" @change="onRangeChange">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="field" style="pointer-events: none; opacity: 0.5; margin-bottom: 7px;">
          <!-- <label>Range (Years):</label> -->
          <small style="font-family: 'Courier New', Courier, monospace;">
            ({{ endYear - startYear + 1 }} yrs)
          </small>
        </div>
        <button class="refresh-btn" @click="loadData" :disabled="loading">
          {{ loading ? '...' : 'Apply' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">Loading trend data...</div>
    <div v-else-if="!series || series.length === 0" class="loading-state">
      No trend data found between {{ startYear }} and {{ endYear }}.
    </div>

    <div v-else class="charts-grid">
      
      <div class="chart-card">
        <div class="chart-title">Obesity (>25 BMI)</div>
        <div class="canvas-wrapper"><canvas ref="bmiCanvas"></canvas></div>
      </div>

      <div class="chart-card">
        <div class="chart-title">Hypertension (>140/90)</div>
        <div class="canvas-wrapper"><canvas ref="bpCanvas"></canvas></div>
      </div>

      <div class="chart-card">
        <div class="chart-title">High BPM (>100 bpm)</div>
        <div class="canvas-wrapper"><canvas ref="pulseCanvas"></canvas></div>
      </div>

      <div class="chart-card wide">
        <div class="card-header-row">
          <div class="chart-title">BMI Category Composition</div>
          <button class="toggle-btn" @click="toggleStackMode">
            {{ isStacked ? 'Switch to Side-by-Side' : 'Switch to Stacked' }}
          </button>
        </div>
        <div class="canvas-wrapper"><canvas ref="bmiStackCanvas"></canvas></div>
      </div>

    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// REGISTER IT GLOBALLY (or locally inside render)
Chart.register(ChartDataLabels);

export default {
  name: 'ChartsTrendSection',
  data() {
    return {
      // Range State
      startYear: null,
      endYear: null, 
      yearOptions: [], 
      series: [],
      loading: false,
      charts: { bmi: null, bp: null, pulse: null, bmiStack: null },
      isStacked: true,
    };
  },
  async mounted() {
    await this.loadAvailableYears();
    this.loadData();
  },
  beforeUnmount() {
    this.destroyCharts();
  },
  methods: {
    // TOGGLE FUNCTION
    toggleStackMode() {
      this.isStacked = !this.isStacked;
      // Re-render just this chart (we pass the saved years/data from memory)
      const years = this.series.map(d => d.year_label);
      this.renderBMIStack(years);
    },

    async loadAvailableYears() {
      try {
        const res = await fetch(`${cfg.API_BASE_URL}/admin/stats/available-years`, {
          headers: { Authorization: `bearer ${localStorage.getItem('jwt_token')}` }
        });
        const json = await res.json();
        
        // Extract years (assuming API returns { "years": [...] })
        const yearsFromDB = (json.years || []).map(y => Number(y.year));
        
        if (yearsFromDB.length > 0) {
            // Sort Descending (2026, 2025, 2024...)
            this.yearOptions = yearsFromDB.sort((a, b) => b - a);

            // Auto-select range: 
            // End = Latest Year
            // Start = Earliest Year (or Latest - 5 if there are too many)
            this.endYear = this.yearOptions[0];
            this.startYear = this.yearOptions[this.yearOptions.length - 1]; 
            
            // Optional: Limit default range to last 5 years to avoid clutter
            // if (this.yearOptions.length > 5) {
            //    this.startYear = this.yearOptions[4]; 
            // }
        } else {
            // Fallback if DB is empty
            const current = new Date().getFullYear();
            this.yearOptions = [current];
            this.startYear = current;
            this.endYear = current;
        }

      } catch (e) {
        console.error("Failed to load years", e);
      }
    },

    generateYearOptions() {
      const current = new Date().getFullYear();
      for (let y = current; y >= 2020; y--) {
        this.yearOptions.push(y);
      }
    },

    onRangeChange() {
      // Swap if Start > End
      if (this.startYear > this.endYear) {
        const temp = this.startYear;
        this.startYear = this.endYear;
        this.endYear = temp;
      }
      this.loadData();
    },

    async loadData() {
      this.loading = true;
      try {
        // NEW URL PARAMETERS
        const url = `${cfg.API_BASE_URL}/admin/stats/trends-risk?start_year=${this.startYear}&end_year=${this.endYear}`;
        
        const res = await fetch(url, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });

        if (handleUnauthorized(res)) return;
        const json = await res.json();
        
        this.series = json.series || [];

        if (this.series.length > 0) {
          setTimeout(() => { this.renderAllCharts(); }, 100); 
        } else {
            // If empty, clean up old charts
            this.destroyCharts();
        }

      } catch (e) {
        console.error("Trend Error", e);
      } finally {
        this.loading = false;
      }
    },

    renderAllCharts() {
        if (!this.$refs.bmiCanvas) return;
        this.destroyCharts();

        const years = this.series.map(d => d.year_label);
        const totals = this.series.map(d => Number(d.total_staff || 0));

        // Pass 'totals' as argument
        this.renderBMIStack(years);

        this.renderLine('bmi', this.$refs.bmiCanvas, years, 
            this.series.map(d => Number(d.risk_bmi || 0)), '#f59e0b', 'BMI > 25', totals);

        this.renderLine('bp', this.$refs.bpCanvas, years, 
            this.series.map(d => Number(d.risk_bp || 0)), '#ef4444', 'High BP', totals);

        this.renderLine('pulse', this.$refs.pulseCanvas, years, 
            this.series.map(d => Number(d.risk_pulse || 0)), '#3b82f6', 'High Pulse', totals);
    },

    renderBMIStack(labels) {
      if (!this.$refs.bmiStackCanvas) return;
      if (this.charts.bmiStack) this.charts.bmiStack.destroy();

      const ctx = this.$refs.bmiStackCanvas.getContext('2d');

      // 1. Math: Divide by TOTAL STAFF (including those with no BMI)
      const getPct = (key, row) => {
          const count = Number(row[key] || 0);
          const total = Number(row.total_staff || 1); 
          return ((count / total) * 100).toFixed(1);
      };

      // 2. Prepare Data Arrays
      const under = this.series.map(d => getPct('count_under', d));
      const normal = this.series.map(d => getPct('count_normal', d));
      const over = this.series.map(d => getPct('count_over', d));
      const unknown = this.series.map(d => getPct('count_unknown', d)); // NEW

      // 3. Prepare Raw Counts
      const cUnder = this.series.map(d => Number(d.count_under || 0));
      const cNormal = this.series.map(d => Number(d.count_normal || 0));
      const cOver = this.series.map(d => Number(d.count_over || 0));
      const cUnknown = this.series.map(d => Number(d.count_unknown || 0)); // NEW

      this.charts.bmiStack = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { 
                    label: 'Underweight', 
                    data: under, 
                    rawCounts: cUnder, 
                    backgroundColor: '#63b3ed' 
                },
                { 
                    label: 'Normal', 
                    data: normal, 
                    rawCounts: cNormal, 
                    backgroundColor: '#48bb78' 
                },
                { 
                    label: 'Overweight/Obese', 
                    data: over, 
                    rawCounts: cOver, 
                    backgroundColor: '#f56565' 
                },
                // NEW DATASET: UNKNOWN
                { 
                    label: 'Not Recorded', 
                    data: unknown, 
                    rawCounts: cUnknown, 
                    backgroundColor: '#cbd5e0' // Grey color
                }
            ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true, position: 'bottom' },
            tooltip: {
              callbacks: {
                // Tooltip shows both: "Normal: 15 Staff (60%)"
                label: (ctx) => {
                  const raw = ctx.dataset.rawCounts[ctx.dataIndex];
                  return `${ctx.dataset.label}: ${raw} Staff (${ctx.formattedValue}%)`;
                }
              }
            },
            datalabels: {
              display: false,
              color: 'white',
              font: { weight: 'bold', size: 10 },
              // 4. DISPLAY RAW COUNT INSIDE BAR
              formatter: (val, ctx) => {
                const count = ctx.dataset.rawCounts[ctx.dataIndex];
                return count > 0 ? count : ''; 
              }
            }
          },
          scales: {
            x: { 
              // 5. CONTROL STACKING HERE
              stacked: this.isStacked, 
              grid: { display: false } 
            },
            y: { 
              // 5. CONTROL STACKING HERE
              stacked: this.isStacked,
              beginAtZero: true,
              max: this.isStacked ? 100 : null, // If stacked, cap at 100%
              title: { display: true, text: '% of Total Staff' },
              ticks: { callback: v => v + '%' }
            }
          }
        }
      });
    },

    // argument at the end: 'totals'
    renderLine(key, canvas, labels, data, color, tooltipLabel, totals) {
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      
      this.charts[key] = new Chart(ctx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
                label: 'Risk %',
                data,
                // NEW: Store the totals array here so the formatter can access it
                totalStaff: totals, 
                borderColor: color,
                backgroundColor: color + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#fff',
                pointBorderColor: color
            }]
          },
          options: {
            responsive: true, 
            maintainAspectRatio: false,
            layout: { padding: { top: 25 } },
            plugins: {
                legend: { display: false },
                tooltip: { 
                  enabled: true,
                  callbacks: { 
                      // Tooltip can show both: "BMI > 25: 5 Staff (15%)"
                      label: (c) => {
                      const count = Math.round((c.raw / 100) * c.dataset.totalStaff[c.dataIndex]);
                      return `${tooltipLabel}: ${count} Staff (${c.raw}%)`;
                      }
                  }
                },
                datalabels: {
                  align: 'top',
                  anchor: 'center',
                  offset: 4,
                  color: '#334155',
                  font: { weight: 'bold', size: 11 },
                  // THIS IS THE KEY CHANGE
                  formatter: (value, context) => {
                      // value is %, we need to convert back to count
                      // Formula: (Percentage / 100) * Total Staff
                      const total = context.dataset.totalStaff[context.dataIndex];
                      const count = Math.round((value / 100) * total);
                      
                      return count > 0 ? count : ''; 
                  }
                }
            },
            scales: {
                y: { 
                beginAtZero: true, 
                suggestedMax: Math.max(...data) * 1.2,
                title: { display: true, text: '% of Staff' },
                ticks: { callback: v => v + '%' } 
                },
                x: { grid: { display: false } }
            }
          }
      });
    },

    destroyCharts() {
      Object.values(this.charts).forEach(c => c && c.destroy());
      this.charts = { bmi: null, bp: null, pulse: null };
    }
  }
};
</script>

<style scoped>
/* Range Selector Styles */
.range-selector { display: flex; align-items: flex-end; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 4px; }
.field label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
select { padding: 6px 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; min-width: 80px; }
.refresh-btn { padding: 7px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; height: 32px; }
.refresh-btn:hover { background: #1d4ed8; }

.trend-section { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
.section-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
h3 { margin: 0; color: #1e293b; font-size: 18px; }
.subtext { font-size: 13px; color: #64748b; }
.charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.chart-card { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; padding: 16px; }
.chart-title { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 12px; text-transform: uppercase; }
.canvas-wrapper { position: relative; height: 200px; width: 100%; }
.loading-state { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }
.wide { grid-column: span 3; }
.card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.toggle-btn { background: white; border: 1px solid #cbd5e0; color: #4a5568; font-size: 11px; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: 600; }
@media (max-width: 800px) { .wide { grid-column: span 1; } }
</style>