<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Play, Pause, Download, Volume2, VolumeX, Music } from 'lucide-vue-next'
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

const audioRef = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const isMuted = ref(false)
const playbackRate = ref(1)
const isSeeking = ref(false)

const audioUrl = computed(() => {
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

function formatTime(seconds) {
  if (isNaN(seconds) || seconds === Infinity || seconds < 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs < 10 ? '0' : ''}${secs}`
}

function togglePlay() {
  if (!audioRef.value) return
  if (isPlaying.value) {
    audioRef.value.pause()
  } else {
    audioRef.value.play().catch(() => {})
  }
}

function toggleMute() {
  if (!audioRef.value) return
  isMuted.value = !isMuted.value
  audioRef.value.muted = isMuted.value
}

function cyclePlaybackRate() {
  if (!audioRef.value) return
  const rates = [1, 1.25, 1.5, 2]
  const currentIndex = rates.indexOf(playbackRate.value)
  const nextRate = rates[(currentIndex + 1) % rates.length]
  playbackRate.value = nextRate
  audioRef.value.playbackRate = nextRate
}

function onLoadedMetadata() {
  if (audioRef.value) {
    duration.value = audioRef.value.duration || 0
  }
}

function onTimeUpdate() {
  if (audioRef.value && !isSeeking.value) {
    currentTime.value = audioRef.value.currentTime
  }
}

function onEnded() {
  isPlaying.value = false
  currentTime.value = 0
}

function onPlay() {
  isPlaying.value = true
}

function onPause() {
  isPlaying.value = false
}

function handleSeekInput(e) {
  isSeeking.value = true
  currentTime.value = parseFloat(e.target.value)
}

function handleSeekChange(e) {
  if (audioRef.value) {
    audioRef.value.currentTime = parseFloat(e.target.value)
  }
  isSeeking.value = false
}

onUnmounted(() => {
  if (audioRef.value) {
    audioRef.value.pause()
  }
})
</script>

<template>
  <div
    :class="[
      'audio-player-card flex flex-col gap-2 p-3 rounded-2xl border transition-all max-w-sm sm:max-w-md w-full select-none',
      isSentByMe
        ? 'bg-indigo-700/60 border-indigo-400/30 text-white'
        : 'bg-white/80 dark:bg-gray-800/90 border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-gray-100 shadow-xs'
    ]"
  >
    <!-- Hidden HTML5 Audio Element -->
    <audio
      ref="audioRef"
      :src="audioUrl"
      preload="metadata"
      @loadedmetadata="onLoadedMetadata"
      @timeupdate="onTimeUpdate"
      @play="onPlay"
      @pause="onPause"
      @ended="onEnded"
    ></audio>

    <!-- Header: icon, file name, download button -->
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 min-w-0 pr-1">
        <div
          :class="[
            'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
            isSentByMe
              ? 'bg-white/20 text-white'
              : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
          ]"
        >
          <Music class="w-4 h-4" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-semibold truncate leading-tight" :title="attachment.original_name">
            {{ attachment.original_name }}
          </p>
          <p
            :class="[
              'text-[10px] mt-0.5 leading-tight',
              isSentByMe ? 'text-indigo-200' : 'text-gray-500 dark:text-gray-400'
            ]"
          >
            {{ formattedSize }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-1 flex-shrink-0">
        <!-- Speed toggle -->
        <button
          type="button"
          :class="[
            'px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors cursor-pointer',
            isSentByMe
              ? 'bg-white/15 hover:bg-white/25 text-white'
              : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'
          ]"
          title="Change playback speed"
          @click="cyclePlaybackRate"
        >
          {{ playbackRate }}x
        </button>

        <!-- Download button -->
        <a
          :href="downloadUrl"
          :download="attachment.original_name"
          target="_blank"
          :class="[
            'p-1.5 rounded-lg transition-colors cursor-pointer',
            isSentByMe
              ? 'text-white/80 hover:text-white hover:bg-white/15'
              : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
          title="Download audio"
        >
          <Download class="w-3.5 h-3.5" />
        </a>
      </div>
    </div>

    <!-- Playback Controls and Scrubber Bar -->
    <div class="flex items-center gap-3 pt-1">
      <!-- Play/Pause Button -->
      <button
        type="button"
        :class="[
          'w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition-all shadow-xs cursor-pointer',
          isSentByMe
            ? 'bg-white text-indigo-700 hover:bg-indigo-50 active:scale-95'
            : 'bg-indigo-600 text-white hover:bg-indigo-700 dark:hover:bg-indigo-500 active:scale-95'
        ]"
        :title="isPlaying ? 'Pause' : 'Play'"
        @click="togglePlay"
      >
        <Pause v-if="isPlaying" class="w-4 h-4 fill-current" />
        <Play v-else class="w-4 h-4 fill-current ml-0.5" />
      </button>

      <!-- Scrubber track & Time display -->
      <div class="flex-1 min-w-0 flex flex-col justify-center gap-1">
        <input
          type="range"
          min="0"
          :max="duration || 100"
          step="0.1"
          :value="currentTime"
          :class="[
            'w-full h-1.5 rounded-lg appearance-none cursor-pointer focus:outline-none transition-all',
            isSentByMe
              ? 'accent-white bg-white/30'
              : 'accent-indigo-600 bg-gray-200 dark:bg-gray-700'
          ]"
          @input="handleSeekInput"
          @change="handleSeekChange"
        />

        <div
          :class="[
            'flex items-center justify-between text-[10px] font-mono select-none',
            isSentByMe ? 'text-indigo-200' : 'text-gray-500 dark:text-gray-400'
          ]"
        >
          <span>{{ formatTime(currentTime) }}</span>
          <span>{{ formatTime(duration) }}</span>
        </div>
      </div>

      <!-- Mute/Unmute Button -->
      <button
        type="button"
        :class="[
          'p-1.5 rounded-lg transition-colors cursor-pointer flex-shrink-0',
          isSentByMe
            ? 'text-white/80 hover:text-white hover:bg-white/15'
            : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
        ]"
        :title="isMuted ? 'Unmute' : 'Mute'"
        @click="toggleMute"
      >
        <VolumeX v-if="isMuted" class="w-4 h-4" />
        <Volume2 v-else class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
