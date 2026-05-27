// src/shared/dateFormat.js

/**
 * Format a timestamp or ISO string to "DD MMM YYYY, HH:mm" (e.g. "29 Oct 2025, 14:45")
 * @param {string|number|Date} value
 * @param {object} options - Optional Intl.DateTimeFormat options
 * @returns {string}
 */
export function formatDate(value, options = {}) {
  if (!value) return '-'

  const date = value instanceof Date ? value : new Date(value)
  if (isNaN(date)) return '-'

  return new Intl.DateTimeFormat('en-MY', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    ...options,
  }).format(date)
}

// date-only version.
export function formatDateShort(value) {
  return formatDate(value, { hour: undefined, minute: undefined })
}
