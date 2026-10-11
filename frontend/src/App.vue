<script setup>
import { onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()

onMounted(() => {
  settingsStore.initTheme()
})
</script>

<template>
  <router-view />
</template>
