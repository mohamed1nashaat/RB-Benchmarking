<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity" @click="$emit('close')">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
        <div class="sm:flex sm:items-start">
          <div class="w-full">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  Campaign Integration Settings
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Configure Google Sheets and conversion pixel tracking for {{ campaign?.name }}
                </p>
              </div>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-500 focus:outline-none"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              <span class="ml-3 text-gray-600">Loading integration settings...</span>
            </div>

            <!-- Content Tabs -->
            <div v-else class="space-y-6">
              <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                  <button
                    @click="activeTab = 'sheets'"
                    :class="[
                      'py-2 px-1 border-b-2 font-medium text-sm',
                      activeTab === 'sheets'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Google Sheets
                  </button>
                  <button
                    @click="activeTab = 'pixel'"
                    :class="[
                      'py-2 px-1 border-b-2 font-medium text-sm',
                      activeTab === 'pixel'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Conversion Pixel
                  </button>
                  <button
                    @click="activeTab = 'analytics'"
                    :class="[
                      'py-2 px-1 border-b-2 font-medium text-sm',
                      activeTab === 'analytics'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Analytics
                  </button>
                </nav>
              </div>

              <!-- Google Sheets Tab -->
              <div v-if="activeTab === 'sheets'" class="space-y-6">
                <!-- Sheets Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0">
                        <div :class="[
                          'h-8 w-8 rounded-full flex items-center justify-center',
                          sheetsConfig.enabled ? 'bg-green-100' : 'bg-gray-100'
                        ]">
                          <CheckCircleIcon v-if="sheetsConfig.enabled" class="h-5 w-5 text-green-600" />
                          <XCircleIcon v-else class="h-5 w-5 text-gray-400" />
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                          Google Sheets Integration
                        </p>
                        <p class="text-sm text-gray-500">
                          {{ sheetsConfig.enabled ? 'Active' : 'Inactive' }}
                          <span v-if="sheetsConfig.last_sync">
                            • Last sync: {{ formatDate(sheetsConfig.last_sync) }}
                          </span>
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button
                        v-if="sheetsConfig.sheet_url"
                        @click="openSheet"
                        class="text-sm text-blue-600 hover:text-blue-500"
                      >
                        View Sheet
                      </button>
                      <button
                        @click="toggleSheetsIntegration"
                        :disabled="saving"
                        :class="[
                          'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                          sheetsConfig.enabled ? 'bg-blue-600' : 'bg-gray-200'
                        ]"
                      >
                        <span
                          :class="[
                            'pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                            sheetsConfig.enabled ? 'translate-x-5' : 'translate-x-0'
                          ]"
                        />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Sheet Mapping Configuration -->
                <div v-if="sheetsConfig.enabled || sheetsConfig.has_sheet" class="space-y-4">
                  <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Sheet Column Mapping</h4>
                    <p class="text-sm text-gray-500 mb-4">
                      Configure how conversion data maps to your Google Sheet columns
                    </p>
                  </div>

                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="(fieldName, sheetColumn) in sheetMapping" :key="sheetColumn" class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ sheetColumn }}
                      </label>
                      <select
                        v-model="sheetMapping[sheetColumn]"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="">-- Select Field --</option>
                        <option v-for="field in availableFields" :key="field.key" :value="field.key">
                          {{ field.label }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <div class="flex items-center space-x-3">
                    <button
                      @click="saveSheetMapping"
                      :disabled="saving"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                      <span v-if="saving">Saving...</span>
                      <span v-else>Save Mapping</span>
                    </button>
                    <button
                      @click="syncToSheets"
                      :disabled="saving || !sheetsConfig.has_sheet"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                      Sync Now
                    </button>
                  </div>
                </div>
              </div>

              <!-- Conversion Pixel Tab -->
              <div v-if="activeTab === 'pixel'" class="space-y-6">
                <!-- Pixel Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0">
                        <div :class="[
                          'h-8 w-8 rounded-full flex items-center justify-center',
                          pixelConfig.enabled ? 'bg-green-100' : 'bg-gray-100'
                        ]">
                          <CheckCircleIcon v-if="pixelConfig.enabled" class="h-5 w-5 text-green-600" />
                          <XCircleIcon v-else class="h-5 w-5 text-gray-400" />
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                          Conversion Pixel Tracking
                        </p>
                        <p class="text-sm text-gray-500">
                          {{ pixelConfig.enabled ? 'Active' : 'Inactive' }}
                          <span v-if="pixelConfig.pixel_id">
                            • ID: {{ pixelConfig.pixel_id }}
                          </span>
                        </p>
                      </div>
                    </div>
                    <button
                      @click="togglePixelTracking"
                      :disabled="saving"
                      :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                        pixelConfig.enabled ? 'bg-blue-600' : 'bg-gray-200'
                      ]"
                    >
                      <span
                        :class="[
                          'pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                          pixelConfig.enabled ? 'translate-x-5' : 'translate-x-0'
                        ]"
                      />
                    </button>
                  </div>
                </div>

                <!-- Pixel Configuration -->
                <div v-if="pixelConfig.enabled" class="space-y-4">
                  <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Pixel Configuration</h4>
                    <p class="text-sm text-gray-500 mb-4">
                      Configure your conversion pixel settings and get the tracking code
                    </p>
                  </div>

                  <div class="grid grid-cols-1 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">
                        Pixel URL
                      </label>
                      <div class="flex">
                        <input
                          :value="pixelConfig.pixel_url"
                          readonly
                          class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 text-sm bg-gray-50 focus:outline-none"
                        />
                        <button
                          @click="copyToClipboard(pixelConfig.pixel_url)"
                          class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md text-sm text-gray-600 hover:bg-gray-50 focus:outline-none"
                        >
                          Copy
                        </button>
                      </div>
                    </div>

                    <div v-if="pixelConfig.javascript_snippet">
                      <label class="block text-sm font-medium text-gray-700 mb-1">
                        JavaScript Tracking Code
                      </label>
                      <div class="relative">
                        <textarea
                          :value="pixelConfig.javascript_snippet"
                          readonly
                          rows="8"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-xs font-mono bg-gray-50 focus:outline-none"
                        ></textarea>
                        <button
                          @click="copyToClipboard(pixelConfig.javascript_snippet)"
                          class="absolute top-2 right-2 px-2 py-1 text-xs text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none"
                        >
                          Copy
                        </button>
                      </div>
                      <p class="mt-1 text-xs text-gray-500">
                        Add this code to your website to track conversions. The pixel will automatically track page views and you can call <code>rbTrackConversion()</code> for custom events.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Analytics Tab -->
              <div v-if="activeTab === 'analytics'" class="space-y-6">
                <div>
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Conversion Analytics</h4>
                  <p class="text-sm text-gray-500 mb-4">
                    View conversion tracking performance and statistics
                  </p>
                </div>

                <div v-if="analytics.metrics" class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                  <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                      <div class="flex items-center">
                        <div class="flex-shrink-0">
                          <ChartBarIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                          <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Conversions</dt>
                            <dd class="text-lg font-medium text-gray-900">
                              {{ analytics.metrics.total_conversions || 0 }}
                            </dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                      <div class="flex items-center">
                        <div class="flex-shrink-0">
                          <CurrencyDollarIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                          <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                            <dd class="text-lg font-medium text-gray-900">
                              ${{ (analytics.metrics.total_value || 0).toFixed(2) }}
                            </dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                      <div class="flex items-center">
                        <div class="flex-shrink-0">
                          <ChartBarIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                          <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Average Value</dt>
                            <dd class="text-lg font-medium text-gray-900">
                              ${{ (analytics.metrics.average_value || 0).toFixed(2) }}
                            </dd>
                          </dl>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <button
                  @click="loadAnalytics"
                  :disabled="loading"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                  <ArrowPathIcon class="h-4 w-4 mr-2" />
                  Refresh Analytics
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  XMarkIcon,
  CheckCircleIcon,
  XCircleIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps<{
  show: boolean
  campaign: any
}>()

