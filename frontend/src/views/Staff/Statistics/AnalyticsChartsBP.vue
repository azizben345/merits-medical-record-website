<template>
  <div class="health-stats-container">
    
    <div class="stats-header">
      <div>
        <h2>Health Vitals History (Mock Data)</h2>
        <div class="staff-meta">
          <strong>Staff:</strong> {{ staffName }} | 
          <strong>ID:</strong> {{ staffId }} | 
          <strong>Age:</strong> {{ staffAge }}
        </div>
      </div>
      <button @click="$router.go(-1)" class="back-btn">Back to Profile</button>
    </div>

    <div class="cards-grid">
      
      <div class="stat-card" :class="getBpStatusClass(latest.sys, latest.dia)">
        <div class="card-title">Blood Pressure</div>
        <div class="card-value">
          {{ latest.sys }}/{{ latest.dia }} <small>mmHg</small>
        </div>
        <div class="card-status">{{ getBpLabel(latest.sys, latest.dia) }}</div>
      </div>

      <div class="stat-card normal">
        <div class="card-title">Heart Rate</div>
        <div class="card-value">
          {{ latest.hr }} <small>bpm</small>
        </div>
        <div class="card-status">Resting Rate</div>
      </div>

      <div class="stat-card" :class="getGlucoseStatusClass(latest.glucose)">
        <div class="card-title">Fasting Glucose</div>
        <div class="card-value">
          {{ latest.glucose }} <small>mmol/L</small>
        </div>
        <div class="card-status">{{ getGlucoseLabel(latest.glucose) }}</div>
      </div>
    </div>

    <div class="charts-section">
      
      <!-- <div class="chart-box">
        <h3>Blood Pressure Trend (Over Time)</h3>
        <div class="chart-container">
          <LineChart :chartData="bpChartData" :chartOptions="bpOptions" />
        </div>
      </div> -->

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

    <div class="history-table-section">
      <h3>Timed Data Entry Log</h3>
      
      <table>
        <thead>
          <tr>
            <th>Checkup Date</th>
            <!-- <th>Time</th> -->
            <th>Checkup Type</th> 
            
            <th class="text-center">Systolic <small>(mmHg)</small></th>
            <th class="text-center">Diastolic <small>(mmHg)</small></th>
            <th class="text-center">Heart Rate <small>(bpm)</small></th>
            
            <th class="text-center">Glucose <small>(mmol/L)</small></th>
            
            <th>Notes / Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(session, index) in history" :key="index" :class="getRowClass(session)">
            
            <td>{{ formatDateShort(session.date) }}</td>
            <!-- <td class="time-col">{{ formatTime(session.date) }}</td> -->
            
            <td>{{ session.type }}</td>
            
            <td class="text-center" :class="getBpColor(session.sys, 'sys')">
              <strong>{{ session.sys }}</strong>
            </td>
            <td class="text-center" :class="getBpColor(session.dia, 'dia')">
              <strong>{{ session.dia }}</strong>
            </td>
            <td class="text-center">{{ session.hr }}</td>

            <td class="text-center" :class="getGlucoseColor(session.glucose)">
              {{ session.glucose }}
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
// Import the chart component
import LineChart from '@/components/LineChart.vue';
import BpComboChart from '@/components/BpComboChart.vue';
import { formatDate, formatDateShort } from '@/shared/dateFormat';

