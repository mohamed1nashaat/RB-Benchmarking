<template>
  <div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center space-x-4">
      <button
        @click="$router.push('/ad-accounts')"
        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <ArrowLeftIcon class="h-4 w-4 mr-2" />
        {{ $t('pages.ad_accounts.back_to_accounts') }}
      </button>
      
      <div class="flex-1">
        <div class="flex items-center space-x-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ adAccount?.account_name }}</h1>
            <p class="text-sm text-gray-500">{{ adAccount?.platform }} • {{ adAccount?.external_account_id }}</p>
          </div>
          <span
            :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              adAccount?.status === 'active' 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
            ]"
          >
            {{ adAccount?.status }}
          </span>
        </div>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Date Filter -->
        <DateRangePicker
          :value="dateRangeValue"
          @change="onDateRangeChange"
        />

        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']"
            aria-hidden="true"
          />
          {{ $t('dashboard.refresh') }}
        </button>
      </div>
    </div>

    <!-- Active Date Filter Banner -->
    <div v-if="filterStartDate && filterEndDate" class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
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

    <!-- Manager Account Banner -->
    <div v-if="isManager" class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-medium text-purple-800">Manager Account (MCC)</h3>
          <div class="mt-2 text-sm text-purple-700">
            <p>This is a Google Ads Manager Account that oversees {{ childAccounts.length }} client accounts. The metrics below are aggregated from all child accounts.</p>
          </div>
          <div v-if="loadingManagerMetrics" class="mt-3">
            <div class="flex items-center text-sm text-purple-600">
              <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading aggregated metrics...
            </div>
          </div>
          <div v-else-if="managerMetrics" class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-md p-3 shadow-sm">
              <dt class="text-xs font-medium text-gray-500 truncate">Total Spend</dt>
              <dd class="mt-1 text-lg font-semibold text-green-600">{{ formatCurrency(managerMetrics.spend) }}</dd>
            </div>
            <div class="bg-white rounded-md p-3 shadow-sm">
              <dt class="text-xs font-medium text-gray-500 truncate">Impressions</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(managerMetrics.impressions) }}</dd>
            </div>
            <div class="bg-white rounded-md p-3 shadow-sm">
              <dt class="text-xs font-medium text-gray-500 truncate">Clicks</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(managerMetrics.clicks) }}</dd>
            </div>
            <div class="bg-white rounded-md p-3 shadow-sm">
              <dt class="text-xs font-medium text-gray-500 truncate">Conversions</dt>
              <dd class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(managerMetrics.conversions) }}</dd>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Child Accounts Table (for Manager Accounts) -->
    <div v-if="isManager && childAccounts.length > 0" class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Child Accounts ({{ childAccounts.length }})</h3>
        <p class="mt-1 text-sm text-gray-500">Client accounts managed by this MCC</p>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Spend (SAR)</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
              <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="child in childAccounts" :key="child.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ child.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">{{ formatCurrency(child.spend) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ formatNumber(child.impressions) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ formatNumber(child.clicks) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                <router-link
                  :to="`/ad-accounts/${child.id}`"
                  class="text-primary-600 hover:text-primary-900 font-medium"
                >
                  View Details
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Account Details Card -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $t('pages.ad_accounts.account_info') }}</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Account details and classification
        </p>
      </div>
      <div class="border-t border-gray-200">
        <dl>
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('pages.ad_accounts.external_account_id') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ adAccount?.external_account_id }}</dd>
          </div>
          <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('labels.platform') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 capitalize">{{ adAccount?.platform }}</dd>
          </div>
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('labels.industry') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
              <div class="flex items-center space-x-2">
                <select
                  v-model="editableAccount.industry"
                  @change="updateAccountInfo"
                  class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">{{ $t('placeholders.select_industry') }}</option>
                  <option v-for="industry in availableIndustries" :key="industry" :value="industry">
                    {{ formatIndustry(industry) }}
                  </option>
                </select>
              </div>
            </dd>
          </div>
          <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('labels.category') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
              <div class="flex items-center space-x-2">
                <select
                  v-model="editableAccount.category"
                  @change="updateAccountInfo"
                  class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select Category</option>
                  <option v-for="cat in availableCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>
            </dd>
          </div>
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('labels.currency') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ adAccount?.currency || 'USD' }}</dd>
          </div>
          <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">{{ $t('pages.ad_accounts.total_campaigns') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ campaigns.length }}</dd>
          </div>
          <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Total Spend (SAR)</dt>
            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
              <!-- Show aggregated metrics for manager accounts -->
              <span v-if="isManager && managerMetrics" class="font-semibold text-green-600">{{ formatCurrency(managerMetrics.spend) }}</span>
              <span v-else class="font-semibold text-green-600">{{ formatCurrency(totalSpendSAR) }}</span>
              <span v-if="isManager" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                Aggregated from {{ childAccounts.length }} accounts
              </span>
              <span v-else-if="filterStartDate && filterEndDate" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                <CalendarIcon class="h-3 w-3 mr-1" />
                {{ filterStartDate }} to {{ filterEndDate }}
              </span>
              <span v-else class="ml-2 text-xs text-gray-400">(All Time)</span>
            </dd>
          </div>
          <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm font-medium text-gray-500">Last Metrics Sync</dt>
            <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
              <span v-if="adAccount?.last_metrics_sync_at" class="text-gray-900">
                {{ formatDateTime(adAccount.last_metrics_sync_at) }}
                <span class="text-gray-500 text-xs ml-2">({{ formatRelativeTime(adAccount.last_metrics_sync_at) }})</span>
              </span>
              <span v-else class="text-gray-400 italic">Never synced</span>
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Campaigns Section -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $t('pages.ad_accounts.campaigns_section') }}</h3>
            <p class="mt-1 text-sm text-gray-500">
              Configure campaign objectives, funnel stages, and user journeys
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <ColumnToggle
              :columns="tableColumns"
              :storage-key="COLUMNS_STORAGE_KEY"
              v-model="visibleColumns"
            />
            <!-- LinkedIn/Google/Snapchat/TikTok: Single Sync All button -->
            <template v-if="isLinkedIn || isGoogle || isSnapchat || isTikTok">
              <button
                @click="showMetricsSyncModal = true"
                :disabled="syncing || syncingMetrics"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
              >
                <ArrowPathIcon
                  :class="['h-4 w-4 mr-2', (syncing || syncingMetrics) ? 'animate-spin' : '']"
                />
                {{ (syncing || syncingMetrics) ? 'Syncing...' : 'Sync All' }}
              </button>
            </template>

            <!-- Other platforms: Separate buttons -->
            <template v-else>
              <button
                @click="syncCampaigns"
                :disabled="syncing"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
              >
                <ArrowPathIcon
                  :class="['h-4 w-4 mr-2', syncing ? 'animate-spin' : '']"
                />
                {{ syncing ? 'Syncing...' : 'Sync Campaigns' }}
              </button>

              <!-- Sync Metrics with Date Range -->
              <div class="relative">
                <button
                  @click="showMetricsSyncModal = true"
                  :disabled="syncingMetrics"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                >
                  <CloudArrowDownIcon
                    :class="['h-4 w-4 mr-2', syncingMetrics ? 'animate-pulse' : '']"
                  />
                  {{ syncingMetrics ? 'Syncing Metrics...' : 'Sync Metrics' }}
                </button>
              </div>
            </template>
          </div>
        </div>

        <!-- LinkedIn Level Tabs -->
        <div v-if="isLinkedIn" class="mt-4 border-b border-gray-200">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
              v-for="tab in linkedinTabs"
              :key="tab.id"
              @click="selectedLinkedInLevel = tab.id"
              :class="[
                selectedLinkedInLevel === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.name }}
              <span
                :class="[
                  selectedLinkedInLevel === tab.id
                    ? 'bg-primary-100 text-primary-600'
                    : 'bg-gray-100 text-gray-900',
                  'ml-2 py-0.5 px-2 rounded-full text-xs font-medium'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Google Level Tabs -->
        <div v-if="isGoogle" class="mt-4 border-b border-gray-200">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
              v-for="tab in googleTabs"
              :key="tab.id"
              @click="selectedGoogleLevel = tab.id"
              :class="[
                selectedGoogleLevel === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.name }}
              <span
                :class="[
                  selectedGoogleLevel === tab.id
                    ? 'bg-primary-100 text-primary-600'
                    : 'bg-gray-100 text-gray-900',
                  'ml-2 py-0.5 px-2 rounded-full text-xs font-medium'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Metrics Sync Modal -->
      <div v-if="showMetricsSyncModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showMetricsSyncModal = false"></div>
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
          <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <CloudArrowDownIcon class="h-6 w-6 text-green-600" />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  {{ isLinkedIn ? 'Sync LinkedIn Data' : (isGoogle ? 'Sync Google Ads Data' : (isTikTok ? 'Sync TikTok Data' : (isSnapchat ? 'Sync Snapchat Data' : 'Sync Metrics Data'))) }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                  <template v-if="isLinkedIn">
                    Syncs all levels (Campaign Groups, Ad Sets, Creatives) and their metrics
                  </template>
                  <template v-else-if="isGoogle">
                    Syncs all campaigns and their metrics from Google Ads
                  </template>
                  <template v-else-if="isSnapchat">
                    Syncs all campaigns and their metrics from Snapchat
                  </template>
                  <template v-else-if="isTikTok">
                    Syncs all campaigns and their metrics from TikTok Ads
                  </template>
                  <template v-else>
                    Select a date range to sync metrics from {{ adAccount?.platform }}
                  </template>
                </p>
              </div>
            </div>
            <div class="mt-5 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">From Date</label>
                  <input
                    type="date"
                    v-model="metricsSyncStartDate"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">To Date</label>
                  <input
                    type="date"
                    v-model="metricsSyncEndDate"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <button @click="setMetricsDateRange('last7')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 7 days</button>
                <button @click="setMetricsDateRange('last30')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 30 days</button>
                <button @click="setMetricsDateRange('last90')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last 90 days</button>
                <button @click="setMetricsDateRange('thisMonth')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">This month</button>
                <button @click="setMetricsDateRange('lastMonth')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">Last month</button>
                <button @click="setMetricsDateRange('thisYear')" class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">This year</button>
                <button
                  @click="setMetricsDateRange('allTime')"
                  :class="['px-2 py-1 text-xs rounded font-medium', syncAllTime ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200']"
                >
                  All Time
                </button>
              </div>
              <p v-if="syncAllTime" class="text-xs text-amber-600 mt-2">
                <span v-if="adAccount?.platform === 'facebook'">Facebook limits historical data to 37 months.</span>
                <span v-else>Syncing all available historical data.</span>
              </p>

              <!-- Progress Bar and Log View -->
              <div v-if="syncingMetrics || syncLogs.length > 0" class="mt-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-green-700">
                    {{ syncingMetrics ? 'Syncing metrics...' : 'Sync completed' }}
                  </span>
                  <span class="text-sm text-gray-500">{{ syncElapsedTime }}</span>
                </div>

                <!-- Progress Bar -->
                <div v-if="syncingMetrics" class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden mb-3">
                  <div class="h-full bg-gradient-to-r from-green-400 via-green-500 to-green-600 rounded-full relative overflow-hidden transition-all duration-300" :style="{ width: syncProgress + '%' }">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                  </div>
                </div>

                <!-- Log View -->
                <div class="bg-gray-900 rounded-lg p-3 max-h-48 overflow-y-auto font-mono text-xs">
                  <div v-for="(log, index) in syncLogs" :key="index" class="flex items-start gap-2 mb-1">
                    <span class="text-gray-500 shrink-0">{{ log.time }}</span>
                    <span :class="{
                      'text-green-400': log.type === 'success',
                      'text-yellow-400': log.type === 'warning',
                      'text-red-400': log.type === 'error',
                      'text-blue-400': log.type === 'info',
                      'text-gray-300': log.type === 'default'
                    }">{{ log.message }}</span>
                  </div>
                  <div v-if="syncingMetrics" class="flex items-center gap-2 text-gray-400">
                    <span class="animate-pulse">▌</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense" v-if="!syncingMetrics">
              <button
                @click="isLinkedIn ? syncAllLinkedIn() : (isGoogle ? syncAllGoogle() : (isSnapchat ? syncAllSnapchat() : (isTikTok ? syncAllTikTok() : syncMetrics())))"
                :disabled="syncingMetrics || (!syncAllTime && (!metricsSyncStartDate || !metricsSyncEndDate))"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
              >
                {{ (isLinkedIn || isGoogle || isSnapchat || isTikTok) ? (syncAllTime ? 'Sync All (Full History)' : 'Sync All') : (syncAllTime ? 'Sync All Time' : 'Start Sync') }}
              </button>
              <button
                @click="showMetricsSyncModal = false"
                :disabled="syncingMetrics"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:col-start-1 sm:text-sm"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Actions Bar -->
      <div v-if="selectedCampaigns.size > 0"
           class="mx-4 mb-4 p-3 bg-primary-50 border border-primary-200 rounded-lg flex items-center gap-4">
        <span class="text-sm font-medium text-primary-700">
          {{ selectedCampaigns.size }} campaign(s) selected
        </span>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600">Campaign Category:</label>
          <select v-model="bulkCategory"
                  class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
            <option value="">Select category...</option>
            <option v-for="cat in campaignCategories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <button @click="bulkUpdateCategory(bulkCategory)"
                  :disabled="!bulkCategory"
                  class="px-3 py-1.5 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
            Apply
          </button>
        </div>
        <button @click="selectedCampaigns = new Set(); selectAll = false"
                class="ml-auto text-sm text-gray-500 hover:text-gray-700">
          Clear selection
        </button>
      </div>

      <!-- Campaigns Table -->
      <div class="overflow-auto max-h-[600px] relative">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 sticky top-0 z-20 shadow-sm">
            <tr>
              <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50 w-10">
                <input type="checkbox" :checked="selectAll" @change="toggleSelectAll"
                       class="h-4 w-4 text-primary-600 border-gray-300 rounded cursor-pointer" />
              </th>
              <th v-if="visibleColumns.name" scope="col" @click="sortBy('name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50 sticky left-0 z-30 min-w-[250px] border-r border-gray-200">
                <div class="flex items-center space-x-1">
                  <span>Campaign Name</span>
                  <ChevronUpDownIcon v-if="sortField !== 'name'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.spend_original" scope="col" @click="sortBy('spend_original')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Spend</span>
                  <ChevronUpDownIcon v-if="sortField !== 'spend_original'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.spend_sar" scope="col" @click="sortBy('spend_sar')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>SAR</span>
                  <ChevronUpDownIcon v-if="sortField !== 'spend_sar'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.objective" scope="col" @click="sortBy('objective')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Objective</span>
                  <ChevronUpDownIcon v-if="sortField !== 'objective'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.sub_industry" scope="col" @click="sortBy('sub_industry')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Category</span>
                  <ChevronUpDownIcon v-if="sortField !== 'sub_industry'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.user_journey" scope="col" @click="sortBy('user_journey')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Journey</span>
                  <ChevronUpDownIcon v-if="sortField !== 'user_journey'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.pixel_data" scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                Pixel
              </th>
              <th v-if="visibleColumns.target_segment" scope="col" @click="sortBy('target_segment')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Segment</span>
                  <ChevronUpDownIcon v-if="sortField !== 'target_segment'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.age_group" scope="col" @click="sortBy('age_group')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Age</span>
                  <ChevronUpDownIcon v-if="sortField !== 'age_group'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.geo_targeting" scope="col" @click="sortBy('geo_targeting')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Geo</span>
                  <ChevronUpDownIcon v-if="sortField !== 'geo_targeting'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.messaging_tone" scope="col" @click="sortBy('messaging_tone')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Tone</span>
                  <ChevronUpDownIcon v-if="sortField !== 'messaging_tone'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.status" scope="col" @click="sortBy('status')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                <div class="flex items-center space-x-1">
                  <span>Status</span>
                  <ChevronUpDownIcon v-if="sortField !== 'status'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.actions" scope="col" class="relative px-4 py-3 bg-gray-50">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading" class="animate-pulse">
              <td :colspan="visibleColumnCount + 1" class="px-6 py-4 text-center text-sm text-gray-500">
                Loading campaigns...
              </td>
            </tr>
            <tr v-else-if="campaigns.length === 0">
              <td :colspan="visibleColumnCount + 1" class="px-6 py-4 text-center text-sm text-gray-500">
                {{ $t('pages.ad_accounts.no_campaigns', { platform: adAccount?.platform }) }}
              </td>
            </tr>
            <tr v-else v-for="campaign in sortedCampaigns" :key="campaign.id" class="group hover:bg-gray-50">
              <td class="px-2 py-3 whitespace-nowrap">
                <input type="checkbox"
                       :checked="selectedCampaigns.has(campaign.id)"
                       @change="toggleCampaignSelection(campaign.id)"
                       class="h-4 w-4 text-primary-600 border-gray-300 rounded cursor-pointer" />
              </td>
              <td v-if="visibleColumns.name" class="px-4 py-3 whitespace-nowrap bg-white group-hover:bg-gray-50 sticky left-0 z-10 min-w-[250px] border-r border-gray-100">
                <div
                  class="text-sm font-medium text-primary-600 hover:text-primary-800 cursor-pointer hover:underline"
                  @click="viewCampaignMetrics(campaign)"
                >
                  {{ campaign.name }}
                </div>
                <div class="text-xs text-gray-500">ID: {{ campaign.external_campaign_id }}</div>
              </td>
              <td v-if="visibleColumns.spend_original" class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm font-semibold text-blue-600">
                  {{ formatCurrencyOriginal(campaign.total_spend || 0, campaign.currency) }}
                </span>
              </td>
              <td v-if="visibleColumns.spend_sar" class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm font-semibold text-green-600">
                  {{ formatCurrency(campaign.total_spend_sar || 0) }}
                </span>
              </td>
              <td v-if="visibleColumns.objective" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.funnel_stage"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="TOF">TOF</option>
                  <option value="MOF">MOF</option>
                  <option value="BOF">BOF</option>
                </select>
              </td>
              <td v-if="visibleColumns.sub_industry" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.sub_industry"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option v-for="category in campaignCategories" :key="category" :value="category">
                    {{ category }}
                  </option>
                  <option v-if="campaignCategories.length === 0" disabled>-</option>
                </select>
              </td>
              <td v-if="visibleColumns.user_journey" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.user_journey"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="instant_form">Instant Form</option>
                  <option value="landing_page">Landing Page</option>
                </select>
              </td>
              <td v-if="visibleColumns.pixel_data" class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                  <input
                    type="checkbox"
                    v-model="campaign.has_pixel_data"
                    @change="updateCampaign(campaign)"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  >
                  <label class="ml-2 text-xs text-gray-700">
                    {{ campaign.has_pixel_data ? 'Yes' : 'No' }}
                  </label>
                </div>
              </td>
              <!-- Target Segment -->
              <td v-if="visibleColumns.target_segment" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.target_segment"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="luxury">Luxury</option>
                  <option value="premium">Premium</option>
                  <option value="mid_class">Mid Class</option>
                  <option value="value">Value</option>
                  <option value="mass_market">Mass</option>
                  <option value="niche">Niche</option>
                </select>
              </td>
              <!-- Age Group -->
              <td v-if="visibleColumns.age_group" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.age_group"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="gen_z">Gen Z</option>
                  <option value="millennials">Millennials</option>
                  <option value="gen_x">Gen X</option>
                  <option value="boomers">Boomers</option>
                  <option value="mixed_age">Mixed</option>
                </select>
              </td>
              <!-- Geo Targeting -->
              <td v-if="visibleColumns.geo_targeting" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.geo_targeting"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="local">Local</option>
                  <option value="regional">Regional</option>
                  <option value="national">National</option>
                  <option value="international">Intl</option>
                </select>
              </td>
              <!-- Messaging Tone -->
              <td v-if="visibleColumns.messaging_tone" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-model="campaign.messaging_tone"
                  @change="updateCampaign(campaign)"
                  class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">Select</option>
                  <option value="professional">Professional</option>
                  <option value="casual">Casual</option>
                  <option value="luxury">Luxury</option>
                  <option value="urgent">Urgent</option>
                  <option value="educational">Educational</option>
                  <option value="emotional">Emotional</option>
                </select>
              </td>
              <td v-if="visibleColumns.status" class="px-4 py-3 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                    campaign.status === 'active'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ campaign.status }}
                </span>
              </td>
              <td v-if="visibleColumns.actions" class="px-4 py-3 whitespace-nowrap text-right text-xs font-medium">
                <button
                  @click="viewCampaignMetrics(campaign)"
                  class="text-primary-600 hover:text-primary-900"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
          <!-- Totals Row -->
          <tfoot v-if="campaigns.length > 0" class="bg-gray-100 font-semibold sticky bottom-0">
            <tr>
              <td class="px-2 py-3 bg-gray-100"></td>
              <td v-if="visibleColumns.name" class="px-4 py-3 whitespace-nowrap bg-gray-100 sticky left-0 z-10 border-r border-gray-200 text-sm text-gray-900">
                Total ({{ campaigns.length }} items)
              </td>
              <td v-if="visibleColumns.spend_original" class="px-4 py-3 whitespace-nowrap text-sm text-blue-700">
                {{ formatCurrencyOriginal(totalSpendOriginal, adAccount?.currency) }}
              </td>
              <td v-if="visibleColumns.spend_sar" class="px-4 py-3 whitespace-nowrap text-sm text-green-700">
                {{ formatCurrency(totalSpendSAR) }}
              </td>
              <td v-if="visibleColumns.objective" class="px-4 py-3"></td>
              <td v-if="visibleColumns.sub_industry" class="px-4 py-3"></td>
              <td v-if="visibleColumns.user_journey" class="px-4 py-3"></td>
              <td v-if="visibleColumns.pixel_data" class="px-4 py-3"></td>
              <td v-if="visibleColumns.target_segment" class="px-4 py-3"></td>
              <td v-if="visibleColumns.age_group" class="px-4 py-3"></td>
              <td v-if="visibleColumns.geo_targeting" class="px-4 py-3"></td>
              <td v-if="visibleColumns.messaging_tone" class="px-4 py-3"></td>
              <td v-if="visibleColumns.status" class="px-4 py-3"></td>
              <td v-if="visibleColumns.actions" class="px-4 py-3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, ArrowPathIcon, CloudArrowDownIcon, ChevronUpIcon, ChevronDownIcon, ChevronUpDownIcon, CalendarIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { getUniqueIndustries, type AdAccount } from '@/utils/industryAggregator'
