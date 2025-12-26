/**
 * API configuration
 * Returns the correct API base URL based on environment
 */

export const getApiUrl = () => {
  // Use VITE_BACKEND_URL if set (for production)
  if (import.meta.env.VITE_BACKEND_URL) {
    return import.meta.env.VITE_BACKEND_URL
  }

  // In development without explicit backend URL, use empty string (proxy handles it)
  return ''
}

export const API_BASE_URL = getApiUrl()

/**
 * Build a full API endpoint URL
 * @param {string} path - API path (e.g., '/api/videos')
 * @returns {string} Full URL
 */
export const buildApiUrl = (path) => {
  const base = getApiUrl()
  // Ensure path starts with /
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return base ? `${base}${normalizedPath}` : normalizedPath
}
