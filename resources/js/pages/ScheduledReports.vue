<template>
  <div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $t('pages.reports.title') }}</h1>
        <p class="mt-2 text-sm text-gray-700">
          {{ $t('pages.reports.description') }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="openCreateModal"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          {{ $t('pages.reports.new_report') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('labels.report_type') }}</label>
          <select
            v-model="filters.report_type"
            @change="loadReports"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t('filters.all_types') }}</option>
            <option value="performance">{{ $t('report_types.performance') }}</option>
            <option value="benchmark">{{ $t('report_types.benchmark') }}</option>
            <option value="campaign">{{ $t('report_types.campaign') }}</option>
            <option value="account">{{ $t('report_types.account') }}</option>
            <option value="industry">{{ $t('report_types.industry') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('labels.status') }}</label>
          <select
            v-model="filters.is_active"
            @change="loadReports"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t('filters.all') }}</option>
            <option value="true">{{ $t('status.active') }}</option>
            <option value="false">{{ $t('status.inactive') }}</option>
          </select>
        </div>

        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            {{ $t('pages.reports.reset_filters') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Reports List -->
    <div v-if="loading" class="mt-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-sm text-gray-500">{{ $t('pages.reports.loading') }}</p>
    </div>

    <div v-else-if="reports.length === 0" class="mt-8 text-center bg-white shadow rounded-lg p-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.reports.no_reports') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('pages.reports.empty_description') }}</p>
      <div class="mt-6">
        <button
          @click="openCreateModal"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          {{ $t('pages.reports.create_scheduled_report') }}
        </button>
      </div>
    </div>

    <div v-else class="mt-8 space-y-4">
      <div
        v-for="report in reports"
        :key="report.id"
        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
      >
        <div class="p-6">
          <!-- Header -->
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center">
                <h3 class="text-lg font-medium text-gray-900">{{ report.name }}</h3>
                <span
                  :class="[
                    'ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium',
                    report.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ report.is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ formatReportType(report.report_type) }}
                </span>
              </div>
              <p v-if="report.description" class="mt-1 text-sm text-gray-500">{{ report.description }}</p>
            </div>

            <!-- Actions -->
            <div class="ml-4 flex items-center space-x-2">
              <button
                @click="generateReport(report)"
                :disabled="generating[report.id]"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                :title="$t('buttons.generate_now')"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span class="ml-1">{{ generating[report.id] ? $t('buttons.generating') : $t('buttons.generate') }}</span>
              </button>

              <button
                @click="toggleReport(report)"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                :title="report.is_active ? $t('buttons.deactivate') : $t('buttons.activate')"
              >
                <svg v-if="report.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>

              <button
                @click="openEditModal(report)"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                :title="$t('common.edit')"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>

              <button
                @click="deleteReport(report)"
                class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                :title="$t('common.delete')"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Report Details -->
          <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
              <dt class="text-xs font-medium text-gray-500">Frequency</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ report.frequency_text }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Formats</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ (report.export_formats || ['pdf']).map(f => f.toUpperCase()).join(', ') }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Last Generated</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ report.last_generated_at ? formatDate(report.last_generated_at) : 'Never' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Next Generation</dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ report.next_generation_at ? formatDate(report.next_generation_at) : 'N/A' }}
              </dd>
            </div>
          </div>

          <!-- Recent History -->
          <div v-if="report.history && report.history.length > 0" class="mt-4 border-t border-gray-200 pt-4">
            <h4 class="text-xs font-medium text-gray-500 mb-2">Recent Generations</h4>
            <div class="space-y-2">
              <div
                v-for="history in report.history.slice(0, 3)"
                :key="history.id"
                class="flex items-center justify-between text-xs"
              >
                <div class="flex items-center space-x-2">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full font-medium',
                      history.status === 'completed' ? 'bg-green-100 text-green-800' :
                      history.status === 'failed' ? 'bg-red-100 text-red-800' :
                      history.status === 'generating' ? 'bg-blue-100 text-blue-800' :
                      'bg-gray-100 text-gray-800'
                    ]"
                  >
                    {{ history.status }}
                  </span>
                  <span class="text-gray-500">{{ formatDate(history.created_at) }}</span>
                  <span v-if="history.file_size" class="text-gray-400">
                    {{ formatFileSize(history.file_size) }}
                  </span>
                </div>
                <button
                  v-if="history.status === 'completed' && history.file_path"
                  @click="downloadReport(report, history)"
                  class="text-primary-600 hover:text-primary-800"
                >
                  Download
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <ReportModal
      v-if="showModal"
      :report="selectedReport"
      @close="closeModal"
      @saved="handleReportSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import ReportModal from '@/components/ReportModal.vue'

