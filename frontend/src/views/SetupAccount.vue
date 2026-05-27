<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="header">
        <h2>Activate Account</h2>
        <p class="subtitle">Set up your security credentials to access MERITS.</p>
      </div>

      <div v-if="checkingLink" class="text-center" style="padding: 20px;">
        Verifying secure link...
      </div>

      <div v-else-if="!validPage" class="alert error">
        <strong>Invalid or Expired Link.</strong>
        <p>This invite code does not exist or has already been used.</p>
        <button class="btn-ghost" @click="$router.push('/')">Return to Login</button>
      </div>

      <form v-else @submit.prevent="handleSetup">
        
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" :value="email" readonly class="readonly-input" />
        </div>

        <div class="form-group">
          <label>Temporary Code</label>
          <input type="text" :value="tempCode" readonly class="readonly-input" />
          <span style="color:green; font-size:11px; font-weight:bold;">✓ Verified</span>
        </div>

        <hr class="divider" />

        <div class="form-group">
          <label>Full Name</label>
          <input v-model="fullname" type="text" placeholder="Enter your full name" required @input="fullname = $event.target.value.toUpperCase()" />
        </div>

        <div class="form-group">
          <label>New Password</label>
          <input v-model="password" type="password" placeholder="Min. 6 characters" required minlength="6"/>
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <input v-model="confirmPassword" type="password" required />
        </div>

        <div v-if="error" class="alert error">{{ error }}</div>
        
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Activating...' : 'Set Password & Login' }}
        </button>

      </form>
    </div>
  </div>
</template>

<script>
import api from '@/utils/api';

export default {
  name: 'SetupAccount',
  data() {
    return {
      email: '',
      tempCode: '',
      fullname: '',
      password: '',
      confirmPassword: '',
      
      // State control
      checkingLink: true,
      validPage: false,
      loading: false,
      error: ''
    };
  },
  async created() {
    // 1. Get Params
    const query = this.$route.query;
    this.email = query.email || '';
    this.tempCode = query.code || '';

    // 2. Validate params exist locally first
    if (!this.email || !this.tempCode) {
      this.checkingLink = false;
      this.validPage = false;
      return;
    }

    // 3. CALL BACKEND TO VERIFY
    try {
      await api.post('/auth/verify-setup-link', {
        email: this.email,
        code: this.tempCode
      });
      
      // If no error thrown, it's valid
      this.validPage = true;
    } catch (err) {
      console.warn("Link verification failed:", err);
      this.validPage = false;
    } finally {
      this.checkingLink = false;
    }
  },
  methods: {
    async handleSetup() {
      // ... same logic as before ...
      if (this.password !== this.confirmPassword) {
        this.error = "Passwords do not match.";
        return;
      }

      this.loading = true;
      try {
        const response = await api.post('/auth/setup-account', {
          email: this.email,
          temp_password: this.tempCode,
          new_password: this.password,
          fullname: this.fullname
        });

        localStorage.setItem('jwt_token', response.token);
        localStorage.setItem('user_info', JSON.stringify(response.user));
        
        alert("Account activated! Logging in...");
        window.dispatchEvent(new Event('user-login'));
        this.$router.push('/dashboard');

      } catch (err) {
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
/* Reusing your clean auth styles */
.auth-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f6f8; padding: 20px; }
.auth-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 420px; }
.header { text-align: center; margin-bottom: 24px; }
h2 { color: #1a202c; margin: 0 0 8px 0; }
.subtitle { color: #718096; font-size: 14px; margin: 0; }

.form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
label { font-size: 13px; font-weight: 600; color: #4a5568; }
input { padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; }

input:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(66,153,225,0.2); }

.readonly-input { background-color: #f7fafc; color: #718096; cursor: not-allowed; border-color: #edf2f7; }
.hint { font-size: 11px; color: #a0aec0; }
.divider { border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0; }

.btn-primary { width: 100%; padding: 12px; background: #2f855a; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-primary:hover { background: #276749; }
.btn-primary:disabled { background: #cbd5e0; cursor: not-allowed; }

.alert { padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; text-align: center; }
.error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
.success { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
</style>