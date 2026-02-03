<template>
  <span class="inline-flex items-center" :class="containerClass">
    <SaudiRiyalIcon
      v-if="showSymbol && currency === 'SAR'"
      :size="iconSize"
      :color="iconColor"
      :class="iconClass"
    />
    <span v-else-if="showSymbol" :class="symbolClass">{{ getSymbol() }}</span>
    <span :class="amountClass">{{ formattedAmount }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import SaudiRiyalIcon from './SaudiRiyalIcon.vue'

interface Props {
  amount: number | string
  currency?: string
  showSymbol?: boolean
  compact?: boolean
  iconSize?: string
  iconColor?: string
  containerClass?: string
  iconClass?: string
  symbolClass?: string
  amountClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'SAR',
  showSymbol: true,
  compact: false,
  iconSize: '0.9em',
  iconColor: 'currentColor',
  containerClass: '',
  iconClass: 'mr-1',
  symbolClass: 'mr-1',
  amountClass: ''
})

const numericAmount = computed(() => {
  const num = typeof props.amount === 'string' ? parseFloat(props.amount) : props.amount
  return isNaN(num) ? 0 : num
})

const formattedAmount = computed(() => {
  const num = numericAmount.value

  if (props.compact) {
    if (num >= 1000000) {
      return `${(num / 1000000).toFixed(1)}M`
    } else if (num >= 1000) {
      return `${(num / 1000).toFixed(1)}K`
    }
  }

  return num.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
})

const getSymbol = (): string => {
  const symbols: Record<string, string> = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'AED': 'د.إ',
    'SAR': 'SR',
    'EGP': 'ج.م',
    'JOD': 'د.أ',
    'KWD': 'د.ك',
    'QAR': 'ر.ق',
  }
  return symbols[props.currency] || props.currency
}
</script>