interface ScheduledReport {
  id: number
  name: string
  description?: string
  report_type: string
  frequency: string
  frequency_text: string
  export_formats: string[]
  is_active: boolean
  last_generated_at?: string
  next_generation_at?: string
  history?: any[]
}

const reports = ref<ScheduledReport[]>([])
const loading = ref(false)
const showModal = ref(false)
const selectedReport = ref<ScheduledReport | null>(null)
const generating = ref<Record<number, boolean>>({})

const filters = reactive({
  report_type: '',
  is_active: '',
})

onMounted(() => {
  loadReports()
})

const loadReports = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.report_type) params.append('report_type', filters.report_type)
    if (filters.is_active) params.append('is_active', filters.is_active)

    const response = await window.axios.get(`/api/scheduled-reports?${params.toString()}`)
    reports.value = response.data.data
  } catch (error: any) {
    console.error('Error loading reports:', error)
    alert('Failed to load reports: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.report_type = ''
  filters.is_active = ''
  loadReports()
}

const openCreateModal = () => {
  selectedReport.value = null
  showModal.value = true
}

const openEditModal = (report: ScheduledReport) => {
  selectedReport.value = report
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedReport.value = null
}

const handleReportSaved = () => {
  closeModal()
  loadReports()
}

const toggleReport = async (report: ScheduledReport) => {
  try {
    await window.axios.post(`/api/scheduled-reports/${report.id}/toggle`)
    report.is_active = !report.is_active
  } catch (error: any) {
    console.error('Error toggling report:', error)
    alert('Failed to toggle report: ' + (error.response?.data?.message || error.message))
  }
}

const deleteReport = async (report: ScheduledReport) => {
  if (!confirm(`Are you sure you want to delete "${report.name}"?`)) {
    return
  }

  try {
    await window.axios.delete(`/api/scheduled-reports/${report.id}`)
    reports.value = reports.value.filter(r => r.id !== report.id)
  } catch (error: any) {
    console.error('Error deleting report:', error)
    alert('Failed to delete report: ' + (error.response?.data?.message || error.message))
  }
}

const generateReport = async (report: ScheduledReport) => {
  generating.value[report.id] = true
  try {
    const response = await window.axios.post(`/api/scheduled-reports/${report.id}/generate`)
    alert('Report generated successfully!')
    loadReports()
  } catch (error: any) {
    console.error('Error generating report:', error)
    alert('Failed to generate report: ' + (error.response?.data?.message || error.message))
  } finally {
    generating.value[report.id] = false
  }
}

const downloadReport = async (report: ScheduledReport, history: any) => {
  try {
    const response = await window.axios.get(
      `/api/scheduled-reports/${report.id}/history/${history.id}/download`
    )
    const url = response.data.data.url
    window.open(url, '_blank')
  } catch (error: any) {
    console.error('Error downloading report:', error)
    alert('Failed to download report: ' + (error.response?.data?.message || error.message))
  }
}

const formatReportType = (type: string): string => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const formatFileSize = (bytes: number): string => {
  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unit = 0

  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024
    unit++
  }

  return `${size.toFixed(2)} ${units[unit]}`
}
</script>
