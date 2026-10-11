<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Download, Maximize2, X, Film, Play } from 'lucide-vue-next'
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
const isOpen = ref(false)
const modalVideoRef = ref(null)

const videoUrl = computed(() => {
  const token = authStore.token || ''
  return `${props.attachment.download_url}?preview=1&token=${token}`
})

const downloadUrl = computed(() => {
  const token = authStore.token || ''
  return `${props.attachment.download_url}?token=${token}`
})

const formattedSize = computed(() => {
  const bytes = props.attachment.size_bytes || 0
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
})

async function openModal() {
  isOpen.value = true
  await nextTick()
  if (modalVideoRef.value) {
    modalVideoRef.value.currentTime = 0
    modalVideoRef.value.play().catch(() => {})
  }
}

function closeModal() {
  if (modalVideoRef.value) {
    modalVideoRef.value.pause()
  }
  isOpen.value = false
}

function handleKeydown(e) {
  if (e.key === 'Escape' && isOpen.value) {
    closeModal()
  }
}

function requestNativeFullscreen() {
  if (modalVideoRef.value) {
    if (modalVideoRef.value.requestFullscreen) {
      modalVideoRef.value.requestFullscreen()
    } else if (modalVideoRef.value.webkitRequestFullscreen) {
      modalVideoRef.value.webkitRequestFullscreen()
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  if (modalVideoRef.value) {
    modalVideoRef.value.pause()
  }
})
</script>

<template>
  <div class="video-preview-card max-w-sm sm:max-w-md w-full rounded-2xl overflow-hidden border border-black/10 dark:border-white/10 bg-black/90 shadow-md">
    <!-- Header info bar -->
    <div class="flex items-center justify-between px-3 py-1.5 bg-black/60 backdrop-blur-xs text-white/90 text-xs border-b border-white/10">
      <div class="flex items-center gap-1.5 min-w-0 pr-2">
        <Film class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" />
        <span class="truncate font-medium text-[11px]" :title="attachment.original_name">
          {{ attachment.original_name }}
        </span>
        <span class="text-[10px] text-white/60 flex-shrink-0">({{ formattedSize }})</span>
      </div>

      <div class="flex items-center gap-1 flex-shrink-0">
        <!-- Download button -->
        <a
          :href="downloadUrl"
          :download="attachment.original_name"
          target="_blank"
          class="p-1 rounded-md hover:bg-white/15 text-white/80 hover:text-white transition-colors cursor-pointer"
          title="Download video"
          @click.stop
        >
          <Download class="w-3.5 h-3.5" />
        </a>
      </div>
    </div>

    <!-- Thumbnail Card with Play Overlay (No Inline Playback) -->
    <div
      class="group relative bg-black aspect-video flex items-center justify-center cursor-pointer overflow-hidden"
      title="Click to play in popup / full screen"
      @click="openModal"
    >
      <!-- Video Poster / Preview Frame -->
      <video
        :src="`${videoUrl}#t=0.5`"
        preload="metadata"
        class="w-full h-full object-cover opacity-80 group-hover:opacity-95 group-hover:scale-105 transition-all duration-300 pointer-events-none"
      ></video>

      <!-- Shaded backdrop gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/30 pointer-events-none"></div>

      <!-- Centered Play Button -->
      <div class="absolute flex flex-col items-center gap-2 text-white transition-transform duration-200 group-hover:scale-110">
        <div class="w-14 h-14 rounded-full bg-indigo-600/90 group-hover:bg-indigo-500 text-white flex items-center justify-center shadow-xl backdrop-blur-xs border border-white/20">
          <Play class="w-6 h-6 fill-current ml-0.5" />
        </div>
        <span class="text-xs font-medium tracking-wide bg-black/60 px-2.5 py-0.5 rounded-full border border-white/10 text-white/90">
          Play video
        </span>
      </div>

      <!-- Top-right expand badge -->
      <div class="absolute top-2 right-2 p-1.5 rounded-lg bg-black/50 hover:bg-black/80 text-white/80 hover:text-white transition-colors backdrop-blur-xs">
        <Maximize2 class="w-3.5 h-3.5" />
      </div>
    </div>

    <!-- Fullscreen / Popup Lightbox Modal -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/95 p-4 sm:p-6 backdrop-blur-md select-none"
        @click.self="closeModal"
      >
        <!-- Modal Top Bar -->
        <div class="w-full max-w-5xl flex items-center justify-between pb-3 text-white border-b border-white/10 mb-3">
          <div class="flex items-center gap-2 min-w-0 pr-4">
            <Film class="w-5 h-5 text-indigo-400 flex-shrink-0" />
            <div class="min-w-0">
              <p class="text-sm font-semibold truncate">{{ attachment.original_name }}</p>
              <p class="text-xs text-gray-400">{{ formattedSize }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <!-- Native Fullscreen button -->
            <button
              type="button"
              class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors flex items-center gap-1.5 text-xs font-medium cursor-pointer"
              title="Full screen"
              @click="requestNativeFullscreen"
            >
              <Maximize2 class="w-4 h-4" />
              <span class="hidden sm:inline">Fullscreen</span>
            </button>

            <!-- Download -->
            <a
              :href="downloadUrl"
              :download="attachment.original_name"
              target="_blank"
              class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors flex items-center gap-1.5 text-xs font-medium"
              title="Download video"
            >
              <Download class="w-4 h-4" />
              <span class="hidden sm:inline">Download</span>
            </a>

            <!-- Close Modal -->
            <button
              type="button"
              class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
              title="Close (Esc)"
              @click="closeModal"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Popup Video Player -->
        <div class="w-full max-w-5xl flex-1 max-h-[82vh] flex items-center justify-center bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10">
          <video
            ref="modalVideoRef"
            :src="videoUrl"
            controls
            autoplay
            playsinline
            class="w-full h-full max-h-[82vh] object-contain focus:outline-none"
          >
            Your browser does not support playing this video format.
          </video>
        </div>
      </div>
    </Teleport>
  </div>
</template>
