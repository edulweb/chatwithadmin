import { createRouter, createWebHistory } from 'vue-router'
import ChatView from '@/views/ChatView.vue'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import ActivateAccountView from '@/views/ActivateAccountView.vue'
import ResetPasswordView from '@/views/ResetPasswordView.vue'
import VerifyEmailChangeView from '@/views/VerifyEmailChangeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'chat',
      component: ChatView,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guestOnly: true },
    },
    {
      path: '/activate-account',
      name: 'activate-account',
      component: ActivateAccountView,
      meta: { guestOnly: false },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { guestOnly: false },
    },
    {
      path: '/verify-email-change',
      name: 'verify-email-change',
      component: VerifyEmailChangeView,
      meta: { guestOnly: false },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('chatbox_token')
  const rawUser = localStorage.getItem('chatbox_user')
  const user = rawUser ? JSON.parse(rawUser) : null

  if (to.name === 'register') {
    if (!token || !user?.is_admin) {
      return next({ name: 'login' })
    }
    return next()
  }

  if (to.meta.requiresAuth && !token) {
    next({ name: 'login' })
  } else if (to.meta.guestOnly && token) {
    next({ name: 'chat' })
  } else {
    next()
  }
})

export default router
