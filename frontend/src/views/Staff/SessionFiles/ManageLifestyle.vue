<template>
  <div>
    <h2>Lifestyle</h2>

    <!-- Edit Lifestyle Modal -->
    <teleport to="body">
      <div v-if="isModalOpen" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <h3>Edit Lifestyle</h3>
          <form @submit.prevent="submitLifestyle">

            <label>Smoking Habit:</label>
            <select v-model="modalLifestyle.smoking_habit">
              <option value="never smoked">Never Smoked</option>
              <option value="ex-smoker">Ex-Smoker</option>
              <option value="current smoker">Current Smoker</option>
            </select><br>

            <label>Years Smoked:</label> 
            <input type="number" v-model.number="modalLifestyle.years_smoked"
                :disabled="modalLifestyle.smoking_habit === 'never smoked'"
                :required="modalLifestyle.smoking_habit !== 'never smoked'" /><br>

            <label>Amount Smoked per Day:</label>
            <input type="number" step="0.01" v-model.number="modalLifestyle.amount_smoke_day"
                :disabled="modalLifestyle.smoking_habit === 'never smoked'"
                :required="modalLifestyle.smoking_habit !== 'never smoked'" /><br>

            <div>
            <label>Date Stopped:</label>
            <!-- <input type="date" v-model="modalLifestyle.date_stopped"
                :disabled="modalLifestyle.smoking_habit === 'never smoked' || modalLifestyle.smoking_habit === 'current smoker'"
                :required="modalLifestyle.smoking_habit === 'ex-smoker'" /> -->
            <VueDatePicker 
                  v-model="modalLifestyle.date_stopped"
                  :max-date="new Date()"
                  :year-range="[1900, new Date().getFullYear()]"
                  auto-apply
                  :enable-time-picker="false"
                  :disabled="modalLifestyle.smoking_habit === 'never smoked' || modalLifestyle.smoking_habit === 'current smoker'"
                  :required="modalLifestyle.smoking_habit === 'ex-smoker'"
            />
            </div>
            <br>

            <!-- <label>Alcohol Drink:</label> -->
            <label>Do you drink alcohol?</label>
            <select v-model="modalLifestyle.alcohol_drink">
              <option value="Y">Yes</option>
              <option value="N">No</option>
            </select><br>

            <!-- <label>Drinks per Week:</label> -->
            <label>How many drinks per typical week? 
              <small><i>
                (Note: 1 drink equivalent to 1 glass wine / 7 oz or half pint beer / 1 oz or single measure spirits)
              </i></small> 
            </label><br>
            <input type="number" v-model.number="modalLifestyle.drink_per_week"
                  :disabled="modalLifestyle.alcohol_drink !== 'Y'"
                  :required="modalLifestyle.alcohol_drink === 'Y'" /><br>

            <!-- <label>Taking Prescribed Drugs:</label> -->
            <label>Are you taking or have you taken drugs other than bought from a chemist or
              prescribed by a doctor to treat an illness?
            </label>
            <select v-model="modalLifestyle.taking_prescribed_drugs">
              <option value="Y">Yes</option>
              <option value="N">No</option>
            </select><br>

            <label>Details: <small><i>(if yes, give details below)</i></small></label>
            <textarea v-model="modalLifestyle.drug_detail"></textarea><br>

            <label>
              Declaration Consent:
              <small><i>(to be signed in the presence of the examining doctor)</i></small>
            </label>
            <p>I hereby certify the above information is correct. 
              I also understand that voluntary non-disclosure of any of Information required above is 
              an offence and disciplinary action may be taken against me. 
              I further agree to give consent to the examining Approved Medical 
              Examiner to disclose the results of this medical questionnaire and examination 
              to authorised ATB Occupational Health Doctor/Advisor, for the purpose of 
              verification of my fitness to work status.
            </p>
            <input type="checkbox" v-model="modalLifestyle.declaration_consent" /><br>

            <label>Consent Signer Name:</label>
            <input type="text" v-model="modalLifestyle.consent_signer_name"
                  :disabled="!modalLifestyle.declaration_consent"
                  :required="modalLifestyle.declaration_consent" /><br>

            <label>Consent Signer Date:</label>
            <input type="date" v-model="modalLifestyle.consent_signer_date"
                  :disabled="!modalLifestyle.declaration_consent"
                  :required="modalLifestyle.declaration_consent" /><br>

            <button type="submit">Save Changes</button>
            <button type="button" @click="closeModal">Cancel</button>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Display Lifestyle Info -->
    <div class="lifestyle-box">
      <div><strong>Smoking Habit:</strong> {{ lifestyle.smoking_habit ? lifestyle.smoking_habit.charAt(0).toUpperCase() + lifestyle.smoking_habit.slice(1) : '-' }}</div>
      <div v-if="lifestyle.smoking_habit !== 'never smoked'"><strong>Years Smoked:</strong> {{ lifestyle.years_smoked || '-' }}</div>
      <div v-if="lifestyle.smoking_habit === 'current smoker'"><strong>Amount Smoked/Day:</strong> {{ lifestyle.amount_smoke_day || '-' }}</div>
      <div v-if="lifestyle.smoking_habit === 'ex-smoker'"><strong>Date Stopped:</strong> {{ lifestyle.date_stopped || '-' }}</div>
        <br>
      <div><strong>Alcohol Drink:</strong> {{ lifestyle.alcohol_drink === 'Y' ? 'Yes' : 'No' }}</div>
      <div v-if="lifestyle.alcohol_drink === 'Y'"><strong>Drinks per Week:</strong> {{ lifestyle.drink_per_week || '-' }}</div>
        <br>
      <div><strong>Prescribed Drugs:</strong> {{ lifestyle.taking_prescribed_drugs === 'Y' ? 'Yes' : 'No' }}</div>

      <div class="inv-box" style="margin-bottom: 0%;">
        <strong>Details: </strong> <small><i>(to be filled by OHD)</i></small>
        <p> {{ lifestyle.drug_detail || '-' }} </p>
      </div>
    </div>
      
    <br>
    <div><strong> Declaration:</strong>
      <p>I hereby certify the above information is correct. 
          I also understand that voluntary non-disclosure of any of Information required above is 
          an offence and disciplinary action may be taken against me. 
          I further agree to give consent to the examining Approved Medical 
          Examiner to disclose the results of this medical questionnaire and examination 
          to authorised ATB Occupational Health Doctor/Advisor, for the purpose of 
          verification of my fitness to work status.
      </p>
    </div>
      <br>
    <div><strong>Declaration Consent:</strong> {{ lifestyle.declaration_consent ? 'Signed Digitally' : 'Unsigned' }}</div>
    <div v-if="lifestyle.declaration_consent"><strong>Name:</strong> {{ lifestyle.consent_signer_name || '-' }}</div>
    <div v-if="lifestyle.declaration_consent"><strong>Date:</strong> {{ formatDateShort(lifestyle.consent_signer_date) || '-' }}</div>   

    <button :disabled="!accessCheck('edit')" class="action-btn" @click="openModal">Edit Lifestyle</button>
    <!-- <button @click="$router.push('/dashboard')" style="margin-left:1rem;">Back</button> -->
  </div>
