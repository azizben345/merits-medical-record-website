<template>
  <div class="ns-page">
    <h2>My Health Trends</h2>

    <!-- <div class="topbar">
      <div class="summary-bar">
        <span class="muted">Viewing data for: </span>
        <strong>{{ staffEmail }}</strong>
      </div>
    </div> -->

    <div v-if="loading" class="loading-box">Loading charts...</div>

    <div v-else>
      
      <!-- <h3 class="section-header">Vitals & Measurements</h3> -->
      <div class="trends-grid">
        
        <div class="box chart-box">
          <h4>Total Cholesterol</h4>
          <TrendChart 
            :labels="years" 
            :datasets="[ds('Cholesterol', 'chol_score', '#f39c12')]" 
          />
        </div>

        <div class="box chart-box">
          <h4>Blood Pressure</h4>
          <TrendChart 
            :labels="years" 
            :datasets="[
              ds('Systolic', 'bp_systolic', '#e74c3c'),
              ds('Diastolic', 'bp_diastolic', '#3498db'),
              limitDs(140, 'Limit (140)'),
              limitDs(90, 'Limit (90)')
            ]" 
          />
        </div>

        <div class="box chart-box">
          <h4>BMI</h4>
          <TrendChart 
            :labels="years" 
            :datasets="[
              ds('BMI', 'bmi', '#9b59b6'),
              limitDs(25, 'Overweight (25)')
            ]" 
          />
        </div>

        <div class="box chart-box">
          <h4>Uric Acid</h4>
          <TrendChart 
            :labels="years" 
            :datasets="[ds('Uric Acid', 'uric_score', '#2ecc71')]" 
          />
        </div>

      </div> 
        <!-- <h3 class="section-header">Medical Status History</h3> -->
      <div class="box chart-box full-width-box" style="margin-top: 1.5rem;">
        
        <div class="table-responsive">
          <table class="status-matrix">
            <thead>
              <tr>
                <th class="sticky-col">Test Parameter</th>
                <th v-for="(item, i) in items" :key="i">{{ item.year_label }}</th>
              </tr>
            </thead>
            <tbody>
              
              <tr>
                <td class="sticky-col row-label">ECG</td>
                <td v-for="(item, i) in items" :key="i">
                  <span class="status-pill" :class="getBadgeClass(item.ecg_score, 1)">
                    {{ item.ecg_score === 1 ? 'Normal' : (item.ecg_score === 0 ? 'Abnormal' : '-') }}
                  </span>
                </td>
              </tr>

              <tr>
                <td class="sticky-col row-label">Spirometry</td>
                <td v-for="(item, i) in items" :key="i">
                  <span class="status-pill" :class="getBadgeClass(item.spiro_score, 1)">
                    {{ item.spiro_score === 1 ? 'Normal' : (item.spiro_score === 0 ? 'Abnormal' : '-') }}
                  </span>
                </td>
              </tr>

              <tr>
                <td class="sticky-col row-label">Audiometry</td>
                <td v-for="(item, i) in items" :key="i">
                  <span class="status-pill" :class="getBadgeClass(item.audio_score, 1)">
                    {{ item.audio_score === 1 ? 'Normal' : (item.audio_score === 0 ? 'Abnormal' : '-') }}
                  </span>
                </td>
              </tr>

              <tr>
                <td class="sticky-col row-label">Diabetic Indicator</td>
                <td v-for="(item, i) in items" :key="i">
                  <span class="status-pill" :class="getBadgeClass(item.is_diabetic, 0)">
                    {{ item.is_diabetic === 1 ? 'Yes' : (item.is_diabetic === 0 ? 'No' : '-') }}
                  </span>
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import TrendChart from '@/components/TrendChart.vue';

