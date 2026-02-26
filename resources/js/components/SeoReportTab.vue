<template>
  <div>
    <!-- Settings Panel -->
    <SeoPropertiesSettings
      :tenant-id="tenantId"
      @saved="fetchReport"
    />

    <!-- Date Range Filter -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <div class="flex flex-wrap items-center gap-4">
        <span class="text-sm font-medium text-gray-600">{{ $t('client_dashboard.seo.period') }}:</span>
        <div class="inline-flex rounded-lg bg-gray-100 p-1">
          <button
            v-for="p in periods"
            :key="p.value"
            @click="selectedPeriod = p.value"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200',
              selectedPeriod === p.value
                ? 'bg-white text-primary-700 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            ]"
          >
            {{ p.label }}
          </button>
        </div>
        <div v-if="loading" class="ml-auto flex items-center text-sm text-gray-500">
          <svg class="animate-spin h-4 w-4 mr-2 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ $t('common.loading') }}
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !reportData" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <template v-else-if="reportData">
      <!-- ========== KEY METRICS OVERVIEW ========== -->
      <div v-if="reportData.search_console?.summary || reportData.ga4?.summary" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
        <div v-if="reportData.ga4?.summary?.active_users" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.active_users') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNumber(reportData.ga4.summary.active_users) }}</p>
        </div>
        <div v-if="reportData.search_console?.summary" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.search_impressions') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNumber(reportData.search_console.summary.total_impressions) }}</p>
        </div>
        <div v-if="reportData.search_console?.summary" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.avg_position') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ reportData.search_console.summary.avg_position }}</p>
        </div>
        <div v-if="reportData.search_console?.summary" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.search_clicks') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNumber(reportData.search_console.summary.total_clicks) }}</p>
        </div>
        <div v-if="reportData.ga4?.summary?.total_revenue" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.revenue') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatCurrency(reportData.ga4.summary.total_revenue) }}</p>
        </div>
        <div v-if="reportData.pagespeed_mobile?.scores?.performance > 0" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.mobile_speed') }}</p>
          <p class="text-xl font-bold" :class="getScoreColor(reportData.pagespeed_mobile.scores.performance)">
            {{ reportData.pagespeed_mobile.scores.performance }}
          </p>
        </div>
        <div v-if="reportData.ga4?.summary?.sessions" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.sessions') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNumber(reportData.ga4.summary.sessions) }}</p>
        </div>
        <div v-if="reportData.ga4?.summary?.page_views" class="bg-white shadow rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('client_dashboard.seo.page_views') }}</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNumber(reportData.ga4.summary.page_views) }}</p>
        </div>
      </div>

      <!-- ========== DEVICE DISTRIBUTION ========== -->
      <div v-if="reportData.ga4?.devices?.length" class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.device_distribution') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="device in reportData.ga4.devices" :key="device.device" class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg">
            <span class="text-2xl">{{ getDeviceIcon(device.device) }}</span>
            <div>
              <p class="text-sm font-medium text-gray-900 capitalize">{{ device.device }}</p>
              <p class="text-lg font-bold text-gray-900">{{ formatNumber(device.users) }}</p>
              <p class="text-xs text-gray-500">{{ device.share }}% of users</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== PAGE SPEED PERFORMANCE ========== -->
      <div v-if="hasPageSpeedData" class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('client_dashboard.seo.pagespeed_title') }}</h3>
          <div class="inline-flex rounded-lg bg-gray-100 p-1">
            <button
              @click="psStrategy = 'mobile'"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md transition-all',
                psStrategy === 'mobile' ? 'bg-white text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              {{ $t('client_dashboard.seo.mobile') }}
            </button>
            <button
              @click="psStrategy = 'desktop'"
              :class="[
                'px-3 py-1 text-sm font-medium rounded-md transition-all',
                psStrategy === 'desktop' ? 'bg-white text-primary-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              {{ $t('client_dashboard.seo.desktop') }}
            </button>
          </div>
        </div>

        <!-- Score Gauges -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
          <div v-for="(label, key) in scoreLabels" :key="key" class="flex flex-col items-center">
            <div class="relative w-24 h-24 mb-2">
              <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="42" stroke="#e5e7eb" stroke-width="8" fill="none" />
                <circle
                  cx="50" cy="50" r="42"
                  :stroke="getScoreHex(currentPS?.scores?.[key] ?? 0)"
                  stroke-width="8" fill="none"
                  stroke-linecap="round"
                  :stroke-dasharray="`${(currentPS?.scores?.[key] ?? 0) * 2.64} 264`"
                />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-xl font-bold" :class="getScoreColor(currentPS?.scores?.[key] ?? 0)">
                  {{ currentPS?.scores?.[key] ?? 0 }}
                </span>
              </div>
            </div>
            <p class="text-sm font-medium text-gray-700 text-center">{{ label }}</p>
          </div>
        </div>

        <!-- Core Web Vitals -->
        <h4 class="text-md font-semibold text-gray-800 mb-3">{{ $t('client_dashboard.seo.core_web_vitals') }}</h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.metric') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.mobile') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.desktop') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.description') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(desc, key) in cwvMetrics" :key="key">
                <td class="px-4 py-2 text-sm font-medium text-gray-900 uppercase">{{ key }}</td>
                <td class="px-4 py-2 text-sm">
                  <span :class="getCwvStatusClass(reportData.pagespeed_mobile?.core_web_vitals?.[key]?.score)">
                    {{ reportData.pagespeed_mobile?.core_web_vitals?.[key]?.display ?? '-' }}
                  </span>
                </td>
                <td class="px-4 py-2 text-sm">
                  <span :class="getCwvStatusClass(reportData.pagespeed_desktop?.core_web_vitals?.[key]?.score)">
                    {{ reportData.pagespeed_desktop?.core_web_vitals?.[key]?.display ?? '-' }}
                  </span>
                </td>
                <td class="px-4 py-2 text-sm text-gray-500">{{ desc }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PageSpeed: URL configured but fetch failed -->
      <div v-else-if="reportData?.pagespeed_error" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.pagespeed_no_data') }}</p>
      </div>

      <!-- PageSpeed: No URL configured -->
      <div v-else-if="reportData && !reportData.pagespeed_mobile && !reportData.pagespeed_desktop" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.configure_pagespeed') }}</p>
      </div>

      <!-- ========== TRAFFIC SOURCES ========== -->
      <div v-if="reportData.ga4?.traffic_sources?.length" class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.traffic_sources') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.channel') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.sessions') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.share') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.revenue') }}</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.distribution') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="source in reportData.ga4.traffic_sources" :key="source.channel" class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ source.channel }}</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(source.sessions) }}</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ source.share }}%</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatCurrency(source.revenue) }}</td>
                <td class="px-4 py-2">
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-primary-600 h-2 rounded-full" :style="{ width: source.share + '%' }"></div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========== GEOGRAPHIC DISTRIBUTION ========== -->
      <div v-if="reportData.ga4?.geo?.length" class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.geographic_distribution') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.country') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.sessions') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.users') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.share') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.engagement') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="geo in reportData.ga4.geo" :key="geo.country" class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ geo.country }}</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(geo.sessions) }}</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(geo.users) }}</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ geo.share }}%</td>
                <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ geo.engagement_rate }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========== SEARCH CONSOLE SECTION ========== -->
      <div v-if="reportData.search_console && !reportData.search_console.error" class="space-y-6 mb-6">
        <!-- SC Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.total_clicks') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(reportData.search_console.summary.total_clicks) }}</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.total_impressions') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(reportData.search_console.summary.total_impressions) }}</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.avg_ctr') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ reportData.search_console.summary.avg_ctr }}%</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.avg_position') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ reportData.search_console.summary.avg_position }}</p>
          </div>
        </div>

        <!-- SC Time Series Chart -->
        <div v-if="scChartData" class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.search_performance') }}</h3>
          <InteractiveChart
            :key="'sc-ts-' + selectedPeriod"
            type="line"
            :data="scChartData"
            :height="300"
            :options="scChartOptions"
          />
        </div>

        <!-- Top Search Queries -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.top_queries') }}</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.query') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.clicks') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.impressions') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.ctr') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.position') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="row in reportData.search_console.queries" :key="row.key" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ row.key }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(row.clicks) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(row.impressions) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ row.ctr }}%</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ row.position }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top Pages -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.top_search_pages') }}</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.page') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.clicks') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.impressions') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.ctr') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.position') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="row in reportData.search_console.pages" :key="row.key" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm font-medium text-gray-900 truncate max-w-xs" :title="row.key">{{ row.key }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(row.clicks) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(row.impressions) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ row.ctr }}%</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ row.position }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SC Not Configured -->
      <div v-else-if="!reportData.search_console" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.configure_sc') }}</p>
      </div>

      <!-- SC Needs Re-auth -->
      <div v-else-if="reportData.search_console?.error === 'needs_reauth'" class="bg-primary-50 border border-primary-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm font-medium text-primary-800">{{ $t('client_dashboard.seo.needs_reauth') }}</p>
        <p class="text-sm text-primary-700 mt-1">{{ $t('client_dashboard.seo.needs_reauth_desc') }}</p>
        <div class="mt-4">
          <button @click="connectGoogle" :disabled="connecting"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200 disabled:opacity-50">
            {{ connecting ? $t('client_dashboard.seo.connecting') : $t('client_dashboard.seo.reauthorize_btn') }}
          </button>
        </div>
      </div>

      <!-- SC API Not Enabled in GCP -->
      <div v-else-if="reportData.search_console?.error === 'api_not_enabled'" class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm font-medium text-amber-800">{{ $t('client_dashboard.seo.sc_api_not_enabled') }}</p>
        <p class="text-sm text-amber-700 mt-1">{{ $t('client_dashboard.seo.sc_api_not_enabled_desc') }}</p>
      </div>

      <!-- SC No Site Access -->
      <div v-else-if="reportData.search_console?.error === 'no_site_access'" class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm font-medium text-amber-800">{{ $t('client_dashboard.seo.sc_no_site_access') }}</p>
        <p class="text-sm text-amber-700 mt-1">{{ $t('client_dashboard.seo.sc_no_site_access_desc') }}</p>
      </div>

      <!-- SC Generic Error -->
      <div v-else-if="reportData.search_console?.error" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.sc_error') }}</p>
      </div>

      <!-- ========== GA4 SECTION ========== -->
      <div v-if="reportData.ga4 && !reportData.ga4.error" class="space-y-6 mb-6">
        <!-- GA4 Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.sessions') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(reportData.ga4.summary.sessions) }}</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.users') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(reportData.ga4.summary.total_users) }}</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.bounce_rate') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ reportData.ga4.summary.bounce_rate }}%</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.conversions') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatNumber(reportData.ga4.summary.conversions) }}</p>
          </div>
          <div class="bg-white shadow rounded-lg p-5">
            <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.avg_session_duration') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatDuration(reportData.ga4.summary.avg_session_duration) }}</p>
          </div>
        </div>

        <!-- GA4 Time Series Chart -->
        <div v-if="ga4ChartData" class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.sessions_users_trend') }}</h3>
          <InteractiveChart
            :key="'ga4-ts-' + selectedPeriod"
            type="line"
            :data="ga4ChartData"
            :height="300"
            :options="ga4ChartOptions"
          />
        </div>

        <!-- Top Pages (GA4) -->
        <div v-if="reportData.ga4.top_pages?.length" class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('client_dashboard.seo.top_pages') }}</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.page') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.page_views') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('client_dashboard.seo.users') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="page in reportData.ga4.top_pages" :key="page.page_path" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm font-medium text-gray-900 truncate max-w-md" :title="page.page_path">{{ page.page_path }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(page.page_views) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ formatNumber(page.users) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- GA4 Not Configured -->
      <div v-else-if="!reportData.ga4" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.configure_ga4') }}</p>
      </div>

      <!-- GA4 Error -->
      <div v-else-if="reportData.ga4?.error" class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6 text-center">
        <p class="text-sm text-gray-500">{{ $t('client_dashboard.seo.ga4_error') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import InteractiveChart from '@/components/InteractiveChart.vue'
import SeoPropertiesSettings from '@/components/SeoPropertiesSettings.vue'
import type { SeoReportData } from '@/types/seo'

const props = defineProps<{
  tenantId: number | string
  clientWebsite?: string | null
}>()

const periods = [
  { value: 7, label: '7D' },
  { value: 30, label: '30D' },
  { value: 90, label: '90D' },
]

const selectedPeriod = ref(30)
const loading = ref(false)
const reportData = ref<SeoReportData | null>(null)
const psStrategy = ref<'mobile' | 'desktop'>('mobile')
const connecting = ref(false)

const scoreLabels: Record<string, string> = {
  performance: 'Performance',
  seo: 'SEO',
  accessibility: 'Accessibility',
  best_practices: 'Best Practices',
}

const cwvMetrics: Record<string, string> = {
  fcp: 'First Contentful Paint',
  lcp: 'Largest Contentful Paint',
  cls: 'Cumulative Layout Shift',
  tbt: 'Total Blocking Time',
  tti: 'Time to Interactive',
  si: 'Speed Index',
  ttfb: 'Server Response Time',
}

const hasPageSpeedData = computed(() => {
  const m = reportData.value?.pagespeed_mobile?.scores
  const d = reportData.value?.pagespeed_desktop?.scores
  return (m && (m.performance > 0 || m.seo > 0 || m.accessibility > 0 || m.best_practices > 0))
      || (d && (d.performance > 0 || d.seo > 0 || d.accessibility > 0 || d.best_practices > 0))
})

const currentPS = computed(() => {
  return psStrategy.value === 'mobile' ? reportData.value?.pagespeed_mobile : reportData.value?.pagespeed_desktop
})

const fetchReport = async () => {
  loading.value = true
  try {
    const endDate = new Date().toISOString().split('T')[0]
    const startDate = new Date(Date.now() - selectedPeriod.value * 86400000).toISOString().split('T')[0]

    const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/report`, {
      params: { start_date: startDate, end_date: endDate },
    })
    reportData.value = data
  } catch (error) {
    console.error('Failed to fetch SEO report:', error)
  } finally {
    loading.value = false
  }
}

// Search Console chart data
const scChartData = computed(() => {
  const ts = reportData.value?.search_console?.timeseries
  if (!ts?.length) return null

  return {
    labels: ts.map(d => {
      const date = new Date(d.date)
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    }),
    datasets: [
      {
        label: 'Clicks',
        data: ts.map(d => d.clicks),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        yAxisID: 'y',
      },
      {
        label: 'Impressions',
        data: ts.map(d => d.impressions),
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.4,
        yAxisID: 'y1',
      },
    ],
  }
})

const scChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: { datalabels: { display: false }, legend: { position: 'top' as const } },
  scales: {
    y: { type: 'linear' as const, display: true, position: 'left' as const, title: { display: true, text: 'Clicks' } },
    y1: { type: 'linear' as const, display: true, position: 'right' as const, title: { display: true, text: 'Impressions' }, grid: { drawOnChartArea: false } },
  },
}

// GA4 chart data
const ga4ChartData = computed(() => {
  const ts = reportData.value?.ga4?.timeseries
  if (!ts?.length) return null

  return {
    labels: ts.map(d => {
      const date = new Date(d.date)
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    }),
    datasets: [
      {
        label: 'Sessions',
        data: ts.map(d => d.sessions),
        borderColor: 'rgb(139, 92, 246)',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        yAxisID: 'y',
      },
      {
        label: 'Users',
        data: ts.map(d => d.users),
        borderColor: 'rgb(249, 115, 22)',
        backgroundColor: 'rgba(249, 115, 22, 0.1)',
        borderWidth: 2,
        fill: false,
        tension: 0.4,
        yAxisID: 'y1',
      },
    ],
  }
})

const ga4ChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  plugins: { datalabels: { display: false }, legend: { position: 'top' as const } },
  scales: {
    y: { type: 'linear' as const, display: true, position: 'left' as const, title: { display: true, text: 'Sessions' } },
    y1: { type: 'linear' as const, display: true, position: 'right' as const, title: { display: true, text: 'Users' }, grid: { drawOnChartArea: false } },
  },
}

// Formatters
const formatNumber = (num: number): string => {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`
  return num?.toLocaleString() ?? '0'
}

const formatCurrency = (amount: number): string => {
  if (!amount) return '0 SAR'
  if (amount >= 1000000) return `${(amount / 1000000).toFixed(1)}M SAR`
  if (amount >= 1000) return `${(amount / 1000).toFixed(1)}K SAR`
  return `${amount.toLocaleString()} SAR`
}

const formatDuration = (seconds: number): string => {
  if (!seconds) return '0s'
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return m > 0 ? `${m}m ${s}s` : `${s}s`
}

const getScoreColor = (score: number): string => {
  if (score >= 90) return 'text-green-600'
  if (score >= 50) return 'text-orange-500'
  return 'text-red-600'
}

const getScoreHex = (score: number): string => {
  if (score >= 90) return '#16a34a'
  if (score >= 50) return '#f97316'
  return '#dc2626'
}

const getCwvStatusClass = (status?: string): string => {
  if (status === 'good') return 'text-green-600 font-medium'
  if (status === 'needs_improvement') return 'text-orange-500 font-medium'
  if (status === 'poor') return 'text-red-600 font-medium'
  return 'text-gray-400'
}

const getDeviceIcon = (device: string): string => {
  const icons: Record<string, string> = { mobile: '📱', desktop: '🖥️', tablet: '📱' }
  return icons[device.toLowerCase()] || '📱'
}

const connectGoogle = async () => {
  connecting.value = true
  try {
    const response = await window.axios.get('/api/google-ads/auth-url', {
      headers: { 'X-Tenant-ID': String(props.tenantId) }
    })
    if (response.data.success && response.data.oauth_url) {
      window.open(response.data.oauth_url, '_blank', 'width=500,height=600')
      let pollCount = 0
      const poll = setInterval(async () => {
        try {
          const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/status`)
          pollCount++
          if (data.has_integration && data.has_search_console_scope) {
            clearInterval(poll)
            connecting.value = false
            fetchReport()
          } else if (data.has_integration && !data.has_search_console_scope && pollCount >= 5) {
            clearInterval(poll)
            connecting.value = false
            fetchReport()
          }
        } catch {}
      }, 3000)
      setTimeout(() => { clearInterval(poll); connecting.value = false }, 5 * 60 * 1000)
    } else {
      connecting.value = false
    }
  } catch {
    connecting.value = false
  }
}

watch(selectedPeriod, () => {
  fetchReport()
})

onMounted(() => {
  fetchReport()
})
</script>
