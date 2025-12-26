<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
    v-bind="$attrs"
  >
    <!-- Loading Spinner -->
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      ></circle>
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      ></path>
    </svg>

    <!-- Icon (left) -->
    <span v-if="$slots.iconLeft && !loading" class="mr-2">
      <slot name="iconLeft" />
    </span>

    <!-- Button Text -->
    <span>
      <slot />
    </span>

    <!-- Icon (right) -->
    <span v-if="$slots.iconRight && !loading" class="ml-2">
      <slot name="iconRight" />
    </span>
  </button>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'SBPrimaryButton',
  inheritAttrs: false,
  emits: ['click'],
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'danger', 'success', 'ghost'].includes(value)
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    type: {
      type: String,
      default: 'button',
      validator: (value) => ['button', 'submit', 'reset'].includes(value)
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    fullWidth: {
      type: Boolean,
      default: false
    },
    rounded: {
      type: String,
      default: 'md',
      validator: (value) => ['none', 'sm', 'md', 'lg', 'full'].includes(value)
    }
  },
  setup(props) {
    const baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed'

    const variantClasses = computed(() => {
      const variants = {
        primary: 'text-white bg-orange-600 hover:bg-orange-700 focus:ring-orange-500 shadow-sm',
        secondary: 'text-orange-700 bg-orange-50 hover:bg-orange-100 focus:ring-orange-500 border border-orange-200',
        danger: 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500 shadow-sm',
        success: 'text-white bg-green-600 hover:bg-green-700 focus:ring-green-500 shadow-sm',
        ghost: 'text-orange-600 hover:text-orange-700 hover:bg-orange-50 focus:ring-orange-500'
      }
      return variants[props.variant] || variants.primary
    })

    const sizeClasses = computed(() => {
      const sizes = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-4 py-2 text-base',
        xl: 'px-6 py-3 text-base'
      }
      return sizes[props.size] || sizes.md
    })

    const roundedClasses = computed(() => {
      const rounded = {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        full: 'rounded-full'
      }
      return rounded[props.rounded] || rounded.md
    })

    const widthClasses = computed(() => {
      return props.fullWidth ? 'w-full' : ''
    })

    const buttonClasses = computed(() => {
      return [
        baseClasses,
        variantClasses.value,
        sizeClasses.value,
        roundedClasses.value,
        widthClasses.value
      ].join(' ')
    })

    return {
      buttonClasses
    }
  }
}
</script>