import { getCategoriesForIndustry } from '@/utils/categoryMapper'
import ColumnToggle from '@/components/ColumnToggle.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'

const route = useRoute()
const router = useRouter()
const accountId = computed(() => route.params.id)

// State
const loading = ref(true)
const syncing = ref(false)
const syncingMetrics = ref(false)
const syncAllTime = ref(false)
const syncLogs = ref<{ time: string; message: string; type: string }[]>([])
const syncProgress = ref(0)
const syncStartTime = ref<number | null>(null)
const syncElapsedTime = ref('')
let syncTimer: any = null
const adAccount = ref<any>(null)
const campaigns = ref<any[]>([])
const availableIndustries = ref<string[]>([])
const availableSubIndustries = ref<string[]>([])
const availableCategories = ref<string[]>([])

// Bulk selection state
const selectedCampaigns = ref<Set<number>>(new Set())
const selectAll = ref(false)
const bulkCategory = ref('')

// LinkedIn level tabs
const selectedLinkedInLevel = ref<'campaign_groups' | 'ad_sets' | 'ads'>('ad_sets')

// Google level tabs
const selectedGoogleLevel = ref<'campaigns' | 'ad_groups' | 'ads'>('campaigns')

// Check if account is LinkedIn
const isLinkedIn = computed(() => {
  return adAccount.value?.platform === 'linkedin'
})

