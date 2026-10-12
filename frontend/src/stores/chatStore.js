import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { getEcho } from '@/services/echo'
import { useAuthStore } from './authStore'

export const useChatStore = defineStore('chat', () => {
  const authStore = useAuthStore()

  const conversations = ref([])
  const activeConversation = ref(null)
  const messages = ref([])
  const replyingToMessage = ref(null)
  const editingMessage = ref(null)

  const isLoadingConversations = ref(false)
  const isLoadingMessages = ref(false)
  const isLoadingMoreMessages = ref(false)
  const hasMoreMessages = ref(false)
  const oldestMessageId = ref(null)
  const newestMessageId = ref(null)

  // Upload states
  const uploadProgress = ref(0)
  const isUploading = ref(false)
  const uploadError = ref(null)

  // Real-time tracking
  const typingUsers = ref({}) // map of userId => { user, timeout }
  const onlineUsers = ref(new Set())
  const connectionStatus = ref('disconnected') // 'connected', 'connecting', 'disconnected'

  let activeEchoChannel = null
  let presenceChannel = null
  let typingTimeout = null

  // Total unread count
  const totalUnreadCount = computed(() => {
    return conversations.value.reduce((sum, conv) => sum + (conv.unread_count || 0), 0)
  })

  // List of active typing users for active conversation
  const activeTypingUsersList = computed(() => {
    return Object.values(typingUsers.value).map((entry) => entry.user)
  })

  async function fetchConversations() {
    isLoadingConversations.value = true
    try {
      const response = await api.get('/conversations')
      conversations.value = response.data.data
      return conversations.value
    } catch (err) {
      console.error('Failed to fetch conversations:', err)
      return []
    } finally {
      isLoadingConversations.value = false
    }
  }

  async function selectConversation(conversation) {
    if (activeConversation.value?.id === conversation.id) {
      return
    }

    // Unsubscribe from previous conversation channel
    unsubscribeFromConversation()

    activeConversation.value = conversation
    messages.value = []
    replyingToMessage.value = null
    editingMessage.value = null
    typingUsers.value = {}
    hasMoreMessages.value = false
    oldestMessageId.value = null
    newestMessageId.value = null

    // Subscribe to new conversation's private channel
    subscribeToConversation(conversation.id)

    // Load initial messages
    await fetchMessages()

    // Mark messages as read
    await markAsRead(conversation.id)
  }

  async function startConversationWithUser(userId) {
    try {
      const response = await api.post('/conversations', { user_id: userId })
      const conv = response.data.data

      // Check if conversation is already in list
      const existingIdx = conversations.value.findIndex((c) => c.id === conv.id)
      if (existingIdx !== -1) {
        conversations.value[existingIdx] = conv
      } else {
        conversations.value.unshift(conv)
      }

      await selectConversation(conv)
      return conv
    } catch (err) {
      console.error('Failed to start conversation:', err)
      throw err
    }
  }

  async function fetchMessages(beforeId = null, searchQuery = null) {
    if (!activeConversation.value) return

    if (beforeId) {
      isLoadingMoreMessages.value = true
    } else {
      isLoadingMessages.value = true
    }

    try {
      const params = { limit: 30 }
      if (beforeId) params.before_id = beforeId
      if (searchQuery) params.search = searchQuery

      const response = await api.get(`/conversations/${activeConversation.value.id}/messages`, { params })
      const fetchedMessages = response.data.data
      const meta = response.data.meta

      hasMoreMessages.value = meta.has_more

      if (beforeId) {
        // Prepend older messages
        messages.value = [...fetchedMessages, ...messages.value]
      } else {
        messages.value = fetchedMessages
      }

      if (messages.value.length > 0) {
        oldestMessageId.value = messages.value[0].id
        newestMessageId.value = messages.value[messages.value.length - 1].id
      }

      return fetchedMessages
    } catch (err) {
      console.error('Failed to load messages:', err)
      return []
    } finally {
      isLoadingMessages.value = false
      isLoadingMoreMessages.value = false
    }
  }

  async function recoverMissedMessages() {
    if (!activeConversation.value || !newestMessageId.value) return
    try {
      const response = await api.get(`/conversations/${activeConversation.value.id}/messages`, {
        params: { after_id: newestMessageId.value },
      })
      const missed = response.data.data
      if (missed.length > 0) {
        missed.forEach((msg) => {
          if (!messages.value.some((m) => m.id === msg.id)) {
            messages.value.push(msg)
          }
        })
        newestMessageId.value = messages.value[messages.value.length - 1].id
      }
    } catch (err) {
      console.error('Failed to recover missed messages:', err)
    }
  }

  async function sendMessage({ body, file, replyToId }) {
    if (!activeConversation.value) return

    isUploading.value = true
    uploadProgress.value = 0
    uploadError.value = null

    try {
      const formData = new FormData()
      if (body) formData.append('body', body)
      if (file) formData.append('file', file)
      if (replyToId) formData.append('reply_to_id', replyToId)

      const response = await api.post(`/conversations/${activeConversation.value.id}/messages`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          }
        },
      })

      const newMsg = response.data.data

      // Append if not already in messages list
      if (!messages.value.some((m) => m.id === newMsg.id)) {
        messages.value.push(newMsg)
        newestMessageId.value = newMsg.id
      }

      // Update latest message in conversation list
      updateConversationLatestMessage(activeConversation.value.id, newMsg)

      replyingToMessage.value = null
      return newMsg
    } catch (err) {
      if (err.response?.status === 413) {
        uploadError.value = 'The video/file is too large for the server. Maximum limit is 100MB.'
      } else if (err.response?.data?.errors?.file?.[0]) {
        uploadError.value = err.response.data.errors.file[0]
      } else if (err.response?.data?.message) {
        uploadError.value = err.response.data.message
      } else {
        uploadError.value = 'Failed to upload video or send message. Please try again.'
      }
      console.error('Send message / upload failed:', err)
      throw err
    } finally {
      isUploading.value = false
      uploadProgress.value = 0
    }
  }

  async function editMessage(messageId, newBody) {
    try {
      const response = await api.patch(`/messages/${messageId}`, { body: newBody })
      const updated = response.data.data

      const idx = messages.value.findIndex((m) => m.id === messageId)
      if (idx !== -1) {
        messages.value[idx] = updated
      }

      editingMessage.value = null
      return updated
    } catch (err) {
      console.error('Failed to edit message:', err)
      throw err
    }
  }

  async function deleteMessage(messageId) {
    try {
      await api.delete(`/messages/${messageId}`)

      const idx = messages.value.findIndex((m) => m.id === messageId)
      if (idx !== -1) {
        messages.value[idx].body = 'This message was deleted'
        messages.value[idx].is_deleted = true
        messages.value[idx].attachments = []
      }
    } catch (err) {
      console.error('Failed to delete message:', err)
      throw err
    }
  }

  async function markAsRead(conversationId) {
    try {
      await api.post(`/conversations/${conversationId}/read`)

      // Clear unread count locally
      const conv = conversations.value.find((c) => c.id === conversationId)
      if (conv) {
        conv.unread_count = 0
      }
    } catch (err) {
      console.error('Failed to mark as read:', err)
    }
  }

  function sendTyping(isTyping = true) {
    if (!activeConversation.value) return

    // Debounce typing broadcast
    if (typingTimeout) clearTimeout(typingTimeout)

    api.post(`/conversations/${activeConversation.value.id}/typing`, { is_typing: isTyping }).catch(() => {})

    if (isTyping) {
      typingTimeout = setTimeout(() => {
        sendTyping(false)
      }, 3000)
    }
  }

  function subscribeToConversation(conversationId) {
    const echo = getEcho()
    if (!echo) return

    const channelName = `conversation.${conversationId}`
    activeEchoChannel = echo.private(channelName)

    activeEchoChannel
      .listen('MessageSent', (e) => {
        const msg = e.message
        if (activeConversation.value && activeConversation.value.id === conversationId) {
          if (!messages.value.some((m) => m.id === msg.id)) {
            messages.value.push(msg)
            newestMessageId.value = msg.id
          }
          // Automatically mark read if active conversation
          if (authStore.user && msg.sender_id !== authStore.user.id) {
            markAsRead(conversationId)
          }
        }

        updateConversationLatestMessage(conversationId, msg, true)
      })
      .listen('MessageRead', (e) => {
        if (activeConversation.value && activeConversation.value.id === conversationId) {
          messages.value.forEach((m) => {
            if (m.sender_id === authStore.user?.id && m.id <= e.last_read_message_id) {
              m.is_read = true
            }
          })
        }
      })
      .listen('MessageUpdated', (e) => {
        const msg = e.message
        if (activeConversation.value && activeConversation.value.id === conversationId) {
          const idx = messages.value.findIndex((m) => m.id === msg.id)
          if (idx !== -1) {
            messages.value[idx] = msg
          }
        }
      })
      .listen('MessageDeleted', (e) => {
        if (activeConversation.value && activeConversation.value.id === conversationId) {
          const idx = messages.value.findIndex((m) => m.id === e.message_id)
          if (idx !== -1) {
            messages.value[idx].body = 'This message was deleted'
            messages.value[idx].is_deleted = true
            messages.value[idx].attachments = []
          }
        }
      })
      .listen('UserTyping', (e) => {
        if (!activeConversation.value || activeConversation.value.id !== conversationId) return
        if (authStore.user && e.user.id === authStore.user.id) return

        if (e.is_typing) {
          // Clear any existing timeout for this user
          if (typingUsers.value[e.user.id]?.timeout) {
            clearTimeout(typingUsers.value[e.user.id].timeout)
          }

          const timeout = setTimeout(() => {
            const copy = { ...typingUsers.value }
            delete copy[e.user.id]
            typingUsers.value = copy
          }, 4000)

          typingUsers.value = {
            ...typingUsers.value,
            [e.user.id]: { user: e.user, timeout },
          }
        } else {
          if (typingUsers.value[e.user.id]?.timeout) {
            clearTimeout(typingUsers.value[e.user.id].timeout)
          }
          const copy = { ...typingUsers.value }
          delete copy[e.user.id]
          typingUsers.value = copy
        }
      })
  }

  function unsubscribeFromConversation() {
    if (activeConversation.value && activeEchoChannel) {
      const echo = getEcho()
      if (echo) {
        echo.leave(`conversation.${activeConversation.value.id}`)
      }
      activeEchoChannel = null
    }
  }

  function initGlobalPresence() {
    const echo = getEcho()
    if (!echo) return

    // Track online presence
    presenceChannel = echo.join('online')
      .here((users) => {
        connectionStatus.value = 'connected'
        onlineUsers.value = new Set(users.map((u) => u.id))
      })
      .joining((user) => {
        onlineUsers.value.add(user.id)
      })
      .leaving((user) => {
        onlineUsers.value.delete(user.id)
      })
      .error((err) => {
        console.error('Echo presence error:', err)
        connectionStatus.value = 'disconnected'
      })

    // Listen to user-specific private channel for instant events (e.g. account disabled)
    if (authStore.user?.id) {
      echo.private(`user.${authStore.user.id}`)
        .listen('UserDisabled', (e) => {
          console.warn('Received UserDisabled event:', e)
          authStore.forceLogoutDisabled(e.reason || 'Your account has been disabled by an administrator.')
        })
    }

    // Listen to window focus or reconnect
    window.addEventListener('focus', () => {
      recoverMissedMessages()
      fetchConversations()
    })
  }

  function updateConversationLatestMessage(convId, message, incrementUnread = false) {
    const conv = conversations.value.find((c) => c.id === convId)
    if (conv) {
      conv.latest_message = message
      conv.updated_at = message.created_at
      if (incrementUnread && activeConversation.value?.id !== convId && message.sender_id !== authStore.user?.id) {
        conv.unread_count = (conv.unread_count || 0) + 1
      }
      // Re-sort conversations
      conversations.value.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
    } else {
      fetchConversations()
    }
  }

  function isUserOnline(userId) {
    return onlineUsers.value.has(userId)
  }

  return {
    conversations,
    activeConversation,
    messages,
    replyingToMessage,
    editingMessage,
    isLoadingConversations,
    isLoadingMessages,
    isLoadingMoreMessages,
    hasMoreMessages,
    uploadProgress,
    isUploading,
    uploadError,
    typingUsers,
    activeTypingUsersList,
    onlineUsers,
    connectionStatus,
    totalUnreadCount,
    fetchConversations,
    selectConversation,
    startConversationWithUser,
    fetchMessages,
    sendMessage,
    editMessage,
    deleteMessage,
    markAsRead,
    sendTyping,
    initGlobalPresence,
    isUserOnline,
    recoverMissedMessages,
  }
})
