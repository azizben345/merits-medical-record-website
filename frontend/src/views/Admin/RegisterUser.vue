<template>
  <div class="register-wrapper">
    <div class="register-card">
      
      <div class="card-header">
        <h2>Register New User</h2>
        <!-- <p class="subtitle">Create an account for a Staff, Doctor, or Admin.</p> -->
      </div>

      <form @submit.prevent="registerUser">
        
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input 
            v-model.trim="form.fullname" 
            id="fullname" 
            type="text" 
            required 
            placeholder="Enter full name" 
          />
        </div>

        <div class="form-group">
          <label for="username">Username 
            <!-- <span class="opt">(Optional, Recommended for Admin)</span> -->
          </label>
          <input 
            v-model.trim="form.username" 
            id="username" 
            type="text" 
            placeholder="e.g. admin01 or staff_name" 
          />
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
            <span v-if="form.role === 'admin'" class="opt">
              For Admin, add a filter '<b>+admin</b>' to the registered email, this will ensure that an email can be used for 2 accounts 
              <br> <i>e.g. <b>user01+admin@example.com</b> (where staff account is 'user01@example.com')</i> <br><br>
            </span>
          <input 
            v-model.trim="form.email" 
            id="email" 
            type="email" 
            required 
            :placeholder="form.role === 'admin' ? 'e.g. user01+admin@example.com' : 'e.g. user01@example.com'" 
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <p class="hint" style="color: grey; font-style: italic;">Password is now generated randomly, and given to user via mail with option to change them.</p>
          <!-- <input 
            v-model="form.password" 
            id="password" 
            type="text" 
            required 
            placeholder="Set initial password"
            autocomplete="new-password"
          /> -->
        </div>

        <div class="form-group">
          <label for="role">Role</label>
          <div class="select-wrapper">
            <select v-model="form.role" id="role" required>
              <option value="" disabled>Select a role</option>
              <option value="staff">Staff</option>
              <option value="doctor">Doctor</option>
              <option value="admin">Admin</option>
              <option value="restricted">Restricted</option>
            </select>
          </div>
        </div>

        <div v-if="isStaff" class="staff-options-panel">
          <h4 class="panel-title">Staff Initialization</h4>
          
          <!-- <div class="checkbox-row">
            <input type="checkbox" id="init_profile" v-model="form.init_staff_profile" />
            <label for="init_profile">
              Create empty staff profile
              <span class="muted">(Creates row in `staff` table)</span>
            </label>
          </div> -->

          <div class="checkbox-row">
            <input type="checkbox" id="init_session" v-model="form.init_session" />
            <label for="init_session">
              Initialize first medical session
            </label>
          </div>

          <div v-if="form.init_session" class="session-grid">
            
            <div class="mini-group">
              <label>Session Date</label> <VueDatePicker 
                v-model="form.session_date" 
                model-type="yyyy-MM-dd"
                :enable-time-picker="false"
                auto-apply
              ></VueDatePicker>
            </div>

            <div class="mini-group">
              <label>Type</label>
              <select v-model="form.session_type">
                <option value="periodic">Periodic</option>
                <option value="pre-employment">Pre-employment</option>
                <option value="followup">Follow-up</option>
                <option value="return-to-work">Return to Work</option>
                <option value="ad-hoc">Ad-hoc</option>
              </select>
            </div>

            <div class="mini-group">
              <label>Status</label>
              <select v-model="form.session_status">
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="locked">Locked</option>
              </select>
            </div>
          </div>
        </div>
        <div class="button-group">
          <button type="button" class="btn-secondary" @click="$router.push('/admin/manage-users')">
            Back
          </button>
          
          <button type="submit" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'Registering...' : 'Register User' }}
          </button>
        </div>
        
        <div v-if="message" :class="['msg-box', success ? 'msg-success' : 'msg-error']">
          {{ message }}
        </div>

      </form>
    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  name: "RegisterUser",
  components: { VueDatePicker },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      submitting: false,
      form: {
        fullname: "",
        username: "", 
        email: "",
        password: "",
        role: "staff",

        // Staff Options
        init_staff_profile: true,
        init_session: true,
        session_date: new Date().toISOString().slice(0, 10),
        session_type: "periodic",
        session_status: "draft",
        duplicate_recent: false,
      },
      message: "",
      success: false,
    };
  },
  computed: {
    isStaff() {
      return this.form.role === "staff";
    },
  },
  watch: {
    "form.role"(v) {
      // Reset staff options if role changes away from staff
      if (v !== "staff") {
        this.form.init_staff_profile = true;
        this.form.init_session = true;
        this.form.duplicate_recent = false;
      }
    },
  },
  methods: {
    async registerUser() {

      // // --- TESTING BLOCK START ---
      // console.log("TESTING MODE ACTIVATED");
      // console.log("Date Picker Value:", this.form.session_date);
      // console.log("Full Payload:", this.form);
      
      // this.message = "Test complete! Check Console (F12) for data.";
      // this.success = true;
      // return; // <--- code below this won't run.
      // // --- TESTING BLOCK END ---

      this.message = "";
      this.success = false;
      this.submitting = true;

      try {
        const token = localStorage.getItem("jwt_token");

        // Prepare Payload
        const body = {
          fullname: this.form.fullname,
          username: this.form.username || null, 
          email: this.form.email,
          password: this.form.password,
          role: this.form.role,

          // Staff Logic
          init_staff_profile: this.form.init_staff_profile ?? true,
          init_session: this.isStaff && !!this.form.init_session,
          session_date: this.isStaff && this.form.init_session ? this.form.session_date : null,
          session_type: this.isStaff && this.form.init_session ? this.form.session_type : null,
          session_status: this.isStaff && this.form.init_session ? this.form.session_status : null,
          duplicate_recent: this.isStaff && this.form.init_session ? !!this.form.duplicate_recent : false,
        };

        const response = await fetch(`${this.baseUrl}/admin/register`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
          },
          body: JSON.stringify(body),
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || "Registration failed");
        }

        this.message = "User registered successfully!";
        this.success = true;

        // Reset form
        this.resetForm();

      } catch (err) {
        this.message = err.message || "Registration failed";
        this.success = false;
      } finally {
        this.submitting = false;
      }
    },
    resetForm() {
      this.form = {
        fullname: "",
        username: "",
        email: "",
        password: "",
        role: "",
        init_staff_profile: true,
        init_session: true,
        session_date: new Date().toISOString().slice(0, 10),
        session_type: "annual",
        session_status: "draft",
        duplicate_recent: false,
      };
    }
  },
};
</script>

