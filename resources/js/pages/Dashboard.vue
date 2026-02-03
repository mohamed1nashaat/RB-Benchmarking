<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('dashboard.title') }}
        </h2>
        <div class="mt-1 space-y-1">
          <p class="text-sm text-gray-500">
            {{ $t(`objectives.${dashboardStore.objective}`) }} • 
            {{ formatDateRange(dashboardStore.dateRange) }}
          </p>
        </div>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <DateRangePicker />
        <FilterBar />
        <button
          @click="refreshData"
          :disabled="dashboardStore.loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', dashboardStore.loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          {{ $t('dashboard.refresh') }}
        </button>
        <ExportButton />
      </div>
    </div>


    <!-- KPI Grid -->
    <KPIGrid />

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Primary Chart -->
      <ChartCard
        :title="getPrimaryChartTitle()"
        :chart-data="timeseriesData"
        :loading="dashboardStore.loading"
        :metric="getPrimaryMetric()"
        chart-type="line"
      />

      <!-- Secondary Chart -->
      <ChartCard
        :title="getSecondaryChartTitle()"
        :chart-data="campaignData"
        :loading="dashboardStore.loading"
        :metric="getPrimaryMetric()"
        chart-type="bar"
      />

      <!-- Funnel Chart -->
      <FunnelChart
        :data="funnelData"
        :loading="dashboardStore.loading"
        :date-range="dashboardStore.dateRange"
      />
    </div>

    <!-- Spend Table -->
    <SpendTable :spend-data="spendData" :loading="dashboardStore.loading" />

    <!-- Data Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          {{ $t('dashboard.campaign_performance') }}
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          {{ $t('dashboard.detailed_breakdown') }}
        </p>
      </div>
      <DataTable :data="tableData" :loading="dashboardStore.loading" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'
import KPIGrid from '@/components/KPIGrid.vue'
import ChartCard from '@/components/ChartCard.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'
import FilterBar from '@/components/FilterBar.vue'
import ExportButton from '@/components/ExportButton.vue'
import DataTable from '@/components/DataTable.vue'
import SpendTable from '@/components/SpendTable.vue'
import FunnelChart from '@/components/FunnelChart.vue'

const { t } = useI18n()
const dashboardStore = useDashboardStore()

const timeseriesData = ref([])
const campaignData = ref([])
const tableData = ref([])
const spendData = ref([])
const funnelData = ref({
  impressions: 0,
  clicks: 0,
  leads: 0
})

const formatDateRange = (dateRange: any) => {
  const from = new Date(dateRange.from).toLocaleDateString()
  const to = new Date(dateRange.to).toLocaleDateString()
  return `${from} - ${to}`
}

const getPrimaryChartTitle = () => {
  switch (dashboardStore.objective) {
    case 'awareness':
      return t('dashboard.impressions_over_time')
    case 'leads':
      return t('dashboard.leads_over_time')
    case 'sales':
      return t('dashboard.revenue_over_time')
    case 'calls':
      return t('dashboard.calls_over_time')
    default:
      return t('dashboard.performance_over_time')
  }
}

const getSecondaryChartTitle = () => {
  switch (dashboardStore.objective) {
    case 'awareness':
      return t('dashboard.cpm_by_campaign')
    case 'leads':
      return t('dashboard.cpl_by_campaign')
    case 'sales':
      return t('dashboard.roas_by_campaign')
    case 'calls':
      return t('dashboard.cost_per_call_by_campaign')
    default:
      return t('dashboard.performance_by_campaign')
  }
}

const getPrimaryMetric = () => {
  return dashboardStore.primaryKpis[0] || 'cpm'
}

const refreshData = async () => {
  await Promise.all([
    dashboardStore.fetchSummary(),
    fetchTimeseriesData(),
    fetchCampaignData(),
    fetchTableData(),
    fetchSpendData(),
    fetchFunnelData()
  ])
}


const fetchTimeseriesData = async () => {
  try {
    const metric = getPrimaryMetric()
    const data = await dashboardStore.fetchTimeseries(metric, 'date')
    timeseriesData.value = data
  } catch (error) {
    console.error('Error fetching timeseries data:', error)
  }
}

const fetchCampaignData = async () => {
  try {
    const metric = getPrimaryMetric()
    const data = await dashboardStore.fetchTimeseries(metric, 'campaign')
    campaignData.value = data
  } catch (error) {
    console.error('Error fetching campaign data:', error)
  }
}

const fetchTableData = async () => {
  try {
    // Fetch detailed campaign data for the table
    const metric = getPrimaryMetric()
    const data = await dashboardStore.fetchTimeseries(metric, 'campaign')
    // Transform data for table display
    tableData.value = data.map((item: any) => {
      const raw = item.raw_metrics || {}
      const spend = parseFloat(raw.spend || 0)
      const impressions = parseInt(raw.impressions || 0)
      const clicks = parseInt(raw.clicks || 0)
      const leads = parseInt(raw.leads || 0)
      const revenue = parseFloat(raw.revenue || 0)
      const calls = parseInt(raw.calls || 0)
      
      // Calculate KPIs
      const ctr = impressions > 0 ? (clicks / impressions) * 100 : 0
      const cpc = clicks > 0 ? spend / clicks : 0
      const cpl = leads > 0 ? spend / leads : 0
      const cpm = impressions > 0 ? (spend / impressions) * 1000 : 0
      const roas = spend > 0 ? revenue / spend : 0
      const costPerCall = calls > 0 ? spend / calls : 0
      const cvr = clicks > 0 ? (leads / clicks) * 100 : 0
      
      return {
        campaign_name: item.period,
        metric_value: item.value,
        spend,
        impressions,
        clicks,
        revenue,
        leads,
        calls,
        ctr,
        cpc,
        cpl,
        cpm,
        roas,
        cost_per_call: costPerCall,
        cvr
      }
    })
  } catch (error) {
    console.error('Error fetching table data:', error)
    tableData.value = []
  }
}

const fetchSpendData = async () => {
  try {
    const params = {
      from: dashboardStore.dateRange.from,
      to: dashboardStore.dateRange.to,
      group_by: 'account',
      ...dashboardStore.filters
    }

    const response = await window.axios.get('/api/metrics/spend-breakdown', { params })
    spendData.value = response.data.data
  } catch (error) {
    console.error('Error fetching spend data:', error)
    spendData.value = []
  }
}

const fetchFunnelData = async () => {
  try {
    // Use the summary data for funnel metrics
    await dashboardStore.fetchSummary()
    funnelData.value = {
      impressions: dashboardStore.kpis.impressions || 0,
      clicks: dashboardStore.kpis.clicks || 0,
      leads: dashboardStore.kpis.leads || 0
    }
  } catch (error) {
    console.error('Error fetching funnel data:', error)
    funnelData.value = { impressions: 0, clicks: 0, leads: 0 }
  }
}

// Watch for objective changes and refresh data
watch(() => dashboardStore.objective, () => {
  refreshData()
})

// Watch for date range changes and refresh data (only when user manually changes it)
watch(() => dashboardStore.dateRange, (newRange, oldRange) => {
  // Only refresh if both from and to are populated (not initial empty state)
  // and if the change wasn't from an API update (which would have already refreshed data)
  if (newRange.from && newRange.to && oldRange.from && oldRange.to) {
    refreshData()
  }
}, { deep: true })

// Watch for filter changes and refresh data
watch(() => dashboardStore.filters, () => {
  refreshData()
}, { deep: true })

onMounted(() => {
  // Date range will be fetched automatically by the store functions when needed
  refreshData()
})
</script>
