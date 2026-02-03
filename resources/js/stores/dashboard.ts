import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { detectCampaignObjective, detectCampaignObjectiveWithConfidence, type CampaignObjective } from '@/utils/objectiveDetection'

export type Objective = 'awareness' | 'engagement' | 'traffic' | 'messages' | 'app_installs' | 'in_app_actions' | 'leads' | 'website_sales' | 'retention'

export interface DateRange {
  from: string
  to: string
}

export interface Filters {
  account_id?: number
  campaign_id?: number
  platform?: 'facebook' | 'google' | 'tiktok'
}

export interface KPIData {
  [key: string]: number | null
}

export interface TimeseriesData {
  period: string
  value: number
  raw_metrics: {
    spend: number
    impressions: number
    clicks: number
    revenue: number
    leads: number
    calls: number
  }
}

export const useDashboardStore = defineStore('dashboard', () => {
  const objective = ref<Objective>('awareness')
  const dateRange = ref<DateRange>({
    from: new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // Last 12 months
    to: new Date().toISOString().split('T')[0]
  })
  const filters = ref<Filters>({})
  const kpis = ref<KPIData>({})
  const timeseriesData = ref<TimeseriesData[]>([])
  const currency = ref<string>('USD')
  const loading = ref(false)

  const primaryKpis = computed(() => {
    switch (objective.value) {
      case 'awareness':
        return ['spend', 'impressions', 'reach', 'cpm']
      case 'engagement':
        return ['spend', 'clicks', 'ctr', 'frequency']
      case 'traffic':
        return ['spend', 'clicks', 'cpc', 'ctr']
      case 'messages':
        return ['spend', 'conversations', 'cpc', 'ctr']
      case 'app_installs':
        return ['spend', 'app_installs', 'cpa', 'ctr']
      case 'in_app_actions':
        return ['spend', 'app_actions', 'cpa', 'atc']
      case 'leads':
        return ['spend', 'leads', 'cpl', 'cvr']
      case 'website_sales':
        return ['spend', 'revenue', 'roas', 'orders']
      case 'retention':
        return ['spend', 'returning_customers', 'cpa', 'ltv']
      default:
        return []
    }
  })

  const secondaryKpis = computed(() => {
    switch (objective.value) {
      case 'awareness':
        return ['frequency', 'vtr', 'unique_reach']
      case 'engagement':
        return ['engagements', 'engagement_rate', 'video_views']
      case 'traffic':
        return ['sessions', 'bounce_rate', 'pages_per_session']
      case 'messages':
        return ['message_rate', 'response_rate', 'cost_per_message']
      case 'app_installs':
        return ['install_rate', 'cost_per_install', 'post_install_events']
      case 'in_app_actions':
        return ['action_rate', 'cost_per_action', 'event_value']
      case 'leads':
        return ['form_submissions', 'cost_per_form', 'lead_quality_score']
      case 'website_sales':
        return ['transactions', 'aov', 'cart_abandonment']
      case 'retention':
        return ['repeat_purchase_rate', 'customer_lifetime_value', 'churn_rate']
      default:
        return []
    }
  })

  async function fetchSummary() {
    loading.value = true
    try {
      const params = {
        from: dateRange.value.from,
        to: dateRange.value.to,
        objective: objective.value,
        ...filters.value
      }

      const response = await axios.get('/api/metrics/summary', { params })
      kpis.value = response.data.kpis
      currency.value = response.data.currency || 'USD'
    } finally {
      loading.value = false
    }
  }

  async function fetchDateRange() {
    try {
      const response = await axios.get('/api/metrics/date-range')
      // Only update if we get different values to avoid triggering watchers unnecessarily
      if (response.data.from !== dateRange.value.from || response.data.to !== dateRange.value.to) {
        dateRange.value = {
          from: response.data.from,
          to: response.data.to
        }
      }
      return response.data
    } catch (error) {
      console.warn('Could not fetch date range from API:', error)
      return dateRange.value
    }
  }

  async function fetchTimeseries(metric: string, groupBy: string = 'date') {
    loading.value = true
    try {
      const params = {
        metric,
        from: dateRange.value.from,
        to: dateRange.value.to,
        objective: objective.value,
        group_by: groupBy,
        ...filters.value
      }

      const response = await axios.get('/api/metrics/timeseries', { params })
      timeseriesData.value = response.data.data
      return response.data.data
    } finally {
      loading.value = false
    }
  }

  function setObjective(newObjective: Objective) {
    objective.value = newObjective
    // Save to localStorage for persistence
    localStorage.setItem('dashboard_objective', newObjective)
  }

  function setDateRange(newDateRange: DateRange) {
    dateRange.value = newDateRange
    // Save to localStorage for persistence
    localStorage.setItem('dashboard_date_range', JSON.stringify(newDateRange))
  }

  function setFilters(newFilters: Filters) {
    filters.value = { ...filters.value, ...newFilters }
    // Save to localStorage for persistence
    localStorage.setItem('dashboard_filters', JSON.stringify(filters.value))
  }

  function clearFilters() {
    filters.value = {}
    localStorage.removeItem('dashboard_filters')
  }

  // Auto-detection functions
  function detectObjectiveFromCampaignName(campaignName: string): Objective | null {
    return detectCampaignObjective(campaignName) as Objective | null
  }

  function detectObjectiveWithConfidence(campaignName: string) {
    return detectCampaignObjectiveWithConfidence(campaignName)
  }

  async function autoDetectAndSetObjective(campaignName?: string) {
    if (!campaignName) {
      // If no campaign name provided, try to get it from current filters or selected campaign
      if (filters.value.campaign_id) {
        try {
          const response = await axios.get(`/api/campaigns/${filters.value.campaign_id}`)
          campaignName = response.data.campaign?.name
        } catch (error) {
          console.warn('Could not fetch campaign name for auto-detection:', error)
          return false
        }
      }
    }

    if (campaignName) {
      const detection = detectObjectiveWithConfidence(campaignName)
      if (detection.objective && detection.confidence !== 'none') {
        setObjective(detection.objective as Objective)
        return { 
          detected: true, 
          objective: detection.objective,
          confidence: detection.confidence,
          campaignName 
        }
      }
    }

    return false
  }

  // Batch detect objectives for campaigns
  async function detectObjectivesForCampaigns(campaigns: Array<{id: number, name: string}>) {
    return campaigns.map(campaign => {
      const detection = detectObjectiveWithConfidence(campaign.name)
      return {
        ...campaign,
        detectedObjective: detection.objective,
        confidence: detection.confidence,
        score: detection.score
      }
    })
  }


  // Initialize from localStorage
  const savedObjective = localStorage.getItem('dashboard_objective') as Objective
  if (savedObjective && ['awareness', 'engagement', 'traffic', 'messages', 'app_installs', 'in_app_actions', 'leads', 'website_sales', 'retention'].includes(savedObjective)) {
    objective.value = savedObjective
  }

  // Load saved date range
  try {
    const savedDateRange = localStorage.getItem('dashboard_date_range')
    if (savedDateRange) {
      const parsed = JSON.parse(savedDateRange)
      if (parsed.from && parsed.to) {
        dateRange.value = parsed
      }
    }
  } catch (e) {
    console.error('Error loading saved date range:', e)
  }

  // Load saved filters
  try {
    const savedFilters = localStorage.getItem('dashboard_filters')
    if (savedFilters) {
      filters.value = JSON.parse(savedFilters)
    }
  } catch (e) {
    console.error('Error loading saved filters:', e)
  }

  return {
    objective,
    dateRange,
    filters,
    kpis,
    timeseriesData,
    currency,
    loading,
    primaryKpis,
    secondaryKpis,
    fetchSummary,
    fetchTimeseries,
    fetchDateRange,
    setObjective,
    setDateRange,
    setFilters,
    clearFilters,
    // Auto-detection functions
    detectObjectiveFromCampaignName,
    detectObjectiveWithConfidence,
    autoDetectAndSetObjective,
    detectObjectivesForCampaigns
  }
})
