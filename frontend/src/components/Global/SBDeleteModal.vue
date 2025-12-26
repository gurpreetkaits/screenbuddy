<template>
  <SBModal
    v-model="isOpen"
    :title="title || 'Delete Item'"
    size="md"
    @close="handleClose"
  >
    <div class="sm:flex sm:items-start">
      <!-- Warning Icon -->
      <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      
      <!-- Content -->
      <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
        <h3 class="text-base font-semibold leading-6 text-gray-900">
          {{ title || 'Delete Item' }}
        </h3>
        <div class="mt-2">
          <p class="text-sm text-gray-500">
            <slot>
              {{ message || 'Are you sure you want to delete this item? This action cannot be undone.' }}
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
          variant="danger"
          @click="handleConfirm"
          :loading="loading"
        >
          {{ confirmText || 'Delete' }}
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
  name: 'SBDeleteModal',
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
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    confirmText: {
      type: String,
      default: 'Delete'
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
