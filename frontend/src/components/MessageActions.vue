<script setup>
import { Reply, Copy, Edit2, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  isSentByMe: {
    type: Boolean,
    default: false,
  },
  isDeleted: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['reply', 'copy', 'edit', 'delete'])
</script>

<template>
  <div
    class="flex items-center gap-0.5 bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow-md border border-gray-100 dark:border-gray-700/80 rounded-lg p-1 text-gray-500 dark:text-gray-400 select-none"
  >
    <button
      v-if="!isDeleted"
      type="button"
      class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md transition-colors"
      title="Reply"
      @click="emit('reply')"
    >
      <Reply class="w-3.5 h-3.5" />
    </button>

    <button
      v-if="!isDeleted"
      type="button"
      class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-200 rounded-md transition-colors"
      title="Copy text"
      @click="emit('copy')"
    >
      <Copy class="w-3.5 h-3.5" />
    </button>

    <button
      v-if="isSentByMe && !isDeleted"
      type="button"
      class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md transition-colors"
      title="Edit message"
      @click="emit('edit')"
    >
      <Edit2 class="w-3.5 h-3.5" />
    </button>

    <button
      v-if="isSentByMe && !isDeleted"
      type="button"
      class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-rose-600 dark:hover:text-rose-400 rounded-md transition-colors"
      title="Delete message"
      @click="emit('delete')"
    >
      <Trash2 class="w-3.5 h-3.5" />
    </button>
  </div>
</template>
