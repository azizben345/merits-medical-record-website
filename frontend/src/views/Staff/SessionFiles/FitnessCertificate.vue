<template>
  <div class="ns-page">
    <h2>Fitness To Work Certificate</h2>

    <div v-if="!loading && !acl.canEdit" class="locked-banner">
      <strong>View Only: </strong> 
      <span v-if="currentUser.role === 'staff'">Staff cannot edit certificates.</span>
      <span v-else>This session is {{ sessionHeader?.status || 'locked' }}.</span>
    </div>

    <div 
      v-if="isDifferentDoctor && acl.canEdit" 
      style="background: #e2e8f0; color: #2d3748; padding: 10px; margin-bottom: 20px; border-radius: 4px; border-left: 5px solid #4299e1;"
    >
      ℹ️ <strong>Note:</strong> This certificate was prepared by <strong>{{ form.doctor_name_qualifications }}</strong>. 
      You are currently logged in as <strong>{{ currentUser.fullname }}</strong>.
    </div>

    <div class="top-actions-wrapper" v-if="acl.canEdit">
      <div class="status-row">
        <span v-if="hasUnsavedChanges" class="unsaved-text">
          <small>⚠️ Unsaved Changes</small>
        </span>
        <span v-else class="saved-text">
          <small>✓ All changes saved</small>
        </span>
      </div>

      <div class="buttons-row">
        <button 
          class="primary"
          @click="saveCertificate" 
          :disabled="saving || !hasUnsavedChanges || !acl.canEdit"
        >
          {{ saving ? 'Saving...' : (hasUnsavedChanges ? 'Save Changes' : 'Saved') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading certificate data...</div>

    <div v-else class="cert-paper" :class="{ 'is-dirty': hasUnsavedChanges }">
      <h3 class="cert-title">FITNESS TO WORK CERTIFICATE</h3>

      <div class="section-box">
        <div class="grid2">
          <label>
            Employee's Name
            <input type="text" v-model="form.staff_name" readonly class="read-only-input" />
          </label>
          <label>
            Staff / NRIC / Passport No.
            <input type="text" v-model="form.staff_ic_passport" readonly class="read-only-input" />
          </label>
        </div>
      </div>

      <div class="divider"></div>

      <p class="cert-statement">
        This is to certify that I have examined the above named person and found him/her to be:
      </p>

      <div class="category-selection">
        
        <label class="radio-card" :class="{ selected: form.fitness_category === 'A', disabled: !acl.canEdit }">
          <input type="radio" v-model="form.fitness_category" value="A" :disabled="!acl.canEdit" />
          <div>
            <strong>A. Fit without restriction</strong>
          </div>
        </label>

        <label class="radio-card" :class="{ selected: form.fitness_category === 'B', disabled: !acl.canEdit }">
          <input type="radio" v-model="form.fitness_category" value="B" :disabled="!acl.canEdit" />
          <div>
            <strong>B. Fit for application subject to restrictions below</strong>
          </div>
        </label>

        <label class="radio-card" :class="{ selected: form.fitness_category === 'C', disabled: !acl.canEdit }">
          <input type="radio" v-model="form.fitness_category" value="C" :disabled="!acl.canEdit" />
          <div>
            <strong>C. Unfit for application</strong>
          </div>
        </label>

      </div>

      <div class="section-box" style="margin-top: 20px;">
        <label>
          Restrictions (Nil or Specify)
          <span v-if="form.fitness_category === 'A'" class="muted-note">
            (Not applicable for Category A)
          </span>
        </label>
        <textarea 
          v-model="form.restrictions_text" 
          rows="4" 
          :disabled="form.fitness_category === 'A' || !acl.canEdit"
          placeholder="Enter specific work restrictions here..."
        ></textarea>
      </div>

      <div class="divider"></div>

      <div class="grid2">
        <label>
          Doctor Name & Qualifications
          <input 
            type="text" 
            v-model="form.doctor_name_qualifications" 
            placeholder="Doctor's Name and Qualification"
            :disabled="!acl.canEdit" 
          />
        </label>

        <div class="date-display">
          <label>Assessment Date:</label>
          
          <VueDatePicker 
            v-model="form.assessment_date"
            :disabled="!acl.canEdit"
            model-type="yyyy-MM-dd"
            :enable-time-picker="false"
            auto-apply
          >
            <template #trigger>
              <span class="editable-text" title="Click to change date">
                {{ formatDateDisplay(form.assessment_date) }}
                <span class="edit-icon" v-if="acl.canEdit">✎</span>
              </span>
            </template>
          </VueDatePicker>
        </div>

      </div>

    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
// Import the Named Exports for ACL
import { canSession, Status } from '@/shared/sessionAcl'; 

export default {
  name: 'FitnessCertificate',
  components: { VueDatePicker },
  
  // Inject the header getter from ViewSession.vue
  inject: {
    currentSessionHeader: { from: 'currentSessionHeader', default: () => () => null },
  },

  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      loading: false,
      saving: false,
      sessionId: this.$route.params.sessionId,
      
      // Store local copy of user info
      currentUser: JSON.parse(localStorage.getItem('user_info') || '{}'),
      
      originalForm: null, // For dirty checking
      
      form: {
        ftw_cert_id: null,
        staff_name: '',
        staff_ic_passport: '',
        doctor_name_qualifications: '',
        assessment_date: '',
        fitness_category: null,
        restrictions_text: ''
      }
    };
  },

  mounted() {
    this.fetchData();
  },
  
  // Browser Navigation Guards
  created() {
    window.addEventListener('beforeunload', this.handleBeforeUnload);
  },
  beforeUnmount() {
    window.removeEventListener('beforeunload', this.handleBeforeUnload);
  },
  beforeRouteLeave(to, from, next) {
    if (this.hasUnsavedChanges) {
      if (confirm('You have unsaved changes. Do you really want to leave?')) {
        next();
      } else {
        next(false);
      }
    } else {
      next();
    }
  },

  computed: {
    // Reactive Session Header from Parent
    sessionHeader() {
      return this.currentSessionHeader ? this.currentSessionHeader() : null;
    },

    acl() {
      // 1. Safety: If header isn't loaded, default to Read-Only
      if (!this.sessionHeader) return { canEdit: false, canView: true };

      const role = this.currentUser.role || 'staff';
      const status = this.sessionHeader.status || Status.DRAFT;

      // 2. RULE A: Staff can NEVER edit certificates
      if (role !== 'doctor' && role !== 'admin') {
        return { canEdit: false, canView: true };
      }

      // 3. RULE B: Doctors/Admins check session status (Draft vs Locked)
      const allowed = canSession(role, status, 'edit');

      return { canEdit: allowed, canView: true };
    },

    isDifferentDoctor() {
      // if form is blank, it's not "different", it's just new
      if (!this.form.doctor_name_qualifications) return false;

      // if user not a doctor, ignore
      if (this.currentUser.role !== 'doctor') return false;

      // check if the saved string INCLUDES my name (to handle " (OHD)" suffixes)
      // or if its completely different.
      const savedName = this.form.doctor_name_qualifications.toLowerCase();
      const myName = (this.currentUser.fullname || '').toLowerCase();

      // returns TRUE if the saved name does NOT contain my name
      return myName && !savedName.includes(myName);
    },

    hasUnsavedChanges() {
      if (!this.originalForm) return false;
      return JSON.stringify(this.form) !== JSON.stringify(this.originalForm);
    }
  },

  methods: {
    formatDateDisplay(dateStr) {
      if (!dateStr) return 'Select Date';
      const [y, m, d] = dateStr.split('-').map(Number);
      return `${String(d).padStart(2, '0')}-${String(m).padStart(2, '0')}-${y}`;
    },

    handleBeforeUnload(e) {
      if (this.hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
      }
    },

    fetchData() {
      if (!this.sessionId) return;
      this.loading = true;

      fetch(`${this.baseUrl}/sessions/${this.sessionId}/fitness-certificate`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;
        if (!res.ok) throw new Error('Failed to load certificate');
        return res.json();
      })
      .then(data => {
        this.form = { ...this.form, ...data };

        // --- INTEGRETY FEATURE: Auto-fill Doctor Name ---
        // If the form is blank (new cert), fill it with the logged-in doctor's name
        if (
            !this.form.doctor_name_qualifications && 
            // this.currentUser &&
            this.currentUser.fullname && 
            this.currentUser.role === 'doctor'
          ) {
           this.form.doctor_name_qualifications = `${this.currentUser.fullname} (OHD)`;
        }

        this.originalForm = JSON.parse(JSON.stringify(this.form));
      })
      .catch(err => {
        console.error(err);
        alert('Could not load certificate data.');
      })
      .finally(() => {
        this.loading = false;
      });
    },

    saveCertificate() {
      // Validation
      if (!this.form.doctor_name_qualifications) {
        alert('Please enter the Doctor Name & Qualifications.');
        return;
      }

      if (this.isDifferentDoctor) {
        const confirmed = confirm(
          `Warning: This certificate is currently signed by "${this.form.doctor_name_qualifications}".\n\n` +
          `Saving this will overwrite their entry with your data (or keep their name if you didn't change it).\n\n` +
          `Are you sure you want to proceed?`
        );
        
        if (!confirmed) return; // Stop if they click Cancel
      }

      // Integrity Check: Logic cleanup
      if (this.form.fitness_category === 'A') {
        this.form.restrictions_text = '';
      }

      // Security Check: Frontend ACL
      if (!this.acl.canEdit) {
        alert("You do not have permission to edit this certificate.");
        return;
      }

      this.saving = true;

      fetch(`${this.baseUrl}/sessions/${this.sessionId}/update-certificate`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(this.form)
      })
      .then(async res => {
        if (handleUnauthorized(res)) return;
        if (!res.ok) throw new Error(await res.text());
        return res.json();
      })
      .then(() => {
        // Update the snapshot to clear the "Unsaved" warning
        this.originalForm = JSON.parse(JSON.stringify(this.form));
        alert('Certificate saved successfully!'); 
      })
      .catch(err => {
        console.error(err);
        alert('Error saving certificate: ' + err.message);
      })
      .finally(() => {
        this.saving = false;
      });
    }
  }
};
</script>

