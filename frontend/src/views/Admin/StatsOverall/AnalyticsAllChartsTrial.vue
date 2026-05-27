<template>
  <div class="abnormality-dashboard">
    <h2>Company Lab Abnormality Dashboard (Latest Session per Staff)</h2>

    <div class="top-controls">

      <div class="summary">
        {{ latestSessionsCount }} unique staff checked • 
        <strong class="red" v-if="totalAbnormal > 0">{{ totalAbnormal }} abnormal results found</strong>
        <div class="loading" v-if="loading">Loading Data...</div>
      </div>

      <div class="filters">
        <!-- <button class="action-btn" @click="mounted">Refresh</button> -->
        <select style="width: 100%" v-model="selectedYear">
          <option :value="null">All Years</option>
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

    </div>

    <div class="groups-grid">
    
      <div class="group-card">
        <h3>Cholesterol Profile</h3>
        <canvas ref="cholesterolCanvas"></canvas>
      </div>

      <div class="group-card">
        <h3>Liver Function</h3>
        <canvas ref="liverCanvas"></canvas>
      </div>

      <div class="group-card">
        <h3>Glucose</h3>
        <canvas ref="glucoseCanvas"></canvas>
      </div>

      <div class="group-card">
        <h3>Renal Function</h3>
        <canvas ref="renalCanvas"></canvas>
      </div>

      <!-- General Investigations Chart -->
      <div class="group-card wide">
        <h3>General Investigations Results</h3>
        <canvas ref="investigationsCanvas"></canvas>
      </div>

      <div class="group-card wide">
        <h3>Other Important Tests</h3>
        <canvas ref="otherCanvas"></canvas>
      </div>

    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import Chart from 'chart.js/auto';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

const GROUPS = {
  cholesterol: {
    title: 'Cholesterol Profile',
    tests: [
      { label: 'LDL (>3.4)', field: 'ldl_result' },
      { label: 'Total Chol (>5.2)', field: 'tchol_result' },
      { label: 'HDL (<1.0 M / <1.3 F)', field: 'hdl_result' },
      { label: 'Triglycerides (>1.7)', field: 'tg_result' },
    ],
    color: '#f97316'
  },
  liver: {
    title: 'Liver Function',
    tests: [
      { label: 'ALT (<66)', field: 'alt_result' },
      { label: 'AST (<51)', field: 'ast_result' },
      { label: 'GGT (<51)', field: 'ggt_result' },
      { label: 'Bilirubin (>21)', field: 'tbil_result' },
    ],
    color: '#dc2626'
  },
  glucose: {
    title: 'Glucose',
    tests: [
      { label: 'FBS (>5.6)', field: 'fbs_result' },
      { label: 'RBS (>7.8)', field: 'rbs_result' },
    ],
    color: '#7c3aed'
  },
  renal: {
    title: 'Renal Function',
    tests: [
      { label: 'Creatinine (>115)', field: 'creat_result' },
      { label: 'Urea (>7.1)', field: 'bu_result' },
      { label: 'Uric Acid (>420)', field: 'ua_result' },
    ],
    color: '#0891b2'
  },
  other: {
    title: 'Other Important Tests',
    tests: [
      { label: 'Sodium (≠135-145)', field: 'na_result' },
      { label: 'Potassium (≠3.5-5.0)', field: 'k_result' },
      { label: 'Chloride (≠98-107)', field: 'cl_result' },
    ],
    color: '#6b7280'
  }
};

