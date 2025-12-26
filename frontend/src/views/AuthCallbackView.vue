<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <!-- Loading State -->
      <div v-if="!error" class="space-y-4">
        <div class="w-12 h-12 mx-auto border-4 border-orange-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-600 text-lg">Completing sign in...</p>
      </div>

      <!-- Error State -->
      <div v-else class="space-y-4">
        <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <p class="text-gray-900 text-lg font-medium">Authentication Failed</p>
        <p class="text-gray-500">{{ errorMessage }}</p>
        <button
          @click="goToHome"
          class="mt-4 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
        >
          Go to Home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/stores/auth'

const router = useRouter()
const auth = useAuth()

const error = ref(false)
const errorMessage = ref('')

function goToHome() {
  router.push('/videos')
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const token = params.get('token')
  const userJson = params.get('user')
  const errorParam = params.get('error')

  if (errorParam) {
    error.value = true
    errorMessage.value = errorParam === 'authentication_failed'
      ? 'Unable to complete Google sign in. Please try again.'
      : 'An unexpected error occurred.'
    return
  }

  if (token && userJson) {
    try {
      const user = JSON.parse(decodeURIComponent(userJson))
      auth.setAuth(token, user)

      // Check for stored redirect URL
      const redirect = localStorage.getItem('auth_redirect')
      localStorage.removeItem('auth_redirect')

      router.push(redirect || '/videos')
    } catch (e) {
      console.error('Failed to parse user data:', e)
      error.value = true
      errorMessage.value = 'Failed to process authentication data.'
    }
  } else {
    error.value = true
    errorMessage.value = 'Missing authentication data.'
  }
})
</script>