const isGoogle = computed(() => {
  return adAccount.value?.platform === 'google'
})

const isSnapchat = computed(() => {
  return adAccount.value?.platform === 'snapchat'
})

const isTikTok = computed(() => {
  return adAccount.value?.platform === 'tiktok'
})

// Manager account (MCC) detection
const isManager = computed(() => {
  const config = adAccount.value?.account_config
  return config?.is_manager === true
})

// Aggregated metrics for manager accounts
const managerMetrics = ref<any>(null)
const childAccounts = ref<any[]>([])
const loadingManagerMetrics = ref(false)

// LinkedIn tabs configuration
const linkedinTabs = computed(() => [
  { id: 'campaign_groups', name: 'Campaign Groups', count: campaigns.value.filter(c => c.linkedin_level === 'campaign_group').length || 0 },
  { id: 'ad_sets', name: 'Ad Sets', count: campaigns.value.filter(c => c.linkedin_level === 'ad_set' || !c.linkedin_level).length || campaigns.value.length },
  { id: 'ads', name: 'Ads', count: campaigns.value.filter(c => c.linkedin_level === 'creative').length || 0 }
])

// Google tabs configuration
const googleTabs = computed(() => [
  { id: 'campaigns', name: 'Campaigns', count: campaigns.value.filter(c => c.google_level === 'campaign' || !c.google_level).length },
  { id: 'ad_groups', name: 'Ad Groups', count: campaigns.value.filter(c => c.google_level === 'ad_group').length },
  { id: 'ads', name: 'Ads', count: campaigns.value.filter(c => c.google_level === 'ad').length }
])

// Filter campaigns by selected level (for LinkedIn and Google accounts)
const filteredCampaigns = computed(() => {
  // LinkedIn filtering
  if (isLinkedIn.value) {
    const levelMap: Record<string, string> = {
      'campaign_groups': 'campaign_group',
      'ad_sets': 'ad_set',
      'ads': 'creative'
    }
    const targetLevel = levelMap[selectedLinkedInLevel.value]

    return campaigns.value.filter(c => {
      if (selectedLinkedInLevel.value === 'ad_sets') {
        return c.linkedin_level === 'ad_set' || !c.linkedin_level
      }
      return c.linkedin_level === targetLevel
    })
  }

  // Google filtering
  if (isGoogle.value) {
    const levelMap: Record<string, string> = {
      'campaigns': 'campaign',
      'ad_groups': 'ad_group',
      'ads': 'ad'
    }
    const targetLevel = levelMap[selectedGoogleLevel.value]

    return campaigns.value.filter(c => {
      if (selectedGoogleLevel.value === 'campaigns') {
        return c.google_level === 'campaign' || !c.google_level
      }
      return c.google_level === targetLevel
    })
  }

  return campaigns.value
})

// Get selected level name for button
const selectedLevelName = computed(() => {
  const names: Record<string, string> = {
    'campaign_groups': 'Campaign Groups',
    'ad_sets': 'Ad Sets',
    'ads': 'Ads'
  }
  return names[selectedLinkedInLevel.value] || 'Campaigns'
})

// Metrics sync state
const showMetricsSyncModal = ref(false)
const metricsSyncStartDate = ref('')
const metricsSyncEndDate = ref('')

// Date filter state
const filterStartDate = ref('')
const filterEndDate = ref('')

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
  loadAccountData()
}

// Editable account info
const editableAccount = ref({
  industry: '',
  category: ''
})

// Campaign categories (loaded from API based on account's industry)
const campaignCategories = ref<string[]>([])

// Load campaign categories from API based on account's industry
const loadCampaignCategories = async () => {
  try {
    const industry = adAccount.value?.industry || editableAccount.value.industry
    if (!industry) {
      campaignCategories.value = []
      return
    }

    // Fetch all industries with their campaign categories
    const response = await window.axios.get('/api/industries')
    const industries = response.data.data || []

    // Find the matching industry and get its campaign categories
    const matchingIndustry = industries.find((ind: any) => ind.name === industry)
    if (matchingIndustry && matchingIndustry.campaign_categories) {
      campaignCategories.value = matchingIndustry.campaign_categories.map((cat: any) => cat.display_name)
    } else {
      campaignCategories.value = []
    }
  } catch (error) {
    console.error('Error loading campaign categories:', error)
    campaignCategories.value = []
  }
}

// Column visibility configuration
const COLUMNS_STORAGE_KEY = 'ad_account_detail_visible_columns'

const tableColumns = [
  { key: 'name', label: 'Campaign Name', defaultVisible: true },
  { key: 'spend_original', label: 'Spend (Original)', defaultVisible: true },
  { key: 'spend_sar', label: 'Spend (SAR)', defaultVisible: true },
  { key: 'objective', label: 'Objective', defaultVisible: true },
  { key: 'sub_industry', label: 'Category', defaultVisible: true },
  { key: 'user_journey', label: 'User Journey', defaultVisible: false },
  { key: 'pixel_data', label: 'Pixel Data', defaultVisible: false },
  { key: 'target_segment', label: 'Target Segment', defaultVisible: false },
  { key: 'age_group', label: 'Age Group', defaultVisible: false },
  { key: 'geo_targeting', label: 'Geo Targeting', defaultVisible: false },
  { key: 'messaging_tone', label: 'Message Tone', defaultVisible: false },
  { key: 'status', label: 'Status', defaultVisible: true },
    { key: 'actions', label: 'Actions', defaultVisible: true }
]

