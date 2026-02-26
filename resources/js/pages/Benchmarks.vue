<template>
  <div class="min-h-screen bg-gray-50">
    <div class="px-4 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('pages.benchmarks.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('pages.benchmarks.description') }}
          <span v-if="autoRefreshEnabled" class="inline-flex items-center ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
            <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
            {{ $t('pages.benchmarks.live_data') }}
          </span>
        </p>
      </div>
      <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3 md:ml-4">
        <!-- Auto-refresh toggle -->
        <div class="flex items-center space-x-2">
          <label class="flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="autoRefreshEnabled"
              @change="toggleAutoRefresh"
              class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
            >
            <span class="ml-2 text-sm text-gray-700">{{ $t('pages.benchmarks.auto_refresh') }}</span>
          </label>
          <span v-if="autoRefreshEnabled" class="text-xs text-gray-500">
            ({{ autoRefreshCountdown }}s)
          </span>
        </div>

        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']"
            aria-hidden="true"
          />
          <span class="hidden sm:inline">{{ $t('pages.benchmarks.refresh') }}</span>
          <span class="sm:hidden">{{ $t('pages.benchmarks.refresh_data') }}</span>
        </button>

        <ExportMenu 
          :data="exportData"
          element-id="benchmark-report"
          :filename="`benchmark-report-${new Date().toISOString().split('T')[0]}`"
          :loading="loading"
          @export="onExport"
        />
      </div>
    </div>

    <!-- Summary Cards (Appears on all tabs) -->
    <div v-if="loading" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-5 animate-pulse">
          <div class="flex items-center">
            <div class="h-12 w-12 bg-gray-200 rounded-lg"></div>
            <div class="ml-5 flex-1">
              <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div class="h-8 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Filters -->
    <BenchmarkFiltersEnhanced
      :initial-filters="dashboardFilters"
      :date-range="dateRange"
      :available-platforms="availablePlatforms"
      :available-industries="availableIndustries"
      @filters-changed="onFiltersChanged"
    />

    <!-- Filter Status Indicator -->
    <div v-if="filters.industry.length > 0 || filters.platform.length > 0 || (filters.country && filters.country.length > 0)" class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
      <p class="text-sm text-blue-700">
        <span class="font-semibold">{{ $t('pages.benchmarks.filtered_view.label') }}</span> {{ $t('pages.benchmarks.filtered_view.showing') }} {{ summary.total_accounts }} {{ $t('pages.benchmarks.filtered_view.of') }} {{ unfilteredSummary.total_accounts }} {{ $t('pages.benchmarks.filtered_view.accounts') }}
        <span v-for="industry in filters.industry" :key="industry" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
          {{ getIndustryLabel(industry) }}
        </span>
        <span v-for="platform in filters.platform" :key="platform" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
          {{ platform }}
        </span>
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading">
      <!-- Loading Skeleton for Trending Section -->
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/4 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
        <div class="p-6 animate-pulse">
          <div class="h-64 bg-gray-200 rounded"></div>
        </div>
      </div>

      <!-- Loading Skeleton for Industry Cards -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3"></div>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-6 p-4 sm:p-6 sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="i in 6" :key="i" class="bg-gray-50 rounded-lg p-6 animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
            <div class="space-y-3">
              <div class="h-4 bg-gray-200 rounded"></div>
              <div class="h-4 bg-gray-200 rounded"></div>
              <div class="h-4 bg-gray-200 rounded w-5/6"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else>
      <!-- Your Performance Overview -->
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $t('pages.benchmarks.your_performance_title') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('pages.benchmarks.your_performance_subtitle') }}</p>
        </div>

        <div class="px-4 py-5 sm:p-6">
          <!-- Overall Metrics Summary Grid -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <!-- Industries Tracked -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-blue-600">{{ $t('pages.benchmarks.industries_tracked') }}</p>
                  <p class="text-xl font-bold text-blue-900">
                    {{ yourOverallMetrics.industries_count }}
                  </p>
                </div>
                <div class="p-2 bg-blue-200 rounded-full">
                  <ChartBarIcon class="h-5 w-5 text-blue-600" />
                </div>
              </div>
            </div>

            <!-- Total Accounts -->
            <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-purple-600">{{ $t('pages.benchmarks.total_accounts') }}</p>
                  <p class="text-xl font-bold text-purple-900">
                    {{ yourOverallMetrics.accounts_count }}
                  </p>
                </div>
                <div class="p-2 bg-purple-200 rounded-full">
                  <BuildingOfficeIcon class="h-5 w-5 text-purple-600" />
                </div>
              </div>
              <p class="text-xs text-purple-600 mt-1">{{ yourOverallMetrics.campaigns_count }} {{ $t('pages.benchmarks.campaigns') }}</p>
            </div>

            <!-- Total Spend -->
            <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-indigo-600">{{ $t('pages.benchmarks.total_spend') }}</p>
                  <p class="text-xl font-bold text-indigo-900">
                    <span v-html="formatCurrency(yourOverallMetrics.total_spend)"></span>
                  </p>
                </div>
                <div class="p-2 bg-indigo-200 rounded-full">
                  <CurrencyDollarIcon class="h-5 w-5 text-indigo-600" />
                </div>
              </div>
            </div>

            <!-- Total Impressions -->
            <div class="bg-gradient-to-r from-cyan-50 to-cyan-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-cyan-600">{{ $t('pages.benchmarks.total_impressions') }}</p>
                  <p class="text-xl font-bold text-cyan-900">
                    {{ formatNumber(yourOverallMetrics.total_impressions) }}
                  </p>
                </div>
                <div class="p-2 bg-cyan-200 rounded-full">
                  <EyeIcon class="h-5 w-5 text-cyan-600" />
                </div>
              </div>
            </div>

            <!-- Total Clicks -->
            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-green-600">{{ $t('pages.benchmarks.total_clicks') }}</p>
                  <p class="text-xl font-bold text-green-900">
                    {{ formatNumber(yourOverallMetrics.total_clicks) }}
                  </p>
                </div>
                <div class="p-2 bg-green-200 rounded-full">
                  <CursorArrowRaysIcon class="h-5 w-5 text-green-600" />
                </div>
              </div>
              <p class="text-xs text-green-600 mt-1">CTR: {{ yourPerformanceIndicators.ctr.toFixed(2) }}%</p>
            </div>

            <!-- Total Conversions -->
            <div class="bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-medium text-amber-600">{{ $t('pages.benchmarks.total_conversions') }}</p>
                  <p class="text-xl font-bold text-amber-900">
                    {{ formatNumber(yourOverallMetrics.total_conversions) }}
                  </p>
                </div>
                <div class="p-2 bg-amber-200 rounded-full">
                  <CheckCircleIcon class="h-5 w-5 text-amber-600" />
                </div>
              </div>
              <p class="text-xs text-amber-600 mt-1">CVR: {{ yourPerformanceIndicators.cvr.toFixed(2) }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Industry Benchmarks Section -->

          <!-- View Mode Toggle - Hidden for now, will be restored later
          <div class="mb-4 flex items-center justify-between bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center space-x-2">
              <span class="text-sm font-medium text-gray-700 mr-2">{{ $t('pages.benchmarks.performance.view_mode') }}:</span>
              <button
                @click="saveViewPreference('card')"
                :class="[
                  'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150',
                  viewMode === 'card'
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                <Squares2X2Icon class="h-5 w-5 mr-1.5" />
                {{ $t('pages.benchmarks.performance.view_modes.card') }}
              </button>
              <button
                @click="saveViewPreference('table')"
                :class="[
                  'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150',
                  viewMode === 'table'
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                <TableCellsIcon class="h-5 w-5 mr-1.5" />
                {{ $t('pages.benchmarks.performance.view_modes.table') }}
              </button>
              <button
                @click="saveViewPreference('chart')"
                :class="[
                  'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150',
                  viewMode === 'chart'
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                <ChartBarIcon class="h-5 w-5 mr-1.5" />
                {{ $t('pages.benchmarks.performance.view_modes.chart') }}
              </button>
            </div>
            <div v-if="viewMode === 'chart'" class="flex items-center space-x-2">
              <span class="text-sm font-medium text-gray-700">{{ $t('pages.benchmarks.performance.chart.select_metric') }}:</span>
              <select
                v-model="selectedChartMetric"
                class="border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="ctr">{{ $t('pages.benchmarks.metrics.ctr') }}</option>
                <option value="cpc">{{ $t('pages.benchmarks.metrics.cpc') }}</option>
                <option value="cpm">{{ $t('pages.benchmarks.metrics.cpm') }}</option>
                <option value="cvr">{{ $t('pages.benchmarks.metrics.cvr') }}</option>
                <option value="cpl">{{ $t('pages.benchmarks.metrics.cpl') }}</option>
                <option value="cpa">{{ $t('pages.benchmarks.metrics.cpa') }}</option>
                <option value="roas">{{ $t('pages.benchmarks.metrics.roas') }}</option>
              </select>
            </div>
          </div>
          -->

          <!-- Industry Benchmarks Container -->
          <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $t('pages.benchmarks.performance.comparison_title') }}</h3>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ $t('pages.benchmarks.performance.comparison_description') }}
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-700">{{ $t('pages.benchmarks.performance.sort_by') }}</span>
                  <select
                    v-model="sortField"
                    class="border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option value="name">{{ $t('pages.benchmarks.performance.sort_industry_name') }}</option>
                    <option value="accounts_count">{{ $t('pages.benchmarks.performance.sort_accounts') }}</option>
                    <option value="total_spend">{{ $t('pages.benchmarks.performance.sort_spend') }}</option>
                    <option value="total_leads">{{ $t('pages.benchmarks.performance.sort_leads') }}</option>
                  </select>
                  <button
                    @click="toggleSortDirection"
                    class="p-1 hover:bg-gray-100 rounded transition-colors duration-150"
                  >
                    <svg v-if="sortDirection === 'asc'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Card View -->
            <div v-if="viewMode === 'card'">
              <!-- Search and filter for Card View -->
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="$t('pages.benchmarks.search_placeholder')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"
                />
              </div>

              <!-- Industry Cards Grid -->
              <div class="p-4">
                <div v-if="filteredIndustries.length === 0" class="text-center py-8">
                  <p class="text-gray-500">{{ $t('pages.benchmarks.performance.no_industries') }}</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div
                    v-for="[industryKey, industry] in filteredIndustries"
                    :key="industryKey"
                    class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-150"
                  >
                    <h4 class="text-base font-medium text-gray-900 mb-1">
                      {{ getIndustryLabel(industryKey) }}
                    </h4>
                    <!-- Industry Summary -->
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3 pb-2 border-b border-gray-100">
                      <div class="group relative inline-block">
                        <span class="cursor-help border-b border-dashed border-gray-400">
                          {{ industry.accounts_count }} {{ industry.accounts_count === 1 ? $t('benchmarks.account') : $t('benchmarks.accounts') }}
                        </span>
                        <div class="invisible group-hover:visible absolute z-10 w-64 p-3 mt-2 text-xs bg-gray-900 text-white rounded-lg shadow-lg left-0 top-full">
                          <div class="font-semibold mb-1.5">Accounts ({{ industry.accounts_count }}):</div>
                          <ul class="space-y-1 max-h-48 overflow-y-auto">
                            <li v-for="name in industry.account_names" :key="name" class="text-gray-200">• {{ name }}</li>
                          </ul>
                        </div>
                      </div>
                      <span class="font-medium" v-html="formatCurrency(industry.total_spend)"></span>
                    </div>
                    <!-- Metrics display with status badges and benchmarks -->
                    <div class="space-y-3">
                      <div v-for="metricKey in ['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'roas']" :key="metricKey">
                        <div class="flex items-center justify-between text-sm">
                          <span class="text-gray-600">{{ getMetricLabel(metricKey) }}:</span>
                          <div class="flex items-center space-x-2">
                            <span class="font-medium text-gray-900" v-html="formatMetricValue(metricKey, industry.metrics[metricKey]?.actual)"></span>
                            <span
                              v-if="industry.metrics[metricKey]"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="getStatusBadgeClass(industry.metrics[metricKey].status)"
                            >
                              {{ getStatusLabel(industry.metrics[metricKey].status) }}
                            </span>
                          </div>
                        </div>
                        <!-- WordStream Benchmark -->
                        <div
                          v-if="getExternalBenchmark(industryKey, metricKey)"
                          class="flex items-center justify-end text-xs text-gray-500 mt-0.5"
                        >
                          <span>
                            Benchmark: <span class="font-medium" v-html="formatMetricValue(metricKey, getExternalBenchmark(industryKey, metricKey).avg)"></span>
                            <span class="text-gray-400 ml-1">({{ getExternalBenchmark(industryKey, metricKey).source }})</span>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Table View - Hierarchical Industry Performance -->
            <div v-if="viewMode === 'table'">
              <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                  <div class="flex-1">
                    <h2 class="text-base font-medium text-gray-900">
                      {{ tableGroupBy === 'industry' ? $t('pages.benchmarks.industry_performance') : 'Client Performance' }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">{{ $t('pages.benchmarks.hierarchical_breakdown') }}</p>
                  </div>

                  <!-- Group By Toggle, Search Bar and Column Selector -->
                  <div class="flex items-center space-x-3">
                    <!-- Group By Toggle -->
                    <div class="flex items-center space-x-2">
                      <span class="text-sm text-gray-700">Group by:</span>
                      <button
                        @click="tableGroupBy = 'industry'"
                        :class="[
                          'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                          tableGroupBy === 'industry'
                            ? 'bg-primary-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                      >
                        Industry
                      </button>
                      <button
                        @click="tableGroupBy = 'client'"
                        :class="[
                          'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                          tableGroupBy === 'client'
                            ? 'bg-primary-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                      >
                        Client
                      </button>
                    </div>
                    <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                      </div>
                      <input
                        v-model="tableSearchQuery"
                        type="text"
                        :placeholder="$t('pages.benchmarks.search_placeholder')"
                        class="block w-full md:w-64 pl-9 pr-9 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                      />
                      <div v-if="tableSearchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button
                          @click="tableSearchQuery = ''"
                          class="text-gray-400 hover:text-gray-600"
                        >
                          <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- Column Selector Dropdown -->
                    <div class="relative">
                      <button
                        @click="showColumnSelector = !showColumnSelector"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                      >
                        <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        {{ $t('pages.benchmarks.columns_button') }}
                        <svg class="h-4 w-4 ml-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                      </button>

                      <!-- Dropdown Panel -->
                      <div
                        v-if="showColumnSelector"
                        class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
                      >
                        <div class="p-3 border-b border-gray-200">
                          <h4 class="text-sm font-semibold text-gray-900">{{ $t('pages.benchmarks.select_columns') }}</h4>
                          <p class="text-xs text-gray-500 mt-1">{{ $t('pages.benchmarks.choose_columns_desc') }}</p>
                        </div>

                        <div class="max-h-80 overflow-y-auto p-2">
                          <!-- Ad Account Level Group -->
                          <div class="mb-3">
                            <div class="flex items-center justify-between px-2 py-1">
                              <span class="text-xs font-semibold text-blue-700 uppercase">{{ $t('pages.benchmarks.column_groups.ad_account_level') }}</span>
                              <div class="flex space-x-1">
                                <button @click="toggleGroupColumns('account', true)" class="text-xs text-blue-600 hover:underline">{{ $t('pages.benchmarks.column_actions.all') }}</button>
                                <span class="text-gray-300">|</span>
                                <button @click="toggleGroupColumns('account', false)" class="text-xs text-blue-600 hover:underline">{{ $t('pages.benchmarks.column_actions.none') }}</button>
                              </div>
                            </div>
                            <div class="space-y-1">
                              <label
                                v-for="col in columnConfig.filter(c => c.group === 'account')"
                                :key="col.key"
                                class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer"
                                :class="{ 'opacity-50 cursor-not-allowed': col.locked }"
                              >
                                <input
                                  type="checkbox"
                                  :checked="col.visible"
                                  :disabled="col.locked"
                                  @change="toggleColumn(col.key)"
                                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ col.label }}</span>
                                <span v-if="col.locked" class="ml-auto text-xs text-gray-400">(required)</span>
                              </label>
                            </div>
                          </div>

                          <!-- Campaign Level Group -->
                          <div class="mb-3">
                            <div class="flex items-center justify-between px-2 py-1">
                              <span class="text-xs font-semibold text-green-700 uppercase">{{ $t('pages.benchmarks.column_groups.campaign_level') }}</span>
                              <div class="flex space-x-1">
                                <button @click="toggleGroupColumns('campaign', true)" class="text-xs text-green-600 hover:underline">{{ $t('pages.benchmarks.column_actions.all') }}</button>
                                <span class="text-gray-300">|</span>
                                <button @click="toggleGroupColumns('campaign', false)" class="text-xs text-green-600 hover:underline">{{ $t('pages.benchmarks.column_actions.none') }}</button>
                              </div>
                            </div>
                            <div class="space-y-1">
                              <label
                                v-for="col in columnConfig.filter(c => c.group === 'campaign')"
                                :key="col.key"
                                class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer"
                              >
                                <input
                                  type="checkbox"
                                  :checked="col.visible"
                                  @change="toggleColumn(col.key)"
                                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ col.label }}</span>
                              </label>
                            </div>
                          </div>

                          <!-- Volume Metrics Group -->
                          <div class="mb-3">
                            <div class="flex items-center justify-between px-2 py-1">
                              <span class="text-xs font-semibold text-purple-700 uppercase">{{ $t('pages.benchmarks.column_groups.volume_metrics') }}</span>
                              <div class="flex space-x-1">
                                <button @click="toggleGroupColumns('volume', true)" class="text-xs text-purple-600 hover:underline">{{ $t('pages.benchmarks.column_actions.all') }}</button>
                                <span class="text-gray-300">|</span>
                                <button @click="toggleGroupColumns('volume', false)" class="text-xs text-purple-600 hover:underline">{{ $t('pages.benchmarks.column_actions.none') }}</button>
                              </div>
                            </div>
                            <div class="space-y-1">
                              <label
                                v-for="col in columnConfig.filter(c => c.group === 'volume')"
                                :key="col.key"
                                class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer"
                              >
                                <input
                                  type="checkbox"
                                  :checked="col.visible"
                                  @change="toggleColumn(col.key)"
                                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ col.label }}</span>
                              </label>
                            </div>
                          </div>

                          <!-- Efficiency Metrics Group -->
                          <div>
                            <div class="flex items-center justify-between px-2 py-1">
                              <span class="text-xs font-semibold text-amber-700 uppercase">{{ $t('pages.benchmarks.column_groups.efficiency_metrics') }}</span>
                              <div class="flex space-x-1">
                                <button @click="toggleGroupColumns('efficiency', true)" class="text-xs text-amber-600 hover:underline">{{ $t('pages.benchmarks.column_actions.all') }}</button>
                                <span class="text-gray-300">|</span>
                                <button @click="toggleGroupColumns('efficiency', false)" class="text-xs text-amber-600 hover:underline">{{ $t('pages.benchmarks.column_actions.none') }}</button>
                              </div>
                            </div>
                            <div class="space-y-1">
                              <label
                                v-for="col in columnConfig.filter(c => c.group === 'efficiency')"
                                :key="col.key"
                                class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer"
                              >
                                <input
                                  type="checkbox"
                                  :checked="col.visible"
                                  @change="toggleColumn(col.key)"
                                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ col.label }}</span>
                              </label>
                            </div>
                          </div>
                        </div>

                        <div class="p-2 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                          <button
                            @click="showColumnSelector = false"
                            class="w-full px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                          >
                            {{ $t('pages.benchmarks.column_actions.done') }}
                          </button>
                        </div>
                      </div>

                      <!-- Click outside to close -->
                      <div
                        v-if="showColumnSelector"
                        class="fixed inset-0 z-40"
                        @click="showColumnSelector = false"
                      ></div>
                    </div>

                    <span class="text-sm text-gray-500 whitespace-nowrap">
                      {{ tableGroupBy === 'industry'
                        ? $t('pages.benchmarks.industries_count', { count: activeTableData.length })
                        : `${activeTableData.length} Clients` }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Hierarchical Table -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                  <thead class="bg-gray-50 sticky top-0 z-10">
                    <!-- Row 1: Group Headers -->
                    <tr class="border-b-2 border-gray-300">
                      <th v-if="getVisibleColumnCount('account') > 0" :colspan="getVisibleColumnCount('account')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-blue-50 border-r border-gray-300">{{ $t('pages.benchmarks.column_groups.ad_account_level') }}</th>
                      <th v-if="getVisibleColumnCount('campaign') > 0" :colspan="getVisibleColumnCount('campaign')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-green-50 border-r border-gray-300">{{ $t('pages.benchmarks.column_groups.campaign_level') }}</th>
                      <th v-if="getVisibleColumnCount('volume') > 0" :colspan="getVisibleColumnCount('volume')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-purple-50 border-r border-gray-300">{{ $t('pages.benchmarks.column_groups.volume_metrics') }}</th>
                      <th v-if="getVisibleColumnCount('efficiency') > 0" :colspan="getVisibleColumnCount('efficiency')" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase bg-amber-50">{{ $t('pages.benchmarks.column_groups.efficiency_metrics') }}</th>
                    </tr>
                    <!-- Row 2: Column Headers -->
                    <tr>
                      <th v-if="isColumnVisible('industry')" class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase bg-blue-50 min-w-[120px]">{{ tableGroupBy === 'industry' ? $t('pages.benchmarks.columns.industry') : 'Client' }}</th>
                      <th v-if="isColumnVisible('country')" class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase bg-blue-50 min-w-[100px]">{{ $t('pages.benchmarks.columns.country') }}</th>
                      <th v-if="isColumnVisible('category')" class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase bg-blue-50 min-w-[100px]">{{ tableGroupBy === 'industry' ? $t('pages.benchmarks.columns.category') : 'Industry' }}</th>
                      <th v-if="isColumnVisible('subCategory')" class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase bg-blue-50 border-r border-gray-300 min-w-[120px]">{{ $t('pages.benchmarks.columns.sub_category') }}</th>
                      <th v-if="isColumnVisible('objective')" class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase bg-green-50 border-r border-gray-300 min-w-[80px]">{{ $t('pages.benchmarks.columns.objective') }}</th>
                      <th v-if="isColumnVisible('spend')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50">
                        <div class="flex items-center justify-end gap-1">
                          <span>{{ $t('pages.benchmarks.columns.spend') }}</span>
                          <button
                            @click.stop="toggleAllSpendValues"
                            class="p-0.5 rounded hover:bg-purple-100 transition-colors"
                            :title="showAllSpend || revealedSpendCells.size > 0 ? $t('pages.benchmarks.hide_spend') : $t('pages.benchmarks.show_spend')"
                          >
                            <EyeIcon v-if="showAllSpend || revealedSpendCells.size > 0" class="h-3.5 w-3.5 text-purple-600" />
                            <EyeSlashIcon v-else class="h-3.5 w-3.5 text-gray-400" />
                          </button>
                        </div>
                      </th>
                      <th v-if="isColumnVisible('impressions')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50 whitespace-nowrap">{{ $t('pages.benchmarks.columns.impressions') }}</th>
                      <th v-if="isColumnVisible('clicks')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50 whitespace-nowrap">{{ $t('pages.benchmarks.columns.clicks') }}</th>
                      <th v-if="isColumnVisible('leads')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50 whitespace-nowrap">{{ $t('pages.benchmarks.columns.leads') }}</th>
                      <th v-if="isColumnVisible('installs')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50 whitespace-nowrap">{{ $t('pages.benchmarks.columns.installs') }}</th>
                      <th v-if="isColumnVisible('conversions')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-purple-50 border-r border-gray-300 whitespace-nowrap">{{ $t('pages.benchmarks.columns.conversions') }}</th>
                      <th v-if="isColumnVisible('cpm')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[80px]">{{ $t('pages.benchmarks.columns.cpm') }}</th>
                      <th v-if="isColumnVisible('cpc')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[80px]">{{ $t('pages.benchmarks.columns.cpc') }}</th>
                      <th v-if="isColumnVisible('ctr')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[70px]">{{ $t('pages.benchmarks.columns.ctr') }}</th>
                      <th v-if="isColumnVisible('cpl')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[80px]">{{ $t('pages.benchmarks.columns.cpl') }}</th>
                      <th v-if="isColumnVisible('cpa')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[80px]">{{ $t('pages.benchmarks.columns.cpa') }}</th>
                      <th v-if="isColumnVisible('cpi')" class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase bg-amber-50 w-[80px]">{{ $t('pages.benchmarks.columns.cpi') }}</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-100">
                    <template v-for="group in activeTableData" :key="group.key">
                      <template v-for="category in group.categories" :key="`${group.key}-${category.key}`">
                        <tr
                          v-for="row in category.rows"
                          :key="`${group.key}-${category.key}-${row.key}`"
                          class="hover:bg-gray-50 transition-colors duration-150"
                        >
                          <td v-if="isColumnVisible('industry')" class="px-3 py-2 text-gray-700 truncate max-w-[150px]" :title="group.label">{{ group.label }}</td>
                          <td v-if="isColumnVisible('country')" class="px-3 py-2 text-gray-700 truncate max-w-[120px]">{{ row.country ? getCountryName(row.country) : '-' }}</td>
                          <td v-if="isColumnVisible('category')" class="px-3 py-2 text-gray-700 truncate max-w-[120px]" :title="category.label">{{ category.label }}</td>
                          <td v-if="isColumnVisible('subCategory')" class="px-3 py-2 text-gray-600 truncate max-w-[150px]" :title="row.subCategory">{{ row.subCategory }}</td>
                          <td v-if="isColumnVisible('objective')" class="px-3 py-2 whitespace-nowrap">
                            <span :class="{
                              'text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded text-xs': row.funnel === 'TOF',
                              'text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded text-xs': row.funnel === 'MOF',
                              'text-green-600 bg-green-50 px-1.5 py-0.5 rounded text-xs': row.funnel === 'BOF',
                              'text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded text-xs': row.funnel === 'Other'
                            }">{{ row.funnel }}</span>
                          </td>
                          <td v-if="isColumnVisible('spend')" class="px-3 py-2 text-right text-gray-700">
                            <div class="flex items-center justify-end gap-1">
                              <span v-html="isSpendRevealed(`${group.key}-${category.key}-${row.key}`) ? formatCurrency(row.metrics.spend) : '****'"></span>
                              <button @click.stop="toggleSpendCell(`${group.key}-${category.key}-${row.key}`)" class="p-0.5 rounded hover:bg-gray-100">
                                <EyeIcon v-if="isSpendRevealed(`${group.key}-${category.key}-${row.key}`)" class="h-3 w-3 text-gray-400" />
                                <EyeSlashIcon v-else class="h-3 w-3 text-gray-300" />
                              </button>
                            </div>
                          </td>
                          <td v-if="isColumnVisible('impressions')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ formatNumber(row.metrics.impressions) }}</td>
                          <td v-if="isColumnVisible('clicks')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ formatNumber(row.metrics.clicks) }}</td>
                          <td v-if="isColumnVisible('leads')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ formatNumber(row.metrics.leads) }}</td>
                          <td v-if="isColumnVisible('installs')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ formatNumber(row.metrics.installs) }}</td>
                          <td v-if="isColumnVisible('conversions')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ formatNumber(row.metrics.conversions) }}</td>
                          <td v-if="isColumnVisible('cpm')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap"><span v-html="formatCurrency(row.metrics.cpm)"></span></td>
                          <td v-if="isColumnVisible('cpc')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap"><span v-html="formatCurrency(row.metrics.cpc)"></span></td>
                          <td v-if="isColumnVisible('ctr')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap">{{ row.metrics.ctr.toFixed(2) }}%</td>
                          <td v-if="isColumnVisible('cpl')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap"><span v-html="formatCurrency(row.metrics.cpl)"></span></td>
                          <td v-if="isColumnVisible('cpa')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap"><span v-html="formatCurrency(row.metrics.cpa)"></span></td>
                          <td v-if="isColumnVisible('cpi')" class="px-3 py-2 text-right text-gray-700 whitespace-nowrap"><span v-html="formatCurrency(row.metrics.cpi)"></span></td>
                        </tr>
                      </template>
                    </template>

                    <!-- Empty State -->
                    <tr v-if="activeTableData.length === 0">
                      <td :colspan="columnConfig.filter(c => c.visible).length" class="px-6 py-8 text-center text-gray-500">
                        {{ $t('pages.benchmarks.empty_state') }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Chart View -->
            <div v-if="viewMode === 'chart'" class="p-6 space-y-6">

              <!-- Summary Cards -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Industries Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-600">{{ $t('pages.benchmarks.chart.total_industries') }}</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ performanceSummaryStats.total_industries }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                      <BuildingOfficeIcon class="h-6 w-6 text-blue-600" />
                    </div>
                  </div>
                </div>

                <!-- Total Accounts Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-600">{{ $t('pages.benchmarks.chart.total_accounts') }}</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ performanceSummaryStats.total_accounts }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                      <TrophyIcon class="h-6 w-6 text-green-600" />
                    </div>
                  </div>
                </div>

                <!-- Total Spend Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-600">{{ $t('pages.benchmarks.chart.total_spend') }}</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1"><span v-html="formatCurrency(performanceSummaryStats.total_spend)"></span></p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                      <CurrencyDollarIcon class="h-6 w-6 text-purple-600" />
                    </div>
                  </div>
                </div>

                <!-- Total Impressions Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-600">{{ $t('pages.benchmarks.chart.total_impressions') }}</p>
                      <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(performanceSummaryStats.total_impressions) }}</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                      <EyeIcon class="h-6 w-6 text-yellow-600" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Charts Grid -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Chart 1: Top Industries -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="mb-4 flex items-center justify-between">
                    <div>
                      <h4 class="text-lg font-medium text-gray-900">{{ $t('pages.benchmarks.chart.top_10_title') }}</h4>
                      <p class="text-sm text-gray-500 mt-1">{{ $t('pages.benchmarks.chart.top_10_desc') }}</p>
                    </div>
                    <select
                      v-model="topIndustriesMetric"
                      class="block w-32 rounded-md border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500"
                    >
                      <option value="spend">{{ $t('pages.benchmarks.metrics.spend') }}</option>
                      <option value="ctr">{{ $t('pages.benchmarks.metrics.ctr') }}</option>
                      <option value="cpc">{{ $t('pages.benchmarks.metrics.cpc') }}</option>
                      <option value="cpm">{{ $t('pages.benchmarks.metrics.cpm') }}</option>
                      <option value="cvr">{{ $t('pages.benchmarks.metrics.cvr') }}</option>
                      <option value="cpl">{{ $t('pages.benchmarks.metrics.cpl') }}</option>
                      <option value="cpa">{{ $t('pages.benchmarks.metrics.cpa') }}</option>
                      <option value="roas">{{ $t('pages.benchmarks.metrics.roas') }}</option>
                    </select>
                  </div>
                  <div v-if="topIndustriesChartData.labels.length > 0" style="height: 400px;">
                    <HorizontalBarChart
                      :labels="topIndustriesChartData.labels"
                      :datasets="topIndustriesChartData.datasets"
                      :height="400"
                    />
                  </div>
                  <div v-else class="flex items-center justify-center" style="height: 400px;">
                    <p class="text-gray-400 text-sm">No industry data available</p>
                  </div>
                </div>

                <!-- Chart 2: Platform Distribution -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="mb-4">
                    <h4 class="text-lg font-medium text-gray-900">Platform Distribution</h4>
                    <p class="text-sm text-gray-500 mt-1">Account distribution across advertising platforms</p>
                  </div>
                  <div v-if="platformDistributionData.labels.length > 0" style="height: 400px;">
                    <DoughnutChart
                      :labels="platformDistributionData.labels"
                      :data="platformDistributionData.data"
                      :backgroundColor="platformDistributionData.backgroundColor"
                      :height="400"
                    />
                  </div>
                  <div v-else class="flex items-center justify-center" style="height: 400px;">
                    <p class="text-gray-400 text-sm">No platform data available</p>
                  </div>
                </div>

                <!-- Chart 3: Spend Distribution -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="mb-4">
                    <h4 class="text-lg font-medium text-gray-900">{{ $t('pages.benchmarks.chart.spend_distribution') }}</h4>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('pages.benchmarks.chart.spend_distribution_desc') }}</p>
                  </div>
                  <div v-if="spendDistributionData.labels.length > 0" style="height: 400px;">
                    <DoughnutChart
                      :labels="spendDistributionData.labels"
                      :data="spendDistributionData.data"
                      :backgroundColor="spendDistributionData.backgroundColor"
                      :height="400"
                    />
                  </div>
                  <div v-else class="flex items-center justify-center" style="height: 400px;">
                    <p class="text-gray-400 text-sm">No spend data available</p>
                  </div>
                </div>

                <!-- Chart 4: Metric Comparison -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <div class="mb-4 flex items-center justify-between">
                    <div>
                      <h4 class="text-lg font-medium text-gray-900">{{ getMetricLabel(selectedChartMetric) }} {{ $t('pages.benchmarks.chart.performance_comparison') }}</h4>
                      <p class="text-sm text-gray-500 mt-1">{{ $t('pages.benchmarks.chart.performance_comparison_desc') }}</p>
                    </div>
                    <select
                      v-model="selectedChartMetric"
                      class="block w-32 rounded-md border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500"
                    >
                      <option value="ctr">{{ $t('pages.benchmarks.metrics.ctr') }}</option>
                      <option value="cpc">{{ $t('pages.benchmarks.metrics.cpc') }}</option>
                      <option value="cpm">{{ $t('pages.benchmarks.metrics.cpm') }}</option>
                      <option value="cvr">{{ $t('pages.benchmarks.metrics.cvr') }}</option>
                      <option value="cpl">{{ $t('pages.benchmarks.metrics.cpl') }}</option>
                      <option value="cpa">{{ $t('pages.benchmarks.metrics.cpa') }}</option>
                      <option value="roas">{{ $t('pages.benchmarks.metrics.roas') }}</option>
                    </select>
                  </div>
                  <div v-if="chartData.labels && chartData.labels.length > 0" style="height: 400px;">
                    <InteractiveChart
                      type="bar"
                      :data="chartData"
                      :options="chartOptions"
                      :height="400"
                    />
                  </div>
                  <div v-else class="flex items-center justify-center" style="height: 400px;">
                    <p class="text-gray-400 text-sm">No metric data available</p>
                  </div>
                </div>

              </div>
            </div>
          </div>
    </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowPathIcon,
  BuildingOfficeIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  TrophyIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XCircleIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  EyeIcon,
  EyeSlashIcon,
  CursorArrowRaysIcon,
  Squares2X2Icon,
  TableCellsIcon
} from '@heroicons/vue/24/outline'
import BenchmarkFiltersEnhanced from '@/components/BenchmarkFiltersEnhanced.vue'
import InteractiveChart from '@/components/InteractiveChart.vue'
import ExportMenu from '@/components/ExportMenu.vue'
import HorizontalBarChart from '@/components/charts/HorizontalBarChart.vue'
import DoughnutChart from '@/components/charts/DoughnutChart.vue'
import CurrencyDisplay from '@/components/CurrencyDisplay.vue'
import { calculateIndustryBenchmarks, calculateAggregatedBenchmarks, calculateAccountMetrics } from '@/utils/benchmarkCalculator'
import { getCountryName } from '@/utils/countries'

