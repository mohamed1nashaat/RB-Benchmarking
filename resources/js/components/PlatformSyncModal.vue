<template>
  <TransitionRoot :show="show" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full" :class="platformBgColor">
                  <CloudArrowDownIcon class="h-6 w-6" :class="platformIconColor" />
                </div>
                <div class="mt-3 text-center sm:mt-5">
                  <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
                    Sync {{ platformName }} Metrics
                  </DialogTitle>
                  <p class="mt-2 text-sm text-gray-500">
                    Sync metrics data for all {{ platformName }} ad accounts
                  </p>
                </div>
              </div>

              <div class="mt-5 space-y-4">
                <!-- Date Range Selection -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">From Date</label>
                    <input
                      type="date"
                      v-model="startDate"
                      :disabled="syncing || syncAllTime"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:bg-gray-100"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">To Date</label>
                    <input
                      type="date"
                      v-model="endDate"
                      :disabled="syncing || syncAllTime"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:bg-gray-100"
                    />
                  </div>
                </div>

                <!-- All Time Toggle -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <span class="text-sm font-medium text-gray-700">Sync All Time</span>
                    <p class="text-xs text-gray-500">Sync all historical data (may take several minutes)</p>
                  </div>
                  <button
                    type="button"
                    @click="syncAllTime = !syncAllTime"
                    :disabled="syncing"
                    :class="[
                      syncAllTime ? 'bg-green-600' : 'bg-gray-200',
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50'
                    ]"
                  >
                    <span
                      :class="[
                        syncAllTime ? 'translate-x-5' : 'translate-x-0',
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                      ]"
                    />
                  </button>
                </div>

                <!-- Quick Mode Toggle -->
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                  <div>
                    <span class="text-sm font-medium text-gray-700">Quick Mode</span>
                    <p class="text-xs text-gray-500">Fast sync using total granularity (no daily breakdown)</p>
                  </div>
                  <button
                    type="button"
                    @click="quickMode = !quickMode"
                    :disabled="syncing"
                    :class="[
                      quickMode ? 'bg-blue-600' : 'bg-gray-200',
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50'
                    ]"
                  >
                    <span
                      :class="[
                        quickMode ? 'translate-x-5' : 'translate-x-0',
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                      ]"
                    />
                  </button>
                </div>

                <!-- Progress Display -->
                <div v-if="syncing" class="space-y-3">
                  <div class="flex justify-between text-sm text-gray-500">
                    <span>{{ syncStatus }}</span>
                    <span>{{ syncProgress }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div
                      class="h-2.5 rounded-full transition-all duration-300"
                      :class="platformProgressColor"
                      :style="{ width: syncProgress + '%' }"
                    ></div>
                  </div>

                  <!-- Sync Logs -->
                  <div class="bg-gray-900 rounded-lg p-3 max-h-48 overflow-y-auto font-mono text-xs">
                    <div v-for="(log, index) in syncLogs" :key="index" class="flex gap-2">
                      <span class="text-gray-500">{{ log.time }}</span>
                      <span :class="{
                        'text-green-400': log.type === 'success',
                        'text-yellow-400': log.type === 'warning',
                        'text-red-400': log.type === 'error',
                        'text-blue-400': log.type === 'info',
                        'text-gray-300': log.type === 'default'
                      }">{{ log.message }}</span>
                    </div>
                    <div v-if="syncing" class="flex items-center gap-2 text-gray-400">
                      <span class="animate-pulse">|</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense" v-if="!syncing">
                <button
                  @click="startSync"
                  :disabled="syncing || (!syncAllTime && (!startDate || !endDate))"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:col-start-2 sm:text-sm disabled:opacity-50"
                  :class="platformButtonColor"
                >
                  {{ syncAllTime ? 'Sync All Time' : 'Start Sync' }}
                </button>
                <button
                  @click="$emit('close')"
                  :disabled="syncing"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                >
                  Cancel
                </button>
              </div>

              <!-- Close button when syncing is complete -->
              <div v-if="syncComplete" class="mt-5">
                <button
                  @click="handleClose"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm"
                >
                  Done
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { CloudArrowDownIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps<{
  show: boolean
  platform: string
}>()

const emit = defineEmits(['close', 'success'])

// State
const startDate = ref('')
const endDate = ref('')
const syncAllTime = ref(false)
const quickMode = ref(false)
const syncing = ref(false)
const syncComplete = ref(false)
const syncProgress = ref(0)
const syncStatus = ref('')
const syncLogs = ref<{ time: string; message: string; type: string }[]>([])

// Reset state when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    // Set default date range (last 30 days)
    const today = new Date()
    const thirtyDaysAgo = new Date()
    thirtyDaysAgo.setDate(today.getDate() - 30)

    endDate.value = today.toISOString().split('T')[0]
    startDate.value = thirtyDaysAgo.toISOString().split('T')[0]

    syncing.value = false
    syncComplete.value = false
    syncProgress.value = 0
    syncStatus.value = ''
    syncLogs.value = []
    syncAllTime.value = false
    quickMode.value = false
  }
})

