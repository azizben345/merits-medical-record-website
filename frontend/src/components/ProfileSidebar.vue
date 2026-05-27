<template>
  <div>
    <!-- Avatar Trigger -->
    <img :src="img" class="avatar" alt="User" @click="openSidebar" />

    <!-- Sidebar Panel -->
    <div class="sidebar" :class="{ open: isOpen }" role="dialog" aria-modal="true" aria-label="User menu">
      <div class="header">
        <span class="close" @click="closeSidebar" aria-label="Close sidebar">✕</span>
      </div>
      <div class="content">
        <img :src="img" class="avatar-large" alt="User avatar" />
        <div class="name">{{ localUser.fullname }}</div>
        <div class="email">{{ localUser.email }}</div>
        <div class="role">({{ formattedRole }})</div>

        <div class="actions">
          <button @click="viewProfile" class="view-profile">👤 View Profile</button>
          <button @click="logout" class="logout">🔓 Logout</button>
        </div>
      </div>
    </div>

    <!-- Clickable overlay (closes on click) -->
    <div v-if="isOpen" class="overlay" @click="closeSidebar"></div>
  </div>
</template>

<script>
export default {
  name: 'ProfileSidebar',
  data() {
    return {
      isOpen: false,
      img: "https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_640.png",
      localUser: { fullname: '', email: '', role: '' }
    };
  },
  mounted() {
    const userInfo = JSON.parse(localStorage.getItem('user_info'));
    if (userInfo) this.localUser = userInfo;

    // close on ESC
    window.addEventListener('keydown', this.onKeydown);

    // close when route changes
    this.unwatch = this.$watch('$route.fullPath', () => {
      if (this.isOpen) this.closeSidebar();
    });
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.onKeydown);
    if (this.unwatch) this.unwatch();
    this.unlockScroll();
  },
  computed: {
    formattedRole() {
      if (!this.localUser.role) return 'Unknown Role';
      const roleMap = { admin: 'Admin', doctor: 'Doctor', staff: 'Staff' };
      return roleMap[this.localUser.role] || (this.localUser.role[0]?.toUpperCase() + this.localUser.role.slice(1));
    }
  },
  methods: {
    onKeydown(e) {
      if (e.key === 'Escape' && this.isOpen) this.closeSidebar();
    },
    lockScroll() {
      // prevent background scroll when open
      document.body.style.overflow = 'hidden';
    },
    unlockScroll() {
      document.body.style.overflow = '';
    },
    openSidebar() {
      this.isOpen = true;
      this.lockScroll();
    },
    closeSidebar() {
      this.isOpen = false;
      this.unlockScroll();
    },
    viewProfile() {
      this.$router.push('/profile');
      this.closeSidebar();
    },
    logout() {
      this.closeSidebar();
      localStorage.removeItem('jwt_token');
      localStorage.removeItem('user_info');
      window.dispatchEvent(new Event('user-logout'));
      this.$router.push('/');
    }
  }
};
</script>

<style scoped>
.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #cbd5e0;
  cursor: pointer;
}

/* Overlay below the sidebar but above page content */
.overlay {
  position: fixed;
  inset: 0; /* top:0; right:0; bottom:0; left:0 */
  background: rgba(0, 0, 0, 0.25);
  z-index: 1090; /* below sidebar (1100) */
}

/* sidebar */
.sidebar {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: clamp(280px, 80vw, 360px); 
  
  background: #fff;
  box-shadow: -4px 0 16px rgba(0, 0, 0, 0.12);
  
  /* CRITICAL: Ensure the default state is hidden */
  transform: translateX(100%);
  transition: transform 0.28s ease;
  z-index: 1100;
  display: flex;
  flex-direction: column;
}

.sidebar.open {
  transform: translateX(0);
}

.header {
  display: flex;
  justify-content: flex-end;
  padding: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.close {
  font-size: 22px;
  color: #718096;
  cursor: pointer;
  line-height: 1;
}

.content {
  padding: 24px;
  text-align: center;
  overflow-y: auto; /* scroll inside if very small height */
  -webkit-overflow-scrolling: touch;
}

.avatar-large {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  margin-bottom: 12px;
}

.name {
  font-weight: 600;
  font-size: 18px;
  margin-bottom: 4px;
}

.email {
  font-size: 14px;
  color: #4a5568;
}

.role {
  font-size: 13px;
  font-style: italic;
  color: #718096;
  margin-bottom: 24px;
}

.actions {
  border-top: 1px solid #e2e8f0;
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

button {
  background: none;
  border: none;
  font-size: 14px;
  color: #2d3748;
  text-align: left;
  cursor: pointer;
}

.view-profile { color: #4a90e2; }
.logout { color: #e53e3e; }

/* optional: tighter header on very small heights */
@media (max-height: 500px) {
  .content { padding: 16px; }
}
</style>
