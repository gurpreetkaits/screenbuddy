<template>
  <SBModal
    v-model="isOpen"
    :title="title || 'Confirm Action'"
    size="md"
    @close="handleClose"
  >
    <div class="sm:flex sm:items-start">
      <!-- Dynamic Icon -->
      <div :class="iconContainerClasses">
        <component :is="iconComponent" :class="iconClasses" />
      </div>
      
      <!-- Content -->
      <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
        <h3 class="text-base font-semibold leading-6 text-gray-900">
          {{ title || 'Confirm Action' }}
        </h3>
        <div class="mt-2">
          <p class="text-sm text-gray-500">
            <slot>
              {{ message || 'Are you sure you want to proceed with this action?' }}
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
          :variant="confirmVariant"
          @click="handleConfirm"
          :loading="loading"
        >
          {{ confirmText || 'Confirm' }}
        </SBPrimaryButton>
      </div>
    </template>
  </SBModal>
</template>

<script>
import { computed, h } from 'vue'
import SBModal from './SBModal.vue'
import SBPrimaryButton from './SBPrimaryButton.vue'

export default {
  name: 'SBConfirmModal',
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
    type: {
      type: String,
      default: 'info',
      validator: (value) => ['info', 'warning', 'danger', 'success'].includes(value)
    },
    confirmText: {
      type: String,
      default: 'Confirm'
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

    const typeConfig = computed(() => {
      const configs = {
        info: {
          containerClass: 'bg-blue-100',
          iconClass: 'text-blue-600',
          variant: 'primary'
        },
        warning: {
          containerClass: 'bg-yellow-100',
          iconClass: 'text-yellow-600',
          variant: 'primary'
        },
        danger: {
          containerClass: 'bg-red-100',
          iconClass: 'text-red-600',
          variant: 'danger'
        },
        success: {
          containerClass: 'bg-green-100',
          iconClass: 'text-green-600',
          variant: 'success'
        }
      }
      return configs[props.type] || configs.info
    })

    const iconContainerClasses = computed(() => {
      return `mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10 ${typeConfig.value.containerClass}`
    })

    const iconClasses = computed(() => {
      return `h-6 w-6 ${typeConfig.value.iconClass}`
    })

    const confirmVariant = computed(() => {
      return typeConfig.value.variant
    })

    const iconComponent = computed(() => {
      const icons = {
        info: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'
          })
        ]),
        warning: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
          })
        ]),
        danger: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
          })
        ]),
        success: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          'stroke-width': '1.5',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'd': 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
          })
        ])
      }
      return icons[props.type] || icons.info
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
      iconContainerClasses,
      iconClasses,
      iconComponent,
      confirmVariant,
      handleConfirm,
      handleCancel,
      handleClose
    }
  }
}
</script>
