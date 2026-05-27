<template>
  <div class="dashboard">
    <h2>Statistics — Overview (all)</h2>

    <!-- Top: search + year chips -->
    <div class="topbar">
      <input
        v-model="q"
        type="search"
        placeholder="Search staff by name or email…"
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
          <div class="chip-sub">Abnormal labs: {{ totalsAll.abnormalLabs }}</div>
        </button>

        <button
          v-for="y in years"
          :key="y"
          class="chip"
          :class="{active: activeYear === y}"
          @click="setYear(y)"
        >
          <div class="chip-title">{{ y }}</div>
          <div class="chip-sub">Abnormal labs: {{ abnormalByYear[y] ?? 0 }}</div>
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpis" v-if="hasAnyData">
      <div class="card">
        <div class="card-label">Staff Covered</div>
        <div class="card-main"><span class="big">{{ kpi.staffCount }}</span></div>
        <div class="card-sub">No. of staff considered in tables</div>
      </div>
      <div class="card">
        <div class="card-label">Session Count</div>
        <div class="card-main"><span class="big">{{ kpi.sessionCount }}</span></div>
        <div class="card-sub">From summary (current year filter)</div>
      </div>
      <div class="card">
        <div class="card-label">Abnormal Lab Results</div>
        <div class="card-main"><span class="big">{{ kpi.abnormal }}</span></div>
        <div class="card-sub">LDL / Total Chol / HDL combined</div>
      </div>
      <div class="card">
        <div class="card-label">% Abnormal (labs)</div>
        <div class="card-main"><span class="big">{{ kpi.pctAbnormal }}</span></div>
        <div class="card-sub">Of lipid results (admin totals)</div>
      </div>
    </div>

    <!-- Tables -->
    <div class="grid">
      <div class="panel">
        <div class="panel-title">Latest Vitals (by staff, in selection)</div>
        <table>
          <thead>
            <tr>
              <th>Staff</th>
              <th>Session</th>
              <th>Weight (kg)</th>
              <th>Height (m)</th>
              <th>BMI</th>
              <th>BP (Sys/Dia)</th>
              <th>Pulse</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in latestVitalsByStaff.slice(0, 12)" :key="row._key">
              <td>
                <div class="pair">
                  <!-- <span class="k">Name:</span> -->
                  <span class="v">{{ row.staff_name || '-' }}</span>
                </div>
                <!-- <div class="pair">
                  <span class="k">Email:</span>
                  <span class="v">{{ row.staff_email }}</span>
                </div> -->
              </td>
              <td>{{ yearOf(row.session_date) }}</td>
              <td>{{ row.weight_kg ?? '-' }}</td>
              <td>{{ row.height_m ?? '-' }}</td>
              <td>{{ row.bmi ?? '-' }}</td>
              <td>{{ row.bp_sys ?? '-' }}/{{ row.bp_dia ?? '-' }}</td>
              <td>{{ row.pulse_bpm ?? '-' }}</td>
            </tr>
            <tr v-if="!latestVitalsByStaff.length">
              <td colspan="7" class="empty">No vitals for this selection.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-title">Key Lipids (LDL / Total Chol / HDL)</div>
        <table>
          <thead>
            <tr>
              <th>Staff</th>
              <th>Session</th>
              <th>LDL</th>
              <th>Total Chol</th>
              <th>HDL</th>
              <th>Abnormal?</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in labsFiltered.slice().reverse().slice(0, 20)" :key="l.session_id + '_' + l.staff_email">
              <td>
                <div class="pair">
                  <!-- <span class="k">Name:</span> -->
                  <span class="v">{{ l.staff_name || '-' }}</span>
                </div>
                <!-- <div class="pair">
                  <span class="k">Email:</span>
                  <span class="v">{{ l.staff_email }}</span>
                </div> -->
              </td>
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
              <td colspan="6" class="empty">No lab rows in this selection.</td>
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
  name: 'AnalyticsAllPage',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      // Aggregates from admin endpoints
      summary: null,         // /admin/stats/summary
      staffMap: new Map(),   // /admin/staff-list (email -> name)
      labsSeriesYear: [],    // /admin/stats/labs (groupBy=year) -> for chips
      // Tables (built from top staff latest rows)
      vitals: [],            // per-staff latest rows
      labs: [],              // per-staff recent rows (flattened)
      // UI state
      activeYear: null,      // null = All
      q: '',                 // search (name/email)
      abnormalByYear: {},    // {year: count}
    };
  },
  computed: {
    hasAnyData() {
      return !!(this.summary || this.vitals.length || this.labs.length);
    },
    years() {
      // Prefer years from labsSeriesYear buckets
      const ys = new Set(
        (this.labsSeriesYear || [])
          .map(r => this.yearFromBucket(r.bucket))
          .filter(Boolean)
      );
      return [...ys].sort((a,b) => b-a);
    },
    // Filtered by year, then by text query
    vitalsFiltered() {
      let arr = this.filterByYear(this.vitals);
      return this.filterByQuery(arr);
    },
    labsFiltered() {
      let arr = this.filterByYear(this.labs);
      return this.filterByQuery(arr);
    },
    totalsAll() {
      // From admin year series (all years)
      const totals = this.sumAbnormalFromSeries(this.labsSeriesYear);
      return { abnormalLabs: totals.abnormal };
    },
    kpi() {
      // Session count from summary.sessions_by_period (respecting year filter)
      const sessionCount = this.sessionCountFromSummary();

      // Lab abnormal % from admin totals, respecting year filter
      const { abnormal, normal, notdone } = this.sumAbnormalFromSeries(this.filteredYearSeries());
      const totalLabResults = abnormal + normal + notdone;
      const pctAbn = totalLabResults ? Math.round((abnormal / totalLabResults) * 100) + '%' : '—';

      // Staff covered = unique in current filtered per-staff rows
      const staffSet = new Set(this.vitalsFiltered.map(s => s.staff_email));
      return {
        staffCount: staffSet.size,
        sessionCount,
        abnormal,
        pctAbnormal: pctAbn
      };
    },
    // Latest vitals per staff (within selection)
    latestVitalsByStaff() {
      const by = new Map(); // email -> latest row
      for (const v of this.vitalsFiltered) {
        const k = v.staff_email;
        const cur = by.get(k);
        if (!cur || new Date(v.session_date) > new Date(cur.session_date)) {
          by.set(k, v);
        }
      }
      const out = [...by.values()].sort((a,b) => new Date(b.session_date) - new Date(a.session_date));
      out.forEach((r, i) => r._key = r.session_id + '_' + r.staff_email + '_' + i);
      return out;
    }
  },
  async mounted() {
    await this.loadData();
    this.buildAbnormalByYear();
  },
  methods: {
    // ---- Fetching ----
    async loadData() {
      const headers = { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };

      // 0) fetch staff list and build a Map
      const staffList = await fetch(`${this.baseUrl}/admin/staff-list`, { headers })
        .then(res => {
          if (handleUnauthorized(res)) return;
          return res.json();
        })
        .catch(() => []);
      this.staffMap = new Map(staffList.map(x => [x.staff_email, x.staff_name]));

      // 1) Admin aggregates
      const [summary, labsYear] = await Promise.all([
        fetch(`${this.baseUrl}/admin/stats/summary?groupBy=month`, { headers }).then(r => { if (handleUnauthorized(r)) return; return r.json() }),
        fetch(`${this.baseUrl}/admin/stats/labs?fields=ldl_result,tchol_result,hdl_result&groupBy=year`, { headers }).then(r => r.json())
      ]);

      this.summary = summary || null;
      this.labsSeriesYear = (labsYear && labsYear.series) ? labsYear.series : [];

      // 2) Build "tables" from top staff
      // Use top N staff from summary to avoid huge fan-out
      const top = (summary?.top_staff_by_sessions || []).slice(0, 20);
      const staffEmails = top.map(t => t.staff_email);
      const unique = Array.from(new Set(staffEmails));

      // Fetch each staff's vitals & labs, take latest (for vitals) and most recent lab rows
      const perStaffPromises = unique.map(async email => {
        const staffXYZ = email.replace(/\./g, 'XYZ');
        const [vitalsRows, labsRows] = await Promise.all([
          fetch(`${this.baseUrl}/stats/vitals/${staffXYZ}`, { headers }).then(r => r.json()).catch(()=>[]),
          fetch(`${this.baseUrl}/stats/labs/${staffXYZ}?fields=ldl_result,tchol_result,hdl_result`, { headers }).then(r => r.json()).catch(()=>[])
        ]);
        // attach staff_email
        const name = this.staffMap.get(email) || null;
        vitalsRows.forEach(r => { r.staff_email = email; r.staff_name = name; });
        labsRows.forEach(r   => { r.staff_email = email; r.staff_name = name; });
        return { vitalsRows, labsRows };
      });

      const all = await Promise.all(perStaffPromises);
      const vitalsAll = [], labsAll = [];
      all.forEach(({vitalsRows, labsRows}) => {
        (vitalsRows || []).forEach(v => vitalsAll.push(v));
        (labsRows || []).forEach(l => labsAll.push(l));
      });

      // sort by date asc for consistency with your earlier code
      const byDateAsc = (a,b) => new Date(a.session_date) - new Date(b.session_date);
      this.vitals = vitalsAll.sort(byDateAsc);
      this.labs   = labsAll.sort(byDateAsc);
      this.attachNames();
    },

    // ---- Computations / helpers ----
    yearOf(d) { return d ? new Date(d).getFullYear() : ''; },
    setYear(y) { this.activeYear = y; },
    attachNames() {
      const get = (email) => this.staffMap.get(email) || null;
      this.vitals.forEach(v => { if (!v.staff_name) v.staff_name = get(v.staff_email); });
      this.labs.forEach(l   => { if (!l.staff_name) l.staff_name = get(l.staff_email); });
    },

    yearFromBucket(bucket) {
      // buckets for groupBy=year are like "2025"
      // if backend returns a date-like string, parse year
      const y = String(bucket || '').slice(0,4);
      const n = Number(y);
      return Number.isFinite(n) ? n : null;
    },

    filterByYear(arr) {
      if (this.activeYear == null) return arr;
      return arr.filter(r => this.yearOf(r.session_date) === this.activeYear);
    },
    filterByQuery(arr) {
      const q = (this.q || '').toLowerCase().trim();
      if (!q) return arr;
      return arr.filter(r => {
        const name = (r.staff_name || '').toLowerCase();
        const email = (r.staff_email || '').toLowerCase();
        return name.includes(q) || email.includes(q);
      });
    },

    filteredYearSeries() {
      if (this.activeYear == null) return this.labsSeriesYear;
      return (this.labsSeriesYear || []).filter(r => this.yearFromBucket(r.bucket) === this.activeYear);
    },

    sessionCountFromSummary() {
      // Sum summary.sessions_by_period counts respecting year filter
      const rows = this.summary?.sessions_by_period || [];
      if (this.activeYear == null) {
        return rows.reduce((acc, r) => acc + (Number(r.count) || 0), 0);
      }
      // filter rows whose bucket belongs to activeYear (buckets are month-start strings or similar)
      return rows
        .filter(r => this.yearFromBucket(String(r.bucket)) === this.activeYear)
        .reduce((acc, r) => acc + (Number(r.count) || 0), 0);
    },

    sumAbnormalFromSeries(series) {
      // series rows include columns like ldl_result_abnormal, _normal, _not_done, etc.
      let abnormal = 0, normal = 0, notdone = 0;
      for (const r of (series || [])) {
        const add = (k) => Number(r[k] || 0);
        abnormal += add('ldl_result_abnormal') + add('tchol_result_abnormal') + add('hdl_result_abnormal');
        normal   += add('ldl_result_normal')   + add('tchol_result_normal')   + add('hdl_result_normal');
        notdone  += add('ldl_result_not_done') + add('tchol_result_not_done') + add('hdl_result_not_done');
      }
      return { abnormal, normal, notdone };
    },

    buildAbnormalByYear() {
      const by = {};
      for (const r of (this.labsSeriesYear || [])) {
        const y = this.yearFromBucket(r.bucket);
        if (!y) continue;
        const counts = {
          abnormal: (Number(r.ldl_result_abnormal||0) + Number(r.tchol_result_abnormal||0) + Number(r.hdl_result_abnormal||0))
        };
        by[y] = (by[y] ?? 0) + counts.abnormal;
      }
      this.abnormalByYear = by;
    },

    // Reused helpers from your per-staff page
    isRowAbnormal(l) {
      return [l.ldl_result, l.tchol_result, l.hdl_result].some(v => v === 'Abnormal');
    },
    badgeClass(isBad) {
      return isBad ? 'badge bad' : 'badge good';
    },
  }
};
</script>


<style scoped>
.dashboard { padding: 24px; }

.topbar {
  display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:10px;
}
.search {
  min-width: 280px; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px;
}

.legend { display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 14px; }
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
.card-sub { font-size:12px; color:#64748b; margin-top:4px; }

.grid {
  display:grid; 
  grid-template-columns: 1fr 1fr; 
  gap: 12px; 
  /* margin: 12px auto;  */
  justify-content: center;
}
@media (max-width: 1250px) { .grid { grid-template-columns: 1fr; } }

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

.pair { display:flex; gap:6px; }
.pair .k { width:56px; color:#64748b; }

.badge {
  font-size:12px; padding:2px 8px; border-radius:12px;
  display:inline-block; border:1px solid transparent;
}
.badge.good { background:#ecfdf5; color:#065f46; border-color:#d1fae5; }
.badge.bad  { background:#fef2f2; color:#991b1b; border-color:#fee2e2; }
</style>
