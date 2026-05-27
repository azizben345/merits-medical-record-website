<!-- src/views/Statistics.vue -->
<template>
  <div class="stats">
    <h2>Statistics</h2>
    <button @click="$router.push('/dashboard')" style="margin-bottom: 18px;">Back to Dashboard</button>

    <div class="switcher">
      <button :class="{active: mode==='overall'}" @click="mode='overall'">Overall</button>
      <!-- <button :class="{active: mode==='dashboard'}" @click="mode='dashboard'">Overview</button> -->
      <button :class="{active: mode==='charts'}" @click="mode='charts'">Charts</button>
      <button :class="{active: mode==='charts2'}" @click="mode='charts2'">Charts-2</button>
      <button :class="{active: mode==='staffStatsView'}" @click="mode='staffStatsView'">Staff Stats View</button>
      <span style="margin-top: 1em;">// Testing -> //</span>
      <button :class="{active: mode==='chartsBP'}" @click="mode='chartsBP'">Testing4Charts-BP</button>
      <button :class="{active: mode==='staffHealthStats'}" @click="mode='staffHealthStats'">Old Health Stats</button>
    </div>

    <!-- <AnalyticsDashboard v-if="mode === 'dashboard'"/> -->
    <keep-alive>
      <AnalyticsOverall v-if="mode === 'overall'"/>
      <AnalyticsCharts v-else-if="mode === 'charts'"/>
      <AnalyticsChartsBP v-else-if="mode === 'chartsBP'"/>
      <StaffHealthStats v-else-if="mode === 'staffHealthStats'"/>
      <StaffStatsView v-else-if="mode === 'staffStatsView'"/>
      <AnalyticsCharts2 v-else/>
    </keep-alive>
  </div>
</template>

<script>
// import AnalyticsDashboard from '@/views/Staff/Statistics/AnalyticsDashboard.vue';
import AnalyticsOverall from './AnalyticsOverall.vue';
import AnalyticsCharts from '@/views/Staff/Statistics/AnalyticsChart.vue';
import AnalyticsCharts2 from '@/views/Staff/Statistics/AnalyticsChart2.vue';
import AnalyticsChartsBP from './AnalyticsChartsBP.vue';
import StaffHealthStats from './StaffHealthStats.vue';
// staff view wrapper
import StaffStatsView from './StaffStatsView.vue';

export default {
  name: 'AnalyticsPage',
  data: () => ({ mode: 'overall' }),
  components: { 
    // AnalyticsDashboard,
    AnalyticsOverall,
    AnalyticsCharts, 
    AnalyticsCharts2,
    AnalyticsChartsBP,
    StaffHealthStats,
    StaffStatsView,
  },
  // computed: {
  //   currentComp() {
  //     return this.mode === 'dashboard' ? AnalyticsDashboard : AnalyticsCharts;
  //   }
  // }
}
</script>

<style scoped>
</style>
