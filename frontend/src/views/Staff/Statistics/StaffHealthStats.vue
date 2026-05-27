<template>
  <div class="analytics-container">
    
    <div class="stats-header">
      <div>
        <h2>Health Vitals Analytics</h2>
        <div v-if="isLoading" class="meta-loading">Loading staff profile...</div>
        <div v-else class="staff-meta">
          <strong>Staff:</strong> {{ staffInfo.name }} 
          <span class="divider">|</span>
          <strong>ID:</strong> {{ staffInfo.id }} 
          <span class="divider">|</span>
          <strong>Age:</strong> {{ staffInfo.age }}
        </div>
      </div>
      <!-- <button @click="$router.go(-1)" class="back-btn">Back</button> -->
    </div>

    <div v-if="errorMessage" class="error-box">
      {{ errorMessage }}
    </div>

    <div v-if="isLoading" class="loading-box">
      <div class="spinner"></div>
      <p>Fetching medical records...</p>
    </div>

    <template v-else>

      <div v-if="history.length === 0 && !errorMessage" class="empty-state">
        <div class="empty-icon">📂</div>
        <h3>No Medical Records Found</h3>
        <p>This staff member has not completed any checkup sessions yet.</p>
      </div>

      <div v-else-if="history.length > 0">
        
        <div class="cards-grid">
           <div class="stat-card" :class="getBpStatusClass(latest.bp_sys, latest.bp_dia)">
             <div class="card-title">Blood Pressure</div>
             <div class="card-value">
               {{ latest.bp_sys }}<span class="slash">/</span>{{ latest.bp_dia }} <small>mmHg</small>
             </div>
             <div class="card-status">{{ getBpLabel(latest.bp_sys, latest.bp_dia) }}</div>
           </div>

           <div class="stat-card normal">
             <div class="card-title">Heart Rate</div>
             <div class="card-value">{{ latest.pulse_bpm }} <small>bpm</small></div>
             <div class="card-status">Resting Rate</div>
           </div>

           <div class="stat-card" :class="getGlucoseStatusClass(latest.fbs_result)">
             <div class="card-title">Fasting Glucose</div>
             <div class="card-value">{{ latest.fbs_result || '-' }} <small>mmol/L</small></div>
             <div class="card-status">{{ getGlucoseLabel(latest.fbs_result) }}</div>
           </div>
        </div>

        <div class="charts-section">
          <div class="chart-box">
            <h3>Blood Pressure & Heart Rate Trend</h3>
            <div class="chart-container">
              <BpComboChart :chartData="bpComboData" :chartOptions="bpComboOptions" />
            </div>
          </div>
          
          <div class="chart-box">
            <h3>Glucose Trend</h3>
            <div class="chart-container">
              <LineChart :chartData="glucoseChartData" :chartOptions="glucoseOptions" />
            </div>
          </div>
        </div>

        <div class="history-table-section">
            <h3>Timed Data Entry Log</h3>
            
            <div class="reference-section">
              <div class="ref-box target">
                <div class="ref-label">TARGET BLOOD PRESSURE</div>
                <div class="ref-values">
                  <span class="ref-val">120 <small>SYS</small></span>
                  <span class="divider">/</span>
                  <span class="ref-val">80 <small>DIA</small></span>
                </div>
              </div>
              
              <div class="ref-box limit">
                <div class="ref-label">CALL PHYSICIAN IF ABOVE</div>
                <div class="ref-values">
                  <span class="ref-val">140 <small>SYS</small></span>
                  <span class="divider">/</span>
                  <span class="ref-val">90 <small>DIA</small></span>
                </div>
              </div>
            </div>

            <div class="table-wrapper">
              <table>
                  <thead>
                      <tr>
                          <th>Date</th>
                          <th>Session Type</th>
                          <th class="text-center">BP <small>(mmHg)</small></th>
                          <th class="text-center">HR <small>(bpm)</small></th>
                          <th class="text-center">Glucose <small>(mmol/L)</small></th>
                          <th>Notes / Status</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr v-for="(session, index) in history" :key="index" :class="getRowClass(session)">
                          <td>{{ formatDate(session.session_date) }}</td>
                          <td>{{ session.session_type }}</td>
                          
                          <td class="text-center" :class="getBpCellColor(session.bp_sys, session.bp_dia)">
                              <strong>{{ session.bp_sys }}/{{ session.bp_dia }}</strong>
                          </td>
                          
                          <td class="text-center">{{ session.pulse_bpm }}</td>
                          
                          <td class="text-center" :class="getGlucoseCellColor(session.fbs_result)">
                              {{ session.fbs_result || '-' }}
                          </td>
                          
                          <td>
                            <span v-if="session.session_remarks" class="notes-text">{{ session.session_remarks }}</span>
                            <span v-else class="badge" :class="getOverallBadge(session)">
                              {{ getOverallLabel(session) }}
                            </span>
                          </td>
                      </tr>
                  </tbody>
              </table>
            </div>
        </div>

      </div>
    </template>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { formatDate, formatDateShort } from '@/shared/dateFormat';
