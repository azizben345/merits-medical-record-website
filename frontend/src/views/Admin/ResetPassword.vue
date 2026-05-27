<template>
  <div class="reset-wrapper">
    <div class="reset-card">
      
      <div class="card-header">
        <h2>{{ title }}</h2>
        <p v-if="isAdmin" class="subtitle">Admin Action by: {{ adminName }}</p>
      </div>

      <form @submit.prevent="resetPassword">
        
        <div class="info-section">
          <div class="info-group">
            <label>User ID</label>
            <div class="static-value">{{ userId }}</div>
          </div>
          <div class="info-group">
            <label>Email Account</label>
            <div class="static-value">{{ userEmail }}</div>
          </div>
        </div>

        <div class="form-group">
          <label for="newPassword">New Password</label>
          <input 
            id="newPassword" 
            type="text" 
            v-model="newPassword" 
            required 
            placeholder="Enter new password"
            autocomplete="off"
            :class="{ 'input-error': newPassword && !isPasswordValid }"
          />
          
          <div class="validation-box">
            <p class="val-title">Password Requirements:</p>
            <ul>
              <li 
                v-for="(rule, index) in passwordRules" 
                :key="index" 
                :class="{ 'met': rule.valid, 'unmet': !rule.valid }"
              >
                <span class="icon">{{ rule.valid ? '✓' : '○' }}</span>
                {{ rule.label }}
              </li>
            </ul>
          </div>
        </div>

        <div class="button-group">
          <button class="btn-secondary" type="button" @click="$router.back()">
            Back
          </button>
          
          <button 
            class="btn-primary" 
            type="submit" 
            :disabled="!isPasswordValid"
          >
            Reset Password
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';

export default {
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      newPassword: '',  
      isAdmin: false,  
      userId: '',  
      userEmail: '', 
      adminName: ''
    };
  },
  computed: {
    title() {
      return this.isAdmin
        ? 'Reset User Password' 
        : 'Reset Your Password'; 
    },
    // NEW: Define the rules here
    passwordRules() {
      const pwd = this.newPassword;
      return [
        { label: 'At least 8 characters', valid: pwd.length >= 8 },
        { label: 'Contains uppercase letter (A-Z)', valid: /[A-Z]/.test(pwd) },
        { label: 'Contains lowercase letter (a-z)', valid: /[a-z]/.test(pwd) },
        { label: 'Contains a number (0-9)', valid: /[0-9]/.test(pwd) },
        { label: 'Contains special char (!@#$)', valid: /[!@#$%^&*(),.?":{}|<>]/.test(pwd) }
      ];
    },
    // NEW: Check if ALL rules are true
    isPasswordValid() {
      return this.passwordRules.every(rule => rule.valid);
    }
  },
  mounted() {
    const userInfo = JSON.parse(localStorage.getItem('user_info'));
    this.userId = this.$route.params.userId || userInfo.id; 
    this.userEmail = this.$route.params.userEmail || userInfo.email; 

    this.isAdmin = userInfo.role === 'admin'; 
    this.adminName = userInfo.fullname; 
  },
  methods: {
    resetPassword() {
      // Double check just in case user enables button by mistake
      if (!this.isPasswordValid) return;

      const confirmation = window.confirm('Are you sure you want to reset the password for this user?');
      if (!confirmation) return;

      const url = this.isAdmin
        ? `${this.baseUrl}/admin/user/reset-password/${this.userId}` 
        : `${this.baseUrl}/user/reset-password/${this.userId}`;

      fetch(url, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ new_password: this.newPassword })
      })
      .then(res => res.json())
      .then(() => {
        alert('Password reset successfully');
        this.$router.push('/admin/manage-users');
      })
      .catch(err => {
        console.error('Error resetting password:', err);
        alert('Failed to reset password. Check console.');
      });
    }
  }
};
</script>

<style scoped>
/* Main Wrapper */
.reset-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 80vh;
  padding-top: 40px;
}

/* Card Styling */
.reset-card {
  background: #ffffff;
  width: 100%;
  max-width: 450px;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

/* Header */
.card-header {
  margin-bottom: 30px;
  text-align: center;
}
.card-header h2 { margin: 0; color: #333; font-size: 1.8rem; }
.subtitle { margin-top: 5px; color: #6c757d; font-size: 0.95rem; }

/* Info Section */
.info-section {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 25px;
  border: 1px solid #e9ecef;
}

.info-group {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}
.info-group:last-child { margin-bottom: 0; }
.info-group label { color: #6c757d; font-weight: 600; font-size: 0.9rem; }
.static-value { color: #333; font-weight: bold; font-family: monospace; }

/* Form Inputs */
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #343a40; }

input[type="text"] {
  width: 100%;
  padding: 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 1rem;
  color: #495057;
  background-color: #fff;
  box-sizing: border-box; 
  transition: all 0.2s;
}

input:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.validation-box {
  margin-top: 10px;
  background: #fdfdfd;
  border-radius: 6px;
  padding: 10px;
  border: 1px dashed #e0e0e0;
}
.val-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #666;
  margin-bottom: 5px;
}
.validation-box ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.validation-box li {
  font-size: 0.85rem;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}
.icon {
  display: inline-block;
  width: 15px;
  margin-right: 5px;
  font-weight: bold;
}
/* Unmet Rule (Grey) */
.unmet { color: #999; }
/* Met Rule (Green) */
.met { color: #28a745; font-weight: 500; }

/* Button Group */
.button-group {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
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
/* Disabled State */
.btn-primary:disabled {
  background-color: #a0cfff;
  cursor: not-allowed;
  transform: none;
  opacity: 0.7;
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