<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import { ArrowDown, MessageSquare } from 'lucide-vue-next'
import MessageBubble from './MessageBubble.vue'
import TypingIndicator from './TypingIndicator.vue'
import { useChatStore } from '@/stores/chatStore'

const chatStore = useChatStore()
const containerRef = ref(null)
const showScrollBottom = ref(false)

// Group messages with date dividers
const messageGroups = computed(() => {
  const groups = []
  let lastDate = null

  chatStore.messages.forEach((msg, idx) => {
    const msgDate = new Date(msg.created_at).toLocaleDateString([], {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })

    if (msgDate !== lastDate) {
      groups.push({
        type: 'date',
        id: `date-${msgDate}`,
        label: formatDateLabel(msg.created_at),
      })
      lastDate = msgDate
    }

    groups.push({
      type: 'message',
      id: msg.id,
      message: msg,
      previousMessage: idx > 0 ? chatStore.messages[idx - 1] : null,
    })
  })

  return groups
})

function formatDateLabel(isoString) {
  const date = new Date(isoString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  }
  if (date.toDateString() === yesterday.toDateString()) {
    return 'Yesterday'
  }
  return date.toLocaleDateString(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
}

function scrollToBottom(smooth = false) {
  nextTick(() => {
    if (containerRef.value) {
      containerRef.value.scrollTo({
        top: containerRef.value.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto',
      })
    }
  })
}

function handleScroll() {
  if (!containerRef.value) return
  const { scrollTop, scrollHeight, clientHeight } = containerRef.value

  // Check if scrolled up enough to show "Scroll to bottom" button
  showScrollBottom.value = scrollHeight - scrollTop - clientHeight > 250

  // Infinite scroll upwards: load older messages
  if (scrollTop < 50 && chatStore.hasMoreMessages && !chatStore.isLoadingMoreMessages) {
    const prevScrollHeight = scrollHeight
    chatStore.fetchMessages(chatStore.oldestMessageId).then(() => {
      nextTick(() => {
        if (containerRef.value) {
          // Preserve scroll position
          containerRef.value.scrollTop = containerRef.value.scrollHeight - prevScrollHeight
        }
      })
    })
  }
}

// Watch for new messages and auto-scroll if near bottom
watch(
  () => chatStore.messages.length,
  (newLen, oldLen) => {
    if (!showScrollBottom.value || oldLen === 0) {
      scrollToBottom(oldLen > 0)
    }
  }
)

onMounted(() => {
  scrollToBottom(false)
})
</script>

<template>
  <div class="relative flex-1 overflow-hidden bg-slate-50/70 dark:bg-gray-950/70">
    <div
      ref="containerRef"
      class="h-full overflow-y-auto px-4 py-4 space-y-1"
      @scroll="handleScroll"
    >
      <!-- Load older messages spinner -->
      <div v-if="chatStore.isLoadingMoreMessages" class="flex justify-center py-2">
        <span class="text-xs text-gray-400">Loading older messages...</span>
      </div>

      <!-- Empty Conversation State -->
      <div
        v-if="chatStore.messages.length === 0 && !chatStore.isLoadingMessages"
        class="h-full flex flex-col items-center justify-center text-center p-6 text-gray-400 select-none"
      >
        <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-3">
          <MessageSquare class="w-8 h-8" />
        </div>
        <p class="font-semibold text-gray-700 dark:text-gray-300 text-sm">No messages yet</p>
        <p class="text-xs text-gray-400 mt-1 max-w-xs">
          Send a greeting or share a file to start your conversation.
        </p>
      </div>

      <!-- Message Items with Date Dividers -->
      <template v-for="item in messageGroups" :key="item.id">
        <!-- Date divider -->
        <div v-if="item.type === 'date'" class="flex justify-center my-3 select-none">
          <span class="px-3 py-1 rounded-full text-[11px] font-semibold bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-xs">
            {{ item.label }}
          </span>
        </div>

        <!-- Message Bubble -->
        <MessageBubble
          v-else-if="item.type === 'message'"
          :message="item.message"
          :previous-message="item.previousMessage"
        />
      </template>

      <!-- Typing Indicator -->
      <TypingIndicator :typing-users="chatStore.activeTypingUsersList" />
    </div>

    <!-- Scroll to bottom button -->
    <button
      v-if="showScrollBottom"
      type="button"
      class="absolute bottom-4 right-4 p-2.5 rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 shadow-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all focus:outline-none z-10"
      title="Scroll to bottom"
      @click="scrollToBottom(true)"
    >
      <ArrowDown class="w-4 h-4" />
    </button>
  </div>
</template>
