<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeIfNotSyncing"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <!-- Modal Panel -->
      <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
        <div>
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full" :class="syncTypeIcon.bgColor">
            <component :is="syncTypeIcon.icon" class="h-6 w-6" :class="syncTypeIcon.textColor" />
          </div>
          <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              {{ modalTitle }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ modalDescription }}
            </p>
          </div>
        </div>

        <!-- Sync Options (only show if not syncing) -->
        <div v-if="!syncing" class="mt-5 space-y-4">
          <!-- Date Range (for metrics sync) -->
          <template v-if="syncType === 'metrics' || syncType === 'all'">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">From Date</label>
                <input
                  type="date"
                  v-model="startDate"
                  :disabled="syncAllTime"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:bg-gray-100"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">To Date</label>
                <input
                  type="date"
                  v-model="endDate"
                  :disabled="syncAllTime"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:bg-gray-100"
                />
              </div>
            </div>

            <!-- Quick Date Buttons -->
            <div class="flex flex-wrap gap-2">
              <button @click="setDateRange('last7')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 7 days</button>
              <button @click="setDateRange('last30')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 30 days</button>
              <button @click="setDateRange('last90')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 90 days</button>
              <button @click="setDateRange('thisMonth')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">This month</button>
              <button @click="setDateRange('lastMonth')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last month</button>
              <button @click="setDateRange('thisYear')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">This year</button>
              <button
                @click="toggleAllTime"
                :class="['px-2 py-1 text-xs rounded font-medium', syncAllTime ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200']"
              >
                All Time
              </button>
            </div>

            <p v-if="syncAllTime" class="text-xs text-amber-600 mt-2">
              <span v-if="platform === 'facebook'">Facebook limits historical data to 37 months.</span>
              <span v-else>Syncing all available historical data. This may take several minutes.</span>
            </p>
          </template>
        </div>

        <!-- Progress Section -->
        <div v-if="syncing || logs.length > 0" class="mt-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium" :class="syncing ? 'text-green-700' : 'text-gray-700'">
              {{ syncing ? 'Syncing...' : 'Sync completed' }}
            </span>
            <span class="text-sm text-gray-500">{{ elapsedTime }}</span>
          </div>

          <!-- Progress Bar -->
          <div v-if="syncing" class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden mb-3">
            <div
              class="h-full bg-gradient-to-r from-green-400 via-green-500 to-green-600 rounded-full relative overflow-hidden transition-all duration-300"
              :style="{ width: progress + '%' }"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
            </div>
          </div>

          <!-- Log View -->
          <div ref="logContainer" class="bg-gray-900 rounded-lg p-3 max-h-48 overflow-y-auto font-mono text-xs">
            <div v-for="(log, index) in logs" :key="index" class="flex items-start gap-2 mb-1">
              <span class="text-gray-500 shrink-0">{{ log.time }}</span>
              <span :class="{
                'text-green-400': log.type === 'success',
                'text-yellow-400': log.type === 'warning',
                'text-red-400': log.type === 'error',
                'text-blue-400': log.type === 'info',
                'text-gray-300': log.type === 'default'
              }">{{ log.message }}</span>
            </div>
            <div v-if="syncing" class="flex items-center gap-2 text-gray-400">
              <span class="animate-pulse">▌</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense" v-if="!syncing">
          <button
            @click="startSync"
            :disabled="!canStartSync"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ startButtonText }}
          </button>
          <button
            @click="close"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:col-start-1 sm:text-sm"
          >
            {{ logs.length > 0 && !syncing ? 'Close' : 'Cancel' }}
          </button>
        </div>

        <!-- Close button when syncing -->
        <div v-else class="mt-5 text-center">
          <p class="text-sm text-gray-500">Sync in progress. Please wait...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { CloudArrowDownIcon, ArrowPathIcon, FolderIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

interface Account {
  id: number
  name: string
  external_account_id?: string
  integration_id: number
  platform?: string
}

interface LogEntry {
  time: string
  message: string
  type: 'success' | 'warning' | 'error' | 'info' | 'default'
}

const props = defineProps<{
  show: boolean
  account: Account | null
  syncType: 'all' | 'campaigns' | 'metrics'
  platform: string
}>()

const emit = defineEmits(['close', 'synced'])

// State
const syncing = ref(false)
const progress = ref(0)
const logs = ref<LogEntry[]>([])
const startDate = ref('')
const endDate = ref('')
const syncAllTime = ref(false)
const elapsedTime = ref('0:00')
const logContainer = ref<HTMLElement | null>(null)

let syncTimer: ReturnType<typeof setInterval> | null = null
let startTime = 0

// Computed
const syncTypeIcon = computed(() => {
  switch (props.syncType) {
    case 'campaigns':
      return { icon: FolderIcon, bgColor: 'bg-blue-100', textColor: 'text-blue-600' }
    case 'metrics':
      return { icon: CloudArrowDownIcon, bgColor: 'bg-green-100', textColor: 'text-green-600' }
    default:
      return { icon: ArrowPathIcon, bgColor: 'bg-purple-100', textColor: 'text-purple-600' }
  }
})

const modalTitle = computed(() => {
  const name = props.account?.name || 'Account'
  switch (props.syncType) {
    case 'campaigns':
      return `Sync Campaigns - ${name}`
    case 'metrics':
      return `Sync Metrics - ${name}`
    default:
      return `Sync All Data - ${name}`
  }
})

const modalDescription = computed(() => {
  const platformsWithCampaignSync = ['facebook', 'google', 'snapchat', 'linkedin']
  const hasCampaignSync = platformsWithCampaignSync.includes(props.platform)

  switch (props.syncType) {
    case 'campaigns':
      if (!hasCampaignSync) {
        return `Campaign sync is not supported for ${props.platform}. Try syncing metrics instead.`
      }
      return 'Sync campaign structure from the platform API.'
    case 'metrics':
      return 'Select a date range to sync metrics data.'
    default:
      return hasCampaignSync
        ? 'Sync campaigns and metrics data from the platform.'
        : 'Sync metrics data from the platform.'
  }
})

const canStartSync = computed(() => {
  const platformsWithCampaignSync = ['facebook', 'google', 'snapchat', 'linkedin']
  const hasCampaignSync = platformsWithCampaignSync.includes(props.platform)

  // Disable if trying to sync campaigns on unsupported platform
  if (props.syncType === 'campaigns' && !hasCampaignSync) return false

  if (props.syncType === 'campaigns') return true
  if (syncAllTime.value) return true
  return startDate.value && endDate.value
})

const startButtonText = computed(() => {
  if (props.syncType === 'campaigns') return 'Start Sync'
  if (syncAllTime.value) return 'Sync All Time'
  return 'Start Sync'
})

// Methods
const setDateRange = (range: string) => {
  const now = new Date()
  const today = now.toISOString().split('T')[0]

  switch (range) {
    case 'last7':
      startDate.value = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      endDate.value = today
      syncAllTime.value = false
      break
    case 'last30':
      startDate.value = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      endDate.value = today
      syncAllTime.value = false
      break
    case 'last90':
      startDate.value = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      endDate.value = today
      syncAllTime.value = false
      break
    case 'thisMonth':
      startDate.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
      endDate.value = today
      syncAllTime.value = false
      break
    case 'lastMonth':
      const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      const lastDayLastMonth = new Date(now.getFullYear(), now.getMonth(), 0)
      startDate.value = lastMonth.toISOString().split('T')[0]
      endDate.value = lastDayLastMonth.toISOString().split('T')[0]
      syncAllTime.value = false
      break
    case 'thisYear':
      startDate.value = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0]
      endDate.value = today
      syncAllTime.value = false
      break
  }
}

