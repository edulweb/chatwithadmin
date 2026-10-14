<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { MessageCircle, KeyRound, CheckCircle2, AlertCircle, Loader2, Lock } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const isSubmitting = ref(false)
const isSuccess = ref(false)
const errorMessage = ref(null)

onMounted(() => {
  token.value = route.query.token || ''
  email.value = route.query.email || ''

  if (!token.value || !email.value) {
    errorMessage.value = 'Invalid password reset link. Please request a new link from the login page.'
  }
})

const handleResetPassword = async () => {
  if (!password.value || password.value.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters long.'
    return
  }

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = null

  try {
    await authStore.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    isSuccess.value = true
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Failed to reset password. The link may have expired.'
    }
  } finally {
    isSubmitting.value = false
  }
}
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
          Set New Password
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Enter your new password below to regain access.
        </p>
      </div>

      <!-- Success State -->
      <div v-if="isSuccess" class="py-4 space-y-4">
        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
          Password Updated!
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Your password has been changed successfully. You can now log in with your new password.
        </p>
        <button
          type="button"
          class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-md shadow-indigo-600/20 cursor-pointer"
          @click="router.push({ name: 'login' })"
        >
          Sign In Now
        </button>
      </div>

      <!-- Reset Form -->
      <form v-else @submit.prevent="handleResetPassword" class="space-y-4 text-left">
        <!-- Error alert -->
        <div v-if="errorMessage" class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl flex items-start gap-2.5 text-xs text-rose-600 dark:text-rose-400">
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            Email Address
          </label>
          <input
            type="email"
            v-model="email"
            disabled
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs cursor-not-allowed"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            New Password
          </label>
          <div class="relative">
            <Lock class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="password"
              v-model="password"
              required
              minlength="8"
              placeholder="••••••••"
              class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder-gray-400 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
            Confirm New Password
          </label>
          <div class="relative">
            <KeyRound class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="password"
              v-model="passwordConfirmation"
              required
              minlength="8"
              placeholder="••••••••"
              class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 placeholder-gray-400 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isSubmitting || !token || !email"
          class="w-full py-3 mt-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors shadow-md shadow-indigo-600/20 cursor-pointer flex items-center justify-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          <span>{{ isSubmitting ? 'Updating Password...' : 'Reset Password' }}</span>
        </button>

        <div class="pt-2 text-center">
          <router-link :to="{ name: 'login' }" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
            Back to Sign In
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
