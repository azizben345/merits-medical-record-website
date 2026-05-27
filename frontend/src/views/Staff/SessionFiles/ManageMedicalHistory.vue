<template>
  <div>
    <h2>Medical History</h2>

    <!-- Edit Medical History Modal -->
    <teleport to="body">
      <div v-if="isModalOpen" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <h3>Edit Medical History</h3>
          <form @submit.prevent="submitMedicalHistory">
            <div class="medical-grid">
              <div v-for="field in medicalFields" :key="field.key" class="medical-item">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="modalMedicalHistory[field.key]" />
                  {{ field.label }}
                </label>
              </div>
            </div>

            <label>Comments:</label>
            <textarea v-model="modalMedicalHistory.comment_by_examine_doctor" style="width:100%;height:150px;"></textarea><br>
            <button type="submit">Save Changes</button>
            <button type="button" @click="closeModal">Cancel</button>
          </form>
        </div>
      </div>
    </teleport>


    <!-- Display Medical History -->
    <div class="medical-box">
      
      <div style="display: flex; gap: 8px;">

        <table class="std-table">
          <thead>
            <tr>
              <th>Medical Condition</th>
              <th>Yes/No</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="field in medicalFields.slice(0, medicalFields.length/3)" :key="field.key">
              <td>{{ field.label }}</td>
              <td :style="{ color: medicalHistory[field.key] ? 'red' : 'green' }">
                {{ medicalHistory[field.key] ? 'Yes' : 'No' }}
              </td>
            </tr>
          </tbody>
        </table>

        <table class="std-table">
          <thead>
            <tr>
              <th>Medical Condition</th>
              <th>Yes/No</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="field in medicalFields.slice(medicalFields.length/3, (medicalFields.length/3)*2)" :key="field.key">
              <td>{{ field.label }}</td>
              <td :style="{ color: medicalHistory[field.key] ? 'red' : 'green' }">
                {{ medicalHistory[field.key] ? 'Yes' : 'No' }}
              </td>
            </tr>
          </tbody>
        </table>

        <table class="std-table">
          <thead>
            <tr>
              <th>Medical Condition</th>
              <th>Yes/No</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="field in medicalFields.slice((medicalFields.length/3)*2)" :key="field.key">
              <td :style="{ fontWeight: field.label === 'None of the Above' ? 'bold' : '' }">
                {{ field.label === 'None of the Above' ? field.label : field.label }}
              </td>
              <td :style="{ color: field.key === 'none_of_the_above' ? (medicalHistory[field.key] ? 'green' : 'red') : (medicalHistory[field.key] ? 'red' : 'green') }">
                {{ medicalHistory[field.key] ? 'Yes' : 'No' }}
              </td>
            </tr>
          </tbody>
        </table>

      </div>

      <div class="inv-box" style="margin-top:1rem; margin-bottom: 1rem;">
        <strong>Doctor's Comment:</strong> <small><i>(to be filled by OHD)</i></small>
        <p>{{ medicalHistory.comment_by_examine_doctor }}</p>
      </div>
    </div>

    <button :disabled="!accessCheck('edit')" class="action-btn" @click="openModal">Edit Medical History</button>
    <!-- <button @click="$router.push('/dashboard')" style="margin-left:1rem;">Back</button> -->
  </div>
</template>

<script>
import { canSession } from '@/shared/sessionAcl';
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';

