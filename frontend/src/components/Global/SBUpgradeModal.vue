<template>
  <SBModal v-model="showModal" @close="handleClose" size="lg">
    <div class="upgrade-modal">
      <div class="header">
        <h2 class="title">Upgrade to Pro</h2>
        <p class="subtitle">Get unlimited video recordings</p>
      </div>

      <!-- Plan Selection -->
      <div class="plan-selection">
        <button
          class="plan-option"
          :class="{ active: selectedPlan === 'monthly' }"
          @click="selectedPlan = 'monthly'"
        >
          <div class="plan-name">Monthly</div>
          <div class="plan-price">$7<span>/month</span></div>
        </button>
        <button
          class="plan-option"
          :class="{ active: selectedPlan === 'yearly' }"
          @click="selectedPlan = 'yearly'"
        >
          <div class="plan-badge">Save 5%</div>
          <div class="plan-name">Yearly</div>
          <div class="plan-price">$80<span>/year</span></div>
          <div class="plan-subtext">~$6.67/month</div>
        </button>
      </div>

      <div class="features">
        <div class="feature-item">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Unlimited video recordings</span>
        </div>
        <div class="feature-item">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>HD video quality</span>
        </div>
        <div class="feature-item">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Unlimited storage</span>
        </div>
        <div class="feature-item">
          <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Priority support</span>
        </div>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Redirecting to checkout...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button @click="startCheckout" class="retry-button">Try Again</button>
      </div>

      <div v-else class="checkout-actions">
        <button @click="startCheckout" class="checkout-button">
          Continue to Payment
        </button>
      </div>
    </div>
  </SBModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SBModal from './SBModal.vue'
import { useAuth } from '@/stores/auth'

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'success', 'update:show'])

const auth = useAuth()
const loading = ref(false)
const error = ref(null)
const selectedPlan = ref('yearly') // Default to yearly (better value)

// Sync with v-model on SBModal
const showModal = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) {
      emit('close')
    }
    emit('update:show', value)
  }
})

// Start checkout with selected plan
async function startCheckout() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch('/api/subscription/checkout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        plan: selectedPlan.value,
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Failed to create checkout session')
    }

    const data = await response.json()

    // Redirect to Polar checkout URL
    if (data.checkout_url) {
      window.location.href = data.checkout_url
    } else {
      throw new Error('Checkout URL not received')
    }
  } catch (e) {
    console.error('Error starting checkout:', e)
    error.value = e.message || 'Failed to start checkout. Please try again.'
    loading.value = false
  }
}

function handleClose() {
  error.value = null
  emit('close')
}

// Reset error when modal opens
watch(() => props.show, (newShow) => {
  if (newShow) {
    error.value = null
  }
})
</script>

<style scoped>
.upgrade-modal {
  padding: 2rem;
}

.header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
}

.subtitle {
  font-size: 1.125rem;
  color: #666;
  margin: 0;
}

/* Plan Selection */
.plan-selection {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.plan-option {
  position: relative;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
}

.plan-option:hover {
  border-color: #3b82f6;
}

.plan-option.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.plan-badge {
  position: absolute;
  top: -10px;
  right: 12px;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  text-transform: uppercase;
}

.plan-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.plan-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
}

.plan-price span {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
}

.plan-subtext {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.features {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.375rem 0;
  color: #1a1a1a;
  font-size: 0.875rem;
}

.icon {
  width: 18px;
  height: 18px;
  color: #10b981;
  flex-shrink: 0;
}

.loading {
  text-align: center;
  padding: 2rem 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading p {
  color: #666;
  margin: 0;
}

.error {
  text-align: center;
  padding: 1.5rem 1rem;
}

.error p {
  color: #dc2626;
  margin: 0 0 1rem 0;
}

.retry-button {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.retry-button:hover {
  background: #2563eb;
}

.checkout-actions {
  margin-top: 0.5rem;
}

.checkout-button {
  width: 100%;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-size: 1.125rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.checkout-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}
</style>
