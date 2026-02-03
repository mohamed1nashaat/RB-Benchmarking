<template>
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Primary KPIs -->
    <div
      v-for="kpi in primaryKpis"
      :key="kpi"
      class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
    >
      <dt>
        <div class="absolute bg-primary-500 rounded-md p-3">
          <component :is="getKpiIcon(kpi)" class="h-6 w-6 text-white" aria-hidden="true" />
        </div>
        <p class="ml-16 text-sm font-medium text-gray-500 truncate">
          {{ $t(`kpis.${kpi}`) }}
        </p>
      </dt>
      <dd class="ml-16 pb-6 flex items-baseline sm:pb-7">
        <p class="text-2xl font-semibold text-gray-900">
          {{ formatKpiValue(kpi, dashboardStore.kpis[kpi]) }}
        </p>
        <div class="absolute bottom-0 inset-x-0 bg-gray-50 px-4 py-4 sm:px-6">
          <div class="text-sm">
            <span class="text-gray-600">{{ getKpiDescription(kpi) }}</span>
          </div>
        </div>
      </dd>
    </div>

    <!-- Secondary KPIs -->
    <div
      v-for="kpi in secondaryKpis"
      :key="kpi"
      class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
    >
      <dt>
        <div class="absolute bg-gray-500 rounded-md p-3">
          <component :is="getKpiIcon(kpi)" class="h-6 w-6 text-white" aria-hidden="true" />
        </div>
        <p class="ml-16 text-sm font-medium text-gray-500 truncate">
          {{ $t(`kpis.${kpi}`) }}
        </p>
      </dt>
      <dd class="ml-16 pb-6 flex items-baseline sm:pb-7">
        <p class="text-2xl font-semibold text-gray-900">
          {{ formatKpiValue(kpi, dashboardStore.kpis[kpi]) }}
        </p>
        <div class="absolute bottom-0 inset-x-0 bg-gray-50 px-4 py-4 sm:px-6">
          <div class="text-sm">
            <span class="text-gray-600">{{ getKpiDescription(kpi) }}</span>
          </div>
        </div>
      </dd>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  EyeIcon,
  CurrencyDollarIcon,
  PhoneIcon,
  UserGroupIcon,
  ChartBarIcon,
  ClockIcon,
  PlayIcon,
  CursorArrowRaysIcon,
  HandRaisedIcon,
  ShoppingCartIcon,
  ChatBubbleLeftRightIcon,
  DevicePhoneMobileIcon,
  ArrowTrendingUpIcon,
  UsersIcon,
  HeartIcon,
  StarIcon
} from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'

const dashboardStore = useDashboardStore()

const primaryKpis = computed(() => dashboardStore.primaryKpis)
const secondaryKpis = computed(() => dashboardStore.secondaryKpis)

const getKpiIcon = (kpi: string) => {
  const iconMap: Record<string, any> = {
    // Cost metrics
    spend: CurrencyDollarIcon,
    cpm: CurrencyDollarIcon,
    cpl: CurrencyDollarIcon,
    cpc: CurrencyDollarIcon,
    cpa: CurrencyDollarIcon,
    aov: CurrencyDollarIcon,
    cost_per_call: PhoneIcon,
    cost_per_message: ChatBubbleLeftRightIcon,
    cost_per_install: DevicePhoneMobileIcon,
    cost_per_action: HandRaisedIcon,
    cost_per_form: ChartBarIcon,
    
    // Volume metrics
    impressions: EyeIcon,
    clicks: CursorArrowRaysIcon,
    reach: UserGroupIcon,
    unique_reach: UsersIcon,
    leads: HandRaisedIcon,
    conversations: ChatBubbleLeftRightIcon,
    app_installs: DevicePhoneMobileIcon,
    app_actions: HandRaisedIcon,
    revenue: CurrencyDollarIcon,
    orders: ShoppingCartIcon,
    returning_customers: HeartIcon,
    engagements: HeartIcon,
    video_views: PlayIcon,
    sessions: UserGroupIcon,
    form_submissions: ChartBarIcon,
    transactions: ShoppingCartIcon,
    
    // Rate metrics
    ctr: ChartBarIcon,
    cvr: ArrowTrendingUpIcon,
    roas: ArrowTrendingUpIcon,
    frequency: ClockIcon,
    vtr: PlayIcon,
    engagement_rate: HeartIcon,
    bounce_rate: ChartBarIcon,
    message_rate: ChatBubbleLeftRightIcon,
    response_rate: ChatBubbleLeftRightIcon,
    install_rate: DevicePhoneMobileIcon,
    action_rate: HandRaisedIcon,
    repeat_purchase_rate: HeartIcon,
    
    // Advanced metrics
    ltv: StarIcon,
    customer_lifetime_value: StarIcon,
    churn_rate: ChartBarIcon,
    pages_per_session: ChartBarIcon,
    post_install_events: DevicePhoneMobileIcon,
    event_value: CurrencyDollarIcon,
    lead_quality_score: StarIcon,
    cart_abandonment: ShoppingCartIcon,
    atc: ShoppingCartIcon,
    retention_rate: HeartIcon,
    call_conversion_rate: PhoneIcon
  }
  return iconMap[kpi] || ChartBarIcon
}