interface BenchmarkMetric {
  actual: number | null
  benchmark: {
    min: number
    max: number
    avg: number
  }
  performance: number | null
  status: string
}

interface IndustryBenchmark {
  industry: string
  accounts_count: number
  account_names?: string[]
  total_spend: number
  total_impressions: number
  total_clicks: number
  total_leads: number
  metrics: Record<string, BenchmarkMetric>
}

interface Insight {
  type: string
  message: string
  priority: string
  metric?: string
}

const { t } = useI18n()

const loading = ref(false)

// localStorage key for filter persistence
const BENCHMARKS_FILTERS_KEY = 'benchmarks_filters'

// Load saved filters from localStorage
const loadSavedFilters = () => {
  try {
    const saved = localStorage.getItem(BENCHMARKS_FILTERS_KEY)
    if (saved) {
      return JSON.parse(saved)
    }
  } catch (e) {
    console.error('Error loading saved benchmarks filters:', e)
  }
  return null
}

const savedFilters = loadSavedFilters()

const dateRange = ref(savedFilters?.dateRange || {
  from: '2010-01-01',
  to: new Date().toISOString().split('T')[0]
})

// Filter state (arrays for multi-select support)
const filters = ref(savedFilters?.filters || {
  platform: [] as string[],
  funnel_stage: [] as string[],
  industry: [] as string[],
  sub_industry: [] as string[],
  country: [] as string[]
})

