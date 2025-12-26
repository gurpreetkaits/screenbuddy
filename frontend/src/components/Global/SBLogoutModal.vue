<template>
  <SBModal
    v-model="isOpen"
    title="Logout"
    size="md"
    @close="handleClose"
  >
    <div class="sm:flex sm:items-start">
      <!-- Logout Icon -->
      <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
        <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
        </svg>
      </div>
      
      <!-- Content -->
      <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
        <h3 class="text-base font-semibold leading-6 text-gray-900">
          Logout
        </h3>
        <div class="mt-2">
          <p class="text-sm text-gray-500">
            <slot>
              {{ message || 'Are you sure you want to logout? Any unsaved work will be lost.' }}
            </slot>
          </p>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0">
        <SBPrimaryButton
          variant="secondary"
          @click="handleCancel"
          :disabled="loading"
        >
          {{ cancelText || 'Cancel' }}
        </SBPrimaryButton>
        
        <SBPrimaryButton
          variant="primary"
          @click="handleConfirm"
          :loading="loading"
        >
          {{ confirmText || 'Logout' }}
        </SBPrimaryButton>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed } from 'vue'
import SBModal from './SBModal.vue'
import SBPrimaryButton from './SBPrimaryButton.vue'

export default {
  name: 'SBLogoutModal',
  components: {
    SBModal,
    SBPrimaryButton
  },
  emits: ['update:modelValue', 'confirm', 'cancel', 'close'],
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    message: {
      type: String,
      default: ''
    },
    confirmText: {
      type: String,
      default: 'Logout'
    },
    cancelText: {
      type: String,
      default: 'Cancel'
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    const isOpen = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value)
    })

    const handleConfirm = () => {
      emit('confirm')
    }

    const handleCancel = () => {
      emit('cancel')
      isOpen.value = false
    }

    const handleClose = () => {
      emit('close')
      isOpen.value = false
    }

    return {
      isOpen,
      handleConfirm,
      handleCancel,
      handleClose
    }
  }
}
</script>
