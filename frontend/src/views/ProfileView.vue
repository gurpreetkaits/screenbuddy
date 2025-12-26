<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
    <!-- Page Header -->
    <div class="mb-6 sm:mb-8">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Profile Settings</h1>
      <p class="mt-1 text-xs sm:text-sm text-gray-600">View your account information</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600"></div>
    </div>

    <!-- Profile Content -->
    <div v-else>
      <!-- Profile Card -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
          <h2 class="text-base sm:text-lg font-medium text-gray-900">Personal Information</h2>
        </div>

        <div class="px-4 sm:px-6 py-6">
          <!-- Profile Picture & Name -->
          <div class="flex flex-col sm:flex-row items-center gap-6 mb-8">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img
                v-if="user?.avatar"
                :src="user.avatar"
                :alt="user.name"
                class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover ring-4 ring-orange-100"
              />
              <div v-else class="w-24 h-24 sm:w-28 sm:h-28 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center ring-4 ring-orange-100">
                <span class="text-3xl sm:text-4xl font-bold text-white">{{ userInitial }}</span>
              </div>
            </div>

            <!-- Name & Email -->
            <div class="text-center sm:text-left">
              <h3 class="text-xl sm:text-2xl font-bold text-gray-900">{{ user?.name || 'User' }}</h3>
              <p class="text-gray-500 mt-1">{{ user?.email }}</p>
              <div class="flex items-center justify-center sm:justify-start gap-2 mt-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  Verified
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  <svg class="w-3 h-3 mr-1" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                  </svg>
                  Google Account
                </span>
              </div>
            </div>
          </div>

          <!-- Account Details -->
          <div class="border-t border-gray-200 pt-6">
            <dl class="divide-y divide-gray-200">
              <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Full name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ user?.name || '-' }}</dd>
              </div>
              <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Email address</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ user?.email || '-' }}</dd>
              </div>
              <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Member since</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ memberSince }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Account Actions -->
      <div class="bg-white shadow rounded-lg mt-6 sm:mt-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
          <h2 class="text-base sm:text-lg font-medium text-gray-900">Account Actions</h2>
        </div>

        <div class="px-4 sm:px-6 py-4 space-y-4">
          <!-- Export Data -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-gray-900">Export Data</h3>
              <p class="text-xs sm:text-sm text-gray-500">Download all your recordings and data</p>
            </div>
            <button
              @click="exportData"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 justify-center"
            >
              Export Data
            </button>
          </div>

          <!-- Delete Account -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-gray-200">
            <div class="flex-1">
              <h3 class="text-sm font-medium text-red-900">Delete Account</h3>
              <p class="text-xs sm:text-sm text-red-600">Permanently delete your account and all data</p>
            </div>
            <button
              @click="deleteAccount"
              class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 justify-center"
            >
              Delete Account
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Account Modal -->
    <SBDeleteModal
      v-model="showDeleteAccountModal"
      title="Delete Account"
      message="Are you sure you want to delete your account? This action cannot be undone. All your videos and data will be permanently deleted."
      confirm-text="Delete Account"
      :loading="isDeletingAccount"
      @confirm="confirmDeleteAccount"
      @cancel="showDeleteAccountModal = false"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'
import SBDeleteModal from '@/components/Global/SBDeleteModal.vue'

export default {
  name: 'ProfileView',
  components: {
    SBDeleteModal
  },
  setup() {
    const auth = useAuth()
    const loading = ref(true)

    // Get user from auth store
    const user = computed(() => auth.user.value)

    // Get user initial for fallback avatar
    const userInitial = computed(() => {
      const name = user.value?.name || ''
      return name.charAt(0).toUpperCase() || 'U'
    })

    // Format member since date
    const memberSince = computed(() => {
      if (!user.value?.created_at) return '-'
      try {
        const date = new Date(user.value.created_at)
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      } catch {
        return '-'
      }
    })

    // Delete account modal state
    const showDeleteAccountModal = ref(false)
    const isDeletingAccount = ref(false)

    onMounted(async () => {
      // Fetch fresh user data
      try {
        await auth.fetchUser()
      } catch (error) {
        console.error('Failed to fetch user:', error)
      } finally {
        loading.value = false
      }
    })

    const exportData = () => {
      toast.info('Data export will be available for download shortly')
    }

    const deleteAccount = () => {
      showDeleteAccountModal.value = true
    }

    const confirmDeleteAccount = () => {
      isDeletingAccount.value = true
      // Simulate API call
      setTimeout(() => {
        toast.warning('Account deletion functionality coming soon')
        showDeleteAccountModal.value = false
        isDeletingAccount.value = false
      }, 1000)
    }

    return {
      user,
      userInitial,
      memberSince,
      loading,
      exportData,
      deleteAccount,
      confirmDeleteAccount,
      showDeleteAccountModal,
      isDeletingAccount
    }
  }
}
</script>
