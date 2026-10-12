<script setup>
import { ref } from 'vue'
import { X, UserPlus, ShieldCheck, User, AtSign, Mail, Lock, Loader2, AlertCircle, CheckCircle2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'
import { useChatStore } from '@/stores/chatStore'
import { useUserStore } from '@/stores/userStore'

const emit = defineEmits(['close', 'user-registered'])

const authStore = useAuthStore()
const chatStore = useChatStore()
const userStore = useUserStore()

const name = ref('')
const username = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const isUserAdmin = ref(false)
const requireActivation = ref(true)
const errorMessage = ref(null)
const successUser = ref(null)
const isLoading = ref(false)

async function handleRegister() {
  errorMessage.value = null
  successUser.value = null
  isLoading.value = true
  try {
    const res = await authStore.adminRegisterUser({
      name: name.value,
      username: username.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      is_admin: isUserAdmin.value,
      require_activation: requireActivation.value,
    })
    successUser.value = res.data
    // Refresh user search results or conversations if needed
    userStore.fetchUsers()
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

async function startChatWithCreatedUser() {
  if (!successUser.value) return
  try {
    await chatStore.startConversationWithUser(successUser.value.id)
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Could not start conversation'
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 select-none" @click.self="emit('close')">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <UserPlus class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
          <span>Register New User</span>
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg cursor-pointer"
          @click="emit('close')"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="p-5 overflow-y-auto">
        <!-- Success State -->
        <div v-if="successUser" class="text-center py-4 space-y-3">
          <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100">
            User Created Successfully!
          </h4>
          <p class="text-xs text-gray-500 max-w-xs mx-auto">
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ successUser.name }}</span> (@{{ successUser.username }}) has been registered {{ successUser.is_admin ? 'as an Administrator' : 'as a Regular User' }}.
          </p>
          <div v-if="!successUser.is_active" class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-amber-700 dark:text-amber-300 text-[11px] text-center max-w-xs mx-auto">
            An activation link has been sent to {{ successUser.email }}. The user must activate their account before signing in.
          </div>

          <div class="pt-3 flex gap-2 justify-center">
            <button
              type="button"
              class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm cursor-pointer"
              @click="startChatWithCreatedUser"
            >
              Start Chat With {{ successUser.name.split(' ')[0] }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 font-semibold text-xs transition-colors cursor-pointer"
              @click="successUser = null; name = ''; username = ''; email = ''; password = ''; passwordConfirmation = ''; isUserAdmin = false; requireActivation = true;"
            >
              Register Another
            </button>
          </div>
        </div>

        <!-- Form State -->
        <form v-else class="space-y-3" @submit.prevent="handleRegister">
          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
              Full Name
            </label>
            <div class="relative flex items-center">
              <User class="w-4 h-4 absolute left-3 text-gray-400" />
              <input
                v-model="name"
                type="text"
                required
                placeholder="Jane Doe"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
              Username
            </label>
            <div class="relative flex items-center">
              <AtSign class="w-4 h-4 absolute left-3 text-gray-400" />
              <input
                v-model="username"
                type="text"
                required
                placeholder="janedoe"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
              Email Address
            </label>
            <div class="relative flex items-center">
              <Mail class="w-4 h-4 absolute left-3 text-gray-400" />
              <input
                v-model="email"
                type="email"
                required
                placeholder="jane@example.com"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
              Password
            </label>
            <div class="relative flex items-center">
              <Lock class="w-4 h-4 absolute left-3 text-gray-400" />
              <input
                v-model="password"
                type="password"
                required
                minlength="8"
                placeholder="••••••••"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
              Confirm Password
            </label>
            <div class="relative flex items-center">
              <Lock class="w-4 h-4 absolute left-3 text-gray-400" />
              <input
                v-model="passwordConfirmation"
                type="password"
                required
                placeholder="••••••••"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Role Toggle -->
          <div class="flex items-center gap-2 pt-1">
            <input
              id="modalAdminCheck"
              v-model="isUserAdmin"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
            />
            <label for="modalAdminCheck" class="text-xs text-gray-700 dark:text-gray-300 font-medium cursor-pointer">
              Grant Administrator Privileges (Admin Role)
            </label>
          </div>

          <!-- Activation Email Toggle -->
          <div class="flex items-center gap-2 pb-1">
            <input
              id="modalRequireActivation"
              v-model="requireActivation"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
            />
            <label for="modalRequireActivation" class="text-xs text-gray-700 dark:text-gray-300 font-medium cursor-pointer">
              Send Email Activation Link (User must activate before login)
            </label>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2 shadow-sm cursor-pointer"
            >
              <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
              <span>Register User</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