export default {
  data() {
    return {
      selectedYear: null,
      latestPerStaff: [], 
      investigationsData: {}, // Store investigation data
      charts: {
        investigations: null
      },
      loading: true
    };
  },
  computed: {
    years() {
      const y = [...new Set(this.latestPerStaff
        .filter(r => r.session_date)
        .map(r => new Date(r.session_date).getFullYear())
      )];
      return y.sort((a,b) => b-a);
    },
    filtered() {
      if (!this.selectedYear) return this.latestPerStaff;
      return this.latestPerStaff.filter(r => new Date(r.session_date).getFullYear() === this.selectedYear);
    },
    latestSessionsCount() { return this.filtered.length; },
    totalAbnormal() {
      let count = 0;
      Object.values(GROUPS).forEach(g => {
        g.tests.forEach(t => {
          count += this.filtered.filter(r => r[t.field]?.toLowerCase() === 'abnormal').length;
        });
      });
      return count;
    }
  },
  watch: {
    selectedYear() { this.loadAllData(); } // Reload all data when year changes
  },
  async mounted() {
    await this.loadAllData();
    // const latestYear = this.years.length ? this.years[0] : null;
    // if (latestYear !== this.selectedYear) { this.selectedYear = latestYear; }
    // this.renderAll(); // Removed: loadAllData now calls renderAll internally
  },
  methods: {
    async loadAllData() {
      this.loading = true;
      const headers = { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };
      
      // Pass year param if selected
      const yearParam = this.selectedYear ? `?year=${this.selectedYear}` : '';

      try {
        const [labRes, invRes] = await Promise.all([
            // Standard Lab Data (uses 'latest-lab-per-staff' which needs year handling in backend ideally, 
            // or we filter locally as done in computed property)
            // Note: For consistent filtering, ensure backend supports year param if 'latestPerStaff' needs to respect it on fetch.
            // Currently we filter locally for labs, so fetching all is fine.
            fetch(`${cfg.API_BASE_URL}/admin/stats/latest-lab-per-staff`, { headers }),
            
            // Investigations Data (Backend handles filtering via ?year=...)
            fetch(`${cfg.API_BASE_URL}/admin/stats/investigations${yearParam}`, { headers })
        ]);

        if (handleUnauthorized(labRes) || handleUnauthorized(invRes)) return;
        if (!labRes.ok || !invRes.ok) throw new Error('Failed to fetch data');

        const labJson = await labRes.json();
        this.latestPerStaff = labJson.results || [];

        const invJson = await invRes.json();
        this.investigationsData = invJson.series || {};

        console.log("Data Loaded:", this.latestPerStaff.length, "staff records");
        
        // const latestYear = this.years.length ? this.years[0] : null;
        // if (latestYear !== this.selectedYear) { this.selectedYear = latestYear; }
        this.renderAll();

      } catch (e) {
        console.error("API Error:", e);
        this.latestPerStaff = [];
        this.investigationsData = {};
      }
      this.loading = false;
    },

    renderAll() {
      this.$nextTick(() => {
        this.destroyAll();
        
        Object.keys(GROUPS).forEach(key => {
            try {
                this.renderGroup(key);
            } catch (err) {
                console.error(`Failed to render chart for ${key}:`, err);
            }
        });

        this.renderInvestigations();
      });
    },

    destroyAll() {
      Object.values(this.charts).forEach(c => c?.destroy());
      this.charts = {};
    },

    // --- NEW: Investigations Chart (Percent + Count) ---
    renderInvestigations() {
      const ctx = this.$refs.investigationsCanvas?.getContext('2d'); if (!ctx) return;
      
      const raw = this.investigationsData;
      const labels = ['Spirometry', 'Audiometry', 'Chest X-Ray', 'ECG'];
      
      // 1. Get Raw Counts
      const rawNormal = labels.map(l => Number(raw[l]?.normal || 0));
      const rawAbnormal = labels.map(l => Number(raw[l]?.abnormal || 0));

      // 2. Helper to Calculate Percentages
      const getPct = (val, i) => {
        const total = rawNormal[i] + rawAbnormal[i];
        return total > 0 ? (val / total) * 100 : 0;
      };

      // 3. Convert data to Percentages for Chart Height
      const pctNormal = rawNormal.map((v, i) => getPct(v, i));
      const pctAbnormal = rawAbnormal.map((v, i) => getPct(v, i));

      this.charts.investigations = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Normal',
              data: pctNormal, // Height is %
              backgroundColor: '#34d399', // Green
              categoryPercentage: 0.8,
              barPercentage: 1.0, // Touching bars
              rawCounts: rawNormal // Store counts
            },
            {
              label: 'Abnormal',
              data: pctAbnormal, // Height is %
              backgroundColor: '#ef4444', // Red
              categoryPercentage: 0.8,
              barPercentage: 1.0,
              rawCounts: rawAbnormal // Store counts
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: (c) => {
                        const count = c.dataset.rawCounts[c.dataIndex];
                        return `${c.dataset.label}: ${count} (${c.raw.toFixed(1)}%)`;
                    }
                }
            },
            datalabels: {
              labels: {
                // Label A: Percent (Top)
                percent: {
                  anchor: 'end',
                  align: 'end',
                  color: '#374151',
                  font: { weight: 'bold', size: 11 },
                  formatter: (val) => val > 0 ? val.toFixed(1) + '%' : ''
                },
                // Label B: Count (Inside)
                value: {
                  anchor: 'end',
                  align: 'start', // Just inside the top edge
                  offset: 4,      // Push down slightly
                  color: '#ffffff', // White text for contrast
                  font: { weight: 'bold', size: 12 },
                  formatter: (val, ctx) => {
                      const count = ctx.dataset.rawCounts[ctx.dataIndex];
                      return count > 0 ? count : '';
                  }
                }
              }
            }
          },
          scales: {
            y: { 
                beginAtZero: true, 
                max: 120, 
                title: { display: true, text: 'Percentage (%)' },
                ticks: { callback: v => v + '%' } 
            },
            x: { grid: { display: false } }
          }
        }
      });
    },

    renderGroup(groupKey) {
      const group = GROUPS[groupKey];
      const canvas = this.$refs[groupKey + 'Canvas'];

      if (!canvas) {
        console.warn(`CANVAS MISSING: Could not find <canvas ref="${groupKey}Canvas">`);
        return;
      }

      const data = this.filtered;
      const total = data.length || 1;

      const labels = [];
      const counts = [];
      const percents = [];

      group.tests.forEach(t => {
        const abnormal = data.filter(r => r[t.field]?.toLowerCase() === 'abnormal').length;
        labels.push(t.label);
        counts.push(abnormal);
        percents.push(Number((abnormal / total * 100).toFixed(1)));
      });

      this.charts[groupKey] = new Chart(canvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data: percents,
            backgroundColor: group.color + 'cc',
            borderColor: group.color,
            borderWidth: 2,
            borderRadius: 8,
            barThickness: 32
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { 
                callbacks: { 
                    label: c => `${c.parsed.x}% (${counts[c.dataIndex]} staff)` 
                } 
            },
            datalabels: {
              anchor: 'end',
              align: 'end',
              color: '#1a1a1a',
              font: { weight: 'bold' },
              formatter: (val, ctx) => {
                 return counts[ctx.dataIndex] > 0 ? counts[ctx.dataIndex] : '';
              }
            }
          },
          scales: {
            x: { 
                max: 100, 
                ticks: { callback: v => v + '%' },
                beginAtZero: true 
            },
            y: { ticks: { font: { size: 12 } } }
          }
        }
      });
    }
  },
  beforeUnmount() { this.destroyAll(); }
};
</script>

<style scoped>
.abnormality-dashboard { padding: 24px; background: #f9fafb; min-height: 100vh; }
h2 { text-align: center; margin-bottom: 12px; color: #1e293b; }
.top-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 16px; }
.summary { font-size: 18px; font-weight: 600; }
.red { color: #dc2626; }

.groups-grid {
  display: grid;
  /* grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); */
  grid-template-columns: 1fr;
  gap: 24px;
}
@media (min-width: 768px) {
  .groups-grid {
    grid-template-columns: 1fr 1fr;
  }
} 
@media (min-width: 1600px) {
  .groups-grid {
    grid-template-columns: 1fr 1fr 1fr 1fr;
  }
}
.group-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  width: 100%;
  min-width: 0;
}
.group-card h3 {
  text-align: center;
  margin: 0 0 20px 0;
  color: #1e293b;
  font-size: 18px;
}
.wide { grid-column: 1 / -1; }

canvas { height: 220px !important; }
</style>