<template>
  <div>
    <h2>Occupational History</h2>

    <!-- Unified Modal for Add/Edit -->
    <div v-if="isModalOpen" class="modal-overlay-vertical" @click="closeModal">
      <div class="modal-content-vertical" @click.stop>
        <h3>{{ editingHistory ? 'Edit Occupational History' : 'Add New Occupational History' }}</h3>
        <form @submit.prevent="submitEntry">
          <label for="year">Year:</label>
          <input type="number" v-model="modalHistory.year" required /><br>

          <label for="company">Company:</label>
          <input type="text" v-model="modalHistory.company" required /><br>

          <label for="location">Location:</label>
          <input type="text" v-model="modalHistory.location" required /><br>

          <label for="job_title">Job Title:</label>
          <input type="text" v-model="modalHistory.job_title" required /><br>

          <label for="nature_of_work">Nature of Work:</label>
          <input type="text" v-model="modalHistory.nature_of_work" required /><br>

          <button type="submit">{{ editingHistory ? 'Save Changes' : 'Add Entry' }}</button>
          <button type="button" @click="closeModal">Cancel</button>
        </form>
      </div>
    </div>

    <div>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Year</th>
            <th>Company</th>
            <th>Location</th>
            <th>Job Title</th>
            <th>Nature of Work</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>

          <tr v-if="!occupationalHistory.length">
            <td colspan="6" class="empty">No occupational history record.</td>
          </tr>

          <template v-if="occupationalHistory.length">
            <tr v-for="(history, index) in occupationalHistory" :key="index">
              <td>{{ index + 1 }}</td>
              <td>{{ history.year ?? '-' }}</td>
              <td>{{ history.company ?? '-' }}</td>
              <td>{{ history.location ?? '-' }}</td>
              <td>{{ history.job_title ?? '-' }}</td>
              <td>{{ history.nature_of_work ?? '-' }}</td>
              <td>
                <button @click="openEditModal(history)">Edit</button>
                <button class="danger" @click="deleteHistory(history)">Delete</button>
              </td>
            </tr>
          </template>

        </tbody>
      </table>
      <button @click="openAddModal">Add New Entry</button>
    </div>

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
      occupationalHistory: [],
      isModalOpen: false,
      editingHistory: null, // null if adding new entry
      // the object bound to the modal inputs
      modalHistory: { 
        year: null,
        company: '',
        location: '',
        job_title: '',
        nature_of_work: ''
      }
    }
  },

  mounted() {
    this.fetchOccupationalHistory();
  },

  methods: {
    fetchOccupationalHistory() {
      const userInfo = localStorage.getItem('user_info');
      if (!userInfo) return;
      // const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      var staffEmailInView = JSON.parse(localStorage.getItem('user_info')).email || '';
      if (JSON.parse(localStorage.getItem('user_info')).role === 'admin') {
        staffEmailInView = this.$route.params.staffEmail || '';
      }
      const staffEmailXYZ = staffEmailInView.replace(/\./g, 'XYZ');

      fetch(`${this.baseUrl}/occupational-history/${staffEmailXYZ}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(res => {
          if (handleUnauthorized(res)) return;

          return res.json();
        })
        .then(data => {
          this.occupationalHistory = data;
          // console.log("Occupational History: ", this.occupationalHistory);
        })
        .catch(err => {
          console.error(err);
          this.occupationalHistory = [];
        });
    },

    // Open modal for adding new entry
    openAddModal() {
      this.editingHistory = null;
      this.modalHistory = { year: null, company: '', location: '', job_title: '', nature_of_work: '' };
      this.isModalOpen = true;
    },

    // Open modal for editing an existing entry
    openEditModal(history) {
      this.editingHistory = history;
      this.modalHistory = { ...history }; // clone to avoid editing table directly
      this.isModalOpen = true;
    },

    closeModal() {
      this.isModalOpen = false;
      this.editingHistory = null;
      this.modalHistory = { year: null, company: '', location: '', job_title: '', nature_of_work: '' };
    },

    // Unified submit handler for add/edit
    submitEntry() {
      const userInfo = localStorage.getItem('user_info');
      const token = localStorage.getItem('jwt_token');
      if (!userInfo || !token) return alert('User not logged in');

      const staffEmailInView = this.$route.params.staffEmail || JSON.parse(localStorage.getItem('user_info')).email || '';
      // const staffEmail = JSON.parse(userInfo).email;
      const staffEmail = staffEmailInView;

      // construct payload with top-level keys (no array wrapper)
      const payload = {
        staff_email: staffEmail,
        year: this.modalHistory.year,
        company: this.modalHistory.company,
        location: this.modalHistory.location,
        job_title: this.modalHistory.job_title,
        nature_of_work: this.modalHistory.nature_of_work
      };

      if (this.editingHistory) {
        // EDIT
        const url = `${this.baseUrl}/occupational-history/edit/${this.editingHistory.oh_id}`;
        fetch(url, {
          method: 'PUT',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(() => {
            // update the table locally
            const index = this.occupationalHistory.findIndex(h => h.oh_id === this.editingHistory.oh_id);
            if (index !== -1) this.occupationalHistory.splice(index, 1, { ...payload, oh_id: this.editingHistory.oh_id });
            this.closeModal();
          })
          .catch(err => console.error(err));
      } else {
        // ADD
        fetch(`${this.baseUrl}/occupational-history/add`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(() => {
            this.closeModal();
            this.fetchOccupationalHistory();
          })
          .catch(err => console.error(err));
      }
    },

    deleteHistory(history) {
      const isConfirmed = window.confirm('Are you sure you want to delete this history?');
      if (!isConfirmed) return;

      fetch(`${this.baseUrl}/occupational-history/delete/${history.oh_id}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
      })
        .then(() => {
          alert('History deleted successfully');
          this.occupationalHistory = this.occupationalHistory.filter(h => h.oh_id !== history.oh_id);
        })
        .catch(err => console.error(err));
    }
  }
}
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
</style>