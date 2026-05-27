<template>
  <div class="manage-sessions">
    <h2>Manage Sessions</h2> 

    <button class="ghost" @click="$router.back()" v-if="isFilteredView">Back</button>
    <button class="ghost" @click="$router.push('/dashboard')" v-else>Back to Dashboard</button>

    <!-- Create Session Modal -->
    <div v-if="isCreateOpen" class="modal-overlay" @click="closeCreateModal">
      <div class="modal-content" @click.stop style="max-width:540px">
        <h3>Create Checkup Session</h3>
        <form @submit.prevent="submitCreate">
          <div class="grid2">
            <label>
              Staff Email
              <select v-model="createForm.staff_email" required @change="syncDuplicateDefault">
                <option v-for="staffEmail in staffEmailList" :key="staffEmail.staff_email" :value="staffEmail.staff_email">
                  {{ staffEmail.staff_name }} ({{ staffEmail.staff_email }})
                </option>
              </select>
            </label>

            <div>
              <label>
                Session Date
                <!-- <input type="date" v-model="createForm.session_date" required /> -->
              </label>
              <!-- max-date: Dec of next year -->
              <VueDatePicker 
                    v-model="createForm.session_date"
                    model-type="yyyy-MM-dd"
                    format="yyyy-MM-dd"
                    :max-date="new Date(new Date().getFullYear() + 1, 11, 31)"
                    :year-range="[1900, new Date().getFullYear() + 1]"
                    auto-apply
                    :enable-time-picker="false"
                />
            </div>

            <label>
              Type
              <select v-model="createForm.session_type" required>
                <option value="pre-employment">Pre-employment</option>
                <!-- <option value="annual">Annual</option> -->
                <option value="periodic">Periodic</option>
                <option value="followup">Follow-up</option>
                <option value="ad-hoc">Ad-hoc</option>
                <option value="return-to-work">Return to Work</option>
              </select>
            </label>

            <label>
              Status
              <select v-model="createForm.status" required>
                <option value="draft">draft</option>
                <option value="submitted">submitted</option>
                <option value="locked">locked</option>
              </select>
            </label>
          </div>

          <label style="display:flex;gap:.5rem;align-items:center;margin-top:8px">
            <input type="checkbox" v-model="createForm.no_forms" />
            No forms (doctor follow-up)
          </label>

          <!-- toggle to choose "duplicate" or "empty" -->
          <label style="display:flex;gap:.5rem;align-items:center;margin-top:8px">
            <input
              type="checkbox"
              v-model="createForm.duplicate_recent"
              :disabled="createForm.no_forms || !hasPrevForSelected"
            />
            Duplicate data from previous session
            <span v-if="!hasPrevForSelected" class="muted">— no previous session found</span>
          </label>

          <div class="modal-actions">
            <button type="submit" :disabled="creating">{{ creating ? 'Creating…' : 'Create' }}</button>
            <button type="button" class="ghost" @click="closeCreateModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Session Modal -->
    <div v-if="isEditOpen" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop style="max-width:540px">
        <h3>Edit Checkup Session</h3>
        <form @submit.prevent="submitEdit">
          <div class="grid2">
            <label>
              Staff (read-only)
              <input type="text" :value="editForm.staff_email" disabled />
            </label>

            <label v-if="isAdminRole">
              Assigned Doctor
              <select v-model="editForm.assigned_doctor_email">
                <option v-for="doctor in doctorEmailList" :key="doctor.doctor_email" :value="doctor.doctor_email">
                  {{ doctor.doctor_name }} ({{ doctor.doctor_email }})
                </option>
              </select>
            </label>

            <label>
              Session Remarks
              <textarea v-model="editForm.session_remarks" placeholder="Overall session remarks" :disabled="isAdminRole"></textarea>
            </label>

            <div v-if="isAdminRole">
              <label v-if="isAdminRole">Session Date</label>
                <VueDatePicker 
                    v-model="editForm.session_date"
                    model-type="yyyy-MM-dd"
                    format="yyyy-MM-dd"
                    :max-date="new Date(new Date().getFullYear() + 1, 11, 31)"
                    :year-range="[1900, new Date().getFullYear() + 1]"
                    auto-apply
                    :enable-time-picker="false"
                />
            </div>

            <label v-if="isAdminRole">
              Type
              <select v-model="editForm.session_type" required>
                <option value="pre-employment">Pre-employment</option>
                <!-- <option value="annual">Annual</option> -->
                <option value="periodic">Periodic</option>
                <option value="followup">Follow-up</option>
                <option value="ad-hoc">Ad-hoc</option>
                <option value="return-to-work">Return to Work</option>
              </select>
            </label>

            <label v-if="isAdminRole">
              Status
              <select v-model="editForm.status" required>
                <option value="draft">draft</option>
                <option value="submitted">submitted</option>
                <option value="locked">locked</option>
              </select>
            </label>
          </div>

          <div class="modal-actions">
            <button type="submit" :disabled="editing">
              {{ editing ? 'Saving…' : 'Save Changes' }}
            </button>
            <button type="button" class="ghost" @click="closeEditModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Top Filter Toolbar -->
    <div class="topbar" style="margin-bottom: 0%;">

      <div class="search-container">
        <input
          v-model.trim="search"
          type="search"
          class="search"
          placeholder="Search staff (name or email)…" />
        <button v-if="search" @click="clearSearch" class="clear-btn" type="button" title="Clear search">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="filters" style="margin-top: 5px;">
        <select v-model="yearFilter">
          <option value="">All Years</option>
          <option v-for="year in availableYears" :key="year" :value="year.toString()">
            {{ year }}
          </option>
        </select>

        <select v-model="typeFilter">
          <option value="">All Types</option>
          <option value="pre-employment">Pre-employment</option>
          <!-- <option value="annual">Annual</option> -->
          <option value="periodic">Periodic</option>
          <option value="followup">Follow-up</option>
          <option value="ad-hoc">Ad-hoc</option>
          <option value="return-to-work">Return to Work</option>
        </select>
      
        <select v-model="statusFilter">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="submitted">Submitted</option>
          <option value="locked">Locked</option>
        </select>
      </div>

      <!-- Pagination -->
      <div class="pagination-bar" style="display: flex; align-items: center; justify-content: flex-start; margin-bottom: 33px;">

        <div class="page-info" style="margin-bottom: auto; margin-right: 0%;">
          <button class="page-btn" :disabled="page<=1 || loading" @click="goFirst">« First</button>
          <button class="page-btn" :disabled="page<=1 || loading" @click="prevPage">‹ Prev</button>
          <span class="page-number" style="margin-right: 8px">Page {{ page }} / {{ totalPages || 1 }}</span>
          <button class="page-btn" :disabled="page>=totalPages || loading" @click="nextPage">Next ›</button>
          <button class="page-btn" :disabled="page>=totalPages || loading" @click="goLast">Last »</button>
        </div>

        <div class="filters" style="margin-top: 3px; margin-left: 0%;">
          <select v-model.number="pageSize" @change="changePageSize">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
        </div>

      </div>

    </div>

    <!-- Info Overlay: Session Status -->
    <!-- <div class="info-container" style="margin-top: 0%;">
      <button class="info-btn" style="margin-top: 0%" @click="showInfo = !this.showInfo">ℹ</button> -->
    <!-- <div class="info-container" style="margin-top: 0%;">
      <span class="info-icon" style="align-items: right; margin-top: 0%;" @click="showInfo = !this.showInfo">ℹ️</span>

      <div v-if="showInfo" class="info-overlay" @click.self="showInfo = false">
        <div class="info-box">
          <h4 style="color:black">Status Guide</h4>
          <p style="color: black">
            Status: <br>
            <b style="color: yellowgreen;">Draft</b> - This session has not been submitted yet.<br>
            <b style="color: blue;">Submitted</b> - This session has been submitted.<br>
            <b style="color: green;">Locked</b> - This session has been locked and cannot be edited.<br>
          </p>
          
          <button style="color: white; background-color: blue;" @click="showInfo = false">Got it</button>
        </div>
      </div>
    </div> -->

    <!-- <div style="display: flex; align-items: center; margin-bottom: 8px; justify-content: flex-start;"> -->
    <div class="toolbar">
      <button v-if="isAdminRole" class="new-btn" @click="createNewSession">
        + New Session
      </button>

      <button @click="showMetadata = !showMetadata" class="new-btn">
        {{ showMetadata ? 'Hide Metadata Columns' : 'Show Metadata Columns' }}
      </button>

      <button @click="showHasTables = !showHasTables" class="new-btn">
        {{ showHasTables ? 'Hide Has-Forms Columns' : 'Show Has-Forms Columns' }}
      </button>

      <div>
        <ExportExcel
          :rows="pagedSessions"
          :allRows="sessions"
          :columns="nsColumns"
          filename="session-list.xlsx"
          sheetName="Session Data"
          title="Export Session Columns"
        >
          <template #trigger="{ open, disabled }">
            <button :disabled="disabled" @click="open" class="new-btn">Export Excel</button>
          </template>
        </ExportExcel>
      </div>  
    </div>

    <!-- Main Table -->
    <div class="table-wrap" @scroll="onTableScroll">
      <table>
        <thead>
          <tr>
            <th v-if="!isFilteredView">Staff</th>
            <th @click="toggleDateSort" style="min-width: 110px; cursor:pointer">
              Session Date
              <span v-if="sortDir === 'asc'">▲</span>
              <span v-else>▼</span>
            </th>
            <th style="width: min-content;">Type</th>
            <th style="width: min-content;">Status</th>
            <th v-if="isAdminRole && showMetadata">Created By</th>
            <th v-if="isAdminRole && showMetadata">Created At</th>
            <th v-if="isAdminRole && showMetadata">Updated By</th>
            <th v-if="showMetadata">Updated At</th>
            <th v-if="showMetadata">Has Forms (All)</th>
            <th v-if="showHasTables">Has Forms (Medical History)</th>
            <th v-if="showHasTables">Has Forms (Lifestyle)</th>
            <th v-if="showHasTables">Has Forms (Physical Exams)</th>
            <th v-if="showHasTables">Has Forms (Investigations)</th>
            <!-- reports column header -->
            <th class="nowrap">Reports</th>
            <th style="min-width: 120px;">Doctor Info</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <!-- <tr v-for="s in pagedSessions" :key="s.session_id"> -->
          <tr v-for="(s, i) in pagedSessions" :key="s.session_id ?? i">
            <td 
              v-if="!isFilteredView"
              :class="{ 'row-deleted': s.staff_deleted_at !== null }"
            >
              <div class="pair"><span class="v"><b>{{ s.staff_name }}</b></span></div>
            </td>
            <td>{{ formatDateShort(s.session_date) }}</td>
            <td>{{ s.session_type }}</td>
            <td>
              <span class="status-pill" :class="s.status">{{ s.status }}</span>
            </td>
            <td v-if="isAdminRole && showMetadata">{{ s.created_by || '-' }}</td>
            <td v-if="isAdminRole && showMetadata">{{ formatDate(s.created_at) }}</td>
            <td v-if="isAdminRole && showMetadata">{{ s.updated_by || '-' }}</td>
            <td v-if="showMetadata">{{ formatDate(s.updated_at) }}</td>
            <td v-if="showMetadata">{{ s.has_forms_all ? 'Yes' : 'No' }}</td>
            <td v-if="showHasTables">
              <span :style="{color: s.has_mh ? '#3e8e41' : '#8b0a1a'}">{{ s.has_mh ? 'Yes' : 'No' }}</span>
            </td>
            <td v-if="showHasTables">
              <span :style="{color: s.has_lifestyle ? '#3e8e41' : '#8b0a1a'}">{{ s.has_lifestyle ? 'Yes' : 'No' }}</span>
            </td>
            <td v-if="showHasTables">
              <span :style="{color: (s.has_pe1 && s.has_pe2) ? '#3e8e41' : '#8b0a1a'}">{{ (s.has_pe1 && s.has_pe2) ? 'Yes' : 'No' }}</span>
            </td>
            <td v-if="showHasTables">
              <span :style="{color: (s.has_investigations && s.has_ilab) ? '#3e8e41' : '#8b0a1a'}">{{ (s.has_investigations && s.has_ilab) ? 'Yes' : 'No' }}</span>
            </td>
            
            <!-- Reports column -->
            <td class="reports-cell" @click.stop>
              <!-- report status pill -->
              <div class="report-pill">
                <span v-if="reportExists(s)" class="pill ok">uploaded</span>
                <span v-else class="pill muted">none</span>
              </div>
              
              <div class="report-menu" @click.stop>
                <button
                  class="menu-btn"
                  @click.stop="toggleReportMenu(s.session_id)"
                >
                  Reports ▾
                </button>

                <!-- dropdown -->
                <div
                  class="menu-list"
                  v-if="openReportMenuId === s.session_id"
                  @click.stop
                >
                  <!-- loader / empty states -->
                  <div v-if="reportsLoadingId === s.session_id" class="muted">Loading…</div>
                  <div v-else-if="(reportsBySession[s.session_id] || []).length === 0" class="muted">
                    No reports.
                  </div>

                  <!-- list -->
                  <div v-else class="report-list">

                    <div v-for="r in reportsBySession[s.session_id]" :key="r.report_id">
                      <div class="meta">
                        <strong>{{ r.title || (r.file_name.length > 10 ? r.file_name.substring(0, 7) + '...' : r.file_name) }}</strong>
                        <div class="sub">
                          {{ (r.file_size/1024).toFixed(0) }} KB •
                          {{ formatDate(r.uploaded_at) }}
                        </div>

                        <div class="upload-block">
                          <a
                            :href="`${baseUrl}/reports/${r.report_id}/download`"
                            target="_blank" rel="noopener" class="btn"
                          >
                            View/Download
                          </a>
                          <a
                            class="btn-danger"
                            @click="deleteReport(s.session_id, r.report_id)"
                            :disabled="deletingReportId === r.report_id"
                            title="Delete"
                          >
                            {{ deletingReportId === r.report_id ? 'Deleting…' : 'Delete' }}
                          </a>
                        </div>

                      </div>
                    </div>
                    
                  </div>

                  <hr v-if="true">

                  <!-- upload area -->
                  <div class="upload-block" v-if="!reportExists(s)">
                    <input
                      class="title-input"
                      type="text"
                      placeholder="Optional title"
                      v-model="uploadTitleBySession[s.session_id]"
                    />
                    <input
                      class="file-input"
                      type="file"
                      accept="application/pdf"
                      @change="onPickFile(s.session_id, $event)"
                    />
                    <button
                      class="upload-btn"
                      @click="uploadReport(s)"
                      :disabled="!uploadFileBySession[s.session_id] || uploadingSessionId === s.session_id"
                    >
                      {{ uploadingSessionId === s.session_id ? 'Uploading…' : 'Upload PDF' }}
                    </button>
                  </div>
                </div>
              </div>
            </td>

            <td :class="{ 'row-deleted': s.doctor_deleted_at !== null }">
              <p :class="{'muted-text': s.assigned_doctor_email === null}">
                {{ s.assigned_doctor_email || 'Unassigned' }}
              </p>
              <p :class="{'muted-text': s.session_remarks === null}">
                {{ s.session_remarks || 'No remarks' }}
              </p>
            </td>

            <td class="actions">
              <button v-if="s.has_forms_all" @click="viewSession(s)">Open</button>
              <button 
                v-if="s.status !== 'locked'"
                class="btn-action"
                :class="statusBtnClass(s.status)"
                style="background-color:#d4edda; border-color:#c3e6cb;"
                @click="advanceStatus(s)"
                :disabled="loadingId === s.session_id"
              >
                <span v-if="loadingId === s.session_id">...</span>
                <span v-else>{{ statusBtnText(s.status) }}</span>
              </button>

              <!-- <span v-else class="badge-locked">
                <i class="fas fa-lock"></i> Locked
              </span> -->

              <button 
                v-if="s.status !== 'draft'" 
                style="background-color:#fee2e2; border-color:#fee2e2;"
                @click.prevent="revertStatus(s)"
                title="Revert to previous status"
              >
                Un{{ s.status === 'locked' ? 'lock' : 'submit' }}
              </button>
              <!-- <button @click="viewSession(s)">Open</button> -->
              <div class="action-menu" @click.stop>
                <button class="menu-btn" @click="toggleRowMenu(s.session_id)">Actions ▾</button>

                <div class="menu-list" v-if="openRowMenuId === s.session_id">
                  <!-- <button @click="viewSession(s)">View/Edit Data</button> -->
                  <button @click="openEdit(s)">Edit Session</button>
                  <button
                    @click="$router.push({ 
                      name: 'export-medical', 
                      query: { session_id: s.session_id, staff_email: s.staff_email } 
                    })"
                  >
                    Export Session Excel
                  </button>
                  <button class="danger" @click="deleteSession(s)">Delete Session & Data</button>
                </div>
              </div>
            </td>
          </tr>

          <tr v-if="filteredSessions.length === 0" class="empty-row">
            <td colspan="8">
              <div class="empty">No sessions found.</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- sticky horizontal scrollbar (proxy) -->
    <div class="x-scroll" ref="xScroll" @scroll="onXScroll">
      <!-- This spacer’s width will be set to match the table’s scroll width -->
      <div :style="{ width: tableScrollWidth + 'px', height: '1px' }"></div>
    </div>
  </div>

