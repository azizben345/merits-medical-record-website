<!-- AnalyticsAbnormalityDrilldown.vue -->
<template>
  <div class="abnormality-drilldown container">
    <h2>Lipids Abnormality Drill-down</h2>

    <div class="filters">
      <div class="filter-group">
        <label>Type</label>
        <select v-model="type">
          <option value="cholesterol">Cholesterol (LDL, TChol, HDL, TG)</option>
          <option value="liver">Liver Function (Bilirubin, GGT, AST, ALT)</option>
          <option value="glucose">Glucose (FBS, RBS)</option>
          <option value="uric_acid">Uric Acid</option>
          <option value="renal">Renal Panel (BUN, Creat, Electrolytes)</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Result Status</label>
        <select v-model="result_status">
            <option value="all">All</option>
            <option value="abnormal">Abnormal</option>
            <option value="normal">Normal</option>
            <option value="not done">Not done</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Date Range</label>
        <div class="date-row">
          <VueDatePicker 
            v-model="from"
            :max-date="new Date()" 
            :enable-time-picker="false"
            auto-apply
            placeholder="Start Date"
          />
          <!-- <span>-</span> -->
          <VueDatePicker 
            v-model="to"
            :max-date="new Date()" 
            :enable-time-picker="false"
            auto-apply
            placeholder="End Date"
          />
        </div>
      </div>

      <!-- <button @click="load" :disabled="loading">Refresh</button> -->

      <!-- Pagination Controls -->
      <div class="pagination-bar" style="float:right" v-if="results.length > 0">
          
          <div class="page-buttons" style="margin-bottom: 18px;">
              <button 
                  @click="changePage(currentPage - 1)" 
                  :disabled="currentPage === 1"
                  class="page-btn"
              >
                  ‹ Prev
              </button>
              <span class="page-number" style="margin-top:10px;">Page {{ currentPage }}</span>
              <button 
                  @click="changePage(currentPage + 1)" 
                  :disabled="currentPage === totalPages"
                  class="page-btn"
              >
                  Next ›
              </button>
          </div>
          <div class="filters" style="margin-left: 0%;">
            <select v-model.number="pageSize">
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
          </div>
      </div>
    </div>

    <div class="page-info">
        Showing {{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, results.length) }} of {{ results.length }}
    </div>

    <div v-if="loading" class="spinner">Loading…</div>

    <table v-else class="std-table">
      <thead>
        <tr>
          <th>Staff</th>
          <th @click="toggleDateSort" style="min-width: 110px; cursor:pointer">
            Session Date
            <span v-if="sortDir === 'asc'">▲</span>
            <span v-else>▼</span>
          </th>
          <th v-for="field in displayedFields" :key="field">{{ formatField(field) }}</th>
        </tr>
      </thead>
      <tbody>
        <!-- <tr v-for="row in results" :key="row.session_id"
            @click="gotoSession(row.session_id)" class="clickable"> -->
        <tr v-for="row in paginatedResults" :key="row.session_id">
          <td>{{ row.staff_name }}</td>
          <td>{{ formatDateShort(row.session_date) }}</td>
          <td v-for="field in displayedFields" :key="field"
              :class="highlightCell(row[field])">
            {{ row[field] || '—' }}
          </td>
        </tr>
      </tbody>
    </table>

    <p v-if="!loading && results.length === 0" class="muted">No records found for the selected filters.</p>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { formatDateShort } from '@/shared/dateFormat';
import { VueDatePicker } from '@vuepic/vue-datepicker';

const fieldLabels = {
  ldl_result: 'LDL', tchol_result: 'Total Chol', hdl_result: 'HDL', tg_result: 'TG',
  tbil_result: 'Total Bilirubin', ggt_result: 'GGT', ast_result: 'AST', alt_result: 'ALT',
  fbs_result: 'FBS', rbs_result: 'RBS',
  ua_result: 'Uric Acid',
  bu_result: 'Urea', creat_result: 'Creatinine', na_result: 'Na', k_result: 'K', cl_result: 'Cl'
};

const typeToFields = {
  cholesterol: ['ldl_result', 'tchol_result', 'hdl_result', 'tg_result'],
  liver: ['tbil_result', 'ggt_result', 'ast_result', 'alt_result'],
  glucose: ['fbs_result', 'rbs_result'],
  uric_acid: ['ua_result'],
  renal: ['bu_result', 'creat_result', 'na_result', 'k_result', 'cl_result'],
};