<style scoped>

.register-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 80vh;
  padding-top: 40px;
  background-color: #f8f9fa;
  padding-bottom: 50px;
}

/* bg card */
.register-card {
  background: #ffffff;
  width: 100%;
  max-width: 550px;
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

/* Form Elements */
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #343a40; }
.opt { font-weight: 400; color: #888; font-size: 0.85em;  }

input[type="text"],
input[type="email"],
input[type="password"],
input[type="date"],
select {
  width: 100%;
  margin: 0;
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 1rem;
  color: #495057;
  background-color: #fff;
  box-sizing: border-box; 
  transition: all 0.2s;
}

input:focus, select:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* --- Staff Options Panel --- */
.staff-options-panel {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  margin-top: 10px;
  margin-bottom: 20px;
}

.panel-title {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 0.95rem;
  color: #007bff;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.checkbox-row label {
  font-weight: 500;
  color: #495057;
  margin: 0;
  cursor: pointer;
}

.muted { font-size: 0.85em; color: #888; font-weight: 400; }

/* Session Grid */
.session-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px dashed #dee2e6;
}
.mini-group label { font-size: 1rem; }

/* Buttons */
/* .button-group {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 35px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

button {
  padding: 10px 24px;
  font-size: 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
  border: none;
} */

.btn-primary { background-color: #007bff; color: white; }
.btn-primary:hover { background-color: #0056b3; transform: translateY(-1px); }
.btn-primary:disabled { background-color: #a0cfff; cursor: not-allowed; opacity: 0.8; }

.btn-secondary { background-color: #e9ecef; color: #495057; }
.btn-secondary:hover { background-color: #dde2e6; color: #212529; }

/* Feedback Messages */
.msg-box {
  margin-top: 20px;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  font-weight: 500;
}
.msg-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.msg-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>