<template>
  <div class="manage-users">
    <h2>Manage Staff Records</h2>

    <button @click="$router.push('/dashboard')" style="margin-bottom: 18px;">Back to Dashboard</button>

    <table>
      <thead>
        <tr>
          <th>Record ID</th>
          <th>User ID</th>
          <th>Staff UID</th>
          <th>Staff Email</th>
          <th>Staff Name</th>
          <th>Doctor Email</th>
          <th>Doctor Name</th>
          <th>Doctor Remarks</th>
          <th>Last Updated</th>
          <th>Updated By</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="record in manageRecords" :key="record.user_id">
          <td>{{ record.record_id }}</td>
          <td>{{ record.user_id }}</td>
          <td>{{ record.staff_uid }}</td>
          <td>{{ record.staff_email }}</td>
          <td>{{ record.staff_name }}</td>
          <td>{{ record.doctor_email }}</td>
          <td>{{ record.doctor_name }}</td>
          <td>{{ record.remarks }}</td>
          <td>{{ record.updated_at }}</td>
          <td>{{ record.updated_by }}</td>
          <td class="actions">
            <button @click="editRecord(record)">View Record</button>
            <button @click="clearDoctor(record.staff_email)">Clear Doctor</button>
            <button class="danger" @click="deleteRecord(record.user_id)">Delete</button>
          </td>
        </tr>
        <tr v-if="manageRecords.length === 0">
          <td colspan="10" class="text-center py-4">No records found.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
export default {
  data() {
    return {
      baseUrl: cfg.API_BASE_URL,
      manageRecords: []
    };
  },
  mounted() {
    this.fetchManageRecords();
  },
  methods: {
    fetchManageRecords() {
      fetch(`${this.baseUrl}/admin/staff-records`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
          'Content-Type': 'application/json'
        }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
          this.manageRecords = data;
        })
        .catch(error => {
          console.error('Error fetching staff records:', error);
        });
    },
    
    editRecord(record) {
      this.$router.push(`/admin/edit-record/${record.staff_email}`);
    },

    clearDoctor(staffEmail) {
      if (confirm(`Clear assigned doctor for ${staffEmail.replace(/XYZ/g, '.')}?`)) {
        const emailParam = encodeURIComponent(staffEmail);
        fetch(`${this.baseUrl}/admin/clear-doctor/${emailParam}`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
            'Content-Type': 'application/json'
          }
        })
          .then(response => {
            if (response.ok) {
              this.fetchManageRecords();
            } else {
              alert('Failed to clear assigned doctor.');
            }
          })
          .catch(error => {
            console.error('Error clearing assigned doctor:', error);
          });
      }
    },

    deleteRecord(userId) { // not yet implemented in backend
      if (confirm('Are you sure you want to delete this record?')) {
        fetch(`http://localhost:8080/api/staff/records/${userId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
          }
        })
          .then(response => {
            if (response.ok) {
              this.fetchManageRecords();
            } else {
              alert('Failed to delete the record.');
            }
          })
          .catch(error => {
            console.error('Error deleting record:', error);
          });
      }
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
</style>
