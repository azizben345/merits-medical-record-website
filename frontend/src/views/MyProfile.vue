<template>
  <div class="profile-page">
    <div class="header-row">
      <h2>User Profile</h2>
    </div>

    <div v-if="loading">Loading profile…</div>

    <form v-else @submit.prevent="save">
      <div class="field">
        <label>Full Name</label>
        <input v-model.trim="form.fullname" type="text" placeholder="Full name" />
      </div>

      <div class="field"> 
        <label>Email</label>
        <input v-model="form.email" type="email" readonly class="readonly" />
      </div>

      <div class="field">
        <label>Role</label>
        <input :value="formattedRole" type="text" readonly class="readonly" />
      </div>

      <div class="field" v-if="formattedRole === 'Staff' || formattedRole === 'Admin'">
        <label v-if="formattedRole === 'Staff'">Job Title</label>
        <label v-if="formattedRole === 'Admin'">Department</label>
        <input v-if="formattedRole === 'Staff'" v-model.trim="form.job_title" type="text" placeholder="Job Title" />
        <input v-if="formattedRole === 'Admin'" v-model.trim="form.department" type="text" placeholder="Department" />
      </div>

      <div class="field">
        <label>Phone No.</label>
        <input v-model.trim="form.phone_no" type="tel" placeholder="e.g. +60 12-345 6789" />
      </div>

      <div class="actions">
        <button
          type="submit"
          class="btn primary"
          :disabled="!isDirty || saving"
        >
          {{ saving ? 'Saving…' : 'Save Changes' }}
        </button>

        <button
          type="button"
          class="btn ghost"
          @click="reset"
          :disabled="!isDirty || saving"
        >
          Reset
        </button>
        
        <button type="button" class="btn warning" style="margin-left: auto;" @click="showPasswordModal = true">
          Change Password
        </button>
        
      </div>
    </form>

    <button type="button" style="margin-top: 1rem;" @click="$router.push('/dashboard')">Back</button>

    <!-- Change Password Modal -->
    <div v-if="showPasswordModal" class="modal-overlay">
      <div class="modal">
        <h3>Change Password</h3>
        <form @submit.prevent="updatePassword">
          <div class="field">
            <label>Current Password</label>
            <input v-model="passForm.current_password" type="password" required />
          </div>
          <div class="field">
            <label>New Password</label>
            <input v-model="passForm.new_password" type="password" minlength="6" required />
          </div>
          <div class="field">
            <label>Confirm New Password</label>
            <input v-model="passForm.confirm_password" type="password" required />
          </div>
          
          <div class="actions">
            <button 
              type="submit" 
              class="btn primary" 
              :disabled="passLoading || !passForm.new_password || passForm.new_password !== passForm.confirm_password"
              :title="passForm.new_password !== passForm.confirm_password ? 'Passwords do not match' : ''"
            >
              {{ passLoading ? 'Updating...' : 'Update Password' }}
            </button>
            <button type="button" class="btn ghost" @click="closePasswordModal">Cancel</button>
          </div>

          <p class="forgot-link">
            <a href="#" @click.prevent="$router.push({ path: '/forgot-password', query: { from: 'profile' } })">
              Forgot Password?
            </a>
          </p>

        </form>
      </div>
    </div>
    
  </div>
</template>

<script>
// Use the new wrapper!
import api from '@/utils/api'; 

