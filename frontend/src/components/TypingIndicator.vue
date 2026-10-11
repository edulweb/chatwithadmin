<script setup>
import { computed } from 'vue'

const props = defineProps({
  typingUsers: {
    type: Array,
    default: () => [],
  },
})

const typingText = computed(() => {
  if (!props.typingUsers || props.typingUsers.length === 0) return ''
  if (props.typingUsers.length === 1) {
    return `${props.typingUsers[0].name} is typing...`
  }
  if (props.typingUsers.length === 2) {
    return `${props.typingUsers[0].name} and ${props.typingUsers[1].name} are typing...`
  }
  return 'Several people are typing...'
})
</script>

<template>
  <div
    v-if="typingUsers.length > 0"
    class="flex items-center gap-2 px-4 py-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium select-none animate-fade-in"
  >
    <div class="flex items-center gap-1">
      <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
      <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
      <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"></span>
    </div>
    <span>{{ typingText }}</span>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
</style>
