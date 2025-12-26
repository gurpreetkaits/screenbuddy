<template>
  <div class="subscription-badge" @click="handleClick" :class="{ clickable: clickable }">
    <div class="badge-container">
      <span class="badge" :class="badgeClass">
        {{ badgeText }}
      </span>
      <div v-if="subscription" class="quota">
        {{ quotaText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  subscription: {
    type: Object,
    default: null,
  },
  clickable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['click'])

const badgeText = computed(() => {
  if (!props.subscription) return 'Free'
  return props.subscription.is_active ? 'Pro' : 'Free'
})

const badgeClass = computed(() => {
  if (!props.subscription) return 'badge-free'
  return props.subscription.is_active ? 'badge-pro' : 'badge-free'
})

const quotaText = computed(() => {
  if (!props.subscription) return '0/1 videos'

  if (props.subscription.remaining_quota === null) {
    return 'Unlimited'
  }

  const used = props.subscription.videos_count || 0
  const total = used + (props.subscription.remaining_quota || 0)
  return `${used}/${total} videos`
})

function handleClick() {
  if (props.clickable) {
    emit('click')
  }
}
</script>

<style scoped>
.subscription-badge {
  display: inline-flex;
  align-items: center;
}

.subscription-badge.clickable {
  cursor: pointer;
}

.subscription-badge.clickable:hover .badge {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.badge-container {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  transition: all 0.2s;
}

.badge-free {
  background: #f3f4f6;
  color: #6b7280;
}

.badge-pro {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
}

.quota {
  font-size: 0.6875rem;
  color: #6b7280;
  font-weight: 500;
}
</style>
