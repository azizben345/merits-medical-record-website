<template>
  <div class="view-wrapper">
    <div class="view-card">
      
      <div class="card-header">
        <h2>User Details</h2>
        <span v-if="user" :class="['role-badge', role]">
          {{ role ? role.toUpperCase() : 'USER' }}
        </span>
      </div>

      <div v-if="user" class="content-body">
        
        <div v-if="role === 'doctor'" class="details-grid">
          <div class="detail-item full-width">
            <span class="label">Full Name</span>
            <span class="value">{{ user.doctor_name || '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Email Address</span>
            <span class="value">{{ user.doctor_email || '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Phone Number</span>
            <span class="value">{{ user.phone_no || '-' }}</span>
          </div>
        </div>
        
        <div v-else-if="role === 'staff'" class="staff-container">
          
          <h4 class="section-title">Identity & Job Info</h4>
          <div class="details-grid">
            <div class="detail-item">
              <span class="label">Full Name</span>
              <span class="value">{{ user.staff_name || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Email</span>
              <span class="value">{{ user.staff_email }}</span>
            </div>
            <!-- <div class="detail-item">
              <span class="label">Staff UID</span>
              <span class="value">{{ user.staff_uid }}</span>
            </div> -->
             <div class="detail-item">
              <span class="label">Staff No</span>
              <span class="value">{{ user.staff_no || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Job Title</span>
              <span class="value">{{ user.job_title || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Department</span>
              <span class="value">{{ user.department || '-' }}</span>
            </div>
             <div class="detail-item">
              <span class="label">IC / Passport</span>
              <span class="value">{{ user.ic_passport || '-' }}</span>
            </div>
          </div>

          <h4 class="section-title">Demographics</h4>
          <div class="details-grid">
            <div class="detail-item">
              <span class="label">Age</span>
              <span class="value">{{ user.age || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Sex</span>
              <span class="value">{{ user.sex || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Date of Birth</span>
              <span class="value">{{ user.date_of_birth || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Marital Status</span>
              <span class="value">{{ user.marital_status || '-' }}</span>
            </div>
             <div class="detail-item">
              <span class="label">Nationality</span>
              <span class="value">{{ user.nationality || '-' }}</span>
            </div>
          </div>

          <h4 class="section-title">Contact & Emergency</h4>
          <div class="details-grid">
            <div class="detail-item full-width">
              <span class="label">Address</span>
              <span class="value">{{ user.address || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Phone No</span>
              <span class="value">{{ user.phone_no || '-' }}</span>
            </div>
             <div class="detail-item">
              <span class="label">Personal Doctor Email</span>
              <span class="value">{{ user.personal_doctor_email || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Doctor Phone</span>
              <span class="value">{{ user.doctor_phone_no || 'N/A' }}</span>
            </div>
            <div class="detail-item full-width">
              <span class="label">Reason for Examination</span>
              <span class="value">{{ user.reason_for_examination || '-' }}</span>
            </div>
          </div>

          <h4 class="section-title">System Meta</h4>
           <div class="details-grid">
            <div class="detail-item">
              <span class="label">Created At</span>
              <span class="value">{{ formatDate(user.created_at) || '-' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Last Updated</span>
              <span class="value">{{ formatDate(user.updated_at) || '-' }}</span>
            </div>
           </div>

        </div>

        <div v-else-if="role === 'admin'" class="details-grid">
          <div class="detail-item full-width">
            <span class="label">Full Name</span>
            <span class="value">{{ user.admin_name || '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Email</span>
            <span class="value">{{ user.admin_email || '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Department</span>
            <span class="value">{{ user.department || '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Phone Number</span>
            <span class="value">{{ user.phone_no || '-' }}</span>
          </div>
        </div>
      </div>

      <div v-else class="loading-state">
        <p>Loading user data...</p>
      </div>

      <div class="button">
        <button class="ghost" @click="$router.push('/admin/manage-users')">
          Back to Manage Users
        </button>
      </div>

    </div>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
import { formatDate } from '@/shared/dateFormat';

export default {
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      user: null,
      role: '',
    };
  },
  mounted() {
    const emailXYZ = (this.$route.params.email)
      .replace(/\./g, 'XYZ')
      .replace(/\+/g, 'UVW');
    this.fetchUserInfo(emailXYZ);
  },
  methods: {
    formatDate,
    async fetchUserInfo(emailXYZ) {
      try {
        const response = await fetch(`${this.baseUrl}/user/role-based/${emailXYZ}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
                'Content-Type': 'application/json',
            },
        });

        const data = await response.json();
        if (response.ok) {
          this.user = data;
          this.role = data.role;
        } else if (handleUnauthorized(response)) {
          // return;
        } else {
          alert('Error fetching user data: ' + data.error);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to load user data');
      }
    },
  },
};
</script>

<style scoped>
/* --- Main Container --- */
.view-wrapper {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 80vh;
  padding-top: 40px;
  background-color: #f8f9fa;
  padding-bottom: 50px;
}

.view-card {
  background: #ffffff;
  width: 100%;
  max-width: 650px; /* Wider than EditUser to fit the grid */
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

/* --- Header & Badge --- */
.card-header {
  margin-bottom: 30px;
  text-align: center;
  position: relative;
}

.card-header h2 {
  margin: 0;
  color: #333;
  font-size: 1.8rem;
}

.role-badge {
  display: inline-block;
  margin-top: 10px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

/* Badge Colors */
.role-badge.admin { background-color: #e2e8f0; color: #475569; }
.role-badge.doctor { background-color: #dbeafe; color: #1e40af; }
.role-badge.staff { background-color: #dcfce7; color: #166534; }

/* --- Grid Layout System --- */
.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr; /* 2 Columns */
  gap: 20px;
  margin-bottom: 25px;
}

/* Label/Value Styling */
.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-item.full-width {
  grid-column: span 2; /* Stretch across both columns */
}

.label {
  font-size: 0.8rem;
  color: #8898aa;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 4px;
}

.value {
  font-size: 1rem;
  color: #32325d;
  font-weight: 500;
  word-break: break-word; /* Prevents long emails from overflowing */
}

/* --- Staff Section Headers --- */
.section-title {
  font-size: 0.9rem;
  color: #007bff; /* Primary Blue */
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
  margin-top: 30px;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.staff-container .section-title:first-child {
  margin-top: 0;
}

.loading-state {
  text-align: center;
  color: #888;
  padding: 40px;
}
</style>
