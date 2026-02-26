<template>
  <div class="min-h-screen bg-gray-50">
    <div class="px-4 sm:px-6 lg:px-8 py-6">

      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
          <div class="flex-1">
            <h1 class="text-2xl font-semibold text-gray-900">
              {{ $t('pages.industry_overview.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
              {{ $t('pages.industry_overview.description') }}
            </p>
          </div>

          <!-- Quick Actions -->
          <div class="mt-4 md:mt-0 flex space-x-3">
            <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
              </svg>
              Export
            </button>
            <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
              </svg>
              Share
            </button>
          </div>
        </div>
      </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
      <!-- Filter Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-medium text-gray-900">Filters</h3>
        <button
          v-if="filters.platform || filters.preset !== 'custom'"
          @click="resetFilters"
          class="text-sm text-primary-600 hover:text-primary-700 font-medium"
        >
          Clear filters
        </button>
      </div>

      <!-- Date Range Presets -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="preset in datePresets"
            :key="preset.value"
            @click="applyDatePreset(preset.value)"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-md',
              filters.preset === preset.value
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ preset.label }}
          </button>
        </div>
      </div>

      <!-- Custom Date Range and Platform Filter -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Custom Date Range -->
        <div v-if="filters.preset === 'custom'" class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Custom Date Range</label>
          <div class="flex space-x-2">
            <input
              v-model="filters.from"
              type="date"
              class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              @change="fetchData"
            />
            <span class="flex items-center text-gray-400">→</span>
            <input
              v-model="filters.to"
              type="date"
              class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              @change="fetchData"
            />
          </div>
        </div>

        <!-- Platform Filter -->
        <div :class="filters.preset === 'custom' ? '' : 'md:col-span-2'">
          <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
          <select
            v-model="filters.platform"
            class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            @change="fetchData"
          >
            <option value="">{{ $t('pages.industry_overview.all_platforms') }}</option>
            <option value="facebook">{{ $t('platforms.facebook') }}</option>
            <option value="google">{{ $t('pages.industry_overview.google_ads') }}</option>
            <option value="tiktok">{{ $t('platforms.tiktok') }}</option>
            <option value="snapchat">{{ $t('pages.industry_overview.snapchat') }}</option>
            <option value="linkedin">LinkedIn</option>
          </select>
        </div>

        <!-- Refresh Button -->
        <div class="flex items-end">
          <button
            @click="fetchData"
            :disabled="loading"
            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="loading">{{ $t('pages.industry_overview.loading') }}</span>
            <span v-else>{{ $t('dashboard.refresh') }}</span>
          </button>
        </div>
      </div>
    </div>
    <!-- Summary Cards -->
    <div v-if="totals" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Total Industries Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $t('pages.industry_overview.total_industries') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ industryData.length }}</p>
          </div>
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Accounts Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $t('pages.industry_overview.total_accounts') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ totals.total_accounts.toLocaleString() }}</p>
          </div>
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Impressions Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $t('pages.industry_overview.total_impressions') }}</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ formatNumber(totals.total_impressions) }}</p>
          </div>
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Spend Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $t('kpis.spend') }}</p>
            <div class="mt-1 text-2xl font-semibold text-gray-900">
              <CurrencyDisplay
                :amount="totals.total_spend"
                currency="SAR"
                :compact="true"
                icon-size="1.2em"
                container-class="text-2xl font-semibold text-gray-900"
              />
            </div>
          </div>
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Visualizations -->
    <div v-if="!loading && totals && industryData.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Industry Performance Chart -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-medium text-gray-900">
            Top Industries by {{ currentMetric === 'spend' ? 'Spend' : 'Impressions' }}
          </h3>
          <div class="flex space-x-2">
            <button
              @click="currentMetric = 'spend'"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-md',
                currentMetric === 'spend'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              Spend
            </button>
            <button
              @click="currentMetric = 'impressions'"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-md',
                currentMetric === 'impressions'
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              Impressions
            </button>
          </div>
        </div>
        <div style="height: 400px;">
          <HorizontalBarChart
            :labels="topIndustriesLabels"
            :datasets="[{
              label: currentMetric === 'spend' ? 'Total Spend (SAR)' : 'Total Impressions',
              data: topIndustriesData,
              backgroundColor: topIndustriesColors,
              borderColor: topIndustriesColors.map(c => c.replace('0.7', '1')),
              borderWidth: 2
            }]"
            @barClick="handleBarClick"
          />
        </div>
      </div>

      <!-- Platform Distribution Chart -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-medium text-gray-900 mb-4">
          Accounts by Platform
        </h3>
        <div style="height: 400px;">
          <DoughnutChart
            v-if="platformData.length > 0"
            :labels="platformLabels"
            :data="platformData"
            :backgroundColor="platformColors"
            @segmentClick="handlePlatformClick"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            <p>No platform data available</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
      <div class="flex">
        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">{{ $t('pages.industry_overview.error_loading') }}</h3>
          <p class="text-sm text-red-700 mt-1">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Industry Data Table -->
    <div v-if="!loading && industryData.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <!-- Table Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex-1">
            <h2 class="text-base font-medium text-gray-900">
              {{ $t('pages.industry_overview.industry_performance') }}
            </h2>
            <p class="text-sm text-gray-600 mt-1">Click to expand industry categories</p>
          </div>

          <!-- Search Bar -->
          <div class="flex items-center space-x-3">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search industries..."
                class="block w-full md:w-64 pl-9 pr-9 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500"
              />
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button
                  @click="searchQuery = ''"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                </button>
              </div>
            </div>
            <span class="text-sm text-gray-500 whitespace-nowrap">
              {{ filteredIndustryData.length }} / {{ industryData.length }}
            </span>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                @click="handleSort('industry')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                <div class="flex items-center space-x-2">
                  <span>{{ $t('pages.industry_overview.industry') }}</span>
                  <svg
                    v-if="sortBy === 'industry'"
                    class="w-4 h-4 text-primary-600 transition-transform"
                    :class="{ 'rotate-180': sortOrder === 'desc' }"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"></path>
                  </svg>
                </div>
              </th>
              <th
                @click="handleSort('accounts')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ $t('pages.industry_overview.accounts') }}</span>
                  <svg
                    v-if="sortBy === 'accounts'"
                    class="w-4 h-4 text-primary-600"
                    :class="{ 'rotate-180': sortOrder === 'desc' }"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </th>
              <th
                @click="handleSort('impressions')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ $t('pages.industry_overview.total_impressions') }}</span>
                  <svg
                    v-if="sortBy === 'impressions'"
                    class="w-4 h-4 text-primary-600"
                    :class="{ 'rotate-180': sortOrder === 'desc' }"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </th>
              <th
                @click="handleSort('spend')"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ $t('kpis.spend') }}</span>
                  <svg
                    v-if="sortBy === 'spend'"
                    class="w-4 h-4 text-primary-600"
                    :class="{ 'rotate-180': sortOrder === 'desc' }"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <template v-for="item in filteredIndustryData" :key="item.industry">
              <!-- Industry Row -->
              <tr
                @click="toggleIndustry(item.industry)"
                class="hover:bg-gray-50 cursor-pointer"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <component
                      :is="isIndustryExpanded(item.industry) ? ChevronDownIcon : ChevronRightIcon"
                      class="w-4 h-4 text-gray-400 mr-2"
                    />
                    <div class="text-sm font-medium text-gray-900">
                      {{ item.industry_display }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ item.accounts_count.toLocaleString() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatNumber(item.total_impressions) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <CurrencyDisplay
                    :amount="item.total_spend"
                    currency="SAR"
                    :compact="false"
                    icon-size="0.8em"
                  />
                </td>
              </tr>

              <!-- Category Rows (Expanded) -->
              <template v-if="isIndustryExpanded(item.industry) && item.categories && item.categories.length > 0">
                <tr
                  v-for="category in item.categories"
                  :key="`${item.industry}-${category.category}`"
                  class="bg-gray-50 hover:bg-gray-100"
                >
                  <td class="px-6 py-3 whitespace-nowrap">
                    <div class="flex items-center pl-10">
                      <div class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-2"></div>
                      <div class="text-sm text-gray-700">
                        {{ category.category_display }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                    {{ category.accounts_count.toLocaleString() }}
                  </td>
                  <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                    {{ category.total_impressions.toLocaleString() }}
                  </td>
                  <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                    <CurrencyDisplay
                      :amount="category.total_spend"
                      currency="SAR"
                      :compact="false"
                      icon-size="0.7em"
                    />
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && industryData.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('messages.no_data') }}</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('pages.industry_overview.no_data') }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="animate-spin mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.industry_overview.loading') }}</h3>
    </div>
    </div><!-- Close container -->
  </div><!-- Close bg-gray-50 -->
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { formatSAR, formatSARCompact, formatSARWithIcon } from '@/utils/currency'
import CurrencyDisplay from '@/components/CurrencyDisplay.vue'
import { aggregateByIndustryWithCategories, filterByPlatform, type AdAccount } from '@/utils/industryAggregator'
import { ChevronDownIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import HorizontalBarChart from '@/components/charts/HorizontalBarChart.vue'
import DoughnutChart from '@/components/charts/DoughnutChart.vue'

const { t } = useI18n()

// Data
const loading = ref(false)
const error = ref('')
const industryData = ref([])
const totals = ref(null)
const currencyInfo = ref(null)
const expandedIndustries = ref<string[]>([])
const searchQuery = ref('')
const sortBy = ref<'industry' | 'accounts' | 'impressions' | 'spend'>('spend')
const sortOrder = ref<'asc' | 'desc'>('desc')

// Filters
const filters = ref({
  from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days ago
  to: new Date().toISOString().split('T')[0], // today
  platform: '',
  preset: '30d' // Default preset
})

// Date presets
const datePresets = [
  { label: 'Last 7 Days', value: '7d' },
  { label: 'Last 30 Days', value: '30d' },
  { label: 'Last 90 Days', value: '90d' },
  { label: 'Last 6 Months', value: '6m' },
  { label: 'Last Year', value: '1y' },
  { label: 'All Time', value: 'all' },
  { label: 'Custom', value: 'custom' }
]

// Apply date preset
const applyDatePreset = (preset: string) => {
  filters.value.preset = preset
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]

  switch (preset) {
    case '7d':
      filters.value.from = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      filters.value.to = todayStr
      fetchData()
      break
    case '30d':
      filters.value.from = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      filters.value.to = todayStr
      fetchData()
      break
    case '90d':
      filters.value.from = new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      filters.value.to = todayStr
      fetchData()
      break
    case '6m':
      filters.value.from = new Date(Date.now() - 180 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      filters.value.to = todayStr
      fetchData()
      break
    case '1y':
      filters.value.from = new Date(Date.now() - 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      filters.value.to = todayStr
      fetchData()
      break
    case 'all':
      filters.value.from = '2020-01-01' // Set to a far back date
      filters.value.to = todayStr
      fetchData()
      break
    case 'custom':
      // Don't fetch automatically for custom - user will set dates
      break
  }
}

// Reset filters
const resetFilters = () => {
  filters.value.preset = '30d'
  filters.value.platform = ''
  applyDatePreset('30d')
}

// Chart state
const currentMetric = ref<'spend' | 'impressions'>('spend')

// Chart computed properties
const topIndustriesLabels = computed(() => {
  if (!industryData.value || industryData.value.length === 0) return []

  return industryData.value
    .slice()
    .sort((a: any, b: any) => {
      if (currentMetric.value === 'spend') {
        return b.total_spend - a.total_spend
      } else {
        return b.total_impressions - a.total_impressions
      }
    })
    .slice(0, 10)
    .map((industry: any) => industry.industry_display)
})

const topIndustriesData = computed(() => {
  if (!industryData.value || industryData.value.length === 0) return []

  return industryData.value
    .slice()
    .sort((a: any, b: any) => {
      if (currentMetric.value === 'spend') {
        return b.total_spend - a.total_spend
      } else {
        return b.total_impressions - a.total_impressions
      }
    })
    .slice(0, 10)
    .map((industry: any) => {
      return currentMetric.value === 'spend'
        ? industry.total_spend
        : industry.total_impressions
    })
})

const topIndustriesColors = computed(() => {
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
  return baseColors.slice(0, topIndustriesData.value.length)
})

const platformLabels = computed(() => {
  // Count accounts by platform from industryData
  const platformCounts = new Map<string, number>()

  industryData.value.forEach((industry: any) => {
    if (industry.categories && industry.categories.length > 0) {
      industry.categories.forEach((category: any) => {
        // Assuming accounts have platform info - we'll extract from account data
        // For now, we'll use a simple count based on the filter
        const platform = filters.value.platform || 'All Platforms'
        platformCounts.set(platform, (platformCounts.get(platform) || 0) + category.accounts_count)
      })
    }
  })

  // If no platform filter, show all platform types
  if (!filters.value.platform && totals.value) {
    // Return common platform names
    return ['Facebook', 'Google Ads', 'TikTok', 'Snapchat', 'LinkedIn']
  }

  return Array.from(platformCounts.keys())
})

const platformData = computed(() => {
  // For now, distribute accounts evenly across platforms
  // This is a simplified version - in production, you'd get this from account platform field
  if (!totals.value) return []

  const totalAccounts = totals.value.total_accounts
  const platforms = platformLabels.value

  if (filters.value.platform) {
    // If filtered, show all accounts for that platform
    return [totalAccounts]
  }

  // Distribute accounts (in production, this would come from actual platform data)
  // For demo purposes, we'll use estimated distribution
  return [
    Math.floor(totalAccounts * 0.35), // Facebook ~35%
    Math.floor(totalAccounts * 0.30), // Google Ads ~30%
    Math.floor(totalAccounts * 0.15), // TikTok ~15%
    Math.floor(totalAccounts * 0.12), // Snapchat ~12%
    Math.floor(totalAccounts * 0.08), // LinkedIn ~8%
  ]
})

const platformColors = computed(() => {
  return [
    'rgba(59, 130, 246, 0.8)',   // Blue - Facebook
    'rgba(234, 67, 53, 0.8)',    // Red - Google
    'rgba(37, 244, 238, 0.8)',   // Cyan - TikTok
    'rgba(255, 252, 0, 0.8)',    // Yellow - Snapchat
    'rgba(0, 119, 181, 0.8)',    // LinkedIn Blue
  ]
})

// Filtered and sorted industry data
const filteredIndustryData = computed(() => {
  if (!industryData.value || industryData.value.length === 0) return []

  // Filter by search query
  let filtered = industryData.value.filter((industry: any) => {
    if (!searchQuery.value) return true
    const query = searchQuery.value.toLowerCase()
    return industry.industry_display.toLowerCase().includes(query) ||
           industry.industry.toLowerCase().includes(query)
  })

  // Sort by selected column
  filtered = filtered.slice().sort((a: any, b: any) => {
    let aValue: any, bValue: any

    switch (sortBy.value) {
      case 'industry':
        aValue = a.industry_display
        bValue = b.industry_display
        break
      case 'accounts':
        aValue = a.accounts_count
        bValue = b.accounts_count
        break
      case 'impressions':
        aValue = a.total_impressions
        bValue = b.total_impressions
        break
      case 'spend':
        aValue = a.total_spend
        bValue = b.total_spend
        break
      default:
        aValue = a.total_spend
        bValue = b.total_spend
    }

    // Handle string vs number comparison
    if (typeof aValue === 'string') {
      return sortOrder.value === 'asc'
        ? aValue.localeCompare(bValue)
        : bValue.localeCompare(aValue)
    } else {
      return sortOrder.value === 'asc'
        ? aValue - bValue
        : bValue - aValue
    }
  })

  return filtered
})

// Event handlers
const handleBarClick = (data: { label: string; value: number; index: number }) => {
  // Future: Filter table by selected industry
  // Could also navigate to industry detail page
}

const handlePlatformClick = (data: { label: string; value: number; index: number }) => {
  // Future: Filter by selected platform
}

// Sorting handler
const handleSort = (column: 'industry' | 'accounts' | 'impressions' | 'spend') => {
  if (sortBy.value === column) {
    // Toggle sort order if clicking the same column
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    // Set new column and default to descending for numbers, ascending for strings
    sortBy.value = column
    sortOrder.value = column === 'industry' ? 'asc' : 'desc'
  }
}

// Methods
const fetchData = async () => {
  loading.value = true
  error.value = ''

  try {
    // Fetch all ad accounts data with filters
    const params = new URLSearchParams({
      from: filters.value.from,
      to: filters.value.to,
      _t: Date.now().toString() // Cache bust
    })
    if (filters.value.platform) {
      params.append('platform', filters.value.platform)
    }
    const url = `/api/ad-accounts?${params.toString()}`
    console.log('IndustryOverview fetchData - calling URL:', url)
    const response = await axios.get(url)
    let accounts: AdAccount[] = response.data.data || []

    // Apply platform filter if specified
    if (filters.value.platform) {
      accounts = filterByPlatform(accounts, filters.value.platform)
    }

    // Aggregate accounts by industry with categories
    const aggregated = aggregateByIndustryWithCategories(accounts)

    // Set industry data
    industryData.value = aggregated.industries

    // Set totals
    totals.value = {
      total_industries: aggregated.totals.total_industries,
      total_accounts: aggregated.totals.total_accounts,
      total_impressions: aggregated.totals.total_impressions,
      total_spend: aggregated.totals.total_spend
    }

    // Set currency info if there are multiple currencies
    const currencies = new Set(accounts.map(acc => acc.currency))
    if (currencies.size > 1) {
      currencyInfo.value = {
        note: `Displaying amounts in SAR. Original currencies: ${Array.from(currencies).join(', ')}`
      }
    } else {
      currencyInfo.value = null
    }
  } catch (err: any) {
    console.error('Error fetching industry overview:', err)
    error.value = err.response?.data?.error || 'Failed to load industry data'
  } finally {
    loading.value = false
  }
}

const formatNumber = (num: number): string => {
  if (num >= 1000000000) {
    return (num / 1000000000).toFixed(1) + 'B'
  } else if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toLocaleString()
}

const toggleIndustry = (industry: string) => {
  const index = expandedIndustries.value.indexOf(industry)
  if (index > -1) {
    expandedIndustries.value.splice(index, 1)
  } else {
    expandedIndustries.value.push(industry)
  }
}

const isIndustryExpanded = (industry: string): boolean => {
  return expandedIndustries.value.includes(industry)
}

// Lifecycle
onMounted(() => {
  fetchData()
})
</script>