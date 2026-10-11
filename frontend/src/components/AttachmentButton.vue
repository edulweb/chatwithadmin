<script setup>
import { ref } from 'vue'
import { Paperclip } from 'lucide-vue-next'

const emit = defineEmits(['selected'])

const fileInputRef = ref(null)

function triggerFileInput() {
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
    fileInputRef.value.click()
  }
}

function handleFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  // 100 MiB client check (100 * 1024 * 1024 bytes)
  const maxSize = 100 * 1024 * 1024
  if (file.size > maxSize) {
    alert('File size exceeds the 100 MiB limit.')
    return
  }

  emit('selected', file)
}
</script>

<template>
  <div>
    <input
      ref="fileInputRef"
      type="file"
      class="hidden"
      @change="handleFileChange"
    />

    <button
      type="button"
      class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none"
      title="Attach file (all types supported, up to 100MB)"
      @click="triggerFileInput"
    >
      <Paperclip class="w-5 h-5" />
    </button>
  </div>
</template>
