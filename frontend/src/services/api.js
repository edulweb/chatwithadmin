import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
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
        if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
          window.location.href = '/login'
        }
      }
    }
    return Promise.reject(error)
  }
)

export default api
