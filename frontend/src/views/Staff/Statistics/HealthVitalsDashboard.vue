<template>
  <div class="dashboard-wrapper">

    <div class="stats-header">
      <div>
        <h2>Health Vitals Analytics</h2>
        <div v-if="isLoading" class="meta-loading">Loading staff profile...</div>
        <!-- <div v-else class="staff-meta">
          <strong>Staff:</strong> {{ staffInfo.name }} 
          <span class="divider">| </span>
          <strong>Staff No.:</strong> {{ staffInfo.id }} 
          <span class="divider">| </span>
          <strong>Age:</strong> {{ staffInfo.age }}
        </div> -->
      </div>
      <!-- <button @click="$router.go(-1)" class="back-btn">Back</button> -->
    </div>
    
    <div class="cards-grid">
      <div class="stat-card" :class="getBpStatusClass(latest.bp_systolic, latest.bp_diastolic)">
        <div class="card-title">Blood Pressure</div>
        <div class="card-value">
          {{ latest.bp_sys || '-' }}<span class="slash">/</span>{{ latest.bp_dia || '-' }} <small>mmHg</small>
        </div>
        <!-- <div class="card-status">{{ getBpLabel(latest.bp_sys, latest.bp_dia) }}</div> -->
      </div>

      <div class="stat-card normal">
        <div class="card-title">Heart Rate</div>
        <div class="card-value">{{ latest.pulse_bpm || '-' }} <small>bpm</small></div>
        <!-- <div class="card-status">Resting Rate</div> -->
      </div>

      <div class="stat-card" :class="getGlucoseStatusClass(latest.fbs_result)">
        <div class="card-title">Fasting Glucose</div>
        <div class="card-value">{{ latest.fbs_result || '-' }} <small>mmol/L</small></div>
        <!-- <div class="card-status">{{ getGlucoseLabel(latest.fbs_result) }}</div> -->
      </div>
    </div>

    <div class="charts-section">
      <div class="chart-box">
        <h3>Blood Pressure & Heart Rate</h3>
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
            <div class="ref-values">120 <small>SYS</small> / 80 <small>DIA</small></div>
          </div>
          <div class="ref-box limit">
            <div class="ref-label">CALL PHYSICIAN IF ABOVE</div>
            <div class="ref-values">140 <small>SYS</small> / 90 <small>DIA</small></div>
          </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="border-right: 3px solid #ccc;">Type</th>
                    <th class="text-center">BP <small>(mmHg)</small></th>
                    <th class="text-center" style="border-right: 3px solid #ccc;">HR <small>(bpm)</small></th>
                    <th class="text-center">Glucose <small>(mmol/L)</small></th>
                    <th>Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(session, index) in history" :key="index" :class="getRowClass(session)">
                    <td>{{ formatDateShort(session.session_date) }}</td>
                    <td style="border-right: 1.5px solid #ccc;">{{ session.session_type }}</td>
                    <td class="text-center" :class="getBpCellColor(session.bp_sys, session.bp_dia)">
                        <strong>{{ session.bp_sys }}/{{ session.bp_dia }}</strong>
                    </td>
                    <td class="text-center" style="border-right: 1.5px solid #ccc;">{{ session.pulse_bpm }}</td>
                    <td class="text-center" :class="getGlucoseCellColor(session.fbs_result)">
                        {{ session.fbs_result || '-' }}
                    </td>
                    <td>
                      <div class="level-container">
                        <div class="level-bar-track multi-zone">
                          
                          <div 
                            class="level-bar-fill" 
                            :class="getGlucoseBarClass(session.fbs_result)"
                            :style="{ width: getBarWidth(session.fbs_result) }"
                          ></div>

                          <div class="zone-line limit-marker-low" title="Low Limit (3.9)"></div>

                          <div class="zone-line limit-marker-high" title="Upper Limit (6.0)"></div>
                          
                        </div>
                      </div>
                    </td>
                    <td>
                        <span v-if="session.remarks" class="notes-text">{{ session.remarks }}</span>
                        <span v-else class="badge" :class="getOverallBadge(session)">
                            {{ getOverallLabel(session) }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

  </div>
</template>

<script>
import LineChart from '@/components/LineChart.vue';
import BpComboChart from '@/components/BpComboChart.vue';
import { formatDateShort } from '@/shared/dateFormat';

export default {
  name: 'HealthVitalsDashboard',
  components: { LineChart, BpComboChart },
  props: {
      history: { type: Array, default: () => [] },
      staffInfo: { type: Object, default: () => ({}) }
  },
  data() {
    return {
      // CHART OPTIONS
      bpComboOptions: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          x: { grid: { display: false } },
          y: { type: 'linear', position: 'left', title: {display:true, text:'Pressure'}, suggestedMin: 60, suggestedMax: 160 },
          y1: { type: 'linear', position: 'right', title: {display:true, text:'HR'}, grid: { drawOnChartArea: false } }
        },
        plugins: { legend: { position: 'bottom' } }
      },
      glucoseOptions: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: false } },
        plugins: { legend: { display: false } }
      }
    };
  },
  computed: {
    latest() { return this.history[0] || {}; },

    bpComboData() {
        if (!this.history.length) return { labels: [], datasets: [] };
        let data = [...this.history].reverse().slice(-12); // Last 12 sessions
        return {
            labels: data.map(h => this.formatDateShort(h.session_date)),
            datasets: [
                { type: 'line', label: 'Heart Rate', borderColor: '#28a745', backgroundColor: '#28a745', data: data.map(h => h.pulse_bpm), yAxisID: 'y1', order: 1 },
                { type: 'bar', label: 'Systolic', backgroundColor: 'rgba(248, 121, 121, 0.7)', data: data.map(h => h.bp_sys), yAxisID: 'y', order: 2 },
                { type: 'bar', label: 'Diastolic', backgroundColor: 'rgba(52, 152, 219, 0.7)', data: data.map(h => h.bp_dia), yAxisID: 'y', order: 3 }
            ]
        };
    },

    glucoseChartData() {
        if (!this.history.length) return { labels: [], datasets: [] };
        let data = [...this.history].reverse().slice(-12);
        return {
            labels: data.map(h => this.formatDateShort(h.session_date)),
            datasets: [{ label: 'Glucose', borderColor: '#2ecc71', backgroundColor: '#2ecc71', data: data.map(h => h.fbs_result), tension: 0.3, fill: false }]
        };
    }
  },
  methods: {
    // formatDate(d) { return new Date(d).toLocaleDateString('en-GB'); },
    // formatDateShort(d) { const date = new Date(d); return `${date.toLocaleString('default', { month: 'short' })} '${date.getFullYear().toString().substr(2)}`; },
    formatDateShort,
    
    // Status Logic
    getBpLabel(s, d) { if(s>=140||d>=90) return 'Hypertension Stg 2'; if(s>=130||d>=80) return 'Hypertension Stg 1'; return s>=120?'Elevated':'Normal'; },
    getBpStatusClass(s, d) { if(s>=140||d>=90) return 'danger'; if(s>=130||d>=80) return 'warning'; return s>=120?'warning-light':'normal'; },
    getGlucoseLabel(v) { if(!v) return '-'; if(v>=7.0) return 'Diabetes'; if(v>=5.6) return 'Pre-Diabetes'; return 'Normal'; },
    getGlucoseStatusClass(v) { if(!v) return ''; if(v>=7.0) return 'danger'; if(v>=5.6) return 'warning'; return 'normal'; },
    // Glucose Level Logic
    getBarWidth(value) {
      if (!value) return '0%';
      const val = parseFloat(value);
      
      const minScale = 2.0;
      const maxScale = 8.0;
      const totalSpan = maxScale - minScale; // 6.0

      // (Value - Min) / Span
      let percentage = ((val - minScale) / totalSpan) * 100;

      // between 0% and 100%
      // if value is 1.5, it becomes 0%. If 12.0, it becomes 100%.
      percentage = Math.max(0, Math.min(percentage, 100));

      return `${percentage}%`;
    },
    getGlucoseBarClass(value) {
      if (!value) return '';
      if (value >= 7.0) return 'bg-danger'; // Red
      if (value >= 5.6) return 'bg-warning'; // Orange/Yellow
      return 'bg-normal'; // Grey or Green
    },
    
    // Table Colors
    getBpCellColor(s, d) { if(s>=140||d>=90) return 'cell-danger'; if(s>=130||d>=80) return 'cell-warning'; return ''; },
    getGlucoseCellColor(v) { if(v>=7.0) return 'cell-danger'; if(v>=5.6) return 'cell-warning'; return ''; },
    getRowClass(sess) { if(sess.bp_sys>=140 || sess.fbs_result>=7.0) return 'row-critical'; if(sess.bp_sys>=130 || sess.fbs_result>=5.6) return 'row-elevated'; return ''; },
    getOverallBadge(sess) { if(sess.bp_sys>=140 || sess.fbs_result>=7.0) return 'badge-red'; if(sess.bp_sys>=130 || sess.fbs_result>=5.6) return 'badge-orange'; return 'badge-green'; },
    getOverallLabel(sess) { if(sess.bp_sys>=140 || sess.fbs_result>=7.0) return 'Action Req'; if(sess.bp_sys>=130 || sess.fbs_result>=5.6) return 'Monitor'; return 'Healthy'; }
  }
};
</script>