const emit = defineEmits(['close', 'updated'])

// State
const activeTab = ref('sheets')
const loading = ref(false)
const saving = ref(false)

// Google Sheets configuration
const sheetsConfig = ref({
  enabled: false,
  sheet_id: null,
  sheet_url: null,
  mapping: {},
  last_sync: null,
  has_sheet: false
})

// Pixel configuration
const pixelConfig = ref({
  enabled: false,
  pixel_id: null,
  pixel_url: null,
  javascript_snippet: null,
  config: {}
})

// Analytics data
const analytics = ref({
  metrics: null,
  conversions: []
})

// Sheet mapping
const sheetMapping = ref({
  'Timestamp': 'timestamp',
  'Conversion ID': 'conversion_id',
  'Campaign ID': 'campaign_id',
  'User ID': 'user_id',
  'Session ID': 'session_id',
  'Conversion Type': 'conversion_type',
  'Conversion Value': 'conversion_value',
  'Currency': 'currency',
  'Source': 'source',
  'Medium': 'medium',
  'Device Type': 'device_type',
  'Browser': 'browser',
  'Page URL': 'page_url',
  'Referrer': 'referrer'
})

// Available fields for mapping
const availableFields = [
  { key: 'timestamp', label: 'Timestamp' },
  { key: 'conversion_id', label: 'Conversion ID' },
  { key: 'campaign_id', label: 'Campaign ID' },
  { key: 'user_id', label: 'User ID' },
  { key: 'session_id', label: 'Session ID' },
  { key: 'conversion_type', label: 'Conversion Type' },
  { key: 'conversion_value', label: 'Conversion Value' },
  { key: 'currency', label: 'Currency' },
  { key: 'source', label: 'Source' },
  { key: 'medium', label: 'Medium' },
  { key: 'channel', label: 'Channel' },
  { key: 'device_type', label: 'Device Type' },
  { key: 'browser', label: 'Browser' },
  { key: 'ip_address', label: 'IP Address' },
  { key: 'user_agent', label: 'User Agent' },
  { key: 'page_url', label: 'Page URL' },
  { key: 'referrer', label: 'Referrer' },
  { key: 'utm_source', label: 'UTM Source' },
  { key: 'utm_medium', label: 'UTM Medium' },
  { key: 'utm_campaign', label: 'UTM Campaign' },
  { key: 'utm_term', label: 'UTM Term' },
  { key: 'utm_content', label: 'UTM Content' }
]

