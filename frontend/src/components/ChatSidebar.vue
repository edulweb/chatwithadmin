<script setup>
import { ref } from 'vue'
import {
  MessageSquarePlus,
  Search,
  Settings,
  Moon,
  Sun,
  X,
  MessageCircle,
  UserPlus
} from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import ConversationList from './ConversationList.vue'
import UserSearch from './UserSearch.vue'
import AdminUserModal from './AdminUserModal.vue'
import ProfilePanel from './ProfilePanel.vue'
import SettingsPanel from './SettingsPanel.vue'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const searchQuery = ref('')
const showUserSearch = ref(false)
const showAdminRegister = ref(false)
const showProfile = ref(false)
const showSettings = ref(false)

function clearSearch() {
  searchQuery.value = ''
}
</script>

<template>
  <aside class="w-full md:w-80 lg:w-96 h-full flex flex-col bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 transition-colors select-none">
    <!-- Top Bar: connectly Branding & Actions -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
          <MessageCircle class="w-5 h-5 fill-current" />
        </div>
        <div>
          <h1 class="text-base font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent leading-none">
            Connectly
          </h1>
          <span class="text-[10px] text-gray-400 font-medium">Personal Messenger</span>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex items-center gap-1">
        <!-- Admin: Register User -->
        <button
          v-if="authStore.isAdmin"
          type="button"
          class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
          title="Register new user"
          @click="showAdminRegister = true"
        >
          <UserPlus class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
        </button>

        <!-- New Chat -->
        <button
          type="button"
          class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
          :title="authStore.isAdmin ? 'New conversation' : 'Contact Administrator'"
          @click="showUserSearch = true"
        >
          <MessageSquarePlus class="w-4 h-4" />
        </button>

        <!-- Theme Toggle -->
        <!-- <button
          type="button"
          class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
          :title="settingsStore.theme === 'dark' ? 'Switch to Light Theme' : 'Switch to Dark Theme'"
          @click="settingsStore.toggleTheme"
        >
          <Moon v-if="settingsStore.theme === 'dark'" class="w-4 h-4" />
          <Sun v-else class="w-4 h-4" />
        </button> -->

        <!-- Settings -->
        <button
          type="button"
          class="p-2 rounded-xl text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
          title="Settings"
          @click="showSettings = true"
        >
          <Settings class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Current User Bar -->
    <div
      class="px-4 py-2.5 bg-gray-50/70 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800/80 transition-colors"
      title="Click to view/edit your profile"
      @click="showProfile = true"
    >
      <div class="flex items-center gap-2.5 min-w-0">
        <UserAvatar
          :user="authStore.user"
          size="sm"
          :is-online="true"
          :show-status="true"
        />
        <div class="min-w-0">
          <div class="flex items-center gap-1.5">
            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
              {{ authStore.user?.name || 'My Profile' }}
            </p>
            <span
              v-if="authStore.isAdmin"
              class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
            >
              Admin
            </span>
          </div>
          <p class="text-[11px] text-gray-400 truncate">
            @{{ authStore.user?.username }}
          </p>
        </div>
      </div>
      <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">
        Online
      </span>
    </div>

    <!-- Search Conversations -->
    <div class="p-3 border-b border-gray-100 dark:border-gray-800">
      <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 rounded-xl px-3 py-1.5 border border-gray-200/80 dark:border-gray-700/80 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-all">
        <Search class="w-3.5 h-3.5 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search chats or messages..."
          class="w-full bg-transparent text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="text-gray-400 hover:text-gray-600 p-0.5"
          @click="clearSearch"
        >
          <X class="w-3 h-3" />
        </button>
      </div>
    </div>

    <!-- Conversation List -->
    <ConversationList
      :filter-query="searchQuery"
      @open-new-chat="showUserSearch = true"
    />

    <!-- Modals -->
    <UserSearch v-if="showUserSearch" @close="showUserSearch = false" />
    <AdminUserModal v-if="showAdminRegister" @close="showAdminRegister = false" />
    <ProfilePanel v-if="showProfile" @close="showProfile = false" />
    <SettingsPanel v-if="showSettings" @close="showSettings = false" />
  </aside>
</template>