export default {
  name: 'AnalyticsChartsBP',
  components: { 
    BpComboChart, 
    LineChart 
  },
  data() {
    return {
      // MOCK DATA: Simulating API response
      staffName: "Ali Bin Abu",
      staffId: "STF-1024",
      staffAge: 45,

      // DATA ORDER: Newest First (Best for Tables)
      // reverse this (automatically) for the Charts
      history: [
        { 
          date: '2024-12-01', type: 'Annual Checkup', 
          sys: 142, dia: 92, hr: 88, glucose: 7.2, bmi: 28.5 
        },
        { 
          date: '2023-11-15', type: 'Follow-up', 
          sys: 135, dia: 85, hr: 82, glucose: 6.8, bmi: 28.1 
        },
        { 
          date: '2023-06-10', type: 'Ad-hoc', 
          sys: 128, dia: 82, hr: 75, glucose: 5.9, bmi: 27.8 
        },
        { 
          date: '2022-12-05', type: 'Annual Checkup', 
          sys: 118, dia: 78, hr: 70, glucose: 5.4, bmi: 26.5 
        },
        { 
          date: '2021-12-01', type: 'Pre-employment', 
          sys: 120, dia: 80, hr: 72, glucose: 5.2, bmi: 26.0 
        }
      ],
      
      // Chart Config
      // bpOptions: {
      //   responsive: true,
      //   maintainAspectRatio: false,
      //   scales: { y: { suggestedMin: 60, suggestedMax: 160 } }, // Keep chart centered on human BP range
      //   plugins: { legend: { position: 'bottom' } }
      // },
      bpComboOptions: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index', // Hovering shows all 3 values for that date
          intersect: false,
        },
        scales: {
          x: {
            grid: { display: false } // Cleaner look
          },
          // LEFT AXIS (for BP)
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: { display: true, text: 'Pressure (mmHg)' },
            suggestedMin: 60,
            suggestedMax: 160
          },
          // RIGHT AXIS (for Heart Rate)
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: { display: true, text: 'Heart Rate (bpm)' },
            grid: {
              drawOnChartArea: false, // Don't show grid lines for this axis (avoids mess)
            }
          }
        },
        plugins: {
          legend: { position: 'bottom' }
        }
      },
      glucoseOptions: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: false } },
        plugins: { legend: { display: false } } // Hide legend since there's only 1 line
      }
    };
  },
  computed: {
    // 1. Latest Session (Top of the list)
    latest() {
      return this.history[0] || {};
    },

    // 2. Chart Data: Blood Pressure
    bpChartData() {
      // Create a reversed copy for the chart (Oldest -> Newest)
      const chartHistory = [...this.history].reverse();
      
      return {
        labels: chartHistory.map(h => this.formatDateShort(h.date)), 
        datasets: [
          {
            label: 'Systolic (Top)',
            backgroundColor: '#f87979', // Red
            borderColor: '#f87979',
            data: chartHistory.map(h => h.sys),
            tension: 0.1
          },
          {
            label: 'Diastolic (Bottom)',
            backgroundColor: '#3498db', // Blue
            borderColor: '#3498db',
            data: chartHistory.map(h => h.dia),
            tension: 0.1
          }
        ]
      }
    },

    // combined chart
    bpComboData() {
      const chartHistory = [...this.history].reverse(); // Oldest -> Newest
      
      return {
        labels: chartHistory.map(h => this.formatDateShort(h.date)), 
        datasets: [
          // 1. HEART RATE (Line) - On Right Axis (y1)
          {
            type: 'line', // Explicitly define as Line
            label: 'Heart Rate',
            borderColor: '#28a745', // Green line
            backgroundColor: '#28a745',
            borderWidth: 2,
            pointRadius: 4,
            data: chartHistory.map(h => h.hr),
            yAxisID: 'y1', // Binds to right axis
            tension: 0.3,
            order: 1 // Layer order: Draw line on TOP of bars
          },
          // 2. SYSTOLIC (Bar) - On Left Axis (y)
          {
            type: 'bar',
            label: 'Systolic',
            backgroundColor: 'rgba(248, 121, 121, 0.7)', // Red with transparency
            data: chartHistory.map(h => h.sys),
            yAxisID: 'y',
            order: 2
          },
          // 3. DIASTOLIC (Bar) - On Left Axis (y)
          {
            type: 'bar',
            label: 'Diastolic',
            backgroundColor: 'rgba(52, 152, 219, 0.7)', // Blue with transparency
            data: chartHistory.map(h => h.dia),
            yAxisID: 'y',
            order: 3
          }
        ]
      }
    },

    // 3. Chart Data: Glucose
    glucoseChartData() {
      const chartHistory = [...this.history].reverse();

      return {
        labels: chartHistory.map(h => this.formatDateShort(h.date)),
        datasets: [
          {
            label: 'Glucose (mmol/L)',
            backgroundColor: '#2ecc71', // Green
            borderColor: '#2ecc71',
            data: chartHistory.map(h => h.glucose),
            tension: 0.3, // Smooth curve
            fill: false
          }
        ]
      }
    }
  },
  methods: {
    formatDate,
    formatDateShort,

    // --- LOGIC HELPERS ---
    formatTime(dateStr) {
      // Extracts "10:30 AM" from the timestamp
      return new Date(dateStr).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    },
    getRowClass(session) {
      // If ANY vital sign is in the critical range, highlight the row
      if (session.sys >= 140 || session.dia >= 90 || session.glucose >= 7.0) {
        return 'row-critical';
      }
      // If elevated but not critical
      if (session.sys >= 130 || session.glucose >= 5.6) {
        return 'row-elevated';
      }
      return '';
    },
    getBpColor(val, type) {
      // Specific coloring for individual cells (Excel style often highlights just the bad number)
      if (type === 'sys' && val >= 140) return 'cell-danger';
      if (type === 'dia' && val >= 90) return 'cell-danger';
      return '';
    },

    getGlucoseColor(val) {
      if (val >= 7.0) return 'cell-danger';
      if (val >= 5.6) return 'cell-warning';
      return '';
    },
    getBpLabel(sys, dia) {
      if (sys >= 140 || dia >= 90) return 'Hypertension Stg 2';
      if (sys >= 130 || dia >= 80) return 'Hypertension Stg 1';
      if (sys >= 120 && sys < 130 && dia < 80) return 'Elevated';
      return 'Normal';
    },
    getBpStatusClass(sys, dia) {
      if (sys >= 140 || dia >= 90) return 'danger';
      if (sys >= 130 || dia >= 80) return 'warning';
      if (sys >= 120) return 'warning-light';
      return 'normal';
    },
    getBpTextClass(sys, dia) {
      return (sys >= 130 || dia >= 80) ? 'text-danger-bold' : '';
    },

    getGlucoseLabel(val) {
      if (val >= 7.0) return 'Diabetes Range';
      if (val >= 5.6) return 'Pre-Diabetes';
      return 'Normal';
    },
    getGlucoseStatusClass(val) {
      if (val >= 7.0) return 'danger';
      if (val >= 5.6) return 'warning';
      return 'normal';
    },
    getGlucoseTextClass(val) {
      return (val >= 5.6) ? 'text-danger-bold' : '';
    },

    getOverallBadge(session) {
      if (session.sys >= 140 || session.dia >= 90 || session.glucose >= 7.0) return 'badge-red';
      if (session.sys >= 130 || session.glucose >= 5.6) return 'badge-orange';
      return 'badge-green';
    },
    getOverallLabel(session) {
      if (session.sys >= 140 || session.dia >= 90 || session.glucose >= 7.0) return 'Action Required';
      if (session.sys >= 130 || session.glucose >= 5.6) return 'Monitor';
      return 'Healthy';
    }
  }
};
</script>

