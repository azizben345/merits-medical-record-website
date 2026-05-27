<template>
  <div class="manage-users">
    <h2>Manage Users</h2>

    <button @click="$router.push('/dashboard')" class="ghost">Back to Dashboard</button>
    <button @click="registerUser" style="color: white; background: blue; margin-bottom: 0;">Register New User</button>

    <div class="topbar">
      <input
          v-model.trim="search"
          type="search"
          class="search"
          placeholder="Search staff (name or email)…" />
      
      <div class="filters" style="margin-top: 0;">
        <select v-model="roleFilter" id="roleFilter" style="width: fit-content;">
          <option value="">All Roles</option>
          <option value="doctor">Doctor</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
          <option value="restricted">Restricted</option>
        </select>

        <select v-model="statusFilter" id="statusFilter" style="width: fit-content;">
          <option value="active">Active Users</option>
          <option value="deleted">Deleted Users</option>
          <option value="status-all">All Users</option>
        </select>

        <div class="info-container">
          <span class="info-icon" style="align-items: right; margin: 0;" @click="showInfo = !this.showInfo">ℹ️</span>

          <div v-if="showInfo" class="info-overlay" @click.self="showInfo = false">
            <div class="info-box">
              <h4 style="color:black">Account Status</h4>
              <p style="color: black">
                Deleted users are made to be easily restored (soft delete).<br>
                This is so that <b>'deleted' users'</b> staff or doctor <b>data is not lost</b>, but they are still considered 'deleted'.
              </p>
              
              <button style="color: white; background-color: blue;" @click="showInfo = false">Got it</button>
            </div>
          </div>
        </div>
        
        <div class="pager" v-if="allFilteredUsers.length > 0" style="margin-bottom: 5px;">
          <div class="page-controls">
            <button class="page-btn" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">‹ Prev</button>
            <span class="page-number" style="margin-right: 8px;">Page {{ currentPage }} / {{ totalPages }}</span>
            <button class="page-btn" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">Next ›</button>
          </div>
        </div>

      </div>
    </div>

    <div class="page-info">
       Showing 
      {{ (currentPage - 1) * pageSize + 1 }} 
      - 
      {{ Math.min(currentPage * pageSize, totalFilteredCount) }} 
      of 
      {{ totalFilteredCount }} 
      users
    </div>

    <div class="info-container">
      <span class="info-icon" style="align-items: right; margin-top: 0;" @click="showInfo2 = !this.showInfo">ℹ️</span>

      <div v-if="showInfo2" class="info-overlay" @click.self="showInfo2 = false">
        <div class="info-box">
          <h4 style="color:black">Admin Email Unique Labelling</h4>
          <p style="color: black">
            Admin account's email is <b>recommeded to apply labelling</b> to their email address <b>if the email is already used</b> for staff acount.<br><br>
            This is for identifier in the database, as <b>identical email cannot be used for multiple accounts.</b> <br><br>
            <i>e.g. <b>user01+admin@example.com</b> (where staff account is 'user01@example.com')</i>
          </p>
          
          <button style="color: white; background-color: blue;" @click="showInfo2 = false">Got it</button>
        </div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          
          
          <th v-if="shouldShowDeletedColumn">Deleted At</th>
          
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="filteredUsers.length === 0">
          <td :colspan="shouldShowDeletedColumn ? 6 : 5" class="empty" v-if="isLoading">Loading...</td>
          <td :colspan="shouldShowDeletedColumn ? 6 : 5" class="empty" v-else>No data found matching your filters.</td>
        </tr>
        
        <template v-else>
          <tr v-for="user in filteredUsers" :key="user.user_id">
            <td>{{ user.user_id }}</td>
            <td>{{ user.fullname || '-' }}</td>
            <td>{{ user.username || '-' }}</td>
            <td>
              {{ user.email }}
              <span v-if="user.email === currentAdmin" style="color: grey"><i>(self)</i></span>
            </td>
            <td>{{ formatRole(user.role) }}</td>

            <td v-if="shouldShowDeletedColumn">
              <span v-if="user.deleted_at" style="color: red; font-weight: bold;">
                {{ formatDateShort(user.deleted_at) }}
              </span>
              <span v-else style="color: green;">Active</span>
            </td>

            <td>
              <button @click="editUser(user)">Edit</button>
              <button @click="$router.push(`/admin/view-user/${user.email}`)">View</button>
              <button @click="$router.push(`/reset-password/${user.user_id}/${user.email}`)">Reset Password</button>
              
              <button 
                :class="user.deleted_at ? 'restore-btn' : 'danger'" 
                @click="deleteRestoreUser(user)"
              >
                {{ user.deleted_at ? 'Restore' : 'Delete' }}
              </button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { formatDateShort } from '@/shared/dateFormat';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

