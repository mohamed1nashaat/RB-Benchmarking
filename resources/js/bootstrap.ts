import axios from 'axios'

// App version for cache busting
const APP_VERSION = '2.0.0'

// Clear old cached data when app version changes
const storedVersion = localStorage.getItem('app_version')
if (storedVersion !== APP_VERSION) {
  console.log('App version updated - clearing old cached data')
  localStorage.removeItem('current_tenant_id')
  sessionStorage.removeItem('current_tenant_id')
  localStorage.setItem('app_version', APP_VERSION)
}

// Set up axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Add request interceptor to include auth token
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
  const tenantId = localStorage.getItem('current_tenant_id') || sessionStorage.getItem('current_tenant_id')
  
  console.log('axios interceptor - token:', token ? `${token.substring(0, 20)}... (length: ${token.length})` : 'null', 'tenantId:', tenantId, 'for URL:', config.url)
  
  if (token) {
    const authHeader = `Bearer ${token}`
    config.headers.Authorization = authHeader
    console.log('axios interceptor - setting Authorization header:', authHeader.substring(0, 30) + '... (full header length:', authHeader.length + ')')
  }
  
  // Add tenant ID header (skip if it's the string 'null')
  if (tenantId && tenantId !== 'null') {
    config.headers['X-Tenant-ID'] = tenantId
  }
  
  // Ensure proper headers are always set
  config.headers['Accept'] = 'application/json'
  config.headers['Content-Type'] = 'application/json'
  
  return config
})

// Add response interceptor to handle auth errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    console.log('axios response interceptor - error:', error.response?.status, 'for URL:', error.config?.url)
    
    // Only handle 401 errors for auth-related endpoints, not all endpoints
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      
      // Only clear auth and redirect for critical auth endpoints
      if (url.includes('/api/me') || url.includes('/api/auth/')) {
        console.log('axios response interceptor - clearing auth and redirecting to login')
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_token_expires')
        localStorage.removeItem('current_tenant_id')
        sessionStorage.removeItem('auth_token')
        sessionStorage.removeItem('auth_token_expires')
        sessionStorage.removeItem('current_tenant_id')
        window.location.href = '/login'
      } else {
        console.log('axios response interceptor - 401 on non-critical endpoint, not redirecting')
      }
    }
    return Promise.reject(error)
  }
)

// Make axios available globally
window.axios = axios
