<template>
  <div class="ns-wrap">
    <div class="head">
      <div>
        <div class="title">Non-Session Details</div>
        <div class="muted">{{ staffEmail || 'unable to fetch email' }}</div>
      </div>
      <button class="ghost" @click="$router.push('/dashboard')">Back to Dashboard</button>
    </div>

    <!-- sticky sub-nav -->
    <nav class="subnav">
      <RouterLink :to="sub('staff-info')" exact-active-class="active">Personal Info</RouterLink>
      <RouterLink :to="sub('occ-history')" exact-active-class="active">Occupational History</RouterLink>
      <RouterLink :to="sub('fam-history')" exact-active-class="active">Family History</RouterLink>
      <!-- <RouterLink :to="sub('fhd')" exact-active-class="active">Family Disease</RouterLink> -->
    </nav>

    <!-- pages render here -->
    <router-view :key="$route.fullPath" />
  </div>
</template>

<script>
import cfg from '@/apiConfig';
export default {
  name: 'ViewNonSession',
  provide() {
    // children can inject these: currentStaffHeader(), refreshStaffHeader()
    return {
      currentStaffHeader: () => this.staffHeader,
      refreshStaffHeader: () => this.refreshHeader(),
    };
  },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      staffEmail: '',
      staffName: '',
      staffHeader: null, // you can put any header fields you want here
      loading: false,
    };
  },
  mounted() {
      this.fetchStaffEmail();
  },
//   watch: {
//     '$route.params.staffEmail': {
//       immediate: true,
//       handler() {
//         this.staffEmail = this.$route.params.staffEmail || '';
//         this.refreshHeader();
//       }
//     }
//   },
  methods: {
    // fetch staffEmail from route (admin, doctor) or localstorage (staff)
    fetchStaffEmail() {
      this.staffEmail = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
    },
    // encode email for use in path
    encodeEmailForPath(email) {
      // matches your existing convention used elsewhere
      return encodeURIComponent((email || '').replace(/\./g, 'XYZ'));
    },
    async refreshHeader() {
      if (!this.staffEmail) return;
      this.loading = true;
      try {
        // reuse the details endpoint you already have:
        // GET /admin/non-session/staff/{emailXYZ}
        const emailXYZ = this.encodeEmailForPath(this.staffEmail);
        const r = await fetch(`${this.baseUrl}/admin/non-session/staff/${emailXYZ}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
        });
        const payload = await r.json();

        // payload shape based on your drawer: { staff, occupational_history, family_history, family_history_disease }
        const s = payload?.staff || {};
        this.staffName = s.staff_name || '';
        // expose anything children might need
        this.staffHeader = {
          staff_email: s.staff_email || this.staffEmail,
          staff_name: s.staff_name || '',
          job_title: s.job_title || '',
          phone_no: s.phone_no || '',
          // add more header fields if useful…
        };
      } catch (e) {
        console.error(e);
        this.staffHeader = { staff_email: this.staffEmail, staff_name: '' };
      } finally {
        this.loading = false;
      }
    },
    sub(child) {
      // keep params; children read staffEmail via $route.params or inject()
      return { name: `ns-${child}`, params: { staffEmail: this.staffEmail } };
    }
  }
};
</script>

<style scoped>
.ns-wrap { padding: 24px; }
.head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.title { font-size:20px; font-weight:700; }
.muted { color:#6b7280; font-size:13px; }
.subnav {
  position: sticky; top: 56px; z-index: 10;
  display:flex; gap:10px; padding:10px; margin-bottom:16px;
  background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;
}
.subnav a { padding:8px 12px; border-radius:6px; color:#1f2937; text-decoration:none; }
.subnav a.active { background:#e0e7ff; color:#1e3a8a; }
</style>
