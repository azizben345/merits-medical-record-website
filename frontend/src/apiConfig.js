// src/apiConfig.js
const isLocal = typeof window !== 'undefined' &&
  /^(localhost|127\.0\.0\.1)/.test(location.hostname);

// Manual override if needed
const IS_PRODUCTION = isLocal ? false : true;

const API_BASE_URL = IS_PRODUCTION
  ? "https://darkgray-mole-938146.hostingersite.com/api"
  : "http://localhost:8000/api";

// Allow emergency override via localStorage (no rebuild needed)
const LS_KEY = 'API_BASE_URL_OVERRIDE';
const override = typeof localStorage !== 'undefined' ? localStorage.getItem(LS_KEY) : null;

export default {
  API_BASE_URL: (override && override.trim()) || API_BASE_URL,
  setOverride(url) { localStorage.setItem(LS_KEY, url); },
  clearOverride() { localStorage.removeItem(LS_KEY); }
};
