<template>
  <div class="bg-white shadow overflow-hidden sm:rounded-md">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
      <h3 class="text-lg leading-6 font-medium text-gray-900">
        Overall Spend Breakdown
      </h3>
      <p class="mt-1 max-w-2xl text-sm text-gray-500">
        Spend breakdown by account, platform, and time period
      </p>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
    
    <div v-else-if="spendData.length === 0" class="text-center py-12">
      <div class="text-gray-500">
        <CurrencyDollarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No spend data available</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or date range.</p>
      </div>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Account / Campaign
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Platform
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Detected Objective
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Currency
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total Spend
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Daily Average
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Results
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Cost per Result
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(item, index) in spendData"
            :key="index"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-8 w-8">
                  <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <component :is="getPlatformIcon(item.platform)" class="h-4 w-4" />
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ item.account_name }}
                  </div>
                  <div v-if="item.campaign_name" class="text-sm text-gray-500">
                    {{ item.campaign_name }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                    :class="getPlatformBadgeClass(item.platform)">
                {{ item.platform }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div v-if="item.campaign_name" class="text-sm">
                <span v-if="getDetectedObjective(item.campaign_name)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getObjectiveConfidenceClass(item.campaign_name)">
                  {{ formatObjective(getDetectedObjective(item.campaign_name)) }}
                  <span class="ml-1 text-xs opacity-75">
                    ({{ getDetectionConfidence(item.campaign_name) }})
                  </span>
                </span>
                <span v-else class="text-xs text-gray-400">
                  Not detected
                </span>
              </div>
              <span v-else class="text-xs text-gray-400">-</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <span class="font-medium">SAR</span>
              <span v-if="item.original_currency && item.original_currency !== 'SAR'" class="text-xs text-gray-500 ml-1">({{ item.original_currency }})</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
              {{ formatCurrency(item.total_spend, 'SAR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
              {{ formatCurrency(item.daily_average, 'SAR') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
              {{ formatNumber(item.results) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
              {{ formatCurrency(item.cost_per_result, 'SAR') }}
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Summary Row -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div class="text-sm font-medium text-gray-900">
            Total Spend Across All Accounts
          </div>
          <div class="text-lg font-semibold text-gray-900">
            {{ getTotalSpendSummary() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CurrencyDollarIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'
import { detectCampaignObjectiveWithConfidence } from '@/utils/objectiveDetection'

interface SpendDataItem {
  account_name: string
  campaign_name?: string
  platform: string
  currency: string
  total_spend: number
  daily_average: number
  results: number
  cost_per_result: number
}

interface Props {
  spendData: SpendDataItem[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const dashboardStore = useDashboardStore()

const getPlatformIcon = (platform: string) => {
  // Return appropriate icon component based on platform
  return ChartBarIcon
}

const getPlatformBadgeClass = (platform: string) => {
  switch (platform.toLowerCase()) {
    case 'facebook':
      return 'bg-blue-100 text-blue-800'
    case 'google':
      return 'bg-green-100 text-green-800'
    case 'tiktok':
      return 'bg-pink-100 text-pink-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

// Auto-detection helper functions
const getDetectedObjective = (campaignName: string) => {
  if (!campaignName) return null
  const detection = detectCampaignObjectiveWithConfidence(campaignName)
  return detection.objective
}

const getDetectionConfidence = (campaignName: string) => {
  if (!campaignName) return 'none'
  const detection = detectCampaignObjectiveWithConfidence(campaignName)
  return detection.confidence
}

const getObjectiveConfidenceClass = (campaignName: string) => {
  const confidence = getDetectionConfidence(campaignName)
  switch (confidence) {
    case 'high':
      return 'bg-primary-100 text-primary-800'
    case 'medium':
      return 'bg-secondary-100 text-secondary-800'
    case 'low':
      return 'bg-gray-100 text-gray-600'
    default:
      return 'bg-gray-50 text-gray-400'
  }
}

const formatObjective = (objective: string | null) => {
  if (!objective) return ''
  return objective.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatCurrency = (value: number | null, currency: string = 'USD'): string => {
  if (value === null || value === undefined) return 'N/A'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const formatNumber = (value: number | null): string => {
  if (value === null || value === undefined) return 'N/A'
  return value.toLocaleString()
}

const getTotalSpendSummary = (): string => {
  if (!props.spendData.length) return 'N/A'
  
  // Group by currency and sum
  const totalsByCurrency = props.spendData.reduce((acc, item) => {
    if (!acc[item.currency]) {
      acc[item.currency] = 0
    }
    acc[item.currency] += item.total_spend
    return acc
  }, {} as Record<string, number>)
  
  // Format each currency total
  const formattedTotals = Object.entries(totalsByCurrency).map(([currency, total]) => 
    formatCurrency(total, currency)
  )
  
  return formattedTotals.join(' + ')
}
</script>