// Load saved column visibility or use defaults
const loadColumnVisibility = () => {
  try {
    const saved = localStorage.getItem(COLUMNS_STORAGE_KEY)
    if (saved) {
      return JSON.parse(saved)
    }
  } catch (e) {
    console.error('Error loading column visibility:', e)
  }
  // Return defaults
  const defaults: Record<string, boolean> = {}
  tableColumns.forEach(col => {
    defaults[col.key] = col.defaultVisible !== false
  })
  return defaults
}

const visibleColumns = ref<Record<string, boolean>>(loadColumnVisibility())

// Compute visible column count for colspan
const visibleColumnCount = computed(() => {
  return Object.values(visibleColumns.value).filter(Boolean).length
})

// Compute total spend in SAR from filtered campaigns
const totalSpendSAR = computed(() => {
  return filteredCampaigns.value.reduce((sum, campaign) => {
    return sum + (parseFloat(campaign.total_spend_sar) || 0)
  }, 0)
})

// Compute total spend in original currency from filtered campaigns
const totalSpendOriginal = computed(() => {
  return filteredCampaigns.value.reduce((sum, campaign) => {
    return sum + (parseFloat(campaign.total_spend) || 0)
  }, 0)
})

// Sorting state
const sortField = ref<string>('name')
const sortDirection = ref<'asc' | 'desc'>('asc')

// Sorted campaigns computed property (uses filtered campaigns for LinkedIn)
const sortedCampaigns = computed(() => {
  const sorted = [...filteredCampaigns.value]

  sorted.sort((a, b) => {
    let aVal: any
    let bVal: any

    // Handle different field types
    switch (sortField.value) {
      case 'spend_original':
      case 'total_spend':
        aVal = parseFloat(a.total_spend) || 0
        bVal = parseFloat(b.total_spend) || 0
        break
      case 'spend_sar':
      case 'total_spend_sar':
        aVal = parseFloat(a.total_spend_sar) || 0
        bVal = parseFloat(b.total_spend_sar) || 0
        break
      case 'name':
        aVal = (a.name || '').toLowerCase()
        bVal = (b.name || '').toLowerCase()
        break
      case 'status':
        aVal = (a.status || '').toLowerCase()
        bVal = (b.status || '').toLowerCase()
        break
      case 'objective':
        aVal = (a.objective || '').toLowerCase()
        bVal = (b.objective || '').toLowerCase()
        break
      default:
        aVal = a[sortField.value] ?? ''
        bVal = b[sortField.value] ?? ''
        if (typeof aVal === 'string') aVal = aVal.toLowerCase()
        if (typeof bVal === 'string') bVal = bVal.toLowerCase()
    }

    // Compare values
    if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1
    if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })

  return sorted
})

// Sort by field function
const sortBy = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

// Load account data
const loadAccountData = async () => {
  loading.value = true
  try {
    
    // Load account details
    const accountResponse = await window.axios.get(`/api/ad-accounts/${accountId.value}`)
    adAccount.value = accountResponse.data.data
    
    // Update editable fields
    editableAccount.value = {
      industry: adAccount.value.industry || '',
      category: adAccount.value.category || ''
    }

    // Load available categories for the account's industry
    availableCategories.value = adAccount.value.available_categories || getCategoriesForIndustry(adAccount.value.industry) || []
    
    // Load campaigns for this account
    try {
      const params: any = { account_id: accountId.value }
      if (filterStartDate.value) {
        params.start_date = filterStartDate.value
      }
      if (filterEndDate.value) {
        params.end_date = filterEndDate.value
      }
      const campaignResponse = await window.axios.get('/api/ad-campaigns', { params })
      campaigns.value = campaignResponse.data.data || []
    } catch (campaignError) {
      console.error('Error loading campaigns:', campaignError)
      campaigns.value = []
    }
    
    // Load available industries from ad accounts
    try {
      const allAccountsResponse = await window.axios.get('/api/ad-accounts')
      const allAccounts: AdAccount[] = allAccountsResponse.data.data || []
      availableIndustries.value = getUniqueIndustries(allAccounts)
    } catch (industriesError) {
      console.error('Error loading industries from ad accounts:', industriesError)
      // Fallback to common industries
      availableIndustries.value = ['automotive', 'technology', 'retail', 'finance', 'healthcare', 'travel_tourism', 'other']
    }
    
    // Load sub-industries
    await loadSubIndustries()

    // Load campaign categories from industry management
    await loadCampaignCategories()

    // Load aggregated metrics for manager accounts
    await loadManagerMetrics()

  } catch (error) {
    console.error('Error loading account data:', error)
    alert('Failed to load account data. Please try refreshing the page.')
  } finally {
    loading.value = false
  }
}

// Load categories based on selected industry from categoryMapper
const loadSubIndustries = async () => {
  try {
    const industry = adAccount.value?.industry || editableAccount.value.industry
    if (industry) {
      availableSubIndustries.value = getCategoriesForIndustry(industry)
    } else {
      availableSubIndustries.value = []
    }
  } catch (error) {
    console.error('Error loading categories:', error)
    availableSubIndustries.value = []
  }
}

// Load aggregated metrics for manager accounts (MCC)
const loadManagerMetrics = async () => {
  const config = adAccount.value?.account_config
  if (!config?.is_manager) {
    managerMetrics.value = null
    childAccounts.value = []
    return
  }

  loadingManagerMetrics.value = true
  try {
    const params: any = {}
    if (filterStartDate.value) {
      params.start_date = filterStartDate.value
    }
    if (filterEndDate.value) {
      params.end_date = filterEndDate.value
    }

    const response = await window.axios.get(`/api/ad-accounts/${accountId.value}/aggregated-metrics`, { params })
    managerMetrics.value = response.data.aggregated
    childAccounts.value = response.data.child_accounts || []
  } catch (error) {
    console.error('Error loading manager metrics:', error)
    managerMetrics.value = null
    childAccounts.value = []
  } finally {
    loadingManagerMetrics.value = false
  }
}

// Update account info
const updateAccountInfo = async () => {
  try {

    const response = await window.axios.put(`/api/ad-accounts/${accountId.value}`, {
      industry: editableAccount.value.industry,
      category: editableAccount.value.category
    })


    // Update local data
    adAccount.value.industry = editableAccount.value.industry
    adAccount.value.category = editableAccount.value.category

    // Reload sub-industries and campaign categories when industry changes
    if (editableAccount.value.industry) {
      await loadSubIndustries()
      await loadCampaignCategories()
      // Update available categories for the new industry
      availableCategories.value = getCategoriesForIndustry(editableAccount.value.industry) || []
    }


  } catch (error) {
    console.error('Error updating account:', error)
    alert(`Failed to update account information: ${error.response?.data?.message || error.message}`)

    // Revert changes on error
    editableAccount.value = {
      industry: adAccount.value.industry || '',
      category: adAccount.value.category || ''
    }
  }
}

// Update campaign
const updateCampaign = async (campaign: any) => {
  try {
    const response = await window.axios.put(`/api/ad-campaigns/${campaign.id}`, {
      objective: campaign.objective,
      sub_industry: campaign.sub_industry,
      funnel_stage: campaign.funnel_stage,
      user_journey: campaign.user_journey,
      has_pixel_data: campaign.has_pixel_data,
      target_segment: campaign.target_segment,
      age_group: campaign.age_group,
      geo_targeting: campaign.geo_targeting,
      messaging_tone: campaign.messaging_tone
    })
  } catch (error) {
    console.error('Error updating campaign:', error)
    alert(`Failed to update campaign "${campaign.name}": ${error.response?.data?.message || error.message}`)

    // Revert the change on error by reloading data
    await loadAccountData()
  }
}

// Bulk selection methods
const toggleCampaignSelection = (campaignId: number) => {
  if (selectedCampaigns.value.has(campaignId)) {
    selectedCampaigns.value.delete(campaignId)
  } else {
    selectedCampaigns.value.add(campaignId)
  }
  selectedCampaigns.value = new Set(selectedCampaigns.value) // trigger reactivity
  selectAll.value = selectedCampaigns.value.size === sortedCampaigns.value.length
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedCampaigns.value = new Set()
  } else {
    selectedCampaigns.value = new Set(sortedCampaigns.value.map(c => c.id))
  }
  selectAll.value = !selectAll.value
}

const bulkUpdateCategory = async (category: string) => {
  if (selectedCampaigns.value.size === 0) return

  try {
    const promises = Array.from(selectedCampaigns.value).map(id => {
      const campaign = campaigns.value.find(c => c.id === id)
      if (campaign) {
        campaign.sub_industry = category
        return window.axios.put(`/api/ad-campaigns/${id}`, {
          objective: campaign.objective,
          sub_industry: category,
          funnel_stage: campaign.funnel_stage,
          user_journey: campaign.user_journey,
          has_pixel_data: campaign.has_pixel_data,
          target_segment: campaign.target_segment,
          age_group: campaign.age_group,
          geo_targeting: campaign.geo_targeting,
          messaging_tone: campaign.messaging_tone
        })
      }
    })
    await Promise.all(promises)
    selectedCampaigns.value = new Set()
    selectAll.value = false
    bulkCategory.value = ''
  } catch (error) {
    console.error('Error bulk updating campaigns:', error)
    alert('Failed to update some campaigns')
    await loadAccountData()
  }
}

