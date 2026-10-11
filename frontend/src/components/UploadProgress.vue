<script setup>
import { computed } from 'vue'
import { X, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  progress: {
    type: Number,
    default: 0,
  },
  fileName: {
    type: String,
    default: '',
  },
  fileSize: {
    type: Number,
    default: 0,
  },
  error: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['cancel'])

const formattedSize = computed(() => {
  if (!props.fileSize) return ''
  if (props.fileSize < 1024) return `${props.fileSize} B`
  if (props.fileSize < 1024 * 1024) return `${(props.fileSize / 1024).toFixed(1)} KB`
  return `${(props.fileSize / (1024 * 1024)).toFixed(1)} MB`
})
</script>

<template>
  <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-800 bg-indigo-50/50 dark:bg-indigo-950/20">
    <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300 mb-1.5">
      <div class="flex items-center gap-2 truncate pr-2">
        <span class="font-medium truncate max-w-[200px]">{{ fileName || 'Message Sending...' }}</span>
        <span v-if="formattedSize" class="text-gray-400 text-[11px]">({{ formattedSize }})</span>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <span v-if="!error" class="font-semibold text-indigo-600 dark:text-indigo-400">{{ progress }}%</span>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded transition-colors"
          title="Cancel"
          @click="emit('cancel')"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Error message if any -->
    <div v-if="error" class="flex items-center gap-1.5 text-xs text-rose-500 font-medium my-1">
      <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
      <span>{{ error }}</span>
    </div>

    <!-- Progress bar -->
    <div v-else class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
      <div
        class="bg-indigo-600 h-full rounded-full transition-all duration-150 ease-out"
        :style="{ width: `${progress}%` }"
      ></div>
    </div>
  </div>
</template>
