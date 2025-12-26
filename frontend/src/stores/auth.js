import { ref, computed, reactive } from 'vue'

// Get backend URL for API calls
const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

// Reactive state (singleton)
const state = reactive({
  user: null,
  token: null,
  loading: false,
  subscription: null, // Subscription data from API
})

// Initialize from localStorage
function initFromStorage() {
  const savedToken = localStorage.getItem('auth_token')
  const savedUser = localStorage.getItem('auth_user')

  if (savedToken && savedUser) {
    try {
      state.token = savedToken
      state.user = JSON.parse(savedUser)
    } catch (e) {
      console.error('Failed to parse auth data from storage:', e)
      clearAuth()
    }
  }
}

// Clear authentication
function clearAuth() {
  state.token = null
  state.user = null
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')

  // Notify extension to clear auth storage
  try {
    window.postMessage({ type: 'SCREENSENSE_AUTH_LOGOUT' }, '*')
  } catch (e) {
    console.log('Could not notify extension of logout')
  }
}

// Set authentication
function setAuth(token, user) {
  state.token = token
  state.user = user
  localStorage.setItem('auth_token', token)
  localStorage.setItem('auth_user', JSON.stringify(user))

  // Sync to extension storage if available
  if (window.chrome?.runtime?.sendMessage) {
    try {
      window.postMessage({ type: 'SCREENSENSE_AUTH_UPDATE', token, user }, '*')
    } catch (e) {
      console.log('Could not sync auth to extension')
    }
  }
}

// Logout function
async function logout() {
  const token = state.token

  if (token) {
    try {
      await fetch(`${API_BASE_URL}/api/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })
    } catch (e) {
      console.error('Logout API call failed:', e)
    }
  }

  clearAuth()

  // Redirect to login page
  window.location.href = '/login'
}

// Fetch current user from API
async function fetchUser() {
  const token = state.token
  if (!token) return null

  state.loading = true
  try {
    const response = await fetch(`${API_BASE_URL}/api/auth/me`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })

    if (response.ok) {
      const data = await response.json()
      state.user = data.user
      localStorage.setItem('auth_user', JSON.stringify(data.user))
      return data.user
    } else {
      // Token invalid, clear auth
      clearAuth()
      return null
    }
  } catch (e) {
    console.error('Failed to fetch user:', e)
    return null
  } finally {
    state.loading = false
  }
}

// Fetch subscription status from API
async function fetchSubscription() {
  const token = state.token
  if (!token) {
    state.subscription = null
    return null
  }

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/status`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })

    if (response.ok) {
      const data = await response.json()
      state.subscription = data.subscription
      return data.subscription
    } else if (response.status === 401) {
      // Token invalid, clear auth
      clearAuth()
      return null
    } else {
      console.error('Failed to fetch subscription:', response.statusText)
      return null
    }
  } catch (e) {
    console.error('Error fetching subscription:', e)
    return null
  }
}

// Composable hook
export function useAuth() {
  const isAuthenticated = computed(() => !!state.token)
  const user = computed(() => state.user)
  const token = computed(() => state.token)
  const loading = computed(() => state.loading)
  const subscription = computed(() => state.subscription)

  // Computed properties for subscription
  const canRecordVideo = computed(() => {
    if (!state.subscription) return false
    return state.subscription.can_record
  })

  const getRemainingQuota = computed(() => {
    if (!state.subscription) return 0
    return state.subscription.remaining_quota
  })

  const hasActiveSubscription = computed(() => {
    if (!state.subscription) return false
    return state.subscription.is_active
  })

  return {
    // State
    user,
    token,
    isAuthenticated,
    loading,
    subscription,

    // Subscription computed
    canRecordVideo,
    getRemainingQuota,
    hasActiveSubscription,

    // Actions
    setAuth,
    logout,
    clearAuth,
    fetchUser,
    fetchSubscription,
    initFromStorage,

    // Google OAuth
    loginWithGoogle() {
      const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
      window.location.href = backendUrl + '/api/auth/google'
    },
  }
}

// Initialize on module load
initFromStorage()
