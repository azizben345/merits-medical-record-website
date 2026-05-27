// src/shared/acl/sessionAcl.js
export const Roles = Object.freeze({ STAFF:'staff', DOCTOR:'doctor', ADMIN:'admin' });
export const Status = Object.freeze({ DRAFT:'draft', SUBMITTED:'submitted', LOCKED:'locked' });

//action: 'view' | 'edit' | 'delete' | 'create' | 'submit' | 'lock' | 'upload_report'

export function canSession(role, status, action) {
  if (role === Roles.ADMIN) {
    // admin can do everything except editing LOCKED (better to use unlock flow)
    if (status === Status.LOCKED && (action === 'edit' || action === 'upload_report')) return false;
    return true;
  }

  if (role === Roles.STAFF) {
    if (action === 'view') return true; // can view all statuses
    if (status === Status.DRAFT && (action === 'edit')) return true;
    return false; // no submit/lock/delete for staff
  }

  if (role === Roles.DOCTOR) {
    if (action === 'view') return true; // can view all statuses
    if (status !== Status.LOCKED && (action === 'edit' || action === 'upload_report')) return true;
    // doctors do not submit/lock/delete sessions
    return false;
  }

  return false;
}

// allowed status transitions by role
export function canTransition(role, from, to) {
  if (role === Roles.ADMIN) return true; // simplest: admin controls transitions
  if (role === Roles.DOCTOR) return (from === Status.DRAFT && to === Status.SUBMITTED);
  // staff typically can’t change status
  return false;
}
