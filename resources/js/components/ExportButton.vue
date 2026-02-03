<template>
  <div class="relative">
    <Menu as="div" class="relative inline-block text-left">
      <div>
        <MenuButton
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <ArrowDownTrayIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          {{ $t('dashboard.export') }}
          <ChevronDownIcon class="ml-2 -mr-1 h-4 w-4" aria-hidden="true" />
        </MenuButton>
      </div>

      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="transform scale-95 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-95 opacity-0"
      >
        <MenuItems
          class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        >
          <div class="py-1">
            <MenuItem v-slot="{ active }">
              <button
                @click="exportData('csv')"
                :disabled="exporting"
                :class="[
                  active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                  'group flex w-full items-center px-4 py-2 text-sm disabled:opacity-50'
                ]"
              >
                <DocumentIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                {{ $t('modals.export_as_csv') }}
              </button>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button
                @click="exportData('xlsx')"
                :disabled="exporting"
                :class="[
                  active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                  'group flex w-full items-center px-4 py-2 text-sm disabled:opacity-50'
                ]"
              >
                <TableCellsIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                {{ $t('modals.export_as_excel') }}
              </button>
            </MenuItem>
          </div>
        </MenuItems>
      </transition>
    </Menu>

    <!-- Export Status Modal -->
    <TransitionRoot as="template" :show="showExportModal">
      <Dialog as="div" class="relative z-50" @close="showExportModal = false">
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
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6">
                <div>
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                    <CheckIcon v-if="exportStatus === 'completed'" class="h-6 w-6 text-green-600" aria-hidden="true" />
                    <ArrowPathIcon v-else class="h-6 w-6 text-primary-600 animate-spin" aria-hidden="true" />
                  </div>
                  <div class="mt-3 text-center sm:mt-5">
                    <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                      {{ exportStatus === 'completed' ? $t('modals.export_ready') : $t('modals.preparing_export') }}
                    </DialogTitle>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500">
                        {{ exportStatus === 'completed'
                          ? $t('modals.export_ready_message')
                          : $t('modals.preparing_export_message')
                        }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-6">
                  <button
                    v-if="exportStatus === 'completed'"
                    @click="downloadExport"
                    type="button"
                    class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                  >
                    {{ $t('buttons.download') }}
                  </button>
                  <button
                    v-else
                    @click="showExportModal = false"
                    type="button"
                    class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
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
import { ref } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem, Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { 
  ArrowDownTrayIcon, 
  ChevronDownIcon, 
  DocumentIcon, 
  TableCellsIcon,
  CheckIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'
import axios from 'axios'

const dashboardStore = useDashboardStore()

const exporting = ref(false)
const showExportModal = ref(false)
const exportStatus = ref<'preparing' | 'completed'>('preparing')
const currentExportId = ref<string | null>(null)

const exportData = async (format: 'csv' | 'xlsx') => {
  exporting.value = true
  showExportModal.value = true
  exportStatus.value = 'preparing'

  try {
    const params = {
      format,
      from: dashboardStore.dateRange.from,
      to: dashboardStore.dateRange.to,
      objective: dashboardStore.objective,
      ...dashboardStore.filters
    }

    const response = await axios.post('/api/reports/export', params)
    currentExportId.value = response.data.export_id

    // Poll for export completion
    pollExportStatus()
  } catch (error) {
    console.error('Export error:', error)
    showExportModal.value = false
  } finally {
    exporting.value = false
  }
}

const pollExportStatus = async () => {
  if (!currentExportId.value) return

  try {
    const response = await axios.get(`/api/reports/${currentExportId.value}`)
    const { status, download_url } = response.data

    if (status === 'completed') {
      exportStatus.value = 'completed'
      // Store download URL for later use
      currentExportId.value = download_url
    } else if (status === 'failed') {
      showExportModal.value = false
      // Could show error message here
    } else {
      // Still processing, poll again
      setTimeout(pollExportStatus, 2000)
    }
  } catch (error) {
    console.error('Export status error:', error)
    showExportModal.value = false
  }
}

const downloadExport = () => {
  if (currentExportId.value) {
    // Create a temporary link to download the file
    const link = document.createElement('a')
    link.href = currentExportId.value
    link.download = ''
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
  
  showExportModal.value = false
  currentExportId.value = null
}
</script>
