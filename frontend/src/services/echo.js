import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getApiBaseUrl } from '@/services/api'

window.Pusher = Pusher

let echoInstance = null
let activeDriver = null
let cachedConfig = null

// Try restoring cached configuration from localStorage
try {
  const stored = localStorage.getItem('connectly_broadcast_config')
  if (stored) {
    cachedConfig = JSON.parse(stored)
  }
} catch (_) {}

/**
 * Fetch latest broadcasting configuration dynamically from backend.
 * Allows switching between Reverb and Pusher anytime by changing BROADCAST_CONNECTION in backend .env.
 */
export async function initBroadcasting() {
  try {
    const apiBase = getApiBaseUrl()
    const res = await fetch(`${apiBase}/broadcasting/config`, {
      headers: { Accept: 'application/json' },
    })

    if (res.ok) {
      const data = await res.json()
      const prevDriver = activeDriver
      cachedConfig = data

      try {
        localStorage.setItem('connectly_broadcast_config', JSON.stringify(data))
      } catch (_) {}

      // If already connected with an older driver that changed, reconnect
      if (echoInstance && prevDriver && prevDriver !== data.driver) {
        disconnectEcho()
        return getEcho()
      }
      return data
    }
  } catch (err) {
    console.warn('[Echo] Could not fetch dynamic broadcasting config:', err)
  }
  return cachedConfig
}

/**
 * Return current active Echo instance (or instantiate dynamically based on current broadcaster mode).
 */
export function getEcho() {
  const token = localStorage.getItem('connectly_token')
  if (!token) {
    if (echoInstance) {
      disconnectEcho()
    }
    return null
  }

  if (echoInstance) {
    return echoInstance
  }

  const apiBase = getApiBaseUrl()
  const rootUrl = apiBase.replace(/\/api\/v1\/?$/, '')

  // Determine active driver: dynamic API > cached config > env variable > default 'pusher'
  const driver = (cachedConfig?.driver || import.meta.env.VITE_BROADCAST_CONNECTION || 'pusher').toLowerCase()

  if (driver === 'reverb') {
    const reverbKey = cachedConfig?.reverb?.key || import.meta.env.VITE_REVERB_APP_KEY
    const reverbHost = cachedConfig?.reverb?.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const reverbPort = Number(cachedConfig?.reverb?.port || import.meta.env.VITE_REVERB_PORT || 8080)
    const reverbScheme = cachedConfig?.reverb?.scheme || import.meta.env.VITE_REVERB_SCHEME || 'http'

    if (!reverbKey) {
      console.warn('[Echo] Reverb key is not configured.')
      return null
    }

    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: reverbKey,
      wsHost: reverbHost,
      wsPort: reverbPort,
      wssPort: reverbPort,
      forceTLS: reverbScheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${rootUrl}/api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      },
    })
    activeDriver = 'reverb'
  } else {
    // Default to Pusher driver
    const pusherKey = cachedConfig?.pusher?.key || import.meta.env.VITE_PUSHER_APP_KEY
    const pusherCluster = cachedConfig?.pusher?.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1'
    const pusherHost = cachedConfig?.pusher?.host || import.meta.env.VITE_PUSHER_HOST
    const pusherPort = cachedConfig?.pusher?.port || import.meta.env.VITE_PUSHER_PORT
    const pusherScheme = cachedConfig?.pusher?.scheme || import.meta.env.VITE_PUSHER_SCHEME || 'https'

    if (!pusherKey) {
      console.warn('[Echo] Pusher key is not configured.')
      return null
    }

    const echoConfig = {
      broadcaster: 'pusher',
      key: pusherKey,
      cluster: pusherCluster,
      forceTLS: pusherScheme === 'https',
      authEndpoint: `${rootUrl}/api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      },
    }

    if (pusherHost) {
      echoConfig.wsHost = pusherHost
      if (pusherPort) {
        echoConfig.wsPort = Number(pusherPort)
        echoConfig.wssPort = Number(pusherPort)
      }
      echoConfig.enabledTransports = ['ws', 'wss']
    }

    echoInstance = new Echo(echoConfig)
    activeDriver = 'pusher'
  }

  return echoInstance
}

/**
 * Disconnect and destroy current Echo instance.
 */
export function disconnectEcho() {
  if (echoInstance) {
    try {
      echoInstance.disconnect()
    } catch (_) {}
    echoInstance = null
    activeDriver = null
  }
}

/**
 * Get the currently active driver name ('reverb', 'pusher', or null)
 */
export function getActiveBroadcaster() {
  return activeDriver || cachedConfig?.driver || import.meta.env.VITE_BROADCAST_CONNECTION || null
}