// Platform display name
const platformName = computed(() => {
  const names: Record<string, string> = {
    'facebook': 'Facebook Ads',
    'google': 'Google Ads',
    'google_ads': 'Google Ads',
    'snapchat': 'Snapchat Ads',
    'linkedin': 'LinkedIn Ads',
    'tiktok': 'TikTok Ads',
    'twitter': 'X/Twitter Ads'
  }
  return names[props.platform] || props.platform
})

// Platform colors
const platformBgColor = computed(() => {
  const colors: Record<string, string> = {
    'facebook': 'bg-blue-100',
    'google': 'bg-red-100',
    'google_ads': 'bg-red-100',
    'snapchat': 'bg-yellow-100',
    'linkedin': 'bg-blue-100',
    'tiktok': 'bg-gray-100',
    'twitter': 'bg-gray-100'
  }
  return colors[props.platform] || 'bg-gray-100'
})

const platformIconColor = computed(() => {
  const colors: Record<string, string> = {
    'facebook': 'text-blue-600',
    'google': 'text-red-600',
    'google_ads': 'text-red-600',
    'snapchat': 'text-yellow-500',
    'linkedin': 'text-blue-700',
    'tiktok': 'text-gray-900',
    'twitter': 'text-gray-900'
  }
  return colors[props.platform] || 'text-gray-600'
})

const platformButtonColor = computed(() => {
  const colors: Record<string, string> = {
    'facebook': 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    'google': 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    'google_ads': 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    'snapchat': 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500',
    'linkedin': 'bg-blue-700 hover:bg-blue-800 focus:ring-blue-500',
    'tiktok': 'bg-gray-800 hover:bg-gray-900 focus:ring-gray-500',
    'twitter': 'bg-gray-900 hover:bg-gray-800 focus:ring-gray-500'
  }
  return colors[props.platform] || 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500'
})

const platformProgressColor = computed(() => {
  const colors: Record<string, string> = {
    'facebook': 'bg-blue-600',
    'google': 'bg-red-600',
    'google_ads': 'bg-red-600',
    'snapchat': 'bg-yellow-500',
    'linkedin': 'bg-blue-700',
    'tiktok': 'bg-gray-800',
    'twitter': 'bg-gray-900'
  }
  return colors[props.platform] || 'bg-primary-600'
})

// Add log entry
const addLog = (message: string, type: string = 'default') => {
  const now = new Date()
  const time = now.toLocaleTimeString('en-US', { hour12: false })
  syncLogs.value.push({ time, message, type })
}

