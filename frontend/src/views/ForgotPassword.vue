<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2>Forgot Password</h2>
      <p class="subtitle">Enter your email to receive a reset link.</p>
      
      <form @submit.prevent="handleRequest">
        <div class="form-group">
          <label>Email Address</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="e.g. ali@vtti.com" 
            required 
            :disabled="loading"
          />
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Sending...' : 'Send Reset Link' }}
        </button>
      </form>

      <div v-if="message" class="alert success">{{ message }}</div>
      <div v-if="error" class="alert error">{{ error }}</div>
      
      <div class="footer-link">
        
        <a 
          v-if="$route.query.from === 'profile'" 
          href="#" 
          @click.prevent="$router.go(-1)"
        >
          &larr; Back to Profile
        </a>

        <router-link v-else to="/">
          Back to Login
        </router-link>

      </div>

    </div>
  </div>
</template>

<script>
import api from '@/utils/api'; // uses your new wrapper

export default {
  data() {
    return {
      email: '',
      loading: false,
      message: '',
      error: ''
    };
  },
  methods: {
    async handleRequest() {
      this.loading = true;
      this.error = '';
      this.message = '';
      
      try {
        // Calls your backend: POST /user/forgot-password
        // The wrapper automatically handles JSON stringify and headers
        await api.post('/user/forgot-password', { email: this.email });
        
        // Success Message
        this.message = "A reset link has been sent to " + this.email + ". Please check your email (or spam folder).";
        this.email = ''; // Clear input
      } catch (err) {
        // The wrapper throws an error object with the message from backend
        this.error = err.message || "An error occurred. Please try again.";
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
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
.footer-link { margin-top: 1.5rem; text-align: center; font-size: 0.9rem; }
.footer-link a { color: #2b6cb0; text-decoration: none; }
.footer-link a:hover { text-decoration: underline; }
</style>