<template>
  <div class="snapshot-section">
    <div class="section-header">
      <div class="header-left">
        <h3>Yearly Abnormality Snapshot</h3>
        <span class="subtext">Unique staff count breakdown by test category ({{ selectedYear }})</span>
      </div>
      <div class="header-right">
        <label>Select Year:</label>
        <select v-model="selectedYear" @change="loadSnapshotData" :disabled="loading">
          <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <div v-if="!loading && groupsData" class="groups-grid">
      
      <BMIScatterCard :points="scatterPoints" />

      <AbnormalityGroupCard 
        title="BMI Categories" 
        :data="groupsData.bmi" 
        color="#f59e0b"
      />

      <AbnormalityGroupCard 
        title="BMI Distribution" 
        :data="groupsData.bmi" 
        color="#f59e0b"
      />

      <AbnormalityGroupCard 
        title="General Investigations" 
        :data="groupsData.general" 
        color="#64748b"
      />

      <AbnormalityGroupCard 
        title="Cholesterol Profile" 
        :data="groupsData.cholesterol" 
        color="#f97316"
      />

      <AbnormalityGroupCard 
        title="Glucose Profile" 
        :data="groupsData.glucose" 
        color="#8b5cf6"
      />

      <AbnormalityGroupCard 
        title="Liver Function" 
        :data="groupsData.liver" 
        color="#ef4444"
      />

      <AbnormalityGroupCard 
        title="Renal Function" 
        :data="groupsData.renal" 
        color="#06b6d4"
      />

    </div>

    <div v-else class="loading-state">
      {{ loading ? 'Loading snapshot data...' : 'No data available for this year.' }}
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import BMIScatterCard from '@/components/BMIScatterCard.vue';
import AbnormalityGroupCard from './AbnormalityGroupCard.vue';

export default {
  name: 'ChartsSnapshotSection',
  components: { 
    BMIScatterCard, 
    AbnormalityGroupCard 
  },
  data() {
    return {
      selectedYear: new Date().getFullYear(),
      availableYears: [],
      groupsData: null,
      scatterPoints: [],
      loading: false
    };
  },
  async mounted() {
    await this.loadYears();
    this.loadSnapshotData();
  },
  methods: {
    async loadYears() {
      try {
        const res = await fetch(`${cfg.API_BASE_URL}/admin/stats/available-years`, {
            headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        const json = await res.json();
        const years = (json.years || []).map(y => y.year);
        if (years.length > 0) {
            this.availableYears = years;
            this.selectedYear = years[0];
        }
      } catch (e) { console.error("Years Error", e); }
    },

    async loadSnapshotData() {
      this.loading = true;
      try {
        const headers = { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` };
        
        // 1. Define URLs
        const groupsUrl = `${cfg.API_BASE_URL}/admin/stats/snapshot-groups?year=${this.selectedYear}`;
        const scatterUrl = `${cfg.API_BASE_URL}/admin/stats/bmi-distribution?year=${this.selectedYear}`;

        // 2. Fetch both in parallel
        const [groupsRes, scatterRes] = await Promise.all([
            fetch(groupsUrl, { headers }),
            fetch(scatterUrl, { headers })
        ]);
        
        // 3. Security Check
        if (handleUnauthorized(groupsRes) || handleUnauthorized(scatterRes)) return;

        // 4. Parse Responses
        const groupsJson = await groupsRes.json();
        const scatterJson = await scatterRes.json();

        // 5. Assign Data
        this.groupsData = groupsJson.groups || null;
        this.scatterPoints = scatterJson.points || []; // <--- Make sure scatterPoints is in your data() return

      } catch (e) {
        console.error("Snapshot Error", e);
      } finally {
        this.loading = false;
      }
    },
  }
};
</script>

<style scoped>
/* Same styles as before */
.snapshot-section { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
.section-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
h3 { margin: 0; color: #1e293b; font-size: 18px; }
.subtext { font-size: 13px; color: #64748b; }

.groups-grid {
  display: grid;
  /* Auto-fit so it looks good on big screens (3 cols) or small (1 col) */
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
  gap: 24px;
}

.loading-state { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }
select { padding: 6px 12px; border: 1px solid #cbd5e0; border-radius: 6px; }
</style>