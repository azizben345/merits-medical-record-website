<template>
  <div class="dashboard">
    <h2>Statistics — Overview</h2>

    <!-- Legend / Session Selector --> 
    <div class="legend">
      <button
        class="chip"
        :class="{active: activeSessionId === null}"
        @click="setActiveSession(null)"
        title="Show all sessions"
      >
        <div class="chip-title">All</div>
        <div class="chip-sub">Abnormal labs: {{ totalsAll.abnormalLabs }}</div>
      </button>

      <button
        v-for="s in sortedSessions"
        :key="s.session_id"
        class="chip"
        :class="{active: activeSessionId === s.session_id}"
        @click="setActiveSession(s.session_id)"
        :title="fmtDate(s.session_date)"
      >
        <div class="chip-title">{{ yearOf(s.session_date) }}</div>
        <div class="chip-sub">Abnormal labs: {{ abnormalBySession[s.session_id] ?? 0 }}</div>
      </button>
    </div>

    <!-- KPI Cards -->
    <h3>Latest Values:</h3>
    <div class="kpis" v-if="vitalsFiltered.length || labsFiltered.length">
      <div class="card">
        <div class="card-label">BMI</div>
        <div class="card-main">
          <span class="big">{{ kpiBMI.value ?? '-' }}</span>
          <span class="delta" :class="deltaClass(kpiBMI.delta)">{{ prettyDelta(kpiBMI.delta) }}</span>
        </div>
        <div class="card-sub">{{ kpiBMI.caption }}</div>
      </div>

      <div class="card">
        <div class="card-label">Blood Pressure</div>
        <div class="card-main">
          <span class="big">{{ kpiBP.value ?? '-' }}</span>
          <span class="delta" :class="deltaClass(kpiBP.delta)">{{ prettyDelta(kpiBP.delta) }}</span>
        </div>
        <div class="card-sub">Systolic / Diastolic (mmHg)</div>
      </div>

      <div class="card">
        <div class="card-label">Pulse</div>
        <div class="card-main">
          <span class="big">{{ kpiPulse.value ?? '-' }}</span>
          <span class="delta" :class="deltaClass(kpiPulse.delta)">{{ prettyDelta(kpiPulse.delta) }}</span>
        </div>
        <div class="card-sub">Beats per minute</div>
      </div>

      <div class="card">
        <div class="card-label">Abnormal Labs</div>
        <div class="card-main">
          <span class="big">{{ kpiLabs.abnormal }}</span>
        </div>
        <div class="card-sub">
          Normal: {{ kpiLabs.normal }} · Not done: {{ kpiLabs.notdone }}
        </div>
      </div>
    </div>

    <!-- Quick Tables -->
    <div class="grid">
      <div class="panel">
        <div class="panel-title">Latest Vitals (in selection)</div>
        <table>
          <thead>
            <tr>
              <th>Session</th>
              <th>Weight (kg)</th>
              <th>Height (m)</th>
              <th>BMI</th>
              <th>BP (Sys/Dia)</th>
              <th>Pulse</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in vitalsFiltered.slice().reverse().slice(0,5)" :key="v.session_id">
              <td>{{ yearOf(v.session_date) }}</td>
              <td>{{ v.weight_kg ?? '-' }}</td>
              <td>{{ v.height_m ?? '-' }}</td>
              <td>{{ v.bmi ?? '-' }}</td>
              <td>{{ v.bp_sys ?? '-' }}/{{ v.bp_dia ?? '-' }}</td>
              <td>{{ v.pulse_bpm ?? '-' }}</td>
            </tr>
            <tr v-if="!vitalsFiltered.length">
              <td colspan="6" class="empty">No vitals in this selection.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-title">Key Lipids (LDL / Total Chol / HDL)</div>
        <table>
          <thead>
            <tr>
              <th>Session</th>
              <th>LDL</th>
              <th>Total Chol</th>
              <th>HDL</th>
              <th>Abnormal?</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in labsFiltered.slice().reverse().slice(0,5)" :key="l.session_id">
              <td>{{ yearOf(l.session_date) }}</td>
              <td>{{ l.ldl_result ?? 'Not done' }}</td>
              <td>{{ l.tchol_result ?? 'Not done' }}</td>
              <td>{{ l.hdl_result ?? 'Not done' }}</td>
              <td>
                <span :class="badgeClass(isRowAbnormal(l))">
                  {{ isRowAbnormal(l) ? 'Yes' : 'No' }}
                </span>
              </td>
            </tr>
            <tr v-if="!labsFiltered.length">
              <td colspan="5" class="empty">No lab rows in this selection.</td>
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
export default {
  name: 'AnalyticsDashboard',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      sessions: [],
      vitals: [],
      labs: [],
      activeSessionId: null, // null = All
      abnormalBySession: {}, // {session_id: count}
    };
  },
  computed: {
    // Filters and Sorting
    vitalsFiltered() {
      if (!this.activeSessionId) return this.vitals;
      return this.vitals.filter(v => v.session_id === this.activeSessionId);
    },
    labsFiltered() {
      if (!this.activeSessionId) return this.labs;
      return this.labs.filter(v => v.session_id === this.activeSessionId);
    },
    sortedSessions() {
        // Return a copy of the sessions array sorted by date descending (newest first)
        return [...this.sessions].sort((a, b) => {
            // Convert dates to time values for comparison
            const dateA = new Date(a.session_date).getTime();
            const dateB = new Date(b.session_date).getTime();

            // Sort descending (b - a)
            return dateB - dateA;
        });
    },

    // Totals for “All” chip
    totalsAll() {
      const counts = this.countLabStatuses(this.labs);
      return { abnormalLabs: counts.Abnormal };
    },

    // KPIs (based on latest rows within current selection)
    kpiBMI() {
      const latest = this.lastByDate(this.vitalsFiltered);
      const prev   = this.prevByDate(this.vitalsFiltered);
      const value = latest?.bmi ?? null;
      const delta = (latest?.bmi != null && prev?.bmi != null) ? +(latest.bmi - prev.bmi).toFixed(1) : null;
      return { value, delta, caption: latest ? `Latest session: ${this.yearOf(latest.session_date)}` : '—' };
    },
    kpiBP() {
      const latest = this.lastByDate(this.vitalsFiltered);
      const prev   = this.prevByDate(this.vitalsFiltered);
      const value = (latest?.bp_sys != null && latest?.bp_dia != null) ? `${latest.bp_sys}/${latest.bp_dia}` : null;
      let delta = null;
      if (latest?.bp_sys != null && prev?.bp_sys != null && latest?.bp_dia != null && prev?.bp_dia != null) {
        delta = `↑${latest.bp_sys - prev.bp_sys}/↑${latest.bp_dia - prev.bp_dia}`;
        const ds = latest.bp_sys - prev.bp_sys;
        const dd = latest.bp_dia - prev.bp_dia;
        // encode a signed feel: if either goes down more than up, show negative-ish
        if (ds <= 0 && dd <= 0) delta = `↓${Math.abs(ds)}/${Math.abs(dd)}`;
      }
      return { value, delta, caption: latest ? `Latest session: ${this.yearOf(latest.session_date)}` : '—' };
    },
    kpiPulse() {
      const latest = this.lastByDate(this.vitalsFiltered);
      const prev   = this.prevByDate(this.vitalsFiltered);
      const value = latest?.pulse_bpm ?? null;
      const delta = (latest?.pulse_bpm != null && prev?.pulse_bpm != null) ? latest.pulse_bpm - prev.pulse_bpm : null;
      return { value, delta, caption: latest ? `Latest session: ${this.yearOf(latest.session_date)}` : '—' };
    },
    kpiLabs() {
      const counts = this.countLabStatuses(this.labsFiltered);
      return {
        abnormal: counts.Abnormal,
        normal: counts.Normal,
        notdone: counts['Not done']
      };
    }
  },
  async mounted() {
    await this.loadData();
    this.buildAbnormalIndex();
    this.activeSessionId = null; // view All Session by default
  },
  methods: {
    fmtDate(d) { return new Date(d).toLocaleDateString(); },
    yearOf(d) { return new Date(d).getFullYear(); },

    async loadData() {
      const userInfo = JSON.parse(localStorage.getItem('user_info'));
      if (!userInfo) return;
      const staffXYZ = userInfo.email.replace(/\./g, 'XYZ');
      const headers = { 
        Authorization: `Bearer ${localStorage.getItem('jwt_token')}` 
      };

      const [sessions, vitals, labs] = await Promise.all([
        // only one need to check for unauthorized
        fetch(`${this.baseUrl}/checkup-sessions/stats/${staffXYZ}`, { headers }).then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        }),
        fetch(`${this.baseUrl}/stats/vitals/${staffXYZ}`, { headers }).then(r => r.json()),
        fetch(`${this.baseUrl}/stats/labs/${staffXYZ}?fields=ldl_result,tchol_result,hdl_result`, { headers }).then(r => r.json())
      ]);

      this.sessions = (sessions || []).sort((a,b) => new Date(a.session_date) - new Date(b.session_date));
      this.vitals   = (vitals   || []).sort((a,b) => new Date(a.session_date) - new Date(b.session_date));
      this.labs     = (labs     || []).sort((a,b) => new Date(a.session_date) - new Date(b.session_date));

      // default to latest session
      this.activeSessionId = this.sessions.at(-1)?.session_id ?? null;
    },

    setActiveSession(id) {
      this.activeSessionId = id; // null = All
    },

    // Build per-session abnormal lab counts for the chips
    buildAbnormalIndex() {
      const by = {};
      for (const row of this.labs) {
        const sid = row.session_id;
        const vals = [row.ldl_result, row.tchol_result, row.hdl_result].filter(Boolean);
        const abnormal = vals.filter(v => v === 'Abnormal').length;
        by[sid] = (by[sid] ?? 0) + abnormal;
      }
      this.abnormalBySession = by;
    },

    // Utilities
    lastByDate(arr) {
      if (!arr.length) return null;
      return arr.slice().sort((a,b) => new Date(a.session_date) - new Date(b.session_date)).at(-1);
    },
    prevByDate(arr) {
      if (arr.length < 2) return null;
      const s = arr.slice().sort((a,b) => new Date(a.session_date) - new Date(b.session_date));
      return s.at(-2);
    },
    countLabStatuses(rows) {
      const counts = { Normal:0, Abnormal:0, 'Not done':0 };
      for (const r of rows) {
        [r.ldl_result, r.tchol_result, r.hdl_result].forEach(v => {
          const k = v ?? 'Not done';
          if (counts[k] == null) counts[k] = 0;
          counts[k] += 1;
        });
      }
      return counts;
    },
    isRowAbnormal(l) {
      return [l.ldl_result, l.tchol_result, l.hdl_result].some(v => v === 'Abnormal');
    },
    deltaClass(delta) {
      if (delta == null) return '';
      if (typeof delta === 'string') {
        // crude parse: string like "↓x/y" or "↑x/y"
        return delta.startsWith('↓') ? 'neg' : 'pos';
      }
      return delta < 0 ? 'neg' : (delta > 0 ? 'pos' : '');
    },
    prettyDelta(delta) {
      if (delta == null) return '—';
      if (typeof delta === 'string') return delta;
      return (delta > 0 ? `+${delta}` : `${delta}`);
    },
    badgeClass(isBad) {
      return isBad ? 'badge bad' : 'badge good';
    }
  }
};
</script>

