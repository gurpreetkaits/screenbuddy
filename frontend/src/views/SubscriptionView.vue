<template>
  <div class="bg-gray-50 min-h-full">
    <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
      <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Subscription</h1>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading subscription details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button @click="loadSubscription" class="retry-button">Retry</button>
      </div>

      <!-- No Subscription State -->
      <div v-else-if="!hasActiveSubscription" class="no-subscription">
        <div class="empty-state">
          <div class="empty-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
          </div>
          <h2>No Active Subscription</h2>
          <p>Upgrade to Pro to unlock unlimited video recordings and more features.</p>

          <div class="free-tier-info">
            <h3>Current Plan: Free</h3>
            <ul>
              <li>
                <svg class="check" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                1 video recording
              </li>
              <li class="disabled">
                <svg class="x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Unlimited recordings
              </li>
              <li class="disabled">
                <svg class="x" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Priority support
              </li>
            </ul>
            <p class="usage">Videos used: {{ subscription?.videos_count || 0 }}/1</p>
          </div>

          <button @click="showUpgradeModal = true" class="upgrade-button">
            Upgrade to Pro
          </button>
        </div>

        <!-- Subscription History (for users with past subscriptions) -->
        <div v-if="history.length > 0" class="history-section">
          <h3 class="history-title">Subscription History</h3>
          <div class="history-table-wrapper">
            <table class="history-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Status</th>
                  <th>Plan</th>
                  <th>Amount</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in history" :key="item.id">
                  <td>
                    <span class="event-label" :class="getEventClass(item.event_type)">
                      {{ item.event_label }}
                    </span>
                  </td>
                  <td>
                    <span class="status-pill" :class="getStatusClass(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                  <td>{{ item.plan_name || '-' }}</td>
                  <td>{{ item.formatted_amount || '-' }}</td>
                  <td>{{ formatHistoryDate(item.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Active Subscription List -->
      <div v-else class="subscription-list">
        <div class="subscription-card">
          <div class="subscription-header">
            <div class="plan-info">
              <span class="plan-badge">Pro</span>
              <h2>ScreenSense Pro</h2>
            </div>
            <span class="status-badge" :class="statusClass">{{ statusText }}</span>
          </div>

          <div class="subscription-details">
            <div class="detail-row">
              <span class="label">Status</span>
              <span class="value" :class="statusClass">{{ statusText }}</span>
            </div>
            <div class="detail-row" v-if="subscription?.started_at">
              <span class="label">Started</span>
              <span class="value">{{ formatDate(subscription.started_at) }}</span>
            </div>
            <div class="detail-row" v-if="subscription?.expires_at">
              <span class="label">{{ subscription?.is_in_grace_period ? 'Expires' : 'Next billing' }}</span>
              <span class="value">{{ formatDate(subscription.expires_at) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Videos recorded</span>
              <span class="value">{{ subscription?.videos_count || 0 }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Video limit</span>
              <span class="value unlimited">Unlimited</span>
            </div>
          </div>

          <div class="subscription-features">
            <h3>Included Features</h3>
            <ul>
              <li>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Unlimited video recordings
              </li>
              <li>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                HD video quality
              </li>
              <li>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Unlimited storage
              </li>
              <li>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Priority support
              </li>
            </ul>
          </div>

          <div class="subscription-actions">
            <button @click="openBillingPortal" class="portal-button" :disabled="loadingPortal">
              {{ loadingPortal ? 'Loading...' : 'Manage Billing' }}
            </button>
            <button
              v-if="!subscription?.is_in_grace_period"
              @click="cancelSubscription"
              class="cancel-button"
              :disabled="canceling"
            >
              {{ canceling ? 'Canceling...' : 'Cancel Subscription' }}
            </button>
          </div>
        </div>

        <!-- Subscription History -->
        <div v-if="history.length > 0" class="history-section history-section-active">
          <h3 class="history-title">Subscription History</h3>
          <div class="history-table-wrapper">
            <table class="history-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Status</th>
                  <th>Plan</th>
                  <th>Amount</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in history" :key="item.id">
                  <td>
                    <span class="event-label" :class="getEventClass(item.event_type)">
                      {{ item.event_label }}
                    </span>
                  </td>
                  <td>
                    <span class="status-pill" :class="getStatusClass(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                  <td>{{ item.plan_name || '-' }}</td>
                  <td>{{ item.formatted_amount || '-' }}</td>
                  <td>{{ formatHistoryDate(item.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Upgrade Modal -->
    <SBUpgradeModal
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      @success="handleUpgradeSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import SBUpgradeModal from '@/components/Global/SBUpgradeModal.vue'
import { useAuth } from '@/stores/auth'
import toast from '@/services/toastService'

const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || ''

const router = useRouter()
const auth = useAuth()

const subscription = ref(null)
const history = ref([])
const loading = ref(false)
const error = ref(null)
const showUpgradeModal = ref(false)
const canceling = ref(false)
const loadingPortal = ref(false)

const hasActiveSubscription = computed(() => {
  return subscription.value?.is_active === true
})

const statusText = computed(() => {
  if (!subscription.value) return 'Free'
  if (subscription.value.is_in_grace_period) return 'Canceled'
  if (subscription.value.is_active) return 'Active'
  return 'Inactive'
})

const statusClass = computed(() => {
  if (!subscription.value) return 'status-free'
  if (subscription.value.is_in_grace_period) return 'status-grace'
  if (subscription.value.is_active) return 'status-active'
  return 'status-inactive'
})

async function loadSubscription() {
  loading.value = true
  error.value = null

  try {
    const sub = await auth.fetchSubscription()
    subscription.value = sub
    // Also fetch history
    await fetchHistory()
  } catch (e) {
    console.error('Error loading subscription:', e)
    error.value = 'Failed to load subscription details'
  } finally {
    loading.value = false
  }
}

async function fetchHistory() {
  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/history`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })

    if (response.ok) {
      const data = await response.json()
      history.value = data.history || []
    }
  } catch (e) {
    console.error('Error loading subscription history:', e)
    // Don't show error for history, it's optional
  }
}

function getEventClass(eventType) {
  switch (eventType) {
    case 'created':
    case 'activated':
      return 'event-success'
    case 'renewed':
      return 'event-info'
    case 'canceled':
      return 'event-warning'
    case 'revoked':
      return 'event-error'
    default:
      return 'event-default'
  }
}

function getStatusClass(status) {
  switch (status) {
    case 'active':
      return 'status-active'
    case 'canceled':
      return 'status-canceled'
    case 'expired':
      return 'status-expired'
    default:
      return 'status-default'
  }
}

function formatHistoryDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function cancelSubscription() {
  if (!confirm('Are you sure you want to cancel your subscription? You will keep access until the end of your billing period.')) {
    return
  }

  canceling.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Failed to cancel subscription')
    }

    // Reload subscription data
    await loadSubscription()
    toast.success('Subscription canceled successfully. You will keep access until ' + formatDate(subscription.value.expires_at))
  } catch (e) {
    console.error('Error canceling subscription:', e)
    toast.error('Failed to cancel subscription: ' + e.message)
  } finally {
    canceling.value = false
  }
}

async function openBillingPortal() {
  loadingPortal.value = true

  try {
    const response = await fetch(`${API_BASE_URL}/api/subscription/portal`, {
      headers: {
        'Authorization': `Bearer ${auth.token.value}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to get billing portal URL')
    }

    const data = await response.json()
    if (data.portal_url) {
      window.location.href = data.portal_url
    }
  } catch (e) {
    console.error('Error opening billing portal:', e)
    toast.error('Failed to open billing portal')
  } finally {
    loadingPortal.value = false
  }
}

function handleUpgradeSuccess() {
  showUpgradeModal.value = false
  loadSubscription()
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  loadSubscription()
})
</script>

<style scoped>
/* Loading & Error States */
.loading-state,
.error-state {
  text-align: center;
  padding: 3rem 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

@media (min-width: 640px) {
  .loading-state,
  .error-state {
    padding: 4rem 1.5rem;
  }
}

@media (min-width: 1024px) {
  .loading-state,
  .error-state {
    padding: 4rem 2rem;
  }
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

.retry-button {
  margin-top: 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
}

/* No Subscription State */
.no-subscription {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.empty-state {
  padding: 2rem 1rem;
  text-align: center;
}

@media (min-width: 640px) {
  .empty-state {
    padding: 3rem 1.5rem;
  }
}

@media (min-width: 1024px) {
  .empty-state {
    padding: 3rem 2rem;
  }
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: #f3f4f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.empty-icon svg {
  width: 32px;
  height: 32px;
  color: #9ca3af;
}

.empty-state h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 0.5rem;
}

.empty-state > p {
  color: #6b7280;
  margin: 0 0 1.5rem;
}

@media (min-width: 640px) {
  .empty-state > p {
    margin: 0 0 2rem;
  }
}

.free-tier-info {
  background: #f9fafb;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

@media (min-width: 640px) {
  .free-tier-info {
    margin-bottom: 2rem;
  }
}

.free-tier-info h3 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 1rem;
}

.free-tier-info ul {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem;
}

.free-tier-info li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  color: #1a1a1a;
  font-size: 0.9375rem;
}

.free-tier-info li.disabled {
  color: #9ca3af;
}

.free-tier-info li svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.free-tier-info li svg.check {
  color: #10b981;
}

.free-tier-info li svg.x {
  color: #d1d5db;
}

.free-tier-info .usage {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.upgrade-button {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-size: 1.125rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.upgrade-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Subscription List */
.subscription-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .subscription-list {
    gap: 2rem;
  }
}

.subscription-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 2px solid #3b82f6;
  overflow: hidden;
}

.subscription-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
}

.plan-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.plan-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.plan-info h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.status-badge {
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.2);
}

.status-badge.status-active {
  background: rgba(16, 185, 129, 0.2);
}

.status-badge.status-grace {
  background: rgba(245, 158, 11, 0.2);
}

.subscription-details {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.625rem 0;
}

.detail-row:not(:last-child) {
  border-bottom: 1px solid #f3f4f6;
}

.detail-row .label {
  color: #6b7280;
  font-size: 0.875rem;
}

.detail-row .value {
  font-weight: 600;
  color: #1a1a1a;
}

.detail-row .value.status-active {
  color: #10b981;
}

.detail-row .value.status-grace {
  color: #f59e0b;
}

.detail-row .value.unlimited {
  color: #8b5cf6;
}

.subscription-features {
  padding: 1.5rem;
  background: #f9fafb;
}

.subscription-features h3 {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 1rem;
}

.subscription-features ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.subscription-features li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #1a1a1a;
}

.subscription-features li svg {
  width: 16px;
  height: 16px;
  color: #10b981;
  flex-shrink: 0;
}

.subscription-actions {
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.portal-button,
.cancel-button {
  flex: 1;
  min-width: 150px;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.portal-button {
  background: #1a1a1a;
  color: white;
}

.portal-button:hover:not(:disabled) {
  background: #333;
}

.portal-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cancel-button {
  background: #fee2e2;
  color: #dc2626;
}

.cancel-button:hover:not(:disabled) {
  background: #fecaca;
}

.cancel-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .subscription-features ul {
    grid-template-columns: 1fr;
  }

  .subscription-actions {
    flex-direction: column;
  }

  .portal-button,
  .cancel-button {
    min-width: 100%;
  }
}

/* Subscription History Styles */
.history-section {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e5e7eb;
}

.history-section-active {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 1.5rem;
  border-top: none;
}

@media (min-width: 640px) {
  .history-section-active {
    margin-top: 2rem;
  }
}

.history-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 1rem;
}

.history-table-wrapper {
  overflow-x: auto;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.history-table th,
.history-table td {
  padding: 0.75rem 0.5rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.history-table th {
  font-weight: 600;
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.history-table td {
  color: #1a1a1a;
}

.history-table tbody tr:hover {
  background: #f9fafb;
}

/* Event label styles */
.event-label {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.event-label.event-success {
  background: #d1fae5;
  color: #065f46;
}

.event-label.event-info {
  background: #dbeafe;
  color: #1e40af;
}

.event-label.event-warning {
  background: #fef3c7;
  color: #92400e;
}

.event-label.event-error {
  background: #fee2e2;
  color: #991b1b;
}

.event-label.event-default {
  background: #f3f4f6;
  color: #4b5563;
}

/* Status pill styles */
.status-pill {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status-pill.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-pill.status-canceled {
  background: #fef3c7;
  color: #92400e;
}

.status-pill.status-expired {
  background: #fee2e2;
  color: #991b1b;
}

.status-pill.status-default {
  background: #f3f4f6;
  color: #4b5563;
}

@media (max-width: 640px) {
  .history-section {
    padding: 1rem;
  }

  .history-table {
    font-size: 0.75rem;
  }

  .history-table th,
  .history-table td {
    padding: 0.5rem 0.25rem;
  }
}
</style>
