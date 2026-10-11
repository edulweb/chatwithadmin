<script setup>
import { Check, CheckCheck } from 'lucide-vue-next'

defineProps({
  isRead: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <span class="inline-flex items-center ml-1 select-none">
    <CheckCheck
      v-if="isRead"
      class="w-3.5 h-3.5 text-sky-400 stroke-[2.5]"
      title="Read"
    />
    <Check
      v-else
      class="w-3.5 h-3.5 text-gray-400 dark:text-gray-400 stroke-[2.5]"
      title="Sent"
    />
  </span>
</template>
