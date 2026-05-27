<template>
  <div class="edit-wrapper">
    <div class="edit-card">
      <div class="card-header">
        <h2>Edit User Info</h2>
        <p class="subtitle">User Account: {{ user.fullname || 'User' }}</p>
      </div>

      <form @submit.prevent="updateUser">
        
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input id="fullname" type="text" v-model="user.fullname" required placeholder="Enter full name" />
          <div v-if="originalUser.fullname && user.fullname !== originalUser.fullname" class="change-hint">
            Current: {{ originalUser.fullname }}
          </div>
        </div>
            
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" type="text" v-model="user.username" required placeholder="e.g. staff123" />
          <div v-if="originalUser.username && user.username !== originalUser.username" class="change-hint">
            Current: {{ originalUser.username }}
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input id="email" :type="'email'" v-model="user.email" required placeholder="user@example.com" />
          <div v-if="originalUser.email && user.email !== originalUser.email" class="change-hint">
            Current: {{ originalUser.email }}
          </div>
        </div>
          
        <div class="form-group">
          <label for="role">Role</label>
          <div class="select-wrapper">
            <select id="role" v-model="user.role" required>
              <option value="admin">Admin</option>
              <option value="doctor">Doctor</option>
              <option value="staff">Staff</option>
              <option value="restricted">Restricted</option>
            </select>
          </div>
          <div v-if="originalUser.role && user.role !== originalUser.role" class="change-hint">
            Current: {{ originalUser.role }}
          </div>
        </div>

        <div class="button-group">
          <button class="btn-secondary" type="button" @click="$router.push('/admin/manage-users')">
            Cancel
          </button>
          <button class="btn-primary" type="submit" :disabled="!isModalChanged">
            Save Changes
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

export default {
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      isAdminSelected: false,
      user: { user_id: '', fullname: '', username: '', email: '', role: '' },
      originalUser: { fullname: '', username: '', email: '', role: '' }
    };
  },
  mounted() {
    const userId = this.$route.params.userId;
    this.fetchUserInfo(userId); 
  },
  computed: {
    isModalChanged() {
      return (
        this.user.fullname !== this.originalUser.fullname ||
        this.user.username !== this.originalUser.username ||
        this.user.email !== this.originalUser.email ||
        this.user.role !== this.originalUser.role
      );
    }
  },
  watch: {
    "user.role"(v) {
      this.isAdminSelected = v === 'admin';
    }
  },
  methods: {
    fetchUserInfo(userId) {
      fetch(`${this.baseUrl}/admin/user/${userId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;
        return res.json();
      })
      .then(data => {
        this.user = data;
        // Copy data to originalUser
        this.originalUser = { ...data };
      })
      .catch(err => console.error('Error fetching user data:', err));
    },
    updateUser() {
      fetch(`${this.baseUrl}/admin/user/${this.user.user_id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(this.user)
      })
      .then(res => res.json())
      .then(() => {
        alert('User info updated successfully');
        this.$router.push('/admin/manage-users'); 
      })
      .catch(err => console.error('Error updating user info:', err));
    }
  }
}
</script>

<style scoped>
/* Main Wrapper: Centers the card vertically and horizontally */
.edit-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 80vh;
  padding-top: 40px;
  /* background-color: #f8f9fa; */
}

/* The Card Itself */
.edit-card {
  background: #ffffff;
  width: 100%;
  max-width: 500px; /* Slightly wider for better breathing room */
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); /* Soft, modern shadow */
  border: 1px solid #e9ecef;
}

/* Header Typography */
.card-header {
  margin-bottom: 30px;
  text-align: center;
}

.card-header h2 {
  margin: 0;
  color: #333;
  font-size: 1.8rem;
}

.subtitle {
  margin-top: 5px;
  color: #6c757d;
  font-size: 0.95rem;
}

/* Form Groups */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #343a40;
  font-size: 0.95rem;
}

/* Inputs & Selects */
input[type="text"],
input[type="email"],
select {
  width: 100%;
  padding: 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 1rem;
  color: #495057;
  transition: border-color 0.2s, box-shadow 0.2s;
  background-color: #fff;
  box-sizing: border-box; /* Ensures padding doesn't break width */
}

input:focus,
select:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Hint Text for Changed Values */
.change-hint {
  margin-top: 5px;
  font-size: 0.85rem;
  color: #e67e22; /* Orange for "warning/change" visibility */
  font-style: italic;
}

/* Button Group */
.button-group {
  display: flex;
  justify-content: flex-end; /* Aligns buttons to the right */
  gap: 15px; /* Space between buttons */
  margin-top: 35px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

/* button {
  padding: 10px 24px;
  font-size: 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
  border: none;
} */

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover {
  background-color: #0056b3;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  background-color: #a0cfff;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background-color: #e9ecef;
  color: #495057;
}

.btn-secondary:hover {
  background-color: #dde2e6;
  color: #212529;
}
</style>