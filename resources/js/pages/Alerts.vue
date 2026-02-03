<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('pages.alerts.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('pages.alerts.description') }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          {{ $t('pages.alerts.create_alert') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('labels.alert_type') }}</label>
          <select
            v-model="filters.type"
            @change="loadAlerts"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option value="">{{ $t('filters.all_types') }}</option>
            <option value="threshold">{{ $t('alert_types.threshold') }}</option>
            <option value="budget">{{ $t('alert_types.budget') }}</option>
            <option value="anomaly">{{ $t('alert_types.anomaly') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('labels.objective') }}</label>
          <select
            v-model="filters.objective"
            @change="loadAlerts"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option value="">{{ $t('filters.all_objectives') }}</option>
            <option value="awareness">{{ $t('objectives.awareness') }}</option>
            <option value="leads">{{ $t('objectives.leads') }}</option>
            <option value="sales">{{ $t('objectives.sales') }}</option>
            <option value="calls">{{ $t('objectives.calls') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('labels.status') }}</label>
          <select
            v-model="filters.is_active"
            @change="loadAlerts"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option value="">{{ $t('filters.all') }}</option>
            <option value="true">{{ $t('status.active') }}</option>
            <option value="false">{{ $t('status.inactive') }}</option>
          </select>
        </div>

        <div class="flex items-end">
          <button
            @click="evaluateAlerts"
            :disabled="evaluating"
            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
          >
            <ArrowPathIcon :class="['h-5 w-5 mr-2', evaluating ? 'animate-spin' : '']" />
            {{ $t('pages.alerts.test_alerts') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="alerts.length === 0" class="text-center py-12 bg-white shadow rounded-lg">
      <BellAlertIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.alerts.no_alerts') }}</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('pages.alerts.empty_description') }}
      </p>
      <div class="mt-6">
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          {{ $t('pages.alerts.create_alert') }}
        </button>
      </div>
    </div>

    <!-- Alerts List -->
    <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul role="list" class="divide-y divide-gray-200">
        <li v-for="alert in alerts" :key="alert.id" class="hover:bg-gray-50">
          <div class="px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3">
                  <h3 class="text-sm font-medium text-gray-900 truncate">
                    {{ alert.name }}
                  </h3>
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      alert.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    ]"
                  >
                    {{ alert.is_active ? 'Active' : 'Inactive' }}
                  </span>
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      getAlertTypeClass(alert.type)
                    ]"
                  >
                    {{ formatAlertType(alert.type) }}
                  </span>
                  <span
                    v-if="alert.objective"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ formatObjective(alert.objective) }}
                  </span>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <p>{{ getAlertDescription(alert) }}</p>
                </div>
                <div class="mt-2 flex items-center space-x-4 text-xs text-gray-400">
                  <span class="flex items-center">
                    <BellIcon class="h-4 w-4 mr-1" />
                    {{ alert.notification_channels.join(', ') }}
                  </span>
                  <span v-if="alert.last_triggered_at">
                    Last triggered: {{ formatDate(alert.last_triggered_at) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="toggleAlert(alert)"
                  :disabled="toggling[alert.id]"
                  class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                  :title="alert.is_active ? 'Deactivate' : 'Activate'"
                >
                  <component
                    :is="alert.is_active ? PauseIcon : PlayIcon"
                    class="h-5 w-5"
                  />
                </button>
                <button
                  @click="openEditModal(alert)"
                  class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  :title="$t('common.edit')"
                >
                  <PencilIcon class="h-5 w-5" />
                </button>
                <button
                  @click="confirmDelete(alert)"
                  class="inline-flex items-center p-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                  :title="$t('common.delete')"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Alert Modal -->
    <AlertModal
      v-if="showModal"
      :alert="selectedAlert"
      @close="closeModal"
      @saved="onAlertSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import {
  BellAlertIcon,
  BellIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  PlayIcon,
  PauseIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import AlertModal from '@/components/AlertModal.vue'

const router = useRouter()

interface Alert {
  id: number
  name: string
  type: string
  objective: string | null
  conditions: any
  notification_channels: string[]
  is_active: boolean
  last_triggered_at: string | null
  created_at: string
  updated_at: string
}

const alerts = ref<Alert[]>([])
const loading = ref(false)
const evaluating = ref(false)
const showModal = ref(false)
const selectedAlert = ref<Alert | null>(null)
const toggling = reactive<Record<number, boolean>>({})

const filters = reactive({
  type: '',
  objective: '',
  is_active: '',
})

onMounted(() => {
  loadAlerts()
})

const loadAlerts = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.type) params.append('type', filters.type)
    if (filters.objective) params.append('objective', filters.objective)
    if (filters.is_active) params.append('is_active', filters.is_active)

    const response = await window.axios.get(`/api/alerts?${params.toString()}`)
    alerts.value = response.data.data
  } catch (error: any) {
    console.error('Error loading alerts:', error)
    alert('Failed to load alerts: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const evaluateAlerts = async () => {
  evaluating.value = true
  try {
    const response = await window.axios.post('/api/alerts/evaluate')
    const results = response.data.data

    alert(
      `Alert Evaluation Complete!\n\n` +
      `Evaluated: ${results.evaluated}\n` +
      `Triggered: ${results.triggered}\n` +
      `Errors: ${results.errors}`
    )

    // Reload alerts to show updated last_triggered_at
    await loadAlerts()
  } catch (error: any) {
    console.error('Error evaluating alerts:', error)
    alert('Failed to evaluate alerts: ' + (error.response?.data?.message || error.message))
  } finally {
    evaluating.value = false
  }
}

const toggleAlert = async (alert: Alert) => {
  toggling[alert.id] = true
  try {
    const response = await window.axios.post(`/api/alerts/${alert.id}/toggle`)

    // Update the alert in the list
    const index = alerts.value.findIndex(a => a.id === alert.id)
    if (index !== -1) {
      alerts.value[index] = response.data.data
    }
  } catch (error: any) {
    console.error('Error toggling alert:', error)
    alert('Failed to toggle alert: ' + (error.response?.data?.message || error.message))
  } finally {
    toggling[alert.id] = false
  }
}

const openCreateModal = () => {
  selectedAlert.value = null
  showModal.value = true
}

const openEditModal = (alert: Alert) => {
  selectedAlert.value = alert
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedAlert.value = null
}

const onAlertSaved = () => {
  closeModal()
  loadAlerts()
}

const confirmDelete = async (alert: Alert) => {
  if (!confirm(`Are you sure you want to delete the alert "${alert.name}"?`)) {
    return
  }

  try {
    await window.axios.delete(`/api/alerts/${alert.id}`)
    await loadAlerts()
  } catch (error: any) {
    console.error('Error deleting alert:', error)
    alert('Failed to delete alert: ' + (error.response?.data?.message || error.message))
  }
}

const getAlertTypeClass = (type: string): string => {
  switch (type) {
    case 'threshold':
      return 'bg-yellow-100 text-yellow-800'
    case 'budget':
      return 'bg-red-100 text-red-800'
    case 'anomaly':
      return 'bg-purple-100 text-purple-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const formatAlertType = (type: string): string => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

const formatObjective = (objective: string): string => {
  return objective.charAt(0).toUpperCase() + objective.slice(1)
}

const getAlertDescription = (alert: Alert): string => {
  const conditions = alert.conditions

  switch (alert.type) {
    case 'threshold':
      const metric = conditions.metric?.toUpperCase() || 'Unknown'
      const operator = conditions.operator || ''
      const value = conditions.value || ''
      const period = conditions.period || ''
      return `${metric} ${operator} ${value} (${period.replace(/_/g, ' ')})`

    case 'budget':
      const budget = conditions.budget || 0
      const budgetPeriod = conditions.period || 'daily'
      const threshold = conditions.threshold || 90
      return `${budgetPeriod} budget ${budget} SAR (alert at ${threshold}%)`

    case 'anomaly':
      return 'Detect unusual patterns in performance'

    default:
      return ''
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 60) {
    return `${diffMins} min${diffMins !== 1 ? 's' : ''} ago`
  } else if (diffHours < 24) {
    return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`
  } else if (diffDays < 7) {
    return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`
  } else {
    return date.toLocaleDateString()
  }
}
</script>
