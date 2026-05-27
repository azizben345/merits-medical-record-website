<template>
  <div class="dashboard">
    <h2>Statistics — Vitals Matrix</h2>

    <hr class="divider" />

    <div class="topbar">
      <div class="control-group">
        <label class="control-label">Session Date Range</label>
        <div class="date-row">
          <VueDatePicker 
            v-model="from"
            :max-date="new Date()" 
            :enable-time-picker="false"
            auto-apply
            placeholder="Start Date"
            class="my-picker"
          />
          <span class="sep">-</span>
          <VueDatePicker 
            v-model="to"
            :max-date="new Date()" 
            :enable-time-picker="false"
            auto-apply
            placeholder="End Date"
            class="my-picker"
          />
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">View Mode</label>
        <div class="switcher">
          <button 
            type="button"
            :class="{ active: scope === 'latest' }" 
            @click="scope = 'latest'"
          >
            Latest Only
          </button>
          <button 
            type="button"
            :class="{ active: scope === 'all' }" 
            @click="scope = 'all'"
          >
            All History
          </button>
        </div>
      </div>

      <div class="divider-v"></div>

      <label class="toggle-btn" :class="{ active: hideIncomplete }" style="height: 38px;">
        <input type="checkbox" v-model="hideIncomplete" />
        <span>Hide Incomplete</span>
      </label>
      
      <input
        v-model="q"
        type="search"
        placeholder="Search staff..."
        class="search"
      />

      <div class="legend">
        <button
          class="chip"
          :class="{active: activeYear === null}"
          @click="setYear(null)"
          title="Show all years"
        >
          <div class="chip-title">All</div>
          <div class="chip-sub">{{ matrixData.length }} Rows</div>
        </button>

        <button
          v-for="y in availableYears"
          :key="y"
          class="chip"
          :class="{active: activeYear === y}"
          @click="setYear(y)"
        >
          <div class="chip-title">{{ y }}</div>
          <div class="chip-sub">{{ countByYear[y] || 0 }} Rows</div>
        </button>
      </div>
    </div>

    <div class="stats-grid">
      
      <div class="stat-card info">
        <div class="stat-value">{{ filteredData.length }}</div>
        <div class="stat-label">Total {{ scope === 'latest' ? 'Unique Staff' : 'Records' }}</div>
      </div>

      <div class="stat-card" :class="stats.highBmi > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.highBmi }}</div>
        <div class="stat-label">High BMI (>25)</div>
      </div>

      <div class="stat-card" :class="stats.highBp > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.highBp }}</div>
        <div class="stat-label">High BP (>140/90)</div>
      </div>

      <div class="stat-card" :class="stats.highGlucose > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.highGlucose }}</div>
        <div class="stat-label">High Glucose (>7.0)</div>
      </div>

      <div class="stat-card" :class="stats.highChol > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.highChol }}</div>
        <div class="stat-label">High Cholesterol (>5.2)</div>
      </div>

      <div class="stat-card" :class="stats.highUric > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.highUric }}</div>
        <div class="stat-label">High Uric Acid (>425)</div>
      </div>

      <div class="stat-card" :class="stats.diabetes > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.diabetes }}</div>
        <div class="stat-label">Diabetes (Yes)</div>
      </div>

      <div class="stat-card" :class="stats.abnormalEcg > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.abnormalEcg }}</div>
        <div class="stat-label">Abnormal ECG</div>
      </div>

      <div class="stat-card" :class="stats.abnormalSpiro > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.abnormalSpiro }}</div>
        <div class="stat-label">Abnormal Spiro</div>
      </div>

      <div class="stat-card" :class="stats.abnormalAudio > 0 ? 'danger' : 'safe'">
        <div class="stat-value">{{ stats.abnormalAudio }}</div>
        <div class="stat-label">Abnormal Audio</div>
      </div>

    </div>

    <div class="panel">
      <div class="panel-title">
        <span>Vitals Data</span>
        <span v-if="loading" style="font-weight:400; font-size:13px; margin-left:10px;">(Updating...)</span>
      </div>
      
      <div v-if="loading && matrixData.length === 0" class="spinner-container">Loading data...</div>

      <div v-else class="table-responsive">
        <table>
          <thead>
            <tr>
              <th class="sticky-col">Staff Details</th>
              <th>Session Date</th>
              <th>BMI</th>
              <th>BP (mmHg)</th>
              
              <th>Glucose</th>
              <th>Cholest.</th>
              <th>Uric Acid</th>

              <th>Diabetes</th>
              <th>ECG</th>
              <th>Spiro</th>
              <th>Audio</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredData" :key="row.session_id">
              <td class="sticky-col">
                <div class="staff-info">
                  <span class="staff-name">{{ row.staff_name }}</span>
                  <span class="staff-email" v-if="scope === 'all'">{{ formatDate(row.session_date) }}</span>
                </div>
              </td>
              
              <td>
                <span class="year-badge">{{ formatDate(row.session_date) }}</span>
              </td>

              <td>
                <span :class="getValClass(row.bmi, 25, 'high')">{{ formatVal(row.bmi) }}</span>
              </td>
              <td>
                <span :class="getBpClass(row.bp_sys, row.bp_dia)">
                  {{ row.bp_sys || '-' }}/{{ row.bp_dia || '-' }}
                </span>
              </td>

              <td>
                <span :class="getValClass(row.glucose_val, 7.0, 'high')">{{ formatVal(row.glucose_val) }}</span>
              </td>
              <td>
                <span :class="getValClass(row.chol_val, 5.2, 'high')">{{ formatVal(row.chol_val) }}</span>
              </td>
              <td>
                <span :class="getValClass(row.uric_val, 425, 'high')">{{ formatVal(row.uric_val) }}</span>
              </td>

              <td>
                <span class="pill" :class="row.diabetes === 'Y' ? 'pill-red' : row.diabetes === 'N' ? 'pill-green' : 'pill-muted'">{{ row.diabetes === 'Y' ? 'Yes' : row.diabetes === 'N' ? 'No' : '-' }}</span>
              </td>
              
              <td><span class="pill" :class="getStatusClass(row.ecg_status)">{{ row.ecg_status || '-' }}</span></td>
              <td><span class="pill" :class="getStatusClass(row.spiro_status)">{{ row.spiro_status || '-' }}</span></td>
              <td><span class="pill" :class="getStatusClass(row.audio_status)">{{ row.audio_status || '-' }}</span></td>
            </tr>
            
            <tr v-if="filteredData.length === 0">
              <td colspan="11" class="empty">No records found for this selection.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  name: 'AnalyticsAllOverall',
  components: { VueDatePicker },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      matrixData: [],
      activeYear: null,
      q: '',
      loading: false,
      
      scope: 'latest',
      from: null,
      to: null,
      hideIncomplete: false,

      debounceTimer: null,
    };
  },
  computed: {
    filteredData() {
      let data = this.matrixData;

      if (this.hideIncomplete) {
        data = data.filter(r => {
          const criticalFields = [r.bmi, r.bp_sys, r.bp_dia, r.diabetes, r.ecg_status];
          return criticalFields.every(v => v !== null && v !== undefined && v !== '');
        });
      }

      if (this.activeYear) {
        data = data.filter(r => r.year_label == this.activeYear);
      }
      
      const search = this.q.toLowerCase().trim();
      if (search) {
        data = data.filter(r => 
          (r.staff_name || '').toLowerCase().includes(search) || 
          (r.staff_email || '').toLowerCase().includes(search)
        );
      }
      return data;
    },

    availableYears() {
      const years = new Set(this.matrixData.map(r => r.year_label).filter(Boolean));
      return Array.from(years).sort((a, b) => b - a);
    },

    // --- Updated Stats logic for the Grid ---
    stats() {
      const d = this.filteredData;
      return {
        highBmi: d.filter(r => r.bmi > 25).length,
        highBp: d.filter(r => (r.bp_sys > 140 || r.bp_dia > 90)).length,
        highGlucose: d.filter(r => r.glucose_val > 7.0).length,
        highChol: d.filter(r => r.chol_val > 5.2).length,
        highUric: d.filter(r => r.uric_val > 425).length,
        diabetes: d.filter(r => r.diabetes === 'Y').length,
        abnormalEcg: d.filter(r => r.ecg_status === 'Abnormal').length,
        abnormalSpiro: d.filter(r => r.spiro_status === 'Abnormal').length,
        abnormalAudio: d.filter(r => r.audio_status === 'Abnormal').length,
      }
    },

    countByYear() {
      const counts = {};
      this.matrixData.forEach(r => {
        const y = r.year_label;
        if (y) counts[y] = (counts[y] || 0) + 1;
      });
      return counts;
    }
  },
  watch: {
    from() { this.debouncedLoad(); },
    to() { this.debouncedLoad(); },
    scope() { this.debouncedLoad(); }
  },
  mounted() {
    this.loadMatrixData();
  },
  methods: {
    formatDate(dateVal) {
      if (!dateVal) return '-';
      const d = new Date(dateVal);
      if (isNaN(d.getTime())) return dateVal;
      return d.toISOString().split('T')[0];
    },

    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.loadMatrixData();
      }, 500); 
    },

    async loadMatrixData() {
      this.loading = true;

      const toApiDate = (date) => {
          if (!date) return null;
          if (typeof date === 'string') return date;
          try {
              const offset = date.getTimezoneOffset() * 60000;
              return (new Date(date - offset)).toISOString().slice(0, 10);
          } catch (e) { return null; }
      };

      try {
        let url = `${this.baseUrl}/admin/stats/staff-vitals-matrix?scope=${this.scope}&`;
        const fromStr = toApiDate(this.from);
        const toStr = toApiDate(this.to);

        if (fromStr) url += `from=${fromStr}&`;
        if (toStr) url += `to=${toStr}`;

        const res = await fetch(url, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        
        if (handleUnauthorized(res)) return;
        
        const json = await res.json();
        this.matrixData = json.items || [];
        this.activeYear = null;
      } catch (e) {
        console.error("Failed to load matrix", e);
      } finally {
        this.loading = false;
      }
    },

    setYear(y) { this.activeYear = y; },

    formatVal(val) {
      if (val === null || val === undefined) return '-';
      return Number(val).toFixed(1);
    },
    getValClass(val, threshold, type = 'high') {
      if (!val) return '';
      if (type === 'high' && val > threshold) return 'text-red bold';
      return '';
    },
    getBpClass(sys, dia) {
      if (!sys || !dia) return '';
      if (sys > 140 || dia > 90) return 'text-red bold';
      return '';
    },
    getStatusClass(status) {
      if (!status || status === 'Not done') return 'pill-grey';
      if (status === 'Normal') return 'pill-green';
      if (status === 'Abnormal') return 'pill-red';
      return 'pill-grey';
    }
  }
};
</script>

