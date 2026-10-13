<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { MessageCircle, CheckCircle2, AlertCircle, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(true)
const isSuccess = ref(false)
const errorMessage = ref(null)

onMounted(async () => {
  const token = route.query.token
  if (!token) {
    isLoading.value = false
    errorMessage.value = 'Missing account activation token. Please verify your activation link.'
    return
  }

  try {
    await authStore.activateAccount(token)
    isSuccess.value = true
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Activation link is invalid or has expired.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen w-full flex items-center justify-center p-4 bg-slate-50 dark:bg-gray-950 font-sans select-none">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 p-8 sm:p-10 text-center">
      <!-- connectly Logo -->
      <div class="flex flex-col items-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 mb-3">
          <MessageCircle class="w-8 h-8 fill-current" />
        </div>
        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
          Account Activation
        </h1>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="py-8 flex flex-col items-center justify-center gap-3">
        <Loader2 class="w-8 h-8 animate-spin text-indigo-600" />
        <p class="text-xs text-gray-500 dark:text-gray-400">Activating your messaging account...</p>
      </div>

      <!-- Success State -->
      <div v-else-if="isSuccess" class="py-4 space-y-4">
        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
          Account Successfully Activated!
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Your email address has been verified. You can now sign in with your credentials to start messaging.
        </p>
        <button
          type="button"
          class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-md shadow-indigo-600/20 cursor-pointer"
          @click="router.push('/login')"
        >
          Sign In Now
        </button>
      </div>

      <!-- Error State -->
      <div v-else class="py-4 space-y-4">
        <div class="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
          <AlertCircle class="w-6 h-6" />
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
          Activation Failed
        </h3>
        <p class="text-xs text-rose-600 dark:text-rose-400">
          {{ errorMessage }}
        </p>
        <button
          type="button"
          class="w-full py-2.5 rounded-xl border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold text-xs transition-colors cursor-pointer"
          @click="router.push('/login')"
        >
          Back to Login
        </button>
      </div>
    </div>
  </div>
</template>