<style scoped>
.stats-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}
/* CARDS */
.cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
.stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid transparent; }
.card-value { font-size: 2em; font-weight: bold; margin: 10px 0; }
.stat-card.normal { border-color: #28a745; } .stat-card.warning { border-color: #ffc107; } .stat-card.danger { border-color: #dc3545; }

/* CHARTS */
.charts-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
.chart-box { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.chart-container { height: 250px; width: 100%; }

/* TABLE */
.history-table-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
.text-center { text-align: center; }
.cell-danger { background: #f8d7da; color: #721c24; font-weight: bold; }
.cell-warning { background: #fff3cd; color: #856404; font-weight: bold; }
.badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
.badge-red { background: #f8d7da; color: #721c24; } .badge-green { background: #d4edda; color: #155724; } .badge-orange { background: #fff3cd; color: #856404; }
.reference-section { display: flex; gap: 15px; margin-bottom: 15px; }
.ref-box { flex: 1; padding: 10px; background: #f9f9f9; border-left: 4px solid #ccc; font-size: 0.9em; }
.ref-box.target { border-color: #28a745; } .ref-box.limit { border-color: #dc3545; }

/* LEVEL COLUMN STYLES */
.level-container {
  position: relative;
  width: 100%;
  min-width: 120px; /* Bit wider to see the zones */
  padding: 5px 0;
}

/* THE TRACK: 3 Visual Parts */
.level-bar-track.multi-zone {
  height: 15px;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
  border: 1px solid #cbd5e1;
  
  /* Zoomed Scale Logic (2.0 to 8.0):
     - 0% to 31.6%   : Low (Values 2.0 - 3.9) -> Grey
     - 31.6% to 66.6%: Normal (Values 3.9 - 6.0) -> Green
     - 66.6% to 100% : High (Values 6.0 - 8.0+) -> Red
  */
  background: linear-gradient(
    to right, 
    #e2e8f0 0%,     #e2e8f0 31.6%, 
    #d1fae5 31.6%,  #d1fae5 66.6%, 
    #fee2e2 66.6%,  #fee2e2 100%
  );
}

/* Markers should align with the gradient stops */
.limit-marker-low { left: 31.6%; }
.limit-marker-high { left: 66.6%; }

/* THE FILL */
.level-bar-fill {
  height: 100%;
  background-color: #475569;
  /* border-radius: 6px; */
  position: absolute; 
  top: 0;
  left: 0;
  z-index: 2;
  transition: width 0.5s ease-out;
  opacity: 0.7;
}

/* THE LINES: Separators between the 3 parts */
.zone-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 1px;
  background-color: rgba(0,0,0,0.15); 
  z-index: 1; 
}
.zone-line.left { left: 31.6%; }
.zone-line.right { right: 31.6%; }
</style>