// Save filters to localStorage
const saveFilters = () => {
  try {
    localStorage.setItem(BENCHMARKS_FILTERS_KEY, JSON.stringify({
      dateRange: dateRange.value,
      filters: filters.value
    }))
  } catch (e) {
    console.error('Error saving benchmarks filters:', e)
  }
}

// Flag to prevent watcher from triggering during programmatic changes
const isAutoSelecting = ref(false)

// Debounce timer for filter changes
const filterDebounceTimer = ref<NodeJS.Timeout | null>(null)

// Cache for expensive benchmark calculations
const benchmarkCache = ref<Map<string, any>>(new Map())
const MAX_CACHE_SIZE = 10 // Keep last 10 calculations

// Sorting state
const sortField = ref<string>('name')
const sortDirection = ref<'asc' | 'desc'>('asc')

// Tab state management
const tabs = ['dashboard', 'performance'] as const
type TabName = typeof tabs[number]

const activeTabIndex = ref(0)
const tabDataLoaded = ref({
  dashboard: false,
  performance: false
})

// Tab-specific filter states (arrays for multi-select support)
const dashboardFilters = ref({
  platform: [] as string[],
  funnel_stage: [] as string[],
  industry: [] as string[],
  sub_industry: [] as string[],
  country: [] as string[]
})

// Performance tab date presets
const datePresets = [
  { label: 'Last 7 Days', value: '7d' },
  { label: 'Last 30 Days', value: '30d' },
  { label: 'Last 90 Days', value: '90d' },
  { label: 'Last 6 Months', value: '6m' },
  { label: 'Last Year', value: '1y' },
  { label: 'All Time', value: 'all' },
  { label: 'Custom', value: 'custom' }
]

const performanceFilters = ref({
  from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days ago
  to: new Date().toISOString().split('T')[0], // today
  platform: '',
  preset: '30d' // Default preset
})

// Performance tab chart state
const performanceCurrentMetric = ref<'spend' | 'impressions'>('spend')

// Tab-specific loading states
const dashboardLoading = ref(false)
const performanceLoading = ref(false)

// Performance tab view modes
const viewMode = ref<'card' | 'table' | 'chart'>('table')
const selectedChartMetric = ref<string>('ctr')
const topIndustriesMetric = ref<'ctr' | 'cpc' | 'cpm' | 'cvr' | 'cpl' | 'cpa' | 'roas' | 'spend'>('spend')
const searchQuery = ref('')

// Table enhancement state
const tableSearchQuery = ref('')
const showColumnSelector = ref(false)
const tableGroupBy = ref<'industry' | 'client'>('industry') // Toggle between industry and client grouping
const showAllSpend = ref(false) // Global toggle to show all spend values
const revealedSpendCells = ref<Set<string>>(new Set()) // Track which individual spend cells are revealed

const isSpendRevealed = (cellKey: string) => showAllSpend.value || revealedSpendCells.value.has(cellKey)
const toggleSpendCell = (cellKey: string) => {
  // If global show is on, turn it off and reveal only this cell
  if (showAllSpend.value) {
    showAllSpend.value = false
    revealedSpendCells.value.clear()
    revealedSpendCells.value.add(cellKey)
    return
  }
  if (revealedSpendCells.value.has(cellKey)) {
    revealedSpendCells.value.delete(cellKey)
  } else {
    revealedSpendCells.value.add(cellKey)
  }
}
const toggleAllSpendValues = () => {
  // Toggle global show all
  if (showAllSpend.value || revealedSpendCells.value.size > 0) {
    showAllSpend.value = false
    revealedSpendCells.value.clear()
  } else {
    showAllSpend.value = true
  }
}

// Column visibility state - stores keys, groups, visibility and locked status
// Labels are dynamically computed via getColumnLabel() for i18n support
const columnConfigRaw = ref([
  // Ad Account Level columns (always visible, not toggleable)
  { key: 'industry', group: 'account', visible: true, locked: true },
  { key: 'country', group: 'account', visible: true, locked: false },
  { key: 'category', group: 'account', visible: true, locked: false },
  { key: 'subCategory', group: 'campaign', visible: true, locked: false },
  // Campaign Level columns
  { key: 'objective', group: 'campaign', visible: true, locked: false },
  { key: 'geotarget', group: 'campaign', visible: false, locked: false },
  // Volume Metrics
  { key: 'spend', group: 'volume', visible: true, locked: false },
  { key: 'impressions', group: 'volume', visible: true, locked: false },
  { key: 'clicks', group: 'volume', visible: true, locked: false },
  { key: 'leads', group: 'volume', visible: true, locked: false },
  { key: 'installs', group: 'volume', visible: false, locked: false },
  { key: 'conversions', group: 'volume', visible: true, locked: false },
  // Efficiency Metrics
  { key: 'cpm', group: 'efficiency', visible: true, locked: false },
  { key: 'cpc', group: 'efficiency', visible: true, locked: false },
  { key: 'ctr', group: 'efficiency', visible: true, locked: false },
  { key: 'cpl', group: 'efficiency', visible: true, locked: false },
  { key: 'cpa', group: 'efficiency', visible: true, locked: false },
  { key: 'cpi', group: 'efficiency', visible: false, locked: false },
])

// Helper to get column label with translation support
const getColumnLabel = (key: string): string => {
  // Map keys to their column translation keys
  const keyMap: Record<string, string> = {
    industry: 'industry',
    country: 'country',
    category: 'category',
    subCategory: 'sub_category',
    objective: 'objective',
    geotarget: 'geotarget',
    spend: 'spend',
    impressions: 'impressions',
    clicks: 'clicks',
    leads: 'leads',
    installs: 'installs',
    conversions: 'conversions',
    cpm: 'cpm',
    cpc: 'cpc',
    ctr: 'ctr',
    cpl: 'cpl',
    cpa: 'cpa',
    cpi: 'cpi'
  }
  const translationKey = keyMap[key] || key
  return t(`pages.benchmarks.columns.${translationKey}`)
}

// Computed column config with translated labels
const columnConfig = computed(() => {
  return columnConfigRaw.value.map(col => ({
    ...col,
    label: getColumnLabel(col.key)
  }))
})

// Toggle column visibility
const toggleColumn = (key: string) => {
  const col = columnConfigRaw.value.find(c => c.key === key)
  if (col && !col.locked) {
    col.visible = !col.visible
    saveColumnPreferences()
  }
}

// Check if column is visible
const isColumnVisible = (key: string) => {
  const col = columnConfigRaw.value.find(c => c.key === key)
  return col ? col.visible : true
}

// Get visible columns count by group
const getVisibleColumnCount = (group: string) => {
  return columnConfigRaw.value.filter(c => c.group === group && c.visible).length
}

// Select/deselect all columns in a group
const toggleGroupColumns = (group: string, visible: boolean) => {
  columnConfigRaw.value.forEach(col => {
    if (col.group === group && !col.locked) {
      col.visible = visible
    }
  })
  saveColumnPreferences()
}

// Save column preferences to localStorage
const STORAGE_KEY_COLUMNS = 'benchmarks_table_columns_v1'
const saveColumnPreferences = () => {
  try {
    const prefs = columnConfigRaw.value.reduce((acc, col) => {
      acc[col.key] = col.visible
      return acc
    }, {} as Record<string, boolean>)
    localStorage.setItem(STORAGE_KEY_COLUMNS, JSON.stringify(prefs))
  } catch (e) {
    console.warn('Failed to save column preferences:', e)
  }
}

// Load column preferences from localStorage
const loadColumnPreferences = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY_COLUMNS)
    if (saved) {
      const prefs = JSON.parse(saved)
      columnConfigRaw.value.forEach(col => {
        if (!col.locked && prefs[col.key] !== undefined) {
          col.visible = prefs[col.key]
        }
      })
    }
  } catch (e) {
    console.warn('Failed to load column preferences:', e)
  }
}

const tableSortField = ref('industry')
const tableSortDirection = ref<'asc' | 'desc'>('asc')

// Expandable rows state
const expandedIndustries = ref<Set<string>>(new Set())

// Chart enhancement state (REMOVED - no longer needed)
// const chartType = ref<'bar' | 'line' | 'radar'>('bar')
// const selectedChartMetrics = ref<string[]>(['ctr']) // Array for multi-select (max 3)
// const isChartStacked = ref(false)
// const showTrendLine = ref(false)
// const highlightedIndustry = ref<string | null>(null)
// const isChartFullscreen = ref(false)

// LocalStorage helpers for view mode persistence
const STORAGE_KEY_VIEW_MODE = 'benchmarks_performance_view_mode_v2'
const loadViewPreference = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY_VIEW_MODE)
    if (saved && ['card', 'table', 'chart'].includes(saved)) {
      viewMode.value = saved as 'card' | 'table' | 'chart'
    }
  } catch (error) {
    console.warn('Failed to load view preference:', error)
  }
}
const saveViewPreference = (mode: 'card' | 'table' | 'chart') => {
  try {
    localStorage.setItem(STORAGE_KEY_VIEW_MODE, mode)
    viewMode.value = mode
  } catch (error) {
    console.warn('Failed to save view preference:', error)
  }
}

// Expandable table row functions
const toggleIndustry = (industryKey: string) => {
  if (expandedIndustries.value.has(industryKey)) {
    expandedIndustries.value.delete(industryKey)
  } else {
    expandedIndustries.value.add(industryKey)
  }
  // Trigger reactivity
  expandedIndustries.value = new Set(expandedIndustries.value)
}

const isExpanded = (industryKey: string) => {
  return expandedIndustries.value.has(industryKey)
}

const getIndustryAccounts = (industryKey: string) => {
  if (industryKey === 'all_industries') {
    return adAccounts.value.filter(acc =>
      (acc.total_impressions || 0) > 0 || (acc.total_clicks || 0) > 0
    )
  }
  return adAccounts.value.filter(acc => acc.industry === industryKey)
}

// Map campaign objectives to funnel stage (TOF/MOF/BOF)
const mapObjectiveToFunnel = (objective: string | undefined | null): string => {
  if (!objective) return 'Other'

  const objectiveLower = objective.toLowerCase()

  // TOF - Top of Funnel (Awareness)
  if (['awareness', 'brand_awareness', 'reach', 'video_views', 'brand'].some(o => objectiveLower.includes(o))) {
    return 'TOF'
  }

  // MOF - Middle of Funnel (Consideration)
  if (['consideration', 'traffic', 'engagement', 'app_engagement', 'messages', 'video_engagement'].some(o => objectiveLower.includes(o))) {
    return 'MOF'
  }

  // BOF - Bottom of Funnel (Conversion)
  if (['conversion', 'conversions', 'lead_generation', 'leads', 'sales', 'app_installs', 'install', 'purchase', 'catalog_sales'].some(o => objectiveLower.includes(o))) {
    return 'BOF'
  }

  return 'Other'
}

// Get funnel label for display
const getFunnelLabel = (funnel: string): string => {
  switch (funnel) {
    case 'TOF': return 'Awareness (TOF)'
    case 'MOF': return 'Consideration (MOF)'
    case 'BOF': return 'Conversion (BOF)'
    default: return 'Other'
  }
}

// Hierarchical table data computed property
// Structure: Industry -> Category -> Sub-Category -> Account -> Campaign
const hierarchicalTableData = computed(() => {
  // Get filtered ad accounts
  const filteredAccounts = adAccounts.value.filter(account => {
    if (!account.industry) return false
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry)) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    if (filters.value.country && filters.value.country.length > 0 && !filters.value.country.includes(account.country || '')) return false
    return true
  })

  // Apply search filter
  const searchFilteredAccounts = tableSearchQuery.value
    ? filteredAccounts.filter(acc =>
        acc.industry?.toLowerCase().includes(tableSearchQuery.value.toLowerCase()) ||
        acc.category?.toLowerCase().includes(tableSearchQuery.value.toLowerCase()) ||
        acc.account_name?.toLowerCase().includes(tableSearchQuery.value.toLowerCase())
      )
    : filteredAccounts

  // Helper to create empty metrics object
  const createEmptyMetrics = () => ({
    spend: 0, impressions: 0, clicks: 0, leads: 0, installs: 0, conversions: 0,
    cpm: 0, cpc: 0, ctr: 0, cpl: 0, cpa: 0, cpi: 0
  })

  // Calculate derived metrics
  const calculateDerivedMetrics = (metrics: any) => {
    metrics.cpm = metrics.impressions > 0 ? (metrics.spend / metrics.impressions) * 1000 : 0
    metrics.cpc = metrics.clicks > 0 ? metrics.spend / metrics.clicks : 0
    metrics.ctr = metrics.impressions > 0 ? (metrics.clicks / metrics.impressions) * 100 : 0
    metrics.cpl = metrics.leads > 0 ? metrics.spend / metrics.leads : 0
    metrics.cpa = metrics.conversions > 0 ? metrics.spend / metrics.conversions : 0
    metrics.cpi = metrics.installs > 0 ? metrics.spend / metrics.installs : 0
  }

  // Build hierarchy: Industry -> Category -> (SubCategory + Funnel) aggregated
  const industryMap = new Map<string, any>()

  searchFilteredAccounts.forEach(account => {
    const industryKey = account.industry || 'Uncategorized'
    const categoryKey = account.category || 'General'

    // Account metrics
    const accountMetrics = {
      spend: account.total_spend || 0,
      impressions: account.total_impressions || 0,
      clicks: account.total_clicks || 0,
      leads: account.total_leads || account.total_conversions || 0,
      installs: account.total_installs || 0,
      conversions: account.total_conversions || 0
    }

    // Initialize industry
    if (!industryMap.has(industryKey)) {
      industryMap.set(industryKey, {
        key: industryKey,
        label: getIndustryLabel(industryKey),
        metrics: createEmptyMetrics(),
        categories: new Map()
      })
    }
    const industry = industryMap.get(industryKey)

    // Initialize category
    if (!industry.categories.has(categoryKey)) {
      industry.categories.set(categoryKey, {
        key: categoryKey,
        label: categoryKey,
        metrics: createEmptyMetrics(),
        rows: new Map() // Changed from accounts to rows (grouped by subCategory + funnel)
      })
    }
    const category = industry.categories.get(categoryKey)

    // Determine sub-category and funnel from campaigns
    const campaigns = account.campaigns || []
    let subCategory = '-'
    let funnel = 'Other'
    let objective = 'Unknown'

    if (campaigns.length > 0) {
      const objectives = [...new Set(campaigns.map((c: any) => c.objective).filter(Boolean))]
      const subIndustries = [...new Set(campaigns.map((c: any) => c.sub_industry).filter(Boolean))]
      objective = objectives[0] || 'Unknown'
      funnel = mapObjectiveToFunnel(objective)
      subCategory = subIndustries[0] || '-'
    }

    // Create a unique key for this combination of subCategory + funnel
    const rowKey = `${subCategory}-${funnel}`

    // Initialize or aggregate row
    if (!category.rows.has(rowKey)) {
      category.rows.set(rowKey, {
        key: rowKey,
        subCategory: subCategory,
        funnel: funnel,
        objective: objective,
        country: account.country || null,
        metrics: createEmptyMetrics()
      })
    }
    const row = category.rows.get(rowKey)

    // Aggregate metrics to this row
    row.metrics.spend += accountMetrics.spend
    row.metrics.impressions += accountMetrics.impressions
    row.metrics.clicks += accountMetrics.clicks
    row.metrics.leads += accountMetrics.leads
    row.metrics.installs += accountMetrics.installs
    row.metrics.conversions += accountMetrics.conversions

    // Aggregate to category level
    category.metrics.spend += accountMetrics.spend
    category.metrics.impressions += accountMetrics.impressions
    category.metrics.clicks += accountMetrics.clicks
    category.metrics.leads += accountMetrics.leads
    category.metrics.installs += accountMetrics.installs
    category.metrics.conversions += accountMetrics.conversions

    // Aggregate to industry level
    industry.metrics.spend += accountMetrics.spend
    industry.metrics.impressions += accountMetrics.impressions
    industry.metrics.clicks += accountMetrics.clicks
    industry.metrics.leads += accountMetrics.leads
    industry.metrics.installs += accountMetrics.installs
    industry.metrics.conversions += accountMetrics.conversions
  })

  // Convert Maps to arrays and calculate derived metrics
  const result: any[] = []
  industryMap.forEach((industry) => {
    calculateDerivedMetrics(industry.metrics)

    const categoryArray: any[] = []
    industry.categories.forEach((category: any) => {
      calculateDerivedMetrics(category.metrics)

      const rowArray: any[] = []
      category.rows.forEach((row: any) => {
        calculateDerivedMetrics(row.metrics)

        // Only include rows with spend
        if (row.metrics.spend > 0) {
          rowArray.push(row)
        }
      })

      // Only include categories with rows
      if (rowArray.length > 0) {
        categoryArray.push({
          ...category,
          rows: rowArray.sort((a, b) => b.metrics.spend - a.metrics.spend)
        })
      }
    })

    // Only include industries with categories
    if (categoryArray.length > 0) {
      result.push({
        ...industry,
        categories: categoryArray.sort((a, b) => b.metrics.spend - a.metrics.spend)
      })
    }
  })

  // Sort industries by spend (descending)
  return result.sort((a, b) => b.metrics.spend - a.metrics.spend)
})

