<template>
  <div class="page">
    <h2>My Checkup Sessions</h2>

    <div class="filters">
      <select v-model="year">
        <option value="">All years</option>
        <option v-for="y in years" :key="y" :value="String(y)">{{ y }}</option>
      </select>

      <select v-model="type">
        <option value="">All type</option>
        <!-- <option value="annual">Annual</option> -->
        <option value="periodic">Periodic</option>
        <option value="followup">Follow-up</option>
        <option value="pre-employment">Pre-employment</option>
        <option value="return-to-work">Return to Work</option>
        <option value="ad-hoc">Ad-hoc</option>
      </select>

      <select v-model="status">
        <option value="">All status</option>
        <option value="draft">Draft</option>
        <option value="submitted">Submitted</option>
        <option value="locked">Locked</option>
      </select>
      
    </div>

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th @click="toggleDateSort" style="min-width: 110px; cursor:pointer">
              Date
              <span v-if="sortDir === 'asc'">▲</span>
              <span v-else>▼</span>
            </th>
            <th>Type</th>
            <th style="display: flex; align-items: center;">
              Status
              <div class="info-container" style="margin: 0;">
                <span class="info-icon" @click="toggleInfo">ℹ️</span>
              </div>
            </th>
            <th>Report</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sortedSessions" :key="s.session_id">
            <td>{{ formatDate(s.session_date) }}</td>
            <td>{{ s.session_type }}</td>
            <td><span class="pill" :class="s.status">{{ s.status }}</span></td>

            <!-- Reports column -->
            <td class="reports-cell" @click.stop>
              <div class="report-wrapper">
                <template v-if="(reportsBySession[s.session_id] || []).length">
                  <button
                    class="pill2 ok pill2-btn"
                    @click.stop="toggleReportMenu(s.session_id)"
                    style="margin-top: 0px;"
                  >
                    Uploaded ▾
                  </button>

                  <div
                    class="menu-list"
                    v-if="openReportMenuId === s.session_id"
                    @click.stop
                  >
                    <div v-if="reportsLoadingId === s.session_id" class="muted">Loading…</div>
                    <ul v-else class="report-list">
                      <li v-for="r in reportsBySession[s.session_id]" :key="r.report_id">
                        <div class="meta">
                          <strong>{{ r.title || safeName(r.file_name) }}</strong>
                          <div class="sub">{{ kb(r.file_size) }} • {{ prettyTs(r.uploaded_at) }}</div>
                        </div>
                      </li>
                    </ul>
                  </div>
                </template>

                <template v-else>
                  <span class="pill2 muted">None</span>
                </template>
              </div>
            </td>

            <td>
              <button class="btn" :disabled="!s.has_forms_all" @click="$router.push(`/staff/session/${s.session_id}`)">
                {{ !s.has_forms_all ? 'No Forms' : 'Open' }}
              </button>
              
              <a
                v-for="r in reportsBySession[s.session_id]" :key="r.report_id"
                :href="`${this.baseUrl}/reports/${r.report_id}/download`"
                target="_blank" rel="noopener" class="btn linklike"
                :class="{ disabled: (reportsBySession[s.session_id] || []).length === 0 }"
                @click="(reportsBySession[s.session_id] || []).length === 0 && $event.preventDefault()"
              >
                Download Report
              </a>

              <button class="btn"
                @click="$router.push({ 
                  name: 'export-medical', 
                  query: { session_id: s.session_id, staff_email: s.staff_email } 
                })"
              >
                Export Excel
              </button>

            </td>
          </tr>

          <tr v-if="!filtered.length">
            <td colspan="5" class="empty">No sessions.</td>
          </tr>
        </tbody>
      </table>
      <teleport to="body">
        <div v-if="showInfo" class="info-overlay" @click.self="closeInfo">
          <div class="info-box">
            <h4>Checkup Status Guide</h4>
            <p>The form's editable status is determined by its current state (excludes <i>Fitness Certificate</i>):</p>
            <ul style="text-align: left; list-style: none; padding: 0; margin: 0;">
              <li>
                <b>Draft: Staff</b> and <b>Doctor</b> can edit the form.
              </li>
              <li>
                <b>Submitted:</b> Only the <b>Doctor</b> can edit the form.
              </li>
              <li>
                <b>Locked:</b> The form is <b>view-only</b> for all.
              </li>
            </ul>
            <button @click="closeInfo">Got it</button>
          </div>
        </div>
      </teleport>
    </div>
  </div>
