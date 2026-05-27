<template>
  <div id="app">

    <div v-if="$route.meta.hideSideNavbar" class="auth-layout">
        <header>
            <div class="header-left">
                <img src="@/assets/vtti-logo.png" alt="VTTI Logo" class="logo" />
                <div class="app-title-container">
                    <h1>MERITS</h1>
                    <span class="app-tagline">Medical Examination Record & Information Tracking System</span>
                </div>
            </div>
        </header>
        
        <div class="auth-content">
            <router-view />
        </div>
    </div>

    <div v-else class="dashboard-layout">
      
      <SideNavBar 
        v-if="isLoggedIn" 
        @toggle-sidebar="updateMargin" 
      />

      <div class="content-pusher" :style="{ marginLeft: sidebarWidth }">
        
        <header>
          <div class="header-left">
            <img src="@/assets/vtti-logo.png" alt="VTTI Logo" class="logo" />
            <div class="app-title-container">
              <h1>MERITS</h1>
              <span class="app-tagline">Medical Examination Record & Information Tracking System</span>
            </div>
          </div>
          
          <div class="header-right">
            <span class="profile-sidebar">
               <ProfileSidebar v-if="currentUser" :user="currentUser" />
            </span>
             <button v-if="!showMobileWarning && !currentUser" class="btn primary" @click="$router.push('/profile')">👤</button>
          </div>
        </header>

        <main class="page-container">
          <router-view /> 
          <ScrollToTop />
        </main>

      </div>
    </div>

    <div class="mobile-warning" v-show="showMobileWarning">
      <div class="warning-card">
          <p class="warning-content">
            <strong>Optimized for Desktop</strong>
            <br>We apologize for the inconvenience! This website is currently optimized 
            for desktop or tablet (landscape) viewing.
            <br>Please switch to <b>desktop mode</b> or use a <b>larger screen</b>.
          </p>
          <button @click="proceedAnyway" class="proceed-btn">
            Proceed Anyway
          </button>
      </div>
    </div>

  </div>
</template>

<script>
import api from '@/utils/api';
import SideNavBar from './components/SideNavBar.vue';
import ProfileSidebar from './components/ProfileSidebar.vue';
import ScrollToTop from './components/ScrollToTop.vue';

export default {
  name: 'App',
  components: { 
    ProfileSidebar, 
    SideNavBar,
    ScrollToTop
  },
  data() {
    return {
      isLoggedIn: !!localStorage.getItem('jwt_token'),
      currentUser: null,
      sidebarExpanded: false, // Default state of sidebar
      
      // Idle Timer State
      idleTimer: null,
      timeoutMinutes: 60,
      
      // Mobile Warning State
      showMobileWarning: window.innerWidth < 768
    };
  },
  computed: {
    // Calculates the margin for the content pusher
    sidebarWidth() {
      if (!this.isLoggedIn) return '0px';
      return this.sidebarExpanded ? '240px' : '64px';
    }
  },
  methods: {
    // --- SIDEBAR LOGIC ---
    updateMargin(isExpanded) {
      this.sidebarExpanded = isExpanded;
    },

    // --- USER LOGIC ---
    loadUser() {
      const userStr = localStorage.getItem('user_info');
      this.currentUser = userStr ? JSON.parse(userStr) : null;
    },
    
    handleLogout() {
      this.currentUser = null;
      this.isLoggedIn = false;
      this.sidebarExpanded = false; // Reset sidebar
    },

    // --- IDLE TIMER LOGIC ---
    startIdleWatcher() {
      const events = ['mousemove', 'keydown', 'click', 'scroll'];
      events.forEach(event => window.addEventListener(event, this.resetTimer));
      this.resetTimer();
    },
    
    resetTimer() {
      if (this.idleTimer) clearTimeout(this.idleTimer);
      this.idleTimer = setTimeout(() => {
        this.autoLogout();
      }, this.timeoutMinutes * 60 * 1000);
    },
    
    async autoLogout() {
      // Remove listeners
      const events = ['mousemove', 'keydown', 'click', 'scroll'];
      events.forEach(event => window.removeEventListener(event, this.resetTimer));

      // Clear Local Storage
      localStorage.removeItem('jwt_token');
      localStorage.removeItem('user_info'); // Changed from user_data to match your loadUser key

      // Call API (Optional)
      try {
        await api.post('/auth/logout'); 
      } catch (e) { /* ignore */ }

      // Update State & Redirect
      this.handleLogout();
      alert("You have been logged out due to inactivity.");
      this.$router.push('/login');
    },

    // --- MOBILE WARNING LOGIC ---
    proceedAnyway() {
      this.showMobileWarning = false;
    }
  },
  created() {
    // Listen for login/logout events from other components
    window.addEventListener('user-login', () => { 
      this.isLoggedIn = true; 
      this.loadUser();
      this.startIdleWatcher();
    });
    window.addEventListener('user-logout', () => { 
      this.isLoggedIn = false; 
      this.handleLogout();
    });
  },
  mounted() {
    this.loadUser();
    
    // Start idle watcher if token exists on mount
    if (localStorage.getItem('jwt_token')) {
      this.startIdleWatcher();
    }

    // Resize listener for mobile warning
    window.addEventListener('resize', () => {
      this.showMobileWarning = window.innerWidth < 768;
    });
  },
  beforeUnmount() {
    // Cleanup listeners
    const events = ['mousemove', 'keydown', 'click', 'scroll'];
    events.forEach(event => window.removeEventListener(event, this.resetTimer));
  }
};
</script>

