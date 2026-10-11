<script setup>
import { computed } from 'vue'
import { MessageSquarePlus } from 'lucide-vue-next'
import ConversationItem from './ConversationItem.vue'
import { useChatStore } from '@/stores/chatStore'

const props = defineProps({
  filterQuery: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['openNewChat'])

const chatStore = useChatStore()

const filteredConversations = computed(() => {
  const query = props.filterQuery.toLowerCase().trim()
  if (!query) return chatStore.conversations

  return chatStore.conversations.filter((c) => {
    const other = c.other_user
    if (!other) return false
    return (
      other.name.toLowerCase().includes(query) ||
      other.username.toLowerCase().includes(query) ||
      c.latest_message?.body?.toLowerCase().includes(query)
    )
  })
})
</script>

<template>
  <div class="flex-1 overflow-y-auto px-2 py-2 space-y-1">
    <!-- Loading state -->
    <div v-if="chatStore.isLoadingConversations && chatStore.conversations.length === 0" class="p-6 text-center text-xs text-gray-400">
      Loading conversations...
    </div>

    <!-- Empty state -->
    <div
      v-else-if="filteredConversations.length === 0"
      class="h-64 flex flex-col items-center justify-center text-center p-6 text-gray-400 select-none"
    >
      <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-2.5">
        <MessageSquarePlus class="w-6 h-6" />
      </div>
      <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No conversations yet</p>
      <p class="text-xs text-gray-400 mt-1 max-w-xs">
        Search for someone to start chatting.
      </p>
      <button
        type="button"
        class="mt-3 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium shadow-sm transition-colors"
        @click="emit('openNewChat')"
      >
        Start a new chat
      </button>
    </div>

    <!-- List of Conversations -->
    <ConversationItem
      v-for="conv in filteredConversations"
      :key="conv.id"
      :conversation="conv"
      :is-active="chatStore.activeConversation?.id === conv.id"
    />
  </div>
</template>
