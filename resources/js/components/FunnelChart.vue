<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Marketing Funnel
        </h3>
        <div class="text-sm text-gray-500">
          {{ formatDateRange(dateRange) }}
        </div>
      </div>
      
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
      
      <div v-else-if="!data.impressions" class="flex justify-center items-center h-64 text-gray-500">
        No data available
      </div>
      
      <div v-else class="space-y-4">
        <!-- Impressions -->
        <div class="relative">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <EyeIcon class="h-5 w-5 text-blue-500" />
              <span class="text-sm font-medium text-gray-900">Impressions</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">
              {{ formatNumber(data.impressions) }}
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-8">
            <div class="bg-primary-500 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" 
                 :style="{ width: '100%' }">
              100%
            </div>
          </div>
        </div>

        <!-- Arrow Down -->
        <div class="flex justify-center">
          <ChevronDownIcon class="h-5 w-5 text-gray-400" />
        </div>

        <!-- Clicks -->
        <div class="relative">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <CursorArrowRaysIcon class="h-5 w-5 text-green-500" />
              <span class="text-sm font-medium text-gray-900">Clicks</span>
            </div>
            <div class="text-right">
              <span class="text-sm font-semibold text-gray-900">
                {{ formatNumber(data.clicks) }}
              </span>
              <span class="text-xs text-gray-500 ml-1">
                ({{ formatPercentage(clickRate) }})
              </span>
            </div>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-8">
            <div class="bg-green-500 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" 
                 :style="{ width: clickRate + '%' }">
              {{ formatPercentage(clickRate) }}
            </div>
          </div>
        </div>

        <!-- Arrow Down -->
        <div class="flex justify-center">
          <ChevronDownIcon class="h-5 w-5 text-gray-400" />
        </div>

        <!-- Leads -->
        <div class="relative">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2">
              <UserGroupIcon class="h-5 w-5 text-purple-500" />
              <span class="text-sm font-medium text-gray-900">Leads</span>
            </div>
            <div class="text-right">
              <span class="text-sm font-semibold text-gray-900">
                {{ formatNumber(data.leads) }}
              </span>
              <span class="text-xs text-gray-500 ml-1">
                ({{ formatPercentage(leadRate) }})
              </span>
            </div>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-8">
            <div class="bg-purple-500 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" 
                 :style="{ width: leadRate + '%' }">
              {{ formatPercentage(leadRate) }}
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="mt-6 grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900">{{ formatPercentage(ctr) }}</div>
            <div class="text-sm text-gray-500">Click-Through Rate</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-900">{{ formatPercentage(cvr) }}</div>
            <div class="text-sm text-gray-500">Conversion Rate</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  EyeIcon,
  CursorArrowRaysIcon,
  UserGroupIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'

interface FunnelData {
  impressions: number
  clicks: number
  leads: number
}

interface Props {
  data: FunnelData
  loading?: boolean
  dateRange?: { from: string; to: string }
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  dateRange: () => ({ from: '', to: '' })
})

const clickRate = computed(() => {
  if (!props.data.impressions) return 0
  return Math.min(100, (props.data.clicks / props.data.impressions) * 100)
})

const leadRate = computed(() => {
  if (!props.data.impressions) return 0
  return Math.min(100, (props.data.leads / props.data.impressions) * 100)
})

const ctr = computed(() => {
  if (!props.data.impressions) return 0
  return (props.data.clicks / props.data.impressions) * 100
})

const cvr = computed(() => {
  if (!props.data.clicks) return 0
  return (props.data.leads / props.data.clicks) * 100
})

const formatNumber = (value: number): string => {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`
  } else if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`
  }
  return value.toLocaleString()
}

const formatPercentage = (value: number): string => {
  return `${value.toFixed(2)}%`
}

const formatDateRange = (dateRange: any) => {
  if (!dateRange.from || !dateRange.to) return ''
  const from = new Date(dateRange.from).toLocaleDateString()
  const to = new Date(dateRange.to).toLocaleDateString()
  return `${from} - ${to}`
}
</script>