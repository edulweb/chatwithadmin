<script setup>
import { ref, computed } from 'vue'
import { Search, MoreVertical, ShieldAlert, ShieldCheck, X, Moon, Sun } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import { useChatStore } from '@/stores/chatStore'
import { useUserStore } from '@/stores/userStore'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'

const chatStore = useChatStore()
const userStore = useUserStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const showMenu = ref(false)
const showSearch = ref(false)
const searchQuery = ref('')

const otherUser = computed(() => {
  return chatStore.activeConversation?.other_user || null
})

const isOnline = computed(() => {
  if (!otherUser.value) return false
  return chatStore.isUserOnline(otherUser.value.id) || otherUser.value.is_online
})

const statusText = computed(() => {
  if (isOnline.value) return 'Online'
  if (!otherUser.value?.last_seen_at) return 'Offline'
  const diffMinutes = Math.round((new Date() - new Date(otherUser.value.last_seen_at)) / 60000)
  if (diffMinutes < 1) return 'Just now'
  if (diffMinutes < 60) return `Last seen ${diffMinutes}m ago`
  const hours = Math.round(diffMinutes / 60)
  if (hours < 24) return `Last seen ${hours}h ago`
  return `Last seen ${new Date(otherUser.value.last_seen_at).toLocaleDateString([], { month: 'short', day: 'numeric' })}`
})

function toggleSearch() {
  showSearch.value = !showSearch.value
  if (!showSearch.value) {
    searchQuery.value = ''
    chatStore.fetchMessages()
  }
}

function handleSearchSubmit() {
  chatStore.fetchMessages(null, searchQuery.value.trim())
}

async function handleBlock() {
  if (!otherUser.value) return
  try {
    await userStore.blockUser(otherUser.value.id)
    chatStore.activeConversation.has_blocked_other = true
    showMenu.value = false
    alert(`${otherUser.value.name} has been blocked.`)
  } catch (e) {
    alert('Failed to block user.')
  }
}

async function handleUnblock() {
  if (!otherUser.value) return
  try {
    await userStore.unblockUser(otherUser.value.id)
    chatStore.activeConversation.has_blocked_other = false
    showMenu.value = false
    alert(`${otherUser.value.name} has been unblocked.`)
  } catch (e) {
    alert('Failed to unblock user.')
  }
}
</script>

<template>
  <div class="h-16 px-4 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between z-10">
    <div class="flex items-center gap-3 min-w-0">
      <UserAvatar
        :user="otherUser"
        size="md"
        :is-online="isOnline"
        :show-status="true"
      />
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
          {{ otherUser?.name || 'Chat' }}
        </h2>
        <p class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-1">
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="isOnline ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
          ></span>
          <span>{{ statusText }}</span>
        </p>
      </div>
    </div>

    <!-- Actions & In-chat search -->
    <div class="flex items-center gap-1.5">
      <!-- In-chat search bar -->
      <div v-if="showSearch" class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg px-2.5 py-1 text-xs">
        <Search class="w-3.5 h-3.5 text-gray-400 mr-1.5" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search in chat..."
          class="bg-transparent focus:outline-none text-gray-800 dark:text-gray-200 w-32 sm:w-48"
          @keydown.enter="handleSearchSubmit"
        />
        <button type="button" class="text-gray-400 hover:text-gray-600 p-0.5 ml-1" @click="toggleSearch">
          <X class="w-3.5 h-3.5" />
        </button>
      </div>

      <button
        v-else
        type="button"
        class="p-2 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        title="Search messages"
        @click="toggleSearch"
      >
        <Search class="w-4 h-4" />
      </button>

      <!-- Dropdown menu -->
      <div class="relative">
        <button
          type="button"
          class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
          title="More options"
          @click="showMenu = !showMenu"
        >
          <MoreVertical class="w-4 h-4" />
        </button>

        <div
          v-if="showMenu"
          class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-xl py-1 z-30 select-none text-xs"
          @click="showMenu = false"
        >
          <button
            v-if="!chatStore.activeConversation?.has_blocked_other"
            type="button"
            class="w-full text-left px-3 py-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center gap-2"
            @click="handleBlock"
          >
            <ShieldAlert class="w-4 h-4" />
            <span>Block {{ otherUser?.name }}</span>
          </button>
          <button
            v-else
            type="button"
            class="w-full text-left px-3 py-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-2"
            @click="handleUnblock"
          >
            <ShieldCheck class="w-4 h-4" />
            <span>Unblock {{ otherUser?.name }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
