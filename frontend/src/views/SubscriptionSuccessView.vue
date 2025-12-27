<template>
  <div class="loading-container">
    <div class="spinner"></div>
    <p>Activating your subscription...</p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const router = useRouter()
const route = useRoute()
const auth = useAuth()

async function handleCheckoutSuccess() {
  // Get checkout_id from URL query parameter
  const checkoutId = route.query.checkout_id

  if (!checkoutId) {
    console.warn('No checkout_id in URL, skipping checkout success call')
    // Redirect immediately if no checkout ID
    router.push('/subscription?success=true')
    return
  }

  try {
    // Call checkout success endpoint to create subscription immediately
    const response = await fetch(`${API_BASE_URL}/api/subscription/checkout/success`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        checkout_id: checkoutId,
      }),
    })

    if (response.ok) {
      const data = await response.json()
      console.log('Subscription created successfully:', data)
    } else {
      console.error('Failed to process checkout success')
    }
  } catch (error) {
    console.error('Error calling checkout success endpoint:', error)
  }

  // Refresh subscription status
  await auth.fetchSubscription()

  // Redirect to subscription page with success flag
  router.push('/subscription?success=true')
}

onMounted(() => {
  handleCheckoutSuccess()
})
</script>

<style scoped>
.loading-container {
  min-height: 100vh;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-container p {
  font-size: 1.125rem;
  color: #6b7280;
  margin: 0;
}
</style>
