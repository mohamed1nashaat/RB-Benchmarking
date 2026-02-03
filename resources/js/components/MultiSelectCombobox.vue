<template>
  <Combobox v-model="selectedValues" multiple>
    <div class="relative">
      <div class="relative w-full cursor-default overflow-hidden rounded-md bg-white text-left border border-gray-300 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
        <div class="flex flex-wrap gap-1 p-1.5">
          <!-- Selected Pills/Tags -->
          <span
            v-for="value in selectedValues"
            :key="value"
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800"
          >
            {{ getOptionLabel(value) }}
            <button
              type="button"
              @click.stop="removeValue(value)"
              class="ml-1 inline-flex items-center p-0.5 text-primary-600 hover:text-primary-900"
            >
              <XMarkIcon class="h-3 w-3" />
            </button>
          </span>

          <!-- Search Input -->
          <ComboboxInput
            class="flex-1 min-w-[120px] border-none py-1 pl-2 pr-2 text-sm leading-5 text-gray-900 focus:ring-0 focus:outline-none"
            :placeholder="selectedValues.length === 0 ? placeholder : ''"
            @change="query = $event.target.value"
            :displayValue="() => ''"
          />
        </div>

        <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
          <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </ComboboxButton>
      </div>

      <transition
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <ComboboxOptions
          class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <div v-if="loading" class="relative cursor-default select-none py-2 px-4 text-gray-700">
            <div class="flex items-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></div>
              {{ $t('common.loading') }}
            </div>
          </div>

          <div v-else-if="filteredOptions.length === 0 && query !== ''" class="relative cursor-default select-none py-2 px-4 text-gray-700">
            {{ $t('common.no_results') }}
          </div>

          <template v-else>
            <!-- Select All / Clear All Options -->
            <div v-if="filteredOptions.length > 0" class="sticky top-0 bg-gray-50 border-b border-gray-200">
              <div class="flex items-center justify-between px-4 py-2">
                <button
                  type="button"
                  @click="selectAll"
                  class="text-xs font-medium text-primary-600 hover:text-primary-900"
                >
                  {{ $t('pages.benchmarks.filters.select_all') }}
                </button>
                <button
                  v-if="selectedValues.length > 0"
                  type="button"
                  @click="clearAll"
                  class="text-xs font-medium text-gray-600 hover:text-gray-900"
                >
                  {{ $t('pages.benchmarks.filters.clear_all') }}
                </button>
              </div>
            </div>

            <ComboboxOption
              v-for="option in filteredOptions"
              :key="option.value"
              :value="option.value"
              v-slot="{ active, selected }"
              as="template"
            >
              <li
                :class="[
                  active ? 'bg-primary-600 text-white' : 'text-gray-900',
                  'relative cursor-pointer select-none py-2 pl-10 pr-4'
                ]"
              >
                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                  {{ option.label }}
                </span>
                <span v-if="selected" :class="[active ? 'text-white' : 'text-primary-600', 'absolute inset-y-0 left-0 flex items-center pl-3']">
                  <CheckIcon class="h-5 w-5" aria-hidden="true" />
                </span>
              </li>
            </ComboboxOption>
          </template>
        </ComboboxOptions>
      </transition>
    </div>
  </Combobox>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, XMarkIcon } from '@heroicons/vue/24/outline'

interface Option {
  value: string
  label: string
}

interface Props {
  modelValue: string[]
  options: Option[]
  placeholder?: string
  searchPlaceholder?: string
  loading?: boolean
  disabled?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string[]): void
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select options...',
  searchPlaceholder: 'Search...',
  loading: false,
  disabled: false
})

const emit = defineEmits<Emits>()
const { t } = useI18n()

const query = ref('')
const selectedValues = ref<string[]>([...props.modelValue])

// Watch for external changes to modelValue (parent updates)
watch(() => props.modelValue, (newValue) => {
  // Only update if the values actually changed to prevent unnecessary updates
  if (JSON.stringify(newValue) !== JSON.stringify(selectedValues.value)) {
    selectedValues.value = [...newValue]
  }
}, { deep: true })

// Watch for user-initiated changes (Combobox selections)
// We watch selectedValues but emit only after a short delay to batch rapid changes
let emitTimeout: NodeJS.Timeout | null = null
watch(selectedValues, (newValue) => {
  // Clear any pending emit
  if (emitTimeout) clearTimeout(emitTimeout)

  // Debounce emit to batch rapid selections (e.g., holding down arrow key)
  emitTimeout = setTimeout(() => {
    emit('update:modelValue', newValue)
    emitTimeout = null
  }, 50) // 50ms debounce for smooth UX
}, { deep: true })

// Filter options based on search query
const filteredOptions = computed(() => {
  if (query.value === '') {
    return props.options
  }

  return props.options.filter((option) =>
    option.label.toLowerCase().includes(query.value.toLowerCase())
  )
})

// Get label for a given value
const getOptionLabel = (value: string): string => {
  const option = props.options.find(opt => opt.value === value)
  return option ? option.label : value
}

// Remove a specific value
const removeValue = (value: string) => {
  selectedValues.value = selectedValues.value.filter(v => v !== value)
}

// Select all filtered options
const selectAll = () => {
  const allValues = new Set([...selectedValues.value, ...filteredOptions.value.map(opt => opt.value)])
  selectedValues.value = Array.from(allValues)
}

// Clear all selected values
const clearAll = () => {
  selectedValues.value = []
}
</script>