export default {
  inject: {
    currentSessionHeader: { from: 'currentSessionHeader', default: () => () => null },
    refreshSessionHeader: { from: 'refreshSessionHeader', default: () => async () => {} },
  },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      medicalHistory: {},
      modalMedicalHistory: {},
      isModalOpen: false,
      // List of fields with labels for display + editing
      medicalFields: [
        { key: 'abnormal_heartbeat', label: 'Abnormal Heartbeat' },
        { key: 'bladder_trouble', label: 'Bladder Trouble' },
        { key: 'dermatitis_eczema', label: 'Dermatitis / Eczema' },
        { key: 'depression', label: 'Depression' },
        { key: 'heart_murmur', label: 'Heart Murmur' },
        { key: 'hernia', label: 'Hernia' },
        { key: 'jaundice', label: 'Jaundice' },
        { key: 'kidney_disease', label: 'Kidney Disease' },
        { key: 'peptic_ulcer', label: 'Peptic Ulcer' },
        { key: 'persistent_night_sweats', label: 'Persistent Night Sweats' },
        { key: 'rectal_bleeding', label: 'Rectal Bleeding' },
        { key: 'unintentional_weight_loss', label: 'Unintentional Weight Loss' },
        { key: 'asthma_bronchitis', label: 'Asthma / Bronchitis' },
        { key: 'bowel_disorder', label: 'Bowel Disorder' },
        { key: 'diabetes', label: 'Diabetes' },
        { key: 'frequent_indigestion', label: 'Frequent Indigestion' },
        { key: 'high_blood_pressure', label: 'High Blood Pressure' },
        { key: 'hospitalisation_surgery', label: 'Hospitalisation / Surgery' },
        { key: 'migraine_headache', label: 'Migraine / Headache' },
        { key: 'psoriasis_skin_disease', label: 'Psoriasis / Skin Disease' },
        { key: 'persistent_diarrhoea', label: 'Persistent Diarrhoea' },
        { key: 'renal_colic_stone', label: 'Renal Colic / Stone' },
        { key: 'swollen_lymph_glands', label: 'Swollen Lymph Glands' },
        { key: 'anxiety', label: 'Anxiety' },
        { key: 'blood_in_urine', label: 'Blood in Urine' },
        { key: 'dizziness_giddiness', label: 'Dizziness / Giddiness' },
        { key: 'faints_blackouts', label: 'Faints / Blackouts' },
        { key: 'hay_fever', label: 'Hay Fever' },
        { key: 'joint_disorder', label: 'Joint Disorder' },
        { key: 'liver_gall_bladder', label: 'Liver / Gall / Bladder' },
        { key: 'piles_haemorrhoids', label: 'Piles / Haemorrhoids' },
        { key: 'rheumatic_fever', label: 'Rheumatic Fever' },
        { key: 'std', label: 'STD' },
        { key: 'tuberculosis', label: 'Tuberculosis' },
        { key: 'none_of_the_above', label: 'None of the Above' },
      ]
    };
  },
  mounted() {
    this.fetchMedicalHistory();
  },
  computed: {
    // step-by-step: call the injected function to get the header
    sessionHeader() {
      return this.currentSessionHeader ? this.currentSessionHeader() : null;
    },
    sessionStatus() {
      return this.sessionHeader?.status || 'draft';
    },
  },
  methods: {
    // Access control wrapper
    accessCheck(action) {
      let role = null;
      try {
        role = JSON.parse(localStorage.getItem('user_info'))?.role || 'staff';
      } catch (e) { /* ignore */ }
      return canSession(role, this.sessionStatus, action);
    },
    
    blankMedicalObj() {
      const base = {}
      this.medicalFields.forEach(f => { base[f.key] = false })
      base.comment_by_examine_doctor = ''
      return base
    },
    fetchMedicalHistory() {
      // const userInfo = localStorage.getItem('user_info');
      // if (!userInfo) return;
      // const staffEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';

      fetch(`${this.baseUrl}/medical-history/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(raw => {
          const data = Array.isArray(raw) ? (raw[0] || {}) : raw

          // start from defaults so every key exists
          const normalized = this.blankMedicalObj()

          // convert Y/N to boolean where present
          this.medicalFields.forEach(f => {
            const v = data[f.key]
            if (v === 'Y') normalized[f.key] = true
            else if (v === 'N') normalized[f.key] = false
            // if API already returns booleans, this also works:
            else if (typeof v === 'boolean') normalized[f.key] = v
          })

          normalized.comment_by_examine_doctor =
            data.comment_by_examine_doctor || ''

          this.medicalHistory = normalized;
          // console.log("Medical History: ", this.medicalHistory);
        })
        .catch(err => console.error(err))
    },

    openModal() {
      // // shallow clone of the already-boolean state
      // this.modalMedicalHistory = { ...this.medicalHistory }
      // this.isModalOpen = true
      // // prevent body scroll while modal open
      // document.body.style.overflow = 'hidden'
      
      // 1. Create a copy of the existing history
      const tempHistory = { ...this.medicalHistory };

      // 2. Sanitize: If any specific disease is true, force none_of_the_above to false
      const hasAnyDisease = this.medicalFields.some(field => 
        field.key !== 'none_of_the_above' && tempHistory[field.key] === true
      );

      if (hasAnyDisease) {
        tempHistory.none_of_the_above = false;
      }

      // 3. Assign to modal and open
      this.modalMedicalHistory = tempHistory;
      this.isModalOpen = true;

      // prevent body scroll while modal open
      document.body.style.overflow = 'hidden';
    },
    closeModal() {
      this.isModalOpen = false
      document.body.style.overflow = ''
    },

    submitMedicalHistory() {
      const userInfo = localStorage.getItem('user_info');
      // if (!userInfo) return;
      // const staffEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';

      // convert boolean back to Y/N before sending
      const payload = {}
      this.medicalFields.forEach(field => {
        payload[field.key] = this.modalMedicalHistory[field.key] ? 'Y' : 'N'
      })
      payload.staff_email = JSON.parse(userInfo).email
      payload.comment_by_examine_doctor =
        this.modalMedicalHistory.comment_by_examine_doctor || ''

      fetch(`${this.baseUrl}/medical-history/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
        .then(res => res.json())
        .then(() => {
          this.isModalOpen = false
          document.body.style.overflow = ''
          this.fetchMedicalHistory() // refresh display
        })
        .catch(err => console.error(err))
    },
  },
  watch: {
    // 1. When 'None of the Above' is checked, uncheck all specific diseases
    'modalMedicalHistory.none_of_the_above'(newVal) {
      if (newVal) {
        this.medicalFields.forEach(field => {
          if (field.key !== 'none_of_the_above') {
            this.modalMedicalHistory[field.key] = false;
          }
        });
      }
    },

    // 2. Deep watcher: If any specific disease is checked, uncheck 'None of the Above'
    modalMedicalHistory: {
      handler(val) {
        // Check if ANY field (except none_of_the_above) is true
        const hasAnyDisease = this.medicalFields.some(field => 
          field.key !== 'none_of_the_above' && val[field.key] === true
        );

        // If a disease is selected BUT 'none' is also checked, uncheck 'none'
        if (hasAnyDisease && val.none_of_the_above) {
          this.modalMedicalHistory.none_of_the_above = false;
        }
      },
      deep: true
    }
  }

};
</script>

<style scoped>
.medical-box {
  background: #f7fafc;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  margin-bottom: 16px;
}
.medical-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}
.medical-item {
  background: white;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.medical-comment {
  margin-top: 12px;
  background: white;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-bottom: 8px;
}
.checkbox-label input[type="checkbox"] {
  transform: scale(2); /* Make checkbox bigger */
  margin-right: 8px;      /* Space between box and label */
  cursor: pointer;
}
</style>
