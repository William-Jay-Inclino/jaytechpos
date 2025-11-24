<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { computed } from 'vue'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

function formatWithCommas(value?: string | number | null): string {
  if (value === undefined || value === null) {
    return ''
  }

  // Keep as string for processing
  let str = String(value)

  // Allow leading minus
  const isNegative = str.startsWith('-')
  if (isNegative) str = str.slice(1)

  // Remove everything except digits and dot
  str = str.replace(/[^0-9.]/g, '')

  // If multiple dots, keep only first
  const parts = str.split('.')
  const intPart = parts.shift() || ''
  const decPart = parts.length ? parts.join('') : ''

  // Add commas to integer part
  const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',')

  const result = decPart ? `${withCommas}.${decPart}` : withCommas
  return isNegative ? `-${result}` : result
}

function parseToNumberString(input: string): string | number {
  // Remove commas and spaces
  const cleaned = String(input).replace(/,/g, '').trim()
  if (cleaned === '' || cleaned === '-' || cleaned === '.' || cleaned === '-.') return ''

  // If it's a valid number string, coerce to number type where appropriate
  if (/^-?\d+$/.test(cleaned)) {
    try {
      return parseInt(cleaned, 10)
    } catch {
      return cleaned
    }
  }

  if (/^-?\d*\.\d+$/.test(cleaned)) {
    try {
      return parseFloat(cleaned)
    } catch {
      return cleaned
    }
  }

  // fallback - return as cleaned string
  return cleaned
}

const displayValue = computed<string>({
  get() {
    return formatWithCommas(modelValue.value)
  },
  set(val: string) {
    const parsed = parseToNumberString(val)
    emits('update:modelValue', parsed)
  },
})
</script>

<template>
  <input
    v-model="displayValue"
    inputmode="decimal"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      props.class,
    )"
  />
</template>
