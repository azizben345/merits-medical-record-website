<template>
  <div class="page-container">
    
    <div class="dashboard-header">
      <div class="header-text">
        <h2>Health Analytics Dashboard</h2>
        <p class="subtitle">Comprehensive overview of Staff Vitals, Lifestyle trends, and Lab Results.</p>
      </div>
      
      <div class="tabs">
        <button 
          :class="['tab-btn', { active: currentTab === 'trends' }]" 
          @click="currentTab = 'trends'"
        >
          📈 Risk Trends (History)
        </button>
        <button 
          :class="['tab-btn', { active: currentTab === 'snapshot' }]" 
          @click="currentTab = 'snapshot'"
        >
          🔍 Yearly Snapshot (Detail)
        </button>
      </div>
    </div>

    <div class="dashboard-content">
      
      <transition name="fade" mode="out-in">
        <div v-if="currentTab === 'trends'" key="trends">
          <ChartsTrendSection /> 
        </div>

        <div v-else-if="currentTab === 'snapshot'" key="snapshot">
          <ChartsSnapshotSection />
        </div>
      </transition>

    </div>

  </div>
</template>

<script>
// Import the sub-components we created
import ChartsTrendSection from './ChartsTrendSection.vue';
import ChartsSnapshotSection from './ChartsSnapshotSection.vue';

export default {
  name: 'AnalyticsAllChartsPage',
  components: {
    ChartsTrendSection,
    ChartsSnapshotSection
  },
  data() {
    return {
      currentTab: 'trends' // Default view
    };
  }
};
</script>

<style scoped>
.page-container { padding: 24px; max-width: 1400px; margin: 0 auto; }

.dashboard-header {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 24px; padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap; gap: 16px;
}

h2 { margin: 0; color: #1e293b; font-size: 24px; }
.subtitle { margin: 4px 0 0 0; color: #64748b; font-size: 14px; }

/* Tab Styling */
.tabs { display: flex; gap: 8px; background: #f1f5f9; padding: 4px; border-radius: 10px; }
.tab-btn {
  padding: 8px 16px;
  border: none;
  background: transparent;
  color: #64748b;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}
.tab-btn:hover { color: #334155; background: #e2e8f0; }
.tab-btn.active {
  background: #fff;
  color: #2563eb;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Simple Fade Transition */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 768px) {
  .dashboard-header { flex-direction: column; align-items: flex-start; }
  .tabs { width: 100%; overflow-x: auto; }
  .tab-btn { flex: 1; white-space: nowrap; }
}
</style>