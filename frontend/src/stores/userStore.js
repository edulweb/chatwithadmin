import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useUserStore = defineStore('user', () => {
  const searchResults = ref([])
  const isSearching = ref(false)
  const blockedUsers = ref([])
  const isLoadingBlocked = ref(false)

  async function searchUsers(query = '') {
    isSearching.value = true
    try {
      const params = query && query.trim() !== '' ? { search: query.trim() } : {}
      const response = await api.get('/users', { params })
      searchResults.value = response.data.data
      return searchResults.value
    } catch (err) {
      console.error('User search failed:', err)
      return []
    } finally {
      isSearching.value = false
    }
  }

  async function fetchBlockedUsers() {
    isLoadingBlocked.value = true
    try {
      const response = await api.get('/blocked-users')
      blockedUsers.value = response.data.data
      return blockedUsers.value
    } catch (err) {
      console.error('Failed to load blocked users:', err)
      return []
    } finally {
      isLoadingBlocked.value = false
    }
  }

  async function blockUser(userId) {
    try {
      await api.post(`/users/${userId}/block`)
      await fetchBlockedUsers()
    } catch (err) {
      console.error('Failed to block user:', err)
      throw err
    }
  }

  async function unblockUser(userId) {
    try {
      await api.delete(`/users/${userId}/block`)
      blockedUsers.value = blockedUsers.value.filter((u) => u.id !== userId)
    } catch (err) {
      console.error('Failed to unblock user:', err)
      throw err
    }
  }

  async function disableUser(userId) {
    try {
      const response = await api.post(`/admin/users/${userId}/disable`)
      return response.data
    } catch (err) {
      console.error('Failed to disable user:', err)
      throw err
    }
  }

  async function enableUser(userId) {
    try {
      const response = await api.post(`/admin/users/${userId}/enable`)
      return response.data
    } catch (err) {
      console.error('Failed to enable user:', err)
      throw err
    }
  }

  return {
    searchResults,
    isSearching,
    blockedUsers,
    isLoadingBlocked,
    searchUsers,
    fetchBlockedUsers,
    blockUser,
    unblockUser,
    disableUser,
    enableUser,
  }
})