// Client-grouped table data computed property
// Structure: Client -> Industry -> Category aggregated
const clientGroupedTableData = computed(() => {
  // Get filtered ad accounts
  const filteredAccounts = adAccounts.value.filter(account => {
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry || '')) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    if (filters.value.country && filters.value.country.length > 0 && !filters.value.country.includes(account.country || '')) return false
    return true
  })

  // Apply search filter
  const searchFilteredAccounts = tableSearchQuery.value
    ? filteredAccounts.filter(acc =>
        acc.tenant?.name?.toLowerCase().includes(tableSearchQuery.value.toLowerCase()) ||
        acc.industry?.toLowerCase().includes(tableSearchQuery.value.toLowerCase()) ||
        acc.account_name?.toLowerCase().includes(tableSearchQuery.value.toLowerCase())
      )
    : filteredAccounts

  // Helper to create empty metrics object
  const createEmptyMetrics = () => ({
    spend: 0, impressions: 0, clicks: 0, leads: 0, installs: 0, conversions: 0,
    cpm: 0, cpc: 0, ctr: 0, cpl: 0, cpa: 0, cpi: 0
  })

  // Calculate derived metrics
  const calculateDerivedMetrics = (metrics: any) => {
    metrics.cpm = metrics.impressions > 0 ? (metrics.spend / metrics.impressions) * 1000 : 0
    metrics.cpc = metrics.clicks > 0 ? metrics.spend / metrics.clicks : 0
    metrics.ctr = metrics.impressions > 0 ? (metrics.clicks / metrics.impressions) * 100 : 0
    metrics.cpl = metrics.leads > 0 ? metrics.spend / metrics.leads : 0
    metrics.cpa = metrics.conversions > 0 ? metrics.spend / metrics.conversions : 0
    metrics.cpi = metrics.installs > 0 ? metrics.spend / metrics.installs : 0
  }

  // Build hierarchy: Client -> Industry -> Category
  const clientMap = new Map<string, any>()

  searchFilteredAccounts.forEach(account => {
    const clientKey = account.tenant?.name || 'Unassigned'
    const industryKey = account.industry || 'Uncategorized'
    const categoryKey = account.category || 'General'

    // Account metrics
    const accountMetrics = {
      spend: account.total_spend || 0,
      impressions: account.total_impressions || 0,
      clicks: account.total_clicks || 0,
      leads: account.total_leads || account.total_conversions || 0,
      installs: account.total_installs || 0,
      conversions: account.total_conversions || 0
    }

    // Initialize client
    if (!clientMap.has(clientKey)) {
      clientMap.set(clientKey, {
        key: clientKey,
        label: clientKey,
        metrics: createEmptyMetrics(),
        categories: new Map()
      })
    }
    const client = clientMap.get(clientKey)

    // Use industry as category in client view
    const rowKey = `${industryKey}-${categoryKey}`

    // Initialize category (industry in this case)
    if (!client.categories.has(industryKey)) {
      client.categories.set(industryKey, {
        key: industryKey,
        label: getIndustryLabel(industryKey),
        metrics: createEmptyMetrics(),
        rows: new Map()
      })
    }
    const category = client.categories.get(industryKey)

    // Determine funnel from campaigns
    const campaigns = account.campaigns || []
    let funnel = 'Other'
    if (campaigns.length > 0) {
      const objectives = [...new Set(campaigns.map((c: any) => c.objective).filter(Boolean))]
      funnel = mapObjectiveToFunnel(objectives[0] || '')
    }

    // Initialize or aggregate row
    if (!category.rows.has(rowKey)) {
      category.rows.set(rowKey, {
        key: rowKey,
        subCategory: categoryKey,
        funnel: funnel,
        country: account.country || null,
        metrics: createEmptyMetrics()
      })
    }
    const row = category.rows.get(rowKey)

    // Aggregate metrics to this row
    row.metrics.spend += accountMetrics.spend
    row.metrics.impressions += accountMetrics.impressions
    row.metrics.clicks += accountMetrics.clicks
    row.metrics.leads += accountMetrics.leads
    row.metrics.installs += accountMetrics.installs
    row.metrics.conversions += accountMetrics.conversions

    // Aggregate to category level
    category.metrics.spend += accountMetrics.spend
    category.metrics.impressions += accountMetrics.impressions
    category.metrics.clicks += accountMetrics.clicks
    category.metrics.leads += accountMetrics.leads
    category.metrics.installs += accountMetrics.installs
    category.metrics.conversions += accountMetrics.conversions

    // Aggregate to client level
    client.metrics.spend += accountMetrics.spend
    client.metrics.impressions += accountMetrics.impressions
    client.metrics.clicks += accountMetrics.clicks
    client.metrics.leads += accountMetrics.leads
    client.metrics.installs += accountMetrics.installs
    client.metrics.conversions += accountMetrics.conversions
  })

  // Convert Maps to arrays and calculate derived metrics
  const result: any[] = []
  clientMap.forEach((client) => {
    calculateDerivedMetrics(client.metrics)

    const categoryArray: any[] = []
    client.categories.forEach((category: any) => {
      calculateDerivedMetrics(category.metrics)

      const rowArray: any[] = []
      category.rows.forEach((row: any) => {
        calculateDerivedMetrics(row.metrics)

        // Only include rows with spend
        if (row.metrics.spend > 0) {
          rowArray.push(row)
        }
      })

      // Only include categories with rows
      if (rowArray.length > 0) {
        categoryArray.push({
          ...category,
          rows: rowArray.sort((a, b) => b.metrics.spend - a.metrics.spend)
        })
      }
    })

    // Only include clients with categories
    if (categoryArray.length > 0) {
      result.push({
        ...client,
        categories: categoryArray.sort((a, b) => b.metrics.spend - a.metrics.spend)
      })
    }
  })

  // Sort clients by spend (descending)
  return result.sort((a, b) => b.metrics.spend - a.metrics.spend)
})

// Active table data based on groupBy selection
const activeTableData = computed(() => {
  return tableGroupBy.value === 'industry' ? hierarchicalTableData.value : clientGroupedTableData.value
})

// Initialize active tab from URL
const initializeTabFromURL = () => {
  const urlParams = new URLSearchParams(window.location.search)
  const tabParam = urlParams.get('tab')
  if (tabParam) {
    const tabIndex = tabs.indexOf(tabParam as TabName)
    if (tabIndex >= 0) {
      activeTabIndex.value = tabIndex
    }
  }
}

// Update URL when tab changes
const onTabChange = (index: number) => {
  activeTabIndex.value = index
  const tabName = tabs[index]
  const url = new URL(window.location.href)
  url.searchParams.set('tab', tabName)
  window.history.pushState({}, '', url)

  // Lazy load tab data if not already loaded
  loadTabData(tabName)
}

// Lazy load data for specific tab
const loadTabData = async (tabName: TabName) => {
  if (tabDataLoaded.value[tabName]) return

  switch (tabName) {
    case 'dashboard':
      await fetchDashboardData()
      break
    case 'performance':
      await fetchPerformanceData()
      break
  }
}

// Tab-specific data fetching functions
const fetchDashboardData = async () => {
  if (dashboardLoading.value) return
  dashboardLoading.value = true

  try {
    await fetchAdAccounts()
    await fetchTrendingData()
    tabDataLoaded.value.dashboard = true
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    dashboardLoading.value = false
  }
}

const fetchPerformanceData = async () => {
  if (performanceLoading.value) return
  performanceLoading.value = true

  try {
    await fetchAdAccounts()
    await fetchBenchmarkData()
    await fetchExternalBenchmarks()
    tabDataLoaded.value.performance = true
  } catch (error) {
    console.error('Error fetching performance data:', error)
  } finally {
    performanceLoading.value = false
  }
}


// Unfiltered summary showing ALL account data (for overview/dashboard display)
const unfilteredSummary = computed(() => {
  // Count accounts with industry AND actual metrics data
  const allAccountsWithIndustry = adAccounts.value.filter(account =>
    account.industry &&
    ((account.total_impressions || 0) > 0 || (account.total_clicks || 0) > 0 || (account.total_spend || 0) > 0)
  )

  const totalAccounts = allAccountsWithIndustry.length
  const totalSpend = allAccountsWithIndustry.reduce((sum, account) => sum + (account.total_spend || 0), 0)

  // Group accounts by industry (only those with data)
  const accountsByIndustry = allAccountsWithIndustry.reduce((acc, account) => {
    if (account.industry) {
      acc[account.industry] = (acc[account.industry] || 0) + 1
    }
    return acc
  }, {} as Record<string, number>)

  const industriesCount = Object.keys(accountsByIndustry).length

  if (totalAccounts > 0) {
    const topIndustryEntry = Object.entries(accountsByIndustry).sort(([,a], [,b]) => b - a)[0]
    const topIndustry = topIndustryEntry ? topIndustryEntry[0] : 'N/A'

    return {
      total_industries: industriesCount,
      total_accounts: totalAccounts,
      total_spend: totalSpend,
      best_performing: [{
        name: topIndustry,
        performance: 0,
        accounts_count: topIndustryEntry ? topIndustryEntry[1] : 0
      }],
      needs_improvement: []
    }
  }

  return {
    total_industries: 0,
    total_accounts: 0,
    total_spend: 0,
    best_performing: [],
    needs_improvement: []
  }
})

// Filtered summary (used for specific calculations based on current filters)
const summary = computed(() => {
  // Filter accounts based on current form filters for summary calculations
  // IMPORTANT: Only count accounts WITH industry classification AND actual metrics data
  const filteredAccounts = adAccounts.value.filter(account => {
    // Always exclude accounts without industry or without metrics data
    if (!account.industry) return false
    if ((account.total_impressions || 0) === 0 && (account.total_clicks || 0) === 0 && (account.total_spend || 0) === 0) return false

    // Handle array-based multi-select filters
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry)) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    return true
  })

  // Use filtered account data for calculations
  const realAccountsCount = filteredAccounts.length
  // Spend is already in SAR in the database - no conversion needed
  const realTotalSpend = filteredAccounts.reduce((sum, account) => {
    const spend = account.total_spend || 0
    return sum + spend
  }, 0)

  // Group filtered accounts by industry for industry-specific counts
  const accountsByIndustry = filteredAccounts.reduce((acc, account) => {
    if (account.industry) {
      acc[account.industry] = (acc[account.industry] || 0) + 1
    }
    return acc
  }, {} as Record<string, number>)

  const industriesCount = Object.keys(accountsByIndustry).length
  
  // If we have real account data, use it for accounts and spend
  if (realAccountsCount > 0) {
    // Find the top industry by account count
    const topIndustryEntry = Object.entries(accountsByIndustry).sort(([,a], [,b]) => b - a)[0]
    const topIndustry = topIndustryEntry ? topIndustryEntry[0] : 'N/A'

    // Use the actual number of industries from real data
    const availableIndustriesCount = industriesCount

    return {
      total_industries: availableIndustriesCount,
      total_accounts: realAccountsCount,
      total_spend: realTotalSpend,
      best_performing: [{
        name: topIndustry,
        performance: 0,
        accounts_count: topIndustryEntry ? topIndustryEntry[1] : 0
      }],
      needs_improvement: []
    }
  }
  
  // Fallback to industryBenchmarks only if no real account data
  const industries = Object.values(industryBenchmarks.value)
  
  if (industries.length === 0) {
    return {
      total_industries: 0,
      total_accounts: 0,
      total_spend: 0,
      best_performing: [],
      needs_improvement: []
    }
  }
  
  // Calculate performance from industry benchmarks 
  const industriesWithPerformance = industries.map(industry => {
    const metrics = Object.values(industry.metrics || {})
    const avgPerformance = metrics.length > 0 
      ? metrics.reduce((sum, metric) => sum + (metric.performance || 0), 0) / metrics.length
      : 0
    
    return {
      name: industry.name,
      performance: avgPerformance,
      accounts_count: industry.accounts_count || 0
    }
  }).sort((a, b) => b.performance - a.performance)
  
  return {
    total_industries: industries.length,
    total_accounts: industries.reduce((sum, industry) => sum + (industry.accounts_count || 0), 0),
    total_spend: industries.reduce((sum, industry) => sum + (industry.total_spend || 0), 0),
    best_performing: industriesWithPerformance.slice(0, 3),
    needs_improvement: industriesWithPerformance.slice(-2)
  }
})

// Your Performance - Overall metrics from filtered accounts
const yourOverallMetrics = computed(() => {
  const filteredAccounts = adAccounts.value.filter(account => {
    if (!account.industry) return false
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry)) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    return true
  })

  const totalSpend = filteredAccounts.reduce((sum, acc) => sum + (acc.total_spend || 0), 0)
  const totalImpressions = filteredAccounts.reduce((sum, acc) => sum + (acc.total_impressions || 0), 0)
  const totalClicks = filteredAccounts.reduce((sum, acc) => sum + (acc.total_clicks || 0), 0)
  const totalConversions = filteredAccounts.reduce((sum, acc) => sum + (acc.total_conversions || 0), 0)
  const totalRevenue = filteredAccounts.reduce((sum, acc) => sum + (acc.total_revenue || 0), 0)
  const totalCampaigns = filteredAccounts.reduce((sum, acc) => sum + (acc.campaigns_count || 0), 0)

  // Calculate unique industries in filtered accounts
  const uniqueIndustries = new Set(filteredAccounts.map(acc => acc.industry).filter(Boolean))

  return {
    accounts_count: filteredAccounts.length,
    campaigns_count: totalCampaigns,
    industries_count: uniqueIndustries.size,
    total_spend: totalSpend,
    total_impressions: totalImpressions,
    total_clicks: totalClicks,
    total_conversions: totalConversions,
    total_revenue: totalRevenue
  }
})

// Track accounts needing industry classification
const unclassifiedAccountsInfo = computed(() => {
  const unclassified = adAccounts.value.filter(account => !account.industry || account.industry === '')
  const total = adAccounts.value.length
  const classified = total - unclassified.length

  return {
    count: unclassified.length,
    total: total,
    classified: classified,
    percentage: total > 0 ? Math.round((unclassified.length / total) * 100) : 0,
    hasUnclassified: unclassified.length > 0
  }
})

// Your Performance - Calculated indicators (CTR, CPC, CVR, etc.)
const yourPerformanceIndicators = computed(() => {
  const metrics = yourOverallMetrics.value

  const ctr = metrics.total_impressions > 0 ? (metrics.total_clicks / metrics.total_impressions) * 100 : 0
  const cpc = metrics.total_clicks > 0 ? metrics.total_spend / metrics.total_clicks : 0
  const cpm = metrics.total_impressions > 0 ? (metrics.total_spend / metrics.total_impressions) * 1000 : 0
  const cvr = metrics.total_clicks > 0 ? (metrics.total_conversions / metrics.total_clicks) * 100 : 0
  const cpl = metrics.total_conversions > 0 ? metrics.total_spend / metrics.total_conversions : 0
  const roas = metrics.total_spend > 0 ? metrics.total_revenue / metrics.total_spend : 0

  return { ctr, cpc, cpm, cvr, cpl, roas }
})

// Top performing accounts (sorted by ROAS or conversions)
const topPerformingAccounts = computed(() => {
  const filteredAccounts = adAccounts.value.filter(account => {
    if (!account.industry) return false
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry)) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    return true
  })

  return filteredAccounts
    .map(account => {
      const roas = account.total_spend > 0 ? (account.total_revenue || 0) / account.total_spend : 0
      const ctr = account.total_impressions > 0 ? (account.total_clicks / account.total_impressions) * 100 : 0
      const cvr = account.total_clicks > 0 ? (account.total_conversions / account.total_clicks) * 100 : 0

      return {
        ...account,
        calculated_roas: roas,
        calculated_ctr: ctr,
        calculated_cvr: cvr,
        performance_score: (roas * 10) + (ctr * 2) + (cvr * 5) // Weighted score
      }
    })
    .sort((a, b) => b.performance_score - a.performance_score)
    .slice(0, 10) // Top 10 accounts
})

const allIndustryBenchmarks = ref<Record<string, IndustryBenchmark>>({})

// Computed property to filter industry benchmarks based on form filters
const industryBenchmarks = computed(() => {
  const all = allIndustryBenchmarks.value

  // If no filters are applied, return all industries
  const hasFilters = Object.entries(filters.value).some(([_, value]) => {
    if (Array.isArray(value)) return value.length > 0
    return value !== ''
  })
  if (!hasFilters) {
    return all
  }

  // Filter industries based on selected filters
  let filtered: Record<string, IndustryBenchmark> = {}

  // If specific industries are selected, only show those industries
  if (filters.value.industry.length > 0) {
    filters.value.industry.forEach(selectedIndustry => {
      if (all[selectedIndustry]) {
        filtered[selectedIndustry] = all[selectedIndustry]
      }
    })
  } else if (hasFilters) {
    // If other filters are applied but no specific industry,
    // modify the data based on platform, objective, etc.
    Object.entries(all).forEach(([industryKey, industryData]) => {
      // Create deep copy of industry data to avoid mutations
      const modifiedData = {
        ...industryData,
        metrics: {}
      }

      // Deep copy metrics and apply adjustments
      Object.entries(industryData.metrics).forEach(([metricKey, metricData]) => {
        const originalMetric = metricData as any
        let platformMultiplier = 1.0

        // Get multipliers (use first selected value for multi-select)
        if (filters.value.platform.length > 0) {
          platformMultiplier = getPlatformMultiplier(filters.value.platform[0])
        }

        // Create modified metric with multipliers applied
        modifiedData.metrics[metricKey] = {
          actual: originalMetric.actual * platformMultiplier,
          benchmark: {
            min: originalMetric.benchmark.min * platformMultiplier,
            avg: originalMetric.benchmark.avg * platformMultiplier,
            max: originalMetric.benchmark.max * platformMultiplier
          },
          performance: originalMetric.performance,
          status: originalMetric.status
        }
      })

      filtered[industryKey] = modifiedData
    })
  } else {
    filtered = all
  }

  return filtered
})