</template>

<script>
import { formatDate } from '@/shared/dateFormat';
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

export default {
  data: () => ({
    baseUrl: cfg.API_BASE_URL,
    showInfo: false,
    sessions: [],
    year: '',
    status: '',
    type: '',
    sortDir: 'desc',
    // reports state
    openReportMenuId: null,
    reportsLoadingId: null,
    reportsBySession: {},    // { [session_id]: Report[] }
    reportHas: {},           // { [session_id]: boolean }
  }),
  computed: {
    years() {
      const set = new Set(
        this.sessions
          .map(s => {
            const d = new Date(s.session_date);
            return isNaN(d) ? null : d.getFullYear();
          })
          .filter(Boolean)
      );
      return [...set].sort((a, b) => b - a);
    },
    filtered() {
      return this.sessions.filter(s => {
        const yOk = !this.year || new Date(s.session_date).getFullYear().toString() === this.year;
        const stOk = !this.status || s.status === this.status;
        const tOk = !this.type || s.session_type === this.type;
        return yOk && stOk && tOk;
      });
    },
    sortedSessions() {
      const dir = this.sortDir === 'asc' ? 1 : -1;
      return [...this.filtered].sort((a, b) => {
        const at = this.toTime(a.session_date);
        const bt = this.toTime(b.session_date);
        if (at === bt) return 0;
        return at > bt ? dir : -dir;
      });
    },
  },
  mounted() {
    this.fetchMine();

    // close the report dropdowns when clicking outside
    this._onDocClick = (e) => {
      if (!e.target.closest('.report-menu')) {
        this.openReportMenuId = null;
      }
    };
    document.addEventListener('click', this._onDocClick);
  },
  beforeUnmount() {
    document.removeEventListener('click', this._onDocClick);
  },
  methods: {
    toggleInfo() { this.showInfo = !this.showInfo; },
    closeInfo() { this.showInfo = false; },
    toTime(d) {
      const t = new Date(d).getTime();
      return Number.isFinite(t) ? t : -Infinity; // invalid dates go to bottom
    },
    async fetchMine() {

      const user = JSON.parse(localStorage.getItem('user_info') || '{}');
      if (!user?.email) return;
      const staffEmail = user.email.replace(/\./g, 'XYZ');

      const r = await fetch(`${this.baseUrl}/checkup-sessions/${staffEmail}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      });
      const data = await r.json();
      if (handleUnauthorized(r)) return;
      this.sessions = Array.isArray(data) ? data : (data.sessions || []);
      this.sessions.forEach(s => {
        s.has_forms_all = 
          (s.has_investigations) &&
          (s.has_ilab) &&
          (s.has_lifestyle) &&
          (s.has_mh) &&
          (s.has_pe1) &&
          (s.has_pe2); 
      });
      // fetch all reports for each session
      const promises = this.sessions.map(s => this.ensureReportsLoaded(s.session_id));
      await Promise.all(promises);
    },

    // --- basic formatters ---
    // fmt(d) { return d ? new Date(d).toISOString().slice(0, 10) : '-'; },
    formatDate,
    prettyTs(ts) {
      const d = new Date(ts);
      return isNaN(d) ? '' : d.toLocaleString();
    },
    kb(bytes) {
      const n = Number(bytes || 0);
      return `${Math.round(n / 1024)} KB`;
    },
    safeName(name = '') {
      return name.length > 16 ? `${name.slice(0, 13)}…` : name;
    },
    toggleDateSort() {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
    },

    // --- report status pill ---
    reportExists(s) {
      const sid = s.session_id;
      if (this.reportHas[sid] !== undefined) return this.reportHas[sid];
      // fallback to row’s `has_report` if your API includes it
      return !!s.has_report;
    },
    setReportExists(sessionId, val) {
      this.reportHas = { ...this.reportHas, [sessionId]: !!val };
    },

    // --- dropdown toggle + lazy load list ---
    toggleReportMenu(sessionId) {
      this.openReportMenuId =
        this.openReportMenuId === sessionId ? null : sessionId;
      if (this.openReportMenuId) {
        this.ensureReportsLoaded(sessionId);
      }
    },

    ensureReportsLoaded(sessionId) {
      if (this.reportsBySession[sessionId]) return; // already loaded
      this.reportsLoadingId = sessionId;

      // staff-side endpoint to read report(s) for this session
      fetch(`${this.baseUrl}/sessions/${sessionId}/reports`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(r => r.json())
        .then(list => {
          const arr = Array.isArray(list) ? list : [];
          this.reportsBySession = { ...this.reportsBySession, [sessionId]: arr };
          this.setReportExists(sessionId, arr.length > 0);
        })
        .catch(() => {
          this.reportsBySession = { ...this.reportsBySession, [sessionId]: [] };
          this.setReportExists(sessionId, false);
        })
        .finally(() => {
          this.reportsLoadingId = null;
        });
    },
  }
};
</script>

<style scoped>
.table-responsive {
  width: 100%;
  overflow-x: auto;
  background: white;
  box-shadow: 0 2px 6px rgba(0,0,0,.05);
  border-radius: 8px;
}
.table-responsive table { width: 100%; border-collapse: collapse; }
.table-responsive th, .table-responsive td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}
.table-responsive th {
  background: #f7fafc;
  color: #2d3748;
  font-weight: 600;
  top: 0;
}
.info-container { 
  margin: 0;
  line-height: 1;
}
.pill { padding:3px 10px; border-radius:999px; text-transform:capitalize; font-size:12px; }
.pill.draft { background:#fef9c3; color:#92400e; }
.pill.submitted { background:#bfdbfe; color:#1e3a8a; }
.pill.locked { background:#d1fae5; color:#065f46; }
.pill.ok { background:#e6f6ea; color:#137333; }
.pill.muted { background:#eef2f6; color:#64748b; }

.pill2 {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 12px;
  line-height: 1.4;
  height: 24px;
}
.pill2.ok {
  background: #e7f7ec;
  color: #1f7a3a;
  border: 1px solid #bfe7c9;
}
.pill2.muted {
  background: #f3f4f6;
  color: #6b7280;
  border: 1px solid #e5e7eb;
}
.pill2-btn {
  cursor: pointer;
  border: 1px solid #bfe7c9;
  background: #e7f7ec;
}

.empty { padding:18px; text-align:center; color:#6b7280; }

.btn {
  display: inline-block;
  padding: 6px 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: #f4f4f4;
  cursor: pointer;
  text-decoration: none;
  color: #222;
  font-size: 13px;
  margin-top: 0px;
  margin-right: 8px;
}
.btn:disabled {
  opacity: 0.5;
  pointer-events: none;
  background: #e2e8f0 !important;
  color: #2d3748 !important;
  border-color: #e2e8f0 !important;
}

/* reports dropdown */
.reports-cell { position: relative; text-align: center; }
.report-wrapper {
  display: inline-block;
  position: relative;
  vertical-align: middle;
}
.report-menu { display: inline-block; position: relative; }
.menu-btn {
  padding: 6px 10px;
  font-size: 12px;
  border-radius: 4px;
  background: #edf2f7;
  color: #2d3748;
  border: 1px solid #e2e8f0;
  cursor: pointer;
}
.menu-btn:hover { background:#e2e8f0; }
/* dropdown alignment */
.menu-list {
  position: absolute;
  top: 100%; /* right below the pill */
  left: 50%;
  transform: translateX(-50%);
  margin-top: 4px;
  min-width: fit-content;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  z-index: 10;
}
.report-list { list-style:none; margin:0; padding:0; min-width: fit-content; white-space: nowrap; }
.report-list li {
  display:flex; justify-content:space-between; gap:10px;
  padding:8px 0; border-bottom:1px dashed #edf2f7;
}
.report-list li:last-child { border-bottom:none; }
.meta { max-width:70%; }
.meta .sub { color:#718096; font-size:12px; }
.row-actions a { text-decoration:none; color:#2b6cb0; }
.row-actions a:hover { text-decoration:underline; }
.report-pill { margin-bottom: 6px; }
.muted { color:#718096; font-size:12px; }
</style>