const toggleAllTime = () => {
  syncAllTime.value = !syncAllTime.value
}

const addLog = (message: string, type: LogEntry['type'] = 'default') => {
  const now = new Date()
  const time = now.toLocaleTimeString('en-US', { hour12: false })
  logs.value.push({ time, message, type })

  // Auto-scroll to bottom
  nextTick(() => {
    if (logContainer.value) {
      logContainer.value.scrollTop = logContainer.value.scrollHeight
    }
  })
}

const updateElapsedTime = () => {
  const elapsed = Math.floor((Date.now() - startTime) / 1000)
  const minutes = Math.floor(elapsed / 60)
  const seconds = elapsed % 60
  elapsedTime.value = `${minutes}:${seconds.toString().padStart(2, '0')}`
}

const startSync = async () => {
  if (!props.account) return

  syncing.value = true
  progress.value = 0
  logs.value = []
  startTime = Date.now()
  elapsedTime.value = '0:00'

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const integrationId = props.account.integration_id
    const accountId = props.account.id

    addLog('═══════════════════════════════════════', 'default')
    addLog(`Starting ${props.syncType === 'all' ? 'Full' : props.syncType.charAt(0).toUpperCase() + props.syncType.slice(1)} Sync`, 'info')
    addLog('═══════════════════════════════════════', 'default')
    addLog(`Account: ${props.account.name}`, 'default')

    if (props.syncType === 'metrics' || props.syncType === 'all') {
      const dateRange = syncAllTime.value ? 'All Time' : `${startDate.value} to ${endDate.value}`
      addLog(`Date Range: ${dateRange}`, 'default')
    }
    addLog('───────────────────────────────────────', 'default')

    progress.value = 5

    // Step 1: Sync campaigns (for 'all' or 'campaigns' type)
    if (props.syncType === 'all' || props.syncType === 'campaigns') {
      addLog('Syncing campaigns...', 'info')
      progress.value = 10

      try {
        const campaignResponse = await axios.post(`/api/integrations/${integrationId}/sync-campaigns`, {
          ad_account_id: accountId
        })

        const count = campaignResponse.data.data?.length || campaignResponse.data.synced || campaignResponse.data.campaigns_count || 0
        addLog(`✓ Campaigns synced: ${count} items`, 'success')
        progress.value = 30
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || error.message
        if (props.syncType === 'campaigns') {
          // If only syncing campaigns and it fails, treat as error
          addLog(`✗ ${errorMsg}`, 'error')
          throw new Error(errorMsg)
        } else {
          // If syncing all, just warn and continue with metrics
          addLog(`⚠ Campaigns: ${errorMsg}`, 'warning')
        }
      }
    }

    // Step 2: Sync metrics (for 'all' or 'metrics' type)
    if (props.syncType === 'all' || props.syncType === 'metrics') {
      addLog('───────────────────────────────────────', 'default')
      addLog('Syncing metrics...', 'info')
      progress.value = 40

      const metricsPayload: any = {
        ad_account_id: accountId,
        background: syncAllTime.value
      }

      if (syncAllTime.value) {
        metricsPayload.all_time = true
        addLog('Running in background mode (may take several minutes)...', 'warning')
      } else {
        metricsPayload.start_date = startDate.value
        metricsPayload.end_date = endDate.value
      }

      const metricsResponse = await axios.post(`/api/integrations/${integrationId}/sync-metrics`, metricsPayload)

      // Handle background sync with progress polling
      if (syncAllTime.value && metricsResponse.data.log_file) {
        addLog('✓ Sync started', 'success')
        addLog('Tracking progress...', 'info')
        progress.value = 5

        await pollProgress(metricsResponse.data.log_file)
      } else {
        // Foreground sync completed
        progress.value = 100
        addLog('───────────────────────────────────────', 'default')
        addLog('✓ SYNC COMPLETE', 'success')

        const stats = metricsResponse.data.data
        if (stats) {
          if (stats.metrics_synced !== undefined) {
            addLog(`Metrics synced: ${stats.metrics_synced}`, 'success')
          }
          if (stats.campaigns_processed !== undefined) {
            addLog(`Campaigns processed: ${stats.campaigns_processed}`, 'success')
          }
        }
      }
    } else {
      // Only campaigns sync
      progress.value = 100
      addLog('───────────────────────────────────────', 'default')
      addLog('✓ SYNC COMPLETE', 'success')
    }

    emit('synced')

  } catch (error: any) {
    console.error('Sync error:', error)
    addLog(`✗ Error: ${error.response?.data?.message || error.message}`, 'error')
    progress.value = 0
  } finally {
    syncing.value = false
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
  }
}

