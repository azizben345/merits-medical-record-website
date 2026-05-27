<template>
  <div class="lookup-container">
    
    <div v-if="!selectedEmail" class="search-mode">
       <div class="search-card">
         <h3>Select Staff Member</h3>
         <input v-model="searchQuery" placeholder="Search by name or ID..." class="search-input" />
         
         <div v-if="isLoadingList">Loading list...</div>
         
         <ul v-else class="staff-list">
            <li 
              v-for="staff in filteredStaff" 
              :key="staff.staff_email" 
              @click="selectUser(staff.staff_email)"
            >
              <b>{{ staff.staff_name }}</b> <span class="sub">({{ staff.staff_email }})</span>
            </li>
         </ul>

       </div>
    </div>

    <div v-else>
        <div class="action-bar">
            <button @click="selectedEmail = null" class="back-btn">Choose Different Staff</button>
            <button @click="refreshHealthVitals" class="refresh-btn" :disabled="isLoadingData">
                <span v-if="isLoadingData">Refreshing...</span>
                <span v-else>↻ Refresh Data</span>
            </button>
            <span class="meta" style="margin-top: 1em;"><b>Viewing:</b> {{ staffInfo.name }}</span>
        </div>

        <div v-if="isLoadingData" class="loading-box">Fetching Records...</div>
        <div v-else>
            <AnalyticsOverall :history="history" :staff_email="selectedEmail" />
            <HealthVitalsDashboard :history="history" :staffInfo="staffInfo" />
            <AnalyticsCharts2 :staffEmail="selectedEmail" :staff_email=selectedEmail />
        </div>
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import AnalyticsOverall from '@/views/Staff/Statistics/AnalyticsOverall.vue';
import HealthVitalsDashboard from '@/views/Staff/Statistics/HealthVitalsDashboard.vue';
import AnalyticsCharts2 from '@/views/Staff/Statistics/AnalyticsChart2.vue';

export default {
  name: 'AdminPatientLookup',
  components: { 
    HealthVitalsDashboard,
    AnalyticsOverall,
    AnalyticsCharts2,
  },
  data() {
      return {
          baseUrl: cfg.API_BASE_URL,
          // Search State
          searchQuery: '',
          staffList: [],
          isLoadingList: false,
          
          // Dashboard State
          selectedEmail: null,
          history: [],
          staffInfo: {},
          isLoadingData: false
      }
  },
  computed: {
      filteredStaff() {
          if(!this.searchQuery) return this.staffList;
          const q = this.searchQuery.toLowerCase();
          // Filters based on name OR staff ID
          return this.staffList.filter(s => 
            s.staff_name.toLowerCase().includes(q) || 
            s.staff_email.toString().includes(q)
          );
      }
  },
  mounted() {
      this.fetchList();
  },
  methods: {
      refreshHealthVitals() {
          if (!this.selectedEmail) return;

          // 1. Refresh the Dashboard Data (Parent)
          this.fetchStats(this.selectedEmail);

          // 2. Refresh the Charts (Child)
          if (this.$refs.charts && typeof this.$refs.charts.fetchData === 'function') {
              this.$refs.charts.fetchData();
          }
      },
      fetchList() {
          this.isLoadingList = true;
          const token = localStorage.getItem('jwt_token');
          fetch(`${this.baseUrl}/admin/staff-list`, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(r => r.json())
            .then(d => this.staffList = d)
            .finally(() => this.isLoadingList = false);
      },
      selectUser(email) {
          this.selectedEmail = email;
          this.fetchStats(email);
      },
      fetchStats(email) {
          this.isLoadingData = true;
          // URL Safe encoding (The XYZ Hack)
          const safeEmail = email.replace(/\./g, 'XYZ').replace(/\+/g, 'UVW'); 
          const token = localStorage.getItem('jwt_token');
          
          fetch(`${this.baseUrl}/staff/stats/bp-glucose/${safeEmail}`, { headers: { 'Authorization': `Bearer ${token}` } })
            .then(r => r.json())
            .then(data => {
                this.history = data.history || [];
                this.staffInfo = data.staff;
            })
            .finally(() => this.isLoadingData = false);
      }
  }
}
</script>

<style scoped>
.search-mode { display: flex; justify-content: center; padding-top: 50px; }
.search-card { background: white; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.search-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; }
.staff-list { list-style: none; padding: 0; max-height: 300px; overflow-y: auto; border: 1px solid #eee; }
.staff-list li { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
.staff-list li:hover { background: #f5f5f5; }
.sub { color: #888; font-size: 0.9em; }
.action-bar { display: flex; gap: 20px ; align-items: flex-start; margin-bottom: 20px; }
.meta { border-left: 2px solid #ddd; padding-left: 20px; color: #555; }
.back-btn { background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
.refresh-btn { background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
.refresh-btn:hover { background: #0056b3; }
.refresh-btn:disabled { background: #ccc; cursor: not-allowed; }
.loading-box { text-align: center; padding: 50px; color: #666; }
</style>