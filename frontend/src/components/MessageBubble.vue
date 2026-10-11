<script setup>
import { ref, computed } from 'vue'
import { Ban, Check, X } from 'lucide-vue-next'
import UserAvatar from './UserAvatar.vue'
import ReadReceipt from './ReadReceipt.vue'
import ImagePreview from './ImagePreview.vue'
import VideoPlayer from './VideoPlayer.vue'
import AudioPlayer from './AudioPlayer.vue'
import FileAttachment from './FileAttachment.vue'
import MessageActions from './MessageActions.vue'
import { useAuthStore } from '@/stores/authStore'
import { useChatStore } from '@/stores/chatStore'

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
  previousMessage: {
    type: Object,
    default: null,
  },
})

const authStore = useAuthStore()
const chatStore = useChatStore()

const isEditing = ref(false)
const editInput = ref(props.message.body)
const isHovered = ref(false)

const isSentByMe = computed(() => {
  return authStore.user && props.message.sender_id === authStore.user.id
})

const formattedTime = computed(() => {
  if (!props.message.created_at) return ''
  const date = new Date(props.message.created_at)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
})

function getAttachmentCategory(a) {
  const name = (a.original_name || '').toLowerCase()
  const ext = name.split('.').pop() || ''
  const mime = (a.mime_type || '').toLowerCase()

  // 1. Audio check first: ensure m4a, mp3, etc. are exclusively treated as audio
  const isAudioExt = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma', 'weba', 'mid', 'midi'].includes(ext)
  if (isAudioExt || a.is_audio || mime.startsWith('audio/')) {
    return 'audio'
  }

  // 2. Video check
  const isVideoExt = ['mp4', 'webm', 'ogv', 'mov', 'mkv', 'avi', 'wmv', 'flv', 'm4v', '3gp'].includes(ext)
  if (isVideoExt || a.is_video || mime.startsWith('video/')) {
    return 'video'
  }

  // 3. Image check
  const isImageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'ico', 'avif'].includes(ext)
  if (isImageExt || a.is_image || mime.startsWith('image/')) {
    return 'image'
  }

  return 'document'
}

const images = computed(() => {
  return (props.message.attachments || []).filter((a) => getAttachmentCategory(a) === 'image')
})

const videos = computed(() => {
  return (props.message.attachments || []).filter((a) => getAttachmentCategory(a) === 'video')
})

const audios = computed(() => {
  return (props.message.attachments || []).filter((a) => getAttachmentCategory(a) === 'audio')
})

const documents = computed(() => {
  return (props.message.attachments || []).filter((a) => getAttachmentCategory(a) === 'document')
})

function handleReply() {
  chatStore.replyingToMessage = props.message
}

function handleCopy() {
  if (props.message.body) {
    navigator.clipboard.writeText(props.message.body)
  }
}

function handleEdit() {
  isEditing.value = true
  editInput.value = props.message.body
}

async function saveEdit() {
  if (!editInput.value || editInput.value.trim() === '') return
  if (editInput.value === props.message.body) {
    isEditing.value = false
    return
  }
  try {
    await chatStore.editMessage(props.message.id, editInput.value.trim())
    isEditing.value = false
  } catch (err) {
    alert('Failed to update message')
  }
}

function cancelEdit() {
  isEditing.value = false
  editInput.value = props.message.body
}

async function handleDelete() {
  if (confirm('Are you sure you want to delete this message?')) {
    try {
      await chatStore.deleteMessage(props.message.id)
    } catch (err) {
      alert('Failed to delete message')
    }
  }
}
</script>

