// src/shared/handleUnauthorized.js
import router from '@/router';

export function handleUnauthorized(res) {
  if (res.status === 401) {
    // clear all session data
    localStorage.clear();

    // call window logout event
    window.dispatchEvent(new Event('user-logout'));

    // notify the user
    alert('Your session has expired. Please log in again.');

    // navigate to login page
    router.push({ name: 'Login' });

    return true; // indicates we handled it
  }
  return false; // not handled, continue normally
}
