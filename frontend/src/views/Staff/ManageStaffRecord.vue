<template>
    <div>
        <h2>Staff Medical Record</h2>

        <div v-if="isEditing">
            <form @submit.prevent="updateStaffRecord">
                <div>
                    <label for="staff_name">Name:</label>
                    <input type="text" v-model="staffRecord.staff_name" required />
                </div>
                <!-- <div>
                    <label for="gender">Gender:</label>
                    <select v-model="staffData.gender">
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </div> -->
                <div>
                    <label for="blood_type">Blood Type:</label>
                    <input type="text" v-model="staffRecord.blood_type" required />
                </div>
                <div>
                    <label for="allergies">Allergies:</label>
                    <input type="text" v-model="staffRecord.allergies" />
                </div>
                <div>
                    <label for="existing_conditions">Existing Conditions:</label>
                    <input type="text" v-model="staffRecord.existing_conditions" />
                </div>
                <div>
                    <label for="medications">Medications:</label>
                    <input type="text" v-model="staffRecord.medications" />
                </div>
                <div>
                    <label for="emergency_contact_name">Emergency Contact Name:</label>
                    <input type="text" v-model="staffRecord.emergency_contact_name" required />
                </div>
                <div>
                    <label for="emergency_contact_phone_no">Emergency Contact Phone No:</label>
                    <input type="text" v-model="staffRecord.emergency_contact_phone_no" required />
                </div>
                <div>
                    <label for="emergency_contact_relationship">Emergency Contact Relationship:</label>
                    <input type="text" v-model="staffRecord.emergency_contact_relationship" required />
                </div>

                <button type="submit" :disabled="!isFormChanged">Save Changes</button>
                <button type="button" @click="cancelEdit">Cancel</button>
            </form>
        </div>

        <div v-else>
            <table>
                <tbody>
                    <tr><td><b>Name:</b></td><td>{{ staffRecord.staff_name }}</td></tr>
                    <tr><td><b>Email:</b></td><td>{{ staffRecord.staff_email }}</td></tr>
                    <tr><td><b>Last Updated at:</b></td><td>{{ staffRecord.last_updated }}</td></tr>
                    <tr><td><b>Updated By:</b></td><td>{{ staffRecord.updated_by }}</td></tr>
                    <tr><td><b>Blood Type:</b></td><td>{{ staffRecord.blood_type }}</td></tr>
                    <tr><td><b>Allergies:</b></td><td>{{ staffRecord.allergies }}</td></tr>
                    <tr><td><b>Existing Conditions:</b></td><td>{{ staffRecord.existing_conditions }}</td></tr>
                    <tr><td><b>Medications:</b></td><td>{{ staffRecord.medications }}</td></tr>
                    <tr><td><b>Emergency Contact Name:</b></td><td>{{ staffRecord.emergency_contact_name }}</td></tr>
                    <tr><td><b>Emergency Contact Phone No:</b></td><td>{{ staffRecord.emergency_contact_phone_no }}</td></tr>
                    <tr><td><b>Emergency Contact Relationship:</b></td><td>{{ staffRecord.emergency_contact_relationship }}</td></tr>
                    <tr><td><b>Doctor's Email:</b></td><td>{{ staffRecord.doctor_email }}</td></tr>
                    <tr><td><b>Doctor's Name:</b></td><td>{{ staffRecord.doctor_name }}</td></tr>
                    <tr><td><b>Doctor's Remarks:</b></td><td>{{ staffRecord.remarks }}</td></tr>
                </tbody>
            </table>
            <button @click="editStaffRecord">Edit</button>
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
            staffRecord: {
                last_updated: '',
                updated_by: '',
                staff_name: '',
                staff_email: '',
                doctor_email: '',
                doctor_name: '',
                remarks: '',
                blood_type: '',
                allergies: '',
                existing_conditions: '',
                medications: '',
                emergency_contact_name: '',
                emergency_contact_phone_no: '',
                emergency_contact_relationship: ''
            },
            isEditing: false,
            originalRecord: {} // to track original data for change detection
        };
    },
    mounted() {
        this.fetchStaffRecord();
    },
    computed: {
        isFormChanged() {
            if (!this.originalRecord) return false;
            const fieldsToCheck = [
                'staff_name', 'blood_type', 'allergies', 'existing_conditions',
                'medications', 'emergency_contact_name', 'emergency_contact_phone_no',
                'emergency_contact_relationship'
            ];
            return fieldsToCheck.some(field => this.staffRecord[field] !== this.originalRecord[field]);
        }
    },
    methods: {
        fetchStaffRecord() {
            const userInfo = localStorage.getItem('user_info');

            if (!userInfo) return;
            const staffEmail = JSON.parse(userInfo).email.replace(/\./g, 'XYZ');

            fetch(`${this.baseUrl}/staff/record/${staffEmail}`, {
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
                    this.staffRecord = data;
                    // deep clone for change detection
                    this.originalRecord = JSON.parse(JSON.stringify(data));
                })
                .catch(err => {
                    console.error('Error fetching staff record:', err);
                });
        },

        editStaffRecord() {
            this.isEditing = true;
        },
        cancelEdit() {
            this.isEditing = false;
            this.fetchStaffRecord(); 
        },

        updateStaffRecord() {
            const staffEmail = this.staffRecord.staff_email.replace(/\./g, 'XYZ');

            fetch(`${this.baseUrl}/staff/edit-record/${staffEmail}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.staffRecord)
            })
                .then(res => res.json())
                .then(() => {
                    this.isEditing = false;
                    alert('Staff record updated successfully');
                    this.fetchStaffRecord();
                })
                .catch(err => {
                    console.error('Error updating staff record:', err);
                    alert('Failed to update record.');
                });
        }
    },
    
};
</script>