</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { getSessionHeader, setSessionHeader, invalidateSessionHeader } from '@/shared/sessionHeaderCache';
import ExportExcel from '@/components/ExportExcel.vue';
import { formatDate, formatDateShort } from '@/shared/dateFormat';
import { VueDatePicker } from '@vuepic/vue-datepicker';

export default {
  components: { ExportExcel, VueDatePicker },
  props: { rows: { type: Array, default: () => [] } },
  data() {
    let role = null;
    try { role = JSON.parse(localStorage.getItem('user_info'))?.role || null; } catch { /* ignore */ }
    return {
      isAdminRole: role === 'admin',
      loadingId: null,
      showInfo: false,
      page: 1,
      pageSize: 10,
      sortDir: 'desc',
      loading: false,
      tableScrollWidth: 0,
      resizeObs: null,

      baseUrl: cfg.API_BASE_URL,

      sessions: [],
      // email lists for session assigned users
      staffEmailList: [],
      doctorEmailList: [],

      search: "",
      yearFilter: "",
      typeFilter: "",
      statusFilter: "",
      showMetadata: false,
      showHasTables: false,
      openRowMenuId: null,
      openReportMenuId: null,

      reportsBySession: {}, // { [session_id]: Array<Report> }
      reportsLoadingId: null, // session_id currently loading
      uploadTitleBySession: {}, // { [session_id]: string }
      uploadFileBySession: {}, // { [session_id]: File }
      uploadingSessionId: null,
      deletingReportId: null,
      reportHas: {}, // { [session_id]: number }

      // create modal
      isCreateOpen: false,
      creating: false,
      createForm: {
        staff_email: '',
        // session_date: new Date().toISOString().slice(0,10), // yyyy-mm-dd
        session_date: new Date().toLocaleDateString('en-CA'), // use 'en-CA' locale which outputs YYYY-MM-DD in LOCAL time
        // session_type: 'annual',
        session_type: 'periodic',
        status: 'draft',
        duplicate_recent: true,
        no_forms: false,
        init_tables: true,
      },
      hasPrevByStaff: {}, // { [staff_email]: boolean }

      // edit modal
      isEditOpen: false,
      editing: false,
      editForm: {
        session_id: null,
        staff_email: '',
        assigned_doctor_email: null,
        session_remarks: null,
        session_date: '',
        session_type: 'annual',
        status: 'draft'
      },

      // duplicate: - old
      duplicating: false,
    };
  },
  computed: {
    isFilteredView() {
        return !!this.$route.query.staff; 
    },
    totalPages() {
      const total = Math.ceil(this.filteredSessions.length / this.pageSize) || 1;
      return total;
    },
    pagedSessions() {
      const start = (this.page - 1) * this.pageSize;
      // return this.filteredSessions.slice(start, start + this.pageSize);
      // console.log('this.sortedSessions', this.sortedSessions.slice(start, start + this.pageSize));
      return this.sortedSessions.slice(start, start + this.pageSize);
    },
    sortedSessions() {
      const dir = this.sortDir === 'asc' ? 1 : -1;
      return [...this.filteredSessions].sort((a, b) => {
        const at = toTime(a.session_date);
        const bt = toTime(b.session_date);
        if (isNaN(at) && isNaN(bt)) return 0;
        if (isNaN(at)) return 1;
        if (isNaN(bt)) return -1;
        if (at === bt) return 0;
        return at > bt ? dir : -dir;
      });
    },
    availableYears() {
      const arr = Array.isArray(this.sessions) ? this.sessions : [];
      const years = new Set(
        arr
          .map(s => {
            // handle ISO date, 'YYYY-MM-DD', or Date-like
            const d = s?.session_date ? new Date(s.session_date) : null;
            return d && !isNaN(d) ? d.getFullYear() : null;
          })
          .filter(y => y !== null)
      );
      return Array.from(years).sort((a, b) => b - a); // newest first
    },
    filteredSessions() {
      const q = this.search.toLowerCase();
      return this.sessions.filter(s => {
        const matchSearch =
          !q ||
          (s.staff_name && s.staff_name.toLowerCase().includes(q)) ||
          (s.staff_email && s.staff_email.toLowerCase().includes(q));

        const matchYear =
          !this.yearFilter ||
          new Date(s.session_date).getFullYear().toString() === this.yearFilter;

        const matchStatus =
          !this.statusFilter || s.status === this.statusFilter;

        const matchType =
          !this.typeFilter || s.session_type === this.typeFilter;

        return matchSearch && matchYear && matchType && matchStatus;
      });
    },
    // derive current staff email from your page state or URL (?staff=…)
    // activeStaffEmail() {
    //   // if already store selected staff in data/computed, use that instead.
    //   // otherwise parse from query param:
    //   const q = this.$route?.query?.staff || '';
    //   return q || ''; // return '' if none
    // },
    hasPrevForSelected() {
      return !!this.hasPrevByStaff[this.createForm.staff_email];
    },
    nsColumns() {
      return [
        // { key: 'session_id', label: 'Session ID' },
        { key: 'staff_name', label: 'Staff Name' },
        { key: 'staff_email', label: 'Staff Email' },
        { key: 'session_date', label: 'Session Date' },
        { key: 'session_type', label: 'Session Type' },
        { key: 'status', label: 'Status' },
        { key: 'created_by', label: 'Created By' },
        { key: 'created_at', label: 'Created At' },
        { key: 'updated_by', label: 'Updated By' },
        { key: 'updated_at', label: 'Updated At' },
        { key: 'assigned_doctor_email', label: 'Assigned Doctor Email' },
        { key: 'session_remarks', label: 'Session Remarks' },
      ];
    }
  },

  mounted() {
    this.fetchSessions();
    this.fetchEmails();
    window.addEventListener("click", this.closeMenus);
    this.applyStaffQuery();
    // avoid adding too many event listeners
    if (!this._onDocClick_reports) {
      this._onDocClick_reports = (e) => {
        if (!e.target.closest('.report-menu')) {
          this.openReportMenuId = null;
        }
      };
      document.addEventListener('click', this._onDocClick_reports);
    }
    this.$nextTick(() => this.measureXBar());
    // keep it in sync on resize/content changes
    window.addEventListener('resize', this.measureXBar);
    this.resizeObs = new ResizeObserver(() => this.measureXBar());
    const wrap = this.$el.querySelector('.table-wrap');
    if (wrap) this.resizeObs.observe(wrap);
  },
  watch: { 
    '$route.query.staff'() { this.applyStaffQuery(); },
    // keep modal's staff_email in sync with current selection
    activeStaffEmail: {
      immediate: true,
      handler(v) {
        if (v && (!this.createForm.staff_email || this.isCreateOpen)) {
          this.createForm.staff_email = v;
        }
      }
    },
    search() { this.page = 1; },
    yearFilter() { this.page = 1; },
    statusFilter() { this.page = 1; },
    sessions: {
      deep: true,
      handler() { 
        // reset and clamp whenever the source list changes
        if (this.page > this.totalPages) this.page = this.totalPages;
      }
    },
    totalPages(n) {
      if (this.page > n) this.page = n;
      if (this.page < 1) this.page = 1;
    },
    'createForm.session_type'(v) {
      // auto-check "No forms" when follow-up is chosen
      this.createForm.no_forms = (v === 'followup');
    },
    'createForm.no_forms'(v) {
      // when "No forms" is true, duplication is irrelevant → turn it off
      if (v) this.createForm.duplicate_recent = false;
    },
  },
  beforeUnmount() {
    window.removeEventListener("click", this.closeMenus);
    window.removeEventListener('resize', this.measureXBar);
    if (this.resizeObs) this.resizeObs.disconnect();
  },
  methods: {
    // pagination controls:
    goFirst() { this.page = 1; },
    prevPage() { if (this.page > 1) this.page--; },
    nextPage() { if (this.page < this.totalPages) this.page++; },
    goLast() { this.page = this.totalPages; },
    changePageSize() {
      // when page size changes, jump back to page 1 to avoid landing on an empty page
      this.page = 1;
    },
    toggleDateSort() {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
    },
    // fmt(d) {
    //   if (!d) return '-'
    //   const t = toTime(d)
    //   if (isNaN(t)) return d
    //   return new Date(t).toISOString().slice(0, 10) // YYYY-MM-DD
    // },
    formatDate, formatDateShort,
    // submit button functions:
    // 1. Decides Button Color
    statusBtnClass(status) {
      if (status === 'draft') return 'btn-blue';      // Blue for "Submit"
      if (status === 'submitted') return 'btn-green'; // Green for "Lock"
      return '';
    },

    // 2. Decides Button Text
    statusBtnText(status) {
      if (status === 'draft') return 'Submit';
      if (status === 'submitted') return 'Lock';
      return '';
    },

    // 3. Action: Move Forward (Draft -> Submitted -> Locked)
    async advanceStatus(session) {
      let nextStatus = '';
      if (session.status === 'draft') nextStatus = 'submitted';
      else if (session.status === 'submitted') nextStatus = 'locked';
      else return; // Should not happen if button is hidden

      await this.updateStatus(session, nextStatus);
    },

    // 4. Action: Move Backward (Locked -> Submitted -> Draft)
    async revertStatus(session) {
      // Safety Check: Ask user before unlocking!
      if (!confirm("Are you sure you want to revert this status?")) return;

      let prevStatus = '';
      if (session.status === 'locked') prevStatus = 'submitted';
      else if (session.status === 'submitted') prevStatus = 'draft';

      await this.updateStatus(session, prevStatus);
    },

    // 5. The API Call
    async updateStatus(session, newStatus) {
      this.loadingId = session.session_id;
      try {
        const res = await fetch(`${this.baseUrl}/edit-status-session/${session.session_id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
          },
          body: JSON.stringify({ status: newStatus })
        });

        if (res.ok) {
          // Update UI instantly without refreshing
          session.status = newStatus;
        } else {
          alert("Failed to update status");
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.loadingId = null;
      }
    },
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
    // fetch:
    async fetchEmails() {
      // const baseUrl = cfg.API_BASE_URL;

      try {
        const res = await fetch(`${this.baseUrl}/admin/staff-list`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        const payload = await res.json();
        this.staffEmailList = Array.isArray(payload) ? payload : [];
      } catch (e) {
        console.error(e);
        this.staffEmailList = []; // still keep it as an array on error
      }
      try {
        const res = await fetch(`${this.baseUrl}/admin/doctor-list`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        const payload = await res.json();
        this.doctorEmailList = Array.isArray(payload) ? payload : [];
      } catch (e) {
        console.error(e);
        this.doctorEmailList = []; // still keep it as an array on error
      }
    },
    async fetchSessions() {
      // const baseUrl = cfg.API_BASE_URL;

      try {
        const res = await fetch(`${this.baseUrl}/admin/sessions`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        const payload = await res.json();
        if (handleUnauthorized(res)) return;

        // normalize to an array no matter what the backend sends
        this.sessions = Array.isArray(payload)
          ? payload
          : (Array.isArray(payload?.sessions) ? payload.sessions : []);

        this.sessions.forEach(s => {
          s.has_forms_all = 
            (s.has_investigations) &&
            (s.has_ilab) &&
            (s.has_lifestyle) &&
            (s.has_mh) &&
            (s.has_pe1) &&
            (s.has_pe2); 
        });

        this.refreshReportFlagsForSessions(this.sessions.map(s => s.session_id));
        this.hasPrevByStaff = this.sessions.reduce((acc, s) => {
          acc[s.staff_email] = true; // any session means "has previous"
          return acc;
        }, {});
      } catch (e) {
        console.error(e);
        this.sessions = []; // still keep it as an array on error
      }
    },
    applyStaffQuery() {
      const q = this.$route.query.staff;
      if (!q) return;

      // decode the email from the URL
      const email = decodeURIComponent(q);

      this.search = email;

      // if you also select a row programmatically, do it here:
      // e.g., this.selectedStaffEmail = email;

      // if your staff list loads async, call this again after data arrives:
      // this.$nextTick(() => this.applyStaffQuery());
    },
    toggleRowMenu(id) {
      this.openRowMenuId = this.openRowMenuId === id ? null : id;
    },
    closeMenus() {
      this.openRowMenuId = null;
    },
    // Actions
    createNewSession() { // == openCreateModal()
      // pick current staff
      const fromQuery = this.$route?.query?.staff || '';
      this.createForm = {
        staff_email: fromQuery || (this.staffEmailList[0]?.staff_email || ''),
        session_date: new Date().toISOString().slice(0,10),
        // session_type: 'annual',
        session_type: 'periodic',
        status: 'draft',
        duplicate_recent: true, // default true (will be disabled if not allowed)
        no_forms: false,
        init_tables: !this.createForm.no_forms,
      };
      // if no previous for selected staff, will auto-uncheck (it remains disabled)
      if (!this.hasPrevForSelected) this.createForm.duplicate_recent = false;
      this.isCreateOpen = true;
      // console.log(this.hasPrevByStaff);
    },
    closeCreateModal() {
      this.isCreateOpen = false;
    },
    syncDuplicateDefault() {
      // if staff changes, keep checkbox checked only if previous session exists
      if (!this.hasPrevForSelected) this.createForm.duplicate_recent = false;
    },
    async submitCreate() {
      if (!this.createForm.staff_email) {
        alert('Please select a staff.');
        return;
      }
      if (!this.isAdminRole) {
        alert('Only admins can create sessions.');
        return;
      }
      if (this.createForm.no_forms) {
        this.createForm.init_tables = false;
        this.createForm.duplicate_recent = false;
      }

      // const baseUrl = cfg.API_BASE_URL;
      this.creating = true;

      try {
        const endpoint =
          (this.createForm.duplicate_recent && this.hasPrevForSelected)
            ? `${this.baseUrl}/sessions/duplicate-last` // "create duplicate" endpoint
            : `${this.baseUrl}/create-session`; // "create empty" endpoint

        const res = await fetch(endpoint, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            staff_email: this.createForm.staff_email,
            session_date: this.createForm.session_date,
            session_type: this.createForm.session_type,
            status: this.createForm.status,
            created_by: JSON.parse(localStorage.getItem('user_info')).email,
            init_tables: !this.createForm.no_forms,  // true by default, false for follow-up
          })
        });

        if (!res.ok) {
          const msg = await res.text();
          throw new Error(msg || 'Create failed');
        }

        // Refresh your table/list (keeps your date sorting intact)
        await this.fetchSessions();
        // Rebuild the "hasPrev" index so the checkbox enables for the next time
        this.hasPrevByStaff = this.sessions.reduce((acc, s) => (acc[s.staff_email] = true, acc), {});
        this.isCreateOpen = false;

      } catch (e) {
        alert(e.message || 'Failed to create session');
      } finally {
        this.creating = false;
      }
    },
    viewSession(s) { 
      // alert(`View ${s.session_id}`);
      const row = s;
      this.$router.push({
        name: 'session-medical-history',
        params: { sessionId: row.session_id },
        query:  { staff: row.staff_email, name: row.staff_name }
      });
    },
    // edit session data
    openEdit(s) {
      // prefill from the row
      this.editForm = {
        session_id: s.session_id,
        staff_email: s.staff_email,
        assigned_doctor_email: s.assigned_doctor_email,
        session_remarks: s.session_remarks,
        session_date: this.toYMD(s.session_date),
        // session_date: s.session_date,
        session_type: s.session_type,
        status: s.status
      };
      this.isEditOpen = true;
    },

    closeEditModal() {
      this.isEditOpen = false;
      this.editing = false;
      // reset form
      this.editForm = {
        session_id: null,
        staff_email: '',
        assigned_doctor_email: null,
        session_remarks: null,
        session_date: '',
        // session_type: 'annual',
        session_type: 'periodic',
        status: 'draft'
      };
    },

    // toYMD(d) {
    //   if (!d) return '';
    //   // accept 'YYYY-MM-DD' or Date/ISO
    //   const dt = new Date(d);
    //   if (isNaN(dt)) return String(d).slice(0,10);
    //   return dt.toISOString().slice(0,10);
    // },
    toYMD(d) {
      if (!d) return '';
      
      // 1. If it's already a simple date string "YYYY-MM-DD", just return it.
      if (typeof d === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(d)) {
        return d;
      }

      // 2. Parse the date
      const dt = new Date(d);
      if (isNaN(dt)) return String(d).slice(0,10);

      // 3. FIXED: Extract LOCAL year, month, date manually
      // Do NOT use .toISOString() here because it converts to UTC
      const year = dt.getFullYear();
      const month = String(dt.getMonth() + 1).padStart(2, '0');
      const day = String(dt.getDate()).padStart(2, '0');

      return `${year}-${month}-${day}`;
    },

    async submitEdit() {
      const { session_id, assigned_doctor_email, session_remarks, session_date, session_type, status } = this.editForm;
      // const baseUrl = cfg.API_BASE_URL;

      if (!session_id || !session_date || !session_type || !status) {
        alert('Please fill all fields.');
        return;
      }

      this.editing = true;
      try {
        const updated_by = JSON.parse(localStorage.getItem('user_info')).email;

        const payload = {
          assigned_doctor_email: assigned_doctor_email || null,
          session_remarks: session_remarks ?? null,
          session_date,
          session_type,
          status,
          updated_by
        };

        const res = await fetch(`${this.baseUrl}/edit-session/${session_id}`, {
          method: 'PUT',
          headers: {
            Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error(await res.text() || 'Failed to update session');

        // refresh the list
        await this.fetchSessions();

        // keep the session header cache consistent (in case ViewSession is open elsewhere)
        invalidateSessionHeader(session_id);
        // repopulate light endpoint
        try {
          const fresh = await getSessionHeader(session_id, { force: true }); // force fetch
          setSessionHeader(session_id, fresh);
        } catch { /* ignore */ }

        this.isEditOpen = false;
      } catch (e) {
        alert(e.message || 'Failed to update session');
      } finally {
        this.editing = false;
      }
    },
    // delete session
    async deleteSession(s) {
      const sid = s.session_id;
      if (!sid) return;

      if (!confirm(`Delete session #${sid} for ${s.staff_email}? This will also remove its report files.`)) {
        return;
      }

      try {
        // 1) Ensure we have the report list for this session
        let reports = this.reportsBySession[sid];
        if (!Array.isArray(reports)) {
          // attempt to fetch if not cached
          const r = await fetch(`${this.baseUrl}/sessions/${sid}/reports`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
          });
          reports = (await r.json()) || [];
        }

        // 2) Delete report (so file are removed server-side)
        if (Array.isArray(reports) && reports.length) {
          const firstReport = reports[0];
          try {
            await fetch(`${this.baseUrl}/reports/${firstReport.report_id}`, {
              method: 'DELETE',
              headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
            });
          } catch (e) { /* best-effort */ }
        }

        // 3) Delete the session row
        const del = await fetch(`${this.baseUrl}/delete-session/${sid}`, {
          method: 'DELETE',
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        if (!del.ok) {
          const txt = await del.text();
          throw new Error(txt || 'Failed to delete session');
        }

        // 4) Clean up UI: remove row & caches, close menus, refresh pills
        this.sessions = this.sessions.filter(row => row.session_id !== sid);

        // clear report cache + pill state for this sid
        if (this.$delete) {
          this.$delete(this.reportsBySession, sid);
          this.$delete(this.reportHas, sid);
        } else {
          const rb = { ...this.reportsBySession }; delete rb[sid]; this.reportsBySession = rb;
          const rh = { ...this.reportHas }; delete rh[sid]; this.reportHas = rh;
        }

        if (this.openReportMenuId === sid) this.openReportMenuId = null;
        if (this.openRowMenuId === sid) this.openRowMenuId = null;

        // optional: show a toast
        // alert('Session deleted');
      } catch (e) {
        console.error(e);
        alert(e.message || 'Failed to delete session');
      }
    },

    // reports:
    reportExists(s) {
      const sid = s.session_id;
      if (this.reportHas[sid] !== undefined) return this.reportHas[sid];
      return !!s.has_report; // fallback if your backend already returns it
    },

    // internal cache setter
    setReportExists(sessionId, val) {
      const next = { ...this.reportHas, [sessionId]: !!val };
      this.reportHas = next;
      this.$nextTick(() => this.reportHas = next);
    },

    // fetch whether a report exists for each session
    async refreshReportFlagsForSessions(sessionIds) {
      // const baseUrl = cfg.API_BASE_URL;

      if (!Array.isArray(sessionIds) || sessionIds.length === 0) return;
      await Promise.all(sessionIds.map(async (sid) => {
        try {
          const r = await fetch(`${this.baseUrl}/admin/checkup-reports/${sid}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
          });
          if (!r.ok) throw 0;
          const arr = await r.json();
          this.setReportExists(sid, Array.isArray(arr) && arr.length > 0);
        } catch {
          this.setReportExists(sid, false);
        }
      }));
    },

    // call these inside your upload/delete success handlers
    onReportUploaded(sessionId) {
      this.setReportExists(sessionId, true);
    },
    onReportDeleted(sessionId) {
      this.setReportExists(sessionId, false);
    },

    // --- reports menu toggle ---
    toggleReportMenu(sessionId) {
      this.openReportMenuId = (this.openReportMenuId === sessionId) ? null : sessionId;
      if (this.openReportMenuId) {
        this.ensureReportsLoaded(sessionId);
      }
    },

    // --- load list ---
    ensureReportsLoaded(sessionId) {
      // const baseUrl = cfg.API_BASE_URL;

      if (this.reportsBySession[sessionId]) return; // already loaded
      this.reportsLoadingId = sessionId;

      fetch(`${this.baseUrl}/sessions/${sessionId}/reports`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(r => r.json())
      .then(list => {
        this.$set
          ? this.$set(this.reportsBySession, sessionId, Array.isArray(list) ? list : [])
          : (this.reportsBySession = { ...this.reportsBySession, [sessionId]: Array.isArray(list) ? list : [] });
      })
      .catch(() => {
        this.$set
          ? this.$set(this.reportsBySession, sessionId, [])
          : (this.reportsBySession = { ...this.reportsBySession, [sessionId]: [] });
      })
      .finally(() => { this.reportsLoadingId = null; });
    },

    // --- pick file ---
    onPickFile(sessionId, e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      // accept only PDF (frontend guard)
      if (file.type !== 'application/pdf') {
        alert('Please select a PDF file.');
        e.target.value = '';
        return;
      }
      // store file by session
      if (this.$set) {
        this.$set(this.uploadFileBySession, sessionId, file);
      } else {
        this.uploadFileBySession = { ...this.uploadFileBySession, [sessionId]: file };
      }
    },

    // --- upload ---
    uploadReport(sessionRow) {
      const sessionId = sessionRow.session_id;
      const file = this.uploadFileBySession[sessionId];
      // const baseUrl = cfg.API_BASE_URL;

      if (!file) return;

      const fd = new FormData();
      fd.append('file', file);
      const title = this.uploadTitleBySession[sessionId] || '';
      if (title) fd.append('title', title);

      this.uploadingSessionId = sessionId;

      fetch(`${this.baseUrl}/sessions/${sessionId}/upload-report`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` },
        body: fd
      })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(() => {
        this.onReportUploaded(sessionId);
        // reset inputs
        if (this.$set) {
          this.$set(this.uploadTitleBySession, sessionId, '');
          this.$set(this.uploadFileBySession, sessionId, null);
          // 
        } else {
          this.uploadTitleBySession = { ...this.uploadTitleBySession, [sessionId]: '' };
          this.uploadFileBySession = { ...this.uploadFileBySession, [sessionId]: null };
        }
        // reload list
        // clear cached then refetch for freshness
        if (this.$delete) {
          this.$delete(this.reportsBySession, sessionId);
        } else {
          const clone = { ...this.reportsBySession };
          delete clone[sessionId];
          this.reportsBySession = clone;
        }
        this.ensureReportsLoaded(sessionId);
      })
      .catch(async (err) => {
        let msg = 'Upload failed';
        try { msg = (await err.json()).error || msg; }
        catch (e) { console.warn('Upload: could not parse error JSON', e); } 
        alert(msg);
      })
      .finally(() => { this.uploadingSessionId = null; });
    },

    // --- delete ---
    deleteReport(sessionId, reportId) {
      // const baseUrl = cfg.API_BASE_URL;

      if (!confirm('Delete this report?')) return;
      this.deletingReportId = reportId;

      fetch(`${this.baseUrl}/reports/${reportId}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(() => {
        // remove from local cache
        const list = this.reportsBySession[sessionId] || [];
        const next = list.filter(r => r.report_id !== reportId);
        this.onReportDeleted(sessionId);
        if (this.$set) {
          this.$set(this.reportsBySession, sessionId, next);
        } else {
          this.reportsBySession = { ...this.reportsBySession, [sessionId]: next };
        }
      })
      .catch(async (err) => {
        let msg = 'Delete failed';
        try { msg = (await err.json()).error || msg; }
        catch (e) { console.warn('Delete: could not parse error JSON', e); }
        alert(msg);
      })
      .finally(() => { this.deletingReportId = null; });
    },
  }
};
// helper outside export to avoid re-creating per call
function toTime(val) {
  return typeof val === 'number' ? val : Date.parse(val)
}
</script>

<style scoped>

.manage-sessions { padding: 24px; }
.back-btn { margin-bottom: 16px; background: none; border: none; color: #2563eb; cursor: pointer; }
.toolbar { display:flex; align-items:center; flex-wrap:nowrap; margin-top: 0; }
.search-input {
  flex:1 1 260px; 
  max-width:400px;
  height: 40px;
  padding:10px 12px;
  border:1px solid #e2e8f0;
  border-radius:8px;
  font-size:14px;
}
.search-input:focus {
  outline:none; border-color:#93c5fd;
  box-shadow:0 0 0 3px rgba(147,197,253,.35);
}

.dup-btn {
  background:#e2e8f0; border:none; border-radius:6px;
  padding:8px 12px; cursor:pointer;
}
/* .table-responsive { width:100%; overflow-x:auto; background:white; box-shadow:0 2px 6px rgba(0,0,0,.05); border-radius:8px; } */
table { width:100%; border-collapse:collapse; }
th, td {
  padding:12px 16px; text-align:left;
  border-bottom:1px solid #e2e8f0;
  vertical-align:top;
}
.muted-text {
  color: #6b7280;
  font-style: italic;
}
th { background:#f7fafc; color:#2d3748; font-weight:600; position:sticky; top:0; z-index:1; }
.status-pill {
  padding:4px 10px; border-radius:999px; font-size:12px;
  text-transform:capitalize; 
}
.status-pill.draft { background:#fef9c3; color:#92400e; }
.status-pill.submitted { background:#bfdbfe; color:#1e3a8a; }
.status-pill.locked { background:#d1fae5; color:#065f46; }

.reports-cell { position: relative; text-align: center; }
.report-menu { display: inline-block; position: relative; }

/* .modal {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
} */

/* dropdown actions */
.action-menu { position:relative; display:inline-block; }
.menu-btn {
  padding: 6px 10px;
  font-size: 12px;
  border-radius: 4px;
  background: #edf2f7;
  color: #2d3748;
  border: 1px solid #e2e8f0;
  cursor: pointer;
}
.menu-btn:hover { background: #e2e8f0; }
.menu-list {
  position: absolute;
  right: 0;
  top: calc(100% + 6px);
  min-width: 280px;
  max-width: 360px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  border-radius: 8px;
  padding: 10px;
  z-index: 5;
  width: max-content;
  min-width: unset;
  max-width: none;
  white-space: nowrap;
}
.menu-list button {
  border:none; background:none; text-align:left; padding:6px 10px; font-size:13px; 
  cursor:pointer; display: block; width: 100%;
}
.menu-list button:hover { background:#f7fafc; }
.menu-list button.danger { color:#b91c1c; padding-right: 6px; }
/* .empty-state { text-align:center; padding:24px; color:#4a5568; } */

.report-list { list-style: none; margin: 0; padding: 0; }
.report-list li {
  display: block;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px dashed #edf2f7;
}
.report-list li:last-child { border-bottom: none; }
.meta {
  display: block;
  max-width: 70%;
}
.meta .sub { color: #718096; font-size: 12px; }
.row-actions { display: inline-flex; gap: 10px; white-space: nowrap; }
.row-actions a { text-decoration: none; color: #2b6cb0; }
.row-actions a:hover { text-decoration: underline; }
.linklike { background: transparent; border: none; color: #c53030; cursor: pointer; padding: 0; }
.linklike:hover { text-decoration: underline; }

.row-deleted {
  /* background-color: #b8b8b8; */
  background-image: linear-gradient(to right, rgba(184,184,184,0.5), #ffffff);
  
  color: #cccccc;
  font-style: italic;
}

.row-deleted .badge {
  opacity: 0.2; 
}

.report-pill { margin-bottom: 6px; text-align: center; }
.pill {
  display: inline-block;
  padding: 3px 10px;
  font-size: 12px;
  border-radius: 999px;
  background: #edf2f7;
  color: #2d3748;
}
.pill.ok { background: #e6f6ea; color: #137333; }
.pill.muted { background: #eef2f6; color: #64748b; }
/* Upload area layout */
.upload-block {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px;
  margin-top: 10px;
  padding-top: 6px;
  border-top: 1px solid #e2e8f0;
  min-width: fit-content;
}

/* ← reset-proof enabled style */
button.upload-btn {
  background: #2563eb !important;   /* visible blue */
  color: #ffffff !important;
  border: none !important;
  opacity: 1 !important;
  visibility: visible !important;

  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0,0,0,.06);
  cursor: pointer;
}

/* hover/active (only when not disabled) */
button.upload-btn:hover:not(:disabled) { background: #1d4ed8 !important; }
button.upload-btn:active:not(:disabled) { background: #1e40af !important; }

/* keep disabled clearly visible */
button.upload-btn:disabled {
  background: #cbd5e0 !important;
  color: #2d3748 !important;
  cursor: not-allowed;
  opacity: 1 !important;
}


.muted { color: #718096; font-size: 12px; }
.danger { color: #c53030; }
.nowrap { white-space: nowrap; }
</style>
