<template>
  <div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <button
          @click="goBack"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-2" />
          {{ $t('common.back') }}
        </button>

        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ campaign?.name || $t('pages.campaign_metrics.title') }}</h1>
          <div class="flex items-center space-x-2 mt-1">
            <span class="text-sm text-gray-500">{{ adAccount?.account_name }}</span>
            <span v-if="campaign" :class="[
              'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
              campaign.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
            ]">
              {{ campaign.status }}
            </span>
            <span v-if="campaign?.funnel_stage" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
              {{ campaign.funnel_stage }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Date Filter -->
        <DateRangePicker
          :value="dateRangeValue"
          @change="onDateRangeChange"
        />

        <button
          @click="loadMetrics"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" />
          {{ $t('dashboard.refresh') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <span class="ml-3 text-gray-600">{{ $t('pages.campaign_metrics.loading_metrics') }}</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">{{ $t('pages.campaign_metrics.error_loading_metrics') }}</h3>
          <p class="mt-1 text-sm text-red-600">{{ error }}</p>
          <button @click="loadMetrics" class="mt-2 text-sm bg-red-100 px-3 py-1 rounded-md text-red-800 hover:bg-red-200">
            {{ $t('common.try_again') }}
          </button>
        </div>
      </div>
    </div>

    <!-- No Data State -->
    <div v-else-if="!hasMetricsData" class="bg-white shadow-sm rounded-lg p-8 text-center border border-gray-200">
      <ChartBarIcon class="mx-auto h-16 w-16 text-gray-300 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No Metrics Data Available</h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
        This campaign doesn't have any performance metrics recorded yet.
      </p>
      <button
        @click="goBack"
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
      >
        <ArrowLeftIcon class="h-4 w-4 mr-2" />
        Back to Account
      </button>
    </div>

    <!-- Metrics Content -->
    <div v-else class="space-y-6">
      <!-- Active Date Filter Banner -->
      <div v-if="filterStartDate && filterEndDate" class="bg-blue-50 border border-blue-200 rounded-md p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <CalendarIcon class="h-5 w-5 text-blue-500 mr-2" />
            <p class="text-sm font-medium text-blue-800">
              Showing data from <span class="font-bold">{{ filterStartDate }}</span> to <span class="font-bold">{{ filterEndDate }}</span>
            </p>
          </div>
          <button
            @click="clearDateFilter"
            class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200"
          >
            <XMarkIcon class="h-3 w-3 mr-1" />
            Clear Filter
          </button>
        </div>
      </div>

      <!-- Hero Metric Cards - White Design System -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Spend Card -->
        <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500">Total Spend</p>
            <div class="p-2 bg-primary-50 rounded-lg">
              <CurrencyDollarIcon class="h-5 w-5 text-primary-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ formatCompactCurrency(metrics.spend || 0) }}</p>
          <p class="text-xs text-gray-500 mt-1">
            <span v-if="filterStartDate && filterEndDate">{{ filterStartDate }} - {{ filterEndDate }}</span>
            <span v-else>All time</span>
          </p>
        </div>

        <!-- Impressions Card -->
        <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500">Impressions</p>
            <div class="p-2 bg-emerald-50 rounded-lg">
              <EyeIcon class="h-5 w-5 text-emerald-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ formatCompactNumber(metrics.impressions || 0) }}</p>
          <p class="text-xs text-gray-500 mt-1">Total views</p>
        </div>

        <!-- Clicks/Leads Card -->
        <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500">{{ campaign?.objective === 'leads' ? 'Leads' : 'Clicks' }}</p>
            <div class="p-2 bg-amber-50 rounded-lg">
              <CursorArrowRaysIcon class="h-5 w-5 text-amber-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ formatCompactNumber(campaign?.objective === 'leads' ? (metrics.leads || 0) : (metrics.clicks || 0)) }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ campaign?.objective === 'leads' ? 'Generated' : 'Total clicks' }}</p>
        </div>

        <!-- CTR/CPL Card -->
        <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500">{{ campaign?.objective === 'leads' ? 'Cost/Lead' : 'CTR' }}</p>
            <div class="p-2 bg-gray-100 rounded-lg">
              <ChartBarIcon class="h-5 w-5 text-gray-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">
            <template v-if="campaign?.objective === 'leads'">
              {{ metrics.leads > 0 ? formatCompactCurrency(metrics.spend / metrics.leads) : '﷼0' }}
            </template>
            <template v-else>
              {{ (metrics.ctr || 0).toFixed(2) }}%
            </template>
          </p>
          <p class="text-xs text-gray-500 mt-1">{{ campaign?.objective === 'leads' ? 'Per lead' : 'Click-through rate' }}</p>
        </div>
      </div>

      <!-- Performance Metrics Grid -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-if="metrics.cpc" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">CPC</p>
            <p class="text-xl font-semibold text-gray-900">﷼{{ (metrics.cpc || 0).toFixed(2) }}</p>
          </div>
          <div v-if="metrics.cpm" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">CPM</p>
            <p class="text-xl font-semibold text-gray-900">﷼{{ (metrics.cpm || 0).toFixed(2) }}</p>
          </div>
          <div v-if="metrics.reach" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">Reach</p>
            <p class="text-xl font-semibold text-gray-900">{{ formatCompactNumber(metrics.reach || 0) }}</p>
          </div>
          <div v-if="metrics.conversions !== undefined" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">Conversions</p>
            <p class="text-xl font-semibold text-gray-900">{{ (metrics.conversions || 0).toLocaleString() }}</p>
          </div>
          <div v-if="metrics.roas" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">ROAS</p>
            <p class="text-xl font-semibold text-gray-900">{{ (metrics.roas || 0).toFixed(2) }}x</p>
          </div>
          <div v-if="metrics.cvr" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">CVR</p>
            <p class="text-xl font-semibold text-gray-900">{{ (metrics.cvr || 0).toFixed(2) }}%</p>
          </div>
          <div v-if="metrics.frequency" class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">Frequency</p>
            <p class="text-xl font-semibold text-gray-900">{{ (metrics.frequency || 0).toFixed(1) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">Objective</p>
            <p class="text-xl font-semibold text-gray-900 capitalize">{{ campaign?.objective || 'N/A' }}</p>
          </div>
        </div>
      </div>

      <!-- Campaign Details Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Category</label>
            <select
              v-model="editableCampaign.sub_industry"
              @change="updateCampaignDetails"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="">Select category</option>
              <option v-for="category in availableSubIndustries" :key="category" :value="category">
                {{ category }}
              </option>
            </select>
          </div>

          <!-- Objective -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Objective</label>
            <p class="text-sm text-gray-900 py-2 capitalize">{{ formatObjective(campaign?.objective) }}</p>
          </div>

          <!-- Funnel Stage -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Funnel Stage</label>
            <p class="text-sm text-gray-900 py-2">
              <span v-if="campaign?.funnel_stage" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                {{ campaign.funnel_stage }}
              </span>
              <span v-else class="text-gray-400">Not set</span>
            </p>
          </div>

          <!-- Platform -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Platform</label>
            <p class="text-sm text-gray-900 py-2 capitalize">{{ adAccount?.platform || 'Unknown' }}</p>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
            <p class="text-sm py-2">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                campaign?.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ campaign?.status || 'Unknown' }}
              </span>
            </p>
          </div>

          <!-- Created -->
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Created</label>
            <p class="text-sm text-gray-900 py-2">{{ formatDate(campaign?.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Integration Modal -->
      <CampaignIntegrationModal
        :show="showIntegrationModal"
        :campaign="campaign"
        @close="showIntegrationModal = false"
        @updated="loadMetrics"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  ChartBarIcon,
  CursorArrowRaysIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  CalendarIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import CampaignIntegrationModal from '@/components/CampaignIntegrationModal.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const campaignId = computed(() => route.params.campaignId)
const accountId = computed(() => route.query.accountId || route.params.accountId)

const FILTERS_STORAGE_KEY = 'campaign_metrics_filters'

const getSavedDateRange = () => {
  try {
    const saved = localStorage.getItem(FILTERS_STORAGE_KEY)
    if (saved) {
      const parsed = JSON.parse(saved)
      return {
        from: parsed.from || null,
        to: parsed.to || null
      }
    }
  } catch (e) {}
  return { from: null, to: null }
}

// State
const loading = ref(true)
const error = ref('')
const campaign = ref<any>(null)
const adAccount = ref<any>(null)
const metrics = ref<any>({})
const dailyData = ref<any[]>([])
const availableSubIndustries = ref<string[]>([])
const showIntegrationModal = ref(false)

// Date filter state
const savedRange = getSavedDateRange()
const filterStartDate = ref(savedRange.from || '')
const filterEndDate = ref(savedRange.to || '')

// Computed for DateRangePicker
const dateRangeValue = computed(() => {
  if (filterStartDate.value && filterEndDate.value) {
    return { from: filterStartDate.value, to: filterEndDate.value }
  }
  return undefined
})

// Handle date range change from picker
const onDateRangeChange = (range: { from: string; to: string }) => {
  filterStartDate.value = range.from
  filterEndDate.value = range.to
  saveFilters()
  loadMetrics()
}

// Clear date filter
const clearDateFilter = () => {
  filterStartDate.value = ''
  filterEndDate.value = ''
  saveFilters()
  loadMetrics()
}

const editableCampaign = ref({
  sub_industry: ''
})

const hasMetricsData = computed(() => {
  const m = metrics.value
  if (!m || Object.keys(m).length === 0) return false
  return (m.spend || 0) > 0 || (m.impressions || 0) > 0 || (m.clicks || 0) > 0
})

// Format helpers
const formatCompactNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toLocaleString()
}

const formatCompactCurrency = (num: number) => {
  if (num >= 1000000) return '﷼' + (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return '﷼' + (num / 1000).toFixed(1) + 'K'
  return '﷼' + num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const formatObjective = (objective: string) => {
  if (!objective) return 'Not set'
  return objective.replace(/_/g, ' ')
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

// Load data
const loadMetrics = async () => {
  loading.value = true
  error.value = ''

  try {
    if (!campaign.value) {
      try {
        const campaignResponse = await window.axios.get(`/api/ad-campaigns/${campaignId.value}`)
        campaign.value = campaignResponse.data.data
        editableCampaign.value = { sub_industry: campaign.value.sub_industry || '' }
      } catch (e) {}
    }

    if (!adAccount.value && accountId.value) {
      try {
        const accountResponse = await window.axios.get(`/api/ad-accounts/${accountId.value}`)
        adAccount.value = accountResponse.data.data
      } catch (e) {}
    }

    // Build params with date filter
    const params: any = {}
    if (filterStartDate.value && filterEndDate.value) {
      params.start_date = filterStartDate.value
      params.end_date = filterEndDate.value
    }

    const metricsResponse = await window.axios.get(`/api/ad-campaigns/${campaignId.value}/metrics`, {
      params
    })

    metrics.value = metricsResponse.data.data || {}
    dailyData.value = metricsResponse.data.daily_data || []

  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Error loading metrics'
    metrics.value = {}
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  if (accountId.value) {
    router.push(`/ad-accounts/${accountId.value}`)
  } else {
    router.back()
  }
}

const loadSubIndustries = async () => {
  try {
    const industry = adAccount.value?.industry
    if (!industry) {
      availableSubIndustries.value = []
      return
    }

    // Fetch categories from industry management API
    const response = await window.axios.get('/api/industries')
    const industries = response.data.data || []

    // Find matching industry and get its categories
    const matchingIndustry = industries.find((ind: any) =>
      ind.name === industry || ind.display_name === industry
    )

    if (matchingIndustry && matchingIndustry.sub_industries) {
      availableSubIndustries.value = matchingIndustry.sub_industries
        .filter((cat: any) => cat.is_active)
        .map((cat: any) => cat.display_name)
    } else {
      availableSubIndustries.value = []
    }
  } catch (e) {
    console.error('Error loading categories from industry management:', e)
    availableSubIndustries.value = []
  }
}

const updateCampaignDetails = async () => {
  try {
    await window.axios.put(`/api/ad-campaigns/${campaignId.value}`, {
      sub_industry: editableCampaign.value.sub_industry
    })
    if (campaign.value) {
      campaign.value.sub_industry = editableCampaign.value.sub_industry
    }
  } catch (e) {
    alert('Failed to update campaign')
  }
}

const saveFilters = () => {
  try {
    localStorage.setItem(FILTERS_STORAGE_KEY, JSON.stringify({
      from: filterStartDate.value,
      to: filterEndDate.value
    }))
  } catch (e) {}
}

onMounted(async () => {
  await loadMetrics()
  await loadSubIndustries()
})
</script>
