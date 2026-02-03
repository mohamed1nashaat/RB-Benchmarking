<template>
  <div class="flex items-center space-x-3">
    <!-- Load Preset Dropdown -->
    <Menu as="div" class="relative">
      <MenuButton
        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <BookmarkIcon class="h-4 w-4 mr-2" />
        {{ $t('pages.benchmarks.filters.load_preset') }}
        <span v-if="presets.length > 0" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
          {{ presets.length }}
        </span>
        <ChevronDownIcon class="ml-2 h-4 w-4" />
      </MenuButton>

      <transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <MenuItems
          class="absolute left-0 z-10 mt-2 w-80 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        >
          <div class="py-1">
            <div v-if="presets.length === 0" class="px-4 py-8 text-center">
              <BookmarkIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">
                {{ $t('pages.benchmarks.filters.no_presets') }}
              </p>
            </div>

            <MenuItem
              v-for="preset in presets"
              :key="preset.id"
              v-slot="{ active }"
            >
              <div
                :class="[
                  active ? 'bg-gray-100' : '',
                  'group flex items-center justify-between px-4 py-2 text-sm'
                ]"
              >
                <button
                  @click="loadPreset(preset)"
                  class="flex-1 text-left text-gray-700 hover:text-gray-900"
                >
                  <div class="font-medium">{{ preset.name }}</div>
                  <div class="text-xs text-gray-500 mt-0.5">
                    {{ formatPresetDate(preset.createdAt) }}
                    <span v-if="getActiveFilterCount(preset.filters) > 0" class="ml-2">
                      • {{ getActiveFilterCount(preset.filters) }} {{ $t('pages.benchmarks.filters.active_filters') }}
                    </span>
                  </div>
                </button>
                <button
                  @click.stop="confirmDelete(preset)"
                  class="ml-3 text-gray-400 hover:text-red-600 transition-colors"
                  :title="$t('pages.benchmarks.filters.delete_preset')"
                >
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </MenuItem>
          </div>
        </MenuItems>
      </transition>
    </Menu>

    <!-- Save Preset Button -->
    <button
      @click="showSaveDialog = true"
      :disabled="!hasActiveFilters"
      class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
      :title="hasActiveFilters ? $t('pages.benchmarks.filters.save_preset') : $t('pages.benchmarks.filters.no_active_filters')"
    >
      <PlusIcon class="h-4 w-4 mr-2" />
      {{ $t('pages.benchmarks.filters.save_preset') }}
    </button>

    <!-- Save Preset Dialog -->
    <TransitionRoot :show="showSaveDialog" as="template">
      <Dialog as="div" class="relative z-50" @close="showSaveDialog = false">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div>
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
                    <BookmarkIcon class="h-6 w-6 text-primary-600" />
                  </div>
                  <div class="mt-3 text-center sm:mt-5">
                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                      {{ $t('pages.benchmarks.filters.save_preset') }}
                    </DialogTitle>
                    <div class="mt-4">
                      <label for="preset-name" class="block text-sm font-medium text-gray-700 text-left mb-2">
                        {{ $t('pages.benchmarks.filters.preset_name') }}
                      </label>
                      <input
                        id="preset-name"
                        v-model="presetName"
                        type="text"
                        :placeholder="$t('pages.benchmarks.filters.preset_name_placeholder')"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        @keyup.enter="savePreset"
                      />
                      <p v-if="nameError" class="mt-2 text-sm text-red-600">{{ nameError }}</p>
                      <p class="mt-2 text-sm text-gray-500 text-left">
                        {{ $t('pages.benchmarks.filters.preset_description', { count: getActiveFilterCount(currentFilters) }) }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                  <button
                    type="button"
                    @click="savePreset"
                    :disabled="!presetName.trim() || !!nameError"
                    class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50 disabled:cursor-not-allowed sm:col-start-2"
                  >
                    {{ $t('common.save') }}
                  </button>
                  <button
                    type="button"
                    @click="showSaveDialog = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                  >
                    {{ $t('common.cancel') }}
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Delete Confirmation Dialog -->
    <TransitionRoot :show="showDeleteDialog" as="template">
      <Dialog as="div" class="relative z-50" @close="showDeleteDialog = false">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div class="sm:flex sm:items-start">
                  <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                      {{ $t('pages.benchmarks.filters.delete_preset') }}
                    </DialogTitle>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500">
                        {{ $t('pages.benchmarks.filters.delete_preset_confirm', { name: presetToDelete?.name }) }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                  <button
                    type="button"
                    @click="deletePresetConfirmed"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                  >
                    {{ $t('common.delete') }}
                  </button>
                  <button
                    type="button"
                    @click="showDeleteDialog = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                  >
                    {{ $t('common.cancel') }}
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Menu,
  MenuButton,
  MenuItems,
  MenuItem,
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue'
import {
  BookmarkIcon,
  ChevronDownIcon,
  PlusIcon,
  TrashIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useFilterPresets, type FilterPreset } from '@/composables/useFilterPresets'

interface Props {
  currentFilters: Record<string, string | string[]>
  dateRange?: { from: string; to: string }
}

interface Emits {
  (e: 'load-preset', preset: FilterPreset): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { t } = useI18n()
const { presets, createPreset, deletePreset: deletePresetFromStore, presetNameExists } = useFilterPresets()

const showSaveDialog = ref(false)
const showDeleteDialog = ref(false)
const presetName = ref('')
const nameError = ref('')
const presetToDelete = ref<FilterPreset | null>(null)

// Check if there are any active filters
const hasActiveFilters = computed(() => {
  return Object.values(props.currentFilters).some(value => {
    if (Array.isArray(value)) {
      return value.length > 0
    }
    return value !== '' && value !== null && value !== undefined
  })
})

// Get count of active filters
const getActiveFilterCount = (filters: Record<string, string | string[]>): number => {
  return Object.values(filters).filter(value => {
    if (Array.isArray(value)) {
      return value.length > 0
    }
    return value !== '' && value !== null && value !== undefined
  }).length
}

// Format preset creation date
const formatPresetDate = (date: string): string => {
  const d = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - d.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays === 0) {
    return t('common.today')
  } else if (diffDays === 1) {
    return t('common.yesterday')
  } else if (diffDays < 7) {
    return t('common.days_ago', { count: diffDays })
  } else {
    return d.toLocaleDateString()
  }
}

// Watch preset name for validation
watch(() => presetName.value, (newName) => {
  if (newName.trim() && presetNameExists.value(newName)) {
    nameError.value = t('pages.benchmarks.filters.preset_name_exists')
  } else {
    nameError.value = ''
  }
})

// Save current filters as a preset
const savePreset = () => {
  if (!presetName.value.trim() || nameError.value) {
    return
  }

  const preset = createPreset(presetName.value, props.currentFilters, props.dateRange)
  if (preset) {
    showSaveDialog.value = false
    presetName.value = ''
    nameError.value = ''
  }
}

// Load a preset
const loadPreset = (preset: FilterPreset) => {
  emit('load-preset', preset)
}

// Confirm delete preset
const confirmDelete = (preset: FilterPreset) => {
  presetToDelete.value = preset
  showDeleteDialog.value = true
}

// Delete preset after confirmation
const deletePresetConfirmed = () => {
  if (presetToDelete.value) {
    deletePresetFromStore(presetToDelete.value.id)
    showDeleteDialog.value = false
    presetToDelete.value = null
  }
}
</script>
