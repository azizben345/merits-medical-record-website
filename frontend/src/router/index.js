// router/index.js
import { createRouter, createWebHashHistory } from 'vue-router';

// Universal Views
import MyDashboard from '../views/MyDashboard.vue';
import MyRegister from '../views/MyRegister.vue';
import MyLogin from '../views/MyLogin.vue';
import MyProfile from '../views/MyProfile.vue';
import ViewSession from '@/views/Staff/SessionFiles/ViewSession.vue';
import ViewNonSession from '@/views/Staff/NonSessionFiles/ViewNonSession.vue';
import AnalyticsAllPage from '@/views/Admin/StatsOverall/AnalyticsAllPage.vue';
import SetupAccountPage from '@/views/SetupAccount.vue';
import ForgotPassword from '@/views/ForgotPassword.vue';
import ResetForgotPassword from '@/views/ResetPassword.vue';

// Admin
import ManageUsers from '../views/Admin/ManageUsers.vue';
import ResetPassword from '../views/Admin/ResetPassword.vue';
import RegisterUser from '../views/Admin/RegisterUser.vue'; // new
import EditUser from '../views/Admin/EditUser.vue'; // new
import ManageSessions from '../views/Admin/ManageSessions.vue'; // new
import NonSessionCompleteness from '@/views/Admin/NonSessionCompleteness.vue';
import ViewUser from '../views/Admin/ViewUser.vue'; // new

// Staff 
import ManageStaffInfo from '../views/Staff/NonSessionFiles/ManageInfo.vue';
// import ManageStaffRecord from '../views/Staff/ManageStaffRecord.vue';
import ManageOccupationHistory from '@/views/Staff/NonSessionFiles/ManageOccupationHistory.vue';
import ManageFamilyHistory from '@/views/Staff/NonSessionFiles/ManageFamilyHistory.vue';
import AnalyticsPage from '@/views/Staff/Statistics/AnalyticsPage.vue';
// may be moved to other access
import MySessions from '@/views/Staff/SessionFiles/MySessions.vue';
import ManageMedicalHistory from '@/views/Staff/SessionFiles/ManageMedicalHistory.vue';
import ManageLifestyle from '@/views/Staff/SessionFiles/ManageLifestyle.vue';
import ManagePhysicalExam from '@/views/Staff/SessionFiles/ManagePhysicalExam.vue';
import ManageInvestigations from '@/views/Staff/SessionFiles/ManageInvestigations.vue';
import ManageFitnessCertificate from '@/views/Staff/SessionFiles/FitnessCertificate.vue';

// Doctor
import ManageDoctorInfo from '../views/Doctor/ManageDoctorInfo.vue';