// Sync campaigns from platform
const syncCampaigns = async () => {
  syncing.value = true
  try {
    const payload: any = {
      ad_account_id: accountId.value
    }

    // For LinkedIn, pass the selected level
    if (isLinkedIn.value) {
      payload.linkedin_level = selectedLinkedInLevel.value
    }

    const response = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-campaigns`, payload)

    if (response.data.status === 'success') {
      // Reload campaigns after sync
      const campaignResponse = await window.axios.get('/api/ad-campaigns', {
        params: { account_id: accountId.value }
      })
      campaigns.value = campaignResponse.data.data || []

      const levelName = isLinkedIn.value ? selectedLevelName.value : 'campaigns'
      alert(`Sync completed successfully! Found ${response.data.data?.length || campaigns.value.length} ${levelName}.`)
    } else {
      alert('Sync completed but may not have found new items.')
    }
  } catch (error) {
    console.error('Error syncing campaigns:', error)
    alert(`Failed to sync: ${error.response?.data?.message || error.message}`)
  } finally {
    syncing.value = false
  }
}

// Sync all LinkedIn levels (campaign_groups, ad_sets, ads) and then metrics
const syncAllLinkedIn = async () => {
  if (!syncAllTime.value && (!metricsSyncStartDate.value || !metricsSyncEndDate.value)) {
    alert('Please select both start and end dates or choose All Time')
    return
  }

  // Keep modal open to show progress
  syncing.value = true
  syncingMetrics.value = true

  // Reset and initialize logging
  syncLogs.value = []
  syncProgress.value = 0
  syncStartTime.value = Date.now()
  syncElapsedTime.value = '0:00'
  progressStageIndex = 0

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const dateRange = syncAllTime.value ? 'All Time' : `${metricsSyncStartDate.value} to ${metricsSyncEndDate.value}`

    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Starting LinkedIn Full Sync`, 'info')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Account: ${adAccount.value.account_name}`, 'default')
    addSyncLog(`Date Range: ${dateRange}`, 'default')
    addSyncLog(`───────────────────────────────────────`, 'default')

    syncProgress.value = 5

    // Step 1: Sync all 3 LinkedIn levels
    const levels = ['ad_sets', 'campaign_groups', 'ads'] as const
    const levelNames = { ad_sets: 'Ad Sets', campaign_groups: 'Campaign Groups', ads: 'Creatives' }

    for (let i = 0; i < levels.length; i++) {
      const level = levels[i]
      addSyncLog(`Syncing ${levelNames[level]}...`, 'info')
      syncProgress.value = 5 + (i + 1) * 10

      try {
        const response = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-campaigns`, {
          ad_account_id: accountId.value,
          linkedin_level: level
        })

        const count = response.data.data?.length || 0
        addSyncLog(`✓ ${levelNames[level]}: ${count} items synced`, 'success')
      } catch (error: any) {
        addSyncLog(`⚠ ${levelNames[level]}: ${error.response?.data?.message || error.message}`, 'warning')
      }
    }

    // Reload campaigns after all syncs
    const campaignResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = campaignResponse.data.data || []
    addSyncLog(`Total campaigns loaded: ${campaigns.value.length}`, 'success')

    syncProgress.value = 40
    addSyncLog(`───────────────────────────────────────`, 'default')
    addSyncLog(`Starting Metrics Sync...`, 'info')

    // Step 2: Sync metrics
    // Use background mode for "All Time" syncs since they take 30+ minutes
    const metricsPayload: any = {
      ad_account_id: accountId.value,
      background: syncAllTime.value, // Background for all-time, foreground for date ranges
    }

    if (syncAllTime.value) {
      metricsPayload.all_time = true
      addSyncLog(`Running in background mode (All Time sync takes 30+ minutes)...`, 'warning')
    } else {
      metricsPayload.start_date = metricsSyncStartDate.value
      metricsPayload.end_date = metricsSyncEndDate.value
    }

    // Progress simulation during metrics sync
    const progressInterval = setInterval(() => {
      if (syncProgress.value < 90) {
        syncProgress.value = Math.min(syncProgress.value + 2, 90)
      }
    }, 500)

    const metricsResponse = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-metrics`, metricsPayload)

    clearInterval(progressInterval)
    syncProgress.value = 100

    if (metricsResponse.data.status === 'success' || metricsResponse.data.status === 'started') {
      const stats = metricsResponse.data.data
      addSyncLog(`───────────────────────────────────────`, 'default')

      // Handle background sync with progress polling
      if (syncAllTime.value && metricsResponse.data.log_file) {
        addSyncLog(`✓ SYNC STARTED`, 'success')
        addSyncLog(`Tracking progress...`, 'info')
        syncProgress.value = 5

        const logFile = metricsResponse.data.log_file
        let lastMonth = ''
        let pollErrors = 0

        const pollInterval = setInterval(async () => {
          try {
            console.log('Polling progress for:', logFile)
            const progressResponse = await window.axios.get(`/api/sync-progress/${logFile}`)
            console.log('Progress response:', progressResponse.data)
            const data = progressResponse.data

            syncProgress.value = data.percent
            addSyncLog(`Progress: ${data.percent}% (${data.monthsProcessed}/${data.totalMonths} months)`, 'default')

            if (data.currentMonth && data.currentMonth !== lastMonth) {
              addSyncLog(`Processing ${data.currentMonth}...`, 'info')
              lastMonth = data.currentMonth
            }

            if (data.complete) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              addSyncLog(`───────────────────────────────────────`, 'default')
              addSyncLog(`✓ SYNC COMPLETED`, 'success')
              addSyncLog(`Total Created: ${data.created}`, 'success')
              addSyncLog(`Total Updated: ${data.updated}`, 'success')

              // Reload campaigns
              const updatedResponse = await window.axios.get('/api/ad-campaigns', {
                params: { account_id: accountId.value }
              })
              campaigns.value = updatedResponse.data.data || []

              syncing.value = false
              syncingMetrics.value = false
              await loadAccountData()
            }

            pollErrors = 0
          } catch (err: any) {
            console.error('Poll error:', err)
            addSyncLog(`Poll error: ${err.message || 'Unknown error'}`, 'error')
            pollErrors++
            if (pollErrors >= 10) {
              clearInterval(pollInterval)
              addSyncLog(`Lost connection to sync progress.`, 'warning')
              syncing.value = false
              syncingMetrics.value = false
            }
          }
        }, 2000)

        return // Don't continue - let polling handle completion
      }

      // Non-background sync - show immediate results
      addSyncLog(`✓ SYNC COMPLETE`, 'success')
      if (stats) {
        addSyncLog(`Campaigns processed: ${stats.campaigns_processed || 0}`, 'success')
        addSyncLog(`Metrics synced: ${stats.metrics_synced || 0}`, 'success')
      }
    }

    // Reload to get updated metrics
    const updatedResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = updatedResponse.data.data || []

  } catch (error: any) {
    console.error('Error in full sync:', error)
    addSyncLog(`✗ Error: ${error.response?.data?.message || error.message}`, 'error')
  } finally {
    syncing.value = false
    syncingMetrics.value = false
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
  }
}

// Sync all Google levels (campaigns, ad_groups, ads) and then metrics
const syncAllGoogle = async () => {
  if (!syncAllTime.value && (!metricsSyncStartDate.value || !metricsSyncEndDate.value)) {
    alert('Please select both start and end dates or choose All Time')
    return
  }

  // Keep modal open to show progress
  syncing.value = true
  syncingMetrics.value = true

  // Reset and initialize logging
  syncLogs.value = []
  syncProgress.value = 0
  syncStartTime.value = Date.now()
  syncElapsedTime.value = '0:00'
  progressStageIndex = 0

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const dateRange = syncAllTime.value ? 'All Time' : `${metricsSyncStartDate.value} to ${metricsSyncEndDate.value}`

    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Starting Google Ads Full Sync`, 'info')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Account: ${adAccount.value.account_name}`, 'default')
    addSyncLog(`Date Range: ${dateRange}`, 'default')
    addSyncLog(`───────────────────────────────────────`, 'default')

    syncProgress.value = 5

    // Step 1: Sync all 3 Google levels
    const levels = ['campaigns', 'ad_groups', 'ads'] as const
    const levelNames = { campaigns: 'Campaigns', ad_groups: 'Ad Groups', ads: 'Ads' }

    for (let i = 0; i < levels.length; i++) {
      const level = levels[i]
      addSyncLog(`Syncing ${levelNames[level]}...`, 'info')
      syncProgress.value = 5 + (i + 1) * 10

      try {
        const response = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-campaigns`, {
          ad_account_id: accountId.value,
          google_level: level
        })

        const count = response.data.data?.length || 0
        addSyncLog(`✓ ${levelNames[level]}: ${count} items synced`, 'success')
      } catch (error: any) {
        addSyncLog(`⚠ ${levelNames[level]}: ${error.response?.data?.message || error.message}`, 'warning')
      }
    }

    // Reload campaigns after all syncs
    const campaignResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = campaignResponse.data.data || []
    addSyncLog(`Total items loaded: ${campaigns.value.length}`, 'success')

    syncProgress.value = 40
    addSyncLog(`───────────────────────────────────────`, 'default')
    addSyncLog(`Starting Metrics Sync...`, 'info')

    // Step 2: Sync metrics
    // Use background mode for "All Time" syncs since they take 30+ minutes
    const metricsPayload: any = {
      ad_account_id: accountId.value,
      background: syncAllTime.value, // Background for all-time, foreground for date ranges
    }

    if (syncAllTime.value) {
      metricsPayload.all_time = true
      addSyncLog(`Running in background mode (All Time sync takes 30+ minutes)...`, 'warning')
    } else {
      metricsPayload.start_date = metricsSyncStartDate.value
      metricsPayload.end_date = metricsSyncEndDate.value
    }

    // Progress simulation during metrics sync
    const progressInterval = setInterval(() => {
      if (syncProgress.value < 90) {
        syncProgress.value = Math.min(syncProgress.value + 2, 90)
      }
    }, 500)

    const metricsResponse = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-metrics`, metricsPayload)

    clearInterval(progressInterval)
    syncProgress.value = 100

    if (metricsResponse.data.status === 'success' || metricsResponse.data.status === 'started') {
      const stats = metricsResponse.data.data
      addSyncLog(`───────────────────────────────────────`, 'default')

      // Handle background sync with progress polling
      if (syncAllTime.value && metricsResponse.data.log_file) {
        addSyncLog(`✓ SYNC STARTED`, 'success')
        addSyncLog(`Tracking progress...`, 'info')
        syncProgress.value = 5

        const logFile = metricsResponse.data.log_file
        let lastMonth = ''
        let pollErrors = 0

        const pollInterval = setInterval(async () => {
          try {
            console.log('Polling progress for:', logFile)
            const progressResponse = await window.axios.get(`/api/sync-progress/${logFile}`)
            console.log('Progress response:', progressResponse.data)
            const data = progressResponse.data

            syncProgress.value = data.percent
            addSyncLog(`Progress: ${data.percent}% (${data.monthsProcessed}/${data.totalMonths} months)`, 'default')

            if (data.currentMonth && data.currentMonth !== lastMonth) {
              addSyncLog(`Processing ${data.currentMonth}...`, 'info')
              lastMonth = data.currentMonth
            }

            if (data.complete) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              addSyncLog(`───────────────────────────────────────`, 'default')
              addSyncLog(`✓ SYNC COMPLETED`, 'success')
              addSyncLog(`Total Created: ${data.created}`, 'success')
              addSyncLog(`Total Updated: ${data.updated}`, 'success')

              // Reload campaigns
              const updatedResponse = await window.axios.get('/api/ad-campaigns', {
                params: { account_id: accountId.value }
              })
              campaigns.value = updatedResponse.data.data || []

              syncing.value = false
              syncingMetrics.value = false
              await loadAccountData()
            }

            pollErrors = 0
          } catch (err: any) {
            console.error('Poll error:', err)
            addSyncLog(`Poll error: ${err.message || 'Unknown error'}`, 'error')
            pollErrors++
            if (pollErrors >= 10) {
              clearInterval(pollInterval)
              addSyncLog(`Lost connection to sync progress.`, 'warning')
              syncing.value = false
              syncingMetrics.value = false
            }
          }
        }, 2000)

        return // Don't continue - let polling handle completion
      }

      // Non-background sync - show immediate results
      addSyncLog(`✓ SYNC COMPLETE`, 'success')
      if (stats) {
        addSyncLog(`Campaigns processed: ${stats.campaigns_processed || 0}`, 'success')
        addSyncLog(`Metrics synced: ${stats.metrics_synced || 0}`, 'success')
      }
    }

    // Reload to get updated metrics
    const updatedResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = updatedResponse.data.data || []

  } catch (error: any) {
    console.error('Error in full sync:', error)
    addSyncLog(`✗ Error: ${error.response?.data?.message || error.message}`, 'error')
  } finally {
    syncing.value = false
    syncingMetrics.value = false
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
  }
}

// Sync all Snapchat campaigns and metrics
const syncAllSnapchat = async () => {
  if (!syncAllTime.value && (!metricsSyncStartDate.value || !metricsSyncEndDate.value)) {
    alert('Please select both start and end dates or choose All Time')
    return
  }

  // Keep modal open to show progress
  syncing.value = true
  syncingMetrics.value = true

  // Reset and initialize logging
  syncLogs.value = []
  syncProgress.value = 0
  syncStartTime.value = Date.now()
  syncElapsedTime.value = '0:00'
  progressStageIndex = 0

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const dateRange = syncAllTime.value ? 'All Time' : `${metricsSyncStartDate.value} to ${metricsSyncEndDate.value}`

    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Starting Snapchat Full Sync`, 'info')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Account: ${adAccount.value.account_name}`, 'default')
    addSyncLog(`Date Range: ${dateRange}`, 'default')
    addSyncLog(`───────────────────────────────────────`, 'default')

    syncProgress.value = 5

    // Step 1: Sync campaigns
    addSyncLog(`Syncing Campaigns...`, 'info')
    syncProgress.value = 10

    try {
      const response = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-campaigns`, {
        ad_account_id: accountId.value
      })

      const count = response.data.campaigns_count || response.data.data?.length || 0
      addSyncLog(`✓ Campaigns: ${count} items synced`, 'success')
    } catch (error: any) {
      addSyncLog(`⚠ Campaigns: ${error.response?.data?.message || error.message}`, 'warning')
    }

    // Reload campaigns after sync
    const campaignResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = campaignResponse.data.data || []
    addSyncLog(`Total campaigns loaded: ${campaigns.value.length}`, 'success')

    syncProgress.value = 40
    addSyncLog(`───────────────────────────────────────`, 'default')
    addSyncLog(`Starting Metrics Sync...`, 'info')

    // Step 2: Sync metrics
    const metricsPayload: any = {
      ad_account_id: accountId.value,
      background: syncAllTime.value,
    }

    if (syncAllTime.value) {
      metricsPayload.all_time = true
      addSyncLog(`Running in background mode (All Time sync may take several minutes)...`, 'warning')
    } else {
      metricsPayload.start_date = metricsSyncStartDate.value
      metricsPayload.end_date = metricsSyncEndDate.value
    }

    // Progress simulation during metrics sync
    const progressInterval = setInterval(() => {
      if (syncProgress.value < 90) {
        syncProgress.value = Math.min(syncProgress.value + 2, 90)
      }
    }, 500)

    const metricsResponse = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-metrics`, metricsPayload)

    clearInterval(progressInterval)
    syncProgress.value = 100

    if (metricsResponse.data.status === 'success' || metricsResponse.data.status === 'started') {
      const stats = metricsResponse.data.data
      addSyncLog(`───────────────────────────────────────`, 'default')

      // Handle background sync with progress polling
      if (syncAllTime.value && metricsResponse.data.log_file) {
        addSyncLog(`✓ SYNC STARTED`, 'success')
        addSyncLog(`Tracking progress...`, 'info')
        syncProgress.value = 5

        const logFile = metricsResponse.data.log_file
        let lastMonth = ''
        let pollErrors = 0

        const pollInterval = setInterval(async () => {
          try {
            const progressResponse = await window.axios.get(`/api/sync-progress/${logFile}`)
            const data = progressResponse.data

            syncProgress.value = data.percent
            addSyncLog(`Progress: ${data.percent}% (${data.monthsProcessed}/${data.totalMonths} months)`, 'default')

            if (data.currentMonth && data.currentMonth !== lastMonth) {
              addSyncLog(`Processing ${data.currentMonth}...`, 'info')
              lastMonth = data.currentMonth
            }

            if (data.complete) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              addSyncLog(`───────────────────────────────────────`, 'default')
              addSyncLog(`✓ SYNC COMPLETED`, 'success')
              addSyncLog(`Total Created: ${data.created}`, 'success')
              addSyncLog(`Total Updated: ${data.updated}`, 'success')

              // Reload campaigns
              const updatedResponse = await window.axios.get('/api/ad-campaigns', {
                params: { account_id: accountId.value }
              })
              campaigns.value = updatedResponse.data.data || []

              syncing.value = false
              syncingMetrics.value = false
              await loadAccountData()
            }

            pollErrors = 0
          } catch (err: any) {
            addSyncLog(`Poll error: ${err.message || 'Unknown error'}`, 'error')
            pollErrors++
            if (pollErrors >= 10) {
              clearInterval(pollInterval)
              addSyncLog(`Lost connection to sync progress.`, 'warning')
              syncing.value = false
              syncingMetrics.value = false
            }
          }
        }, 2000)

        return // Let polling handle completion
      }

      // Non-background sync - show immediate results
      addSyncLog(`✓ SYNC COMPLETE`, 'success')
      if (stats) {
        addSyncLog(`Campaigns processed: ${stats.campaigns_processed || 0}`, 'success')
        addSyncLog(`Metrics synced: ${stats.metrics_synced || 0}`, 'success')
      }
    }

    // Reload to get updated metrics
    const updatedResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = updatedResponse.data.data || []

  } catch (error: any) {
    console.error('Error in Snapchat sync:', error)
    addSyncLog(`✗ Error: ${error.response?.data?.message || error.message}`, 'error')
  } finally {
    syncing.value = false
    syncingMetrics.value = false
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
  }
}

// Sync all TikTok campaigns and metrics
const syncAllTikTok = async () => {
  if (!syncAllTime.value && (!metricsSyncStartDate.value || !metricsSyncEndDate.value)) {
    alert('Please select both start and end dates or choose All Time')
    return
  }

  // Keep modal open to show progress
  syncing.value = true
  syncingMetrics.value = true

  // Reset and initialize logging
  syncLogs.value = []
  syncProgress.value = 0
  syncStartTime.value = Date.now()
  syncElapsedTime.value = '0:00'
  progressStageIndex = 0

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const dateRange = syncAllTime.value ? 'All Time' : `${metricsSyncStartDate.value} to ${metricsSyncEndDate.value}`

    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Starting TikTok Full Sync`, 'info')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Account: ${adAccount.value.account_name}`, 'default')
    addSyncLog(`Date Range: ${dateRange}`, 'default')
    addSyncLog(`───────────────────────────────────────`, 'default')

    syncProgress.value = 5

    // Step 1: Sync campaigns
    addSyncLog(`Syncing Campaigns...`, 'info')
    syncProgress.value = 10

    try {
      const response = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-campaigns`, {
        ad_account_id: accountId.value
      })

      const count = response.data.campaigns_count || response.data.data?.length || 0
      addSyncLog(`✓ Campaigns: ${count} items synced`, 'success')
    } catch (error: any) {
      addSyncLog(`⚠ Campaigns: ${error.response?.data?.message || error.message}`, 'warning')
    }

    // Reload campaigns after sync
    const campaignResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = campaignResponse.data.data || []
    addSyncLog(`Total campaigns loaded: ${campaigns.value.length}`, 'success')

    syncProgress.value = 40
    addSyncLog(`───────────────────────────────────────`, 'default')
    addSyncLog(`Starting Metrics Sync...`, 'info')

    // Step 2: Sync metrics
    const metricsPayload: any = {
      ad_account_id: accountId.value,
      background: syncAllTime.value,
    }

    if (syncAllTime.value) {
      metricsPayload.all_time = true
      addSyncLog(`Running in background mode (All Time sync may take several minutes)...`, 'warning')
    } else {
      metricsPayload.start_date = metricsSyncStartDate.value
      metricsPayload.end_date = metricsSyncEndDate.value
    }

    // Progress simulation during metrics sync
    const progressInterval = setInterval(() => {
      if (syncProgress.value < 90) {
        syncProgress.value = Math.min(syncProgress.value + 2, 90)
      }
    }, 500)

    const metricsResponse = await window.axios.post(`/api/integrations/${adAccount.value.integration_id}/sync-metrics`, metricsPayload)

    clearInterval(progressInterval)
    syncProgress.value = 100

    if (metricsResponse.data.status === 'success' || metricsResponse.data.status === 'started') {
      const stats = metricsResponse.data.data
      addSyncLog(`───────────────────────────────────────`, 'default')

      // Handle background sync with progress polling
      if (syncAllTime.value && metricsResponse.data.log_file) {
        addSyncLog(`✓ SYNC STARTED`, 'success')
        addSyncLog(`Tracking progress...`, 'info')
        syncProgress.value = 5

        const logFile = metricsResponse.data.log_file
        let lastMonth = ''
        let pollErrors = 0

        const pollInterval = setInterval(async () => {
          try {
            const progressResponse = await window.axios.get(`/api/sync-progress/${logFile}`)
            const data = progressResponse.data

            syncProgress.value = data.percent
            addSyncLog(`Progress: ${data.percent}% (${data.monthsProcessed}/${data.totalMonths} months)`, 'default')

            if (data.currentMonth && data.currentMonth !== lastMonth) {
              addSyncLog(`Processing ${data.currentMonth}...`, 'info')
              lastMonth = data.currentMonth
            }

            if (data.complete) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              addSyncLog(`───────────────────────────────────────`, 'default')
              addSyncLog(`✓ SYNC COMPLETED`, 'success')
              addSyncLog(`Total Created: ${data.created}`, 'success')
              addSyncLog(`Total Updated: ${data.updated}`, 'success')

              // Reload campaigns
              const updatedResponse = await window.axios.get('/api/ad-campaigns', {
                params: { account_id: accountId.value }
              })
              campaigns.value = updatedResponse.data.data || []

              syncing.value = false
              syncingMetrics.value = false
              await loadAccountData()
            }

            pollErrors = 0
          } catch (err: any) {
            addSyncLog(`Poll error: ${err.message || 'Unknown error'}`, 'error')
            pollErrors++
            if (pollErrors >= 10) {
              clearInterval(pollInterval)
              addSyncLog(`Lost connection to sync progress.`, 'warning')
              syncing.value = false
              syncingMetrics.value = false
            }
          }
        }, 2000)

        return // Let polling handle completion
      }

      // Non-background sync - show immediate results
      addSyncLog(`✓ SYNC COMPLETE`, 'success')
      if (stats) {
        addSyncLog(`Campaigns processed: ${stats.campaigns_processed || 0}`, 'success')
        addSyncLog(`Metrics synced: ${stats.metrics_synced || 0}`, 'success')
      }
    }

    // Reload to get updated metrics
    const updatedResponse = await window.axios.get('/api/ad-campaigns', {
      params: { account_id: accountId.value }
    })
    campaigns.value = updatedResponse.data.data || []

  } catch (error: any) {
    console.error('Error in TikTok sync:', error)
    addSyncLog(`✗ Error: ${error.response?.data?.message || error.message}`, 'error')
  } finally {
    syncing.value = false
    syncingMetrics.value = false
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
  }
}