const formatKpiValue = (kpi: string, value: number | null): string => {
  if (value === null || value === undefined) {
    return 'N/A'
  }

  // Currency KPIs
  if (['cpm', 'cpl', 'cpc', 'cpa', 'aov', 'cost_per_call', 'spend', 'revenue', 'ltv', 'customer_lifetime_value', 'cost_per_message', 'cost_per_install', 'cost_per_action', 'cost_per_form', 'event_value'].includes(kpi)) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: dashboardStore.currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value)
  }

  // Percentage KPIs (already multiplied by 100 in backend)
  if (['ctr', 'cvr', 'vtr', 'call_conversion_rate', 'engagement_rate', 'bounce_rate', 'message_rate', 'response_rate', 'install_rate', 'action_rate', 'repeat_purchase_rate', 'churn_rate', 'cart_abandonment'].includes(kpi)) {
    return `${value.toFixed(2)}%`
  }

  // Ratio KPIs
  if (['roas'].includes(kpi)) {
    return `${value.toFixed(2)}x`
  }

  // Score KPIs (0-10 scale)
  if (['lead_quality_score'].includes(kpi)) {
    return `${value.toFixed(1)}/10`
  }

  // Decimal metrics
  if (['frequency', 'pages_per_session'].includes(kpi)) {
    return value.toFixed(2)
  }

  // Large numbers (reach, impressions, clicks, etc.)
  if (['reach', 'unique_reach', 'impressions', 'clicks', 'leads', 'conversations', 'app_installs', 'app_actions', 'orders', 'returning_customers', 'engagements', 'video_views', 'sessions', 'form_submissions', 'transactions', 'post_install_events'].includes(kpi)) {
    if (value >= 1000000) {
      return `${(value / 1000000).toFixed(1)}M`
    } else if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}K`
    }
  }

  return value.toLocaleString()
}

const getKpiDescription = (kpi: string): string => {
  const descriptions: Record<string, string> = {
    // Cost metrics
    spend: 'Total advertising spend',
    cpm: 'Cost per 1,000 impressions',
    cpl: 'Cost per lead generated',
    cpc: 'Cost per click',
    cpa: 'Cost per acquisition',
    aov: 'Average order value',
    cost_per_call: 'Cost per phone call',
    cost_per_message: 'Cost per message sent',
    cost_per_install: 'Cost per app install',
    cost_per_action: 'Cost per in-app action',
    cost_per_form: 'Cost per form submission',
    
    // Volume metrics
    impressions: 'Total ad impressions',
    clicks: 'Total clicks received',
    reach: 'Unique users reached',
    unique_reach: 'Total unique users',
    leads: 'Total leads generated',
    conversations: 'Total conversations started',
    app_installs: 'Total app installations',
    app_actions: 'Total in-app actions',
    revenue: 'Total revenue generated',
    orders: 'Total orders placed',
    returning_customers: 'Customers who returned',
    engagements: 'Total post engagements',
    video_views: 'Total video views',
    sessions: 'Total website sessions',
    form_submissions: 'Total form submissions',
    transactions: 'Total transactions',
    
    // Rate metrics
    ctr: 'Click-through rate',
    cvr: 'Conversion rate',
    roas: 'Return on ad spend',
    frequency: 'Average impressions per user',
    vtr: 'Video view rate',
    engagement_rate: 'Post engagement rate',
    bounce_rate: 'Website bounce rate',
    message_rate: 'Message conversion rate',
    response_rate: 'Response rate to messages',
    install_rate: 'App install conversion rate',
    action_rate: 'In-app action rate',
    repeat_purchase_rate: 'Customer repeat rate',
    
    // Advanced metrics
    ltv: 'Customer lifetime value',
    customer_lifetime_value: 'Average customer LTV',
    churn_rate: 'Customer churn rate',
    pages_per_session: 'Average pages per session',
    post_install_events: 'Events after install',
    event_value: 'Average event value',
    lead_quality_score: 'Lead quality rating',
    cart_abandonment: 'Cart abandonment rate',
    atc: 'Add to cart events',
    retention_rate: 'Customer retention rate',
    call_conversion_rate: 'Calls per click'
  }
  return descriptions[kpi] || ''
}
</script>