export default {
  name: 'ManageUsers',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      showInfo: false,
      showInfo2: false,
      isLoading: false,
      search: '',
      roleFilter: '',
      statusFilter: 'active', // Default to Active
      currentPage: 1,
      pageSize: 10,
      users: [] 
    };
  },
  computed: {
    // 2. MAIN FILTERING LOGIC (All logic in one place)
    allFilteredUsers() {
      return this.users.filter(u => {
        // A. Search Logic
        const searchMatch = this.search
          ? (u.fullname || '').toLowerCase().includes(this.search.toLowerCase()) ||
            (u.email || '').toLowerCase().includes(this.search.toLowerCase())
          : true;

        // B. Role Logic
        const roleMatch = this.roleFilter
          ? u.role === this.roleFilter
          : true;

        // C. Status Logic (Fixed)
        let statusMatch = true;
        if (this.statusFilter === 'active') {
          statusMatch = u.deleted_at === null; // Only NULL
        } else if (this.statusFilter === 'deleted') {
          statusMatch = u.deleted_at !== null; // Only NOT NULL
        } 
        // if 'status-all', we simply keep statusMatch as true

        return searchMatch && roleMatch && statusMatch;
      });
    },

    // 3. PAGINATION SLICE (Relies on result above)
    filteredUsers() {
      const startIndex = (this.currentPage - 1) * this.pageSize;
      const endIndex = startIndex + this.pageSize;
      return this.allFilteredUsers.slice(startIndex, endIndex);
    },

    // 4. DYNAMIC COLUMN LOGIC
    shouldShowDeletedColumn() {
      // Logic: If ANY user in the CURRENT VISIBLE LIST has a deleted_at date, show the column.
      // If we are filtering by 'Active', this automatically returns false (hiding the column).
      return this.filteredUsers.some(u => u.deleted_at !== null);
    },

    totalFilteredCount() {
      return this.allFilteredUsers.length;
    },

    totalPages() {
      return Math.ceil(this.totalFilteredCount / this.pageSize) || 1;
    },

    currentAdmin() {
      const info = localStorage.getItem('user_info');
      return info ? JSON.parse(info).email : '';
    }
  },
  watch: {
    // Reset page if ANY filter changes
    roleFilter() { this.currentPage = 1; },
    statusFilter() { this.currentPage = 1; },
    search() { this.currentPage = 1; }
  },
  methods: {
    formatDateShort,
    registerUser() {
      this.$router.push('/admin/register-user');
    },
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    },
    formatRole(role) {
      if (!role) return 'Unknown';
      const map = {
        admin: 'Admin',
        doctor: 'Doctor',
        staff: 'Staff'
      };
      return map[role] || role.charAt(0).toUpperCase() + role.slice(1);
    },
    editUser(user) {
      this.$router.push(`/admin/edit-user/${user.user_id}`);
    },
    
    // handle both Delete and Restore actions
    deleteRestoreUser(user) {
      // 1. Determine State
      const isRestoring = user.deleted_at !== null;
      const actionText = isRestoring ? 'Restore' : 'Delete';

      // 2. Define Routes & Methods based on state
      let url = '';
      let method = '';

      if (isRestoring) {
          url = `${this.baseUrl}/admin/user/restore/${user.user_id}`;
          method = 'PUT';
      } else {
          url = `${this.baseUrl}/admin/user/soft-delete/${user.user_id}`;
          method = 'PUT';
      }

      // 3. Confirm Action
      const actionTextLower = actionText.toLowerCase();
      if (confirm(`Are you sure you want to ${actionTextLower} ${user.fullname}?`)) {
          const token = localStorage.getItem('jwt_token');

          // 4. Fire Request
          fetch(url, {
              method: method,
              headers: {
                  'Authorization': `Bearer ${token}`,
                  'Content-Type': 'application/json'
              }
          })
          .then(res => {
              if (!res.ok) throw new Error(`Failed to ${actionText.toLowerCase()} user`);
              
              // Success! Reload to see changes.
              window.location.reload();
          })
          .catch(err => {
              console.error(err);
              alert(`Error: ${err.message}`);
          });
      }
  },
  },
  mounted() {
    this.isLoading = true;
    const token = localStorage.getItem('jwt_token');
    
    fetch(`${this.baseUrl}/admin/users`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    .then(res => {
      if (handleUnauthorized(res)) return;
      return res.json();
    })
    .then(data => {
      this.users = data;
    })
    .catch(err => {
      console.error('Failed to fetch users:', err);
    })
    .finally(() => {
      this.isLoading = false;
    });
  }
};
</script>

<style>
.manage-users {
  padding: 24px;
}

h2 {
  font-size: 22px;
  margin-bottom: 16px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

th,
td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

th {
  background-color: #f7fafc;
  color: #2d3748;
  font-weight: 600;
}

/* .actions {
  text-align: center;
  display: flex;
  margin-bottom: 0;
} */

button {
  padding: 6px 12px;
  font-size: 13px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #edf2f7;
  color: #2d3748;
  transition: background 0.2s ease;
}

button:hover {
  background: #e2e8f0;
}

button.danger {
  background: #feb2b2;
  color: #742a2a;
}

button.danger:hover {
  background: #fc8181;
}

.restore-btn {
  background-color: #28a745; /* Green */
  color: white;
}
.restore-btn:hover {
  background-color: #218838;
}
</style>
