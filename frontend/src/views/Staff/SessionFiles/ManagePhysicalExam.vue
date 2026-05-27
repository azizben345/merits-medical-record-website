<template>
  <div>
    <h2>Physical Examination</h2>

    <!-- Section 1 Table: Vitals / Vision / Colour Vision -->
    <h3>Vitals & Vision</h3>

    <div>
      <table class="std-table" style="border-collapse: collapse;">
        <thead>
          <tr>
            <th>Weight (kg)</th>
            <th>Height (m)</th>
            <th>BMI</th>
            <th>BP (Sys/Dia)</th>
            <th>Pulse (bpm)</th>
            <th>Blood Group</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ physicalExam.weight_kg ?? '-' }}</td>
            <td>{{ physicalExam.height_m ?? '-' }}</td>
            <td>{{ physicalExam.bmi ?? '-' }}</td>
            <td>{{ physicalExam.bp_sys ?? '-' }}/{{ physicalExam.bp_dia ?? '-' }}</td>
            <td>{{ physicalExam.pulse_bpm ?? '-' }}</td>
            <td>{{ physicalExam.blood_group ?? '-' }}</td>
          </tr>

        </tbody>
      </table>
    </div>

    <div style="display: flex; gap: 16px; margin: 16px 0; margin-bottom: 0%;">

      <table class="std-table">
        <thead>
          <tr>
            <th colspan="4">Distant Vision</th>
          </tr>
          <tr>
            <th></th> 
            <th>R</th>
            <th>L</th>
            <th>Both</th> 
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>Uncorrected</th>
            <td>{{ physicalExam.dist_uncorr_r ?? '-' }}</td>
            <td>{{ physicalExam.dist_uncorr_l ?? '-' }}</td>
            <td>{{ physicalExam.dist_uncorr_b ?? '-' }}</td>
            <!-- <td></td> -->
          </tr>
          <tr>
            <th>Corrected</th>
            <td>{{ physicalExam.dist_corr_r ?? '-' }}</td>
            <td>{{ physicalExam.dist_corr_l ?? '-' }}</td>
            <td>{{ physicalExam.dist_corr_b ?? '-' }}</td>
            <!-- <td></td> -->
          </tr>

            </tbody>
      </table>

      <table class="std-table">
        <thead>
          <tr>
            <th colspan="4">Near Vision</th>
          </tr>
          <tr>
            <th></th> 
            <th>R</th>
            <th>L</th>
            <th>Both</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>Uncorrected</th>
            <td>{{ physicalExam.near_uncorr_r ?? '-' }}</td>
            <td>{{ physicalExam.near_uncorr_l ?? '-' }}</td>
            <td>{{ physicalExam.near_uncorr_b ?? '-' }}</td>
          </tr>
          <tr>
            <th>Corrected</th>
            <td>{{ physicalExam.near_corr_r ?? '-' }}</td>
            <td>{{ physicalExam.near_corr_l ?? '-' }}</td>
            <td>{{ physicalExam.near_corr_b ?? '-' }}</td>
          </tr>

        </tbody>
      </table>
      <table class="std-table">
        <tbody>
          <tr>
            <th colspan="2">Colour Vision</th>
          </tr>
          <tr>
            <td colspan="2">{{ physicalExam.colour_vision ?? '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <button :disabled="!accessCheck('edit')" class="action-btn" @click="openModal">Edit Vitals & Vision</button>

    <!-- Section 2 Table: Body Systems -->
    <h3>Body System Examination</h3>
    <div style="display: flex; gap: 8px;">

      <table class="std-table">
        <thead>
          <tr>
            <th>Body System</th>
            <th>Result</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="field in bodySystemFields.slice(0, bodySystemFields.length / 2)" :key="field.key">
            <td><strong>{{ field.label }}</strong></td>
            <td :style="{ color: 
              physicalExam2[field.key] === 'Abnormal' ? 'red' : 
              physicalExam2[field.key] === 'Normal' ? 'green' : 
              physicalExam2[field.key] === 'Not examined' ? 'grey' : '' }"
            >
              {{ physicalExam2[field.key] ?? '-' }}
            </td>
            <td>
              {{ physicalExam2[field.key + '_details_abnormality'] || '-' }}
            </td>
          </tr>
        </tbody>
      </table>

      <table class="std-table">
        <thead>
          <tr>
            <th>Body System</th>
            <th>Result</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="field in bodySystemFields.slice(bodySystemFields.length / 2)" :key="field.key">
            <td><strong>{{ field.label }}</strong></td>
            <td :style="{ color: 
              physicalExam2[field.key] === 'Abnormal' ? 'red' : 
              physicalExam2[field.key] === 'Normal' ? 'green' : 
              physicalExam2[field.key] === 'Not examined' ? 'grey' : '' }"
            >
              {{ physicalExam2[field.key] ?? '-' }}
            </td>
            <td>
              {{ physicalExam2[field.key + '_details_abnormality'] || '-' }}
            </td>
          </tr>
        </tbody>
      </table>
      
    </div>
    <button :disabled="!accessCheck('edit')" class="action-btn" @click="openModal2">Edit Body Systems</button>

    <!-- <button @click="$router.push('/dashboard')" style="margin-top:1rem;">Back</button> -->

    <!-- Modal 1: Edit physical_exams -->
    <div v-if="isModalOpen" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop style="overflow-y:auto; max-height:90vh;">
        <h3>Edit Vitals & Vision</h3>
        <form @submit.prevent="submitPhysicalExam">
          <div class="physical-grid">
            <div class="physical-item">
              <label>Weight (kg):</label>
              <input type="number" step="0.1" v-model.number="modalPhysicalExam.weight_kg" @input="calculateBMI" />
            </div>
            <div class="physical-item">
              <label>Height (m):</label>
              <input type="number" step="0.01" v-model.number="modalPhysicalExam.height_m" @input="calculateBMI" />
            </div>
            <div class="physical-item">
              <label>BMI:</label>
              <input type="number" :value="modalPhysicalExam.bmi" readonly />
            </div>
            <div class="physical-item">
              <label>BP Systolic:</label>
              <input type="number" v-model="modalPhysicalExam.bp_sys" />
            </div>
            <div class="physical-item">
              <label>BP Diastolic:</label>
              <input type="number" v-model="modalPhysicalExam.bp_dia" />
            </div>
            <div class="physical-item">
              <label>Pulse (bpm):</label>
              <input type="number" v-model="modalPhysicalExam.pulse_bpm" />
            </div>
            <div class="physical-item">
              <label>Blood Group:</label>
              <select v-model="modalPhysicalExam.blood_group">
                <option>A+</option>
                <option>A-</option>
                <option>B+</option>
                <option>B-</option>
                <option>AB+</option>
                <option>AB-</option>
                <option>O+</option>
                <option>O-</option>
                <option>Unknown</option>
              </select>
            </div>
            <div class="physical-item">
              <label>Distant Vision (R/L/B):</label>
              <input type="text" placeholder="R" v-model="modalPhysicalExam.dist_uncorr_r" />
              <input type="text" placeholder="L" v-model="modalPhysicalExam.dist_uncorr_l" />
              <input type="text" placeholder="B" v-model="modalPhysicalExam.dist_uncorr_b" />
            </div>
            <div class="physical-item">
              <label>Distant Vision Corrected (R/L/B):</label>
              <input type="text" placeholder="R" v-model="modalPhysicalExam.dist_corr_r" />
              <input type="text" placeholder="L" v-model="modalPhysicalExam.dist_corr_l" />
              <input type="text" placeholder="B" v-model="modalPhysicalExam.dist_corr_b" />
            </div>
            <div class="physical-item">
              <label>Near Vision (R/L/B):</label>
              <input type="text" placeholder="R" v-model="modalPhysicalExam.near_uncorr_r" />
              <input type="text" placeholder="L" v-model="modalPhysicalExam.near_uncorr_l" />
              <input type="text" placeholder="B" v-model="modalPhysicalExam.near_uncorr_b" />
            </div>
            <div class="physical-item">
              <label>Near Vision Corrected (R/L/B):</label>
              <input type="text" placeholder="R" v-model="modalPhysicalExam.near_corr_r" />
              <input type="text" placeholder="L" v-model="modalPhysicalExam.near_corr_l" />
              <input type="text" placeholder="B" v-model="modalPhysicalExam.near_corr_b" />
            </div>
            <div class="physical-item">
              <label>Colour Vision:</label>
              <input type="text" v-model="modalPhysicalExam.colour_vision" />
            </div>
          </div>
          <button type="submit">Save Changes</button>
          <button type="button" @click="closeModal">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Modal 2: Edit physical_exams_2 -->
    <div v-if="isModal2Open" class="modal-overlay" @click="closeModal2">
      <div class="modal-content" @click.stop style="overflow-y:auto; max-height:90vh;">
        <h3>Edit Body System Exam</h3>
        <form @submit.prevent="submitPhysicalExam2">
          <div class="physical-grid">
            <div v-for="field in bodySystemFields" :key="field.key" class="physical-item">
              <label>{{ field.label }}:</label>
              <select v-model="modalPhysicalExam2[field.key]">
                <option>Normal</option>
                <option>Abnormal</option>
                <option>Not examined</option>
              </select>
              <textarea v-model="modalPhysicalExam2[field.key + '_details_abnormality']" placeholder="Details (if abnormal)" style="width:100%; height:50px;" :disabled="modalPhysicalExam2[field.key] !== 'Abnormal'"></textarea>
            </div>
          </div>
          <button type="submit">Save Changes</button>
          <button type="button" @click="closeModal2">Cancel</button>
        </form>
      </div>
    </div>

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
      physicalExam: {},
      physicalExam2: {},
      isModalOpen: false,
      isModal2Open: false,
      modalPhysicalExam: {},
      modalPhysicalExam2: {},
      bodySystemFields: [
        { key: 'head', label: 'Head' },
        { key: 'eyes', label: 'Eyes' },
        { key: 'ears_and_drums', label: 'Ears and Drums' },
        { key: 'hearing', label: 'Hearing' },
        { key: 'nose_and_sinuses', label: 'Nose and Sinuses' },
        { key: 'mouth_teeth_throat', label: 'Mouth, Teeth, Throat' },
        { key: 'neck_and_thyroid', label: 'Neck and Thyroid' },
        { key: 'chest_and_lungs', label: 'Chest and Lungs' },
        { key: 'breasts', label: 'Breasts' },
        { key: 'heart', label: 'Heart' },
        { key: 'peripheral_arteries', label: 'Peripheral Arteries' },
        { key: 'peripheral_veins', label: 'Peripheral Veins' },
        { key: 'abdomen', label: 'Abdomen' },
        { key: 'hernia_orifices', label: 'Hernia Orifices' },
        { key: 'genitalia', label: 'Genitalia' },
        { key: 'rectal_examination', label: 'Rectal Examination' },
        { key: 'upper_limbs', label: 'Upper Limbs' },
        { key: 'lower_limbs', label: 'Lower Limbs' },
        { key: 'spine', label: 'Spine' },
        { key: 'skin', label: 'Skin' },
        { key: 'lymph_nodes', label: 'Lymph Nodes' },
        { key: 'neurological', label: 'Neurological' },
        { key: 'psychiatric', label: 'Psychiatric' },
      ]
    };
  },
  mounted() {
    this.fetchPhysicalExam();
    this.fetchPhysicalExam2();
  },
  computed: {
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

    calculateBMI() {
      if (!this.modalPhysicalExam) this.modalPhysicalExam = {};
      const w = parseFloat(this.modalPhysicalExam.weight_kg);
      const h = parseFloat(this.modalPhysicalExam.height_m);
      if (Number.isFinite(w) && Number.isFinite(h) && h > 0) {
        this.modalPhysicalExam.bmi = +(w / (h * h)).toFixed(1);
      } else {
        this.modalPhysicalExam.bmi = null;
      }
    },

    fetchPhysicalExam() {
      // const staffEmail = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/physical-exams/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
            this.physicalExam = Array.isArray(data) ? data[0] || {} : data;
            // console.log("physicalExam: ", this.physicalExam);
        })
        .catch(err => console.error(err));

    },
    fetchPhysicalExam2() {
      // const staffEmail = JSON.parse(localStorage.getItem('user_info')).email.replace(/\./g, 'XYZ');
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/physical-exams-2/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;

        return res.json();
      })
      .then(data => {
          this.physicalExam2 = Array.isArray(data) ? data[0] || {} : data;
          // console.log("physicalExam2: ", this.physicalExam2);
      })
      .catch(err => console.error(err));

    },
    openModal() {
      const defaults = {
        weight_kg: null, height_m: null, bmi: null,
        bp_sys: null, bp_dia: null, pulse_bpm: null,
        blood_group: 'Unknown',
        dist_uncorr_r: null, dist_uncorr_l: null, dist_uncorr_b: null,
        dist_corr_r: null,   dist_corr_l: null,   dist_corr_b: null,
        near_uncorr_r: null, near_uncorr_l: null, near_uncorr_b: null,
        near_corr_r: null,   near_corr_l: null,   near_corr_b: null,
        colour_vision: null
      };
      this.modalPhysicalExam = { ...defaults, ...(this.physicalExam || {}) };
      this.calculateBMI();
      this.isModalOpen = true;
    },
    closeModal() {
      this.isModalOpen = false;
    },
    openModal2() {
      this.modalPhysicalExam2 = { ...this.physicalExam2 };
      this.isModal2Open = true;
    },
    closeModal2() {
      this.isModal2Open = false;
    },
    submitPhysicalExam() {
      const updated_by = JSON.parse(localStorage.getItem('user_info')).email;
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/physical-exams/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...this.modalPhysicalExam,
          updated_by
        })
      })
        .then(res => res.json())
        .then(() => {
          this.fetchPhysicalExam();
          this.closeModal();
        })
        .catch(err => console.error(err));
    },
    submitPhysicalExam2() {
      const updated_by = JSON.parse(localStorage.getItem('user_info')).email;
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/physical-exams-2/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...this.modalPhysicalExam2,
          updated_by
        })
      })
        .then(res => res.json())
        .then(() => {
          this.fetchPhysicalExam2();
          this.closeModal2();
        })
        .catch(err => console.error(err));
    }
  }
};
</script>

<style scoped>
.physical-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.physical-item {
  background: #f8f8f8;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #ddd;
}
</style>
