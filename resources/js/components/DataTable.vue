<template>
  <div class="overflow-hidden">
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
    
    <div v-else-if="data.length === 0" class="text-center py-12">
      <div class="text-gray-500">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('messages.no_data') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('messages.adjust_date_range') }}</p>
      </div>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              @click="sortBy(column.key)"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <div class="flex flex-col">
                  <ChevronUpIcon 
                    :class="[
                      'h-3 w-3',
                      sortColumn === column.key && sortDirection === 'asc' 
                        ? 'text-primary-600' 
                        : 'text-gray-400'
                    ]" 
                  />
                  <ChevronDownIcon 
                    :class="[
                      'h-3 w-3 -mt-1',
                      sortColumn === column.key && sortDirection === 'desc' 
                        ? 'text-primary-600' 
                        : 'text-gray-400'
                    ]" 
                  />
                </div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(row, index) in sortedData"
            :key="index"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap text-sm"
            >
              <div v-if="column.key === 'campaign_name'" class="flex items-center">
                <div class="flex-shrink-0 h-8 w-8">
                  <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <component :is="getPlatformIcon(row.platform)" class="h-4 w-4" />
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ row[column.key] }}
                  </div>
                  <div class="text-sm text-gray-500 capitalize">
                    {{ row.platform }}
                  </div>
                </div>
              </div>
              
              <div v-else-if="column.type === 'currency'" class="text-gray-900">
                {{ formatCurrency(row[column.key]) }}
              </div>
              
              <div v-else-if="column.type === 'percentage'" class="text-gray-900">
                {{ formatPercentage(row[column.key]) }}
              </div>
              
              <div v-else-if="column.type === 'number'" class="text-gray-900">
                {{ formatNumber(row[column.key]) }}
              </div>
              
              <div v-else-if="column.type === 'ratio'" class="text-gray-900">
                {{ formatRatio(row[column.key]) }}
              </div>
              
              <div v-else class="text-gray-900">
                {{ row[column.key] }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ $t('buttons.previous') }}
        </button>
        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ $t('buttons.next') }}
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            {{ $t('table.showing') }}
            <span class="font-medium">{{ startIndex }}</span>
            {{ $t('table.to') }}
            <span class="font-medium">{{ endIndex }}</span>
            {{ $t('table.of') }}
            <span class="font-medium">{{ data.length }}</span>
            {{ $t('table.results') }}
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
            </button>
            
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === currentPage
                  ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ChartBarIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'

const { t } = useI18n()

interface Column {
  key: string
  label: string
  type?: 'text' | 'currency' | 'percentage' | 'number' | 'ratio'
}

interface Props {
  data: any[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const dashboardStore = useDashboardStore()

const sortColumn = ref('spend')
const sortDirection = ref<'asc' | 'desc'>('desc')
const currentPage = ref(1)
const itemsPerPage = 10

const columns = computed((): Column[] => {
  const baseColumns: Column[] = [
    { key: 'campaign_name', label: t('table.campaign'), type: 'text' },
    { key: 'spend', label: t('kpis.spend'), type: 'currency' },
    { key: 'impressions', label: t('kpis.impressions'), type: 'number' },
    { key: 'clicks', label: t('kpis.clicks'), type: 'number' },
    { key: 'ctr', label: t('kpis.ctr'), type: 'percentage' },
    { key: 'cpc', label: t('kpis.cpc'), type: 'currency' }
  ]

  // Add objective-specific columns
  switch (dashboardStore.objective) {
    case 'awareness':
      baseColumns.push(
        { key: 'cpm', label: t('kpis.cpm'), type: 'currency' },
        { key: 'reach', label: t('kpis.reach'), type: 'number' }
      )
      break
    case 'leads':
      baseColumns.push(
        { key: 'leads', label: t('kpis.leads'), type: 'number' },
        { key: 'cpl', label: t('kpis.cpl'), type: 'currency' },
        { key: 'cvr', label: t('kpis.cvr'), type: 'percentage' }
      )
      break
    case 'sales':
      baseColumns.push(
        { key: 'revenue', label: t('kpis.revenue'), type: 'currency' },
        { key: 'purchases', label: t('kpis.purchases'), type: 'number' },
        { key: 'roas', label: t('kpis.roas'), type: 'ratio' },
        { key: 'cpa', label: t('kpis.cpa'), type: 'currency' }
      )
      break
    case 'calls':
      baseColumns.push(
        { key: 'calls', label: t('kpis.calls'), type: 'number' },
        { key: 'cost_per_call', label: t('kpis.cost_per_call'), type: 'currency' }
      )
      break
  }

  return baseColumns
})

const sortedData = computed(() => {
  if (!props.data.length) return []

  const sorted = [...props.data].sort((a, b) => {
    const aVal = a[sortColumn.value] || 0
    const bVal = b[sortColumn.value] || 0

    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  // Apply pagination
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return sorted.slice(start, end)
})

const totalPages = computed(() => Math.ceil(props.data.length / itemsPerPage))

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage + 1)
const endIndex = computed(() => Math.min(currentPage.value * itemsPerPage, props.data.length))

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const sortBy = (column: string) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortDirection.value = 'desc'
  }
  currentPage.value = 1 // Reset to first page when sorting
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const goToPage = (page: number) => {
  currentPage.value = page
}

const getPlatformIcon = (platform: string) => {
  // Return appropriate icon component based on platform
  // For now, return a generic chart icon
  return ChartBarIcon
}

const formatCurrency = (value: number | null): string => {
  if (value === null || value === undefined) return t('status.not_available')
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: dashboardStore.currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const formatPercentage = (value: number | null): string => {
  if (value === null || value === undefined) return t('status.not_available')
  return `${value.toFixed(2)}%`  // Already multiplied by 100 in backend
}

const formatNumber = (value: number | null): string => {
  if (value === null || value === undefined) return t('status.not_available')
  return value.toLocaleString()
}

const formatRatio = (value: number | null): string => {
  if (value === null || value === undefined) return t('status.not_available')
  return `${value.toFixed(2)}x`
}
</script>
