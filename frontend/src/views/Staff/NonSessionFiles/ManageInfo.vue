<template>
  <div>
    <h2>Staff Personal Information</h2>

    <button class="action-btn" @click="openModal">Edit</button>

    <!-- Display view -->
    <table class="pretty">
      <tbody>
        <tr><td><b>Full Name:</b></td> <td>{{ staffData.staff_name || '-' }}</td></tr>
        <tr><td><b>Email:</b></td> <td>{{ staffData.staff_email || '-' }}</td></tr>
        <tr><td><b>Last Updated at:</b></td> <td>{{ formatDate(staffData.updated_at) || '-' }}</td></tr>
        <tr><td><b>Marital Status:</b></td> <td>{{ staffData.marital_status ? staffData.marital_status.charAt(0).toUpperCase() + staffData.marital_status.slice(1) : '-' }}</td></tr>
        <tr><td><b>Gender:</b></td> <td>{{ staffData.sex === 'M' ? 'Male' : staffData.sex === 'F' ? 'Female' : '-' }}</td></tr>
        <tr><td><b>Date of Birth:</b></td> <td>{{ formatDateShort(staffData.date_of_birth) || '-' }}</td></tr>
        <tr><td><b>Year of Born:</b></td> <td>{{ staffData.year_of_born ?? '-' }}</td></tr>
        <tr><td><b>Age:</b></td> <td>{{ staffData.age ?? '-' }}</td></tr>
        <tr><td><b>IC Passport:</b></td> <td>{{ staffData.ic_passport || '-' }}</td></tr>
        <tr><td><b>Nationality:</b></td> <td>{{ staffData.nationality || '-' }}</td></tr>
        <tr><td><b>Department:</b></td> <td>{{ staffData.department || '-' }}</td></tr>
        <tr><td><b>Job Title / Position:</b></td> <td>{{ staffData.job_title || '-' }}</td></tr>
        <tr><td><b>Staff No:</b></td> <td>{{ staffData.staff_no || '-' }}</td></tr>
        <tr><td><b>Phone No:</b></td> <td>{{ staffData.phone_no || '-' }}</td></tr>
        <tr><td><b>Address:</b></td> <td>{{ staffData.address || '-' }}</td></tr>
        <tr><td><b>Personal Doctor Email:</b></td> <td>{{ staffData.personal_doctor_email || '-' }}</td></tr>
        <tr><td><b>Doctor's Phone No:</b></td> <td>{{ staffData.doctor_phone_no || '-' }}</td></tr>
        <tr><td><b>Reason for Examination:</b></td> <td>{{ staffData.reason_for_examination || '-' }}</td></tr>
        <tr><td><b>Date of This (Current) Assessment:</b></td> <td>{{ formatDateShort(staffData.date_of_this_assessment) || '-' }}</td></tr>
        <tr><td><b>Date of Last Assessment:</b></td> <td>{{ formatDateShort(staffData.date_of_last_assessment) || '-' }}</td></tr>
      </tbody>
    </table>

    <!-- <button @click="$router.back()" style="margin-left:1rem;">Back</button> -->

    <!-- Modal: Edit staff info -->
    <div v-if="isModalOpen" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h3>Edit Staff Personal Information</h3>
        <form @submit.prevent="submitStaffEdit">
          <div class="form-grid">
            <div class="field">
              <label>Full Name*</label>
              <input type="text" v-model="modalStaff.staff_name" required 
                @input="modalStaff.staff_name = $event.target.value.toUpperCase()"
              />
            </div>

            <div class="field">
              <label>Marital Status*</label>
              <select style="margin-top: 0.5px" v-model="modalStaff.marital_status" required>
                <option value="single">SINGLE</option>
                <option value="married">MARRIED</option>
                <option value="divorced">DIVORCED</option>
                <!-- <option value="cohabiting">Cohabiting</option> -->
              </select>
            </div>

            <div class="field">
              <label>Gender*</label>
              <select style="margin-top: 0.5px" v-model="modalStaff.sex">
                <option value="M">MALE</option>
                <option value="F">FEMALE</option>
              </select>
            </div>

            <div class="field">
              <label>Date of Birth*</label>
              <VueDatePicker 
                  v-model="modalStaff.date_of_birth"
                  :max-date="new Date()"
                  :year-range="[1900, new Date().getFullYear()]"
                  auto-apply
                  :enable-time-picker="false"
              />
            </div>

            <div class="field">
              <label>Year of Born*</label>
              <input type="number" v-model.number="modalStaff.year_of_born" 
                @input="syncAgeFromYear" 
              />
            </div>

            <div class="field">
              <label>Age*</label>
              <input type="number" v-model.number="modalStaff.age" />
            </div>

            <div class="field">
              <label>IC / Passport*<small class="mini-ts">- without dash (-)</small></label>
              <input type="text" v-model="modalStaff.ic_passport" 
                @input="modalStaff.ic_passport = modalStaff.ic_passport.replace(/[^a-zA-Z0-9]/g, '').toUpperCase()" 
              />

            </div>

            <div class="field">
              <label>Nationality</label>
              <input type="text" v-model="modalStaff.nationality" required 
                @input="modalStaff.nationality = $event.target.value.toUpperCase()"
              />
            </div>

            <div class="field">
              <label>Department</label>
              <select 
                style="margin-top: 0.5px" 
                v-model="modalStaff.department" 
                @change="modalStaff.job_title = ''"
                required
              >
                <option value="OPS-A">OPS-A</option>
                <option value="OPS-B">OPS-B</option>
                <option value="OPS-C">OPS-C</option>
                <option value="OPS-D">OPS-D</option>
                <option value="OPS-Normal">OPS-Normal</option>
                <option value="E&M">E&M</option>
                <option value="HSE">HSE</option>
                <option value="CS-Comm">CS & Commercial</option>
                <option value="Finance">Finance</option>
                <option value="Procurement">Procurement</option>
                <option value="HR">HR</option>
                <option value="IT">IT</option>
                <option value="MO">MO</option>
              </select>
            </div>

            <!-- <div class="field">
              <label>Job Title / Position</label>
              <input type="text" v-model="modalStaff.job_title" />
            </div> -->
            <div class="field">
              <label>Job Title / Position</label>
              
              <select v-model="modalStaff.job_title" required :disabled="!modalStaff.department">
                <option disabled value="">
                  {{ modalStaff.department ? 'Select Position' : 'Select Department First' }}
                </option>
                
                <option v-for="job in availableJobTitles" :key="job" :value="job">
                  {{ job }}
                </option>
                
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="field">
              <label>Staff No</label>
              <input type="text" v-model="modalStaff.staff_no" required 
                @input="modalStaff.staff_no = modalStaff.staff_no.replace(/[^a-zA-Z0-9]/g, '').toUpperCase()" 
              />
            </div>

            <div class="field">
              <label>Phone No</label>
              <input type="text" v-model="modalStaff.phone_no" required
                @input="modalStaff.phone_no = modalStaff.phone_no.replace(/[^0-9]/g, '')" 
              />
            </div>

            <div class="field span-2">
              <label>Address</label>
              <input type="text" v-model="modalStaff.address" required @input="modalStaff.address = $event.target.value.toUpperCase() " />
            </div>

            <div class="field">
              <label>Personal Doctor Email</label>
              <input type="text" v-model="modalStaff.personal_doctor_email" readonly class="readonly" />
            </div>

            <div class="field">
              <label>Doctor's Phone No</label>
              <input type="text" v-model="modalStaff.doctor_phone_no" readonly class="readonly" />
            </div>

            <div class="field">
              <label>Date of This (Current) Assessment</label>
              <input type="text" v-model="modalStaff.date_of_this_assessment" readonly class="readonly" />
            </div>

            <div class="field">
              <label>Date of Last Assessment</label>
              <input type="text" v-model="modalStaff.date_of_last_assessment" readonly class="readonly" />
            </div>

            <div class="field span-2">
              <label>Reason for Examination</label>
              <select style="margin-top: 0.5px" v-model="modalStaff.reason_for_examination" required>
                <option value="Pre-employment">Pre-employment</option>
                <option value="Periodic / Medical Surveillance">Periodic / Medical Surveillance</option>
                <!-- <option value="Medical Surveillance">Medical Surveillance</option> -->
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="submit" class="action-btn" :disabled="!isModalChanged">
              Save Changes
            </button>
            <button type="button" class="action-btn" @click="closeModal">Cancel</button>
          </div>

        </form>
      </div>
    </div>

  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import {formatDate, formatDateShort} from '@/shared/dateFormat';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  components: { VueDatePicker },
  name: 'ManageStaffInfo',
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      isModalOpen: false,
      staffData: {
        staff_uid: '',
        staff_email: '',
        staff_name: '',
        updated_at: '',
        marital_status: '',
        sex: '',
        date_of_birth: null,
        year_of_born: null,
        age: null,
        ic_passport: '',
        nationality: '',
        job_title: '',
        department: '',
        staff_no: '',
        phone_no: '',
        address: '',
        personal_doctor_email: '',
        doctor_phone_no: '',
        reason_for_examination: '',
        date_of_this_assessment: '',
        date_of_last_assessment: '',
      },
      originalStaffData: null,
      modalStaff: {}, // clone for editing
      jobMap: {
        'OPS-A': ['Operator', 'Senior Operator', 'Loading Master', 'Control Room Operator', 'Senior Control Room Operator', 'Shift Supervisor A',],
        'OPS-B': ['Operator', 'Senior Operator', 'Loading Master', 'Control Room Operator', 'Senior Control Room Operator', 'Shift Supervisor B',],
        'OPS-C': ['Operator', 'Senior Operator', 'Loading Master', 'Control Room Operator', 'Senior Control Room Operator', 'Shift Supervisor C',],
        'OPS-D': ['Operator', 'Senior Operator', 'Loading Master', 'Control Room Operator', 'Senior Control Room Operator', 'Shift Supervisor D',],
        'OPS-Normal': ['Operation Intern', 'Operations Documentation Clerk', 'OST Operator', 'Operations Support Coordinator', 'Operational Excellence Lead', 'Assistant Operations Manager', 'Operations Manager'],
        'E&M': ['Intern', 'Technician', 'Engineer', 'Maintenance Lead', 'Planner', 'E&M Manager'],
        'HSE': ['HSE Intern', 'HSE Technician', 'HSE  Officer - Sustainability', 'HSE Manager'],
        'CS-Comm': ['CS Executive', 'Commercial Manager', 'Clerk'],
        'Finance': ['Accountant', 'Finance Executive', 'Manager'],
        'Procurement': ['Procurement Executive', 'Procurement Manager'],
        'HR': ['HR Executive', 'Recruiter', 'Admin', 'HR Manager'],
        'IT': ['System Administrator', 'IT Support'],
        'MO': ['Marine Officer', 'Loading Master', 'Scheduler'],
      },
    };
  },
  computed: {
    // original "form changed" (display view didn’t use this, but keeping parity)
    isFormChanged() {
      if (!this.originalStaffData) return false;
      const fields = [
        'staff_name','marital_status','sex','date_of_birth','year_of_born','age',
        'ic_passport','nationality','job_title','department','staff_no','phone_no','address',
        'personal_doctor_email','doctor_phone_no','reason_for_examination'
      ];
      return fields.some(f => this.staffData[f] !== this.originalStaffData[f]);
    },
    // modal change detection
    isModalChanged() {
      if (!this.originalStaffData) return false;
      const fields = [
        'staff_name','marital_status','sex','date_of_birth','year_of_born','age',
        'ic_passport','nationality','job_title','department','staff_no','phone_no','address',
        'personal_doctor_email','doctor_phone_no','reason_for_examination'
      ];
      return fields.some(f => this.modalStaff[f] !== this.originalStaffData[f]);
    },
    // auto change the 'Job Title' dropdown list based on selected department
    availableJobTitles() {
      if (!this.modalStaff.department) return [];
      return this.jobMap[this.modalStaff.department] || [];
    },
  },
  // watch: {
  //   // Watcher for the year_of_born
  //   'modalStaff.year_of_born'(newYear) {
  //     if (newYear) {
  //       // Calculate the new date, keeping month/day
  //       const newDate = new Date(
  //         newYear,
  //         this.modalStaff.date_of_birth ? this.modalStaff.date_of_birth.getMonth() : 0, // Use existing month or default to Jan
  //         this.modalStaff.date_of_birth ? this.modalStaff.date_of_birth.getDate() : 1   // Use existing day or default to 1st
  //       );
        
  //       // ONLY update date_of_birth if the year has actually changed 
  //       // (This prevents the 'date_of_birth' watcher from firing unnecessarily)
  //       if (this.modalStaff.date_of_birth?.getFullYear() !== newDate.getFullYear()) {
  //            this.modalStaff.date_of_birth = newDate;
  //       }
  //     } else {
  //        this.modalStaff.date_of_birth = null;
  //     }
  //   },

  //   // Watcher for the date_of_birth
  //   'modalStaff.date_of_birth'(newDate) {
  //     if (newDate) {
  //       const newYear = newDate.getFullYear();
        
  //       // ONLY update year_of_born if it has actually changed
  //       // This stops the infinite loop
  //       if (this.modalStaff.year_of_born !== newYear) {
  //           this.modalStaff.year_of_born = newYear;
  //       }
  //     } else {
  //       this.modalStaff.year_of_born = null;
  //     }
  //   },
  // },
  mounted() {
    this.fetchStaffInfo();
  },
  methods: {
    formatDateShort, formatDate,
    fetchStaffInfo() {
      const userInfo = localStorage.getItem('user_info');
      if (!userInfo) return;

      var staffEmailInView = JSON.parse(localStorage.getItem('user_info')).email || '';
      if (JSON.parse(localStorage.getItem('user_info')).role === 'admin') {
        staffEmailInView = this.$route.params.staffEmail || '';
      }
      const staffEmailXYZ = staffEmailInView.replace(/\./g, 'XYZ');
      fetch(`${this.baseUrl}/staff/info/${encodeURIComponent(staffEmailXYZ)}`, {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
      .then(res => {
        if (handleUnauthorized(res)) return;

        return res.json();
      })
      .then(data => {
        this.staffData = data || {};
        // console.log("staffData: ", this.staffData);
        this.originalStaffData = JSON.parse(JSON.stringify(this.staffData));
      })
      .catch(err => {
        console.error('Error fetching staff info:', err);
        alert('Failed to fetch staff info.');
      });
    },

    openModal() {
      this.modalStaff = JSON.parse(JSON.stringify(this.staffData));
      this.isModalOpen = true;
    },
    closeModal() {
      this.isModalOpen = false;
      // discard edits
      this.modalStaff = JSON.parse(JSON.stringify(this.staffData));
    },

    // auto-calc Age when Year of Birth is adjusted
    syncAgeFromYear() {
      const y = Number(this.modalStaff.year_of_born);
      if (y && y > 1900) {
        const now = new Date();
        const age = now.getFullYear() - y;
        // if date_of_birth is set, refine with month/day when you want; for now keep simple
        this.modalStaff.age = age;
      }
    },

    submitStaffEdit() {
      // use the email on the record (authoritative)
      const staffEmailXYZ = 
        (this.modalStaff.staff_email || this.staffData.staff_email || '')
        .replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/staff/edit-info/${staffEmailXYZ}`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(this.modalStaff)
      })
      .then(res => res.json())
      .then(() => {
        alert('Personal info updated successfully');
        this.isModalOpen = false;
        // refresh display + baseline
        this.fetchStaffInfo();
      })
      .catch(err => {
        console.error('Error updating staff data:', err);
        alert('Failed to update data.');
      });
    }
  }
};
</script>

<style scoped>
.pretty {
  width: 100%;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.pretty td {
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
}
.pretty tr:nth-child(odd) td {
  background:#fcfdff;
}

.action-btn {
  background: #063d9b;
  color: #edf2f7;
  margin: 12px 0;
}
action-btn:disabled {
  background: #cbd5e0 !important;
  color: #2d3748 !important;
  cursor: not-allowed;
  opacity: 0.5 !important;
}

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
