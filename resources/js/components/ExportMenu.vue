<template>
  <div class="relative inline-block text-left" ref="menuRef">
    <button
      @click="toggleMenu"
      :disabled="loading"
      class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
    >
      <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
      <span class="hidden sm:inline">{{ $t('dashboard.export') }}</span>
      <span class="sm:hidden">{{ $t('buttons.export_report') }}</span>
      <ChevronDownIcon class="h-4 w-4 ml-1" />
    </button>

    <!-- Dropdown menu -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="menuOpen"
        class="absolute right-0 z-10 mt-2 w-48 sm:w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        role="menu"
      >
        <div class="py-1" role="none">
          <button
            @click="handleExport('pdf')"
            :disabled="exporting"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
            role="menuitem"
          >
            <DocumentIcon class="h-4 w-4 mr-3 text-red-500" />
            <span>{{ $t('messages.pdf_export_success').replace(' successfully', '') }}</span>
            <div v-if="exporting === 'pdf'" class="ml-auto">
              <div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-red-500 rounded-full"></div>
            </div>
          </button>

          <button
            @click="handleExport('excel')"
            :disabled="exporting"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
            role="menuitem"
          >
            <TableCellsIcon class="h-4 w-4 mr-3 text-green-500" />
            <span>{{ $t('messages.excel_export_success').replace(' successfully', '') }}</span>
            <div v-if="exporting === 'excel'" class="ml-auto">
              <div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-green-500 rounded-full"></div>
            </div>
          </button>

          <button
            @click="handleExport('csv')"
            :disabled="exporting"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
            role="menuitem"
          >
            <DocumentTextIcon class="h-4 w-4 mr-3 text-blue-500" />
            <span>{{ $t('messages.csv_export_success').replace(' successfully', '') }}</span>
            <div v-if="exporting === 'csv'" class="ml-auto">
              <div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-blue-500 rounded-full"></div>
            </div>
          </button>

          <div class="border-t border-gray-100 my-1"></div>

          <button
            @click="handleExport('print')"
            :disabled="exporting"
            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
            role="menuitem"
          >
            <PrinterIcon class="h-4 w-4 mr-3 text-gray-500" />
            <span>{{ $t('buttons.print_report') }}</span>
          </button>
        </div>
      </div>
    </transition>

    <!-- Success/Error Toast -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform opacity-0 translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 translate-y-2"
    >
      <div
        v-if="toast.show"
        class="fixed bottom-4 right-4 z-50 flex items-center p-4 rounded-md shadow-lg"
        :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
      >
        <CheckCircleIcon v-if="toast.type === 'success'" class="h-5 w-5 mr-2" />
        <ExclamationCircleIcon v-else class="h-5 w-5 mr-2" />
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ChevronDownIcon,
  DocumentIcon,
  TableCellsIcon,
  DocumentTextIcon,
  PrinterIcon,
  CheckCircleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import { exportToPDF, exportToExcel, exportToCSV, formatDataForExport } from '../utils/exportUtils'

const { t } = useI18n()

interface Props {
  data?: any
  elementId?: string
  filename?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  elementId: 'benchmark-report',
  filename: 'benchmark-report',
  loading: false
})

const emit = defineEmits<{
  export: [type: string, success: boolean]
}>()

const menuRef = ref<HTMLElement>()
const menuOpen = ref(false)
const exporting = ref<string | false>(false)
const toast = ref({
  show: false,
  type: 'success' as 'success' | 'error',
  message: ''
})

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const closeMenu = () => {
  menuOpen.value = false
}

const showToast = (type: 'success' | 'error', message: string) => {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const handleExport = async (type: string) => {
  if (exporting.value) return

  exporting.value = type
  closeMenu()

  try {
    let success = false

    switch (type) {
      case 'pdf':
        success = await exportToPDF(props.elementId, `${props.filename}.pdf`)
        showToast(
          success ? 'success' : 'error',
          success ? t('messages.pdf_export_success') : t('messages.pdf_export_failed')
        )
        break

      case 'excel':
        if (props.data) {
          const exportData = formatDataForExport(
            props.data.industryBenchmarks,
            props.data.trendingData,
            props.data.summary
          )
          success = exportToExcel(exportData, `${props.filename}.xlsx`)
          showToast(
            success ? 'success' : 'error',
            success ? t('messages.excel_export_success') : t('messages.excel_export_failed')
          )
        }
        break

      case 'csv':
        if (props.data) {
          const exportData = formatDataForExport(
            props.data.industryBenchmarks,
            props.data.trendingData,
            props.data.summary
          )
          // Export the first sheet as CSV
          if (exportData.length > 0) {
            success = exportToCSV(exportData[0].data, `${props.filename}.csv`, exportData[0].headers)
            showToast(
              success ? 'success' : 'error',
              success ? t('messages.csv_export_success') : t('messages.csv_export_failed')
            )
          }
        }
        break

      case 'print':
        window.print()
        success = true
        break
    }

    emit('export', type, success)
  } catch (error) {
    console.error(`Export ${type} failed:`, error)
    showToast('error', `Failed to export ${type.toUpperCase()}`)
    emit('export', type, false)
  } finally {
    exporting.value = false
  }
}

const handleClickOutside = (event: Event) => {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>