const pollProgress = async (logFile: string) => {
  let lastMonth = ''
  let pollErrors = 0

  return new Promise<void>((resolve) => {
    const pollInterval = setInterval(async () => {
      try {
        const response = await axios.get(`/api/sync-progress/${logFile}`)
        const data = response.data

        progress.value = data.percent

        if (data.currentMonth && data.currentMonth !== lastMonth) {
          addLog(`Processing ${data.currentMonth}...`, 'info')
          lastMonth = data.currentMonth
        }

        if (data.complete) {
          clearInterval(pollInterval)
          progress.value = 100
          addLog('───────────────────────────────────────', 'default')
          addLog('✓ SYNC COMPLETED', 'success')
          addLog(`Total Created: ${data.created || 0}`, 'success')
          addLog(`Total Updated: ${data.updated || 0}`, 'success')
          syncing.value = false
          if (syncTimer) {
            clearInterval(syncTimer)
            syncTimer = null
          }
          resolve()
        }

        pollErrors = 0
      } catch (err: any) {
        console.error('Poll error:', err)
        pollErrors++
        if (pollErrors >= 10) {
          clearInterval(pollInterval)
          addLog('Lost connection to sync progress.', 'warning')
          syncing.value = false
          if (syncTimer) {
            clearInterval(syncTimer)
            syncTimer = null
          }
          resolve()
        }
      }
    }, 2000)
  })
}

const close = () => {
  if (!syncing.value) {
    logs.value = []
    progress.value = 0
    syncAllTime.value = false
    emit('close')
  }
}

const closeIfNotSyncing = () => {
  if (!syncing.value) {
    close()
  }
}

// Initialize with last 30 days
watch(() => props.show, (newVal) => {
  if (newVal) {
    setDateRange('last30')
    logs.value = []
    progress.value = 0
    syncing.value = false
  }
})
</script>

<style scoped>
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.animate-shimmer {
  animation: shimmer 1.5s infinite;
}
</style>
