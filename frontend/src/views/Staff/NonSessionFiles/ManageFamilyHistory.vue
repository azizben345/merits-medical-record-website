<template>
  <div>
    <h2>Family History</h2>

    <!-- Unified Add/Edit Family Member Modal -->
    <div v-if="isFamilyModalOpen" class="modal-overlay-vertical" @click="closeFamilyModal">
      <div class="modal-content-vertical" @click.stop>
        <h3>{{ editingFamily ? 'Edit Family Member' : 'Add Family Member' }}</h3>
        <form @submit.prevent="submitFamilyEntry">
          <label>Relationship:</label>
          <select v-model="modalFamily.relationship" required>
            <option value="father">Father</option>
            <option value="mother">Mother</option>
            <option value="spouse">Spouse</option>
            <option value="brother">Brother</option>
            <option value="sister">Sister</option>
            <option value="child">Child</option>
          </select><br><br>

          <label>Relative Name:</label>
          <input type="text" v-model="modalFamily.relative_name" required /><br>

          <label>Sex:</label>
          <select v-model="modalFamily.sex">
            <option value="M">M</option>
            <option value="F">F</option>
          </select><br><br>

          <label>Year of Birth:</label>
          <input type="number" v-model="modalFamily.year_of_born" /><br>

          <label>Age Now:</label>
          <!-- <input type="number" v-model="modalFamily.age_now" /><br> -->
          <input type="number" v-model="modalFamily.age_now" readonly style="background-color: #f2f2f2;" /><br>

          <label>Age at Death:</label>
          <input type="number" v-model="modalFamily.age_at_death" /><br>

          <label>State Health / Death Cause:</label>
          <input type="text" v-model="modalFamily.state_health_death_cause" /><br>

          <button type="submit">{{ editingFamily ? 'Save Changes' : 'Add Member' }}</button>
          <button type="button" @click="closeFamilyModal">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Family Disease Modal -->
    <div v-if="isDiseaseModalOpen" class="modal-overlay-vertical" @click="closeDiseaseModal">
      <div class="modal-content-vertical" @click.stop style="overflow-y: auto; max-height: 90vh;">
        <h3>Edit Family Disease</h3>
        <form @submit.prevent="submitDiseaseEntry">
          <table>
            <tbody>
              <tr>
                <td><label>Heart Disease</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.heart_disease" /></td>
              </tr>
              <tr>
                <td><label>High Blood Pressure</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.high_blood_pressure" /></td>
              </tr>
              <tr>
                <td><label>Stroke</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.stroke" /></td>
              </tr>
              <tr>
                <td><label>Cancer</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.cancer" /></td>
              </tr>
              <tr>
                <td><label>Diabetes</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.diabetes" /></td>
              </tr>
              <tr>
                <td><label>Kidney Disease</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.kidney_disease" /></td>
              </tr>
              <tr>
                <td><label>Allergy</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.allergy" /></td>
              </tr>
              <tr>
                <td><label>Asthma</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.asthma" /></td>
              </tr>
              <tr>
                <td><label>Eczema</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.eczema" /></td>
              </tr>
              <tr>
                <td><label>Tuberculosis</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.tuberculosis" /></td>
              </tr>
              <tr>
                <td><label>Epilepsy</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.epilepsy" /></td>
              </tr>
              <tr>
                <td><label>Mental Disorder</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.mental_disorder" /></td>
              </tr>
              <tr>
                <td><label>Alcohol Dependence</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.alcohol_dependence" /></td>
              </tr>
              <tr>
                <td><label>Drug Abuse</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.drug_abuse" /></td>
              </tr>
              <tr>
                <td><label>Birth Abnormality</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.birth_abnormality" /></td>
              </tr>
              <tr>
                <td><label>None of the Above</label></td>
                <td><input type="checkbox" class="checkbox" v-model="modalDisease.none_above" /></td>
              </tr>
              <tr>
                <td><label>Details:</label></td>
                <td><textarea v-model="modalDisease.details" style="height: 150px; width: 100%;"></textarea></td>
              </tr>
            </tbody>
          </table>

          <button type="submit">Save Disease Info</button>
          <button type="button" @click="closeDiseaseModal">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Family History Table -->
    <table>
      <thead>
        <tr>
          <th>Relationship</th>
          <th>Relative Name</th>
          <th>Sex</th>
          <th>Year of Birth</th>
          <th>Age</th>
          <th>Age at Death</th>
          <th>State Health / Death Cause</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="history in familyHistory" :key="history.fh_id">
          <td>{{ history.relationship.charAt(0).toUpperCase() + history.relationship.slice(1) }}</td>
          <td>{{ history.relative_name ?? '' }}</td>
          <td>{{ history.sex ?? '' }}</td>
          <td>{{ history.year_of_born ?? '' }}</td>
          <td>{{ history.age_now ?? '' }}</td>
          <td>{{ history.age_at_death ?? '' }}</td>
          <td>{{ history.state_health_death_cause ?? '' }}</td>
          <td>
            <button @click="openEditFamilyModal(history)">Edit</button>
            <button class="danger" @click="deleteFamilyMember(history)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button @click="openAddFamilyModal">Add Family Member</button>

    <!-- Family Disease Box -->
    <div class="disease-box" style="margin-top: 1rem">
      <h3>Family History Disease</h3>

      <!-- info icon -->
      <!-- <div class="info-container">
        <span class="info-icon" @click="showInfo = !this.showInfo">ℹ️</span>
        <div v-if="showInfo" class="info-overlay" @click.self="showInfo = false">
          <div class="info-box">
            <h4 style="color: black">Guide</h4>
            <p style="color: black">If this is your first time filling out this form and you don't want to check any boxes, make sure to also click <b>'Save Disease Info'</b> to ensure the data is up to date.</p>
            <button style="color: white; background-color: blue;" @click="showInfo = false">Got it</button>
          </div>
        </div>
      </div> -->


      <div style="display: flex;">
      <table class="disease-table">
        <thead>
          <tr>
            <th>Disease</th>
            <th>Yes/No</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Heart Disease</td>
            <td><span :style="{ color: familyHistoryDisease.heart_disease ? 'red' : 'green' }">{{ familyHistoryDisease.heart_disease ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>High Blood Pressure</td>
            <td><span :style="{ color: familyHistoryDisease.high_blood_pressure ? 'red' : 'green' }">{{ familyHistoryDisease.high_blood_pressure ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Stroke</td>
            <td><span :style="{ color: familyHistoryDisease.stroke ? 'red' : 'green' }">{{ familyHistoryDisease.stroke ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Cancer</td>
            <td><span :style="{ color: familyHistoryDisease.cancer ? 'red' : 'green' }">{{ familyHistoryDisease.cancer ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Diabetes</td>
            <td><span :style="{ color: familyHistoryDisease.diabetes ? 'red' : 'green' }">{{ familyHistoryDisease.diabetes ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Kidney Disease</td>
            <td><span :style="{ color: familyHistoryDisease.kidney_disease ? 'red' : 'green' }">{{ familyHistoryDisease.kidney_disease ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Allergy</td>
            <td><span :style="{ color: familyHistoryDisease.allergy ? 'red' : 'green' }">{{ familyHistoryDisease.allergy ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Asthma</td>
            <td><span :style="{ color: familyHistoryDisease.asthma ? 'red' : 'green' }">{{ familyHistoryDisease.asthma ? 'Yes' : 'No' }}</span></td>
          </tr>
        </tbody>
      </table>
      <!-- separate 16/2 -->
      <table class="disease-table" style="margin-left: auto;">
        <thead>
          <tr>
            <th>Disease</th>
            <th>Yes/No</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Eczema</td>
            <td><span :style="{ color: familyHistoryDisease.eczema ? 'red' : 'green' }">{{ familyHistoryDisease.eczema ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Tuberculosis</td>
            <td><span :style="{ color: familyHistoryDisease.tuberculosis ? 'red' : 'green' }">{{ familyHistoryDisease.tuberculosis ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Epilepsy</td>
            <td><span :style="{ color: familyHistoryDisease.epilepsy ? 'red' : 'green' }">{{ familyHistoryDisease.epilepsy ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Nervous or Mental Dependence</td>
            <td><span :style="{ color: familyHistoryDisease.alcohol_dependence ? 'red' : 'green' }">{{ familyHistoryDisease.alcohol_dependence ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Drug Abuse</td>
            <td><span :style="{ color: familyHistoryDisease.drug_abuse ? 'red' : 'green' }">{{ familyHistoryDisease.drug_abuse ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td>Birth Abnormality</td>
            <td><span :style="{ color: familyHistoryDisease.birth_abnormality ? 'red' : 'green' }">{{ familyHistoryDisease.birth_abnormality ? 'Yes' : 'No' }}</span></td>
          </tr>
          <tr>
            <td><b>None of the Above</b></td>
            <td><span :style="{ color: familyHistoryDisease.none_above ? 'green' : 'red' }">{{ familyHistoryDisease.none_above ? 'Yes' : 'No' }}</span></td>
          </tr>
        </tbody>
      </table>
      </div>

      <div class="inv-box">
        <strong>Details:</strong> <small><i>(to be filled by OHD)</i></small>
        <p>{{ familyHistoryDisease.details || '-' }}</p>
      </div>
    </div>

    <button @click="openDiseaseModal">Edit Family Disease</button>
    <!-- <button @click="$router.back()" style="margin-left: 1rem;">Back</button> -->
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
export default {
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      familyHistory: [],
      familyHistoryDisease: [],

      showInfo: false,

      // Family Member modal
      isFamilyModalOpen: false,
      editingFamily: null,
      modalFamily: {
        relationship: '',
        relative_name: '',
        sex: '',
        year_of_born: null,
        age_now: null,
        age_at_death: null,
        state_health_death_cause: ''
      },

      // Disease modal
      isDiseaseModalOpen: false,
      modalDisease: {},
      diseaseFields: [
        'heart_disease', 'high_blood_pressure', 'stroke', 'cancer',
        'diabetes', 'kidney_disease', 'allergy', 'asthma', 'eczema',
        'tuberculosis', 'epilepsy', 'mental_disorder',
        'alcohol_dependence', 'drug_abuse', 'birth_abnormality'
      ],
    };
  },

  mounted() {
    this.fetchFamilyHistory();
    this.fetchFamilyDisease();
  },

  methods: {

    fetchFamilyHistory() {
      const userInfo = localStorage.getItem('user_info');
      if (!userInfo) return;
      // const staffEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');
      const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      const staffEmailXYZ = staffEmailInView.replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/family-history/${staffEmailXYZ}`, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}` 
        }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
          this.familyHistory = data.family_history;
          // console.log("familyHistory: ",this.familyHistory);
        })
        .catch(err => console.error(err));
    },
    // boolean normalizer
    asBool(v) {
      if (v === true || v === false) return v;
      if (v == null) return false;
      if (typeof v === 'number') return v === 1;
      if (typeof v === 'string') {
        const s = v.trim().toLowerCase();
        return s === '1' || s === 'true' || s === 'y' || s === 'yes';
      }
      return !!v;
    },
    fetchFamilyDisease() {
      const userInfo = localStorage.getItem('user_info');
      if (!userInfo) return;
      // const staffEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');
      const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      const staffEmailXYZ = staffEmailInView.replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/family-history-disease/${staffEmailXYZ}`, {
        headers: { 
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}` 
        }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
          // console.log(data);
          // this.familyHistoryDisease = data;

          // if data is an array, take the first element
          const fhdData = Array.isArray(data) ? data[0] || {} : data;
          Object.keys(fhdData).forEach(key => {
            if (key !== 'staff_email' && key !== 'created_at' && key !== 'updated_at' && key !== 'details') {
              fhdData[key] = this.asBool(fhdData[key]);
            }
          });
          // this.familyHistoryDisease = Array.isArray(data) ? data[0] || {} : data;
          this.familyHistoryDisease = fhdData;

          // // convert numbers to booleans
          // Object.keys(this.familyHistoryDisease).forEach(key => {
          //   if (key !== 'staff_email' && key !== 'created_at' && key !== 'updated_at' && key !== 'details') {
          //     this.familyHistoryDisease[key] = !!this.familyHistoryDisease[key]; // 0 -> false, 1 -> true
          //   }
          // });

          // console.log("familyHistoryDisease: ",this.familyHistoryDisease);
        })
        .catch(err => console.error(err));
    },

    // -------- Family Member Modal ----------
    openAddFamilyModal() {
      this.editingFamily = null;
      this.modalFamily = { 
        relationship: '', relative_name: '', sex: '', year_of_born: null, 
        age_now: null, age_at_death: null, state_health_death_cause: '' 
      };
      this.isFamilyModalOpen = true;
    },
    openEditFamilyModal(history) {
      this.editingFamily = history;
      this.modalFamily = { ...history };
      this.isFamilyModalOpen = true;
      // this.modalFamily.age_now = new Date().getFullYear() - this.modalFamily.year_of_born;
    },
    closeFamilyModal() {
      this.isFamilyModalOpen = false;
      this.editingFamily = null;
    },
    submitFamilyEntry() { // not finished / not yet implemented
      
      const userInfo = localStorage.getItem('user_info');

      if (!userInfo) return;
      // const staffEmail = JSON.parse(userInfo).email;
      const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      const staffEmail = staffEmailInView;
      const payload = { 
        staff_email: staffEmail,
        ...
        this.modalFamily 
      };

      if (this.editingFamily) {
        // Edit existing
        fetch(`${this.baseUrl}/family-history/edit/${this.editingFamily.fh_id}`, {
          method: 'PUT',
          headers: { 
            Authorization: `Bearer ${localStorage.getItem('jwt_token')}`, 
            'Content-Type': 'application/json' 
          },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(() => {
            // console.log(payload);
            this.closeFamilyModal();
            this.fetchFamilyHistory();
          })
          .catch(err => console.error(err));
      } else {
        // Add new
        fetch(`${this.baseUrl}/family-history/add`, {
          method: 'POST',
          headers: { 
            Authorization: `Bearer ${localStorage.getItem('jwt_token')}`, 
            'Content-Type': 'application/json' 
          },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(() => {
            this.closeFamilyModal();
            this.fetchFamilyHistory();
          })
          .catch(err => console.error(err));
      }
    },

    deleteFamilyMember(history) {
      const confirmed = window.confirm('Are you sure you want to delete this member?');
      if (!confirmed) return;
      fetch(`${this.baseUrl}/family-history/delete/${history.fh_id}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(() => {
          this.familyHistory = this.familyHistory.filter(f => f.fh_id !== history.fh_id);
        })
        .catch(err => console.error(err));
    },

    // -------- Family Disease Modal ----------
    openDiseaseModal() {
      const defaultDisease = {
        heart_disease: false,
        high_blood_pressure: false,
        stroke: false,
        cancer: false,
        diabetes: false,
        kidney_disease: false,
        allergy: false,
        asthma: false,
        eczema: false,
        tuberculosis: false,
        epilepsy: false,
        mental_disorder: false,
        alcohol_dependence: false,
        drug_abuse: false,
        birth_abnormality: false,
        none_above: false,
        details: ''
      };

      // 1. Get the existing data
      const existingData = JSON.parse(JSON.stringify(this.familyHistoryDisease));

      // 2. Sanitize: If ANY disease is true, force 'none_above' to false.
      // This prevents the watcher from seeing "none_above: true" and wiping the specific diseases.
      const hasAnyDisease = this.diseaseFields.some(field => existingData[field] === true);
      
      if (hasAnyDisease) {
        existingData.none_above = false;
      }

      // 3. Merge and open
      this.modalDisease = { 
        ...defaultDisease, 
        ...existingData
      };
      
      this.isDiseaseModalOpen = true;
    },

    closeDiseaseModal() {
      this.isDiseaseModalOpen = false;
    },

    submitDiseaseEntry() {
      const userInfo = localStorage.getItem('user_info');
      if (!userInfo) return;

      // const staffEmail = JSON.parse(userInfo).email;
      const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      const staffEmail = staffEmailInView;

      // create a plain object copy to remove Vue Proxy
      const payload = {
        staff_email: staffEmail,
        heart_disease: this.modalDisease.heart_disease || false,
        high_blood_pressure: this.modalDisease.high_blood_pressure || false,
        stroke: this.modalDisease.stroke || false,
        cancer: this.modalDisease.cancer || false,
        diabetes: this.modalDisease.diabetes || false,
        kidney_disease: this.modalDisease.kidney_disease || false,
        allergy: this.modalDisease.allergy || false,
        asthma: this.modalDisease.asthma || false,
        eczema: this.modalDisease.eczema || false,
        tuberculosis: this.modalDisease.tuberculosis || false,
        epilepsy: this.modalDisease.epilepsy || false,
        mental_disorder: this.modalDisease.mental_disorder || false,
        alcohol_dependence: this.modalDisease.alcohol_dependence || false,
        drug_abuse: this.modalDisease.drug_abuse || false,
        birth_abnormality: this.modalDisease.birth_abnormality || false,
        none_above: this.modalDisease.none_above,
        details: this.modalDisease.details || ''
      };
      // console.log(payload);
      const staffEmailXYZ = staffEmail.replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/family-history-disease/edit/${staffEmailXYZ}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
        .then(res => res.json())
        .then(() => {
          this.familyHistoryDisease = { ...payload };
          this.closeDiseaseModal();
          this.fetchFamilyHistory();
          this.fetchFamilyDisease();
        })
        .catch(err => console.error(err));
    },

    formatDiseaseLabel(key) {
      return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
  },

  watch: {
    'modalFamily.year_of_born'(newYear) {
      const currentYear = new Date().getFullYear();
      this.modalFamily.age_now = newYear ? currentYear - newYear : null;
    },
    'modalFamily.relationship'(newRelationship) {
        const maleRelationships = ['father', 'brother'];
        const femaleRelationships = ['mother', 'sister'];

        if (maleRelationships.includes(newRelationship)) {
            this.modalFamily.sex = 'M';
        } else if (femaleRelationships.includes(newRelationship)) {
            this.modalFamily.sex = 'F';
        } else {
            // Clear or keep current sex for spouse/child
            this.modalFamily.sex = ''; 
        }
    },
    //When "None of the above" is checked, uncheck all others
    'modalDisease.none_above'(newValue) {
      if (newValue) {
        this.diseaseFields.forEach(key => {
          // We set them to false directly
          this.modalDisease[key] = false;
        });
      }
    },

    // 2. Deep watcher: If any disease is checked, uncheck "None of the above"
    modalDisease: {
      handler(val) {
        // Check if ANY of the disease fields are true
        const hasAnyDisease = this.diseaseFields.some(key => val[key] === true);

        // If a disease is selected BUT 'none_above' is also checked, uncheck 'none_above'
        if (hasAnyDisease && val.none_above) {
          this.modalDisease.none_above = false;
        }
      },
      deep: true
    }
  }
};
</script> 


<style scoped>
.manage-users {
  padding: 24px;
}

h2 {
  font-size: 22px;
  margin-bottom: 16px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

th,
td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

th {
  background-color: #f7fafc;
  color: #2d3748;
  font-weight: 600;
}

.actions {
  text-align: center;
  display: flex;
  gap: 8px;
}

button {
  padding: 6px 12px;
  font-size: 13px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #edf2f7;
  color: #2d3748;
  transition: background 0.2s ease;
}

button:hover {
  background: #e2e8f0;
}

button.danger {
  background: #feb2b2;
  color: #742a2a;
}

button.danger:hover {
  background: #fc8181;
}

input {
  width: 100%;
  padding: 8px;
  margin-bottom: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

input:focus {
  outline: none;
  border-color: #4a90e2;
}

button[type="submit"], button[type="button"] {
  width: 100%;
  padding: 8px;
  margin-top: 10px;
  margin: 10px;
  font-size: 16px;
}

.disease-box {
  background: #f7fafc;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  margin-bottom: 16px;
}

.disease-box h3 {
  margin-bottom: 12px;
}

.disease-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  /* grid-template-columns: repeat(2, 1fr);  2 columns */
  gap: 12px;
}

.disease-table {
  border-collapse: collapse;
  width: 49%;
  background: white;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  border-radius: 8px;
}

.disease-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.disease-table th {
  background-color: #f7fafc;
  color: #2d3748;
  font-weight: 600;
}

/* .disease-details {
  margin-top: 12px;
  background: white;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
} */

.checkbox {
  transform: scale(2.5); 
  margin-top: 1rem
}

/* .disease-item {
  background: white;
  padding: 8px;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  text-align: left;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-bottom: 8px;
}

.checkbox-label input[type="checkbox"] {
  transform: scale(3);
  margin-right: 8px;
  cursor: pointer;
  vertical-align: left;
} */

</style>