const routes = [
  // Authentication Routes
  { path: '/', name: 'Login', component: MyLogin, meta: { hideSideNavbar: true} }, // login route
  { path: '/register', name: 'Register', component: MyRegister }, // register route
  { path: '/setup-account', component: SetupAccountPage },
  { path: '/forgot-password', component: ForgotPassword, meta: { hideSideNavbar: true} },
  { path: '/reset-forgot-password', component: ResetForgotPassword },

  // View Profile
  {
      path: '/profile',
      name: 'profile',
      component: MyProfile  // Map the Profile route to your Profile component
    },
  // Dashboards - Protected route
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: MyDashboard,
    meta: { requiresAuth: true } // Mark this route as requiring authentication
  },

  // Admin Routes - Protected routes
  { path: '/admin/manage-users', component: ManageUsers, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin/view-user/:email', component: ViewUser, props: true, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin/register-user', component: RegisterUser, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin/edit-user/:userId', component: EditUser, props: true, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin/manage-sessions', component: ManageSessions, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin/non-session-completeness', component: NonSessionCompleteness, meta: { requiresAuth: true, role: 'admin' } },
  // { path: '/admin/edit-record/:staffEmail', component: EditRecord, props: true, meta: { requiresAuth: true, role: 'admin' } },
  { path: '/reset-password/:userId/:userEmail', component: ResetPassword, meta: { requiresAuth: true, role: 'admin' } },

  // Staff Routes - Protected routes
  // { path: '/staff/manage-info', component: ManageStaffInfo, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-record', component: ManageStaffRecord, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-occupation-history', component: ManageOccupationHistory, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-family-history', component: ManageFamilyHistory, meta: { requiresAuth: true, role: 'staff' } },
  { path: '/staff/analytics-page', component: AnalyticsPage, meta: { requiresAuth: true, role: 'staff' } },
  // may be moved to other access
  { path: '/staff/my-sessions', component: MySessions, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-medical-history', component: ManageMedicalHistory, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-lifestyle', component: ManageLifestyle, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-physical-exams', component: ManagePhysicalExam, meta: { requiresAuth: true, role: 'staff' } },
  // { path: '/staff/manage-investigations', component: ManageInvestigations, meta: { requiresAuth: true, role: 'staff' } },

  // Doctor Routes - Protected routes
  { path: '/doctor/manage-info', component: ManageDoctorInfo, meta: { requiresAuth: true, role: 'doctor' } },
  { path: '/doctor/non-session-completeness', component: NonSessionCompleteness, meta: { requiresAuth: true, role: 'doctor' } },

  // General Routes - except 'Restricted'
  {
    path: '/analytics-all-page',
    alias: ['/admin/analytics-all-page', '/doctor/analytics-all-page'],
    component: AnalyticsAllPage,
    meta: { requiresAuth: true, rolesAllowed: ['admin', 'doctor'] }
  },
  {
    path: '/manage-sessions',
    alias: ['/admin/manage-sessions', '/doctor/manage-sessions'],
    component: ManageSessions,
    meta: { requiresAuth: true, rolesAllowed: ['admin', 'doctor'] }
  },
  {
    path: '/non-session/:staffEmail?',
    alias: ['/staff/non-session', '/admin/non-session/:staffEmail', '/doctor/non-session/:staffEmail'],
    component: ViewNonSession,
    children: [
      { path: '', redirect: { name: 'ns-staff-info' } },
      { path: 'staff-info',name: 'ns-staff-info',component: ManageStaffInfo },
      { path: 'occ-history', name: 'ns-occ-history', component: ManageOccupationHistory },
      { path: 'fam-history', name: 'ns-fam-history',component: ManageFamilyHistory },
    ],
    meta: { requiresAuth: true, rolesAllowed: ['staff', 'admin', 'doctor'] }
  },
  {
      path: '/session/:sessionId',
      alias: ['/staff/session/:sessionId', '/admin/session/:sessionId', '/doctor/session/:sessionId'],
      component: ViewSession,
      children: [
        { path: '', redirect: { name: 'session-medical-history' } },
        { path: 'medical-history',name: 'session-medical-history',component: ManageMedicalHistory },
        { path: 'physical',       name: 'session-physical',      component: ManagePhysicalExam },
        { path: 'investigations', name: 'session-investigations',component: ManageInvestigations },
        { path: 'lifestyle',      name: 'session-lifestyle',     component: ManageLifestyle },
        { path: 'ftw-certificate', name: 'session-ftw-certificate', component: ManageFitnessCertificate },
      ],
      meta: { requiresAuth: true, rolesAllowed: ['staff', 'doctor', 'admin'] },
  },
  {
    path: '/export/medical',
    name: 'export-medical',
    component: () => import('@/views/ExportMedicalRecord.vue'),
    meta: { requiresAuth: true, rolesAllowed: ['admin', 'doctor', 'staff'] }
  },
  { path: '/reset-password', component: ResetPassword } // allow users to reset their own password without role restriction
];

const router = createRouter({
  history: createWebHashHistory(), // uses hash mode
  routes
});

// Navigation Guard: Protect routes that require authentication and handle role-based access
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('jwt_token');
  const userInfoString = localStorage.getItem('user_info');
  let userRole = null;

  if (userInfoString) {
    try {
      userRole = JSON.parse(userInfoString).role || null;
    } catch (e) {
      console.error('Error parsing user_info from localStorage:', e);
      localStorage.removeItem('jwt_token');
      localStorage.removeItem('user_info');
      return next({ name: 'Login' });
    }
  }

  // // prevent visiting login/register when already authed
  // if ((to.name === 'Login' || to.name === 'Register') && isAuthenticated) {
  //   return next({ name: 'Dashboard' });
  // }

  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      return next({ name: 'Login' });
    }

    // legacy single-role check (keeps your existing admin-bypass)
    if (to.meta.role && userRole !== to.meta.role && userRole !== 'admin') {
      alert(`Access Denied: You do not have the required role (${to.meta.role}) to view this page.`);
      return next({ name: 'Dashboard' });
    }

    // multi-role check
    if (Array.isArray(to.meta.rolesAllowed) && !to.meta.rolesAllowed.includes(userRole)) {
      // if always want admin to bypass, either include 'admin' in rolesAllowed or use the code below
      // if (userRole !== 'admin') {
      alert('Access Denied: You do not have permission to view this page.');
      return next({ name: 'Dashboard' });
      // }
    }
  }

  next();
});

export default router;
