import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

export interface FilterPreset {
  id: string
  name: string
  filters: Record<string, string | string[]>
  dateRange?: {
    from: string
    to: string
  }
  createdAt: string
}

const STORAGE_KEY = 'benchmark_filter_presets'

export function useFilterPresets() {
  const { t } = useI18n()
  const presets = ref<FilterPreset[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Load presets from localStorage
  const loadPresets = () => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY)
      if (stored) {
        presets.value = JSON.parse(stored)
      }
    } catch (e) {
      console.error('Failed to load filter presets:', e)
      error.value = t('pages.benchmarks.filters.load_error')
    }
  }

  // Save presets to localStorage
  const savePresetsToStorage = () => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(presets.value))
    } catch (e) {
      console.error('Failed to save filter presets:', e)
      error.value = t('pages.benchmarks.filters.save_error')
    }
  }

  // Create a new preset
  const createPreset = (
    name: string,
    filters: Record<string, string | string[]>,
    dateRange?: { from: string; to: string }
  ): FilterPreset | null => {
    try {
      const preset: FilterPreset = {
        id: `preset-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        name: name.trim(),
        filters: JSON.parse(JSON.stringify(filters)), // Deep clone
        dateRange: dateRange ? { ...dateRange } : undefined,
        createdAt: new Date().toISOString()
      }

      presets.value.push(preset)
      savePresetsToStorage()
      error.value = null
      return preset
    } catch (e) {
      console.error('Failed to create preset:', e)
      error.value = t('pages.benchmarks.filters.create_error')
      return null
    }
  }

  // Delete a preset by ID
  const deletePreset = (id: string): boolean => {
    try {
      const index = presets.value.findIndex(p => p.id === id)
      if (index === -1) {
        error.value = t('pages.benchmarks.filters.preset_not_found')
        return false
      }

      presets.value.splice(index, 1)
      savePresetsToStorage()
      error.value = null
      return true
    } catch (e) {
      console.error('Failed to delete preset:', e)
      error.value = t('pages.benchmarks.filters.delete_error')
      return false
    }
  }

  // Get a preset by ID
  const getPreset = (id: string): FilterPreset | null => {
    return presets.value.find(p => p.id === id) || null
  }

  // Update an existing preset
  const updatePreset = (
    id: string,
    name: string,
    filters: Record<string, string | string[]>,
    dateRange?: { from: string; to: string }
  ): boolean => {
    try {
      const preset = presets.value.find(p => p.id === id)
      if (!preset) {
        error.value = t('pages.benchmarks.filters.preset_not_found')
        return false
      }

      preset.name = name.trim()
      preset.filters = JSON.parse(JSON.stringify(filters))
      preset.dateRange = dateRange ? { ...dateRange } : undefined

      savePresetsToStorage()
      error.value = null
      return true
    } catch (e) {
      console.error('Failed to update preset:', e)
      error.value = t('pages.benchmarks.filters.update_error')
      return false
    }
  }

  // Clear all error messages
  const clearError = () => {
    error.value = null
  }

  // Computed: Check if a preset name already exists
  const presetNameExists = computed(() => (name: string) => {
    return presets.value.some(p => p.name.toLowerCase() === name.toLowerCase().trim())
  })

  // Computed: Get presets sorted by creation date (newest first)
  const sortedPresets = computed(() => {
    return [...presets.value].sort((a, b) =>
      new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    )
  })

  // Initialize - load presets on first use
  loadPresets()

  return {
    presets: sortedPresets,
    isLoading,
    error,
    createPreset,
    deletePreset,
    getPreset,
    updatePreset,
    clearError,
    presetNameExists
  }
}