<style scoped>
.ns-page { padding: 20px; max-width: 900px; margin: 0 auto; }

/* Locked Banner */
.locked-banner {
  background: #fff3cd; 
  color: #856404; 
  padding: 15px; 
  margin-bottom: 20px; 
  border-radius: 4px; 
  text-align: center;
  border: 1px solid #ffeeba;
}

/* Actions Area */
.top-actions-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 20px;
  gap: 5px;
}
.buttons-row {
  display: flex;
  gap: 10px;
}

/* Unsaved Changes Indicators */
.unsaved-text { color: #e53e3e; font-weight: 600; animation: fadeIn 0.3s ease-in-out; }
.saved-text { color: #38a169; opacity: 0.8; animation: fadeIn 0.3s ease-in-out; }

/* Buttons */
.primary { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;}
.primary:hover { background: #1d4ed8; }
.primary:disabled {
  background: #cbd5e1;
  color: #64748b;
  cursor: not-allowed;
  opacity: 0.7;
}

/* Paper / Form Styling */
.cert-paper {
  background: white;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  padding: 40px;
  border-radius: 8px;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.cert-paper.is-dirty {
  border-color: #f59e0b; /* Amber */
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
}

.cert-title {
  text-align: center;
  text-transform: uppercase;
  border-bottom: 2px solid #2d3748;
  padding-bottom: 10px;
  margin-bottom: 30px;
  letter-spacing: 1px;
}

.section-box { margin-bottom: 20px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; color: #4a5568; }

input[type="text"], textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 1rem;
}
input[type="text"]:disabled, textarea:disabled {
  background-color: #f7fafc;
  color: #a0aec0;
  cursor: not-allowed;
}
.read-only-input { background-color: #f8fafc; color: #4a5568; cursor: default; user-select: none; }
textarea { resize: vertical; }

.cert-statement { font-size: 1.1rem; margin: 20px 0; color: #2d3748; }

/* Radio Cards (Category A/B/C) */
.category-selection { display: flex; flex-direction: column; gap: 12px; }
.radio-card {
  display: flex; align-items: center; gap: 12px;
  border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px;
  cursor: pointer; transition: all 0.2s;
}
.radio-card:hover:not(.disabled) { background-color: #f7fafc; }
.radio-card.selected { background-color: #ebf8ff; border-color: #3182ce; box-shadow: 0 0 0 1px #3182ce; }
.radio-card.disabled { opacity: 0.7; cursor: not-allowed; background-color: #f7fafc; }

.divider { height: 1px; background: #e2e8f0; margin: 30px 0; }
.muted-note { font-weight: normal; color: #718096; font-size: 0.85em; margin-left: 5px; }

/* Date Picker Custom Trigger */
.editable-text {
  border-bottom: 1px dashed #2563eb;
  cursor: pointer; font-weight: bold; font-size: 1.1rem;
  padding-bottom: 2px; color: #2d3748;
}
.editable-text:hover { background-color: #ebf8ff; color: #2b6cb0; }
.edit-icon { font-size: 0.8em; color: #a0aec0; margin-left: 5px; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>