<style>
/* --- GLOBAL RESETS --- */
body { 
  margin: 0; 
  padding: 0;
  font-family: 'Inter', sans-serif; 
  background-color: #f9fafb; 
}

/* --- LAYOUT 1: AUTH LAYOUT (Login) --- */
.auth-layout {
  min-height: 100vh;
  width: 100vw;
  display: flex;
  flex-direction: column; /* Stack Header and Content vertically */
  
  /* Background Image Settings */
  background-image: url('https://signup.softclinicgenx.com/wp-content/uploads/2022/05/medical-report-with-medical-equipment.jpg');
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

/* Ensure the header inside auth-layout looks right */
.auth-layout header {
  background: rgba(255, 255, 255, 0.95); /* Slight transparency for style */
  padding: 12px 32px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0; /* Prevents header from shrinking */
}

/* The container for the actual Login Form */
.auth-content {
  flex: 1; /* Fills remaining height */
  display: flex;
  justify-content: center; /* Center horizontally */
  align-items: center;     /* Center vertically */
  padding: 20px;
}

/* --- LAYOUT 2: DASHBOARD LAYOUT --- */
.dashboard-layout {
  min-height: 100vh;
  position: relative;
  display: flex; /* Ensures sidebar and pusher are side-by-side if needed, though sidebar is fixed */
}

/* The wrapper that gets pushed */
.content-pusher {
  width: 100%;
  transition: margin-left 0.3s ease;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header Styling */
header {
  background: #ffffff;
  padding: 12px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  height: 70px; /* Fixed height helps layout stability */
  box-sizing: border-box;
}

.header-left { display: flex; align-items: center; gap: 12px; }
.logo { height: 2.5em; width: auto; }
.app-title-container { display: flex; flex-direction: column; line-height: 1.2; }
header h1 { font-size: 24px; font-weight: 800; color: #2f855a; margin: 0; }
.app-tagline { font-size: 11px; color: #718096; }
.header-right { display: flex; align-items: center; gap: 16px; }

/* Page Container (The White Card) */
.page-container {
  flex: 1;
  width: 95%; /* Responsive width */
  max-width: 1600px;
  margin: 2rem auto;
  padding: 2rem 3rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  box-sizing: border-box;
  position: relative;
}

/* Mobile Warning Overlay */
.mobile-warning {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.8);
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
}
.warning-card {
  background: white;
  padding: 30px;
  border-radius: 8px;
  text-align: center;
  max-width: 80%;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}
.proceed-btn {
  border: 1px solid #2b6cb0; 
  background: #2b6cb0; 
  color: white; 
  padding: 10px 20px; 
  border-radius: 6px; 
  cursor: pointer;
  margin-top: 15px;
  font-weight: bold;
}
.proceed-btn:hover {
  background: #2c5282;
}
</style>