<style scoped>
.dashboard { padding: 24px; margin: 0 auto; }

/* --- NEW STATS GRID --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}

.stat-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  text-align: center;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  transition: transform 0.1s;
}

.stat-card:hover { transform: translateY(-2px); }

.stat-value {
  font-size: 24px;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
}

/* 1. INFO CARD (Neutral) */
.stat-card.info {
  border-color: #bfdbfe;
  background: #eff6ff;
}
.stat-card.info .stat-value { color: #2563eb; }
.stat-card.info .stat-label { color: #1e40af; }

/* 2. SAFE CARD (Green - 0 Abnormalities) */
.stat-card.safe {
  border-color: #bbf7d0;
  background: #f0fdf4;
}
.stat-card.safe .stat-value { color: #166534; } /* Dark Green */
.stat-card.safe .stat-label { color: #15803d; }

/* 3. DANGER CARD (Red - Has Abnormalities) */
.stat-card.danger {
  border-color: #fecdd3;
  background: #fff1f2;
}
.stat-card.danger .stat-value { color: #e11d48; } /* Red */
.stat-card.danger .stat-label { color: #9f1239; }

/* --- FILTERS & LAYOUT (Standard) --- */
.date-row { display: flex; align-items: center; gap: 8px; }
.my-picker { width: 150px; } 
.sep { color: #64748b; font-weight: bold; }

.control-group { display: flex; flex-direction: column; gap: 4px; }
.control-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; }

.switcher { display: flex; background: #f1f5f9; padding: 3px; border-radius: 6px; }
.switcher button {
  border: none; background: transparent; padding: 6px 12px; font-size: 12px; font-weight: 600;
  color: #64748b; cursor: pointer; border-radius: 4px; transition: all 0.2s;
}
.switcher button.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

.topbar { display:flex; gap:16px; flex-wrap:wrap; margin-bottom: 20px; align-items: flex-end; }
.divider-v { width: 1px; height: 32px; background: #e2e8f0; margin: 0 4px; }

.search { 
  min-width: 200px; padding: 9px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size: 13px;
}
.legend { display:flex; gap:8px; flex-wrap:wrap; margin-left: auto; }

.chip {
  border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:4px 10px;
  display:flex; flex-direction:column; min-width:60px; cursor:pointer; text-align: left;
}
.chip:hover { background: #f8fafc; }
.chip.active { background:#2c3e50; color:#fff; border-color:#2c3e50; }
.chip-title { font-weight: 700; font-size: 12px; }
.chip-sub { font-size: 10px; opacity: 0.8; }

.toggle-btn {
  display: flex; align-items: center; gap: 8px;
  background: #fff; border: 1px solid #e2e8f0; padding: 0 12px;
  border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600;
  user-select: none;
}
.toggle-btn.active { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }

.panel {
  background:#fff; border:1px solid #e2e8f0; border-radius:10px; 
  box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden;
}
.panel-title { 
  padding: 12px 20px; border-bottom: 1px solid #f1f5f9; 
  font-weight:700; color:#334155; font-size: 14px; background: #f8fafc;
  display: flex; align-items: center;
}

.spinner-container { padding: 40px; text-align: center; color: #64748b; }
.table-responsive { overflow-x: auto; max-height: 70vh; }

table { width:100%; border-collapse: collapse; font-size: 12px; }
th, td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; white-space: nowrap; }
th { background: #f8fafc; color: #475569; font-weight: 700; position: sticky; top: 0; z-index: 10; }

.sticky-col { 
  position: sticky; left: 0; background: #fff; z-index: 5; 
  border-right: 2px solid #f1f5f9; min-width: 160px;
}
th.sticky-col { z-index: 15; background: #f8fafc; }

.staff-name { font-weight: 600; color: #1e293b; display: block; }
.staff-email { font-size: 10px; color: #94a3b8; }

.year-badge { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 11px; }

.text-red { color: #e11d48; }
.bold { font-weight: 700; }
.pill { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
.pill-green { background: #dcfce7; color: #166534; }
.pill-red { background: #ffe4e6; color: #be123c; }
.pill-grey { background: #f1f5f9; color: #94a3b8; }
.empty { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }

@media (max-width: 800px) {
  .topbar { flex-direction: column; align-items: stretch; gap: 10px; }
  .control-group { width: 100%; }
  .my-picker { width: 100% !important; }
  .sticky-col { position: static; border-right: none; }
  
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>