// Set metrics date range presets
const setMetricsDateRange = (preset: string) => {
  // Handle All Time selection
  if (preset === 'allTime') {
    syncAllTime.value = true
    metricsSyncStartDate.value = ''
    metricsSyncEndDate.value = ''
    return
  }

  // Reset allTime when selecting other presets
  syncAllTime.value = false

  const today = new Date()
  let startDate: Date
  let endDate: Date = today

  switch (preset) {
    case 'last7':
      startDate = new Date(today)
      startDate.setDate(today.getDate() - 7)
      break
    case 'last30':
      startDate = new Date(today)
      startDate.setDate(today.getDate() - 30)
      break
    case 'last90':
      startDate = new Date(today)
      startDate.setDate(today.getDate() - 90)
      break
    case 'thisMonth':
      startDate = new Date(today.getFullYear(), today.getMonth(), 1)
      break
    case 'lastMonth':
      startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      endDate = new Date(today.getFullYear(), today.getMonth(), 0)
      break
    case 'thisYear':
      startDate = new Date(today.getFullYear(), 0, 1)
      break
    default:
      startDate = new Date(today)
      startDate.setDate(today.getDate() - 30)
  }

  metricsSyncStartDate.value = startDate.toISOString().split('T')[0]
  metricsSyncEndDate.value = endDate.toISOString().split('T')[0]
}

