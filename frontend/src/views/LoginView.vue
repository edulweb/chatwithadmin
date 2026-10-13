<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { MessageCircle, Lock, Mail, Loader2, AlertCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const loginInput = ref('')
const password = ref('')
const forgotEmail = ref('')
const errorMessage = ref(null)
const statusMessage = ref(null)
const isLoading = ref(false)
const mode = ref('login') // 'login' or 'forgot'
const canResendActivation = ref(false)

onMounted(() => {
  const notice = sessionStorage.getItem('auth_notice')
  if (notice) {
    errorMessage.value = notice
    sessionStorage.removeItem('auth_notice')
  }
})

async function handleLogin() {
  errorMessage.value = null
  statusMessage.value = null
  canResendActivation.value = false
  isLoading.value = true
  try {
    await authStore.login({
      login: loginInput.value,
      password: password.value,
    })
    router.push('/')
  } catch (err) {
    const msg = err.response?.data?.errors?.login?.[0] || err.response?.data?.message || 'Login failed'
    errorMessage.value = msg
    if (msg.toLowerCase().includes('activate') || msg.toLowerCase().includes('verify')) {
      canResendActivation.value = true
    }
  } finally {
    isLoading.value = false
  }
}

async function handleForgotPassword() {
  errorMessage.value = null
  statusMessage.value = null
  isLoading.value = true
  try {
    const res = await authStore.forgotPassword(forgotEmail.value)
    statusMessage.value = res.message || 'A password reset link has been sent to your email.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to send password reset link.'
  } finally {
    isLoading.value = false
  }
}

async function handleResendActivation() {
  if (!loginInput.value) return
  errorMessage.value = null
  statusMessage.value = null
  isLoading.value = true
  try {
    const res = await authStore.resendActivation(loginInput.value)
    statusMessage.value = res.message || 'Activation email resent. Please check your inbox.'
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to resend activation email.'
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
      <!-- connectly Logo -->
      <div class="flex flex-col items-center text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 mb-3">
          <MessageCircle class="w-8 h-8 fill-current" />
        </div>
        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
          {{ mode === 'login' ? 'connectly' : 'Reset Password' }}
        </h1>
        <p class="text-xs text-gray-400 mt-1">
          {{ mode === 'login' ? 'Sign in to your personal messaging account' : 'Enter your email to receive a password reset link' }}
        </p>
      </div>

      <!-- Success Alert -->
      <div v-if="statusMessage" class="mb-5 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
        <span>{{ statusMessage }}</span>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>
        <button
          v-if="canResendActivation"
          type="button"
          class="self-start text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
          @click="handleResendActivation"
        >
          Resend Activation Email
        </button>
      </div>

      <!-- Login Form -->
      <form v-if="mode === 'login'" class="space-y-4" @submit.prevent="handleLogin">
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
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
              Password
            </label>
            <button
              type="button"
              class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
              @click="mode = 'forgot'; errorMessage = null; statusMessage = null; forgotEmail = loginInput.includes('@') ? loginInput : ''"
            >
              Forgot password?
            </button>
          </div>
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

      <!-- Forgot Password Form -->
      <form v-else class="space-y-4" @submit.prevent="handleForgotPassword">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            Registered Email Address
          </label>
          <div class="relative flex items-center">
            <Mail class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="forgotEmail"
              type="email"
              required
              placeholder="your-email@example.com"
              class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading || !forgotEmail"
          class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-600/20 mt-2 cursor-pointer"
        >
          <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
          <span>Send Password Reset Link</span>
        </button>

        <div class="text-center pt-2">
          <button
            type="button"
            class="text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
            @click="mode = 'login'; errorMessage = null; statusMessage = null"
          >
            ← Back to Sign In
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
