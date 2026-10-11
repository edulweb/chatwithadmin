<script setup>
import { ref, computed } from 'vue'
import { Download, X, Maximize2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  attachment: {
    type: Object,
    required: true,
  },
})

const authStore = useAuthStore()
const isOpen = ref(false)

const imageUrl = computed(() => {
  const token = authStore.token
  return `${props.attachment.download_url}?preview=1&token=${token}`
})

const downloadUrl = computed(() => {
  const token = authStore.token
  return `${props.attachment.download_url}?token=${token}`
})

function openLightbox() {
  isOpen.value = true
}

function closeLightbox() {
  isOpen.value = false
}
</script>

<template>
  <div>
    <!-- Chat thumbnail preview -->
    <div
      class="group relative max-w-sm rounded-lg overflow-hidden cursor-pointer border border-black/5 dark:border-white/10 bg-black/5"
      @click="openLightbox"
    >
      <img
        :src="imageUrl"
        :alt="attachment.original_name"
        class="w-full max-h-72 object-cover transition-transform duration-200 group-hover:scale-[1.02]"
        loading="lazy"
      />
      <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 text-white">
        <Maximize2 class="w-5 h-5" />
        <span class="text-xs font-medium">Click to view</span>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm select-none"
        @click.self="closeLightbox"
      >
        <div class="absolute top-4 right-4 flex items-center gap-3">
          <a
            :href="downloadUrl"
            :download="attachment.original_name"
            target="_blank"
            class="p-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
            title="Download full size"
            @click.stop
          >
            <Download class="w-5 h-5" />
          </a>
          <button
            type="button"
            class="p-2.5 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
            title="Close"
            @click="closeLightbox"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="max-w-4xl max-h-[85vh] flex flex-col items-center">
          <img
            :src="imageUrl"
            :alt="attachment.original_name"
            class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl"
          />
          <div class="mt-3 text-xs text-gray-300 font-medium truncate max-w-md">
            {{ attachment.original_name }}
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