// Add log entry helper
const addSyncLog = (message: string, type: string = 'default') => {
  const now = new Date()
  const time = now.toLocaleTimeString('en-US', { hour12: false })
  syncLogs.value.push({ time, message, type })
  // Auto-scroll to bottom
  setTimeout(() => {
    const logContainer = document.querySelector('.bg-gray-900.overflow-y-auto')
    if (logContainer) {
      logContainer.scrollTop = logContainer.scrollHeight
    }
  }, 10)
}

// Update elapsed time
const updateElapsedTime = () => {
  if (syncStartTime.value) {
    const elapsed = Math.floor((Date.now() - syncStartTime.value) / 1000)
    const minutes = Math.floor(elapsed / 60)
    const seconds = elapsed % 60
    syncElapsedTime.value = `${minutes}:${seconds.toString().padStart(2, '0')}`
  }
}

// Sync stage messages for detailed progress
const syncStageMessages = [
  { progress: 25, messages: ['Authenticating with platform API...', 'Validating access token...'] },
  { progress: 35, messages: ['Retrieving ad account information...', 'Loading campaign list...'] },
  { progress: 45, messages: ['Fetching campaign performance data...', 'Processing daily metrics...'] },
  { progress: 55, messages: ['Downloading impression data...', 'Collecting click statistics...'] },
  { progress: 65, messages: ['Processing conversion events...', 'Aggregating spend data...'] },
  { progress: 75, messages: ['Syncing metrics to database...', 'Updating campaign records...'] },
  { progress: 85, messages: ['Calculating derived metrics...', 'Finalizing data import...'] },
  { progress: 92, messages: ['Validating data integrity...', 'Running final checks...'] },
]

let progressStageIndex = 0
let activityInterval: any = null

