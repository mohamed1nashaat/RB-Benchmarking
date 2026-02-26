<template>
  <div class="py-6">
    <!-- Back Button -->
    <div class="mb-4">
      <button
        @click="router.push({ name: 'clients' })"
        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
      >
        <ArrowLeftIcon class="w-4 h-4 mr-1" />
        {{ $t('common.back_to_clients') }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="dashboardData">
      <!-- Client Header -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ dashboardData.client.name }}</h1>
              <p v-if="dashboardData.client.industry" class="text-sm text-gray-500 capitalize">
                {{ formatIndustry(dashboardData.client.industry) }}
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <!-- Export Dropdown -->
            <Menu as="div" class="relative inline-block text-left">
              <MenuButton
                :disabled="exporting"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
              >
                <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                {{ exporting ? $t('common.exporting') : $t('common.export') }}
              </MenuButton>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="exportDashboard('pdf')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center w-full px-4 py-2 text-sm']"
                      >
                        <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        {{ $t('common.export_as_pdf') }}
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="exportDashboard('csv')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center w-full px-4 py-2 text-sm']"
                      >
                        <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        {{ $t('common.export_as_csv') }}
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="exportDashboard('excel')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center w-full px-4 py-2 text-sm']"
                      >
                        <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $t('common.export_as_excel') }}
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            @click="setActiveTab(0)"
            :class="[
              'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors',
              activeTabIndex === 0
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <ChartBarIcon class="w-5 h-5 inline-block mr-1.5 -mt-0.5" />
            {{ $t('client_dashboard.tabs.performance') }}
          </button>
          <button
            @click="setActiveTab(1)"
            :class="[
              'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors',
              activeTabIndex === 1
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <MagnifyingGlassIcon class="w-5 h-5 inline-block mr-1.5 -mt-0.5" />
            {{ $t('client_dashboard.tabs.seo_report') }}
          </button>
        </nav>
      </div>

      <!-- Ad Performance Tab -->
      <div v-if="activeTabIndex === 0">

      <!-- Filters Panel - Always Visible -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
          <!-- Period Buttons -->
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600">Period:</span>
            <div class="inline-flex rounded-lg bg-gray-100 p-1">
              <button
                v-for="period in [7, 30, 90, 365, 0]"
                :key="period"
                @click="selectedPeriod = period"
                :class="[
                  'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200',
                  selectedPeriod === period
                    ? 'bg-white text-primary-700 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                {{ period === 0 ? 'All' : period === 365 ? '1Y' : period + 'D' }}
              </button>
            </div>
          </div>

          <!-- Divider -->
          <div class="hidden md:block h-8 w-px bg-gray-200"></div>

          <!-- Custom Date Range -->
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600">Custom:</span>
            <input
              v-model="dateFrom"
              type="date"
              class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5"
              placeholder="From"
            />
            <span class="text-gray-400">-</span>
            <input
              v-model="dateTo"
              type="date"
              class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5"
              placeholder="To"
            />
          </div>

          <!-- Divider -->
          <div class="hidden md:block h-8 w-px bg-gray-200"></div>

          <!-- Platform Filter -->
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600">Platform:</span>
            <select
              v-model="selectedPlatform"
              class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8"
            >
              <option value="">All Platforms</option>
              <option value="facebook">Facebook</option>
              <option value="google">Google</option>
              <option value="instagram">Instagram</option>
              <option value="twitter">Twitter</option>
              <option value="linkedin">LinkedIn</option>
              <option value="tiktok">TikTok</option>
            </select>
          </div>

          <!-- Reset Button -->
          <button
            v-if="selectedPeriod !== 30 || selectedPlatform || dateFrom || dateTo"
            @click="resetFilters"
            class="ml-auto inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
          >
            <XMarkIcon class="w-4 h-4 mr-1" />
            Reset
          </button>

          <!-- Loading Indicator -->
          <div v-if="loading" class="ml-auto flex items-center text-sm text-gray-500">
            <svg class="animate-spin h-4 w-4 mr-2 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
          </div>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Total Spend</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatCurrency(dashboardData.metrics.total_spend) }}
              </p>
            </div>
            <div class="p-3 bg-primary-100 rounded-lg">
              <CurrencyDollarIcon class="w-6 h-6 text-primary-600" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Impressions</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatNumber(dashboardData.metrics.total_impressions) }}
              </p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
              <EyeIcon class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Clicks</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatNumber(dashboardData.metrics.total_clicks) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">CTR: {{ dashboardData.metrics.ctr.toFixed(2) }}%</p>
            </div>
            <div class="p-3 bg-purple-100 rounded-lg">
              <CursorArrowRaysIcon class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Conversions</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatNumber(dashboardData.metrics.total_conversions) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">CVR: {{ dashboardData.metrics.cvr.toFixed(2) }}%</p>
            </div>
            <div class="p-3 bg-orange-100 rounded-lg">
              <CheckCircleIcon class="w-6 h-6 text-orange-600" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Cost Per Click</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatCurrency(dashboardData.metrics.cpc) }}
              </p>
            </div>
            <div class="p-3 bg-indigo-100 rounded-lg">
              <CalculatorIcon class="w-6 h-6 text-indigo-600" />
            </div>
          </div>
        </div>

        <div v-if="dashboardData.metrics.total_revenue > 0" class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Total Revenue</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatCurrency(dashboardData.metrics.total_revenue) }}
              </p>
            </div>
            <div class="p-3 bg-emerald-100 rounded-lg">
              <BanknotesIcon class="w-6 h-6 text-emerald-600" />
            </div>
          </div>
        </div>

        <div v-if="dashboardData.metrics.roas > 0" class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">ROAS</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ dashboardData.metrics.roas.toFixed(2) }}x
              </p>
              <p class="text-xs" :class="dashboardData.metrics.roas >= 2 ? 'text-green-600' : 'text-gray-500'">
                {{ dashboardData.metrics.roas >= 2 ? 'Excellent' : 'Average' }}
              </p>
            </div>
            <div class="p-3 bg-teal-100 rounded-lg">
              <ArrowTrendingUpIcon class="w-6 h-6 text-teal-600" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Avg Impressions</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ formatNumber(dashboardData.metrics.total_impressions) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
            </div>
            <div class="p-3 bg-cyan-100 rounded-lg">
              <ChartBarIcon class="w-6 h-6 text-cyan-600" />
            </div>
          </div>
        </div>
      </div>

      <!-- Platform Breakdown -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Platform Breakdown</h3>
          <select
            v-model="platformChartType"
            class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8"
          >
            <option value="doughnut">Doughnut</option>
            <option value="pie">Pie</option>
            <option value="bar">Bar</option>
            <option value="polarArea">Polar Area</option>
            <option value="radar">Radar</option>
          </select>
        </div>
        <div class="flex flex-col lg:flex-row items-center gap-6">
          <div class="w-full lg:w-1/2 flex justify-center">
            <InteractiveChart
              v-if="platformChartData"
              :key="'platform-' + chartKey + '-' + platformChartType"
              :type="platformChartType"
              :data="platformChartData"
              :height="256"
              :loading="loading"
              :options="{ plugins: { datalabels: { display: false } } }"
            />
          </div>
          <div class="w-full lg:w-1/2 space-y-2">
            <div v-for="platform in dashboardData.platform_breakdown" :key="platform.platform" class="flex items-center justify-between text-sm p-2 rounded hover:bg-gray-50">
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: getPlatformColor(platform.platform) }"></div>
                <span class="font-medium text-gray-900 capitalize">{{ platform.platform }}</span>
                <span class="text-gray-500">({{ platform.accounts_count }})</span>
              </div>
              <span class="font-semibold text-gray-900">{{ formatCurrency(platform.spend) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Trends - All Metrics -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Performance Trends ({{ periodLabel }})</h3>
          <select
            v-model="trendChartType"
            class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8"
          >
            <option value="line">Line</option>
            <option value="bar">Bar</option>
            <option value="radar">Radar</option>
          </select>
        </div>
        <InteractiveChart
          v-if="combinedTrendChartData"
          :key="'trends-' + chartKey + '-' + trendChartType"
          :type="trendChartType"
          :data="combinedTrendChartData"
          :height="300"
          :loading="loading"
          :options="combinedTrendChartOptions"
        />
      </div>

      <!-- Ad Accounts List -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ad Accounts</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spend</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Health</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="account in dashboardData.ad_accounts" :key="account.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ account.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                  {{ account.platform }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="account.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                    {{ account.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatCurrency(account.total_spend) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getHealthClass(account.health)">
                    {{ account.health }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Campaigns Chart -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Top Campaigns Performance ({{ periodLabel }})</h3>
          <select
            v-model="campaignsChartType"
            class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pr-8"
          >
            <option value="bar">Bar</option>
            <option value="line">Line</option>
            <option value="radar">Radar</option>
            <option value="polarArea">Polar Area</option>
          </select>
        </div>
        <InteractiveChart
          v-if="campaignsChartData"
          :key="'campaigns-' + chartKey + '-' + campaignsChartType"
          :type="campaignsChartType"
          :data="campaignsChartData"
          :height="300"
          :loading="loading"
          :options="campaignsChartOptions"
        />
      </div>

      <!-- Top Campaigns Table -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Campaigns (Details)</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversions</th>
                <th v-if="dashboardData.metrics.total_revenue > 0" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ROAS</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="campaign in dashboardData.top_campaigns" :key="campaign.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ campaign.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ campaign.account_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatCurrency(campaign.spend) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatNumber(campaign.conversions) }}
                </td>
                <td v-if="dashboardData.metrics.total_revenue > 0" class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-semibold"
                    :class="campaign.roas >= 2 ? 'text-green-600' : campaign.roas >= 1 ? 'text-orange-600' : 'text-red-600'">
                    {{ campaign.roas.toFixed(2) }}x
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      </div><!-- /Ad Performance Tab -->

      <!-- SEO Report Tab -->
      <div v-if="activeTabIndex === 1">
        <SeoReportTab
          :tenant-id="route.params.id"
          :client-website="dashboardData?.client?.website"
        />
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">Failed to load client dashboard</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  CurrencyDollarIcon,
  EyeIcon,
  CursorArrowRaysIcon,
  CheckCircleIcon,
  CalculatorIcon,
  BanknotesIcon,
  ChartBarIcon,
  ArrowTrendingUpIcon,
  ArrowDownTrayIcon,
  FunnelIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import InteractiveChart from '@/components/InteractiveChart.vue'
import SeoReportTab from '@/components/SeoReportTab.vue'
import type { ClientDashboardData } from '@/types/client'

const route = useRoute()
const router = useRouter()

const activeTabIndex = ref(0)
const dashboardData = ref<ClientDashboardData | null>(null)
const loading = ref(true)
const selectedPeriod = ref(30)
const platformChartType = ref('doughnut')
const trendChartType = ref('line')
const campaignsChartType = ref('bar')
const selectedPlatform = ref('')
const selectedMetric = ref('impressions')
const dateFrom = ref('')
const dateTo = ref('')
const exporting = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

// Computed key for forcing chart re-renders when filters change
const chartKey = computed(() => `${selectedPeriod.value}-${selectedPlatform.value}-${dateFrom.value}-${dateTo.value}`)

// Computed label for chart titles based on selected period
const periodLabel = computed(() => {
  if (dateFrom.value && dateTo.value) {
    return `${dateFrom.value} to ${dateTo.value}`
  }
  if (!selectedPeriod.value || selectedPeriod.value === 0) {
    return 'All Time'
  }
  return `Last ${selectedPeriod.value} Days`
})

const fetchDashboard = async () => {
  loading.value = true
  try {
    const clientId = route.params.id
    const params = new URLSearchParams()

    if (selectedPeriod.value) params.append('period', selectedPeriod.value.toString())
    if (selectedPlatform.value) params.append('platform', selectedPlatform.value)
    if (dateFrom.value) params.append('from', dateFrom.value)
    if (dateTo.value) params.append('to', dateTo.value)

    const response = await window.axios.get(`/api/clients/${clientId}/dashboard?${params.toString()}`)
    dashboardData.value = response.data
  } catch (error) {
    console.error('Error fetching client dashboard:', error)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  selectedPeriod.value = 30
  selectedPlatform.value = ''
  dateFrom.value = ''
  dateTo.value = ''
}

// Debounced fetch for date inputs
const debouncedFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchDashboard()
  }, 500)
}

// Watchers for auto-apply filters
watch(selectedPeriod, () => {
  // Clear custom dates when selecting a preset period
  if (selectedPeriod.value) {
    dateFrom.value = ''
    dateTo.value = ''
  }
  fetchDashboard()
})

watch(selectedPlatform, () => {
  fetchDashboard()
})

watch([dateFrom, dateTo], () => {
  // Clear preset period when using custom dates
  if (dateFrom.value || dateTo.value) {
    selectedPeriod.value = 0
  }
  debouncedFetch()
})

const exportDashboard = async (format: 'pdf' | 'csv' | 'excel') => {
  if (exporting.value) return

  exporting.value = true
  try {
    const clientId = route.params.id
    const params = {
      period: selectedPeriod.value,
      platform: selectedPlatform.value,
      from: dateFrom.value,
      to: dateTo.value,
    }

    const response = await window.axios.post(
      `/api/clients/${clientId}/export/${format}`,
      params,
      { responseType: 'blob' }
    )

    // Download the file
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `client-dashboard-${clientId}-${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error(`Error exporting to ${format}:`, error)
    alert(`Failed to export dashboard as ${format.toUpperCase()}`)
  } finally {
    exporting.value = false
  }
}

const getInitials = (name: string): string => {
  return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
}

const formatIndustry = (industry: string): string => {
  return industry.replace(/_/g, ' ')
}

const formatCurrency = (amount: number): string => {
  if (amount >= 1000000) return `${(amount / 1000000).toFixed(1)}M SAR`
  if (amount >= 1000) return `${(amount / 1000).toFixed(1)}K SAR`
  return `${amount.toFixed(0)} SAR`
}

const formatNumber = (num: number): string => {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`
  return num.toString()
}

const getHealthClass = (health: string): string => {
  const classes = {
    healthy: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    critical: 'bg-red-100 text-red-800',
    inactive: 'bg-gray-100 text-gray-800',
  }
  return classes[health as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getPlatformColor = (platform: string): string => {
  // Official platform brand colors
  const colors: Record<string, string> = {
    facebook: '#1877F2',
    google: '#EA4335',
    instagram: '#E4405F',
    twitter: '#1DA1F2',
    linkedin: '#0A66C2',
    tiktok: '#000000',
    snapchat: '#FFFC00',
    youtube: '#FF0000',
    pinterest: '#E60023',
  }
  return colors[platform.toLowerCase()] || '#6B7280'
}

// Computed: Spend Trend Chart Data
// Computed: Platform Breakdown Chart Data
const platformChartData = computed(() => {
  if (!dashboardData.value?.platform_breakdown) return null

  const labels = dashboardData.value.platform_breakdown.map((p: any) =>
    p.platform.charAt(0).toUpperCase() + p.platform.slice(1)
  )
  const data = dashboardData.value.platform_breakdown.map((p: any) => p.spend)
  const colors = dashboardData.value.platform_breakdown.map((p: any) =>
    getPlatformColor(p.platform)
  )

  return {
    labels,
    datasets: [{
      label: 'Spend by Platform',
      data,
      backgroundColor: colors,
      borderWidth: 0
    }]
  }
})

// Computed: Selected Trend Chart Data
const selectedTrendChartData = computed(() => {
  if (!dashboardData.value?.trends) return null

  const metric = selectedMetric.value
  const trendData = dashboardData.value.trends[metric as keyof typeof dashboardData.value.trends]

  if (!trendData || !Array.isArray(trendData)) return null

  const labels = trendData.map((item: any) => {
    const date = new Date(item.date)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  })

  const data = trendData.map((item: any) => item.value)

  const colorMap: Record<string, { border: string; bg: string }> = {
    impressions: { border: 'rgb(16, 185, 129)', bg: 'rgba(16, 185, 129, 0.1)' },
    clicks: { border: 'rgb(139, 92, 246)', bg: 'rgba(139, 92, 246, 0.1)' },
    conversions: { border: 'rgb(249, 115, 22)', bg: 'rgba(249, 115, 22, 0.1)' }
  }

  const colors = colorMap[metric] || { border: 'rgb(229, 62, 62)', bg: 'rgba(229, 62, 62, 0.1)' }

  return {
    labels,
    datasets: [{
      label: metric.charAt(0).toUpperCase() + metric.slice(1),
      data,
      borderColor: colors.border,
      backgroundColor: colors.bg,
      borderWidth: 2,
      fill: true,
      tension: 0.4
    }]
  }
})

// Computed: Combined Trend Chart Data (all 3 metrics together)
const combinedTrendChartData = computed(() => {
  if (!dashboardData.value?.trends) return null

  const impressions = dashboardData.value.trends.impressions
  const clicks = dashboardData.value.trends.clicks
  const conversions = dashboardData.value.trends.conversions

  if (!impressions || !Array.isArray(impressions)) return null

  const labels = impressions.map((item: any) => {
    const date = new Date(item.date)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  })

  return {
    labels,
    datasets: [
      {
        label: 'Impressions',
        data: impressions.map((item: any) => item.value),
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.4,
        yAxisID: 'y'
      },
      {
        label: 'Clicks',
        data: clicks?.map((item: any) => item.value) || [],
        borderColor: 'rgb(139, 92, 246)',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.4,
        yAxisID: 'y1'
      },
      {
        label: 'Conversions',
        data: conversions?.map((item: any) => item.value) || [],
        borderColor: 'rgb(249, 115, 22)',
        backgroundColor: 'rgba(249, 115, 22, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.4,
        yAxisID: 'y1'
      }
    ]
  }
})

// Chart options for combined trends (dual Y-axis)
const combinedTrendChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  plugins: {
    datalabels: {
      display: false
    },
    legend: {
      position: 'top' as const,
    }
  },
  scales: {
    y: {
      type: 'linear' as const,
      display: true,
      position: 'left' as const,
      title: {
        display: true,
        text: 'Impressions'
      }
    },
    y1: {
      type: 'linear' as const,
      display: true,
      position: 'right' as const,
      title: {
        display: true,
        text: 'Clicks / Conversions'
      },
      grid: {
        drawOnChartArea: false,
      },
    }
  }
}

// Computed: Campaigns Bar Chart Data
const campaignsChartData = computed(() => {
  if (!dashboardData.value?.top_campaigns) return null

  const labels = dashboardData.value.top_campaigns.map((c: any) => c.name)
  const spendData = dashboardData.value.top_campaigns.map((c: any) => c.spend)
  const conversionsData = dashboardData.value.top_campaigns.map((c: any) => c.conversions)

  return {
    labels,
    datasets: [
      {
        label: 'Spend (SAR)',
        data: spendData,
        backgroundColor: 'rgba(229, 62, 62, 0.8)',
        borderColor: 'rgb(229, 62, 62)',
        borderWidth: 1
      },
      {
        label: 'Conversions',
        data: conversionsData,
        backgroundColor: 'rgba(16, 185, 129, 0.8)',
        borderColor: 'rgb(16, 185, 129)',
        borderWidth: 1
      }
    ]
  }
})

// Chart options for campaigns
const campaignsChartOptions = {
  indexAxis: 'y' as const,
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
    datalabels: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context: any) => {
          const label = context.dataset.label || ''
          const value = context.parsed.x
          if (label.includes('Spend')) {
            return `${label}: ${formatCurrency(value)}`
          }
          return `${label}: ${formatNumber(value)}`
        }
      }
    }
  },
  scales: {
    x: {
      beginAtZero: true
    }
  }
}

const setActiveTab = (index: number) => {
  activeTabIndex.value = index
  const url = new URL(window.location.href)
  if (index === 0) {
    url.searchParams.delete('tab')
  } else {
    url.searchParams.set('tab', 'seo')
  }
  window.history.replaceState({}, '', url.toString())
}

const initializeTabFromURL = () => {
  const tab = new URLSearchParams(window.location.search).get('tab')
  if (tab === 'seo') {
    activeTabIndex.value = 1
  }
}

onMounted(() => {
  initializeTabFromURL()
  fetchDashboard()
})
</script>
