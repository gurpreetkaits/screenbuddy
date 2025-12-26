<template>
  <div class="success-page">
    <div class="container">
      <div class="success-card">
          <div class="icon-container">
            <svg class="success-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <h1 class="title">Welcome to Pro!</h1>
          <p class="message">
            Your subscription is now active. You can now record unlimited videos.
          </p>

          <div class="features">
            <h3>What's included:</h3>
            <div class="feature-list">
              <div class="feature">
                <svg class="check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Unlimited video recordings</span>
              </div>
              <div class="feature">
                <svg class="check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>HD video quality</span>
              </div>
              <div class="feature">
                <svg class="check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Unlimited storage</span>
              </div>
              <div class="feature">
                <svg class="check-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Priority support</span>
              </div>
            </div>
          </div>

          <div class="countdown">
            <p>Redirecting to record page in {{ countdown }} seconds...</p>
          </div>

          <div class="actions">
            <button @click="goToRecord" class="primary-button">
              Start Recording Now
            </button>
            <button @click="goToSubscription" class="secondary-button">
              View Subscription
            </button>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/stores/auth'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const router = useRouter()
const route = useRoute()
const auth = useAuth()

const countdown = ref(5)
let countdownInterval = null

function goToRecord() {
  router.push('/record')
}

function goToSubscription() {
  router.push('/subscription')
}

async function handleCheckoutSuccess() {
  // Get checkout_id from URL query parameter
  const checkoutId = route.query.checkout_id

  if (!checkoutId) {
    console.warn('No checkout_id in URL, skipping checkout success call')
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
}

onMounted(async () => {
  // Process checkout success first (creates subscription)
  await handleCheckoutSuccess()

  // Then refresh subscription status
  await auth.fetchSubscription()

  // Start countdown
  countdownInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      goToRecord()
    }
  }, 1000)
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
.success-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.container {
  max-width: 600px;
  width: 100%;
}

.success-card {
  background: white;
  border-radius: 24px;
  padding: 3rem 2rem;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.icon-container {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 2rem;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.success-icon {
  width: 48px;
  height: 48px;
  color: white;
  stroke-width: 3;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1rem 0;
}

.message {
  font-size: 1.125rem;
  color: #666;
  margin: 0 0 2rem 0;
}

.features {
  background: #f9fafb;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  text-align: left;
}

.features h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 1rem 0;
}

.feature-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.feature {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #1a1a1a;
}

.check-icon {
  width: 20px;
  height: 20px;
  color: #10b981;
  flex-shrink: 0;
}

.countdown {
  margin-bottom: 2rem;
}

.countdown p {
  color: #666;
  font-size: 0.875rem;
  margin: 0;
}

.actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.primary-button,
.secondary-button {
  width: 100%;
  padding: 1rem;
  border-radius: 12px;
  font-size: 1.125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.primary-button {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
}

.primary-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

.secondary-button {
  background: #f3f4f6;
  color: #1a1a1a;
}

.secondary-button:hover {
  background: #e5e7eb;
}

@media (max-width: 640px) {
  .success-card {
    padding: 2rem 1.5rem;
  }

  .title {
    font-size: 2rem;
  }
}
</style>