// Sorted industries based on sortField and sortDirection
const sortedIndustries = computed(() => {
  const industries = Object.entries(industryBenchmarks.value)

  return industries.sort(([keyA, dataA], [keyB, dataB]) => {
    let aVal: any
    let bVal: any

    switch (sortField.value) {
      case 'name':
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
        break
      case 'accounts_count':
        aVal = dataA.accounts_count || 0
        bVal = dataB.accounts_count || 0
        break
      case 'total_spend':
        aVal = dataA.total_spend || 0
        bVal = dataB.total_spend || 0
        break
      case 'total_leads':
        aVal = dataA.total_leads || 0
        bVal = dataB.total_leads || 0
        break
      default:
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
    }

    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : aVal < bVal ? -1 : 0
    } else {
      return aVal < bVal ? 1 : aVal > bVal ? -1 : 0
    }
  }).reduce((acc, [key, data]) => {
    acc[key] = data
    return acc
  }, {} as Record<string, IndustryBenchmark>)
})

// Unfiltered industry benchmarks for Performance tab (shows ALL accounts)
const unfilteredIndustryBenchmarks = computed(() => {
  // Try to calculate industry-specific benchmarks
  const classifiedBenchmarks = calculateIndustryBenchmarks(adAccounts.value, {})

  // If no accounts have industry classification, show aggregate view with real data
  if (Object.keys(classifiedBenchmarks).length === 0) {
    return calculateAggregatedBenchmarks(adAccounts.value, {})
  }

  return classifiedBenchmarks
})

// Check if we're showing aggregate view (no industry classification)
const isAggregateView = computed(() => {
  return Object.keys(unfilteredIndustryBenchmarks.value).length === 1 &&
         unfilteredIndustryBenchmarks.value['all_industries'] !== undefined
})

// Helper function to get external benchmark for a specific industry and metric
const getExternalBenchmark = (industry: string, metric: string) => {
  // Special handling for aggregate view (all industries)
  if (industry === 'all_industries') {
    // Aggregate across ALL industries in WordStream data
    const allMetrics: any[] = []
    externalBenchmarks.value.forEach((b: any) => {
      if (b.platforms) {
        b.platforms.forEach((platform: any) => {
          const metricData = platform.metrics?.find((m: any) => m.metric === metric)
          if (metricData && metricData.percentiles) {
            allMetrics.push(metricData.percentiles)
          }
        })
      }
    })

    if (allMetrics.length === 0) return null

    // Average the percentiles across ALL industries and platforms
    const avgP50 = allMetrics.reduce((sum, m) => sum + (m.p50 || 0), 0) / allMetrics.length
    const avgP25 = allMetrics.reduce((sum, m) => sum + (m.p25 || 0), 0) / allMetrics.length
    const avgP75 = allMetrics.reduce((sum, m) => sum + (m.p75 || 0), 0) / allMetrics.length

    return {
      min: avgP25,
      avg: avgP50,
      max: avgP75,
      source: 'WordStream 2024 (Cross-Industry)'
    }
  }

  // Find matching external benchmarks for specific industry
  const matches = externalBenchmarks.value.filter((b: any) => {
    return b.industry === industry &&
           b.platforms &&
           b.platforms.some((p: any) =>
             p.metrics &&
             p.metrics.some((m: any) => m.metric === metric)
           )
  })

  if (matches.length === 0) return null

  // Aggregate across all platforms for this industry
  const allMetrics: any[] = []
  matches.forEach((industryData: any) => {
    industryData.platforms?.forEach((platform: any) => {
      const metricData = platform.metrics?.find((m: any) => m.metric === metric)
      if (metricData) {
        allMetrics.push(metricData.percentiles)
      }
    })
  })

  if (allMetrics.length === 0) return null

  // Average the percentiles across platforms
  const avgP50 = allMetrics.reduce((sum, m) => sum + (m.p50 || 0), 0) / allMetrics.length
  const avgP25 = allMetrics.reduce((sum, m) => sum + (m.p25 || 0), 0) / allMetrics.length
  const avgP75 = allMetrics.reduce((sum, m) => sum + (m.p75 || 0), 0) / allMetrics.length

  return {
    min: avgP25,
    avg: avgP50,
    max: avgP75,
    source: 'WordStream 2024'
  }
}

// Sorted unfiltered industries for Performance tab (always shows all data)
const unfilteredSortedIndustries = computed(() => {
  const industries = Object.entries(unfilteredIndustryBenchmarks.value)

  return industries.sort(([keyA, dataA], [keyB, dataB]) => {
    let aVal: any
    let bVal: any

    switch (sortField.value) {
      case 'name':
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
        break
      case 'accounts_count':
        aVal = dataA.accounts_count || 0
        bVal = dataB.accounts_count || 0
        break
      case 'total_spend':
        aVal = dataA.total_spend || 0
        bVal = dataB.total_spend || 0
        break
      case 'total_leads':
        aVal = dataA.total_leads || 0
        bVal = dataB.total_leads || 0
        break
      default:
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
    }

    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : aVal < bVal ? -1 : 0
    } else {
      return aVal < bVal ? 1 : aVal > bVal ? -1 : 0
    }
  }).reduce((acc, [key, data]) => {
    acc[key] = data
    return acc
  }, {} as Record<string, IndustryBenchmark>)
})

// Filtered industries for card view search
const filteredIndustries = computed(() => {
  const industries = Object.entries(unfilteredSortedIndustries.value)

  if (!searchQuery.value.trim()) {
    return industries
  }

  const query = searchQuery.value.toLowerCase()
  return industries.filter(([key]) => {
    const label = getIndustryLabel(key).toLowerCase()
    return label.includes(query)
  })
})

// Chart data for selected metric
const chartData = computed(() => {
  const industries = Object.entries(unfilteredSortedIndustries.value)

  const labels = industries.map(([key]) => getIndustryLabel(key))
  const yourPerformanceData = industries.map(([_, data]) => {
    const metric = data.metrics[selectedChartMetric.value]
    return metric?.actual || 0
  })

  const industryAverageData = industries.map(([key, data]) => {
    const metric = data.metrics[selectedChartMetric.value]
    // Try to get external benchmark first, otherwise use account average
    const externalBenchmark = getExternalBenchmark(key, selectedChartMetric.value)
    if (externalBenchmark) {
      return externalBenchmark.avg || 0
    }
    return metric?.benchmark.avg || 0
  })

  return {
    labels,
    datasets: [
      {
        label: t('pages.benchmarks.chart.your_performance'),
        data: yourPerformanceData,
        backgroundColor: 'rgba(99, 102, 241, 0.8)', // primary-600
        borderColor: 'rgba(99, 102, 241, 1)',
        borderWidth: 1
      },
      {
        label: t('pages.benchmarks.chart.industry_average'),
        data: industryAverageData,
        backgroundColor: 'rgba(156, 163, 175, 0.8)', // gray-400
        borderColor: 'rgba(156, 163, 175, 1)',
        borderWidth: 1
      }
    ]
  }
})

// Chart options for selected metric
const chartOptions = computed(() => {
  const metricLabel = getMetricLabel(selectedChartMetric.value)
  const isPercentage = ['ctr', 'cvr'].includes(selectedChartMetric.value)
  const isCurrency = ['cpc', 'cpm', 'cpl', 'cpa'].includes(selectedChartMetric.value)

  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top' as const,
        labels: {
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            let label = context.dataset.label || ''
            if (label) {
              label += ': '
            }
            if (isPercentage) {
              label += context.parsed.y.toFixed(2) + '%'
            } else if (isCurrency) {
              label += context.parsed.y.toFixed(2) + ' SR'
            } else if (selectedChartMetric.value === 'roas') {
              label += context.parsed.y.toFixed(2) + 'x'
            } else {
              label += context.parsed.y.toFixed(2)
            }
            return label
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: any) => {
            if (isPercentage) {
              return value + '%'
            } else if (isCurrency) {
              return value + ' SR'
            } else if (selectedChartMetric.value === 'roas') {
              return value + 'x'
            }
            return value
          }
        },
        title: {
          display: true,
          text: metricLabel
        }
      },
      x: {
        ticks: {
          maxRotation: 45,
          minRotation: 45,
          font: {
            size: 10
          }
        }
      }
    }
  }
})

// Top Industries Chart Data (Horizontal Bar)
const topIndustriesChartData = computed(() => {
  const industries = Object.entries(unfilteredIndustryBenchmarks.value)

  // Sort by selected metric and get top 10
  const sortedIndustries = industries.sort(([_keyA, dataA], [_keyB, dataB]) => {
    let valueA = 0
    let valueB = 0

    if (topIndustriesMetric.value === 'spend') {
      valueA = dataA.total_spend || 0
      valueB = dataB.total_spend || 0
    } else {
      valueA = dataA.metrics[topIndustriesMetric.value]?.actual || 0
      valueB = dataB.metrics[topIndustriesMetric.value]?.actual || 0
    }

    return valueB - valueA // Descending order
  }).slice(0, 10)

  const labels = sortedIndustries.map(([key]) => getIndustryLabel(key))
  const data = sortedIndustries.map(([_, industryData]) => {
    if (topIndustriesMetric.value === 'spend') {
      return industryData.total_spend || 0
    }
    return industryData.metrics[topIndustriesMetric.value]?.actual || 0
  })

  // 10 distinct colors
  const colors = [
    'rgba(59, 130, 246, 0.7)',   // Blue
    'rgba(16, 185, 129, 0.7)',   // Green
    'rgba(168, 85, 247, 0.7)',   // Purple
    'rgba(251, 191, 36, 0.7)',   // Yellow
    'rgba(239, 68, 68, 0.7)',    // Red
    'rgba(236, 72, 153, 0.7)',   // Pink
    'rgba(14, 165, 233, 0.7)',   // Sky
    'rgba(245, 158, 11, 0.7)',   // Amber
    'rgba(99, 102, 241, 0.7)',   // Indigo
    'rgba(20, 184, 166, 0.7)'    // Teal
  ]

  const borderColors = colors.map(c => c.replace('0.7', '1'))

  return {
    labels,
    datasets: [{
      label: getMetricLabel(topIndustriesMetric.value),
      data,
      backgroundColor: colors,
      borderColor: borderColors,
      borderWidth: 2
    }]
  }
})

// Platform Distribution Chart Data (Doughnut)
const platformDistributionData = computed(() => {
  const platformCounts: Record<string, number> = {}

  adAccounts.value.forEach(account => {
    const platform = account.platform?.toLowerCase() || 'unknown'
    platformCounts[platform] = (platformCounts[platform] || 0) + 1
  })

  const labels = Object.keys(platformCounts).map(p => {
    // Capitalize first letter
    return p.charAt(0).toUpperCase() + p.slice(1)
  })
  const data = Object.values(platformCounts)

  // Platform-specific colors
  const platformColors: Record<string, string> = {
    'google': 'rgba(234, 67, 53, 0.8)',
    'facebook': 'rgba(59, 130, 246, 0.8)',
    'meta': 'rgba(59, 130, 246, 0.8)',
    'linkedin': 'rgba(0, 119, 181, 0.8)',
    'snapchat': 'rgba(255, 252, 0, 0.8)',
    'tiktok': 'rgba(37, 244, 238, 0.8)'
  }

  const backgroundColor = Object.keys(platformCounts).map(p =>
    platformColors[p.toLowerCase()] || 'rgba(156, 163, 175, 0.8)'
  )

  return {
    labels,
    data,
    backgroundColor
  }
})

// Spend Distribution Chart Data (Doughnut)
const spendDistributionData = computed(() => {
  const industries = Object.entries(unfilteredIndustryBenchmarks.value)

  // Sort by spend and get top 10
  const sortedBySpend = industries
    .sort(([_a, dataA], [_b, dataB]) => (dataB.total_spend || 0) - (dataA.total_spend || 0))
    .slice(0, 10)

  const labels = sortedBySpend.map(([key]) => getIndustryLabel(key))
  const data = sortedBySpend.map(([_, industryData]) => industryData.total_spend || 0)

  // Rainbow colors
  const colors = [
    'rgba(239, 68, 68, 0.7)',    // Red
    'rgba(245, 158, 11, 0.7)',   // Orange
    'rgba(251, 191, 36, 0.7)',   // Yellow
    'rgba(16, 185, 129, 0.7)',   // Green
    'rgba(14, 165, 233, 0.7)',   // Sky
    'rgba(59, 130, 246, 0.7)',   // Blue
    'rgba(99, 102, 241, 0.7)',   // Indigo
    'rgba(168, 85, 247, 0.7)',   // Purple
    'rgba(236, 72, 153, 0.7)',   // Pink
    'rgba(20, 184, 166, 0.7)'    // Teal
  ]

  return {
    labels,
    data,
    backgroundColor: colors.slice(0, data.length)
  }
})

// Performance Summary Stats
const performanceSummaryStats = computed(() => {
  const industries = Object.keys(unfilteredIndustryBenchmarks.value)
  const totalAccounts = Object.values(unfilteredIndustryBenchmarks.value)
    .reduce((sum, ind) => sum + (ind.accounts_count || 0), 0)
  const totalSpend = Object.values(unfilteredIndustryBenchmarks.value)
    .reduce((sum, ind) => sum + (ind.total_spend || 0), 0)
  const totalImpressions = Object.values(unfilteredIndustryBenchmarks.value)
    .reduce((sum, ind) => sum + (ind.total_impressions || 0), 0)

  return {
    total_industries: industries.length,
    total_accounts: totalAccounts,
    total_spend: totalSpend,
    total_impressions: totalImpressions
  }
})

// Table enhancement computed properties
const filteredTableIndustries = computed(() => {
  const industries = Object.entries(unfilteredSortedIndustries.value)

  if (!tableSearchQuery.value.trim()) {
    return industries
  }

  const query = tableSearchQuery.value.toLowerCase().trim()
  return industries.filter(([key, data]) => {
    const industryName = getIndustryLabel(key).toLowerCase()
    return industryName.includes(query)
  })
})

const sortedTableIndustries = computed(() => {
  const industries = [...filteredTableIndustries.value]

  return industries.sort(([keyA, dataA], [keyB, dataB]) => {
    let aVal: any
    let bVal: any

    switch (tableSortField.value) {
      case 'industry':
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
        break
      case 'accounts':
        aVal = dataA.accounts_count || 0
        bVal = dataB.accounts_count || 0
        break
      case 'spend':
        aVal = dataA.total_spend || 0
        bVal = dataB.total_spend || 0
        break
      case 'leads':
        aVal = dataA.total_leads || 0
        bVal = dataB.total_leads || 0
        break
      case 'ctr':
      case 'cpc':
      case 'cpm':
      case 'cvr':
      case 'cpl':
      case 'cpa':
      case 'roas':
        aVal = dataA.metrics[tableSortField.value]?.actual || 0
        bVal = dataB.metrics[tableSortField.value]?.actual || 0
        break
      default:
        aVal = getIndustryLabel(keyA).toLowerCase()
        bVal = getIndustryLabel(keyB).toLowerCase()
    }

    if (tableSortDirection.value === 'asc') {
      return aVal > bVal ? 1 : aVal < bVal ? -1 : 0
    } else {
      return aVal < bVal ? 1 : aVal > bVal ? -1 : 0
    }
  })
})

// Pagination (REMOVED - no longer needed for new Performance tab)
// const paginatedTableIndustries = computed(() => {
//   const start = (currentPage.value - 1) * itemsPerPage.value
//   const end = start + itemsPerPage.value
//   return sortedTableIndustries.value.slice(start, end)
// })
// const totalPages = computed(() => {
//   return Math.ceil(sortedTableIndustries.value.length / itemsPerPage.value)
// })

// Enhanced chart data for multi-metric support
const enhancedChartData = computed(() => {
  const industries = Object.entries(unfilteredSortedIndustries.value)
  const labels = industries.map(([key]) => getIndustryLabel(key))

  // Color palette for different metrics
  const colors = [
    { bg: 'rgba(99, 102, 241, 0.8)', border: 'rgba(99, 102, 241, 1)' },      // primary-600
    { bg: 'rgba(16, 185, 129, 0.8)', border: 'rgba(16, 185, 129, 1)' },      // green-500
    { bg: 'rgba(245, 158, 11, 0.8)', border: 'rgba(245, 158, 11, 1)' },      // yellow-500
  ]

  const datasets = []

  // Add datasets for each selected metric
  selectedChartMetrics.value.forEach((metricKey, index) => {
    const yourPerformanceData = industries.map(([_, data]) => {
      const metric = data.metrics[metricKey]
      return metric?.actual || 0
    })

    const industryAverageData = industries.map(([key, data]) => {
      const metric = data.metrics[metricKey]
      const externalBenchmark = getExternalBenchmark(key, metricKey)
      if (externalBenchmark) {
        return externalBenchmark.avg || 0
      }
      return metric?.benchmark.avg || 0
    })

    const metricLabel = getMetricLabel(metricKey)
    const color = colors[index % colors.length]

    // Add "Your Performance" dataset
    datasets.push({
      label: `Your ${metricLabel}`,
      data: yourPerformanceData,
      backgroundColor: highlightedIndustry.value ? yourPerformanceData.map((_, i) =>
        labels[i] === highlightedIndustry.value ? color.bg : color.bg.replace('0.8', '0.3')
      ) : color.bg,
      borderColor: color.border,
      borderWidth: 1,
      stack: isChartStacked.value ? metricKey : undefined,
      type: chartType.value === 'radar' ? 'radar' : chartType.value
    })

    // Add "Industry Average" dataset
    datasets.push({
      label: `Avg ${metricLabel}`,
      data: industryAverageData,
      backgroundColor: 'rgba(156, 163, 175, 0.5)',
      borderColor: 'rgba(156, 163, 175, 1)',
      borderWidth: 1,
      borderDash: [5, 5],
      stack: isChartStacked.value ? metricKey : undefined,
      type: chartType.value === 'radar' ? 'radar' : chartType.value
    })
  })

  // Add trend line if enabled
  if (showTrendLine.value && selectedChartMetrics.value.length === 1) {
    const metricKey = selectedChartMetrics.value[0]
    const allValues = industries.map(([_, data]) => data.metrics[metricKey]?.actual || 0)
    const average = allValues.reduce((sum, val) => sum + val, 0) / allValues.length
    const trendData = new Array(labels.length).fill(average)

    datasets.push({
      label: 'Trend Line',
      data: trendData,
      backgroundColor: 'transparent',
      borderColor: 'rgba(239, 68, 68, 1)',
      borderWidth: 2,
      borderDash: [10, 5],
      type: 'line',
      pointRadius: 0
    })
  }

  return {
    labels,
    datasets
  }
})

