<template>
  <div>
     <div v-if="isLoading" class="loading-state">Loading your health records...</div>
     
     <div v-else-if="error" class="error-msg">{{ error }}</div>
     
     <HealthVitalsDashboard v-else :history="history" :staffInfo="staffInfo" />
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import HealthVitalsDashboard from './HealthVitalsDashboard.vue';

export default {
  components: { HealthVitalsDashboard },
  data() {
      return {
          baseUrl: cfg.API_BASE_URL,
          isLoading: true,
          history: [],
          staffInfo: {},
          error: ''
      }
  },
  mounted() {
      this.fetchMyData();
  },
  methods: {
      fetchMyData() {
          const token = localStorage.getItem('jwt_token');
          const userEmail = JSON.parse(localStorage.getItem('user_info')).email;
          
          if (!userEmail) {
              this.error = "User email not found in session.";
              this.isLoading = false;
              return;
          }

          const safeEmail = userEmail.replace(/\./g, 'XYZ').replace(/\+/g, 'UVW');
          
          fetch(`${this.baseUrl}/staff/stats/bp-glucose/${safeEmail}`, { 
              headers: { 'Authorization': `Bearer ${token}` } 
          })
          .then(res => {
              if(!res.ok) throw new Error("Failed to load records");
              return res.json();
          })
          .then(data => {
              this.history = data.history || [];
              this.staffInfo = data.staff;
          })
          .catch(e => this.error = e.message)
          .finally(() => this.isLoading = false);
      }
  }
}
</script>

<style scoped>
.loading-state { text-align: center; padding: 50px; font-style: italic; color: #666; }
.error-msg { text-align: center; padding: 20px; color: red; background: #fee; border-radius: 8px; }
</style>