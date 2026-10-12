<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { MessageCircle, Lock, Mail, Loader2, AlertCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const loginInput = ref('')
const password = ref('')
const errorMessage = ref(null)
const isLoading = ref(false)

onMounted(() => {
  const notice = sessionStorage.getItem('auth_notice')
  if (notice) {
    errorMessage.value = notice
    sessionStorage.removeItem('auth_notice')
  }
})

async function handleLogin() {
  errorMessage.value = null
  isLoading.value = true
  try {
    await authStore.login({
      login: loginInput.value,
      password: password.value,
    })
    router.push('/')
  } catch (err) {
    errorMessage.value = err.response?.data?.errors?.login?.[0] || err.response?.data?.message || 'Login failed'
  } finally {
    isLoading.value = false
  }
}

function fillDemo(email) {
  loginInput.value = email
  password.value = 'password123'
}
</script>

<template>
  <div class="min-h-screen w-full flex items-center justify-center p-4 bg-slate-50 dark:bg-gray-950 font-sans select-none">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 p-8 sm:p-10">
      <!-- ChatBox Logo -->
      <div class="flex flex-col items-center text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 mb-3">
          <MessageCircle class="w-8 h-8 fill-current" />
        </div>
        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
          ChatBox
        </h1>
        <p class="text-xs text-gray-400 mt-1">Sign in to your personal messaging account</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Login Form -->
      <form class="space-y-4" @submit.prevent="handleLogin">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            Email or Username
          </label>
          <div class="relative flex items-center">
            <Mail class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="loginInput"
              type="text"
              required
              placeholder="email/username"
              class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            Password
          </label>
          <div class="relative flex items-center">
            <Lock class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="password"
              type="password"
              required
              placeholder="••••••••"
              class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-600/20 mt-2 cursor-pointer"
        >
          <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
          <span>Sign In</span>
        </button>
      </form>

      <!-- Quick Demo Logins -->
      <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider text-center mb-2.5">
          Quick Demo Accounts
        </p>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-900 bg-indigo-50/50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 text-[11px] font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors flex flex-col items-center cursor-pointer"
            @click="fillDemo('alex@chatbox.test')"
          >
            <span class="font-bold">Alex (Admin)</span>
            <span class="text-[10px] opacity-75">alex@chatbox.test</span>
          </button>
          <button
            type="button"
            class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 text-[11px] font-medium hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex flex-col items-center cursor-pointer"
            @click="fillDemo('sarah@chatbox.test')"
          >
            <span class="font-bold">Sarah (User)</span>
            <span class="text-[10px] opacity-75">sarah@chatbox.test</span>
          </button>
        </div>
      </div>

      <!-- Registration Info -->
      <div class="text-center mt-5 text-xs text-gray-400">
        Account registration is managed by system administrators.
      </div>
    </div>
  </div>
</template>
