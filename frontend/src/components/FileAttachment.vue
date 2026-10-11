<script setup>
import { computed } from 'vue'
import {
  FileText,
  FileSpreadsheet,
  FileArchive,
  FileCode,
  Presentation,
  Film,
  Music,
  File,
  Download,
  Eye
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  attachment: {
    type: Object,
    required: true,
  },
  isSentByMe: {
    type: Boolean,
    default: false,
  },
})

const authStore = useAuthStore()

const formattedSize = computed(() => {
  const bytes = props.attachment.size_bytes || 0
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
})

const fileDetails = computed(() => {
  const mime = (props.attachment.mime_type || '').toLowerCase()
  const name = (props.attachment.original_name || '').toLowerCase()

  if (mime.includes('pdf') || name.endsWith('.pdf')) {
    return { icon: FileText, color: 'text-red-500 bg-red-50 dark:bg-red-950/50', canPreview: true }
  }
  if (
    mime.includes('sheet') ||
    mime.includes('excel') ||
    mime.includes('csv') ||
    /\.(xls|xlsx|csv|tsv|ods)$/.test(name)
  ) {
    return { icon: FileSpreadsheet, color: 'text-emerald-500 bg-emerald-50 dark:bg-emerald-950/50', canPreview: false }
  }
  if (
    mime.includes('presentation') ||
    mime.includes('powerpoint') ||
    /\.(ppt|pptx|key|odp)$/.test(name)
  ) {
    return { icon: Presentation, color: 'text-amber-500 bg-amber-50 dark:bg-amber-950/50', canPreview: false }
  }
  if (
    mime.includes('zip') ||
    mime.includes('tar') ||
    mime.includes('archive') ||
    /\.(zip|rar|7z|tar|gz|bz2)$/.test(name)
  ) {
    return { icon: FileArchive, color: 'text-purple-500 bg-purple-50 dark:bg-purple-950/50', canPreview: false }
  }
  if (
    mime.includes('javascript') ||
    mime.includes('json') ||
    mime.includes('xml') ||
    mime.includes('html') ||
    /\.(js|ts|jsx|tsx|py|php|json|xml|html|css|scss|sql|sh|c|cpp|h|java|go|rs)$/.test(name)
  ) {
    return { icon: FileCode, color: 'text-cyan-500 bg-cyan-50 dark:bg-cyan-950/50', canPreview: true }
  }
  if (mime.includes('text') || /\.(txt|md|log|ini|env)$/.test(name)) {
    return { icon: FileText, color: 'text-blue-500 bg-blue-50 dark:bg-blue-950/50', canPreview: true }
  }
  if (mime.startsWith('video/') || /\.(mp4|webm|mkv|mov|avi)$/.test(name)) {
    return { icon: Film, color: 'text-rose-500 bg-rose-50 dark:bg-rose-950/50', canPreview: true }
  }
  if (mime.startsWith('audio/') || /\.(mp3|wav|ogg|m4a|flac)$/.test(name)) {
    return { icon: Music, color: 'text-violet-500 bg-violet-50 dark:bg-violet-950/50', canPreview: true }
  }

  return { icon: File, color: 'text-indigo-500 bg-indigo-50 dark:bg-indigo-950/50', canPreview: false }
})

const downloadUrl = computed(() => {
  const token = authStore.token || ''
  return `${props.attachment.download_url}?token=${token}`
})

const previewUrl = computed(() => {
  const token = authStore.token || ''
  return `${props.attachment.download_url}?preview=1&token=${token}`
})
</script>

<template>
  <div
    :class="[
      'flex items-center gap-3 p-3 rounded-xl border transition-colors max-w-sm w-full',
      isSentByMe
        ? 'bg-indigo-700/60 border-indigo-400/30 text-white'
        : 'border-gray-200 dark:border-gray-700/80 bg-white/70 dark:bg-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800'
    ]"
  >
    <!-- Icon Container -->
    <div
      :class="[
        'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
        isSentByMe ? 'bg-white/20 text-white' : fileDetails.color
      ]"
    >
      <component :is="fileDetails.icon" class="w-5 h-5" />
    </div>

    <!-- File info -->
    <div class="flex-1 min-w-0">
      <p
        :class="[
          'text-xs font-semibold truncate',
          isSentByMe ? 'text-white' : 'text-gray-900 dark:text-gray-100'
        ]"
        :title="attachment.original_name"
      >
        {{ attachment.original_name }}
      </p>
      <p
        :class="[
          'text-[11px] mt-0.5',
          isSentByMe ? 'text-indigo-200' : 'text-gray-500 dark:text-gray-400'
        ]"
      >
        {{ formattedSize }}
      </p>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-1 flex-shrink-0">
      <!-- Preview/View button for PDF / Text / Code -->
      <a
        v-if="fileDetails.canPreview"
        :href="previewUrl"
        target="_blank"
        :class="[
          'p-2 rounded-lg transition-colors cursor-pointer',
          isSentByMe
            ? 'text-white/80 hover:text-white hover:bg-white/15'
            : 'text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700'
        ]"
        title="View in new tab"
      >
        <Eye class="w-4 h-4" />
      </a>

      <!-- Download Button -->
      <a
        :href="downloadUrl"
        :download="attachment.original_name"
        :class="[
          'p-2 rounded-lg transition-colors cursor-pointer',
          isSentByMe
            ? 'text-white/80 hover:text-white hover:bg-white/15'
            : 'text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700'
        ]"
        title="Download file"
        target="_blank"
      >
        <Download class="w-4 h-4" />
      </a>
    </div>
  </div>
</template>