export default {
  name: 'AnalyticsChart2',
  components: { TrendChart },
  props: {
    staff_email: String
  },
  data() {
    return {
      loading: false,
      baseUrl: cfg.API_BASE_URL,
      staffEmail: '',
      items: [], // Raw API data
    };
  },
  computed: {
    // Extract unique years for X-Axis
    years() {
      return this.items.map(i => i.year_label);
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.loading = true;
      
      this.staffEmail = 
        // this.$route.params.staffEmail || 
        this.staff_email || 
        JSON.parse(localStorage.getItem('user_info')).email || '';
      const staffEmailXYZ = this.staffEmail.replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/staff/health-trends/${staffEmailXYZ}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        this.items = data.items || [];
        // Extract email from local storage or the first item for display
        const user = JSON.parse(localStorage.getItem('user_info') || '{}');
        this.staffEmail = user.email || 'Me';
      })
      .catch(err => console.error(err))
      .finally(() => {
        this.loading = false;
      });
    },

    /**
     * @param {String} label - Legend label
     * @param {String} key - Key in the API item object
     * @param {String} color - Hex color
     * @param {Boolean} isStep - If true, make lines sharper
     */
    ds(label, key, color, isStep = false) {
      return {
        label: label,
        data: this.items.map(row => row[key]),
        borderColor: color,
        backgroundColor: color,
        tension: isStep ? 0 : 0.3,
        fill: false,
        pointRadius: 4, 
        pointHoverRadius: 6,
        pointHitRadius: 10,
      };
    },

    /**
     * LIMIT LINE (The Dotted Threshold)
     * @param {Number} value - Y-axis value (e.g., 140)
     * @param {String} label - Legend label (e.g., "Limit")
     */
    limitDs(value, label) {
      return {
        label: label,
        data: this.items.map(() => value), // Flat line
        borderColor: '#9CA3AF', // Cool Grey (subtle)
        borderWidth: 2,
        borderDash: [6, 6], // Dotted effect
        pointRadius: 0, // No dots on this line
        fill: false,
        order: 1 // Push to back so data line sits on top
      };
    },
    /**
     * to determine badge color
     * @param {Number} val - The actual value from database
     * @param {Number} target - The "Good" value
     */
    getBadgeClass(val, target) {
      if (val === null || val === undefined) return 'bg-grey';
      return val === target ? 'bg-green' : 'bg-red';
    },
  }
};
</script>

<style scoped>
.ns-page { padding: 20px; }
.topbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
.ghost { background: transparent; border: 1px solid #ccc; padding: 6px 12px; cursor: pointer; }
.box { 
  background: #fff; 
  padding: 15px; 
  border-radius: 8px; 
  box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
  border: 1px solid #eee;
}

/* Heatmap Style start */
/* Full width container override */
.full-width-box {
  grid-column: 1 / -1; /* Make this box span the entire width of the grid */
}

/* Scrollable Table Container */
.table-responsive {
  overflow-x: auto;
  border: 1px solid #eee;
  border-radius: 6px;
}

/* The Table */
.status-matrix {
  width: 100%;
  border-collapse: collapse;
  min-width: 600px; /* Force scroll on tiny screens */
}

.status-matrix th, 
.status-matrix td {
  padding: 12px 15px;
  text-align: center;
  border-bottom: 1px solid #eee;
  border-right: 1px solid #f5f5f5;
  white-space: nowrap;
}

/* Headers */
.status-matrix thead th {
  background-color: #f8f9fa;
  color: #666;
  font-size: 0.85rem;
  text-transform: uppercase;
  font-weight: 700;
}

/* First Column (Labels) Styling */
.status-matrix .row-label {
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  background-color: #fff; /* Ensure label covers scroll content */
  min-width: 150px;
}

/* Sticky Column Logic (Optional: Keeps 'Test Name' visible when scrolling) */
.sticky-col {
  position: sticky;
  left: 0;
  z-index: 2;
  border-right: 2px solid #e2e8f0 !important; /* separation line */
}

/* Status Pills */
.status-pill {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  color: white;
  min-width: 70px;
}

/* Colors */
.bg-green { background-color: #2ecc71; }
.bg-red   { background-color: #e74c3c; }
.bg-grey  { background-color: #cbd5e0; color: #718096; }
/* Heatmap Style end */

/* Grid for the charts */
.trends-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
}
.chart-box h4 { margin-top: 0; margin-bottom: 10px; color: #333; }
.muted { color: #888; font-size: 0.9em; }
.loading-box {
  text-align: center;
  padding: 40px;
  color: #666;
}
/* The container for the year boxes */
.status-grid {
  display: flex;
  gap: 4px;
  margin-top: 10px;
  overflow-x: auto;
}
/* Individual Box */
.status-box {
  flex: 1;
  min-width: 40px;
  text-align: center;
  padding: 8px 2px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: bold;
  color: white;
  position: relative;
}
/* Colors based on value */
.box-normal { background-color: #2ecc71; } /* Green */
.box-abnormal { background-color: #e74c3c; } /* Red */
.box-null { background-color: #e2e8f0; color: #999; } /* Grey */
/* Year Label inside the box */
.box-year {
  font-size: 10px;
  opacity: 0.8;
  display: block;
  margin-bottom: 2px;
}

</style>