import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { disconnectEcho } from '@/services/echo'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('connectly_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('connectly_user') || 'null'))
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => Boolean(user.value?.is_admin))

  async function register(form) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/register', form)
      token.value = response.data.data.token
      user.value = response.data.data.user
      localStorage.setItem('connectly_token', token.value)
      localStorage.setItem('connectly_user', JSON.stringify(user.value))
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function login(form) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/login', form)
      token.value = response.data.data.token
      user.value = response.data.data.user
      localStorage.setItem('connectly_token', token.value)
      localStorage.setItem('connectly_user', JSON.stringify(user.value))
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch (e) {
      // Ignore network errors on logout
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('connectly_token')
      localStorage.removeItem('connectly_user')
      disconnectEcho()
    }
  }

  function forceLogoutDisabled(reason = 'Your account has been disabled by an administrator.') {
    token.value = null
    user.value = null
    localStorage.removeItem('connectly_token')
    localStorage.removeItem('connectly_user')
    disconnectEcho()
    sessionStorage.setItem('auth_notice', reason)
    alert(reason)
    if (window.location.pathname !== '/login') {
      window.location.href = '/login'
    }
  }

  async function fetchMe() {
    if (!token.value) return null
    try {
      const response = await api.get('/me')
      user.value = response.data.data
      localStorage.setItem('connectly_user', JSON.stringify(user.value))
      return user.value
    } catch (err) {
      if (err.response?.status === 401) {
        logout()
      }
      throw err
    }
  }

  async function updateProfile(formData) {
    loading.value = true
    error.value = null
    try {
      // If uploading an avatar file, send multipart/form-data
      const config = {}
      if (formData instanceof FormData) {
        // In Laravel, multipart with PATCH requires _method: PATCH with POST
        formData.append('_method', 'PATCH')
        const response = await api.post('/me', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        user.value = response.data.data
        localStorage.setItem('connectly_user', JSON.stringify(user.value))
        return response.data
      } else {
        const response = await api.patch('/me', formData)
        user.value = response.data.data
        localStorage.setItem('connectly_user', JSON.stringify(user.value))
        return response.data
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Update failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function changePassword(form) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/me/password', form)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Password change failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function adminRegisterUser(form) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/admin/users', form)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to register user'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function forgotPassword(email) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/forgot-password', { email })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to send password reset email'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function resetPassword(form) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/reset-password', form)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.response?.data?.errors?.token?.[0] || 'Password reset failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function activateAccount(tokenValue) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/activate', { token: tokenValue })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Account activation failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function verifyEmailChange(tokenValue) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/verify-email-change', { token: tokenValue })
      if (response.data?.data && user.value && user.value.id === response.data.data.id) {
        user.value = response.data.data
        localStorage.setItem('connectly_user', JSON.stringify(user.value))
      }
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Email verification failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function resendActivation(loginValue) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/auth/resend-activation', { login: loginValue })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to resend activation email'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    register,
    adminRegisterUser,
    login,
    logout,
    forceLogoutDisabled,
    fetchMe,
    updateProfile,
    changePassword,
    forgotPassword,
    resetPassword,
    activateAccount,
    verifyEmailChange,
    resendActivation,
  }
})