// Start sync process
const startSync = async () => {
  syncing.value = true
  syncComplete.value = false
  syncProgress.value = 0
  syncLogs.value = []

  const platformLabel = platformName.value
  addLog(`Starting ${platformLabel} metrics sync...`, 'info')
  addLog(`Mode: ${quickMode.value ? 'Quick (TOTAL granularity)' : 'Full (Daily breakdown)'}`, 'info')

  if (syncAllTime.value) {
    addLog(`Date Range: All Time`, 'info')
  } else {
    addLog(`Date Range: ${startDate.value} to ${endDate.value}`, 'info')
  }

  try {
    syncStatus.value = 'Initializing sync...'
    syncProgress.value = 10

    const payload = {
      platform: props.platform,
      start_date: syncAllTime.value ? null : startDate.value,
      end_date: syncAllTime.value ? null : endDate.value,
      full_history: syncAllTime.value,
      quick_mode: quickMode.value
    }

    addLog(`Connecting to ${platformLabel} API...`, 'info')
    syncProgress.value = 20
    syncStatus.value = 'Fetching accounts...'

    // Make the API call
    const response = await axios.post('/api/platform/sync-metrics', payload, {
      timeout: 1800000 // 30 minute timeout
    })

    // Handle response
    if (response.data.status === 'success') {
      syncProgress.value = 100
      syncStatus.value = 'Sync completed!'
      addLog(`════════════════════════════════════`, 'default')
      addLog(`SYNC COMPLETED SUCCESSFULLY`, 'success')
      addLog(`════════════════════════════════════`, 'default')
      addLog(`Accounts synced: ${response.data.accounts_synced || 0}`, 'success')
      addLog(`Total metrics: ${response.data.metrics_count || 0}`, 'success')

      if (response.data.errors && response.data.errors.length > 0) {
        addLog(`Errors: ${response.data.errors.length}`, 'warning')
        response.data.errors.forEach((err: string) => {
          addLog(`  - ${err}`, 'error')
        })
      }

      syncComplete.value = true
      emit('success')
    } else if (response.data.status === 'started') {
      // Background sync started - poll for progress
      const totalAccounts = response.data.accounts_synced || 0
      syncProgress.value = 25
      syncStatus.value = `Syncing ${totalAccounts} accounts...`
      addLog(`════════════════════════════════════`, 'default')
      addLog(`SYNC STARTED`, 'success')
      addLog(`════════════════════════════════════`, 'default')
      addLog(response.data.message || 'Sync is running on the server.', 'info')
      addLog(`Accounts to sync: ${totalAccounts}`, 'info')
      addLog(``, 'default')
      addLog(`Syncing accounts sequentially...`, 'info')

      // Poll master log for progress if available
      const masterLog = response.data.master_log
      if (masterLog && totalAccounts > 0) {
        let completedAccounts = 0
        let pollErrors = 0

        const pollInterval = setInterval(async () => {
          try {
            const logResponse = await axios.get(`/api/sync-progress/${masterLog}`)
            const content = logResponse.data.lastLine || ''

            // Check for completion
            if (content.includes('Platform Sync Complete')) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              syncStatus.value = 'Sync completed!'
              addLog(``, 'default')
              addLog(`════════════════════════════════════`, 'default')
              addLog(`ALL ACCOUNTS SYNCED`, 'success')
              addLog(`════════════════════════════════════`, 'default')
              syncComplete.value = true
              emit('success')
              return
            }

            // Check for account completion
            const completedMatch = content.match(/Completed account (\d+)\/(\d+)/)
            if (completedMatch) {
              const current = parseInt(completedMatch[1])
              if (current > completedAccounts) {
                completedAccounts = current
                const percent = Math.min(95, Math.round((completedAccounts / totalAccounts) * 100))
                syncProgress.value = percent
                syncStatus.value = `Synced ${completedAccounts} of ${totalAccounts} accounts`
                addLog(`✓ Account ${completedAccounts}/${totalAccounts} complete`, 'success')
              }
            }

            // Check for current account
            const currentMatch = content.match(/Syncing account (\d+)\/(\d+): (.+)/)
            if (currentMatch) {
              const accountName = currentMatch[3]
              syncStatus.value = `Syncing: ${accountName}`
            }

            pollErrors = 0
          } catch (err) {
            pollErrors++
            if (pollErrors >= 10) {
              clearInterval(pollInterval)
              addLog(``, 'default')
              addLog(`Lost connection to sync progress`, 'warning')
              addLog(`Sync may still be running on the server`, 'info')
              syncComplete.value = true
            }
          }
        }, 3000)

        // Stop polling after 60 minutes max
        setTimeout(() => {
          clearInterval(pollInterval)
          if (!syncComplete.value) {
            addLog(``, 'default')
            addLog(`Progress tracking timed out`, 'warning')
            addLog(`Sync may still be running on the server`, 'info')
            syncComplete.value = true
          }
        }, 3600000)
      } else {
        // No master log, just show completion
        addLog(``, 'default')
        addLog(`You can close this modal and continue working.`, 'info')
        addLog(`Metrics will appear after the sync completes.`, 'info')
        syncComplete.value = true
      }
    } else {
      addLog(`Sync completed with issues: ${response.data.message}`, 'warning')
      syncComplete.value = true
    }
  } catch (error: any) {
    console.error('Sync error:', error)
    syncProgress.value = 100
    syncStatus.value = 'Sync failed'
    addLog(`════════════════════════════════════`, 'default')
    addLog(`SYNC FAILED`, 'error')
    addLog(`════════════════════════════════════`, 'default')

    // Handle specific error types
    if (error.code === 'ECONNABORTED') {
      addLog(`Request timed out after 30 minutes.`, 'error')
      addLog(`Try a smaller date range or use Quick Mode.`, 'warning')
    } else if (error.response?.status === 401) {
      addLog(`Authentication failed. Please log in again.`, 'error')
    } else if (error.response?.status === 403) {
      addLog(`You don't have permission to sync this platform.`, 'error')
    } else if (error.response?.status === 404) {
      addLog(`No active ${platformName.value} integrations found.`, 'error')
      addLog(`Please connect the platform first.`, 'warning')
    } else if (error.response?.status === 500) {
      addLog(`Server error occurred during sync.`, 'error')
      addLog(`Error: ${error.response?.data?.message || 'Unknown server error'}`, 'error')
    } else if (error.message === 'Network Error') {
      addLog(`Network error. Please check your connection.`, 'error')
    } else {
      addLog(`Error: ${error.response?.data?.message || error.message}`, 'error')
    }

    // Show any errors from the response
    if (error.response?.data?.errors && error.response.data.errors.length > 0) {
      addLog(``, 'default')
      addLog(`Account errors:`, 'warning')
      error.response.data.errors.forEach((err: string) => {
        addLog(`  - ${err}`, 'error')
      })
    }

    syncComplete.value = true
  } finally {
    syncing.value = false
  }
}

const handleClose = () => {
  emit('close')
}
</script>