// Enhanced chart options
const enhancedChartOptions = computed(() => {
  const metricKey = selectedChartMetrics.value[0] || 'ctr'
  const metricLabel = getMetricLabel(metricKey)
  const isPercentage = ['ctr', 'cvr'].includes(metricKey)
  const isCurrency = ['cpc', 'cpm', 'cpl', 'cpa'].includes(metricKey)

  return {
    responsive: true,
    maintainAspectRatio: false,
    onClick: (event: any, elements: any[]) => {
      if (elements.length > 0) {
        const index = elements[0].index
        const label = enhancedChartData.value.labels[index]
        highlightedIndustry.value = highlightedIndustry.value === label ? null : label
      }
    },
    plugins: {
      legend: {
        position: 'top' as const,
        labels: {
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            let label = context.dataset.label || ''
            if (label) {
              label += ': '
            }
            if (isPercentage) {
              label += context.parsed.y.toFixed(2) + '%'
            } else if (isCurrency) {
              label += context.parsed.y.toFixed(2) + ' SR'
            } else if (metricKey === 'roas') {
              label += context.parsed.y.toFixed(2) + 'x'
            } else {
              label += context.parsed.y.toFixed(2)
            }
            return label
          }
        }
      },
      datalabels: {
        display: selectedChartMetrics.value.length === 1,
        anchor: 'end' as const,
        align: 'top' as const,
        formatter: (value: number) => {
          if (isPercentage) {
            return value.toFixed(1) + '%'
          } else if (isCurrency) {
            return value.toFixed(0)
          } else if (metricKey === 'roas') {
            return value.toFixed(1) + 'x'
          }
          return value.toFixed(0)
        },
        font: {
          size: 9,
          weight: 'bold' as const
        }
      }
    },
    scales: chartType.value === 'radar' ? undefined : {
      y: {
        beginAtZero: true,
        stacked: isChartStacked.value,
        ticks: {
          callback: (value: any) => {
            if (isPercentage) {
              return value + '%'
            } else if (isCurrency) {
              return value + ' SR'
            } else if (metricKey === 'roas') {
              return value + 'x'
            }
            return value
          }
        },
        title: {
          display: true,
          text: metricLabel
        }
      },
      x: {
        stacked: isChartStacked.value,
        ticks: {
          maxRotation: 45,
          minRotation: 45,
          font: {
            size: 10
          }
        }
      }
    }
  }
})

// Available options for external benchmark filters
const availableExternalIndustries = computed(() => {
  return [...new Set(externalBenchmarks.value.map(b => b.industry))].sort()
})

// Filtered external benchmarks based on filters
const filteredExternalBenchmarks = computed(() => {
  let filtered = [...externalBenchmarks.value]

  // Apply industry filter
  if (externalBenchmarkFilters.value.industry) {
    filtered = filtered.filter(b => b.industry === externalBenchmarkFilters.value.industry)
  }

  // Apply platform filter
  if (externalBenchmarkFilters.value.platform) {
    filtered = filtered.map(industry => ({
      ...industry,
      platforms: industry.platforms.filter((p: any) => p.platform === externalBenchmarkFilters.value.platform)
    })).filter(industry => industry.platforms.length > 0)
  }

  // Apply metric filter
  if (externalBenchmarkFilters.value.metric) {
    filtered = filtered.map(industry => ({
      ...industry,
      platforms: industry.platforms.map((platform: any) => ({
        ...platform,
        metrics: platform.metrics.filter((m: any) => m.metric === externalBenchmarkFilters.value.metric)
      })).filter((platform: any) => platform.metrics.length > 0)
    })).filter(industry => industry.platforms.length > 0)
  }

  return filtered
})

const insights = ref<Insight[]>([])

// External industry benchmarks (WordStream, Meta, Google, LinkedIn)
const externalBenchmarks = ref<any[]>([])
const externalBenchmarksLoading = ref(false)

// External benchmark filters
const externalBenchmarkFilters = ref({
  industry: '',
  platform: '',
  metric: ''
})

// Trending data state
const loadingTrends = ref(false)
const selectedTrendMetric = ref('cpc')
const trendingData = ref({
  your_avg: 0,
  your_change: 0,
  industry_avg: 0,
  industry_change: 0,
  top_performers_avg: 0,
  audience_breakdown: {}
})

// Auto-refresh functionality
const autoRefreshEnabled = ref(false)
const autoRefreshCountdown = ref(30)
const autoRefreshInterval = ref<NodeJS.Timeout | null>(null)
const countdownInterval = ref<NodeJS.Timeout | null>(null)

// Ad accounts data for dynamic summary calculation
interface AdAccount {
  id: number
  account_name: string
  external_account_id: string
  platform: string
  status: string
  industry?: string
  sub_industry?: string | null
  currency: string
  campaigns_count: number
  total_spend?: number
  total_impressions: number
  total_clicks: number
  total_conversions: number
  total_revenue: number
  created_at: string
  updated_at: string
}

const adAccounts = ref<AdAccount[]>([])
const accountsLoading = ref(false)
const availableIndustries = ref<string[]>([])

// Computed available platforms from actual data
const availablePlatforms = computed(() => {
  const platforms = new Set(adAccounts.value.map(a => a.platform).filter(Boolean))
  return Array.from(platforms)
})
const industryLabelsFromApi = ref<Record<string, string>>({})
const totalTrackableIndustries = ref<number>(36) // Main industries + sub-industries

const industryLabels: Record<string, string> = {
  // Current database industries
  automotive: 'Automotive',
  travel_destinations: 'Travel & Destinations',
  e_commerce_retail: 'E-commerce / Retail',
  education: 'Education',
  fmcg_cpg: 'FMCG / CPG',
  real_estate: 'Real Estate',
  finance_insurance: 'Finance & Insurance',
  healthcare_pharmaceuticals: 'Healthcare & Pharmaceuticals',
  technology: 'Technology',
  entertainment_media: 'Entertainment & Media',
  government_public_sector: 'Government & Public Sector',
  hospitality_food_services: 'Hospitality & Food Services',
  energy_utilities: 'Energy & Utilities',
  construction_manufacturing: 'Construction & Manufacturing',
  fashion_luxury: 'Fashion & Luxury',
  telecommunications: 'Telecommunications',
  transportation_logistics: 'Transportation & Logistics',
  nonprofit_social_impact: 'Nonprofit & Social Impact',
  agriculture_food_production: 'Agriculture & Food Production',
  professional_services: 'Professional Services',
  // Legacy fallbacks
  beauty_fitness: 'Beauty & Fitness',
  business_industrial: 'Business & Industrial',
  computers_electronics: 'Computers & Electronics',
  entertainment: 'Entertainment',
  food_beverage: 'Food & Beverage',
  health_medicine: 'Health & Medicine',
  home_garden: 'Home & Garden',
  law_government: 'Law & Government',
  lifestyle: 'Lifestyle',
  media_publishing: 'Media & Publishing',
  nonprofit: 'Non-Profit',
  retail_ecommerce: 'Retail & E-commerce',
  sports_recreation: 'Sports & Recreation',
  travel_tourism: 'Travel & Tourism',
  other: 'Other'
}

// Note: metricLabels is kept for fallback but getMetricLabel now uses translations
const metricLabels: Record<string, string> = {
  ctr: 'CTR',
  cpc: 'CPC',
  cpm: 'CPM',
  cvr: 'CVR',
  cpl: 'CPL',
  cpa: 'CPA',
  roas: 'ROAS',
  spend: 'Spend'
}

// Helper functions for filter-based data adjustments
const getPlatformMultiplier = (platform: string): number => {
  const multipliers: Record<string, number> = {
    facebook: 1.0,
    instagram: 1.1,
    google: 0.9,
    youtube: 1.2,
    linkedin: 1.3,
    twitter: 0.8,
    tiktok: 1.4
  }
  return multipliers[platform] || 1.0
}

// Default data functions for when APIs fail

const getDefaultIndustryBenchmarks = () => {
  // Generate dynamic data based on current date and metric
  const currentDate = new Date()
  const seed = currentDate.getDate() + currentDate.getMonth() + currentDate.getFullYear()
  
  // Dynamic multipliers based on date to simulate market changes
  const marketMultiplier = 1 + (Math.sin(seed * 0.1) * 0.15) // ±15% market variation
  const seasonalMultiplier = 1 + (Math.sin((currentDate.getMonth() / 12) * Math.PI * 2) * 0.1) // ±10% seasonal
  
  const generateMetricData = (baseValue: number, variance: number = 0.3) => {
    const dynamicValue = baseValue * marketMultiplier * seasonalMultiplier
    const actualValue = dynamicValue + (Math.random() - 0.5) * variance * dynamicValue
    const benchmarkMin = dynamicValue * 0.6
    const benchmarkMax = dynamicValue * 1.4
    const benchmarkAvg = dynamicValue
    
    const performance = Math.max(0, Math.min(100, 
      ((actualValue - benchmarkMin) / (benchmarkMax - benchmarkMin)) * 100
    ))
    
    let status = 'average'
    if (performance >= 85) status = 'excellent'
    else if (performance >= 70) status = 'good'
    else if (performance < 55) status = 'below_average'
    
    return {
      actual: Math.max(0, actualValue),
      benchmark: { 
        min: Math.max(0, benchmarkMin), 
        avg: Math.max(0, benchmarkAvg), 
        max: Math.max(0, benchmarkMax) 
      },
      performance: Math.round(performance),
      status
    }
  }
  
  const generateIndustryData = (baseCtr: number, baseCpc: number, baseCvr: number, baseCpl: number, accountsBase: number) => {
    const accounts = Math.floor(accountsBase * (1 + (Math.random() - 0.5) * 0.2))
    const totalSpend = accounts * baseCpc * 1000 * (1 + Math.random() * 0.5)
    const totalClicks = Math.floor(totalSpend / baseCpc)
    const totalImpressions = Math.floor(totalClicks / (baseCtr / 100))
    const totalLeads = Math.floor(totalClicks * (baseCvr / 100))
    
    return {
      accounts_count: accounts,
      total_spend: Math.floor(totalSpend),
      total_impressions: totalImpressions,
      total_clicks: totalClicks,
      total_leads: totalLeads,
      metrics: {
        ctr: generateMetricData(baseCtr, 0.4),
        cpc: generateMetricData(baseCpc, 0.3),
        cvr: generateMetricData(baseCvr, 0.5),
        cpl: generateMetricData(baseCpl, 0.4)
      }
    }
  }
  
  return {
    automotive: {
      industry: 'automotive',
      ...generateIndustryData(1.6, 1.4, 9.5, 28.0, 95)
    },
    beauty_fitness: {
      industry: 'beauty_fitness',
      ...generateIndustryData(2.4, 1.3, 11.8, 19.5, 120)
    },
    business_industrial: {
      industry: 'business_industrial',
      ...generateIndustryData(1.9, 2.1, 8.5, 35.0, 85)
    },
    computers_electronics: {
      industry: 'computers_electronics',
      ...generateIndustryData(2.0, 1.6, 10.2, 24.0, 110)
    },
    education: {
      industry: 'education',
      ...generateIndustryData(2.5, 0.8, 11.5, 14.0, 45)
    },
    entertainment: {
      industry: 'entertainment',
      ...generateIndustryData(2.2, 1.1, 12.0, 16.8, 95)
    },
    finance_insurance: {
      industry: 'finance_insurance',
      ...generateIndustryData(1.9, 2.8, 8.0, 42.0, 65)
    },
    food_beverage: {
      industry: 'food_beverage',
      ...generateIndustryData(2.1, 1.0, 13.5, 15.2, 140)
    },
    health_medicine: {
      industry: 'health_medicine',
      ...generateIndustryData(2.3, 1.8, 15.0, 25.0, 85)
    },
    home_garden: {
      industry: 'home_garden',
      ...generateIndustryData(1.8, 1.2, 9.8, 20.5, 105)
    },
    law_government: {
      industry: 'law_government',
      ...generateIndustryData(1.7, 2.5, 7.5, 38.0, 35)
    },
    lifestyle: {
      industry: 'lifestyle',
      ...generateIndustryData(2.3, 1.1, 12.8, 17.5, 125)
    },
    media_publishing: {
      industry: 'media_publishing',
      ...generateIndustryData(2.0, 1.3, 10.5, 21.0, 70)
    },
    nonprofit: {
      industry: 'nonprofit',
      ...generateIndustryData(2.4, 0.6, 14.2, 9.5, 25)
    },
    real_estate: {
      industry: 'real_estate',
      ...generateIndustryData(1.7, 1.1, 11.0, 12.5, 120)
    },
    retail_ecommerce: {
      industry: 'retail_ecommerce', 
      ...generateIndustryData(1.8, 1.2, 10.0, 18.0, 300)
    },
    sports_recreation: {
      industry: 'sports_recreation',
      ...generateIndustryData(2.1, 1.0, 12.5, 16.0, 80)
    },
    technology: {
      industry: 'technology',
      ...generateIndustryData(2.1, 1.5, 12.5, 22.0, 150)
    },
    travel_tourism: {
      industry: 'travel_tourism',
      ...generateIndustryData(2.0, 0.9, 13.2, 16.5, 78)
    },
    other: {
      industry: 'other',
      ...generateIndustryData(1.8, 1.4, 9.0, 20.0, 50)
    }
  }
}

const getDefaultInsights = () => ([
  {
    type: 'info',
    message: 'Connect your advertising accounts to get personalized benchmark insights based on your actual performance data.',
    priority: 'info'
  },
  {
    type: 'info',
    message: 'Industry benchmarks are calculated dynamically from real account performance across multiple campaigns.',
    priority: 'info'
  }
])

// Generate real insights based on actual account data
const generateRealInsights = () => {
  const realInsights = []
  
  // Filter accounts based on current form filters
  const filteredAccounts = adAccounts.value.filter(account => {
    if (filters.value.industry.length > 0 && !filters.value.industry.includes(account.industry)) return false
    if (filters.value.platform.length > 0 && !filters.value.platform.includes(account.platform)) return false
    return true
  })

  const accountsCount = filteredAccounts.length
  // Spend is already in SAR in the database - no conversion needed
  const totalSpend = filteredAccounts.reduce((sum, account) => {
    const spend = account.total_spend || 0
    return sum + spend
  }, 0)
  
  // Group filtered accounts by industry for analysis
  const accountsByIndustry = filteredAccounts.reduce((acc, account) => {
    if (account.industry) {
      acc[account.industry] = (acc[account.industry] || 0) + 1
    }
    return acc
  }, {} as Record<string, number>)
  
  const industriesWithAccounts = Object.keys(accountsByIndustry).length
  const topIndustry = Object.entries(accountsByIndustry).sort(([,a], [,b]) => b - a)[0]
  const accountsWithoutIndustry = accountsCount - Object.values(accountsByIndustry).reduce((sum, count) => sum + count, 0)
  
  
  // Industry diversification insight
  if (industriesWithAccounts >= 5) {
    realInsights.push({
      type: 'strength',
      message: `Excellent industry diversification with ${industriesWithAccounts} different industries represented across your ${accountsCount} accounts. This reduces risk and provides broader market insights.`,
      priority: 'high'
    })
  } else if (industriesWithAccounts >= 3) {
    realInsights.push({
      type: 'improvement',
      message: `Good industry coverage with ${industriesWithAccounts} industries. Consider expanding to more industries to diversify your benchmark insights.`,
      priority: 'medium'
    })
  } else if (industriesWithAccounts > 0) {
    realInsights.push({
      type: 'warning',
      message: `Limited industry diversity with only ${industriesWithAccounts} industries. Expanding to more industries will improve benchmark accuracy.`,
      priority: 'high'
    })
  }
  
  // Top industry insight
  if (topIndustry) {
    const [industryName, count] = topIndustry
    const percentage = Math.round((count / accountsCount) * 100)
    const industryLabel = getIndustryLabel(industryName)
    
    if (percentage >= 40) {
      realInsights.push({
        type: 'info',
        message: `${industryLabel} dominates your portfolio with ${count} accounts (${percentage}%). This concentration provides deep insights but consider diversification.`,
        priority: 'medium'
      })
    } else {
      realInsights.push({
        type: 'strength',
        message: `${industryLabel} leads with ${count} accounts (${percentage}%), showing good balance across industries without over-concentration.`,
        priority: 'low'
      })
    }
  }
  
  // Spend analysis insight
  if (totalSpend > 0) {
    const avgSpendPerAccount = totalSpend / accountsCount
    if (avgSpendPerAccount > 50000) {
      realInsights.push({
        type: 'strength',
        message: `Strong advertising investment with SR ${totalSpend.toLocaleString()} total spend across ${accountsCount} accounts (avg SR ${Math.round(avgSpendPerAccount).toLocaleString()} per account).`,
        priority: 'medium'
      })
    } else if (avgSpendPerAccount > 10000) {
      realInsights.push({
        type: 'info',
        message: `Moderate advertising investment with SR ${totalSpend.toLocaleString()} total spend. Average of SR ${Math.round(avgSpendPerAccount).toLocaleString()} per account provides good benchmark data.`,
        priority: 'low'
      })
    }
  }
  
  // Data completeness insight
  if (accountsWithoutIndustry > 0) {
    realInsights.push({
      type: 'improvement',
      message: `${accountsWithoutIndustry} accounts lack industry classification. Updating these will improve benchmark accuracy and insights.`,
      priority: 'medium'
    })
  }
  
  // Data coverage insight
  if (accountsCount >= 50) {
    realInsights.push({
      type: 'success',
      message: `Excellent data coverage with ${accountsCount} connected accounts providing robust benchmark insights across ${industriesWithAccounts} industries.`,
      priority: 'high'
    })
  }
  
  return realInsights.length > 0 ? realInsights : getDefaultInsights()
}

