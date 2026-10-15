<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { Send, X, FileUp, Film, Music, Image as ImageIcon, FileText, File, AlertCircle } from 'lucide-vue-next'
import AttachmentButton from './AttachmentButton.vue'
import UploadProgress from './UploadProgress.vue'
import { useChatStore } from '@/stores/chatStore'

const chatStore = useChatStore()

const text = ref('')
const selectedFile = ref(null)
const textareaRef = ref(null)
const isSubmitting = ref(false)
const isDragging = ref(false)
const composerError = ref(null)

const fileCategoryIcon = computed(() => {
  if (!selectedFile.value) return FileUp
  const type = selectedFile.value.type || ''
  const name = selectedFile.value.name.toLowerCase()

  if (type.startsWith('video/') || /\.(mp4|webm|ogg|mov|mkv|avi|m4v|3gp)$/i.test(name)) return Film
  if (type.startsWith('audio/') || /\.(mp3|wav|ogg|m4a|aac|flac|opus|weba)$/i.test(name)) return Music
  if (type.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|svg|bmp)$/i.test(name)) return ImageIcon
  if (type.includes('pdf') || /\.(pdf|doc|docx|txt)$/i.test(name)) return FileText
  return File
})

const formattedSelectedSize = computed(() => {
  if (!selectedFile.value) return ''
  const bytes = selectedFile.value.size
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
})

function onFileSelected(file) {
  if (!file) return
  composerError.value = null
  if (file.size > 100 * 1024 * 1024) {
    composerError.value = 'File size exceeds the 100 MiB limit.'
    return
  }
  selectedFile.value = file
}

function removeSelectedFile() {
  selectedFile.value = null
  composerError.value = null
}

function cancelReply() {
  chatStore.replyingToMessage = null
}

function onInput() {
  composerError.value = null
  if (text.value && text.value.trim().length > 0) {
    chatStore.sendTyping(true)
  } else {
    chatStore.sendTyping(false)
  }
  adjustTextareaHeight()
}

function adjustTextareaHeight() {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = `${Math.min(textareaRef.value.scrollHeight, 120)}px`
    }
  })
}

function handleKeydown(e) {
  // Allow submission via Ctrl+Enter or Cmd+Enter; plain Enter creates a newline
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    handleSubmit()
  }
}

function handlePaste(e) {
  if (e.clipboardData && e.clipboardData.files && e.clipboardData.files.length > 0) {
    const file = e.clipboardData.files[0]
    onFileSelected(file)
  }
}

function handleDragEnter(e) {
  e.preventDefault()
  isDragging.value = true
}

function handleDragOver(e) {
  e.preventDefault()
  isDragging.value = true
}

function handleDragLeave(e) {
  e.preventDefault()
  // Only turn off if leaving composer boundary
  if (!e.currentTarget.contains(e.relatedTarget)) {
    isDragging.value = false
  }
}

function handleDrop(e) {
  e.preventDefault()
  isDragging.value = false
  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    const file = e.dataTransfer.files[0]
    onFileSelected(file)
  }
}

