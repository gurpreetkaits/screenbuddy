<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar - Hidden on mobile/tablet, shown on desktop (lg and up) -->
    <div class="hidden lg:flex flex-col w-64 bg-white shadow-lg">
      <!-- Logo -->
      <div class="flex items-center px-6 py-4 border-b border-gray-200">
        <img src="/logo.png" alt="ScreenSense" class="w-8 h-8 rounded-lg object-contain" />
        <h1 class="ml-3 text-xl font-semibold text-gray-900">ScreenSense</h1>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-6 space-y-2">
        <router-link
          to="/videos"
          class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200"
          :class="isActive('/videos') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
          My Videos
        </router-link>

        <router-link
          to="/profile"
          class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200"
          :class="isActive('/profile') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Profile
        </router-link>

        <router-link
          to="/subscription"
          class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200"
          :class="isActive('/subscription') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
          </svg>
          Subscription
        </router-link>
      </nav>

      <!-- User Info & Logout (when authenticated) -->
      <div v-if="isAuthenticated" class="px-4 py-4 border-t border-gray-200">
        <!-- Subscription Badge -->
        <div class="mb-3">
          <SBSubscriptionBadge
            :subscription="auth.subscription.value"
            @click="router.push('/subscription')"
          />
        </div>

        <div class="flex items-center mb-3">
          <img
            v-if="userInfo.avatar"
            :src="userInfo.avatar"
            :alt="userInfo.name"
            class="w-8 h-8 rounded-full object-cover"
          />
          <div v-else class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center">
            <span class="text-sm font-bold text-white">{{ userInfo.initial }}</span>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">{{ userInfo.name }}</p>
            <p class="text-xs text-gray-500">{{ userInfo.email }}</p>
          </div>
        </div>

        <button
          @click="showLogoutModal = true"
          class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200"
        >
          <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Logout
        </button>
      </div>

      <!-- Sign In Button (when not authenticated) -->
      <div v-else class="px-4 py-4 border-t border-gray-200">
        <button
          @click="handleLogin"
          class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
        >
          <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Sign in with Google
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Mobile Header (hidden on desktop) -->
      <header class="lg:hidden bg-white shadow-sm border-b border-gray-200">
        <div class="flex items-center justify-between px-4 py-3">
          <button
            @click="sidebarOpen = true"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-orange-500"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          
          <div class="flex items-center">
            <img src="/logo.png" alt="ScreenSense" class="w-8 h-8 rounded-lg object-contain" />
            <h1 class="ml-3 text-xl font-semibold text-gray-900">ScreenSense</h1>
          </div>

          <div class="w-10"></div> <!-- Spacer for balance -->
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <router-view />
      </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-50 lg:hidden"
      @click="sidebarOpen = false"
    >
      <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
      
      <div class="fixed inset-y-0 left-0 flex flex-col w-64 bg-white shadow-xl">
        <!-- Mobile Logo -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <div class="flex items-center">
            <img src="/logo.png" alt="ScreenSense" class="w-8 h-8 rounded-lg object-contain" />
            <h1 class="ml-3 text-xl font-semibold text-gray-900">ScreenSense</h1>
          </div>
          
          <button
            @click="sidebarOpen = false"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Mobile Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2">
          <router-link
            to="/videos"
            @click="sidebarOpen = false"
            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200"
            :class="isActive('/videos') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            My Videos
          </router-link>

          <router-link
            to="/profile"
            @click="sidebarOpen = false"
            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200"
            :class="isActive('/profile') ? 'bg-orange-100 text-orange-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profile
          </router-link>
        </nav>

        <!-- Mobile User Info & Logout (when authenticated) -->
        <div v-if="isAuthenticated" class="px-4 py-4 border-t border-gray-200">
          <div class="flex items-center mb-3">
            <img
              v-if="userInfo.avatar"
              :src="userInfo.avatar"
              :alt="userInfo.name"
              class="w-8 h-8 rounded-full object-cover"
            />
            <div v-else class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center">
              <span class="text-sm font-bold text-white">{{ userInfo.initial }}</span>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-gray-900">{{ userInfo.name }}</p>
              <p class="text-xs text-gray-500">{{ userInfo.email }}</p>
            </div>
          </div>

          <button
            @click="showLogoutModal = true"
            class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200"
          >
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
          </button>
        </div>

        <!-- Mobile Sign In Button (when not authenticated) -->
        <div v-else class="px-4 py-4 border-t border-gray-200">
          <button
            @click="handleLogin"
            class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
          </button>
        </div>
      </div>
    </div>

    <!-- Logout Modal -->
    <SBLogoutModal
      v-model="showLogoutModal"
      message="Are you sure you want to logout? Any unsaved work will be lost."
      :loading="logoutLoading"
      @confirm="handleLogout"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SBLogoutModal } from '../Global'
import SBSubscriptionBadge from '../Global/SBSubscriptionBadge.vue'
import { useAuth } from '@/stores/auth'

export default {
  name: 'AppLayout',
  components: {
    SBLogoutModal,
    SBSubscriptionBadge
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const auth = useAuth()
    const sidebarOpen = ref(false)
    const showLogoutModal = ref(false)
    const logoutLoading = ref(false)

    // Use auth store for user info
    const userInfo = computed(() => ({
      name: auth.user.value?.name || 'Guest',
      email: auth.user.value?.email || '',
      avatar: auth.user.value?.avatar || null,
      initial: (auth.user.value?.name || 'U').charAt(0).toUpperCase(),
    }))

    const isAuthenticated = computed(() => auth.isAuthenticated.value)

    const isActive = (path) => {
      return route.path === path
    }

    const handleLogin = () => {
      auth.loginWithGoogle()
    }

    const handleLogout = async () => {
      logoutLoading.value = true

      try {
        await auth.logout()
        // Redirect is handled in auth.logout()
      } catch (error) {
        console.error('Logout failed:', error)
        logoutLoading.value = false
      }
    }

    // Fetch subscription status on mount
    onMounted(() => {
      if (isAuthenticated.value) {
        auth.fetchSubscription()
      }
    })

    return {
      auth,
      router,
      sidebarOpen,
      showLogoutModal,
      logoutLoading,
      userInfo,
      isAuthenticated,
      isActive,
      handleLogin,
      handleLogout
    }
  }
}
</script>