// Load integration settings when modal opens
watch(() => props.show, (show) => {
  if (show && props.campaign) {
    loadIntegrationSettings()
  }
})

// Load integration settings
const loadIntegrationSettings = async () => {
  loading.value = true
  try {
    // Load Google Sheets status
    const sheetsResponse = await window.axios.get(`/api/campaigns/${props.campaign.id}/google-sheets/status`)
    sheetsConfig.value = sheetsResponse.data.data

    // Update sheet mapping if exists
    if (sheetsConfig.value.mapping) {
      Object.assign(sheetMapping.value, sheetsConfig.value.mapping)
    }

    // Load pixel status
    const pixelResponse = await window.axios.get(`/api/campaigns/${props.campaign.id}/conversion-pixel/status`)
    pixelConfig.value = pixelResponse.data.data

    // Load analytics
    await loadAnalytics()

  } catch (error) {
    console.error('Error loading integration settings:', error)
  } finally {
    loading.value = false
  }
}

// Toggle Google Sheets integration
const toggleSheetsIntegration = async () => {
  saving.value = true
  try {
    const response = await window.axios.post(`/api/campaigns/${props.campaign.id}/google-sheets/setup`, {
      enabled: !sheetsConfig.value.enabled,
      mapping: sheetMapping.value
    })

    sheetsConfig.value = response.data.data
    emit('updated')

  } catch (error) {
    console.error('Error toggling sheets integration:', error)

    let errorMessage = 'Failed to update Google Sheets integration'
    if (error.response && error.response.data) {
      if (error.response.data.message) {
        errorMessage = error.response.data.message
      }
      if (error.response.data.suggestion) {
        errorMessage += '\n\nSuggestion: ' + error.response.data.suggestion
      }
    }

    alert(errorMessage)
  } finally {
    saving.value = false
  }
}

// Save sheet mapping
const saveSheetMapping = async () => {
  saving.value = true
  try {
    await window.axios.post(`/api/campaigns/${props.campaign.id}/google-sheets/mapping`, {
      mapping: sheetMapping.value
    })

    sheetsConfig.value.mapping = sheetMapping.value
    alert('Sheet mapping saved successfully')

  } catch (error) {
    console.error('Error saving sheet mapping:', error)
    alert('Failed to save sheet mapping')
  } finally {
    saving.value = false
  }
}

// Sync to Google Sheets
const syncToSheets = async () => {
  saving.value = true
  try {
    const response = await window.axios.post(`/api/campaigns/${props.campaign.id}/google-sheets/sync`)
    alert(response.data.message)
    sheetsConfig.value.last_sync = new Date().toISOString()

  } catch (error) {
    console.error('Error syncing to sheets:', error)
    alert('Failed to sync to Google Sheets')
  } finally {
    saving.value = false
  }
}

// Toggle pixel tracking
const togglePixelTracking = async () => {
  saving.value = true
  try {
    const response = await window.axios.post(`/api/campaigns/${props.campaign.id}/conversion-pixel/setup`, {
      enabled: !pixelConfig.value.enabled
    })

    pixelConfig.value = response.data.data
    emit('updated')

  } catch (error) {
    console.error('Error toggling pixel tracking:', error)
    alert('Failed to update conversion pixel tracking')
  } finally {
    saving.value = false
  }
}

// Load analytics
const loadAnalytics = async () => {
  try {
    const response = await window.axios.get(`/api/campaigns/${props.campaign.id}/conversion-analytics`)
    analytics.value = response.data.data
  } catch (error) {
    console.error('Error loading analytics:', error)
  }
}

// Utility functions
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}

const openSheet = () => {
  if (sheetsConfig.value.sheet_url) {
    window.open(sheetsConfig.value.sheet_url, '_blank')
  }
}

const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    alert('Copied to clipboard!')
  } catch (error) {
    console.error('Failed to copy:', error)
    alert('Failed to copy to clipboard')
  }
}
</script>