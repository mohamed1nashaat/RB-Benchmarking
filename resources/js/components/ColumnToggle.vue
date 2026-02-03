<template>
  <div class="relative inline-block text-left">
    <button
      @click="isOpen = !isOpen"
      type="button"
      class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
    >
      <ViewColumnsIcon class="h-4 w-4 mr-2" />
      Columns
      <ChevronDownIcon class="h-4 w-4 ml-1" />
    </button>

    <!-- Dropdown panel -->
    <div
      v-if="isOpen"
      class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
    >
      <div class="py-1 max-h-80 overflow-y-auto" role="menu">
        <!-- Header with actions -->
        <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
          <span class="text-xs font-medium text-gray-500 uppercase">Toggle Columns</span>
          <div class="flex space-x-2">
            <button
              @click="showAll"
              class="text-xs text-primary-600 hover:text-primary-800"
            >
              Show All
            </button>
            <button
              @click="resetToDefault"
              class="text-xs text-gray-500 hover:text-gray-700"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Column checkboxes -->
        <div class="py-1">
          <label
            v-for="column in columns"
            :key="column.key"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
          >
            <input
              type="checkbox"
              :checked="visibleColumns[column.key]"
              @change="toggleColumn(column.key)"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            >
            <span class="ml-3">{{ column.label }}</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Click outside to close -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40"
      @click="isOpen = false"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { ViewColumnsIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

interface Column {
  key: string
  label: string
  defaultVisible?: boolean
}

const props = defineProps<{
  columns: Column[]
  storageKey: string
  modelValue: Record<string, boolean>
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, boolean>): void
}>()

const isOpen = ref(false)

// Use the modelValue from parent
const visibleColumns = ref<Record<string, boolean>>({ ...props.modelValue })

// Toggle a single column
const toggleColumn = (key: string) => {
  visibleColumns.value[key] = !visibleColumns.value[key]
  saveAndEmit()
}

// Show all columns
const showAll = () => {
  props.columns.forEach(col => {
    visibleColumns.value[col.key] = true
  })
  saveAndEmit()
}

// Reset to default visibility
const resetToDefault = () => {
  props.columns.forEach(col => {
    visibleColumns.value[col.key] = col.defaultVisible !== false
  })
  saveAndEmit()
}

// Save to localStorage and emit update
const saveAndEmit = () => {
  try {
    localStorage.setItem(props.storageKey, JSON.stringify(visibleColumns.value))
  } catch (e) {
    console.error('Error saving column visibility:', e)
  }
  emit('update:modelValue', { ...visibleColumns.value })
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  visibleColumns.value = { ...newValue }
}, { deep: true })
</script>
