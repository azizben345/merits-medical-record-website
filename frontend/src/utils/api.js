// src/utils/api.js
import router from '@/router';
import config from '@/apiConfig'; 

// Now we use YOUR smart URL logic
const BASE_URL = config.API_BASE_URL;

// Helper to handle requests
async function request(endpoint, options = {}) {
    const token = localStorage.getItem('jwt_token');

    const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
    };

    if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    }

    // Ensure endpoint starts with '/' if missing
    const safeEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;

    
    // USE THE IMPORTED BASE_URL HERE
    const response = await fetch(`${BASE_URL}${safeEndpoint}`, {
        ...options,
        headers,
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({})); 
        
        // THE INTERCEPTOR LOGIC
        if (response.status === 401) {
        const msg = errorData.error || '';
        if (msg.includes('Session expired') || msg.includes('expired')) {
            console.warn('Session expired. Logging out...');
            localStorage.removeItem('jwt_token');
            localStorage.removeItem('user_data');
            
            alert("Your session has expired due to inactivity. Please login again.");
            router.push('/login');
            
            throw new Error('SESSION_EXPIRED'); 
        }
        }

        const error = new Error(errorData.error || 'API Error');
        error.status = response.status;
        throw error;
    }

    return await response.json();
}

export default {
    get: (url) => request(url, { method: 'GET' }),
    post: (url, body) => request(url, { method: 'POST', body: JSON.stringify(body) }),
    put: (url, body) => request(url, { method: 'PUT', body: JSON.stringify(body) }),
    del: (url) => request(url, { method: 'DELETE' }),
};