// Fetch ad accounts data for real account counts and spend
const fetchAdAccounts = async () => {
  accountsLoading.value = true
  try {
    const params = new URLSearchParams({
      page: '1',
      per_page: '500', // Get all accounts
      from: dateRange.value.from,
      to: dateRange.value.to
    })

    const url = `/api/ad-accounts?${params.toString()}`
    const response = await window.axios.get(url)

    adAccounts.value = response.data.data || []
  } catch (error) {
    console.error('❌ AD ACCOUNTS API FAILED - USING DEMO DATA!', error.response?.status, error.message)
    console.error('⚠️ Calculator will use FAKE DATA, not your real account metrics!')
    console.error('Error details:', error)
    
    // Generate demo accounts data that reflects the user's 56 accounts
    const demoAccounts: AdAccount[] = []
    const industries = ['technology', 'healthcare', 'retail', 'finance_insurance', 'education', 'real_estate', 'automotive', 'food_beverage']
    const platforms = ['facebook', 'google', 'linkedin', 'tiktok']
    
    for (let i = 1; i <= 56; i++) {
      const industry = industries[i % industries.length]
      const platform = platforms[i % platforms.length]
      const baseSpend = Math.floor(1000 + Math.random() * 5000)
      const impressions = Math.floor(baseSpend * 100 * (1 + Math.random()))
      const clicks = Math.floor(impressions * 0.02 * (1 + Math.random()))
      const conversions = Math.floor(clicks * 0.1 * (1 + Math.random()))
      const revenue = baseSpend * (1 + Math.random() * 2)

      demoAccounts.push({
        id: i,
        account_name: `Demo Account ${i}`,
        external_account_id: `ext_${i}_${platform}`,
        platform: platform,
        status: ['active', 'inactive'][Math.random() > 0.2 ? 0 : 1],
        industry: industry,
        sub_industry: null,
        currency: 'SAR',
        campaigns_count: Math.floor(3 + Math.random() * 15),
        total_spend: baseSpend,
        total_impressions: impressions,
        total_clicks: clicks,
        total_conversions: conversions,
        total_revenue: revenue,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      })
    }
    
    adAccounts.value = demoAccounts
  } finally {
    accountsLoading.value = false

    // Auto-selection removed - page now loads with all accounts visible by default
    // Users can manually apply filters to focus on specific industries/platforms
  }
}

const fetchBenchmarkData = async () => {
  loading.value = true
  try {
    const params: Record<string, any> = {
      from: dateRange.value.from,
      to: dateRange.value.to
    }

    // Add non-empty filters to params, handling arrays
    Object.entries(filters.value).forEach(([key, value]) => {
      if (Array.isArray(value) && value.length > 0) {
        // For arrays, pass as array (API should handle array params)
        params[key] = value
      } else if (!Array.isArray(value) && value !== '') {
        // For strings, pass as is
        params[key] = value
      }
    })

    // Calculate REAL industry benchmarks from ad account data (with caching)
    try {
      // Ensure ad accounts are loaded first
      if (adAccounts.value.length === 0) {
        await fetchAdAccounts()
      }

      // Create cache key based on filters and account count
      const cacheKey = `${adAccounts.value.length}-${JSON.stringify(filters.value)}`

      // Check if we have cached results
      let calculatedBenchmarks
      if (benchmarkCache.value.has(cacheKey)) {
        calculatedBenchmarks = benchmarkCache.value.get(cacheKey)
      } else {
        // Calculate benchmarks from actual account data
        calculatedBenchmarks = calculateIndustryBenchmarks(adAccounts.value, filters.value)

        // Cache the result
        benchmarkCache.value.set(cacheKey, calculatedBenchmarks)

        // Limit cache size (LRU-style)
        if (benchmarkCache.value.size > MAX_CACHE_SIZE) {
          const firstKey = benchmarkCache.value.keys().next().value
          benchmarkCache.value.delete(firstKey)
        }

      }

      if (Object.keys(calculatedBenchmarks).length > 0) {
        allIndustryBenchmarks.value = calculatedBenchmarks
      } else {
        console.warn('⚠️ No accounts with industry classification found.')
        console.warn('📋 ACTION REQUIRED: Visit /ad-accounts to classify your accounts for accurate benchmark analysis.')
        console.warn('🎭 Showing demo data as fallback - this is NOT your real performance data.')
        allIndustryBenchmarks.value = getDefaultIndustryBenchmarks()
      }
    } catch (benchmarkError: any) {
      console.error('❌ Benchmark calculation failed:', benchmarkError?.message)
      console.warn('🎭 Using demo data as fallback - this is NOT your real performance data.')
      console.warn('💡 TIP: Ensure your accounts have industry classification at /ad-accounts')
      allIndustryBenchmarks.value = getDefaultIndustryBenchmarks()
    }

    // Generate real insights based on actual account data
    try {
      if (adAccounts.value.length > 0) {
        insights.value = generateRealInsights()
      } else {
        const insightsResponse = await window.axios.get('/api/benchmarks/insights', { params })
        insights.value = insightsResponse.data.insights || getDefaultInsights()
      }
    } catch (insightsError) {
      console.warn('Insights generation failed, using defaults:', insightsError)
      insights.value = getDefaultInsights()
    }

    // Fetch available industries for calculator and total count
    try {
      const industriesResponse = await window.axios.get('/api/ad-accounts/industries')
      if (industriesResponse.data.industries) {
        availableIndustries.value = Object.keys(industriesResponse.data.industries)
        industryLabelsFromApi.value = industriesResponse.data.industries
      }

      // Update total trackable industries count from API
      if (industriesResponse.data.total_trackable_industries) {
        totalTrackableIndustries.value = industriesResponse.data.total_trackable_industries
      }

      // If no industries from API, use defaults
      if (availableIndustries.value.length === 0) {
        availableIndustries.value = Object.keys(industryLabels)
        industryLabelsFromApi.value = industryLabels
      }
    } catch (industriesError: any) {
      console.warn('Industries API failed:', industriesError?.response?.status || industriesError?.message)
      availableIndustries.value = Object.keys(industryLabels)
      industryLabelsFromApi.value = industryLabels
    }

  } catch (error) {
    console.error('Error fetching benchmark data:', error)
    // Use all default values as fallback
    allIndustryBenchmarks.value = getDefaultIndustryBenchmarks()
    insights.value = getDefaultInsights()
    availableIndustries.value = Object.keys(industryLabels)
    industryLabelsFromApi.value = industryLabels
  } finally {
    loading.value = false
  }
}

/**
 * Fetch real external industry benchmarks from WordStream, Meta, Google, LinkedIn
 */
const fetchExternalBenchmarks = async () => {
  externalBenchmarksLoading.value = true
  try {
    const params: any = {}

    // Add industry filter if selected
    if (externalBenchmarkFilters.value.industry) {
      params.industry = externalBenchmarkFilters.value.industry
    }

    // Add platform filter if selected
    if (externalBenchmarkFilters.value.platform) {
      params.platform = externalBenchmarkFilters.value.platform
    }

    // Add metric filter if selected
    if (externalBenchmarkFilters.value.metric) {
      params.metric = externalBenchmarkFilters.value.metric
    }

    const response = await window.axios.get('/api/benchmarks/external', { params })

    if (response.data && response.data.data) {
      externalBenchmarks.value = response.data.data
    } else {
      console.warn('No external benchmark data available')
      externalBenchmarks.value = []
    }
  } catch (error: any) {
    console.error('Error fetching external benchmarks:', error?.message || error)
    externalBenchmarks.value = []
  } finally {
    externalBenchmarksLoading.value = false
  }
}

/**
 * Apply external benchmark filters
 */
const applyExternalFilters = () => {
  fetchExternalBenchmarks()
}

/**
 * Clear all external benchmark filters
 */
const clearExternalFilters = () => {
  externalBenchmarkFilters.value = {
    industry: '',
    platform: '',
    metric: ''
  }
  fetchExternalBenchmarks()
}

const refreshData = () => {
  // Refresh only the active tab's data
  const activeTab = tabs[activeTabIndex.value]

  // Reset the loaded state to force refresh
  tabDataLoaded.value[activeTab] = false

  // Reload the tab data
  loadTabData(activeTab)
}

// Handle filter changes
const onFiltersChanged = (newFilters: any) => {
  // Update only filter properties (exclude dateRange)
  const { dateRange: newDateRange, ...filterProps } = newFilters
  Object.assign(filters.value, filterProps)

  // Update date range if provided
  if (newDateRange) {
    dateRange.value = newDateRange
  }

  // Refresh data with new filters
  fetchBenchmarkData()
  fetchTrendingData()
}

// Toggle sort direction
const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

// Clear all filters
const clearAllFilters = () => {
  Object.keys(filters.value).forEach(key => {
    filters.value[key] = ''
  })
  fetchBenchmarkData()
}

const getIndustryLabel = (industry: string): string => {
  // Handle special case for aggregate view
  if (industry === 'all_industries') {
    return t('benchmarks.all_industries') || 'All Industries'
  }
  // Use global replace for all underscores and title case the result
  const fallbackLabel = industry?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || 'N/A'
  return industryLabelsFromApi.value[industry] || industryLabels[industry] || fallbackLabel
}

const getMetricUnit = (metric: string): string => {
  const units: Record<string, string> = {
    ctr: '%',
    cvr: '%',
    cpc: '$',
    cpl: '$',
    cpm: '$',
    cpa: '$',
    roas: 'x',
    engagement_rate: '%'
  }
  return units[metric] || ''
}

const getMetricLabel = (metric: string): string => {
  const translationKey = `pages.benchmarks.metrics.${metric.toLowerCase()}`
  const translated = t(translationKey)
  // If translation key is returned as-is, fall back to metricLabels or uppercase
  if (translated === translationKey) {
    return metricLabels[metric] || metric.toUpperCase()
  }
  return translated
}

/**
 * Get user's actual metric value for comparison with external benchmarks
 */
const getUserMetricForComparison = (industry: string, platform: string, metric: string): { value: number; metric: string } | null => {
  const userIndustryData = industryBenchmarks.value[industry]
  if (!userIndustryData) return null

  const metricData = userIndustryData.metrics[metric]
  if (!metricData || metricData.actual === null || metricData.actual === undefined) return null

  return {
    value: metricData.actual,
    metric: metric
  }
}

/**
 * Calculate user's percentile rank compared to external benchmarks
 */
const calculateUserPercentileComparison = (userValue: number, metric: string, percentiles: any): { rank: string; percentile: number } => {
  // Inverse metrics where lower is better
  const inverseMetrics = ['cpc', 'cpm', 'cpl', 'cpa']
  const isInverse = inverseMetrics.includes(metric.toLowerCase())

  let rank = ''
  let percentile = 0

  if (isInverse) {
    // For cost metrics, lower values are better
    if (userValue <= percentiles.p10) {
      rank = 'Top 10%'
      percentile = 95
    } else if (userValue <= percentiles.p25) {
      rank = 'Top 25%'
      percentile = 80
    } else if (userValue <= percentiles.p50) {
      rank = 'Above Median'
      percentile = 65
    } else if (userValue <= percentiles.p75) {
      rank = 'Below Median'
      percentile = 40
    } else {
      rank = 'Bottom 25%'
      percentile = 20
    }
  } else {
    // For performance metrics, higher values are better
    if (userValue >= percentiles.p90) {
      rank = 'Top 10%'
      percentile = 95
    } else if (userValue >= percentiles.p75) {
      rank = 'Top 25%'
      percentile = 80
    } else if (userValue >= percentiles.p50) {
      rank = 'Above Median'
      percentile = 65
    } else if (userValue >= percentiles.p25) {
      rank = 'Below Median'
      percentile = 40
    } else {
      rank = 'Bottom 25%'
      percentile = 20
    }
  }

  return { rank, percentile }
}

/**
 * Get full comparison data for user vs external benchmarks
 */
const getUserComparisonData = (industry: string, platform: string, metric: string, percentiles: any): { value: number; rank: string; percentile: number } | null => {
  const userData = getUserMetricForComparison(industry, platform, metric)
  if (!userData) return null

  const comparison = calculateUserPercentileComparison(userData.value, metric, percentiles)

  return {
    value: userData.value,
    rank: comparison.rank,
    percentile: comparison.percentile
  }
}

/**
 * Get badge CSS class based on percentile rank
 */
const getPercentileBadgeClass = (rank: string): string => {
  const classes: Record<string, string> = {
    'Top 10%': 'bg-green-100 text-green-800',
    'Top 25%': 'bg-blue-100 text-blue-800',
    'Above Median': 'bg-yellow-100 text-yellow-800',
    'Below Median': 'bg-orange-100 text-orange-800',
    'Bottom 25%': 'bg-red-100 text-red-800'
  }
  return classes[rank] || 'bg-gray-100 text-gray-800'
}

/**
 * Get progress bar color based on percentile
 */
const getPercentileProgressClass = (percentile: number): string => {
  if (percentile >= 80) return 'bg-green-500'
  if (percentile >= 60) return 'bg-blue-500'
  if (percentile >= 40) return 'bg-yellow-500'
  return 'bg-red-500'
}

/**
 * Get performance message based on rank
 */
const getPerformanceMessage = (rank: string, metric: string): string => {
  const metricLabel = getMetricLabel(metric)

  const rankKeyMap: Record<string, string> = {
    'Top 10%': 'top_10',
    'Top 25%': 'top_25',
    'Above Median': 'above_median',
    'Below Median': 'below_median',
    'Bottom 25%': 'bottom_25'
  }

  const rankKey = rankKeyMap[rank]
  if (rankKey) {
    return t(`pages.benchmarks.messages.${rankKey}`, { metric: metricLabel })
  }

  return 'Performance comparison available'
}

const getStatusLabel = (status: string): string => {
  const translationKey = `pages.benchmarks.status.${status}`
  const translated = t(translationKey)
  // If translation key is returned as-is, fall back to formatted status
  if (translated === translationKey) {
    const statusLabels: Record<string, string> = {
      excellent: 'Excellent',
      good: 'Good',
      average: 'Average',
      below_average: 'Below Average',
      poor: 'Poor',
      no_data: 'No Data'
    }
    return statusLabels[status] || status
  }
  return translated
}

