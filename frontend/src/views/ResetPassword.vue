<template>
  <!-- Maybe Unused -->
  <div class="auth-container">
    <div class="auth-card">
      <h2>Set New Password</h2>
      <p class="subtitle">Please enter your new password below.</p>
      
      <form @submit.prevent="handleReset">
        <div class="form-group">
          <label>New Password</label>
          <input 
            v-model="password" 
            type="password" 
            placeholder="At least 6 characters" 
            required 
            minlength="6"
          />
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input 
            v-model="confirmPassword" 
            type="password" 
            placeholder="Re-type password" 
            required 
          />
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Updating...' : 'Reset Password' }}
        </button>
      </form>

      <div v-if="message" class="alert success">{{ message }}</div>
      <div v-if="error" class="alert error">{{ error }}</div>
    </div>
  </div>
</template>

<script>
import api from '@/utils/api';

export default {
  data() {
    return {
      token: '',
      password: '',
      confirmPassword: '',
      loading: false,
      message: '',
      error: ''
    };
  },
  created() {
    // 1. Grab the token from the URL
    this.token = this.$route.query.token;
    
    // 2. Validate existence
    if (!this.token) {
      this.error = "Invalid or missing reset token. Please request a new link.";
    }
  },
  methods: {
    async handleReset() {
      if (this.password !== this.confirmPassword) {
        this.error = "Passwords do not match.";
        return;
      }
      
      if (!this.token) {
        this.error = "Missing token. Cannot proceed.";
        return;
      }

      this.loading = true;
      this.error = '';
      
      try {
        // Calls the PUBLIC route we just created
        await api.post('/user/reset-password', {
          token: this.token,
          new_password: this.password
        });
        
        this.message = "Password updated successfully! Redirecting to login...";
        
        // Redirect after 2 seconds
        setTimeout(() => {
          this.$router.push('/login'); // or '/'
        }, 2000);

      } catch (err) {
        this.error = err.message || "Token is invalid or expired.";
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
/* Reuse the exact same styles from ForgotPassword.vue */
.auth-container { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f6f8; }
.auth-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
.subtitle { color: #666; margin-bottom: 1.5rem; font-size: 0.9rem; }
.form-group { margin-bottom: 1rem; }
input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
.btn-primary { width: 100%; padding: 0.75rem; background: #2b6cb0; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
.btn-primary:disabled { background: #cbd5e0; cursor: not-allowed; }
.alert { margin-top: 1rem; padding: 0.75rem; border-radius: 4px; font-size: 0.9rem; }
.success { background: #c6f6d5; color: #2f855a; }
.error { background: #fed7d7; color: #c53030; }
</style>