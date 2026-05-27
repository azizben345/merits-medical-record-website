<template>
  <div class="stats">
    <h2>Statistics — All Staff</h2>

    <div class="switcher">
      <button :class="{active: mode==='overall'}" @click="mode='overall'">Overall (All)</button>
      <button :class="{active: mode==='charts-page'}" @click="mode='charts-page'">Charts (Page)</button>
      <!-- <button :class="{active: mode==='dashboard'}" @click="mode='dashboard'">Overview</button> -->
      <button :class="{active: mode==='charts'}" @click="mode='charts'">Charts (All)</button>     
      <button :class="{active: mode==='individual'}" @click="mode='individual'">Individual Search</button>
      <!-- <button :class="{active: mode==='dashboard-trial'}" @click="mode='dashboard-trial'">Overview (Trial)</button> -->
      <button :class="{active: mode==='charts-trial'}" @click="mode='charts-trial'">Charts (Trial)</button>
    </div>

    <!-- <AnalyticsAllDashboard v-if="mode === 'dashboard'"/> -->
    <keep-alive>
      <AnalyticsAllOverall v-if="mode === 'overall'"/>
      <AnalyticsAllChartsPage v-else-if="mode === 'charts-page'"/>
      <AnalyticsAllCharts v-else-if="mode === 'charts'"/>    
      <AdminPatientLookup v-else-if="mode === 'individual'"/>
      <AnalyticsAllDashboardTrial v-else-if="mode === 'dashboard-trial'"/>
      <AnalyticsAllChartsTrial v-else-if="mode === 'charts-trial'"/>
    </keep-alive>
  </div>
</template>

<script>
import AdminPatientLookup from './AdminPatientLookup.vue';
import AnalyticsAllCharts from './AnalyticsAllCharts.vue';
// import AnalyticsAllDashboard from './AnalyticsAllDashboard.vue';
import AnalyticsAllOverall from './AnalyticsAllOverall.vue';
import AnalyticsAllDashboardTrial from './AnalyticsAllDashboardTrial.vue';
import AnalyticsAllChartsTrial from './AnalyticsAllChartsTrial.vue';
// import AnalyticsAllCharts2 from './AnalyticsAllCharts2.vue';
import AnalyticsAllChartsPage from './ChartsOverall/AnalyticsAllChartsPage.vue';

export default {
  name: 'AnalyticsAllPage',
  data: () => ({ mode: 'overall' }),
  components: {
    AdminPatientLookup,
    AnalyticsAllCharts,
    // AnalyticsAllDashboard,
    AnalyticsAllOverall,
    // AnalyticsAllCharts2,
    AnalyticsAllDashboardTrial,
    AnalyticsAllChartsTrial,
    AnalyticsAllChartsPage
  }
}
</script>

<style scoped>
</style>
