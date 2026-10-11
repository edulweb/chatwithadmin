<script setup>
import { computed } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  name: {
    type: String,
    default: '',
  },
  avatarUrl: {
    type: String,
    default: null,
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl
  },
  isOnline: {
    type: Boolean,
    default: false,
  },
  showStatus: {
    type: Boolean,
    default: false,
  },
})

const displayName = computed(() => {
  return props.user?.name || props.name || 'User'
})

const resolvedAvatar = computed(() => {
  return props.user?.avatar_url || props.avatarUrl || null
})

const initials = computed(() => {
  const parts = displayName.value.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return displayName.value.slice(0, 2).toUpperCase()
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-8 h-8 text-xs'
    case 'lg':
      return 'w-12 h-12 text-base'
    case 'xl':
      return 'w-16 h-16 text-xl'
    case 'md':
    default:
      return 'w-10 h-10 text-sm'
  }
})

const statusSizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-2.5 h-2.5 border'
    case 'lg':
      return 'w-3.5 h-3.5 border-2'
    case 'xl':
      return 'w-4 h-4 border-2'
    case 'md':
    default:
      return 'w-3 h-3 border-2'
  }
})

// Deterministic pastel color for initials avatar
const avatarBg = computed(() => {
  const colors = [
    'bg-indigo-600',
    'bg-emerald-600',
    'bg-violet-600',
    'bg-sky-600',
    'bg-amber-600',
    'bg-rose-600',
    'bg-teal-600',
  ]
  let hash = 0
  for (let i = 0; i < displayName.value.length; i++) {
    hash = displayName.value.charCodeAt(i) + ((hash << 5) - hash)
  }
  return colors[Math.abs(hash) % colors.length]
})
</script>

<template>
  <div class="relative inline-flex flex-shrink-0">
    <div
      :class="[
        sizeClasses,
        'rounded-full overflow-hidden flex items-center justify-center font-semibold text-white select-none shadow-sm transition-all',
        avatarBg
      ]"
    >
      <img
        v-if="resolvedAvatar"
        :src="resolvedAvatar"
        :alt="displayName"
        class="w-full h-full object-cover"
        @error="(e) => e.target.style.display = 'none'"
      />
      <span v-else>{{ initials }}</span>
    </div>

    <!-- Online indicator -->
    <span
      v-if="showStatus"
      :class="[
        statusSizeClasses,
        'absolute bottom-0 right-0 rounded-full border-white dark:border-gray-900',
        isOnline ? 'bg-emerald-500' : 'bg-gray-400'
      ]"
      :title="isOnline ? 'Online' : 'Offline'"
    ></span>
  </div>
</template>
