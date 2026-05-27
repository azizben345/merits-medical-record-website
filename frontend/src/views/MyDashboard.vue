<template>
  <div class="dashboard">
    <header>
      <h1>Welcome, {{ userDisplayName }} ({{ roleLabel }})</h1>

      <div class="toolbar">
        <button @click="logout">Logout</button>
      </div>
    </header>

    <!-- <nav>
      <ul>
        <li v-for="item in menu" :key="item.name">
          <router-link :to="item.route">{{ item.name }}</router-link>
        </li>
      </ul>
    </nav> -->

    <main>
      <!-- info icon -->
      <!-- <div class="info-container">
        <span class="info-icon" @click="showInfo = !this.showInfo">ℹ️</span>

        <div v-if="showInfo" class="info-overlay" @click.self="showInfo = false">
          <div class="info-box">
            <h4 style="color:black">Medical Examination Form</h4>
            <p style="color: black">
              Please answer the questions as they apply to you. If in doubt, leave a blank and ask the nurse or doctor later.
              The completed Medical Report should be sent to the ATB Occupational Health Advisor (OHA).
            </p>
            
            <button style="color: white; background-color: blue;" @click="showInfo = false">Got it</button>
          </div>
        </div>
      </div> -->
      <!-- loads sub-pages based on route -->
      <!-- <router-view />  -->
      <!-- <analytics-all-dashboard v-if="roleLabel === 'Admin'" /> -->
      <analytics-all-overall v-if="roleLabel === 'Admin'" />
      <analytics-overall v-if="roleLabel === 'Staff'" />
      <p v-if="roleLabel === 'Restricted'">You currently do not have permissions to access this dashboard. Please contact the admin/IT to resolve this issue.</p>
    </main>
  </div>
</template>

<script>
// import AnalyticsAllDashboard from './Admin/StatsOverall/AnalyticsAllDashboard.vue';
import AnalyticsAllOverall from './Admin/StatsOverall/AnalyticsAllOverall.vue';
// import AnalyticsDashboard from './Staff/Statistics/AnalyticsDashboard.vue';
import AnalyticsOverall from './Staff/Statistics/AnalyticsOverall.vue';

export default {
  name: 'MyDashboard',
  components: {
    // AnalyticsAllDashboard,
    AnalyticsAllOverall,
    // AnalyticsDashboard,
    AnalyticsOverall
  },

  data() {
    return {
      // Auth/user
      currentUserInfo: null,
      showInfo: false
    };
  },

  computed: {
    jwtToken() {
      return localStorage.getItem('jwt_token');
    },

    userRole() {
      return this.currentUserInfo ? (this.currentUserInfo.role || 'Restricted') : 'Restricted';
    },

    userDisplayName() {
      if (this.currentUserInfo?.fullname) return this.currentUserInfo.fullname;
      if (this.currentUserInfo?.username) return this.currentUserInfo.username;
      return 'Unknown User';
    },

    roleLabel() {
      return {
        doctor: 'Doctor',
        staff: 'Staff',
        admin: 'Admin',
        restricted: 'Restricted',
      }[this.userRole] || 'Restricted';
    },

  },

  created() {
    this.loadUserInfo();
  },

  mounted() {
    if (!this.jwtToken || !this.currentUserInfo) {
      console.warn('Authentication token or user info missing in Dashboard. Redirecting to login.');
      this.logout();
    }
  },

  methods: {
    loadUserInfo() {
      const userInfoString = localStorage.getItem('user_info');
      if (!userInfoString) { this.currentUserInfo = null; return; }
      try {
        this.currentUserInfo = JSON.parse(userInfoString);
      } catch (e) {
        console.error('Error parsing user_info in Dashboard:', e);
        this.currentUserInfo = null;
      }
    },

    logout() {
      localStorage.removeItem('jwt_token');
      localStorage.removeItem('user_info');
      window.dispatchEvent(new Event('user-logout'));
      this.$router.push('/');
    },
  },
};
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
  /* background-color: #f8f9fa;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); */
  /* Light background for header */
  border-bottom: 2px solid #e0e0e0;
  color: #898e93;
}

header h1 {
  margin: 0;
  font-size: 1.8rem;
  color: #007bff;
  font-weight: bold;
}

header button {
  padding: 8px 15px;
  background-color: #dc3545; /* Red for logout button */
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.2s ease;
}

header button:hover {
  background-color: #c82333;
}

nav {
  background-color: #343a40; /* Dark background for navigation */
  padding: 15px 30px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 25px; /* Spacing between menu items */
}

nav li a {
  text-decoration: none;
  color: #ffffff; /* White text for links */
  font-weight: bold;
  font-size: 1rem;
  padding: 5px 0;
  transition: color 0.2s ease, border-bottom 0.2s ease;
}

nav li a:hover {
  color: #007bff; /* Blue on hover */
  border-bottom: 2px solid #007bff;
}

main {
  flex-grow: 1; /* Allows main content to take up available space */
  padding: 30px;
  background-color: #ffffff; 
}
</style>
