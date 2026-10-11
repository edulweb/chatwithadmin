import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSettingsStore = defineStore('settings', () => {
  const getInitialTheme = () => {
    try {
      const saved = localStorage.getItem('chatbox_theme')
      if (saved) return saved
      return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    } catch (e) {
      return 'light'
    }
  }

  const theme = ref(getInitialTheme())

  function applyTheme(newTheme) {
    theme.value = newTheme
    try {
      localStorage.setItem('chatbox_theme', newTheme)
    } catch (e) {}

    if (newTheme === 'dark') {
      document.documentElement.classList.add('dark')
      document.documentElement.setAttribute('data-theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      document.documentElement.setAttribute('data-theme', 'light')
    }
  }

  function toggleTheme() {
    applyTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  function initTheme() {
    applyTheme(theme.value)
  }

  // Apply theme immediately upon store creation
  initTheme()

  return {
    theme,
    toggleTheme,
    initTheme,
  }
})