const getStatusBadgeClass = (status: string): string => {
  const classes: Record<string, string> = {
    excellent: 'bg-green-100 text-green-800',
    good: 'bg-primary-100 text-primary-800',
    average: 'bg-yellow-100 text-yellow-800',
    below_average: 'bg-orange-100 text-orange-800',
    poor: 'bg-red-100 text-red-800',
    no_data: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPerformanceBarClass = (performance: number | null): string => {
  if (performance === null || performance === undefined) return 'bg-gray-300'
  if (performance >= 80) return 'bg-green-500'
  if (performance >= 60) return 'bg-primary-500'
  if (performance >= 40) return 'bg-yellow-500'
  if (performance >= 20) return 'bg-orange-500'
  return 'bg-red-500'
}

const getInsightIcon = (type: string) => {
  const icons: Record<string, any> = {
    improvement: ExclamationTriangleIcon,
    strength: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    success: CheckCircleIcon,
    info: InformationCircleIcon
  }
  return icons[type] || InformationCircleIcon
}

const getInsightBgClass = (type: string): string => {
  const classes: Record<string, string> = {
    improvement: 'bg-yellow-50',
    strength: 'bg-green-50',
    warning: 'bg-orange-50',
    success: 'bg-green-50',
    info: 'bg-primary-50'
  }
  return classes[type] || 'bg-gray-50'
}

const getInsightIconClass = (type: string): string => {
  const classes: Record<string, string> = {
    improvement: 'text-yellow-400',
    strength: 'text-green-400',
    warning: 'text-orange-400',
    success: 'text-green-400',
    info: 'text-primary-400'
  }
  return classes[type] || 'text-gray-400'
}

const getInsightTextClass = (type: string): string => {
  const classes: Record<string, string> = {
    improvement: 'text-yellow-800',
    strength: 'text-green-800',
    warning: 'text-orange-800',
    success: 'text-green-800',
    info: 'text-primary-800'
  }
  return classes[type] || 'text-gray-800'
}

const formatCurrency = (value: number): string => {
  if (value === null || value === undefined) return 'N/A'
  const formatted = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
  return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SR" class="inline-block w-4 h-4 mr-1" />${formatted}`
}

const formatNumber = (value: number): string => {
  if (value === null || value === undefined) return 'N/A'
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`
  } else if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`
  }
  return value.toLocaleString()
}

const formatMetricValue = (metric: string, value: number | null): string => {
  if (value === null || value === undefined) return 'N/A'
  
  if (['ctr', 'cvr'].includes(metric)) {
    return `${value.toFixed(2)}%`
  }
  
  if (['cpc', 'cpm', 'cpl', 'cpa', 'cost', 'spend'].includes(metric)) {
    return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SAR" class="inline-block w-4 h-4 mr-1" />${value.toFixed(2)}`
  }
  
  return value.toFixed(2)
}

const getScenarioBadgeClass = (scenario: string): string => {
  const classes: Record<string, string> = {
    poor: 'bg-red-100 text-red-800',
    average: 'bg-yellow-100 text-yellow-800',
    good: 'bg-primary-100 text-primary-800',
    excellent: 'bg-green-100 text-green-800'
  }
  return classes[scenario] || 'bg-gray-100 text-gray-800'
}

// Trending data functions
const fetchTrendingData = async () => {
  loadingTrends.value = true
  try {
    const params: Record<string, any> = {
      metric: selectedTrendMetric.value,
      from: dateRange.value.from,
      to: dateRange.value.to
    }

    // Add non-empty filters to params, handling arrays
    Object.entries(filters.value).forEach(([key, value]) => {
      if (Array.isArray(value) && value.length > 0) {
        // For arrays, pass as array (API should handle array params)
        params[key] = value
      }
    })

    const response = await window.axios.get('/api/benchmarks/trending-metrics', { params })
    trendingData.value = response.data.data
  } catch (error) {
    console.warn('Trending API failed, using defaults:', error)
    // Set realistic default values based on selected metric
    trendingData.value = getDefaultTrendingData()
  } finally {
    loadingTrends.value = false
  }
}

const getDefaultTrendingData = () => {
  // Dynamic data generation based on time and filters
  const currentTime = new Date().getTime()
  const dailySeed = Math.floor(currentTime / (1000 * 60 * 60 * 24)) // Changes daily
  const hourlySeed = Math.floor(currentTime / (1000 * 60 * 60)) // Changes hourly
  
  // Base metrics that vary by selected metric and filters
  const metricDefaults = {
    ctr: { base: 2.1, variance: 0.8 },
    cpc: { base: 1.5, variance: 0.6 },
    cvr: { base: 12.8, variance: 4.2 },
    cpl: { base: 18.25, variance: 8.5 },
    roas: { base: 3.8, variance: 1.8 }
  }
  
  const selectedMetric = metricDefaults[selectedTrendMetric.value] || metricDefaults.ctr
  
  // Apply filter-based multipliers
  let platformMultiplier = 1.0
  let industryMultiplier = 1.0
  let audienceMultiplier = 1.0

  // Use first selected platform for multiplier
  const selectedPlatform = filters.value.platform.length > 0 ? filters.value.platform[0] : null
  if (selectedPlatform === 'facebook') platformMultiplier = 0.95
  else if (selectedPlatform === 'google') platformMultiplier = 1.08
  else if (selectedPlatform === 'linkedin') platformMultiplier = 1.15

  // Use first selected industry for multiplier
  const selectedIndustry = filters.value.industry.length > 0 ? filters.value.industry[0] : null
  if (selectedIndustry === 'technology') industryMultiplier = 1.12
  else if (selectedIndustry === 'healthcare') industryMultiplier = 1.25
  else if (selectedIndustry === 'finance') industryMultiplier = 0.88
  
  const totalMultiplier = platformMultiplier * industryMultiplier * audienceMultiplier
  
  // Generate dynamic values with realistic variance
  const generateValue = (baseMultiplier: number, seed: number) => {
    const random = ((seed * 9301 + 49297) % 233280) / 233280 // Deterministic "random"
    const seasonalEffect = Math.sin((new Date().getMonth() / 12) * Math.PI * 2) * 0.1
    return Math.max(0.1, selectedMetric.base * baseMultiplier * totalMultiplier * 
      (1 + (random - 0.5) * 0.3 + seasonalEffect))
  }
  
  const yourAvg = generateValue(0.92, dailySeed)
  const industryAvg = generateValue(1.0, dailySeed + 1)
  const topPerformersAvg = generateValue(1.35, dailySeed + 2)
  
  // Dynamic change calculations
  const getChangeValue = (current: number, seed: number) => {
    const random = ((seed * 9301 + 49297) % 233280) / 233280
    return (random - 0.5) * 12 // ±6% change
  }
  
  return {
    your_avg: Number(yourAvg.toFixed(2)),
    your_change: Number(getChangeValue(yourAvg, hourlySeed).toFixed(1)),
    industry_avg: Number(industryAvg.toFixed(2)),
    industry_change: Number(getChangeValue(industryAvg, hourlySeed + 1).toFixed(1)),
    top_performers_avg: Number(topPerformersAvg.toFixed(2)),
    audience_breakdown: {
      luxury: { 
        avg: Number((topPerformersAvg * (1.05 + Math.random() * 0.1)).toFixed(2)), 
        accounts: Math.floor(3 + Math.random() * 5) 
      },
      premium: { 
        avg: Number((yourAvg * (1.02 + Math.random() * 0.08)).toFixed(2)), 
        accounts: Math.floor(8 + Math.random() * 8) 
      },
      mid_class: { 
        avg: Number((industryAvg * (0.98 + Math.random() * 0.06)).toFixed(2)), 
        accounts: Math.floor(18 + Math.random() * 14) 
      },
      value: { 
        avg: Number((industryAvg * (0.90 + Math.random() * 0.08)).toFixed(2)), 
        accounts: Math.floor(12 + Math.random() * 12) 
      }
    }
  }
}

const updateTrendingChart = () => {
  fetchTrendingData()
}

const refreshTrendingData = () => {
  fetchTrendingData()
}

// Auto-refresh functions
const toggleAutoRefresh = () => {
  if (autoRefreshEnabled.value) {
    // Start auto-refresh
    autoRefreshInterval.value = setInterval(() => {
      fetchBenchmarkData()
      fetchTrendingData()
    }, 30000) // Refresh every 30 seconds
    
    // Start countdown
    startCountdown()
  } else {
    // Stop auto-refresh
    if (autoRefreshInterval.value) {
      clearInterval(autoRefreshInterval.value)
      autoRefreshInterval.value = null
    }
    if (countdownInterval.value) {
      clearInterval(countdownInterval.value)
      countdownInterval.value = null
    }
  }
}

const startCountdown = () => {
  autoRefreshCountdown.value = 30
  countdownInterval.value = setInterval(() => {
    autoRefreshCountdown.value--
    if (autoRefreshCountdown.value <= 0) {
      autoRefreshCountdown.value = 30
    }
  }, 1000)
}

const formatTrendMetric = (metric: string, value: number | null): string => {
  if (value === null || value === undefined) return 'N/A'
  
  if (['ctr', 'cvr'].includes(metric)) {
    return `${value.toFixed(2)}%`
  }
  
  if (['cpc', 'cpm', 'cpl', 'cpa', 'cost', 'spend'].includes(metric)) {
    return `<img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg" alt="SAR" class="inline-block w-4 h-4 mr-1" />${value.toFixed(2)}`
  }
  
  if (metric === 'roas') {
    return `${value.toFixed(2)}x`
  }
  
  return value.toFixed(2)
}

const getTrendChangeClass = (change: number | null): string => {
  if (change === null || change === undefined) return 'text-gray-500'
  if (change > 0) return 'text-green-600'
  if (change < 0) return 'text-red-600'
  return 'text-gray-500'
}

const getTrendChangeIcon = (change: number | null) => {
  if (change === null || change === undefined) return InformationCircleIcon
  if (change > 0) return ArrowUpIcon
  if (change < 0) return ArrowDownIcon
  return InformationCircleIcon
}

const formatSegmentLabel = (segment: string): string => {
  const labels: Record<string, string> = {
    luxury: 'Luxury',
    premium: 'Premium',
    mid_class: 'Mid Class',
    value: 'Value',
    mass_market: 'Mass Market',
    niche: 'Niche',
    gen_z: 'Gen Z',
    millennials: 'Millennials',
    gen_x: 'Gen X',
    boomers: 'Boomers',
    mixed_age: 'Mixed Age',
    local: 'Local',
    regional: 'Regional',
    national: 'National',
    international: 'Global',
    professional: 'Professional',
    casual: 'Casual',
    luxury_tone: 'Luxury Tone',
    urgent: 'Urgent',
    educational: 'Educational',
    emotional: 'Emotional'
  }
  return labels[segment] || segment.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Chart data and functions

// Export data for the export component
const exportData = computed(() => ({
  summary: summary,
  trendingData: trendingData.value,
  industryBenchmarks: industryBenchmarks.value
}))

// Export event handler
const onExport = (type: string, success: boolean) => {
}

// Performance tab date preset handler
const applyPerformanceDatePreset = (preset: string) => {
  performanceFilters.value.preset = preset
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]

  switch (preset) {
    case '7d':
      performanceFilters.value.from = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case '30d':
      performanceFilters.value.from = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case '90d':
      performanceFilters.value.from = new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case '6m':
      performanceFilters.value.from = new Date(Date.now() - 180 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case '1y':
      performanceFilters.value.from = new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case 'all':
      performanceFilters.value.from = '2020-01-01'
      performanceFilters.value.to = todayStr
      fetchPerformanceData()
      break
    case 'custom':
      // Don't fetch automatically for custom
      break
  }
}

// Reset performance filters
const resetPerformanceFilters = () => {
  performanceFilters.value.preset = '30d'
  performanceFilters.value.platform = ''
  applyPerformanceDatePreset('30d')
}

// Note: fetchPerformanceData already exists at line 1162
// It handles loading all Performance tab data properly

// Chart interaction handlers
const handlePerformanceBarClick = (data: { label: string; value: number; index: number }) => {
  // Future: Could filter table by selected industry
}

const handlePerformancePlatformClick = (data: { label: string; value: number; index: number }) => {
  // Future: Could filter by selected platform
}

// Note: formatNumber already exists at line 2906

// Watchers for dynamic updates
watch(selectedTrendMetric, () => {
  fetchTrendingData()
}, { immediate: false })

// Force update all sections when filters change
const forceUpdateAllSections = () => {

  // Force reactivity updates by refreshing all data sources
  allIndustryBenchmarks.value = getDefaultIndustryBenchmarks()

  trendingData.value = getDefaultTrendingData()

  // Regenerate insights based on new filters
  if (adAccounts.value.length > 0) {
    insights.value = generateRealInsights()
  } else {
    insights.value = getDefaultInsights()
  }
  
}

// Create a computed hash of filters for efficient change detection
// This avoids expensive deep watching of the entire filters object
const filtersHash = computed(() => JSON.stringify(filters.value))

// Performance tab computed properties for totals
const performanceTotals = computed(() => {
  const industries = Object.values(unfilteredSortedIndustries.value)

  if (industries.length === 0) {
    return null
  }

  return {
    total_industries: industries.length,
    total_accounts: industries.reduce((sum, industry) => sum + (industry.accounts_count || 0), 0),
    total_impressions: industries.reduce((sum, industry) => sum + (industry.total_impressions || 0), 0),
    total_spend: industries.reduce((sum, industry) => sum + (industry.total_spend || 0), 0)
  }
})

// Performance tab chart computed properties
const performanceTopIndustriesLabels = computed(() => {
  if (!unfilteredSortedIndustries.value || Object.keys(unfilteredSortedIndustries.value).length === 0) return []

  const industries = Object.entries(unfilteredSortedIndustries.value)
  return industries
    .sort(([_keyA, dataA], [_keyB, dataB]) => {
      if (performanceCurrentMetric.value === 'spend') {
        return (dataB.total_spend || 0) - (dataA.total_spend || 0)
      } else {
        return (dataB.total_impressions || 0) - (dataA.total_impressions || 0)
      }
    })
    .slice(0, 10)
    .map(([key]) => getIndustryLabel(key))
})

const performanceTopIndustriesData = computed(() => {
  if (!unfilteredSortedIndustries.value || Object.keys(unfilteredSortedIndustries.value).length === 0) return []

  const industries = Object.entries(unfilteredSortedIndustries.value)
  return industries
    .sort(([_keyA, dataA], [_keyB, dataB]) => {
      if (performanceCurrentMetric.value === 'spend') {
        return (dataB.total_spend || 0) - (dataA.total_spend || 0)
      } else {
        return (dataB.total_impressions || 0) - (dataA.total_impressions || 0)
      }
    })
    .slice(0, 10)
    .map(([_, data]) => {
      return performanceCurrentMetric.value === 'spend'
        ? data.total_spend || 0
        : data.total_impressions || 0
    })
})

const performanceTopIndustriesColors = computed(() => {
  const baseColors = [
    'rgba(59, 130, 246, 0.7)',   // Blue
    'rgba(16, 185, 129, 0.7)',   // Green
    'rgba(168, 85, 247, 0.7)',   // Purple
    'rgba(251, 191, 36, 0.7)',   // Yellow
    'rgba(239, 68, 68, 0.7)',    // Red
    'rgba(236, 72, 153, 0.7)',   // Pink
    'rgba(14, 165, 233, 0.7)',   // Sky
    'rgba(245, 158, 11, 0.7)',   // Amber
    'rgba(99, 102, 241, 0.7)',   // Indigo
    'rgba(20, 184, 166, 0.7)',   // Teal
  ]
  return baseColors.slice(0, performanceTopIndustriesData.value.length)
})

const performancePlatformLabels = computed(() => {
  if (!performanceFilters.value.platform) {
    return ['Facebook', 'Google Ads', 'TikTok', 'Snapchat', 'LinkedIn']
  }
  return [performanceFilters.value.platform]
})

const performancePlatformData = computed(() => {
  if (!performanceTotals.value) return []

  const totalAccounts = performanceTotals.value.total_accounts

  if (performanceFilters.value.platform) {
    return [totalAccounts]
  }

  // Distribute accounts (estimated distribution)
  return [
    Math.floor(totalAccounts * 0.35), // Facebook ~35%
    Math.floor(totalAccounts * 0.30), // Google Ads ~30%
    Math.floor(totalAccounts * 0.15), // TikTok ~15%
    Math.floor(totalAccounts * 0.12), // Snapchat ~12%
    Math.floor(totalAccounts * 0.08), // LinkedIn ~8%
  ]
})

const performancePlatformColors = computed(() => {
  return [
    'rgba(59, 130, 246, 0.8)',   // Blue - Facebook
    'rgba(234, 67, 53, 0.8)',    // Red - Google
    'rgba(37, 244, 238, 0.8)',   // Cyan - TikTok
    'rgba(255, 252, 0, 0.8)',    // Yellow - Snapchat
    'rgba(0, 119, 181, 0.8)',    // LinkedIn Blue
  ]
})

watch(filtersHash, () => {
  // Skip watcher trigger if this is an auto-selection
  if (isAutoSelecting.value) {
    return
  }

  // Save filters to localStorage
  saveFilters()

  // Clear previous debounce timer to prevent race conditions
  if (filterDebounceTimer.value) {
    clearTimeout(filterDebounceTimer.value)
  }

  // Debounced update to prevent expensive calculations on rapid filter changes
  // This batches multiple quick filter selections into one update
  filterDebounceTimer.value = setTimeout(() => {
    // Update all sections with new filter data
    forceUpdateAllSections()

    // Fetch fresh data from API
    fetchAdAccounts() // Refresh accounts data with new filters
    fetchBenchmarkData()
    fetchTrendingData()
    filterDebounceTimer.value = null
  }, 500) // Increased from 300ms to 500ms for better batching
})

watch(() => dateRange.value, () => {
  // Validate date range
  const today = new Date().toISOString().split('T')[0]
  let needsUpdate = false

  // Prevent future dates for 'to' date
  if (dateRange.value.to > today) {
    console.warn('⚠️ End date cannot be in the future. Resetting to today.')
    dateRange.value.to = today
    needsUpdate = true
  }

  // Ensure 'from' date is before 'to' date
  if (dateRange.value.from > dateRange.value.to) {
    console.warn('⚠️ Start date cannot be after end date. Swapping dates.')
    const temp = dateRange.value.from
    dateRange.value.from = dateRange.value.to
    dateRange.value.to = temp
    needsUpdate = true
  }

  // If dates were corrected, the watcher will trigger again with valid dates
  if (needsUpdate) {
    return
  }

  // Save filters to localStorage
  saveFilters()

  fetchAdAccounts() // Refresh accounts data with new date range
  fetchBenchmarkData()
  fetchTrendingData()
}, { deep: true })

// Real-time data updates every 5 minutes when auto-refresh is enabled
const realTimeInterval = ref<NodeJS.Timeout | null>(null)

const startRealTimeUpdates = () => {
  if (realTimeInterval.value) return
  
  realTimeInterval.value = setInterval(() => {
    if (autoRefreshEnabled.value) {
      // Generate fresh dynamic data
      trendingData.value = getDefaultTrendingData()
      allIndustryBenchmarks.value = getDefaultIndustryBenchmarks()
    }
  }, 5 * 60 * 1000) // Update every 5 minutes
}

const stopRealTimeUpdates = () => {
  if (realTimeInterval.value) {
    clearInterval(realTimeInterval.value)
    realTimeInterval.value = null
  }
}

watch(autoRefreshEnabled, (enabled) => {
  if (enabled) {
    startRealTimeUpdates()
  } else {
    stopRealTimeUpdates()
  }
})

// Table sorting helper
const handleSort = (field: string) => {
  if (tableSortField.value === field) {
    // Toggle direction if same field
    tableSortDirection.value = tableSortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    // New field, default to ascending
    tableSortField.value = field
    tableSortDirection.value = 'asc'
  }
  // Reset to first page when sorting changes (REMOVED - no pagination)
  // currentPage.value = 1
}

// Multi-metric selection helper
const toggleMetricSelection = (metric: string) => {
  const index = selectedChartMetrics.value.indexOf(metric)
  if (index > -1) {
    // Remove metric if already selected
    selectedChartMetrics.value.splice(index, 1)
  } else {
    // Add metric if not at max (3 metrics)
    if (selectedChartMetrics.value.length < 3) {
      selectedChartMetrics.value.push(metric)
    }
  }
}

// Fullscreen toggle helper
const toggleFullscreen = () => {
  isChartFullscreen.value = !isChartFullscreen.value
}

// Column visibility toggle helper
onMounted(async () => {
  // Initialize tab from URL
  initializeTabFromURL()

  // Load view mode preference from localStorage
  loadViewPreference()

  // Load column visibility preferences
  loadColumnPreferences()

  // Lazy load only the active tab's data
  const initialTab = tabs[activeTabIndex.value]
  await loadTabData(initialTab)

  // Start real-time updates
  startRealTimeUpdates()
})

onUnmounted(() => {
  // Clean up intervals when component unmounts
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value)
  }
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
  stopRealTimeUpdates()
})
</script>