export default {
  components: { VueDatePicker },
  data() {
    return {
      currentPage: 1,
      pageSize: 10,
      type: 'cholesterol',
      result_status: 'all',
      from: null,
      to: null,
      debounceTimer: null,
      abortController: null,
      sortDir: 'desc',
      results: [],
      loading: false,
    };
  },
  computed: {
    displayedFields() {
      return typeToFields[this.type];
    },
    totalPages() {
      return Math.ceil(this.results.length / this.pageSize);
    },
    paginatedResults() {
      // 1. Create a safe copy of results so we don't mutate the original list
      const sorted = [...this.results];

      // 2. Apply Sorting Logic (Date)
      sorted.sort((a, b) => {
        const dateA = new Date(a.session_date || 0);
        const dateB = new Date(b.session_date || 0);
        
        // Compare dates based on direction
        if (this.sortDir === 'asc') {
          return dateA - dateB;
        } else {
          return dateB - dateA;
        }
      });

      // 3. Apply Pagination Slice
      const start = (this.currentPage - 1) * this.pageSize;
      const end = start + this.pageSize;
      
      return sorted.slice(start, end);
    }
  },
  methods: {
    formatDateShort,
    toggleDateSort() {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
    },
    // --- NEW: Page Navigation ---
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    },
    sortByDate() {
      this.results.sort((a, b) => {
        const dateA = new Date(a.session_date);
        const dateB = new Date(b.session_date);
        return this.sortDir === 'asc' ? dateA - dateB : dateB - dateA;
      });
    },
    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.load();
      }, 500); // Wait 500ms after the last change
    },
    async load() {
      // 1. Helper to safely convert Date Object -> "2025-11-21"
      const formatDate = (date) => {
          if (!date) return null;
          // If it's already a string, return it
          if (typeof date === 'string') return date;
          // If it's a Date object, format it safely
          try {
              // Adjust for timezone offset if necessary, or use simple ISO
              // .toISOString() returns UTC. If you need local time, use this:
              const offset = date.getTimezoneOffset() * 60000;
              const localISOTime = (new Date(date - offset)).toISOString().slice(0, 10);
              return localISOTime;
          } catch (e) { return null; }
      };

      this.loading = true;

      const headers = { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };
      
      // 2. Prepare the URL with base parameters
      let url = `${cfg.API_BASE_URL}/admin/stats/staff-abnormality?type=${this.type}&result_status=${this.result_status}`;

      // 3. Convert dates and append only if they exist
      const fromStr = formatDate(this.from);
      const toStr = formatDate(this.to);

      if (fromStr) url += `&from=${fromStr}`;
      if (toStr) url += `&to=${toStr}`;

      try {
          const res = await fetch(url, { headers });
          
          if (handleUnauthorized(res)) return;
          if (!res.ok) throw new Error('Failed');

          const json = await res.json();
          this.results = json.results || [];
          this.currentPage = 1;
      } catch (e) {
          // Only alert if it's NOT a cancellation error (optional)
          if (e.name !== 'AbortError') {
              console.error(e);
              alert('Failed to load data');
          }
      } finally {
          this.loading = false;
      }
    },

    formatField(col) { return fieldLabels[col] || col; },
    highlightCell(val) {
      if (!val) return '';
      return val.toLowerCase() === 'abnormal' ? 'abnormal' :
             val.toLowerCase() === 'normal' ? 'normal' : '';
    },
    gotoSession(sessionId) {
      this.$router.push(`/staff/session/${sessionId}/investigations`);
    }
  },
  mounted() {
    this.load();
  },
  watch: {
    from() { this.debouncedLoad(); },
    to() { this.debouncedLoad(); },
    type() { this.debouncedLoad(); },
    result_status() { this.debouncedLoad(); }
  }
};
</script>

<style scoped>
/* .filters-bar { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px; align-items:end; } */
.filter-group { display:flex; flex-direction:column; gap:4px; }
.filter-group label { font-size:13px; color:#555; }
.filter-group select, .filter-group input { padding:6px 8px; }

.results-table { width:100%; border-collapse:collapse; margin-top:10px; }
.results-table th, .results-table td { border:1px solid #ddd; padding:8px 10px; text-align:left; font-size:13px; }
.results-table th { background:#f7fafc; }
.clickable { cursor:pointer; }
.clickable:hover { background:#f0fff4; }
.abnormal { background:#fee2e2; font-weight:600; }
.normal { background:#ecfdf5; }
</style>