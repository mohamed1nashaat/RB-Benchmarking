<template>
  <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <!-- Filter Header with Presets and Clear All -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-3 sm:space-y-0">
      <div class="flex items-center space-x-3">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('filters.benchmark_filters') }}
        </h3>
        <span
          v-if="activeFilterCount > 0"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800"
        >
          {{ $t('pages.benchmarks.filters.active_count', { count: activeFilterCount }) }}
        </span>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Filter Presets -->
        <FilterPresets
          :current-filters="filters"
          :date-range="dateRange"
          @load-preset="loadPreset"
        />

        <!-- Clear All Button -->
        <button
          v-if="activeFilterCount > 0"
          @click="clearAllFilters"
          class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <XMarkIcon class="h-4 w-4 mr-2" />
          {{ $t('filters.clear_all') }}
        </button>
      </div>
    </div>

    <!-- Filter Grid - All filters in one row -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
      <!-- Platform Filter (Multi-select Combobox) -->
      <div class="relative">
        <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
          <span class="flex items-center">
            <ServerIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ $t('labels.platform') }}
          </span>
          <button
            v-if="filters.platform.length > 0"
            @click="clearFilter('platform')"
            class="text-gray-400 hover:text-gray-600"
            :title="$t('pages.benchmarks.filters.clear_filter', { filter: $t('labels.platform') })"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </label>
        <MultiSelectCombobox
          v-model="filters.platform"
          :options="platformOptions"
          :placeholder="$t('filters.all_platforms')"
          :searchPlaceholder="$t('pages.benchmarks.filters.search_placeholder', { filter: $t('labels.platform') })"
          @update:modelValue="updateFilters"
        />
      </div>

      <!-- Funnel Stage Filter (Multi-select Combobox) -->
      <div class="relative">
        <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
          <span class="flex items-center">
            <FunnelIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ $t('funnel_stages.label') }}
          </span>
          <button
            v-if="filters.funnel_stage.length > 0"
            @click="clearFilter('funnel_stage')"
            class="text-gray-400 hover:text-gray-600"
            :title="$t('pages.benchmarks.filters.clear_filter', { filter: $t('funnel_stages.label') })"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </label>
        <MultiSelectCombobox
          v-model="filters.funnel_stage"
          :options="funnelStageOptions"
          :placeholder="$t('funnel_stages.all')"
          :searchPlaceholder="$t('pages.benchmarks.filters.search_placeholder', { filter: $t('funnel_stages.label') })"
          @update:modelValue="updateFilters"
        />
      </div>

      <!-- Industry Filter (Multi-select Combobox with API data) -->
      <div class="relative">
        <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
          <span class="flex items-center">
            <BuildingOfficeIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ $t('labels.industry') }}
          </span>
          <button
            v-if="filters.industry.length > 0"
            @click="clearFilter('industry')"
            class="text-gray-400 hover:text-gray-600"
            :title="$t('pages.benchmarks.filters.clear_filter', { filter: $t('labels.industry') })"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </label>
        <MultiSelectCombobox
          v-model="filters.industry"
          :options="industryOptions"
          :placeholder="$t('filters.all_industries')"
          :searchPlaceholder="$t('pages.benchmarks.filters.search_placeholder', { filter: $t('labels.industry') })"
          :loading="loadingIndustries"
          @update:modelValue="onIndustryChange"
        />
      </div>

      <!-- Sub-Industry Filter (Multi-select Combobox with dynamic data) -->
      <div class="relative">
        <label class="flex items-center justify-between text-sm font-medium text-gray-700 mb-1">
          <span class="flex items-center">
            <TagIcon class="h-4 w-4 mr-1.5 text-gray-400" />
            {{ $t('filters.sub_industry') }}
          </span>
          <button
            v-if="filters.sub_industry.length > 0"
            @click="clearFilter('sub_industry')"
            class="text-gray-400 hover:text-gray-600"
            :title="$t('pages.benchmarks.filters.clear_filter', { filter: $t('filters.sub_industry') })"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </label>
        <MultiSelectCombobox
          v-model="filters.sub_industry"
          :options="subIndustryOptions"
          :placeholder="$t('filters.all_sub_industries')"
          :searchPlaceholder="$t('pages.benchmarks.filters.search_placeholder', { filter: $t('filters.sub_industry') })"
          :loading="loadingSubIndustries"
          :disabled="filters.industry.length === 0"
          @update:modelValue="updateFilters"
        />
      </div>

      <!-- Date Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          <CalendarIcon class="inline h-4 w-4 mr-1.5 text-gray-400" />
          {{ $t('dashboard.date_range') }}
        </label>
        <DateRangePicker @change="onDateRangeChange" :value="dateRange" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  XMarkIcon,
  ServerIcon,
  FunnelIcon,
  BuildingOfficeIcon,
  TagIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'
import DateRangePicker from '@/components/DateRangePicker.vue'
import FilterPresets from '@/components/FilterPresets.vue'
import MultiSelectCombobox from '@/components/MultiSelectCombobox.vue'
import type { FilterPreset } from '@/composables/useFilterPresets'

interface Props {
  initialFilters?: any
  dateRange?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['filtersChanged'])
const { t } = useI18n()

// Filter data - now using arrays for multi-select
const filters = ref({
  platform: [] as string[],
  funnel_stage: [] as string[],
  industry: [] as string[],
  sub_industry: [] as string[]
})

// Loading states
const loadingIndustries = ref(false)
const loadingSubIndustries = ref(false)

// Dynamic data
const industries = ref<Array<{name: string, display_name: string}>>([])
const subIndustries = ref<Array<{name: string, display_name: string}>>([])

