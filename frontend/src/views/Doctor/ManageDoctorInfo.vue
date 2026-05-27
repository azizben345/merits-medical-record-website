<template>
    <div>
        <h2>Doctor Information</h2>

        <div v-if="isEditing">
            <form @submit.prevent="updateDoctorInfo">
                <div>
                    <label for="doctor_name">Name:</label>
                    <input type="text" v-model="doctorData.doctor_name" required />
                </div>
                <div>
                    <label for="doctor_email">Email (readonly):</label>
                    <input type="text" v-model="doctorData.doctor_email" readonly style="background-color: #ccc; cursor: not-allowed;" />
                </div>
                <!-- <div>
                    <label for="gender">Gender:</label>
                    <select v-model="staffData.gender">
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </div> -->
                <div>
                    <label for="phone_no">Phone No:</label>
                    <input type="text" v-model="doctorData.phone_no" required />
                </div>
                <button type="submit" :disabled="!isFormChanged">Save Changes</button>
                <button type="button" @click="cancelEdit">Cancel</button>
            </form>
        </div>

        <div v-else>
            <table>
                <tbody>
                    <tr><td><b>Name:</b></td> <td>{{ doctorData.doctor_name }}</td></tr>
                    <tr><td><b>Email:</b></td> <td>{{ doctorData.doctor_email }}</td></tr>
                    <tr><td><b>Phone No:</b></td> <td>{{ doctorData.phone_no }}</td></tr>
                </tbody>
            </table>
            <button @click="editDoctorInfo">Edit</button>
            <button @click="$router.push('/dashboard')" style="margin-left: 1rem;">Back</button>
        </div>
    </div>
</template>

<script>
import cfg from '@/apiConfig';
import { handleUnauthorized } from '@/shared/handleUnauthorized';
export default {
    data() {
        return {
            baseUrl: cfg.API_BASE_URL,
            isEditing: false,
            doctorData: {
                doctor_uid: '',
                doctor_email: '',
                doctor_name: '',
                phone_no: ''
            },
            originalDoctorData: null
        };
    },
    mounted() {
        this.fetchDoctorInfo();
    },
    computed: {
        isFormChanged() {
            if (!this.originalDoctorData) return false;
            
            const fields = [
                'doctor_name',
                'phone_no'
            ];
            return fields.some(field => this.doctorData[field] !== this.originalDoctorData[field]);
        }
    },
    methods: {
        fetchDoctorInfo() {
            const userInfo = localStorage.getItem('user_info');

            if (!userInfo) return;
            const doctorEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');
            
            fetch(`${this.baseUrl}/doctor/info/${encodeURIComponent(doctorEmail)}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    this.doctorData = data;
                    // deep clone for change detection
                    this.originalDoctorData = JSON.parse(JSON.stringify(data));
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Failed to fetch doctor info.');
                });
        },
        editDoctorInfo() {
            this.isEditing = true;
        },
        cancelEdit() {
            this.isEditing = false;
            this.fetchDoctorInfo();
        },
        updateDoctorInfo() {
            const userInfo = localStorage.getItem('user_info');

            if (!userInfo) return;
            const doctorEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');

            fetch(`${this.baseUrl}/doctor/edit-info/${encodeURIComponent(doctorEmail)}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.doctorData)
            })
                .then(res => {
                    if (handleUnauthorized(res)) return;

                    return res.json();
                })
                .then(() => {
                    this.isEditing = false;
                    alert('Personal info updated successfully');
                    this.fetchDoctorInfo();
                })
                .catch(err => {
                    console.error('Error updating doctor info:', err);
                    alert('Failed to update data.');
                });
        }
    }
};

</script>
