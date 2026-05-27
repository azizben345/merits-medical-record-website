<template>
  <div class="dashboard">
    <h2>Staff Health History</h2>

    <div class="topbar">
      <div class="legend">
        <button
          class="chip"
          :class="{active: activeYear === null}"
          @click="setYear(null)"
          title="Show all history"
        >
          <div class="chip-title">All Time</div>
          <div class="chip-sub">{{ historyData.length }} Sessions</div>
        </button>

        <button
          v-for="y in availableYears"
          :key="y"
          class="chip"
          :class="{active: activeYear === y}"
          @click="setYear(y)"
        >
          <div class="chip-title">{{ y }}</div>
          <!-- <div class="chip-sub">{{ countByYear[y] || 0 }} Sessions</div> -->
          <!-- <span class="chip-sub">
            {{ y === null ? 'Status Unknown' : (getYearStatus(activeYear) === 'pending' ? 'Pending' : 'Submitted') }}
          </span> -->
        </button>
      </div>
    </div>

    <div class="kpis" v-if="historyData.length">
      <div class="card">
        <div class="card-label">Total Checkups</div>
        <div class="card-main"><span class="big">{{ kpi.totalSessions }}</span></div>
        <div class="card-sub">Recorded sessions</div>
      </div>
      
      <div class="card">
        <div class="card-label">Latest BMI ({{ kpi.latestYear }})</div>
        <div class="card-main">
          <span class="big">{{ formatVal(kpi.latestBmi) }}</span>
        </div>
        <div class="card-sub">
          Fit: &lt; 25.0
          <span v-if="kpi.latestBmi > 25" class="text-red bold">(High)</span>
        </div>
      </div>

      <div class="card">
        <div class="card-label">Latest BP ({{ kpi.latestYear }})</div>
        <div class="card-main">
          <span class="big">{{ kpi.latestBp }}</span>
        </div>
        <div class="card-sub">Systolic / Diastolic</div>
      </div>

      <div class="card">
        <div class="card-label">Abnormal Flags ({{ kpi.latestYear }})</div>
        <div class="card-main"><span class="big">{{ kpi.abnormalCount }}</span></div>
        <div class="card-sub">Labs/ECG abnormal</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-title">Checkup History ({{ filteredData.length }} Records)</div>
      
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th class="sticky-col">Year</th>
              <th>Status</th>
              <th>Session Type</th>
              
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
                <!-- <span class="year-badge">{{ row.year_label }}</span> -->
                <div>{{ formatDateShort(row.session_date) }}</div>
              </td>

              <td>
                <span 
                  class="pill" 
                  :class="{
                    'pill-orange': row.status === 'draft',
                    'pill-green': row.status === 'submitted' || row.status === 'locked'
                  }"
                >
                  {{ row.status === 'draft' ? 'Pending' : 'Submitted' }}
                </span>
              </td>

              <td>{{ row.session_type }}</td>

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
                <span :class="getValClass(row.uric_val, 0.42, 'high')">{{ formatVal(row.uric_val) }}</span>
              </td>

              <td>
                <span class="pill" :class="row.diabetes === 'Y' ? 'pill-red' : 'pill-green'">
                  {{ row.diabetes === 'Y' ? 'Yes' : 'No' }}
                </span>
              </td>
              
              <td><span class="pill" :class="getStatusClass(row.ecg_status)">{{ row.ecg_status || '-' }}</span></td>
              <td><span class="pill" :class="getStatusClass(row.spiro_status)">{{ row.spiro_status || '-' }}</span></td>
              <td><span class="pill" :class="getStatusClass(row.audio_status)">{{ row.audio_status || '-' }}</span></td>
            </tr>
            
            <tr v-if="filteredData.length === 0">
              <td colspan="11" class="empty">No history found.</td>
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
import { formatDateShort } from '@/shared/dateFormat';

