// components/NavBar.vue
<template>
  <!-- <nav>
    <ul>
      <li v-for="item in menus" :key="item">
        <router-link :to="`/${item.toLowerCase().replace(/\s+/g, '-')}`">{{ item }}</router-link>
      </li>
    </ul>
  </nav> -->
  <nav>
      <ul>
        <li v-for="item in menu" :key="item.name">
          <router-link :to="item.route">{{ item.name }}</router-link>
        </li>
      </ul>
    </nav>
</template>

<script>
export default {
  name: 'NavBar',

  data() {
    return {
      // will store the parsed user_info object
      currentUserInfo: null, // will be populated in created hook
      menuItems: {
        doctor: [
          { name: '🏠︎', route: '/dashboard' },
          { name: 'Manage Doctor Info', route: '/doctor/manage-info' },
          { name: 'Manage Staff Info', route: '/doctor/non-session-completeness' },
          { name: 'Manage Sessions & Reports', route: '/doctor/manage-sessions' },
          { name: 'Analytics', route: '/doctor/analytics-all-page' }
        ],
        staff: [
          { name: '🏠︎', route: '/dashboard' },
          { name: 'My Staff Info', route: '/staff/non-session' },
          // { name: 'Manage Staff Info', route: '/staff/manage-info' },
          // { name: 'Manage Medical Record', route: '/staff/manage-record' },
          // { name: 'Manage Occupational History', route: '/staff/manage-occupation-history' },
          // { name: 'Manage Family History', route: '/staff/manage-family-history' },
          // { name: 'Manage Medical History', route: '/staff/manage-medical-history' },
          // { name: 'Mangage Lifestyle', route: '/staff/manage-lifestyle' },
          // { name: 'Manage Physical Exams', route: '/staff/manage-physical-exams' },
          // { name: 'Manage Investigations', route: '/staff/manage-investigations' },
          { name: 'My Sessions Info', route: '/staff/my-sessions' },
          { name: 'Analytics', route: '/staff/analytics-page' }
        ],
        admin: [
          { name: '🏠︎', route: '/dashboard' },
          { name: 'Manage Users', route: '/admin/manage-users' },
          { name: 'Manage Staff Info', route: '/admin/non-session-completeness' },
          { name: 'Manage Sessions', route: '/admin/manage-sessions' },
          { name: 'Analytics', route: '/admin/analytics-all-page' }
        ],
        restricted: [
          { name: '🏠︎', route: '/dashboard' },
        ]
      }
    };
  },

  computed: {
    menu() {
      const user_raw = localStorage.getItem('user_info');
      if (!user_raw) return []; // guard when logged out
      
      try {
        const role = JSON.parse(user_raw)?.role;
        return this.menuItems[role] || [];
      } catch (_) {
        return [];
      }
    }
  }

}
</script>

<style scoped>
nav {
  background-color: #343a40;
  padding: 15px 30px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 25px; 
}

nav li a {
  text-decoration: none;
  color: #ffffff; 
  font-weight: bold;
  font-size: 1rem;
  padding: 5px 0;
  transition: color 0.2s ease, border-bottom 0.2s ease;
}

nav li a:hover {
  color: #007bff; 
  /* border-bottom: 2px solid #007bff; */
  background-color: transparent;
}
</style>