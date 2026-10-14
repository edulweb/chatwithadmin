import { createRouter, createWebHistory } from 'vue-router'
import ChatView from '@/views/ChatView.vue'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import ActivateAccountView from '@/views/ActivateAccountView.vue'
import ResetPasswordView from '@/views/ResetPasswordView.vue'
import VerifyEmailChangeView from '@/views/VerifyEmailChangeView.vue'

export function getAppBasePath() {
  // 1. Injected dynamic runtime config from Laravel
  if (typeof window !== 'undefined' && window.__APP_CONFIG__?.basePath) {
    return window.__APP_CONFIG__.basePath
  }

  // 2. HTML <base href="...">
  if (typeof document !== 'undefined') {
    const baseEl = document.querySelector('base')
    const href = baseEl?.getAttribute('href')
    if (href && href !== './') {
      return href.endsWith('/') ? href : `${href}/`
    }
  }

  // 3. Dynamic subfolder detection from current URL path:
  // e.g. /connectly/login -> subfolder is /connectly/
  // /my-app/activate-account -> subfolder is /my-app/
  // /login or / -> root '/'
  if (typeof window !== 'undefined' && window.location?.pathname) {
    const knownRoutes = ['login', 'register', 'activate-account', 'reset-password', 'verify-email-change']
    const segments = window.location.pathname.split('/').filter(Boolean)

    if (segments.length >= 2 && knownRoutes.includes(segments[segments.length - 1])) {
      return '/' + segments.slice(0, -1).join('/') + '/'
    }

    if (segments.length === 1 && !knownRoutes.includes(segments[0])) {
      return '/' + segments[0] + '/'
    }
  }

  return '/'
}

const router = createRouter({
  history: createWebHistory(getAppBasePath()),
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
  const token = localStorage.getItem('connectly_token')
  const rawUser = localStorage.getItem('connectly_user')
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
