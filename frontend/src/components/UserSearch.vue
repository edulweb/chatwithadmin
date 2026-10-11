<script setup>
import { ref } from 'vue'
import { Search, X, UserPlus, Loader2 } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import { useUserStore } from '@/stores/userStore'
import { useChatStore } from '@/stores/chatStore'

const emit = defineEmits(['close'])

const userStore = useUserStore()
const chatStore = useChatStore()

const query = ref('')
let debounceTimer = null

function handleInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    userStore.searchUsers(query.value)
  }, 250)
}

async function selectUser(user) {
  try {
    await chatStore.startConversationWithUser(user.id)
    emit('close')
  } catch (err) {
    alert(err.response?.data?.message || 'Could not start conversation')
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 select-none" @click.self="emit('close')">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[80vh]">
      <!-- Header -->
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <UserPlus class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
          <span>New Conversation</span>
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg"
          @click="emit('close')"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Search Input -->
      <div class="p-4 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-700/80 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
          <Search class="w-4 h-4 text-gray-400" />
          <input
            v-model="query"
            type="text"
            placeholder="Search by name or username..."
            class="w-full bg-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none"
            autofocus
            @input="handleInput"
          />
        </div>
      </div>

      <!-- Search Results List -->
      <div class="flex-1 overflow-y-auto p-2 space-y-1">
        <div v-if="userStore.isSearching" class="p-8 text-center text-xs text-gray-400 flex items-center justify-center gap-2">
          <Loader2 class="w-4 h-4 animate-spin text-indigo-600" />
          <span>Searching users...</span>
        </div>

        <div
          v-else-if="query.trim() && userStore.searchResults.length === 0"
          class="p-8 text-center text-xs text-gray-400"
        >
          No users found matching "{{ query }}"
        </div>

        <div
          v-else-if="!query.trim()"
          class="p-8 text-center text-xs text-gray-400"
        >
          Type a name or @username to find contacts.
        </div>

        <div
          v-for="user in userStore.searchResults"
          :key="user.id"
          class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/80 cursor-pointer transition-colors"
          @click="selectUser(user)"
        >
          <UserAvatar :user="user" size="md" />
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
              {{ user.name }}
            </h4>
            <p class="text-xs text-gray-400 truncate">
              @{{ user.username }}
            </p>
          </div>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 text-xs font-semibold hover:bg-indigo-100 transition-colors"
          >
            Chat
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
