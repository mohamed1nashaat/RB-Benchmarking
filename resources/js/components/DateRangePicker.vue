<template>
  <div class="relative">
    <Popover v-slot="{ open }">
      <!-- Single Input Field -->
      <PopoverButton
        class="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-md text-xs bg-white hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
      >
        <div class="flex items-center space-x-2">
          <CalendarIcon class="h-4 w-4 text-gray-400" />
          <span class="text-gray-700 whitespace-nowrap">
            {{ formattedDateRange }}
          </span>
        </div>
        <ChevronDownIcon
          :class="['h-4 w-4 text-gray-400 transition-transform', open ? 'transform rotate-180' : '']"
        />
      </PopoverButton>

      <!-- Popover Dropdown -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <PopoverPanel
          class="absolute z-50 mt-2 bg-white rounded-lg shadow-xl border border-gray-200 p-4"
        >
          <div class="flex space-x-4">
            <!-- Quick Preset Buttons -->
            <div class="flex flex-col space-y-2 pr-4 border-r border-gray-200">
              <button
                v-for="preset in presets"
                :key="preset.label"
                @click="applyPreset(preset)"
                class="text-left px-3 py-2 text-sm rounded-md hover:bg-gray-100 transition-colors whitespace-nowrap"
                :class="isPresetActive(preset) ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700'"
              >
                {{ preset.label }}
              </button>
            </div>

            <!-- Dual Calendar -->
            <div class="flex space-x-4">
              <!-- First Calendar (Current Month) -->
              <div class="calendar-container">
                <div class="flex items-center justify-between mb-3">
                  <button
                    @click="previousMonth"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                  >
                    <ChevronLeftIcon class="h-4 w-4 text-gray-600" />
                  </button>
                  <span class="text-sm font-medium text-gray-900">
                    {{ format(currentMonth, 'MMMM yyyy') }}
                  </span>
                  <button
                    @click="nextMonth"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                  >
                    <ChevronRightIcon class="h-4 w-4 text-gray-600" />
                  </button>
                </div>
                <div class="calendar-grid">
                  <div v-for="day in weekDays" :key="day" class="calendar-weekday">
                    {{ day }}
                  </div>
                  <button
                    v-for="day in getCalendarDays(currentMonth)"
                    :key="day.date"
                    @click="selectDate(day.date)"
                    @mouseenter="hoverDate = day.date"
                    @mouseleave="hoverDate = null"
                    :disabled="!day.isCurrentMonth"
                    class="calendar-day"
                    :class="getDayClasses(day)"
                  >
                    {{ day.day }}
                  </button>
                </div>
              </div>

              <!-- Second Calendar (Next Month) -->
              <div class="calendar-container">
                <div class="flex items-center justify-between mb-3">
                  <button
                    @click="previousMonth"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                  >
                    <ChevronLeftIcon class="h-4 w-4 text-gray-600" />
                  </button>
                  <span class="text-sm font-medium text-gray-900">
                    {{ format(nextMonthDate, 'MMMM yyyy') }}
                  </span>
                  <button
                    @click="nextMonth"
                    class="p-1 hover:bg-gray-100 rounded transition-colors"
                  >
                    <ChevronRightIcon class="h-4 w-4 text-gray-600" />
                  </button>
                </div>
                <div class="calendar-grid">
                  <div v-for="day in weekDays" :key="day" class="calendar-weekday">
                    {{ day }}
                  </div>
                  <button
                    v-for="day in getCalendarDays(nextMonthDate)"
                    :key="day.date"
                    @click="selectDate(day.date)"
                    @mouseenter="hoverDate = day.date"
                    @mouseleave="hoverDate = null"
                    :disabled="!day.isCurrentMonth"
                    class="calendar-day"
                    :class="getDayClasses(day)"
                  >
                    {{ day.day }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer with Clear Button -->
          <div class="flex justify-end mt-4 pt-4 border-t border-gray-200">
            <button
              @click="clearSelection"
              class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
            >
              Clear
            </button>
          </div>
        </PopoverPanel>
      </transition>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { CalendarIcon, ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import {
  format,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  addDays,
  addMonths,
  subDays,
  subMonths,
  isSameDay,
  isWithinInterval,
  startOfYear,
  parseISO,
  isAfter,
  isBefore
} from 'date-fns'

const props = defineProps<{
  value?: {
    from: string
    to: string
  }
}>()

const emit = defineEmits(['change'])

// Date range state
const localDateRange = ref({
  from: props.value?.from || format(subDays(new Date(), 30), 'yyyy-MM-dd'),
  to: props.value?.to || format(new Date(), 'yyyy-MM-dd')
})

// Calendar navigation
const currentMonth = ref(new Date())
const nextMonthDate = computed(() => addMonths(currentMonth.value, 1))

// Selection state
const tempStartDate = ref<Date | null>(null)
const tempEndDate = ref<Date | null>(null)
const hoverDate = ref<Date | null>(null)

// Week days
const weekDays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

// Quick presets
const presets = [
  {
    label: 'Last 7 days',
    getValue: () => ({
      from: format(subDays(new Date(), 6), 'yyyy-MM-dd'),
      to: format(new Date(), 'yyyy-MM-dd')
    })
  },
  {
    label: 'Last 30 days',
    getValue: () => ({
      from: format(subDays(new Date(), 29), 'yyyy-MM-dd'),
      to: format(new Date(), 'yyyy-MM-dd')
    })
  },
  {
    label: 'This month',
    getValue: () => ({
      from: format(startOfMonth(new Date()), 'yyyy-MM-dd'),
      to: format(new Date(), 'yyyy-MM-dd')
    })
  },
  {
    label: 'Last month',
    getValue: () => {
      const lastMonth = subMonths(new Date(), 1)
      return {
        from: format(startOfMonth(lastMonth), 'yyyy-MM-dd'),
        to: format(endOfMonth(lastMonth), 'yyyy-MM-dd')
      }
    }
  },
  {
    label: 'This year',
    getValue: () => ({
      from: format(startOfYear(new Date()), 'yyyy-MM-dd'),
      to: format(new Date(), 'yyyy-MM-dd')
    })
  },
  {
    label: 'All time',
    getValue: () => ({
      from: '2010-01-01',
      to: format(new Date(), 'yyyy-MM-dd')
    })
  }
]

// Formatted date range display
const formattedDateRange = computed(() => {
  if (!localDateRange.value.from || !localDateRange.value.to) {
    return 'Select date range'
  }

  const fromDate = parseISO(localDateRange.value.from)
  const toDate = parseISO(localDateRange.value.to)

  return `${format(fromDate, 'MMM d, yy')} - ${format(toDate, 'MMM d, yy')}`
})

// Check if preset is active
const isPresetActive = (preset: any) => {
  const presetValue = preset.getValue()
  return localDateRange.value.from === presetValue.from &&
         localDateRange.value.to === presetValue.to
}

// Get calendar days for a month
const getCalendarDays = (monthDate: Date) => {
  const start = startOfWeek(startOfMonth(monthDate))
  const end = endOfWeek(endOfMonth(monthDate))
  const days = []

  let currentDate = start
  while (currentDate <= end) {
    const monthStart = startOfMonth(monthDate)
    const monthEnd = endOfMonth(monthDate)

    days.push({
      date: currentDate,
      day: currentDate.getDate(),
      isCurrentMonth: currentDate >= monthStart && currentDate <= monthEnd
    })

    currentDate = addDays(currentDate, 1)
  }

  return days
}

// Get day styling classes
const getDayClasses = (day: any) => {
  if (!day.isCurrentMonth) {
    return 'text-gray-300 cursor-not-allowed'
  }

  const fromDate = localDateRange.value.from ? parseISO(localDateRange.value.from) : null
  const toDate = localDateRange.value.to ? parseISO(localDateRange.value.to) : null

  const isStart = fromDate && isSameDay(day.date, fromDate)
  const isEnd = toDate && isSameDay(day.date, toDate)
  const isInRange = fromDate && toDate && isWithinInterval(day.date, { start: fromDate, end: toDate })

  // Handle hover preview during selection
  let isHoverPreview = false
  if (tempStartDate.value && !tempEndDate.value && hoverDate.value) {
    // Ensure start is before end to prevent invalid interval error
    const start = isBefore(hoverDate.value, tempStartDate.value) ? hoverDate.value : tempStartDate.value
    const end = isBefore(hoverDate.value, tempStartDate.value) ? tempStartDate.value : hoverDate.value
    isHoverPreview = isWithinInterval(day.date, { start, end })
  }

  const classes = []

  if (isStart && isEnd) {
    classes.push('bg-primary-600 text-white font-semibold rounded-md')
  } else if (isStart) {
    classes.push('bg-primary-600 text-white font-semibold rounded-l-md')
  } else if (isEnd) {
    classes.push('bg-primary-600 text-white font-semibold rounded-r-md')
  } else if (isInRange) {
    classes.push('bg-primary-100 text-primary-900')
  } else if (isHoverPreview) {
    classes.push('bg-primary-50 text-primary-700')
  } else {
    classes.push('hover:bg-gray-100 text-gray-900')
  }

  return classes.join(' ')
}

// Date selection logic
const selectDate = (date: Date) => {
  if (!tempStartDate.value) {
    // First click - set start date
    tempStartDate.value = date
    tempEndDate.value = null
  } else if (!tempEndDate.value) {
    // Second click - set end date
    if (isBefore(date, tempStartDate.value)) {
      // If end is before start, swap them
      tempEndDate.value = tempStartDate.value
      tempStartDate.value = date
    } else {
      tempEndDate.value = date
    }

    // Apply the selection
    localDateRange.value = {
      from: format(tempStartDate.value, 'yyyy-MM-dd'),
      to: format(tempEndDate.value, 'yyyy-MM-dd')
    }

    emit('change', { ...localDateRange.value })

    // Reset temp selection
    tempStartDate.value = null
    tempEndDate.value = null
  } else {
    // Start new selection
    tempStartDate.value = date
    tempEndDate.value = null
  }
}

// Apply preset
const applyPreset = (preset: any) => {
  const value = preset.getValue()
  localDateRange.value = value
  emit('change', { ...value })

  // Reset temp selection
  tempStartDate.value = null
  tempEndDate.value = null
}

// Clear selection
const clearSelection = () => {
  localDateRange.value = {
    from: format(subDays(new Date(), 30), 'yyyy-MM-dd'),
    to: format(new Date(), 'yyyy-MM-dd')
  }
  emit('change', { ...localDateRange.value })
  tempStartDate.value = null
  tempEndDate.value = null
}

// Month navigation
const previousMonth = () => {
  currentMonth.value = subMonths(currentMonth.value, 1)
}

const nextMonth = () => {
  currentMonth.value = addMonths(currentMonth.value, 1)
}

// Watch for external changes
watch(() => props.value, (newValue) => {
  if (newValue) {
    localDateRange.value = { ...newValue }
  }
}, { deep: true })
</script>

<style scoped>
.calendar-container {
  @apply min-w-[280px];
}

.calendar-grid {
  @apply grid grid-cols-7 gap-1;
}

.calendar-weekday {
  @apply text-center text-xs font-medium text-gray-500 py-2;
}

.calendar-day {
  @apply w-9 h-9 flex items-center justify-center text-sm rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500;
}

.calendar-day:disabled {
  @apply cursor-not-allowed;
}
</style>