export default {
  name: 'MyProfile',
  data() {
    return {
      loading: true,
      saving: false,
      original: null,
      form: {
        fullname: '',
        email: '',
        role: '',
        phone_no: '',
        job_title: '',
        department: ''
      },
      // Password Modal Data
      showPasswordModal: false,
      passLoading: false,
      passForm: {
        current_password: '',
        new_password: '',
        confirm_password: ''
      }
    }
  },
  computed: {
    formattedRole() {
      const role = this.form.role;
      if (!role) return 'Unknown Role';
      const map = { admin: 'Admin', staff: 'Staff', doctor: 'Doctor' };
      return map[role] || role.charAt(0).toUpperCase() + role.slice(1);
    },
    isDirty() {
      const pick = (o) => ({
        fullname: o.fullname || '',
        phone_no: o.phone_no || '',
        job_title: o.job_title || '',
        department: o.department || '',
      });
      try {
        return JSON.stringify(pick(this.form)) !== JSON.stringify(pick(this.original || {}));
      } catch {
        return true;
      }
    },
  },
  mounted() {
    this.loadUserData();
  },
  methods: {
    async loadUserData() {
      const user = JSON.parse(localStorage.getItem('user_info') || 'null');
      if (!user) return;
      const staffEmailXYZ = user.email.replace(/\./g, 'XYZ').replace(/\+/g, 'UVW');

      try {
        // Using api.get handles headers & Auth automatically
        const data = await api.get(`/user/role-based/${staffEmailXYZ}`);
        
        this.form = data;
        this.form.email = data.staff_email || data.doctor_email || data.admin_email || '';
        this.form.fullname = data.staff_name || data.doctor_name || data.admin_name || '';
        this.original = JSON.parse(JSON.stringify(this.form));

      } catch (err) {
        if(err.message !== 'SESSION_EXPIRED') {
          console.error(err);
        }
      } finally {
        this.loading = false;
      }
    },

    reset() {
      this.form = JSON.parse(JSON.stringify(this.original));
    },

    async save() {
      if (!this.isDirty || this.saving) return;
      this.saving = true;

      const staffEmailXYZ = this.form.email.replace(/\./g, 'XYZ').replace(/\+/g, 'UVW');

      try {
        const updated = await api.put(`/user/edit/role-based/${staffEmailXYZ}`, {
          fullname: this.form.fullname,
          phone_no: this.form.phone_no,
          role: this.form.role,
          job_title: this.form.job_title,
          department: this.form.department,
        });

        // Update LocalStorage
        const current = JSON.parse(localStorage.getItem('user_info') || '{}');
        const merged = { ...current, fullname: updated.fullname ?? this.form.fullname };
        localStorage.setItem('user_info', JSON.stringify(merged));

        // Update State
        this.form = { ...this.form, fullname: merged.fullname };
        this.original = JSON.parse(JSON.stringify(this.form));
        
        window.dispatchEvent(new Event('user-login'));
        alert('Profile saved 👍');

      } catch (e) {
        if(e.message !== 'SESSION_EXPIRED') alert(e.message || 'Failed to save profile');
      } finally {
        this.saving = false;
      }
    },

    // --- NEW PASSWORD LOGIC ---
    closePasswordModal() {
      this.showPasswordModal = false;
      this.passForm = { current_password: '', new_password: '', confirm_password: '' };
    },

    async updatePassword() {
      if (this.passForm.new_password !== this.passForm.confirm_password) {
        alert("New passwords do not match!");
        return;
      }

      this.passLoading = true;
      try {
        await api.post('/user/change-password', {
          current_password: this.passForm.current_password,
          new_password: this.passForm.new_password
        });
        
        alert("Password updated successfully!");
        this.closePasswordModal();
        
      } catch (err) {
        alert(err.message || "Failed to update password");
      } finally {
        this.passLoading = false;
      }
    }
  },
}
</script>

<style scoped>
.profile-page { padding: 24px; max-width: 720px; }
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.field { margin-bottom: 14px; display: flex; flex-direction: column; }
label { font-size: 13px; color: #4b5563; margin-bottom: 6px; }
input { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 10px; font-size: 14px; }
input.readonly { background: #f9fafb; color: #6b7280; }
.actions { display: flex; gap: 8px; margin-top: 12px; }
.btn { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #f3f4f6; cursor: pointer; }
.btn.ghost { background: #938e32; }
.btn.primary { background: #2563eb; color: white; border-color: #2563eb; }
.btn.warning { background: #d97706; color: white; border-color: #d97706; font-size: 0.9rem;}
.btn[disabled] { opacity: .55; cursor: not-allowed; }

/* Modal Styles */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex; justify-content: center; align-items: center; z-index: 1000;
}
.modal {
  background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px;
}
</style>