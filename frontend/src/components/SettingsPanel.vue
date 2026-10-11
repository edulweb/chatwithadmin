<script setup>
import { onMounted } from 'vue'
import { X, Moon, Sun, Shield, UserCheck, Loader2 } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import { useSettingsStore } from '@/stores/settingsStore'
import { useUserStore } from '@/stores/userStore'

const emit = defineEmits(['close'])

const settingsStore = useSettingsStore()
const userStore = useUserStore()

onMounted(() => {
  userStore.fetchBlockedUsers()
})

async function handleUnblock(userId) {
  try {
    await userStore.unblockUser(userId)
  } catch (err) {
    alert('Failed to unblock user')
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 select-none" @click.self="emit('close')">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[85vh]">
      <!-- Header -->
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <span>Settings</span>
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg"
          @click="emit('close')"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-5 space-y-6">
        <!-- Theme section -->
        <div>
          <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
            Appearance
          </h4>
          <div class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200/60 dark:border-gray-700/60">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <Moon v-if="settingsStore.theme === 'dark'" class="w-4 h-4" />
                <Sun v-else class="w-4 h-4" />
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                  {{ settingsStore.theme === 'dark' ? 'Dark Theme' : 'Light Theme' }}
                </p>
                <p class="text-[11px] text-gray-400">Toggle dark / light appearance</p>
              </div>
            </div>

            <button
              type="button"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
              :class="settingsStore.theme === 'dark' ? 'bg-indigo-600' : 'bg-gray-300'"
              @click="settingsStore.toggleTheme"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-xs"
                :class="settingsStore.theme === 'dark' ? 'translate-x-6' : 'translate-x-1'"
              ></span>
            </button>
          </div>
        </div>

        <!-- Blocked Users section -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
              <Shield class="w-3.5 h-3.5 text-rose-500" />
              <span>Blocked Users</span>
            </h4>
            <span class="text-[11px] text-gray-400">{{ userStore.blockedUsers.length }} blocked</span>
          </div>

          <div v-if="userStore.isLoadingBlocked" class="p-6 text-center text-xs text-gray-400 flex items-center justify-center gap-2">
            <Loader2 class="w-4 h-4 animate-spin text-indigo-600" />
            <span>Loading blocked users...</span>
          </div>

          <div
            v-else-if="userStore.blockedUsers.length === 0"
            class="p-6 text-center text-xs text-gray-400 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl"
          >
            No blocked users.
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="bUser in userStore.blockedUsers"
              :key="bUser.id"
              class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <UserAvatar :user="bUser" size="sm" />
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
                    {{ bUser.name }}
                  </p>
                  <p class="text-[11px] text-gray-400 truncate">
                    @{{ bUser.username }}
                  </p>
                </div>
              </div>

              <button
                type="button"
                class="px-2.5 py-1 text-xs rounded-lg font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors flex items-center gap-1"
                @click="handleUnblock(bUser.id)"
              >
                <UserCheck class="w-3.5 h-3.5" />
                <span>Unblock</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
