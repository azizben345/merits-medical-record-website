<template>
  <aside :class="['sidebar', { 'is-expanded': isExpanded }]">
    
    <div class="sidebar-header">
      <div v-if="isExpanded" class="logo-text">MERITS</div>
      <button class="toggle-btn" @click="toggleMenu">
        <span>{{ isExpanded ? '◀' : '▶' }}</span>
      </button>
    </div>

    <nav class="menu">
      <ul>
        <li v-for="(item, index) in menu" :key="index">
          <router-link :to="item.route" class="menu-item" :title="item.label">
            <span class="icon">{{ item.icon }}</span>
            <span class="text" v-if="isExpanded">{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <button class="menu-item logout-btn" @click="handleLogout" title="Logout">
        <span class="icon">🚪</span>
        <span class="text" v-if="isExpanded">Logout</span>
      </button>
    </div>
  </aside>
</template>

<script>
import api from '@/utils/api'; // Import your API wrapper if needed for logout

export default {
  name: 'SideNavBar',
  data() {
    return {
      isExpanded: true, // Default state
      menuItems: {
        doctor: [
          { label: 'Dashboard', route: '/dashboard', icon: '🏠' },
          { label: 'My Info', route: '/doctor/manage-info', icon: '👨‍⚕️' },
          { label: 'Staff Records', route: '/doctor/non-session-completeness', icon: '📂' },
          { label: 'Sessions', route: '/doctor/manage-sessions', icon: '🩺' },
          { label: 'Analytics', route: '/doctor/analytics-all-page', icon: '📊' }
        ],
        staff: [
          { label: 'Dashboard', route: '/dashboard', icon: '🏠' },
          { label: 'My Info', route: '/staff/non-session', icon: '👤' },
          { label: 'My Sessions', route: '/staff/my-sessions', icon: '📅' },
          { label: 'Analytics', route: '/staff/analytics-page', icon: '📈' }
        ],
        admin: [
          { label: 'Dashboard', route: '/dashboard', icon: '🏠' },
          { label: 'Users', route: '/admin/manage-users', icon: '👥' },
          { label: 'Staff Info', route: '/admin/non-session-completeness', icon: '🗂️' },
          { label: 'Sessions', route: '/admin/manage-sessions', icon: '🕒' },
          { label: 'Analytics', route: '/admin/analytics-all-page', icon: '📉' }
        ],
        restricted: [
          { label: 'Dashboard', route: '/dashboard', icon: '🏠' },
        ]
      }
    };
  },
  computed: {
    menu() {
      const user_raw = localStorage.getItem('user_info');
      if (!user_raw) return [];
      try {
        const role = JSON.parse(user_raw)?.role;
        return this.menuItems[role] || [];
      } catch (_) {
        return [];
      }
    }
  },
  methods: {
    toggleMenu() {
      this.isExpanded = !this.isExpanded;
      // Emit event so App.vue knows to adjust the margin
      this.$emit('toggle-sidebar', this.isExpanded);
    },
    async handleLogout() {
        // Use your existing logout logic here
        // If using api.js wrapper that handles redirects:
        try {
            await api.post('/auth/logout');
        } catch(e) { console.warn(e); }
        finally {
            localStorage.clear();
            window.dispatchEvent(new Event('user-logout'));
            this.$router.push('/');
        }
    }
  }
}
</script>

<style scoped>
/* VARS */
:root {
  --sidebar-width: 240px;
  --sidebar-width-collapsed: 64px;
  --bg-color: #1e293b; /* Slate 800 */
  --hover-color: #334155; /* Slate 700 */
  --accent-color: #3b82f6; /* Blue 500 */
  --text-color: #f1f5f9;
}

/* CONTAINER */
.sidebar {
  display: flex;
  flex-direction: column;
  background-color: #1e293b; 
  color: #f1f5f9;
  height: 100vh;
  position: fixed; /* Fixes it to the left */
  top: 0;
  left: 0;
  z-index: 99;
  transition: width 0.3s ease; /* Smooth toggle animation */
  width: 64px; /* Default collapsed width */
  box-shadow: 4px 0 10px rgba(0,0,0,0.1);
  overflow: hidden; /* Hides text when collapsed */
}

.sidebar.is-expanded {
  width: 240px; /* Expanded width */
}

/* HEADER */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  height: 60px;
  border-bottom: 1px solid #334155;
}

.logo-text {
  font-weight: bold;
  font-size: 1.2rem;
  color: #60a5fa;
  white-space: nowrap;
}

.toggle-btn {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0;
  margin: 0 auto; /* Center button when collapsed */
}
.is-expanded .toggle-btn { margin: 0; } /* Reset margin when expanded */

/* MENU LIST */
.menu { flex: 1; padding-top: 1rem; }
ul { list-style: none; padding: 0; margin: 0; }

.menu-item {
  display: flex;
  align-items: center;
  padding: 12px 1rem;
  color: #cbd5e1;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
  white-space: nowrap; /* Prevents text wrap during animation */
  cursor: pointer;
  background: none;
  border: none;
  width: 100%;
  font-size: 1rem;
}

.menu-item:hover, .menu-item.router-link-active {
  background-color: #334155;
  color: #fff;
  border-right: 4px solid #3b82f6; /* Active indicator */
}

.logout-btn:hover {
    background-color: #7f1d1d; /* Red hover for logout */
    border-right-color: #ef4444;
}

/* ICONS & TEXT */
.icon {
  font-size: 1.2rem;
  min-width: 32px; /* Ensures icon alignment stays consistent */
  display: flex;
  justify-content: center;
}

.text {
  margin-left: 12px;
  opacity: 0;
  animation: fadeIn 0.3s forwards 0.1s; /* Tiny delay so text doesn't bleed out */
}

@keyframes fadeIn {
  to { opacity: 1; }
}

/* FOOTER */
.sidebar-footer {
  border-top: 1px solid #334155;
  padding: 0.5rem 0;
}
</style>