<style scoped>
/* LAYOUT */
.health-stats-container {
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
.staff-meta {
  color: #555;
  font-size: 1.1em;
  margin-top: 5px;
}
.back-btn {
  padding: 8px 16px;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

/* REFERENCE SECTION STYLES */
.reference-section {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}
.ref-box {
  flex: 1;
  background: white;
  padding: 15px;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-left: 5px solid #ccc;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.ref-box.target { border-left-color: #28a745; } /* Green */
.ref-box.limit { border-left-color: #dc3545; }  /* Red */

.ref-label { font-weight: bold; color: #555; font-size: 0.9em; }
.ref-values { font-size: 1.5em; font-weight: bold; color: #333; }
.ref-val small { font-size: 0.4em; color: #999; vertical-align: middle; }

/* TABLE TWEAKS */
.text-center { text-align: center; }
.time-col { color: #888; font-family: monospace; }
.notes-text { font-style: italic; color: #666; font-size: 0.9em; }

/* Cell Highlights */
.cell-danger { background-color: #f8d7da; color: #721c24; }
.cell-warning { background-color: #fff3cd; color: #856404; }

/* CARDS */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}
.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  text-align: center;
  border-top: 5px solid transparent;
}
.card-title { color: #888; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
.card-value { font-size: 2.5em; font-weight: bold; margin: 10px 0; color: #333; }
.card-value small { font-size: 0.4em; color: #999; }
.card-status { font-weight: 600; padding: 4px 8px; border-radius: 4px; display: inline-block; font-size: 0.85em; }

/* Status Colors */
.stat-card.normal { border-color: #28a745; }
.stat-card.normal .card-status { background: #d4edda; color: #155724; }

.stat-card.warning { border-color: #ffc107; }
.stat-card.warning .card-status { background: #fff3cd; color: #856404; }

.stat-card.warning-light { border-color: #ffc107; } /* Yellow for elevated */
.stat-card.warning-light .card-status { background: #fff9db; color: #856404; }

.stat-card.danger { border-color: #dc3545; }
.stat-card.danger .card-status { background: #f8d7da; color: #721c24; }

/* CHARTS */
.charts-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 30px;
}
.chart-box {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.chart-container {
  height: 300px;
  width: 100%;
}

/* TABLE */
.history-table-section {
  background: white;
  padding: 25px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th { text-align: left; padding: 12px; background: #f1f3f5; color: #495057; border-bottom: 2px solid #dee2e6; }
td { padding: 12px; border-bottom: 1px solid #dee2e6; }
tr:last-child td { border-bottom: none; }

/* ROW HIGHLIGHTS */
.row-critical {
  background-color: #fff5f5; /* Very light red */
}
.row-critical:hover {
  background-color: #ffe3e3; /* Slightly darker on hover */
}

.row-elevated {
  background-color: #fff9db; /* Very light yellow */
}
.row-elevated:hover {
  background-color: #fff3cd;
}

/* Ensure alternating row colors (zebra striping) don't clash too hard */
table tr { transition: background-color 0.2s; }

.text-danger-bold { color: #dc3545; font-weight: bold; }

/* Badges */
.badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; }
.badge-green { background: #d4edda; color: #155724; }
.badge-orange { background: #fff3cd; color: #856404; }
.badge-red { background: #f8d7da; color: #721c24; }
</style>