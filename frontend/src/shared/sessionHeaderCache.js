// small in-memory cache
const _cache = new Map();

import cfg from '@/apiConfig';

export async function getSessionHeader(sessionId) {
  const baseURL = cfg.API_BASE_URL;
  if (_cache.has(sessionId)) return _cache.get(sessionId);

  const r = await fetch(`${baseURL}/session-header/${sessionId}`, {
    headers: { Authorization: `Bearer ${localStorage.getItem('jwt_token')}` }
  });
  if (!r.ok) throw new Error('Failed to fetch session header');
  const data = await r.json();
  _cache.set(sessionId, data);
  return data;
}

export function setSessionHeader(sessionId, header) {
  _cache.set(sessionId, header);
}

export function invalidateSessionHeader(sessionId) { _cache.delete(sessionId); }