<template>
  <div
    :class="[
      'group relative flex flex-col my-1 max-w-[85%] sm:max-w-[70%]',
      isSentByMe ? 'ml-auto items-end' : 'mr-auto items-start'
    ]"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Floating Action menu on hover -->
    <div
      v-if="!isEditing"
      :class="[
        'absolute -top-7 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-150',
        isSentByMe ? 'right-0' : 'left-0'
      ]"
    >
      <MessageActions
        :is-sent-by-me="isSentByMe"
        :is-deleted="message.is_deleted"
        @reply="handleReply"
        @copy="handleCopy"
        @edit="handleEdit"
        @delete="handleDelete"
      />
    </div>

    <!-- Message Bubble Box -->
    <div
      :class="[
        'relative rounded-2xl p-3 shadow-sm transition-all',
        isSentByMe
          ? 'bg-indigo-600 text-white rounded-br-xs'
          : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-xs border border-gray-100 dark:border-gray-700/60'
      ]"
    >
      <!-- Reply Preview Header if replying to a message -->
      <div
        v-if="message.reply_to"
        :class="[
          'mb-2 p-2 rounded-lg text-xs border-l-4 cursor-pointer',
          isSentByMe
            ? 'bg-indigo-700/60 border-white/60 text-white/90'
            : 'bg-gray-100 dark:bg-gray-700/70 border-indigo-500 text-gray-700 dark:text-gray-300'
        ]"
      >
        <span class="font-semibold block text-[11px] mb-0.5">
          {{ message.reply_to.sender_name }}
        </span>
        <span class="truncate block line-clamp-1 italic">
          {{ message.reply_to.body || '[Attachment]' }}
        </span>
      </div>

      <!-- Editing input -->
      <div v-if="isEditing" class="flex flex-col gap-2 min-w-[240px]">
        <textarea
          v-model="editInput"
          rows="2"
          class="w-full text-xs p-2 rounded bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-1 focus:ring-indigo-400 resize-none"
          @keydown.enter.exact.prevent="saveEdit"
          @keydown.esc="cancelEdit"
        ></textarea>
        <div class="flex items-center justify-end gap-1.5">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded bg-white/20 hover:bg-white/30 text-white flex items-center gap-1 transition-colors"
            @click="cancelEdit"
          >
            <X class="w-3 h-3" /> Cancel
          </button>
          <button
            type="button"
            class="px-2 py-1 text-xs rounded bg-white text-indigo-700 font-semibold flex items-center gap-1 hover:bg-gray-100 transition-colors shadow"
            @click="saveEdit"
          >
            <Check class="w-3 h-3" /> Save
          </button>
        </div>
      </div>

      <!-- Normal Content -->
      <template v-else>
        <!-- Soft deleted message -->
        <div v-if="message.is_deleted" class="flex items-center gap-1.5 italic opacity-75 text-xs py-1">
          <Ban class="w-3.5 h-3.5" />
          <span>This message was deleted</span>
        </div>

        <template v-else>
          <!-- Image Attachments -->
          <div v-if="images.length > 0" class="flex flex-col gap-2 mb-2">
            <ImagePreview
              v-for="img in images"
              :key="img.id"
              :attachment="img"
            />
          </div>

          <!-- Video Attachments (Play & View) -->
          <div v-if="videos.length > 0" class="flex flex-col gap-2 mb-2">
            <VideoPlayer
              v-for="vid in videos"
              :key="vid.id"
              :attachment="vid"
              :is-sent-by-me="isSentByMe"
            />
          </div>

          <!-- Audio Attachments (Play inline) -->
          <div v-if="audios.length > 0" class="flex flex-col gap-2 mb-2">
            <AudioPlayer
              v-for="aud in audios"
              :key="aud.id"
              :attachment="aud"
              :is-sent-by-me="isSentByMe"
            />
          </div>

          <!-- Document / File Attachments -->
          <div v-if="documents.length > 0" class="flex flex-col gap-2 mb-2">
            <FileAttachment
              v-for="doc in documents"
              :key="doc.id"
              :attachment="doc"
              :is-sent-by-me="isSentByMe"
            />
          </div>

          <!-- Message text body -->
          <p
            v-if="message.body"
            class="text-sm leading-relaxed whitespace-pre-wrap break-words select-text"
          >
            {{ message.body }}
          </p>
        </template>

        <!-- Timestamp and status -->
        <div
          :class="[
            'flex items-center justify-end gap-1 mt-1 text-[11px] select-none',
            isSentByMe ? 'text-indigo-200' : 'text-gray-400 dark:text-gray-400'
          ]"
        >
          <span v-if="message.edited_at && !message.is_deleted" class="text-[10px] italic mr-0.5">
            edited
          </span>
          <span>{{ formattedTime }}</span>
          <ReadReceipt v-if="isSentByMe && !message.is_deleted" :is-read="message.is_read" />
        </div>
      </template>
    </div>
  </div>
</template>
