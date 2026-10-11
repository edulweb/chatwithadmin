<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { MessageCircle, Lock, Mail, User, AtSign, Loader2, AlertCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const username = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref(null)
const isLoading = ref(false)

async function handleRegister() {
  errorMessage.value = null
  isLoading.value = true
  try {
    await authStore.register({
      name: name.value,
      username: username.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push('/')
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = err.response?.data?.message || 'Registration failed'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen w-full flex items-center justify-center p-4 bg-slate-50 dark:bg-gray-950 font-sans select-none">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 p-8 sm:p-10">
      <!-- ChatBox Logo -->
      <div class="flex flex-col items-center text-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 mb-3">
          <MessageCircle class="w-8 h-8 fill-current" />
        </div>
        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
          Join ChatBox
        </h1>
        <p class="text-xs text-gray-400 mt-1">Create your personal messaging account</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Register Form -->
      <form class="space-y-3.5" @submit.prevent="handleRegister">
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Full Name
          </label>
          <div class="relative flex items-center">
            <User class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="name"
              type="text"
              required
              placeholder="Alex Morgan"
              class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Username
          </label>
          <div class="relative flex items-center">
            <AtSign class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="username"
              type="text"
              required
              placeholder="alex"
              class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Email Address
          </label>
          <div class="relative flex items-center">
            <Mail class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="email"
              type="email"
              required
              placeholder="alex@example.com"
              class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Password (min 8 chars)
          </label>
          <div class="relative flex items-center">
            <Lock class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="password"
              type="password"
              required
              minlength="8"
              placeholder="••••••••"
              class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            Confirm Password
          </label>
          <div class="relative flex items-center">
            <Lock class="w-4 h-4 absolute left-3.5 text-gray-400" />
            <input
              v-model="passwordConfirmation"
              type="password"
              required
              placeholder="••••••••"
              class="w-full pl-10 pr-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-600/20 mt-3"
        >
          <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
          <span>Create Account</span>
        </button>
      </form>

      <!-- Login Link -->
      <div class="text-center mt-6 text-xs text-gray-500">
        Already have an account?
        <router-link to="/login" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline ml-1">
          Sign In
        </router-link>
      </div>
    </div>
  </div>
</template>
