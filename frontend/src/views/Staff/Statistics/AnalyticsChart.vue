<template>
  <div class="analytics-charts">
    <h2>Health Statistics</h2>

    <div class="session-bar" v-if="sessions.length">
      <span class="hint">Sessions</span>
      <span
        v-for="s in sessions"
        :key="s.session_id"
        class="session-pill"
        :title="fmtDate(s.session_date)"
      >
        {{ new Date(s.session_date).getFullYear() }}
      </span>
    </div>

    <div class="grid">
      <div class="card">
        <div class="card-title">BMI Trend</div>
        <canvas ref="bmiCanvas" height="120"></canvas>
      </div>

      <div class="card">
        <div class="card-title">Blood Pressure Trend</div>
        <canvas ref="bpCanvas" height="120"></canvas>
      </div>

      <div class="card">
        <div class="card-title">Pulse Trend</div>
        <canvas ref="pulseCanvas" height="120"></canvas>
      </div>

      <div class="card wide">
        <div class="card-title">Key Lipids by Session (Normal / Abnormal / Not done)</div>
        <canvas ref="lipidsCanvas" height="160"></canvas>
      </div>
    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { Chart, LineController, BarController, LineElement, BarElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler, Title } from 'chart.js';
Chart.register(LineController, BarController, LineElement, BarElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler, Title);

export default {
  name: 'AnalyticsCharts',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      sessions: [],
      vitals: [],
      labs: [],
      charts: {
        bmi: null,
        bp: null,
        pulse: null,
        lipids: null
      }
    };
  },
  async mounted() {
    await this.loadData();
    this.renderAllCharts();
    window.addEventListener('resize', this.resizeCharts);
  },
  beforeUnmount() {
    this.destroyCharts();
    window.removeEventListener('resize', this.resizeCharts);
  },
  methods: {
    fmtDate(d) { return new Date(d).toLocaleDateString(); },

    async loadData() {
      const userInfo = JSON.parse(localStorage.getItem('user_info'));
      if (!userInfo) return;
      const staffXYZ = userInfo.email.replace(/\./g, 'XYZ');
      const headers = { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };

      const [sessions, vitals, labs] = await Promise.all([
        fetch(`${this.baseUrl}/checkup-sessions/stats/${staffXYZ}`, { headers }).then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        }),
        fetch(`${this.baseUrl}/stats/vitals/${staffXYZ}`, { headers }).then(r => r.json()),
        fetch(`${this.baseUrl}/stats/labs/${staffXYZ}?fields=ldl_result,tchol_result,hdl_result`, { headers }).then(r => r.json())
      ]);

      // Ensure arrays + sort by date ascending
      this.sessions = (sessions || []).sort((a, b) => new Date(a.session_date) - new Date(b.session_date));
      this.vitals = (vitals || []).sort((a, b) => new Date(a.session_date) - new Date(b.session_date));
      this.labs = (labs || []).sort((a, b) => new Date(a.session_date) - new Date(b.session_date));
    },

    // ---- Chart helpers ----
    destroyCharts() {
      Object.keys(this.charts).forEach(k => {
        if (this.charts[k]) { this.charts[k].destroy(); this.charts[k] = null; }
      });
    },
    resizeCharts() {
      Object.values(this.charts).forEach(c => c && c.resize());
    },

    renderAllCharts() {
      this.destroyCharts();
      this.renderBMI();
      this.renderBP();
      this.renderPulse();
      this.renderLipids();
    },

    // 1) BMI line
    renderBMI() {
      const ctx = this.$refs.bmiCanvas?.getContext('2d'); if (!ctx) return;
      const labels = this.vitals.map(v => new Date(v.session_date).getFullYear());
      const data = this.vitals.map(v => v.bmi ?? null);

      this.charts.bmi = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'BMI',
            data,
            tension: 0.3,
            fill: false,
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            tooltip: { mode: 'index', intersect: false }
          },
          scales: {
            y: { beginAtZero: false, title: { display: true, text: 'BMI' } },
            x: { title: { display: true, text: 'Session (Year)' } }
          }
        }
      });
    },

    // 2) BP line (Sys & Dia)
    renderBP() {
      const ctx = this.$refs.bpCanvas?.getContext('2d'); if (!ctx) return;
      const labels = this.vitals.map(v => new Date(v.session_date).getFullYear());
      const sys = this.vitals.map(v => v.bp_sys ?? null);
      const dia = this.vitals.map(v => v.bp_dia ?? null);

      this.charts.bp = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Systolic', data: sys, tension: 0.3, pointRadius: 3 },
            { label: 'Diastolic', data: dia, tension: 0.3, pointRadius: 3 }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            tooltip: { mode: 'index', intersect: false }
          },
          scales: {
            y: { beginAtZero: false, title: { display: true, text: 'mmHg' } },
            x: { title: { display: true, text: 'Session (Year)' } }
          }
        }
      });
    },

    // 3) Pulse line
    renderPulse() {
      const ctx = this.$refs.pulseCanvas?.getContext('2d'); if (!ctx) return;
      const labels = this.vitals.map(v => new Date(v.session_date).getFullYear());
      const pulse = this.vitals.map(v => v.pulse_bpm ?? null);

      this.charts.pulse = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{ label: 'Pulse (bpm)', data: pulse, tension: 0.3, pointRadius: 3 }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: {
            y: { beginAtZero: false, title: { display: true, text: 'bpm' } },
            x: { title: { display: true, text: 'Session (Year)' } }
          }
        }
      });
    },

    // 4) Lipids stacked bar (Normal / Abnormal / Not done) for LDL, Tchol, HDL
    renderLipids() {
      const ctx = this.$refs.lipidsCanvas?.getContext('2d'); if (!ctx) return;

      // One bar per session, each bar is stacked: Normal / Abnormal / Not done count among the 3 fields
      const labels = this.labs.map(v => new Date(v.session_date).getFullYear());

      const perSessionCounts = this.labs.map(row => {
        const vals = [
          row.ldl_result ?? 'Not done',
          row.tchol_result ?? 'Not done',
          row.hdl_result ?? 'Not done'
        ];
        return {
          normal: vals.filter(v => v === 'Normal').length,
          abnormal: vals.filter(v => v === 'Abnormal').length,
          notdone: vals.filter(v => v === 'Not done').length
        };
      });

      const normal = perSessionCounts.map(x => x.normal);
      const abnormal = perSessionCounts.map(x => x.abnormal);
      const notdone = perSessionCounts.map(x => x.notdone);

      this.charts.lipids = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Normal',   data: normal,   stack: 'lipids', backgroundColor: '#008000' },
            { label: 'Abnormal', data: abnormal, stack: 'lipids', backgroundColor: '#ff0000' },
            { label: 'Not done', data: notdone,  stack: 'lipids', backgroundColor: '#cccccc' }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: {
            x: { stacked: true, title: { display: true, text: 'Session (Year)' } },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, title: { display: true, text: 'Count of Tests' } }
          }
        }
      });
    }
  }
};
</script>

<style scoped>
.analytics-charts { padding: 24px; }
.session-bar { display:flex; align-items:center; gap:8px; margin-bottom: 16px; flex-wrap: wrap; }
.session-pill { background:#edf2f7; border-radius: 14px; padding:4px 10px; font-size:12px; color:#2d3748; }
.hint { color:#4a5568; margin-right:6px; font-size:12px; }

.card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
.card.wide { grid-column: 1 / -1; }
.card-title { font-weight:600; color:#2d3748; margin-bottom:10px; font-size:14px; }
</style>
