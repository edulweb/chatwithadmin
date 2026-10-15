<script setup>
import { ref } from 'vue'
import { X, Camera, Lock, User, LogOut, Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import { useAuthStore } from '@/stores/authStore'

const emit = defineEmits(['close'])

const authStore = useAuthStore()

const activeTab = ref('profile') // 'profile' or 'password'

// Profile form
const name = ref(authStore.user?.name || '')
const username = ref(authStore.user?.username || '')
const email = ref(authStore.user?.email || '')
const avatarFile = ref(null)
const avatarPreview = ref(null)

// Password form
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')

const statusMessage = ref(null)
const errorMessage = ref(null)
const isSubmitting = ref(false)

function onAvatarChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function handleProfileSubmit() {
  errorMessage.value = null
  statusMessage.value = null
  isSubmitting.value = true

  try {
    const formData = new FormData()
    formData.append('name', name.value)
    formData.append('username', username.value)
    formData.append('email', email.value)
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    await authStore.updateProfile(formData)
    avatarFile.value = null
    avatarPreview.value = null
    statusMessage.value = 'Profile updated successfully!'
    setTimeout(() => (statusMessage.value = null), 4000)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update profile'
  } finally {
    isSubmitting.value = false
  }
}

async function handlePasswordSubmit() {
  errorMessage.value = null
  statusMessage.value = null
  isSubmitting.value = true

  try {
    await authStore.changePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: newPasswordConfirmation.value,
    })
    statusMessage.value = 'Password changed successfully!'
    currentPassword.value = ''
    newPassword.value = ''
    newPasswordConfirmation.value = ''
    setTimeout(() => (statusMessage.value = null), 4000)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to change password'
  } finally {
    isSubmitting.value = false
  }
}

async function handleLogout() {
  if (confirm('Are you sure you want to log out?')) {
    await authStore.logout()
    window.location.href = '/login'
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4 select-none" @click.self="emit('close')">
    <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <User class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
          <span>My Profile</span>
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg"
          @click="emit('close')"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex border-b border-gray-100 dark:border-gray-800 text-xs font-semibold">
        <button
          type="button"
          :class="[
            'flex-1 py-3 text-center border-b-2 transition-colors',
            activeTab === 'profile'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
          ]"
          @click="activeTab = 'profile'"
        >
          Profile Details
        </button>
        <button
          type="button"
          :class="[
            'flex-1 py-3 text-center border-b-2 transition-colors',
            activeTab === 'password'
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
          ]"
          @click="activeTab = 'password'"
        >
          Change Password
        </button>
      </div>

      <!-- Body / Form -->
      <div class="flex-1 overflow-y-auto p-5">
        <!-- Success Alert -->
        <div v-if="statusMessage" class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
          <span>{{ statusMessage }}</span>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="mb-4 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Tab: Profile Details -->
        <form v-if="activeTab === 'profile'" class="space-y-4" @submit.prevent="handleProfileSubmit">
          <!-- Avatar Uploader -->
          <div class="flex flex-col items-center justify-center gap-2 pb-2">
            <div class="relative group">
              <UserAvatar
                :user="authStore.user"
                :avatar-url="avatarPreview"
                size="xl"
              />
              <label
                for="avatar-upload"
                class="absolute inset-0 rounded-full bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity"
                title="Change avatar"
              >
                <Camera class="w-5 h-5" />
              </label>
              <input
                id="avatar-upload"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/jpg"
                class="hidden"
                @change="onAvatarChange"
              />
            </div>
            <p class="text-[11px] text-gray-400">Click photo to update avatar</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
            <input
              v-model="name"
              type="text"
              required
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Username</label>
            <input
              v-model="username"
              type="text"
              required
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <!-- Pending Email Verification Notice -->
          <div v-if="authStore.user?.pending_email" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-[11px] text-amber-800 dark:text-amber-300 space-y-1">
            <div class="font-semibold flex items-center gap-1.5">
              <span>Verification Pending</span>
            </div>
            <p>
              A confirmation link was sent to <strong class="underline">{{ authStore.user.pending_email }}</strong>. Your email will not update until you click that link.
            </p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
            <input
              v-model="email"
              type="email"
              required
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p class="text-[10px] text-gray-400 mt-1">
              Changing your email will send a verification link to the new address before taking effect.
            </p>
          </div>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <span>Save Profile Changes</span>
          </button>
        </form>

        <!-- Tab: Change Password -->
        <form v-else-if="activeTab === 'password'" class="space-y-4" @submit.prevent="handlePasswordSubmit">
          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
            <input
              v-model="currentPassword"
              type="password"
              required
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">New Password (min 8 chars)</label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="8"
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              class="w-full text-xs px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <span>Update Password</span>
          </button>
        </form>

        <!-- Logout Button -->
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
          <button
            type="button"
            class="w-full py-2 px-3 rounded-xl border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
            @click="handleLogout"
          >
            <LogOut class="w-4 h-4" />
            <span>Log Out of connectly</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