export default {
  name: 'MyHealthHistory',
  props: {
    staff_email: String
  },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      historyData: [],
      activeYear: null,
      loading: false
    };
  },
  computed: {
    // Filter logic (Year Filter only)
    filteredData() {
      if (!this.activeYear) return this.historyData;
      return this.historyData.filter(r => r.year_label == this.activeYear);
    },

    // // Get year status
    // getYearStatus(activeYear) {
    //   if (!activeYear) return null;
    //   return this.historyData.find(r => r.year_label == activeYear);
    // },

    // Extract available years
    availableYears() {
      const years = new Set(this.historyData.map(r => r.year_label).filter(Boolean));
      return Array.from(years).sort((a, b) => b - a);
    },

    // Count helper for chips
    // countByYear() {
    //   const counts = {};
    //   this.historyData.forEach(r => {
    //     const y = r.year_label;
    //     if (y) counts[y] = (counts[y] || 0) + 1;
    //   });
    //   return counts;
    // },

    // 4. Personal KPIs (Calculated from Latest Data)
    kpi() {
      if (!this.historyData.length) return {};
      //    Since historyData is sorted Newest -> Oldest, .find() automatically gives you the "Latest Complete" one.
      const validRecord = this.historyData.find(r => 
        r.bmi != null &&
        r.bp_sys != null &&
        r.bp_dia != null 
        // Check for valid status (to skip 'Not done' or null)
        && r.ecg_status 
        && r.ecg_status !== 'Not done' 
        && r.spiro_status 
        && r.spiro_status !== 'Not done' 
        && r.audio_status 
        && r.audio_status !== 'Not done'
      );

      // if NO complete record exists, just show the absolute latest (even if empty)
      const displayRecord = validRecord || this.historyData[0];

      // calculate Abnormal Count 
      let abnormal = 0;
      if (validRecord.ecg_status === 'Abnormal') abnormal++;
      if (validRecord.spiro_status === 'Abnormal') abnormal++;
      if (validRecord.audio_status === 'Abnormal') abnormal++;
      if (validRecord.diabetes === 'Y') abnormal++;

      // const latest = validRecord || this.historyData[0];
      // console.log(
      //   'Latest ecg:', latest.ecg_status,
      //   '\nLatest spiro:', latest.spiro_status,
      //   '\nLatest audio:', latest.audio_status,
      //   '\nLatest diabetes:', latest.diabetes,
      //   '\nAbnormal count:', abnormal,
      // );

      return {
        totalSessions: this.historyData.length,
        // latestYear: displayRecord.session_date ? formatDateShort(displayRecord.session_date.toString()) : '-',
        latestYear: displayRecord.session_date ? displayRecord.session_date.substring(0, 4) : '-',
        latestBmi: displayRecord.bmi, 
        latestBp: (displayRecord.bp_sys && displayRecord.bp_dia) 
          ? `${displayRecord.bp_sys}/${displayRecord.bp_dia}` 
          : '-',
          
        abnormalCount: abnormal
      };
    }
  },
  async mounted() {
    await this.loadHistory();
  },
  methods: {
    async loadHistory() {
      this.loading = true;
      const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}');
      if (!userInfo.email) return;

      const staffEmail = 
        // this.$route.params.staffEmail || 
        this.staff_email ||
        userInfo.email || '';
      const staffXYZ = staffEmail.replace(/\./g, 'XYZ');

      try {
        const res = await fetch(`${this.baseUrl}/staff/my-health-history/${staffXYZ}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        
        if (handleUnauthorized(res)) return;
        
        const json = await res.json();
        this.historyData = json.history || [];
      } catch (e) {
        console.error("Failed to load history", e);
      } finally {
        this.loading = false;
      }
    },

    setYear(y) {
      this.activeYear = y;
    },

    // --- Formatters ---
    formatDateShort,
    // formatDate(d) {
    //   if (!d) return '';
    //   return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    // },

    formatVal(val) {
      if (val === null || val === undefined) return '-';
      return Number(val).toFixed(1);
    },

    // --- Styling Helpers ---
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
.dashboard { 
    padding: 24px; 
    /* max-width: 1200px;  */
    margin: 0 auto; 
}

/* Top Bar */
.topbar { display:flex; margin-bottom: 20px; }
.legend { display:flex; gap:8px; flex-wrap:wrap; }
.chip {
  border:1px solid #e2e8f0; background:#fff; border-radius:10px; padding:8px 12px;
  display:flex; flex-direction:column; min-width:80px; cursor:pointer; text-align: left;
  transition: all 0.2s;
}
.chip:hover { border-color: #cbd5e0; background: #f8fafc; }
.chip.active { background:#2c3e50; color:#fff; border-color:#2c3e50; }
.chip-title { font-weight: 700; font-size: 14px; }
.chip-sub { font-size: 11px; opacity: 0.8; margin-top: 2px; }

/* KPIs */
.kpis {
  display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px; margin-bottom: 25px;
}
.card {
  background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
.card-label { font-size:12px; color:#64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.card-main .big { font-size:28px; font-weight:700; color:#2d3748; line-height: 1.2; }
.card-sub { font-size:12px; color:#94a3b8; margin-top:4px; }

/* Main Panel & Table */
.panel {
  background:#fff; border:1px solid #e2e8f0; border-radius:10px; 
  box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden;
}
.panel-title { 
  padding: 15px 20px; border-bottom: 1px solid #f1f5f9; 
  font-weight:700; color:#334155; font-size: 16px; background: #f8fafc;
}

.table-responsive { overflow-x: auto; }

table { width:100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; white-space: nowrap; }
th { background: #f8fafc; color: #475569; font-weight: 600; }

/* Sticky Year Column */
.sticky-col { 
  position: sticky; left: 0; background: #fff; z-index: 5; 
  border-right: 2px solid #f1f5f9; min-width: 100px;
}
th.sticky-col { z-index: 15; background: #f8fafc; }

.year-badge { 
  background: #f1f5f9; color: #475569; padding: 2px 6px; 
  border-radius: 4px; font-weight: 700; font-size: 12px; 
}
.date-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* Value Styling */
.text-red { color: #e11d48; }
.bold { font-weight: 700; }

/* Pills */
.pill { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.pill-green { background: #dcfce7; color: #166534; }
.pill-red { background: #ffe4e6; color: #be123c; }
.pill-orange { background: #ffedd5; color: #c2410c; } /* New Orange for Draft */
.pill-grey { background: #f1f5f9; color: #94a3b8; }

.empty { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }

@media (max-width: 768px) {
  .dashboard { padding: 10px; }
  .sticky-col { position: static; border-right: none; }
}
</style>