// Sync metrics for date range
const syncMetrics = async () => {
  if (!syncAllTime.value && (!metricsSyncStartDate.value || !metricsSyncEndDate.value)) {
    alert('Please select both start and end dates or choose All Time')
    return
  }

  // Reset and initialize
  syncLogs.value = []
  syncProgress.value = 0
  syncStartTime.value = Date.now()
  syncElapsedTime.value = '0:00'
  syncingMetrics.value = true
  progressStageIndex = 0

  // Start elapsed time timer
  syncTimer = setInterval(updateElapsedTime, 1000)

  try {
    const dateRange = syncAllTime.value ? 'All Time' : `${metricsSyncStartDate.value} to ${metricsSyncEndDate.value}`
    const platform = adAccount.value.platform?.charAt(0).toUpperCase() + adAccount.value.platform?.slice(1)

    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Starting ${platform} Metrics Sync`, 'info')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Account: ${adAccount.value.account_name}`, 'default')
    addSyncLog(`Account ID: ${adAccount.value.external_account_id || adAccount.value.id}`, 'default')
    addSyncLog(`Date Range: ${dateRange}`, 'default')

    syncProgress.value = 5

    // Use background mode for "All Time" syncs since they take 30+ minutes
    const payload: any = {
      ad_account_id: accountId.value,
      background: syncAllTime.value, // Background for all-time, foreground for date ranges
    }

    if (syncAllTime.value) {
      payload.all_time = true
      addSyncLog(`Mode: Full Historical Sync (running in background)`, 'warning')
      addSyncLog(`All Time syncs take 30-60 minutes. You can close this modal.`, 'warning')
      if (adAccount.value.platform === 'facebook') {
        addSyncLog(`⚠ Facebook API limits historical data to 37 months`, 'warning')
      } else if (adAccount.value.platform === 'snapchat') {
        addSyncLog(`Snapchat: Fetching up to 5 years of data`, 'info')
      }
    } else {
      payload.start_date = metricsSyncStartDate.value
      payload.end_date = metricsSyncEndDate.value

      // Calculate days
      const start = new Date(metricsSyncStartDate.value)
      const end = new Date(metricsSyncEndDate.value)
      const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
      addSyncLog(`Period: ${days} days of data`, 'default')
    }

    addSyncLog(`───────────────────────────────────────`, 'default')
    syncProgress.value = 10
    addSyncLog(`Initializing API connection...`, 'info')

    await new Promise(resolve => setTimeout(resolve, 300))
    syncProgress.value = 15
    addSyncLog(`✓ API connection established`, 'success')

    // Waiting messages to show while API is processing
    const waitingMessages = [
      'Processing campaigns...',
      'Fetching daily insights...',
      'Retrieving ad metrics...',
      'Downloading performance data...',
      'Processing reach data...',
      'Calculating impressions...',
      'Aggregating conversions...',
      'Processing click data...',
      'Syncing spend metrics...',
      'Still working on API request...',
      'Large dataset - please wait...',
      'Waiting for platform response...',
      'Processing historical data...',
      'Almost there, still syncing...',
    ]
    let waitingMessageIndex = 0
    let lastWaitingMessageTime = 0

    // Start detailed progress simulation
    const progressInterval = setInterval(() => {
      const now = Date.now()

      if (progressStageIndex < syncStageMessages.length) {
        const stage = syncStageMessages[progressStageIndex]
        if (syncProgress.value < stage.progress) {
          syncProgress.value = Math.min(syncProgress.value + 1, stage.progress)
        }

        if (syncProgress.value >= stage.progress - 5 && syncProgress.value < stage.progress) {
          const randomMessage = stage.messages[Math.floor(Math.random() * stage.messages.length)]
          addSyncLog(randomMessage, 'default')
          progressStageIndex++
          lastWaitingMessageTime = now
        }
      } else {
        // All stages complete, show waiting messages every 8 seconds
        if (now - lastWaitingMessageTime > 8000) {
          const message = waitingMessages[waitingMessageIndex % waitingMessages.length]
          addSyncLog(message, 'info')
          waitingMessageIndex++
          lastWaitingMessageTime = now

          // Slowly increment progress towards 94%
          if (syncProgress.value < 94) {
            syncProgress.value = Math.min(syncProgress.value + 0.5, 94)
          }
        }
      }
    }, 800)

    // Activity indicator to show we're not frozen
    let activityDots = 0
    activityInterval = setInterval(() => {
      activityDots = (activityDots + 1) % 4
      const dots = '.'.repeat(activityDots)
      // Update activity in elapsed time display
      syncElapsedTime.value = `${Math.floor((Date.now() - syncStartTime.value!) / 1000 / 60)}:${(Math.floor((Date.now() - syncStartTime.value!) / 1000) % 60).toString().padStart(2, '0')} ${dots}`
    }, 500)

    addSyncLog(`Requesting data from ${platform} API...`, 'info')
    addSyncLog(`This may take several minutes for large date ranges...`, 'warning')

    // Make the API call with extended timeout
    const response = await window.axios.post(
      `/api/integrations/${adAccount.value.integration_id}/sync-metrics`,
      payload,
      { timeout: 1800000 } // 30 minute timeout
    )

    clearInterval(progressInterval)
    if (activityInterval) {
      clearInterval(activityInterval)
      activityInterval = null
    }

    syncProgress.value = 95
    addSyncLog(`───────────────────────────────────────`, 'default')
    addSyncLog(`Processing API response...`, 'info')


    await new Promise(resolve => setTimeout(resolve, 500))
    syncProgress.value = 98

    if (response.data.status === 'success') {
      syncProgress.value = 100
      addSyncLog(`═══════════════════════════════════════`, 'default')
      addSyncLog(`✓ SYNC COMPLETED SUCCESSFULLY`, 'success')
      addSyncLog(`═══════════════════════════════════════`, 'default')
      addSyncLog(`Total metrics synced: ${response.data.metrics_count || 0} records`, 'success')

      const elapsed = Math.floor((Date.now() - syncStartTime.value!) / 1000)
      addSyncLog(`Duration: ${Math.floor(elapsed / 60)}m ${elapsed % 60}s`, 'info')

      if (response.data.warning) {
        addSyncLog(`⚠ Note: ${response.data.warning}`, 'warning')
      }

      addSyncLog(`Refreshing campaign data...`, 'info')

      // Wait a moment to show completion
      await new Promise(resolve => setTimeout(resolve, 2000))

      showMetricsSyncModal.value = false
      syncAllTime.value = false
      syncLogs.value = []
      // Reload campaigns to show updated metrics
      await loadAccountData()
    } else if (response.data.status === 'started') {
      // Background sync started - poll for real-time progress
      addSyncLog(`═══════════════════════════════════════`, 'default')
      addSyncLog(`✓ SYNC STARTED`, 'success')
      addSyncLog(`═══════════════════════════════════════`, 'default')
      addSyncLog(`${response.data.message}`, 'info')
      addSyncLog(``, 'default')

      const logFile = response.data.log_file
      if (logFile) {
        addSyncLog(`Tracking progress...`, 'info')
        syncProgress.value = 5

        let lastMonth = ''
        let pollErrors = 0

        // Poll for progress updates
        const pollInterval = setInterval(async () => {
          try {
            const progressResponse = await window.axios.get(`/api/sync-progress/${logFile}`)
            const data = progressResponse.data

            // Update progress
            syncProgress.value = data.percent

            // Log new month if changed
            if (data.currentMonth && data.currentMonth !== lastMonth) {
              addSyncLog(`Processing ${data.currentMonth}...`, 'info')
              lastMonth = data.currentMonth
            }

            // Show running totals periodically
            if (data.monthsProcessed > 0 && data.monthsProcessed % 6 === 0) {
              addSyncLog(`  Progress: ${data.monthsProcessed}/${data.totalMonths} months, +${data.created} created, ~${data.updated} updated`, 'default')
            }

            // Check if complete
            if (data.complete) {
              clearInterval(pollInterval)
              syncProgress.value = 100
              addSyncLog(`═══════════════════════════════════════`, 'default')
              addSyncLog(`✓ SYNC COMPLETED`, 'success')
              addSyncLog(`═══════════════════════════════════════`, 'default')
              addSyncLog(`Total Created: ${data.created}`, 'success')
              addSyncLog(`Total Updated: ${data.updated}`, 'success')

              const elapsed = Math.floor((Date.now() - syncStartTime.value!) / 1000)
              addSyncLog(`Duration: ${Math.floor(elapsed / 60)}m ${elapsed % 60}s`, 'info')

              addSyncLog(`Refreshing campaign data...`, 'info')
              await new Promise(resolve => setTimeout(resolve, 2000))

              showMetricsSyncModal.value = false
              syncAllTime.value = false
              syncLogs.value = []
              syncingMetrics.value = false
              await loadAccountData()
            }

            pollErrors = 0 // Reset error count on success
          } catch (err) {
            pollErrors++
            if (pollErrors >= 10) {
              // Too many errors, stop polling
              clearInterval(pollInterval)
              addSyncLog(`Lost connection to sync progress. Sync may still be running.`, 'warning')
              addSyncLog(`Check back later for results.`, 'info')
            }
          }
        }, 2000) // Poll every 2 seconds
      } else {
        // No log file - fallback to old behavior
        addSyncLog(`The sync is running on the server.`, 'info')
        addSyncLog(`You can close this modal and continue working.`, 'info')
        addSyncLog(`Metrics will appear after the sync completes.`, 'info')

        await new Promise(resolve => setTimeout(resolve, 3000))
        showMetricsSyncModal.value = false
        syncAllTime.value = false
        syncLogs.value = []
      }
    } else {
      addSyncLog(`⚠ Sync completed with issues`, 'warning')
      addSyncLog(`Message: ${response.data.message}`, 'warning')
    }
  } catch (error: any) {
    console.error('Error syncing metrics:', error)
    syncProgress.value = 100
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`✗ SYNC FAILED`, 'error')
    addSyncLog(`═══════════════════════════════════════`, 'default')
    addSyncLog(`Error: ${error.response?.data?.message || error.message}`, 'error')

    if (error.code === 'ECONNABORTED') {
      addSyncLog(`The request timed out. Try a smaller date range.`, 'warning')
    }
  } finally {
    if (syncTimer) {
      clearInterval(syncTimer)
      syncTimer = null
    }
    if (activityInterval) {
      clearInterval(activityInterval)
      activityInterval = null
    }
    syncingMetrics.value = false
    // Update final elapsed time without dots
    if (syncStartTime.value) {
      const elapsed = Math.floor((Date.now() - syncStartTime.value) / 1000)
      syncElapsedTime.value = `${Math.floor(elapsed / 60)}:${(elapsed % 60).toString().padStart(2, '0')}`
    }
  }
}

// View campaign metrics
const viewCampaignMetrics = (campaign: any) => {
  router.push({
    name: 'campaign-metrics',
    params: {
      campaignId: campaign.id
    },
    query: {
      accountId: campaign.ad_account_id || adAccount.value?.id,
      campaignName: campaign.name
    }
  })
}

// Get detection confidence badge class
const getConfidenceBadgeClass = (confidence: string) => {
  const classes = {
    'high': 'bg-green-100 text-green-800',
    'medium': 'bg-yellow-100 text-yellow-800', 
    'low': 'bg-orange-100 text-orange-800',
    'none': 'bg-gray-100 text-gray-800'
  }
  return classes[confidence as keyof typeof classes] || classes.none
}

// Refresh data
const refreshData = () => {
  loadAccountData()
}

// Date filter functions
const applyDateFilter = () => {
  // Only reload if both dates are set, or if clearing
  if ((filterStartDate.value && filterEndDate.value) || (!filterStartDate.value && !filterEndDate.value)) {
    loadAccountData()
  }
}

const clearDateFilter = () => {
  filterStartDate.value = ''
  filterEndDate.value = ''
  loadAccountData()
}

// Format helpers
const formatIndustry = (industry: string) => {
  return industry.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatObjective = (objective: string) => {
  if (!objective) return 'Not Set'
  return objective.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatCurrency = (value: number) => {
  // All spend values are normalized to SAR in the backend
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'SAR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const formatCurrencyOriginal = (value: number, currency: string) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

const formatNumber = (value: number | null | undefined) => {
  if (value == null) return '0'
  return new Intl.NumberFormat('en-US').format(value)
}

const formatDateTime = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const formatRelativeTime = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins} minute${diffMins === 1 ? '' : 's'} ago`
  if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`
  if (diffDays < 7) return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} week${Math.floor(diffDays / 7) === 1 ? '' : 's'} ago`
  return `${Math.floor(diffDays / 30)} month${Math.floor(diffDays / 30) === 1 ? '' : 's'} ago`
}

// Watch for industry changes
watch(() => editableAccount.value.industry, (newIndustry) => {
  loadSubIndustries()
  // Update available categories when industry changes
  if (newIndustry) {
    availableCategories.value = getCategoriesForIndustry(newIndustry) || []
  } else {
    availableCategories.value = []
  }
})

// Load data on mount
onMounted(() => {
  loadAccountData()
})
</script>

<style scoped>
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 1.5s infinite;
}
</style>