// Compute active filter count
const activeFilterCount = computed(() => {
  let count = 0
  Object.entries(filters.value).forEach(([key, value]) => {
    if (Array.isArray(value) && value.length > 0) {
      count++
    }
  })
  return count
})

// Platform options
const platformOptions = computed(() => [
  { value: 'facebook', label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'google', label: 'Google Ads' },
  { value: 'youtube', label: 'YouTube' },
  { value: 'linkedin', label: 'LinkedIn' },
  { value: 'twitter', label: 'Twitter' },
  { value: 'tiktok', label: 'TikTok' },
  { value: 'snapchat', label: 'Snapchat' }
])

// Funnel stage options
const funnelStageOptions = computed(() => [
  { value: 'awareness', label: t('funnel_stages.awareness') },
  { value: 'consideration', label: t('funnel_stages.consideration') },
  { value: 'conversion', label: t('funnel_stages.conversion') },
  { value: 'retention', label: t('funnel_stages.retention') }
])

// Industry options (from API)
const industryOptions = computed(() =>
  industries.value.map(ind => ({ value: ind.name, label: ind.display_name }))
)

// Sub-industry options (from API, dynamic based on industry)
const subIndustryOptions = computed(() =>
  subIndustries.value.map(sub => ({ value: sub.name, label: sub.display_name }))
)

// Load industries from API
const loadIndustries = async () => {
  loadingIndustries.value = true
  try {
    const response = await fetch('/api/industries', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      industries.value = data.data.map((industry: any) => ({
        name: industry.name,
        display_name: industry.display_name
      }))
    } else {
      // Fallback to static data
      industries.value = [
        { name: 'automotive', display_name: 'Automotive' },
        { name: 'beauty_fitness', display_name: 'Beauty & Fitness' },
        { name: 'technology', display_name: 'Technology' },
        { name: 'retail_ecommerce', display_name: 'Retail & E-commerce' },
        { name: 'real_estate', display_name: 'Real Estate' },
        { name: 'finance_insurance', display_name: 'Finance & Insurance' },
        { name: 'health_medicine', display_name: 'Health & Medicine' },
        { name: 'food_beverage', display_name: 'Food & Beverage' },
        { name: 'education', display_name: 'Education' },
        { name: 'entertainment', display_name: 'Entertainment' }
      ]
    }
  } catch (error) {
    console.error('Error loading industries:', error)
  } finally {
    loadingIndustries.value = false
  }
}

// Load sub-industries based on selected industries
const loadSubIndustries = async () => {
  if (filters.value.industry.length === 0) {
    subIndustries.value = []
    return
  }

  // For simplicity, load sub-industries for the first selected industry
  const primaryIndustry = filters.value.industry[0]

  loadingSubIndustries.value = true
  try {
    const response = await fetch(`/api/industries/${primaryIndustry}/sub-industries`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (response.ok) {
      const data = await response.json()
      subIndustries.value = data.data.map((sub: any) => ({
        name: sub.name,
        display_name: sub.display_name
      }))
    } else {
      subIndustries.value = []
    }
  } catch (error) {
    console.error('Error loading sub-industries:', error)
    subIndustries.value = []
  } finally {
    loadingSubIndustries.value = false
  }
}

// Handle industry change (user selects/deselects industries)
const onIndustryChange = () => {
  // Clear sub-industry selections when industries change
  filters.value.sub_industry = []

  // Load sub-industries for newly selected industries
  loadSubIndustries()

  // Emit filter changes to parent
  updateFilters()
}

// Update filters (with debouncing to batch rapid changes)
let updateTimeout: NodeJS.Timeout | null = null
const updateFilters = () => {
  if (updateTimeout) {
    clearTimeout(updateTimeout)
  }

  updateTimeout = setTimeout(() => {
    emit('filtersChanged', { ...filters.value })
  }, 500) // Increased from 300ms to 500ms for better performance
}

// Clear individual filter
const clearFilter = (filterName: keyof typeof filters.value) => {
  (filters.value[filterName] as string[]) = []
  updateFilters()
}

// Clear all filters
const clearAllFilters = () => {
  filters.value = {
    platform: [],
    objective: [],
    funnel_stage: [],
    industry: [],
    sub_industry: []
  }
  updateFilters()
}

// Handle date range change
const onDateRangeChange = (newDateRange: any) => {
  emit('filtersChanged', { ...filters.value, dateRange: newDateRange })
}

// Load preset
const loadPreset = (preset: FilterPreset) => {
  // Convert single values to arrays if needed, only for filters that still exist
  Object.entries(preset.filters).forEach(([key, value]) => {
    if (key in filters.value) {
      filters.value[key as keyof typeof filters.value] = Array.isArray(value) ? value : (value ? [value] : [])
    }
  })

  if (preset.dateRange) {
    onDateRangeChange(preset.dateRange)
  } else {
    updateFilters()
  }
}

// Initialize
onMounted(async () => {
  await loadIndustries()

  // Set initial filters if provided
  if (props.initialFilters) {
    Object.entries(props.initialFilters).forEach(([key, value]) => {
      if (key === 'has_pixel_data') {
        filters.value[key as keyof typeof filters.value] = value as string
      } else {
        // Convert single values to arrays
        filters.value[key as keyof typeof filters.value] = Array.isArray(value) ? value : (value ? [value as string] : [])
      }
    })

    // Load sub-industries if industry is selected
    if (filters.value.industry.length > 0) {
      await loadSubIndustries()
    }
  }
})
</script>
