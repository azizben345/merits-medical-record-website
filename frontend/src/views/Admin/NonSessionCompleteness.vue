<template>
  <div class="ns-page">
    <h2>Manage Staff Personal Info</h2>

    <button @click="$router.push('/dashboard')" class="ghost">Back to Dashboard</button>

    <!-- Top bar: search + filter + pagination -->
    <div class="topbar">
      <div class="search-container">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="search"
          placeholder="Search staff by name, email, or staff no…"
          class="search"
        />
        <button v-if="search" @click="clearSearch" class="clear-btn" type="button" title="Clear search">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="filters">
        <select v-model="statusFilter">
          <option value="all">All Status</option>
          <option value="complete">Complete</option>
          <option value="incomplete">Incomplete</option>
        </select>
      </div>

      <div class="pager" style="height: calc(2.5rem + 16px)">
        <button class="page-btn" :disabled="page===1 || loading" @click="prevPage">‹ Prev</button>
        <span class="page-number">Page {{ page }}</span>
        <button class="page-btn" style="margin-left: 8px;" :disabled="loading || items.length < pageSize" @click="nextPage">Next ›</button>
      </div>
    </div>

    <!-- summary bar -->
    <div class="toolbar">

      <div style="margin-bottom: 1rem;">
        <!-- <button class="new-btn" @click="showSummaryColumns = !showSummaryColumns" style="background-color: #1d1d1d;"> -->
        <button class="new-btn" @click="showSummaryColumns = !showSummaryColumns">
          {{ showSummaryColumns ? 'Hide' : 'Show' }} Details Columns
        </button>
        <ExportExcel
          :rows="displayedItems"
          :allRows="items"
          :columns="nsColumns"
          filename="non-session.xlsx"
          sheetName="Non-Session Data"
          title="Export Non-Session Columns"
        >
          <template #trigger="{ open, disabled }">
            <button :disabled="disabled" @click="open" class="new-btn">Export Excel</button>
          </template>
        </ExportExcel>
      </div>

      <div class="summary-bar" style="">
        <span class="pill ok">Complete: {{ counts.filtered.complete }}</span>
        <span class="pill bad">Incomplete: {{ counts.filtered.incomplete }}</span>
        <span class="sep">|</span>
        <span class="muted" style="margin-top: 4px;">All staff →</span>
        <span class="pill mini ok">C: {{ counts.all.complete }}</span>
        <span class="pill mini bad">I: {{ counts.all.incomplete }}</span>
      </div>

    </div>

    <!-- Main Table -->
    <div class="table-wrap" 
    @scroll="onTableScroll">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th v-if="!showSummaryColumns" class="center">Staff Info Status</th>
            <th v-if="showSummaryColumns" class="center">Personal Info</th>
            <th v-if="showSummaryColumns" class="center">Occupational History</th>
            <th v-if="showSummaryColumns" class="center">Family (named entries)</th>
            <th v-if="showSummaryColumns" class="center">Family Disease</th>
            <!-- <th v-if="showSummaryColumns" style="min-width: 320px;">Updated At</th> -->
            <th class="center">Session Counts</th>
            <th class="nowrap" style="">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in displayedItems"
            :key="row.staff_email"
            @click="openDetails(row)"
            class="clickable"
            :class="{ 'row-deleted': row.deleted_at !== null }"
          >
            <!-- Name -->
            <td>
              <div class="pair"><span class="v">{{ row.staff_name || '-' }}</span></div>
            </td>

            <!-- Staff Info -->
            <td v-if="!showSummaryColumns" class="center">
              <span :class="['chip', row.non_session_score >= 3 ? 'ok' : 'bad']">
                {{ row.non_session_score >= 3 ? 'Complete' : 'Incomplete' }}
              </span>
            </td>

            <!-- Personal Info -->
            <td v-if="showSummaryColumns" class="center">
              <span :class="['chip', row.staff_info_complete ? 'ok' : 'bad']">
                {{ row.staff_info_complete ? 'Complete' : 'Incomplete' }}
              </span>
              <br><br>
              <div class="pair"><span class="k">Doctor:</span><br><span class="v">{{ row.personal_doctor_email || '-' }}</span></div>
              <br>
              <div v-if="row.staff_updated_at" class="mini-ts">{{ formatDate(row.staff_updated_at) }}</div>
              <br>
              <div v-if="sinceCreated(row,'staff')" class="since-pill">since created</div>
            </td>

            <!-- Occupational History -->
            <td v-if="showSummaryColumns" class="center">
              <div class="tcount">
                <!-- <span class="num">{{ row.occ_count ?? 0 }}</span>
                <br><br> -->
                <small v-if="row.occ_updated_at">{{ formatDate(row.occ_updated_at) }}</small>
                <br>
                <div v-if="sinceCreated(row,'occ')" class="since-pill">since created</div>
              </div>
            </td>

            <!-- Family -->
            <td v-if="showSummaryColumns" class="center">
              <span :class="['chip', row.family_min_ok ? 'ok' : 'bad']">{{ row.family_min_ok ? 'Complete' : 'Incomplete' }}</span>
              <br><br>
              <div class="tcount">
                <span class="num">{{ row.fam_named_count ?? 0 }}</span>
                <br><br>
                <div v-if="row.fam_updated_at" class="mini-ts">{{ formatDate(row.fam_updated_at) }}</div>
                <br>
                <div v-if="sinceCreated(row,'fam')" class="since-pill">since created</div>
              </div>
            </td>

            <!-- Family Disease -->
            <td v-if="showSummaryColumns" class="center">
              <span :class="['chip', row.fhd_answered ? 'ok' : 'bad']">{{ row.fhd_answered ? 'Complete' : 'Incomplete' }}</span>
              <br><br>
              <div v-if="row.fhd_updated_at" class="mini-ts">{{ formatDate(row.fhd_updated_at) }}</div>
              <br>
              <div v-if="sinceCreated(row,'fhd')" class="since-pill">since created</div>
            </td>

            <!-- Updated At -->
            <!-- <td v-if="showSummaryColumns">
              <div class="pair">
                <span class="k">Staff:</span>
                <span class="v">
                  {{ formatDate(row.staff_updated_at) || '-' }}
                  <span v-if="sinceCreated(row,'staff')" class="since">• since created</span>
                </span>
              </div>
              <div class="pair">
                <span class="k">Occ:</span>
                <span class="v">
                  {{ formatDate(row.occ_updated_at) || '-' }}
                  <span v-if="sinceCreated(row,'occ')" class="since">• since created</span>
                </span>
              </div>
              <div class="pair">
                <span class="k">Fam:</span>
                <span class="v">
                  {{ formatDate(row.fam_updated_at) || '-' }}
                  <span v-if="sinceCreated(row,'fam')" class="since">• since created</span>
                </span>
              </div>
              <div class="pair">
                <span class="k">FHD:</span>
                <span class="v">
                  {{ formatDate(row.fhd_updated_at) || '-' }}
                  <span v-if="sinceCreated(row,'fhd')" class="since">• since created</span>
                </span>
              </div>
            </td> -->

            <!-- Session Count -->
            <td class="center">{{ row.session_count }}</td>

            <!-- Actions -->
            <td class="center" @click.stop>
              <button @click="goEditStaff(row.staff_email)">Open</button>
              <button @click="goSessions(row.staff_email)">View Sessions</button>
              <button @click="openDetails(row)">Preview Details</button>
            </td>
          </tr>

          <tr v-if="!loading && displayedItems.length===0">
            <td colspan="8" class="empty">No staff found for this filter.</td>
          </tr>

          <tr v-if="loading">
            <td colspan="8" class="loading">Loading…</td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- sticky horizontal scrollbar (proxy) -->
    <div class="x-scroll" ref="xScroll" @scroll="onXScroll">
      <!-- This spacer’s width will be set to match the table’s scroll width -->
      <div :style="{ width: tableScrollWidth + 'px', height: '1px' }"></div>
    </div>

    <!-- :class="{ 'row-inactive': staff.deleted_at !== null }" -->


    <!-- Right drawer -->
    <div class="drawer" :class="{open: detailOpen}" @click.self="closeDetails">
      <div class="drawer-panel" @click.stop>
        <div class="drawer-header">
          <div>
            <div class="title"><strong> Non-Session Details</strong></div>
            <div class="subtitle" v-if="detail?.staff">
              {{ detail.staff.staff_name || '(no name)' }} - {{ detail.staff.staff_email }}
              <!-- <span v-if="detail?.staff?.deleted_at"> <i>(deleted)</i> </span> -->
            </div>
          </div>
          <button class="x" @click="closeDetails">✕</button>
        </div>

        <div class="drawer-body">
          <section class="box" v-if="detail?.staff">
            <h4>Staff Info</h4>
            <div class="grid2">
              <div class="pair"><span class="k">Staff No:</span><span class="v">{{ detail.staff.staff_no || '-' }}</span></div>
              <div class="pair"><span class="k">Job Title / Position:</span><span class="v">{{ detail.staff.job_title || '-' }}</span></div>
              <div class="pair"><span class="k">Department:</span><span class="v">{{ detail.staff.department || '-' }}</span></div>
              <div class="pair"><span class="k">Nationality:</span><span class="v">{{ detail.staff.nationality || '-' }}</span></div>
              <div class="pair"><span class="k">Phone:</span><span class="v">{{ detail.staff.phone_no || '-' }}</span></div>
              <div class="pair"><span class="k">Address:</span><span class="v">{{ detail.staff.address || '-' }}</span></div>
            </div>
          </section>

          <section class="box">
            <h4>Occupational History ({{ detail?.occupational_history?.length || 0 }})</h4>
            <div v-if="detail?.occupational_history?.length">
              <table class="mini">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Year</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Job Title</th>
                    <th>Nature of Work</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(oh,i) in detail.occupational_history" :key="oh.oh_id || i">
                    <td>{{ i+1 }}</td>
                    <td>{{ oh.year ?? '' }}</td>
                    <td>{{ oh.company ?? '' }}</td>
                    <td>{{ oh.location ?? '' }}</td>
                    <td>{{ oh.job_title ?? '' }}</td>
                    <td>{{ oh.nature_of_work ?? '' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="muted">No occupational history.</div>
          </section>

          <section class="box">
            <h4>Family Members ({{ detail?.family_history?.length || 0 }})</h4>
            <div v-if="detail?.family_history?.length">
              <table class="mini">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Relationship</th>
                    <th>Name</th>
                    <th>Sex</th>
                    <th>Year of Birth</th>
                    <th>Age</th>
                    <th>Age at Death</th>
                    <th>Health / Cause of Death</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(fh,i) in detail.family_history" :key="fh.fh_id || i">
                    <td>{{ i+1 }}</td>
                    <td>{{ fh.relationship }}</td>
                    <td>{{ fh.relative_name ?? '' }}</td>
                    <td>{{ fh.sex ?? '' }}</td>
                    <td>{{ fh.year_of_born ?? '' }}</td>
                    <td>{{ fh.age_now ?? '' }}</td>
                    <td>{{ fh.age_at_death ?? '-' }}</td>
                    <td>{{ fh.state_health_death_cause ?? '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="muted">No family members.</div>
          </section>

          <section class="box">
            <h4>Family History Disease</h4>
            <div v-if="detail?.family_history_disease">
              <table class="mini">
                <thead>
                  <tr>
                    <th>Disease</th>
                    <th>Yes / No</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Heart disease</td>
                    <td>{{ yn(detail.family_history_disease.heart_disease) }}</td>
                  </tr>
                  <tr>
                    <td>HBP</td>
                    <td>{{ yn(detail.family_history_disease.high_blood_pressure) }}</td>
                  </tr>
                  <tr>
                    <td>Diabetes</td>
                    <td>{{ yn(detail.family_history_disease.diabetes) }}</td>
                  </tr>
                  <tr>
                    <td>Stroke</td>
                    <td>{{ yn(detail.family_history_disease.stroke) }}</td>
                  </tr>
                  <tr>
                    <td>Kidney</td>
                    <td>{{ yn(detail.family_history_disease.kidney_disease) }}</td>
                  </tr>
                  <tr>
                    <td>Allergy</td>
                    <td>{{ yn(detail.family_history_disease.allergy) }}</td>
                  </tr>
                  <tr>
                    <td>Asthma</td>
                    <td>{{ yn(detail.family_history_disease.asthma) }}</td>
                  </tr>
                  <tr>
                    <td>Eczema</td>
                    <td>{{ yn(detail.family_history_disease.eczema) }}</td>
                  </tr>
                  <tr>
                    <td>TB</td>
                    <td>{{ yn(detail.family_history_disease.tuberculosis) }}</td>
                  </tr>
                  <tr>
                    <td>Epilepsy</td>
                    <td>{{ yn(detail.family_history_disease.epilepsy) }}</td>
                  </tr>
                  <tr>
                    <td>Mental Disorder</td>
                    <td>{{ yn(detail.family_history_disease.mental_disorder) }}</td>
                  </tr>
                  <tr>
                    <td>Alcohol</td>
                    <td>{{ yn(detail.family_history_disease.alcohol_dependence) }}</td>
                  </tr>
                  <tr>
                    <td>Drugs</td>
                    <td>{{ yn(detail.family_history_disease.drug_abuse) }}</td>
                  </tr>
                  <tr>
                    <td>Birth abnormality</td>
                    <td>{{ yn(detail.family_history_disease.birth_abnormality) }}</td>
                  </tr>
                  <tr>
                    <td><b>None of the Above</b></td>
                    <td>{{ yn(detail.family_history_disease.none_above) }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="box">
                <p style="margin-bottom: 1rem;">
                  <b>Details:</b>
                  <br>{{ detail.family_history_disease.details }}
                </p>
              </div>
            </div>
            <div v-else class="muted">No family disease summary.</div>
          </section>
        </div>

        <div class="drawer-footer">
          <button @click="goEditStaff(detail?.staff?.staff_email)" :disabled="!detail?.staff">Open Info</button>
          <button @click="goSessions(detail?.staff?.staff_email)" :disabled="!detail?.staff">View Sessions</button>
          <button class="ghost" @click="closeDetails">Close</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import ExportExcel from '@/components/ExportExcel.vue';
import { formatDate } from '@/shared/dateFormat';

export default {
  name: 'NonSessionCompleteness',
  components: { ExportExcel },
  data() {
    return {
      loading: false,
      tableScrollWidth: 0,
      resizeObs: null,
      // isAdminRole: false,

      baseUrl: cfg.API_BASE_URL,

      items: [],
      search: '',
      page: 1,
      pageSize: 20,
      statusFilter: 'all',
      showSummaryColumns: false,
      globalCounts: { complete: 0, incomplete: 0 },

      // dropdown state
      openRowMenuId: null,

      // drawer
      detailOpen: false,
      detail: null,

      // debounce
      t: null,
    };
  },
  mounted() {
    this.fetchSummary();
    // // close row action menu on outside click
    // this._onDocClick = (e) => {
    //   if (!e.target.closest('.action-menu')) this.openRowMenuId = null;
    // };
    // document.addEventListener('click', this._onDocClick);
    // // Esc closes menus/drawer
    // this._onKey = (e) => {
    //   if (e.key === 'Escape') {
    //     this.openRowMenuId = null;
    //     if (this.detailOpen) this.closeDetails();
    //   }
    // };
    // document.addEventListener('keydown', this._onKey);
    this.$nextTick(() => this.measureXBar());
    // keep it in sync on resize/content changes
    window.addEventListener('resize', this.measureXBar);
    this.resizeObs = new ResizeObserver(() => this.measureXBar());
    const wrap = this.$el.querySelector('.table-wrap');
    if (wrap) this.resizeObs.observe(wrap);
  },
  beforeUnmount() {
    // document.removeEventListener('click', this._onDocClick);
    // document.removeEventListener('keydown', this._onKey);
    window.removeEventListener('resize', this.measureXBar);
    if (this.resizeObs) this.resizeObs.disconnect();
  },
  computed: {
    isAdminRole() { 
      const userInfoString = localStorage.getItem('user_info');
      if (userInfoString) {
        try {
          const userInfo = JSON.parse(userInfoString);
          return userInfo.role === 'admin';
        } catch (e) {
          console.error('Error parsing user_info from localStorage:', e);
          return false;
        }
      }
      return false;
    },
    displayedItems() {
      const src = Array.isArray(this.items) ? this.items : [];
      const q = (this.search || '').toLowerCase();

      // 1) text search
      let out = src.filter(r => {
        const hay = `${r.staff_name || ''} ${r.staff_email || ''} ${r.staff_no || ''}`.toLowerCase();
        return !q || hay.includes(q);
      });

      // 2) filter (complete/incomplete)
      if (this.statusFilter !== 'all') {
        out = out.filter(r => this.rowStatus(r) === this.statusFilter);
      }
      return out;
    },
    counts() {
      const all = 
      this.globalCounts;
      // this.items.reduce(
      //   (acc, r) => {
      //     const s = this.rowStatus(r);
      //     acc[s] = (acc[s] || 0) + 1;
      //     return acc;
      //   },
      //   { complete: 0, incomplete: 0 }
      // );

      const filtered = this.displayedItems.reduce(
        (acc, r) => {
          const s = this.rowStatus(r);
          acc[s] = (acc[s] || 0) + 1;
          return acc;
        },
        { complete: 0, incomplete: 0 }
      );
      return { all, filtered };
    },
    // export excel
    nsColumns() {
      // can reuse helpers available in this SFC 
      return [
        { key: 'staff_name',  label: 'Name' },
        { key: 'staff_email', label: 'Email' },
        { key: 'staff_no',    label: 'Staff No' },
        { key: 'job_title',   label: 'Job Title / Position' },
        { key: 'department', label: 'Department' },

        { key: 'staff_info_complete', label: 'Staff Info Complete', format: v => v ? 'Yes' : 'No' },
        { key: 'family_min_ok',       label: 'Family Minimum OK',   format: v => v ? 'Yes' : 'No' },
        { key: 'fhd_answered',        label: 'Family Disease Answered', format: v => v ? 'Yes' : 'No' },

        { key: 'occ_count',       label: 'Occupational Entries' },
        { key: 'fam_named_count', label: 'Family Named Entries' },

        { key: 'staff_updated_at', label: 'Staff Updated At', format: (v) => this.fmtDateTime?.(v) ?? v ?? '' },
        { key: 'occ_updated_at',   label: 'Occupational Updated At', format: (v) => this.fmtDateTime?.(v) ?? v ?? '' },
        { key: 'fam_updated_at',   label: 'Family Updated At', format: (v) => this.fmtDateTime?.(v) ?? v ?? '' },
        { key: 'fhd_updated_at',   label: 'Family Disease Updated At', format: (v) => this.fmtDateTime?.(v) ?? v ?? '' },

        { key: 'status', label: 'Overall Status', getter: (row) => this.rowStatus?.(row) ?? '' },
        { key: 'non_session_score', label: 'Score' },
      ];
    },
  },
  methods: {
    formatDate,
    // measure the table’s scrollable width and align proxy position
    measureXBar() {
      const wrap = this.$el.querySelector('.table-wrap');
      if (!wrap) return;
      this.tableScrollWidth = wrap.scrollWidth;
      if (this.$refs.xScroll) {
        this.$refs.xScroll.scrollLeft = wrap.scrollLeft;
      }
    },
    // when the real table scrolls horizontally, move the proxy
    onTableScroll(e) {
      if (this.$refs.xScroll) {
        this.$refs.xScroll.scrollLeft = e.target.scrollLeft;
      }
    },
    // when the proxy scrolls, move the real table
    onXScroll(e) {
      const wrap = this.$el.querySelector('.table-wrap');
      if (wrap) wrap.scrollLeft = e.target.scrollLeft;
    },
    // ---- Fetching ----
    fetchSummary() {
      
      this.loading = true;
      const params = new URLSearchParams({
        q: this.search || '',
        page: String(this.page),
        page_size: String(this.pageSize)
      });

      fetch(`${this.baseUrl}/admin/non-session/summary?${params.toString()}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;

        return res.json();
      })
      .then(data => {
        this.items = Array.isArray(data) ? data : (Array.isArray(data.items) ? data.items : []);

        if (data.counts) {
          this.globalCounts = data.counts; 
        }
      })
      .catch(() => { this.items = []; })
      .finally(() => {
        this.loading = false;
        this.$nextTick(() => this.measureXBar());
      });
    },
    debouncedFetch() {
      clearTimeout(this.t);
      this.t = setTimeout(() => {
        this.page = 1;
        this.fetchSummary();
      }, 300);
    },
    nextPage() { this.page++; this.fetchSummary(); },
    prevPage() { if (this.page > 1) { this.page--; this.fetchSummary(); } },

    // ---- "since created" helpers ----
    // sameDay(a, b) {
    //   if (!a || !b) return false;
    //   const da = new Date(a), db = new Date(b);
    //   if (isNaN(da) || isNaN(db)) return false;
    //   return da.getFullYear()===db.getFullYear()
    //     && da.getMonth()===db.getMonth()
    //     && da.getDate()===db.getDate();
    // },
    // check if a specific section's updated_at equals the session_created_at (by day)
    sinceCreated(row, key) {
      // key all: 'staff' | 'occ' | 'fam' | 'fhd'
      const map = {
        staff: row.staff_updated_at,
        occ:   row.occ_updated_at,
        fam:   row.fam_updated_at,
        fhd:   row.fhd_updated_at
      };
      const dateA = new Date(map[key]);
      const dateB = new Date(row.staff_created_at);
      return dateA.toDateString() === dateB.toDateString() && dateA.getHours() === dateB.getHours() && dateA.getMinutes() === dateB.getMinutes();
    },
    // ---- Status logic (Complete / Incomplete) ----
    rowStatus(r) {
      const staffOk = r?.staff_info_complete ? 1 : 0;
      const famOk   = r?.family_min_ok ? 1 : 0;      // backend already enforces ≥2 named relatives
      const fhdOk   = r?.fhd_answered ? 1 : 0;       // updated_at > created_at

      const complete = (staffOk && famOk && fhdOk);
      return complete ? 'complete' : 'incomplete';
    },
    // rowStatus(r) {
    //   const hasStaffInfo = !!r.staff_info_complete;
    //   const famCount = Number(r.fam_count ?? 0);
    //   const hasFhd = (r.has_fhd === true || r.has_fhd === 1 || r.has_fhd === '1');
    //   const occCount = Number(r.occ_count ?? 0);

    //   const incomplete = !hasStaffInfo || famCount === 0 || !hasFhd || occCount === 0;
    //   return incomplete ? 'incomplete' : 'complete';
    // },

    // ---- UI Helpers ----
    fmtDateTime(v) {
      if (!v) return '';
      const d = new Date(v);
      if (isNaN(d)) return v;
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return d.toLocaleString('default', options).replace(/:\d{2}$/, ':00');
    },
    yn(v) { return (v === true || v === 1 || v === '1' || v === 'Y' || v === 'y') ? 'Yes' : 'No'; },

    // ---- Row actions ----
    // toggleRowMenu(id) {
    //   this.openRowMenuId = this.openRowMenuId === id ? null : id;
    // },
    openDetails(row) {
      // close any open dropdown before opening the drawer
      this.openRowMenuId = null;
      this.detailOpen = true;
      this.detail = null;

      const emailXYZ = encodeURIComponent(row.staff_email.replace(/\./g, 'XYZ'));
      fetch(`${this.baseUrl}/admin/non-session/staff/${emailXYZ}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
        .then(r => r.json())
        .then(payload => { this.detail = payload || {}; })
        .catch(() => {});
    },
    closeDetails() {
      this.detailOpen = false;
      this.detail = null;
      this.openRowMenuId = null;
    },
    goSessions(staffEmail) {
      this.openRowMenuId = null;
      if (!staffEmail) return;
      const q = encodeURIComponent(staffEmail);
      if (!this.isAdminRole) {
        this.$router.push(`/doctor/manage-sessions?staff=${q}`);
      } else {
        this.$router.push(`/admin/manage-sessions?staff=${q}`);
      }
    },
    goEditStaff(staffEmail) {
      this.openRowMenuId = null;
      if (!staffEmail) return;
      const q = encodeURIComponent(staffEmail);
      console.log('goEditStaff', q);
      this.$router.push(`/admin/non-session/${q}`);
    },
  }
};
</script>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>