</template>

<script>
import { canSession } from '@/shared/sessionAcl';
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { formatDateShort } from '@/shared/dateFormat';
import { VueDatePicker } from '@vuepic/vue-datepicker';

export default {
  components: { VueDatePicker },
  inject: {
    currentSessionHeader: { from: 'currentSessionHeader', default: () => () => null },
    refreshSessionHeader: { from: 'refreshSessionHeader', default: () => async () => {} },
  },
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      lifestyle: {},
      modalLifestyle: {},
      isModalOpen: false
    };
  },
  mounted() {
    this.fetchLifestyle();
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
    formatDateShort,
    // Access control wrapper
    accessCheck(action) {
      let role = null;
      try {
        role = JSON.parse(localStorage.getItem('user_info'))?.role || 'staff';
      } catch (e) { /* ignore */ }
      return canSession(role, this.sessionStatus, action);
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
    fetchLifestyle() {
      const staffSessionID = this.$route.params.sessionId || '';
      fetch(`${this.baseUrl}/lifestyle/${staffSessionID}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;

        return res.json();
      })
      .then(data => {
        const lifestyleData = Array.isArray(data) ? data[0] || {} : data;
        lifestyleData.declaration_consent = this.asBool(lifestyleData.declaration_consent);

        this.lifestyle = lifestyleData;
        // console.log("Lifestyle: ", this.lifestyle);
      })
      .catch(err => console.error(err));
    },

    openModal() {
      this.modalLifestyle = { 
        ...this.lifestyle, 
        declaration_consent: this.asBool(this.lifestyle.declaration_consent),
      };
      this.isModalOpen = true;
    },
    closeModal() { this.isModalOpen = false; },
    
    submitLifestyle() {
      const staffSessionID = this.$route.params.sessionId || '';
      const payload = { ...this.modalLifestyle };

      if (payload.smoking_habit === 'never smoked') {
        payload.years_smoked = null;
        payload.amount_smoke_day = null;
        payload.date_stopped = null;
      } else if (payload.smoking_habit === 'current smoker') {
        payload.date_stopped = null;
      }

      if (payload.alcohol_drink !== 'Y') payload.drink_per_week = null;

      if (!payload.declaration_consent) {
        payload.consent_signer_name = '';
        payload.consent_signer_date = null;
      }

      payload.declaration_consent = this.asBool(payload.declaration_consent) ? 1 : 0;
      payload.updated_by = JSON.parse(localStorage.getItem('user_info'))?.email || null;

      fetch(`${this.baseUrl}/lifestyle/edit/${staffSessionID}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(() => {
        this.isModalOpen = false;
        this.fetchLifestyle();
      })
      .catch(err => console.error(err));
    }
  }
};
</script>


<style scoped>
/* Form layout */
.form-grid {
  display:grid;
  grid-template-columns: repeat(2, minmax(220px, 1fr));
  gap: 12px 16px;
  margin-top: 8px;
}
.field { display:flex; flex-direction:column; gap:6px; }
.field label { font-size: 13px; color:#4a5568; }
.field input,
.field select {
  border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px;
  font-size:14px; outline:none;
}
.field input.readonly {
  background-color:#f0f0f0; cursor:not-allowed;
}
.span-2 { grid-column: span 2; }
</style>
