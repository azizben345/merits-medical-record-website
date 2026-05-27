<template>
  <div>
    <div class="login-container">
      <h2>Login</h2>

      <form @submit.prevent="handleLogin">
        
        <div class="form-row">
          <label for="email">Email or Username:</label>
          <input 
            v-model="email" 
            type="text" 
            id="email" 
            placeholder="e.g. username_01 or xxx@vtti.com"
            required 
            :disabled="loading"
          />
        </div>

        <div class="form-row">
          <label for="password">Password:</label>
          <input 
            v-model="password" 
            type="password" 
            id="password" 
            required 
            :disabled="loading"
          />
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>

        <p class="forgot-link" style="text-align: center; margin-top: 15px;">
          <a href="#" @click.prevent="$router.push('/forgot-password')">Forgot Password?</a>
        </p>

      </form>
    </div>

    <p v-if="successMessage" class="success">{{ successMessage }}</p>
    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
  </div>
</template>

<script>
import cfg from '@/apiConfig';

export default {
  name: 'MyLogin',
  data() {
    return {
      loading: false,
      email: '', 
      password: '',
      errorMessage: '',
      successMessage: ''
    };
  },
  methods: {
    async handleLogin() {
      this.errorMessage = '';
      this.successMessage = '';
      this.loading = true;

      try {
        // Pointing to standard login, not login-otp
        // Note: You might need to check routes.php if the URL is still '/login-otp' or just '/login'
        const res = await fetch(`${cfg.API_BASE_URL}/login`, { 
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: this.email, password: this.password })
        });

        const data = await res.json();

        if (res.ok && data.token) {
          this.successMessage = 'Login successful!';
          
          // Save Token & User Data
          localStorage.setItem('jwt_token', data.token);
          localStorage.setItem('user_info', JSON.stringify(data.user));
          
          // Notify App to update state
          window.dispatchEvent(new Event('user-login'));
          
          // Redirect
          if (this.$router) this.$router.push('/dashboard');
          else window.location.href = 'dashboard.html';
        } else {
          this.errorMessage = data.error || 'Invalid credentials.';
        }
      } catch (err) {
        console.error(err);
        this.errorMessage = 'Server connection failed.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
/* Kept your original styling */
.login-container {
  max-width: 400px;
  margin: 20px auto;
  padding: 30px;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  background-color: #fff;
}
h2 { text-align: center; color: #333; margin-bottom: 25px; font-size: 24px; }
.form-row { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 15px; }
.form-row label { width: 120px; margin-right: 15px; font-weight: bold; text-align: center; color: #000; }
.form-row input { flex: 1; padding: 10px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; transition: background-color 0.3s ease; margin-top: 20px; }
button:hover { background-color: #0056b3; }
.error { color: #dc3545; margin-top: 15px; text-align: center; font-weight: bold; }
.success { color: #28a745; margin-top: 15px; text-align: center; font-weight: bold; }
.forgot-link a { color: #007bff; text-decoration: none; font-size: 0.9em; }
</style>