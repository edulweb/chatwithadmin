import axios from 'axios'

import { getAppBasePath } from '@/router'

export function getApiBaseUrl() {
  // 1. Injected dynamic runtime config from Laravel
  if (typeof window !== 'undefined' && window.__APP_CONFIG__?.apiBase) {
    return window.__APP_CONFIG__.apiBase
  }

  // 2. Explicit environment variable (if absolute URL like http://localhost:8000/api/v1)
  const envUrl = import.meta.env.VITE_API_BASE_URL
  if (envUrl && (envUrl.startsWith('http://') || envUrl.startsWith('https://'))) {
    return envUrl
  }

  // 3. Derive dynamically from current base path
  const base = getAppBasePath().replace(/\/$/, '')
  return base !== '' ? `${base}/api/v1` : '/api/v1'
}

const api = axios.create({
  baseURL: getApiBaseUrl(),
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('connectly_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  if (typeof window !== 'undefined' && window.location) {
    const base = getAppBasePath().replace(/\/$/, '')
    config.headers['X-Frontend-Base-Url'] = window.location.origin + base
  }
  config.baseURL = getApiBaseUrl()
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      if (error.response.status === 401 || (error.response.status === 403 && error.response.data?.account_disabled)) {
        localStorage.removeItem('connectly_token')
        localStorage.removeItem('connectly_user')
        if (error.response.data?.account_disabled) {
          sessionStorage.setItem('auth_notice', error.response.data.message || 'Your account has been disabled by an administrator.')
        }
        const base = getAppBasePath().replace(/\/$/, '')
        const loginUrl = base !== '' ? `${base}/login` : '/login'
        if (!window.location.pathname.endsWith('/login') && !window.location.pathname.endsWith('/register')) {
          window.location.href = loginUrl
        }
      }
    }
    return Promise.reject(error)
  }
)

export default api
