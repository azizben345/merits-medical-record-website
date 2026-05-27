<template>
  <div class="session-wrap">
    <div class="head">
      <div>
        <div class="title">Session {{ sessionId }}</div>
        <div class="muted">
          {{ staffName || '(no name)' }} — {{ staffEmail }}
        </div>
      </div>
      <div style="display: flex; justify-content: flex-end;">
        <button class="ghost" style="margin-left: auto;" 
          @click="goSessions(staffEmail)">Back to Sessions View</button>
        <button class="ghost" style="margin-left: 8px;" 
          @click="$router.push('/dashboard')">Back to Dashboard</button>
      </div>
    </div>

    <!-- sticky sub-nav -->
    <nav class="subnav">
      <RouterLink :to="sub('medical-history')" exact-active-class="active">Medical History</RouterLink>
      <RouterLink :to="sub('lifestyle')" exact-active-class="active">Lifestyle</RouterLink>
      <RouterLink :to="sub('physical')" exact-active-class="active">Physical Exams</RouterLink>
      <RouterLink :to="sub('investigations')" exact-active-class="active">Investigations</RouterLink>
      <RouterLink :to="sub('ftw-certificate')" exact-active-class="active">Fitness Certificate</RouterLink>
    </nav>

    <!-- pages render here -->
    <router-view :key="$route.fullPath" />
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { getSessionHeader, setSessionHeader } from '@/shared/sessionHeaderCache';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
// import config from '@/';
export default {
  provide() {
    return { 
      refreshSessionHeader: () => this.refreshHeader(),
      currentSessionHeader: () => this.sessionHeader, // read-only access 
    }; 
  },
  data() { 
    return {
      baseUrl: cfg.API_BASE_URL,
      staffEmail: '',
      staffName: '',
      sessionHeader: null,
      // route: this.$route,
    }; 
  },
  name: 'ViewSession',
  computed: {
    sessionId()  { return this.$route.params.sessionId; },
    userRole() { 
      const userInfoString = localStorage.getItem('user_info');
      if (userInfoString) {
        try {
          const userInfo = JSON.parse(userInfoString);
          return userInfo.role || 'staff'; // Default to staff if missing
        } catch (e) {
          console.error('Error parsing user_info:', e);
          return 'staff';
        }
      }
      return 'staff';
    },
    // staffEmail() { return this.$route.query.staff || ''; },
    // staffName()  { return this.$route.query.name || ''; },
  },
  async mounted() {
    this.getStaffInfo();
    this.sessionHeader = await getSessionHeader(this.sessionId);
    // console.log('route: ', this.route);
  },
  methods: {
    async getStaffInfo() {
      const res = await fetch(`${this.baseUrl}/staff-session/${this.sessionId}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`
        }
      });
      const data = await res.json();
      if (handleUnauthorized(res)) return;
      this.staffEmail = data.staff_email;
      this.staffName = data.staff_name;
    },
    async refreshHeader() {
      const id = this.$route.params.sessionId;
      this.sessionHeader = await getSessionHeader(id);
      setSessionHeader(id, this.sessionHeader);
    },
    goSessions(staffEmail) {
      if (!staffEmail) return;
      const q = encodeURIComponent(staffEmail);
      if (this.userRole === 'admin') {
        this.$router.push(`/doctor/manage-sessions?staff=${q}`);
      } else if (this.userRole === 'doctor') {
        this.$router.push(`/admin/manage-sessions?staff=${q}`);
      } else {
        this.$router.push(`/staff/my-sessions`);
      }
    },
    sub(child) {
      // keep staff info in the query so children can read it if needed
      return { name: `session-${child}`, params: { sessionId: this.sessionId }, query: this.$route.query };
    }
  }
};
</script>

<style scoped>
.session-wrap { padding: 24px; }
.head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.title { font-size:20px; font-weight:700; }
.muted { color:#6b7280; font-size:13px; }

.subnav {
  position: sticky; top: 56px; z-index: 100;
  display:flex; gap:10px; padding:10px; margin-bottom:16px;
  background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;
}
.subnav a { padding:8px 12px; border-radius:6px; color:#1f2937; text-decoration:none; }
.subnav a.active { background:#e0e7ff; color:#1e3a8a; }
button.ghost { background:transparent; border:1px solid #CBD5E0; padding:6px 10px; border-radius:6px; }
</style>