import LineChart from '@/components/LineChart.vue';
import BpComboChart from '@/components/BpComboChart.vue';

export default {
  name: 'AnalyticsChartsBP',
  components: { LineChart, BpComboChart },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      isLoading: true,
      errorMessage: '',
      
      // Data Containers
      staffInfo: { name: '', id: '', age: '' },
      history: [], 

      // --- CHART OPTIONS ---
      bpComboOptions: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
          x: { grid: { display: false } },
          y: { 
            type: 'linear', display: true, position: 'left', 
            title: { display: true, text: 'Pressure (mmHg)' },
            suggestedMin: 60, suggestedMax: 160
          },
          y1: { 
            type: 'linear', display: true, position: 'right', 
            title: { display: true, text: 'Heart Rate (bpm)' },
            grid: { drawOnChartArea: false }
          }
        },
        plugins: { legend: { position: 'bottom' } }
      },
      glucoseOptions: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: false } },
        plugins: { legend: { display: false } }
      }
    };
  },
  computed: {
    // Helper for Cards (Latest Record)
    latest() {
      return this.history[0] || { bp_systolic: '-', bp_diastolic: '-', pulse_rate: '-', glucose_fasting: '-' };
    },

    // 1. BP Combo Chart Data
    bpComboData() {
        if (this.history.length === 0) return { labels: [], datasets: [] };

        // Logic: Slice to last 12 sessions so graph isn't crowded
        let chartHistory = [...this.history].reverse(); 
        if (chartHistory.length > 12) chartHistory = chartHistory.slice(-12);

        return {
            labels: chartHistory.map(h => this.formatDateShort(h.session_date)),
            datasets: [
                {
                    type: 'line', label: 'Heart Rate',
                    borderColor: '#28a745', backgroundColor: '#28a745',
                    borderWidth: 2, pointRadius: 4,
                    data: chartHistory.map(h => h.pulse_bpm),
                    yAxisID: 'y1', tension: 0.3, order: 1
                },
                {
                    type: 'bar', label: 'Systolic',
                    backgroundColor: 'rgba(248, 121, 121, 0.7)',
                    data: chartHistory.map(h => h.bp_sys),
                    yAxisID: 'y', order: 2
                },
                {
                    type: 'bar', label: 'Diastolic',
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    data: chartHistory.map(h => h.bp_dia),
                    yAxisID: 'y', order: 3
                }
            ]
        };
    },

    // 2. Glucose Chart Data
    glucoseChartData() {
        if (this.history.length === 0) return { labels: [], datasets: [] };
        
        let chartHistory = [...this.history].reverse();
        if (chartHistory.length > 12) chartHistory = chartHistory.slice(-12);

        return {
            labels: chartHistory.map(h => this.formatDateShort(h.session_date)),
            datasets: [
                {
                    label: 'Glucose', borderColor: '#2ecc71', backgroundColor: '#2ecc71',
                    data: chartHistory.map(h => h.fbs_result),
                    tension: 0.3, fill: false
                }
            ]
        };
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    formatEmailForUrl(email) {
        return email.replace(/\./g, 'XYZ').replace(/\+/g, 'UVW');
    },

    fetchData() {
        this.isLoading = true;
        this.errorMessage = '';
        
        // const routeEmail = this.$route.params.email;
        const routeEmail = JSON.parse(localStorage.getItem('user_info')).email; // self email for staff (testing)
        if (!routeEmail) {
            this.errorMessage = "No email provided in URL";
            this.isLoading = false;
            return;
        }

        // Apply encoding hack before sending to API
        const safeEmail = this.formatEmailForUrl(routeEmail);
        const token = localStorage.getItem('jwt_token');

        fetch(`${this.baseUrl}/staff/stats/bp-glucose/${safeEmail}`, {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
        .then(res => {
            if (res.status === 404) throw new Error("Staff member not found.");
            if (!res.ok) throw new Error("Failed to fetch staff data");
            return res.json();
        })
        .then(data => {
            this.staffInfo = data.staff;
            this.history = data.history || [];
        })
        .catch(err => {
            console.error(err);
            this.errorMessage = err.message;
        })
        .finally(() => {
            this.isLoading = false;
        });
    },

    // --- FORMATTING HELPERS ---
    formatDate, formatDateShort,

    // --- COLOR LOGIC (CARDS) ---
    getBpLabel(sys, dia) {
      if (sys >= 140 || dia >= 90) return 'Hypertension Stg 2';
      if (sys >= 130 || dia >= 80) return 'Hypertension Stg 1';
      if (sys >= 120) return 'Elevated';
      return 'Normal';
    },
    getBpStatusClass(sys, dia) {
      if (sys >= 140 || dia >= 90) return 'danger';
      if (sys >= 130 || dia >= 80) return 'warning';
      if (sys >= 120) return 'warning-light';
      return 'normal';
    },
    getGlucoseLabel(val) {
      if (!val) return 'No Data';
      if (val >= 7.0) return 'Diabetes Range';
      if (val >= 5.6) return 'Pre-Diabetes';
      return 'Normal';
    },
    getGlucoseStatusClass(val) {
      if (!val) return 'normal';
      if (val >= 7.0) return 'danger';
      if (val >= 5.6) return 'warning';
      return 'normal';
    },

    // --- COLOR LOGIC (TABLE) ---
    getBpCellColor(sys, dia) {
       // Highlight cell if either value is high
       if (sys >= 140 || dia >= 90) return 'cell-danger';
       if (sys >= 130 || dia >= 80) return 'cell-warning';
       return '';
    },
    getGlucoseCellColor(val) {
       if (val >= 7.0) return 'cell-danger';
       if (val >= 5.6) return 'cell-warning';
       return '';
    },
    getOverallBadge(session) {
      const sys = session.bp_sys;
      const glu = session.fbs_result;
      if (sys >= 140 || glu >= 7.0) return 'badge-red';
      if (sys >= 130 || glu >= 5.6) return 'badge-orange';
      return 'badge-green';
    },
    getOverallLabel(session) {
      const sys = session.bp_sys;
      const glu = session.fbs_result;
      if (sys >= 140 || glu >= 7.0) return 'Action Required';
      if (sys >= 130 || glu >= 5.6) return 'Monitor';
      return 'Healthy';
    },
    getRowClass(session) {
      const sys = session.bp_sys;
      const dia = session.bp_dia;
      const glu = session.fbs_result;
      if (sys >= 140 || dia >= 90 || glu >= 7.0) return 'row-critical';
      if (sys >= 130 || glu >= 5.6) return 'row-elevated';
      return '';
    }
  }
};
</script>

<style scoped>
.analytics-container {
  padding: 20px;
  background-color: #f4f6f8;
  font-family: 'Segoe UI', sans-serif;
  color: #333;
}
.stats-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}
.staff-meta { color: #555; font-size: 1.1em; margin-top: 5px; }
.meta-loading { color: #999; font-style: italic; }
.divider { margin: 0 10px; color: #ccc; }
.back-btn {
  padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;
}

/* CARDS */
.cards-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;
}
.stat-card {
  background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  text-align: center; border-top: 5px solid transparent;
}
.card-title { color: #888; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
.card-value { font-size: 2.5em; font-weight: bold; margin: 10px 0; color: #333; }
.card-value .slash { color: #ccc; font-weight: normal; }
.card-value small { font-size: 0.4em; color: #999; }
.card-status { font-weight: 600; padding: 4px 8px; border-radius: 4px; display: inline-block; font-size: 0.85em; }

/* Status Colors */
.stat-card.normal { border-color: #28a745; }
.stat-card.normal .card-status { background: #d4edda; color: #155724; }
.stat-card.warning { border-color: #ffc107; }
.stat-card.warning .card-status { background: #fff3cd; color: #856404; }
.stat-card.warning-light { border-color: #ffc107; } 
.stat-card.warning-light .card-status { background: #fff9db; color: #856404; }
.stat-card.danger { border-color: #dc3545; }
.stat-card.danger .card-status { background: #f8d7da; color: #721c24; }

/* CHARTS */
.charts-section {
  display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;
}
.chart-box {
  background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.chart-container { height: 300px; width: 100%; }

/* REFERENCE & TABLE */
.history-table-section {
  background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.reference-section {
  display: flex; gap: 20px; margin-bottom: 20px;
}
.ref-box {
  flex: 1; background: #fafafa; padding: 15px; border-radius: 8px;
  display: flex; justify-content: space-between; align-items: center;
  border-left: 5px solid #ccc;
}
.ref-box.target { border-left-color: #28a745; }
.ref-box.limit { border-left-color: #dc3545; }
.ref-label { font-weight: bold; color: #555; font-size: 0.85em; }
.ref-values { font-size: 1.4em; font-weight: bold; color: #333; }
.ref-val small { font-size: 0.5em; color: #999; }

.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th { text-align: left; padding: 12px; background: #f1f3f5; color: #495057; border-bottom: 2px solid #dee2e6; white-space: nowrap;}
td { padding: 12px; border-bottom: 1px solid #dee2e6; }
.text-center { text-align: center; }
.notes-text { font-style: italic; color: #666; font-size: 0.9em; }

/* Table Colors */
.cell-danger { background-color: #f8d7da; color: #721c24; font-weight: bold; }
.cell-warning { background-color: #fff3cd; color: #856404; font-weight: bold; }
.row-critical { background-color: #fff5f5; }
.row-elevated { background-color: #fffae6; }

/* Badge */
.badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; display: inline-block; }
.badge-green { background: #d4edda; color: #155724; }
.badge-orange { background: #fff3cd; color: #856404; }
.badge-red { background: #f8d7da; color: #721c24; }

/* Empty/Loading/Error */
.empty-state { text-align: center; padding: 50px; background: white; border-radius: 8px; color: #777; margin-top: 20px; }
.empty-icon { font-size: 3em; margin-bottom: 10px; }
.loading-box { text-align: center; padding: 40px; color: #666; }
.spinner { /* Simple spinner CSS */
  border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%;
  width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 10px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.error-box { background: #fee; color: #c00; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
</style>