<style scoped>
.dashboard { padding: 24px; }

/* Legend / session chips */
.legend {
  display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 14px;
}
.chip {
  border:1px solid #e2e8f0; background:#fff; border-radius:10px; padding:8px 10px;
  display:flex; flex-direction:column; gap:2px; min-width:90px; cursor:pointer;
}
.chip:hover { background:#f7fafc; }
.chip.active { background:#2b6cb0; color:#fff; border-color:#2b6cb0; }
.chip-sub { font-size:12px; opacity:0.9; }

/* KPIs */
.kpis {
  display:grid; grid-template-columns: repeat(4, minmax(180px, 1fr));
  gap: 12px; margin-bottom: 16px;
} @media (max-width: 1000px) { 
  .kpis { 
    grid-template-columns: 1fr 1fr; 
    /* max-width: fit-content;  */
  } 
} 

.card {
  background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.card-label { font-size:12px; color:#4a5568; margin-bottom:4px; }
.card-main { display:flex; align-items:baseline; gap:10px; }
.big { font-size:24px; font-weight:700; color:#2d3748; }
.delta { font-size:12px; }
.delta.pos { color:#0f766e; }
.delta.neg { color:#b91c1c; }
.card-sub { font-size:12px; color:#64748b; margin-top:4px; }

.grid {
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
} @media (max-width: 1190px) { .grid { grid-template-columns: 1fr; } }

/* Panels */
.panel {
  background:#fff; border:1px solid #e2e8f0; 
  border-radius:10px; padding:12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  width: 100%;
}
.panel-title { font-weight:600; color:#2d3748; margin-bottom:8px; }

table { width:100%; border-collapse: collapse; background:white; }
th, td { padding:10px 12px; border-bottom:1px solid #e2e8f0; text-align:left; }
th { background:#f7fafc; color:#2d3748; font-weight:600; }
.empty { color:#64748b; text-align:center; }

/* Badges */
.badge {
  font-size:12px; padding:2px 8px; border-radius:12px;
  display:inline-block; border:1px solid transparent;
}
.badge.good { background:#ecfdf5; color:#065f46; border-color:#d1fae5; }
.badge.bad  { background:#fef2f2; color:#991b1b; border-color:#fee2e2; }


</style>
