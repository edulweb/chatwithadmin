<script setup>
import { computed } from 'vue'
import { Image, FileText, Ban } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import { useChatStore } from '@/stores/chatStore'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  conversation: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
})

const chatStore = useChatStore()
const authStore = useAuthStore()

const otherUser = computed(() => {
  return props.conversation.other_user || null
})

const isOnline = computed(() => {
  if (!otherUser.value) return false
  return chatStore.isUserOnline(otherUser.value.id) || otherUser.value.is_online
})

const formattedTime = computed(() => {
  const ts = props.conversation.latest_message?.created_at || props.conversation.updated_at
  if (!ts) return ''
  const date = new Date(ts)
  const now = new Date()
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
})

const messageSnippet = computed(() => {
  const msg = props.conversation.latest_message
  if (!msg) return 'No messages yet'
  if (msg.is_deleted) return 'This message was deleted'
  if (msg.body) return msg.body
  if (msg.type === 'file' || msg.attachments?.length > 0) {
    const isImg = msg.attachments?.[0]?.is_image
    return isImg ? 'Photo' : 'Document'
  }
  return 'Message'
})

const isSnippetImage = computed(() => {
  const msg = props.conversation.latest_message
  return msg && !msg.is_deleted && msg.attachments?.some((a) => a.is_image) && !msg.body
})

const isSnippetDoc = computed(() => {
  const msg = props.conversation.latest_message
  return msg && !msg.is_deleted && msg.attachments?.some((a) => !a.is_image) && !msg.body
})

const isSnippetDeleted = computed(() => {
  return props.conversation.latest_message?.is_deleted
})
</script>

<template>
  <div
    :class="[
      'flex items-center gap-3 px-3.5 py-3 rounded-2xl cursor-pointer transition-all select-none',
      isActive
        ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-950 dark:text-indigo-100 shadow-xs'
        : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 text-gray-800 dark:text-gray-200'
    ]"
    @click="chatStore.selectConversation(conversation)"
  >
    <UserAvatar
      :user="otherUser"
      size="md"
      :is-online="isOnline"
      :show-status="true"
    />

    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-1.5 min-w-0">
          <h3 class="text-sm font-semibold truncate" :class="isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-900 dark:text-gray-100'">
            {{ otherUser?.name || 'Direct Chat' }}
          </h3>
          <span
            v-if="otherUser?.is_admin"
            class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex-shrink-0"
          >
            Admin
          </span>
          <span
            v-if="otherUser?.is_disabled"
            class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 flex-shrink-0"
          >
            Disabled
          </span>
        </div>
        <span class="text-[11px] text-gray-400 dark:text-gray-500 flex-shrink-0 ml-2">
          {{ formattedTime }}
        </span>
      </div>

      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 truncate">
          <Ban v-if="isSnippetDeleted" class="w-3.5 h-3.5 flex-shrink-0 text-gray-400 italic" />
          <Image v-else-if="isSnippetImage" class="w-3.5 h-3.5 flex-shrink-0 text-indigo-500" />
          <FileText v-else-if="isSnippetDoc" class="w-3.5 h-3.5 flex-shrink-0 text-indigo-500" />
          <span class="truncate" :class="{ 'italic text-gray-400': isSnippetDeleted }">
            {{ messageSnippet }}
          </span>
        </div>

        <!-- Unread badge pill -->
        <span
          v-if="conversation.unread_count > 0"
          class="min-w-4.5 h-4.5 px-1.5 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0 shadow-xs"
        >
          {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
        </span>
      </div>
    </div>
  </div>
</template>
