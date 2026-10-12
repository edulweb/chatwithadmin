<script setup>
import { onMounted } from 'vue'
import { MessageSquareDashed } from 'lucide-vue-next'
import ChatSidebar from './ChatSidebar.vue'
import ChatHeader from './ChatHeader.vue'
import MobileChatHeader from './MobileChatHeader.vue'
import MessageList from './MessageList.vue'
import MessageComposer from './MessageComposer.vue'
import { useChatStore } from '@/stores/chatStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { initBroadcasting } from '@/services/echo'

const chatStore = useChatStore()
const settingsStore = useSettingsStore()

onMounted(async () => {
  settingsStore.initTheme()
  await initBroadcasting().catch(() => {})
  chatStore.initGlobalPresence()
  await chatStore.fetchConversations()
})
</script>

<template>
  <div class="flex h-[100dvh] w-full overflow-hidden bg-slate-100 dark:bg-gray-950 font-sans antialiased text-gray-800 dark:text-gray-200">
    <!-- Sidebar: Visible on Desktop, or on Mobile when no active conversation -->
    <div
      :class="[
        'h-full flex-shrink-0 z-20 transition-all',
        chatStore.activeConversation ? 'hidden md:flex' : 'flex w-full md:w-80 lg:w-96'
      ]"
    >
      <ChatSidebar />
    </div>

    <!-- Active Chat Area: Visible on Desktop, or on Mobile when conversation is open -->
    <main
      :class="[
        'flex-1 h-full flex flex-col min-w-0 bg-white dark:bg-gray-900 overflow-hidden relative',
        !chatStore.activeConversation ? 'hidden md:flex' : 'flex w-full'
      ]"
    >
      <template v-if="chatStore.activeConversation">
        <!-- Desktop Header -->
        <ChatHeader class="hidden md:flex" />

        <!-- Mobile Header with Back Button -->
        <MobileChatHeader class="flex md:hidden" />

        <!-- Disabled Account Banner -->
        <div
          v-if="chatStore.activeConversation.other_user?.is_disabled"
          class="bg-rose-50 dark:bg-rose-950/60 border-b border-rose-200 dark:border-rose-900/60 px-4 py-2.5 text-xs text-rose-700 dark:text-rose-300 text-center font-medium select-none"
        >
          This account has been disabled by an administrator. Messages cannot be sent.
        </div>

        <!-- Blocked Banner if user is blocked by other or has blocked other -->
        <div
          v-else-if="chatStore.activeConversation.is_blocked"
          class="bg-rose-50 dark:bg-rose-950/50 border-b border-rose-200 dark:border-rose-900/60 px-4 py-2.5 text-xs text-rose-700 dark:text-rose-300 text-center font-medium select-none"
        >
          You cannot reply to this conversation because you have been blocked by this user.
        </div>

        <div
          v-else-if="chatStore.activeConversation.has_blocked_other"
          class="bg-amber-50 dark:bg-amber-950/50 border-b border-amber-200 dark:border-amber-900/60 px-4 py-2.5 text-xs text-amber-700 dark:text-amber-300 text-center font-medium select-none"
        >
          You have blocked this contact. Unblock to send messages.
        </div>

        <!-- Messages List -->
        <MessageList />

        <!-- Message Composer (Disabled if blocked or disabled) -->
        <MessageComposer
          v-if="!chatStore.activeConversation.is_blocked && !chatStore.activeConversation.has_blocked_other && !chatStore.activeConversation.other_user?.is_disabled"
        />
      </template>

      <!-- Empty State on Desktop when no conversation is active -->
      <div
        v-else
        class="h-full flex flex-col items-center justify-center p-8 text-center select-none bg-slate-50/50 dark:bg-gray-900"
      >
        <div class="w-20 h-20 rounded-3xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4 shadow-sm">
          <MessageSquareDashed class="w-10 h-10" />
        </div>
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
          Your Personal Messenger
        </h2>
        <p class="text-xs text-gray-400 max-w-sm mt-1.5 leading-relaxed">
          Select a conversation from the sidebar or click the new message button to connect securely with your contacts.
        </p>
      </div>
    </main>
  </div>
</template>