async function handleSubmit() {
  const body = text.value.trim()
  const file = selectedFile.value

  if (!body && !file) return
  if (isSubmitting.value) return

  composerError.value = null
  isSubmitting.value = true
  try {
    await chatStore.sendMessage({
      body: body || null,
      file: file || null,
      replyToId: chatStore.replyingToMessage?.id || null,
    })

    text.value = ''
    selectedFile.value = null
    chatStore.sendTyping(false)
    adjustTextareaHeight()
  } catch (err) {
    composerError.value = chatStore.uploadError || err.response?.data?.message || 'Failed to send video / message.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    class="relative bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 transition-colors"
    @dragenter="handleDragEnter"
    @dragover="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
  >
    <!-- Upload / Send Error Alert Banner -->
    <div
      v-if="composerError"
      class="flex items-center justify-between px-4 py-2.5 bg-rose-50 dark:bg-rose-950/60 border-b border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300 transition-all select-none"
    >
      <div class="flex items-center gap-2 pr-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0 text-rose-500" />
        <span class="font-medium">{{ composerError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 dark:hover:text-rose-200 p-0.5 cursor-pointer rounded"
        title="Dismiss"
        @click="composerError = null"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Drag & Drop Overlay Indicator -->
    <div
      v-if="isDragging"
      class="absolute inset-0 z-20 bg-indigo-50/90 dark:bg-indigo-950/90 border-2 border-dashed border-indigo-500 rounded-t-xl flex items-center justify-center gap-2 backdrop-blur-xs text-indigo-700 dark:text-indigo-300 pointer-events-none transition-all"
    >
      <FileUp class="w-6 h-6 animate-bounce" />
      <span class="text-sm font-semibold">Drop file to attach (all types supported up to 100MB)</span>
    </div>

    <!-- Active Reply Banner -->
    <div
      v-if="chatStore.replyingToMessage"
      class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200/50 dark:border-gray-700/50 text-xs"
    >
      <div class="border-l-3 border-indigo-500 pl-2 truncate pr-2">
        <p class="font-semibold text-indigo-600 dark:text-indigo-400">
          Replying to {{ chatStore.replyingToMessage.sender?.name || 'User' }}
        </p>
        <p class="text-gray-500 dark:text-gray-400 truncate">
          {{ chatStore.replyingToMessage.body || '[Attachment]' }}
        </p>
      </div>
      <button
        type="button"
        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 cursor-pointer"
        @click="cancelReply"
      >
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Selected File Preview Pill -->
    <div
      v-if="selectedFile && !chatStore.isUploading"
      class="flex items-center justify-between px-4 py-2 bg-indigo-50/70 dark:bg-indigo-950/40 border-b border-indigo-100 dark:border-indigo-900/40 text-xs"
    >
      <div class="flex items-center gap-2 truncate pr-2">
        <div class="w-6 h-6 rounded-md bg-indigo-100 dark:bg-indigo-900/60 flex items-center justify-center flex-shrink-0 text-indigo-600 dark:text-indigo-400">
          <component :is="fileCategoryIcon" class="w-3.5 h-3.5" />
        </div>
        <span class="font-medium truncate text-gray-800 dark:text-gray-200">{{ selectedFile.name }}</span>
        <span class="text-gray-400 text-[11px] flex-shrink-0">({{ formattedSelectedSize }})</span>
      </div>
      <button
        type="button"
        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 cursor-pointer rounded-full hover:bg-black/5 dark:hover:bg-white/5"
        title="Remove file"
        @click="removeSelectedFile"
      >
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Active Upload Progress bar -->
    <UploadProgress
      v-if="chatStore.isUploading"
      :progress="chatStore.uploadProgress"
      :file-name="selectedFile?.name"
      :file-size="selectedFile?.size"
      :error="chatStore.uploadError"
      @cancel="removeSelectedFile"
    />

    <!-- Input Form -->
    <form class="flex items-end gap-2 p-3" @submit.prevent="handleSubmit">
      <!-- Attachment button -->
      <AttachmentButton @selected="onFileSelected" />

      <!-- Textarea -->
      <div class="flex-1 min-w-0 bg-gray-50 dark:bg-gray-800/80 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-all flex items-center px-3 py-1.5">
        <textarea
          ref="textareaRef"
          v-model="text"
          rows="1"
          placeholder="Type a message or drag/drop / paste files..."
          class="w-full bg-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 resize-none focus:outline-none max-h-32 py-1"
          @input="onInput"
          @keydown="handleKeydown"
          @paste="handlePaste"
        ></textarea>
      </div>

      <!-- Send button -->
      <button
        type="submit"
        :disabled="(!text.trim() && !selectedFile) || isSubmitting"
        class="p-2.5 rounded-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:hover:bg-indigo-600 text-white shadow-sm transition-all flex-shrink-0 flex items-center justify-center focus:outline-none cursor-pointer"
        title="Send message"
        aria-label="Send message"
      >
        <Send class="w-4 h-4" />
      </button>
    </form>
  </div>
</template>
