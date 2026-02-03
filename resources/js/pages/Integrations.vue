<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <div class="flex items-center">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            {{ $t('navigation.integrations') }}
          </h2>
          <!-- Live Status Indicator -->
          <div class="ml-3 flex items-center">
            <div 
              :class="[
                'h-3 w-3 rounded-full',
                allIntegrationsHealthy ? 'bg-green-500' : hasActiveIntegrations ? 'bg-yellow-500' : 'bg-gray-400'
              ]"
            ></div>
            <span class="ml-1 text-sm font-medium" :class="[
              allIntegrationsHealthy ? 'text-green-700' : hasActiveIntegrations ? 'text-yellow-700' : 'text-gray-500'
            ]">
              {{ getOverallStatus() }}
            </span>
          </div>
        </div>
        <p class="mt-1 text-sm text-gray-500">
          Connect your ad platforms to start importing data
        </p>
      </div>
      <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
        <!-- Reconnect Button - shows when tokens need refresh -->
        <button
          v-if="platformsNeedingReconnection.length > 0"
          @click="showReconnectModal = true"
          class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
        >
          <ExclamationTriangleIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          {{ $t('pages.integrations.reconnect_platforms', { count: platformsNeedingReconnection.length }) }}
        </button>
        <button
          @click="refreshAllIntegrations"
          :disabled="refreshingAll"
          class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <span v-if="refreshingAll" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></span>
          <ArrowPathIcon v-else class="h-4 w-4 mr-2" aria-hidden="true" />
          Refresh All
        </button>
        <button
          @click="showAddIntegration = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Add Integration
        </button>
      </div>
    </div>

    <!-- Available Platforms -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Available Platforms
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Connect to these advertising platforms to import your campaign data
        </p>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 p-6">
        <!-- Facebook Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <component :is="FacebookIcon" class="h-6 w-6 text-blue-600" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">Facebook Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">Meta Platforms</p>
                </div>
              </div>
              <div v-if="getIntegrationStatus('facebook')" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              Connect Facebook and Instagram advertising campaigns to import performance data and metrics.
            </p>

            <div class="flex items-center justify-between">
              <span
                v-if="getIntegrationStatus('facebook')"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <button
                v-else
                @click="connectPlatform(availablePlatforms.find(p => p.id === 'facebook'))"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              >
                Connect
              </button>
            </div>

            <!-- Sync & Import Buttons -->
            <div v-if="getIntegrationStatus('facebook')" class="mt-3 pt-3 border-t border-gray-200 space-y-2">
              <button
                @click="openPlatformSyncModal('facebook')"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                Sync Metrics
              </button>
              <button
                @click="showCsvUpload = true"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              >
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Import Historical CSV
              </button>
            </div>
          </div>
        </div>

        <!-- Google Sheets Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border transition-all duration-200 focus-within:ring-2"
             :class="[
               googleSheetsStatus.authenticated
                 ? 'border-green-200 bg-green-50/30 hover:shadow-md hover:border-green-300 focus-within:ring-green-500 focus-within:border-green-500'
                 : 'border-gray-200 hover:shadow-md hover:border-gray-300 focus-within:ring-green-500 focus-within:border-green-500'
             ]">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <component :is="GoogleIcon" class="h-6 w-6 text-green-600" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">Google Sheets</h3>
                  <p class="text-xs text-gray-500 mt-0.5">Google Workspace</p>
                </div>
              </div>
              <div v-if="googleSheetsStatus.authenticated" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-2 line-clamp-2">
              Export campaign data and reports directly to Google Drive folders and spreadsheets.
            </p>

            <div v-if="googleSheetsStatus.authenticated" class="mb-3">
              <p class="text-xs text-green-700 bg-green-100 rounded px-2 py-1">
                Connected via {{ googleSheetsStatus.auth_method || 'OAuth 2.0' }}
              </p>
            </div>
            <div v-if="googleSheetsStatus.error" class="mb-3">
              <p class="text-xs text-red-700 bg-red-100 rounded px-2 py-1">{{ googleSheetsStatus.error }}</p>
            </div>

            <div class="flex items-center justify-between">
              <span
                v-if="googleSheetsStatus.authenticated"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <div class="flex items-center space-x-2">
                <button
                  v-if="showGoogleSheetsButton"
                  @click="initiateGoogleAuth"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                >
                  Connect
                </button>
                <button
                  v-if="googleSheetsStatus.connecting"
                  disabled
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-gray-500 bg-gray-100 cursor-not-allowed"
                >
                  <ArrowPathIcon class="animate-spin -ml-1 mr-1.5 h-3 w-3" />
                  Connecting...
                </button>
                <button
                  v-if="googleSheetsStatus.authenticated"
                  @click="testGoogleConnection"
                  :disabled="googleSheetsStatus.testing"
                  class="inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                >
                  <ArrowPathIcon v-if="googleSheetsStatus.testing" class="animate-spin h-3 w-3" />
                  <span v-else>Test</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Google Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border transition-all duration-200 focus-within:ring-2"
             :class="[
               googleAdsStatus.authenticated
                 ? 'border-green-200 bg-green-50/30 hover:shadow-md hover:border-green-300 focus-within:ring-red-500 focus-within:border-red-500'
                 : 'border-gray-200 hover:shadow-md hover:border-gray-300 focus-within:ring-red-500 focus-within:border-red-500'
             ]">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <component :is="GoogleIcon" class="h-6 w-6 text-red-600" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">Google Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">Google Ads</p>
                </div>
              </div>
              <div v-if="googleAdsStatus.authenticated" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-2 line-clamp-2">
              Import Google Ads campaigns, ad groups, and performance metrics for comprehensive analysis.
            </p>

            <div v-if="googleAdsStatus.authenticated" class="mb-3">
              <p class="text-xs text-green-700 bg-green-100 rounded px-2 py-1">
                {{ googleAdsStatus.total_accounts }} accounts connected
              </p>
            </div>
            <div v-if="googleAdsStatus.error" class="mb-3">
              <p class="text-xs text-red-700 bg-red-100 rounded px-2 py-1">{{ googleAdsStatus.error }}</p>
            </div>

            <div class="flex items-center justify-between">
              <span
                v-if="googleAdsStatus.authenticated"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <div class="flex items-center space-x-2">
                <button
                  v-if="showGoogleAdsButton"
                  @click="initiateGoogleAdsAuth"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                >
                  Connect
                </button>
                <button
                  v-if="googleAdsStatus.connecting"
                  disabled
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-gray-500 bg-gray-100 cursor-not-allowed"
                >
                  <ArrowPathIcon class="animate-spin -ml-1 mr-1.5 h-3 w-3" />
                  Connecting...
                </button>
                <button
                  v-if="googleAdsStatus.authenticated"
                  @click="testGoogleAdsConnection"
                  :disabled="googleAdsStatus.testing"
                  class="inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                >
                  <ArrowPathIcon v-if="googleAdsStatus.testing" class="animate-spin h-3 w-3" />
                  <span v-else>Test</span>
                </button>
              </div>
            </div>

            <!-- Sync & Import Buttons -->
            <div v-if="googleAdsStatus.authenticated" class="mt-3 pt-3 border-t border-gray-200 space-y-2">
              <button
                @click="openPlatformSyncModal('google_ads')"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
              >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                Sync Metrics
              </button>
              <button
                @click="showGoogleAdsCsvUpload = true"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
              >
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Import Historical CSV
              </button>
            </div>
          </div>
        </div>

        <!-- TikTok Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 focus-within:ring-2 focus-within:ring-gray-500 focus-within:border-gray-500">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                    <component :is="TikTokIcon" class="h-6 w-6 text-black" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">TikTok Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">TikTok for Business</p>
                </div>
              </div>
              <div v-if="getIntegrationStatus('tiktok')" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              Connect TikTok for Business campaigns to track video ad performance and user engagement metrics.
            </p>

            <div class="flex items-center justify-between">
              <span
                v-if="getIntegrationStatus('tiktok')"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <button
                v-else
                @click="connectPlatform(availablePlatforms.find(p => p.id === 'tiktok'))"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
              >
                Connect
              </button>
            </div>

            <!-- Sync Metrics Button -->
            <div v-if="getIntegrationStatus('tiktok')" class="mt-3 pt-3 border-t border-gray-200">
              <button
                @click="openPlatformSyncModal('tiktok')"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
              >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                Sync Metrics
              </button>
            </div>
          </div>
        </div>

        <!-- Snapchat Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 focus-within:ring-2 focus-within:ring-yellow-500 focus-within:border-yellow-500">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <component :is="SnapchatIcon" class="h-6 w-6 text-yellow-500" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">Snapchat Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">Snapchat for Business</p>
                </div>
              </div>
              <div v-if="getIntegrationStatus('snapchat')" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              Connect Snapchat for Business to track Story ads, Snap campaigns, and user engagement metrics.
            </p>

            <div class="flex items-center justify-between">
              <span
                v-if="getIntegrationStatus('snapchat')"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <button
                v-else
                @click="connectPlatform(availablePlatforms.find(p => p.id === 'snapchat'))"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200"
              >
                Connect
              </button>
            </div>

            <!-- Sync Metrics Button -->
            <div v-if="getIntegrationStatus('snapchat')" class="mt-3 pt-3 border-t border-gray-200">
              <button
                @click="openPlatformSyncModal('snapchat')"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200"
              >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                Sync Metrics
              </button>
            </div>
          </div>
        </div>

        <!-- LinkedIn Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <component :is="LinkedInIcon" class="h-6 w-6 text-blue-700" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">LinkedIn Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">LinkedIn for Business</p>
                </div>
              </div>
              <div v-if="getIntegrationStatus('linkedin')" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              Connect LinkedIn for Business to track professional advertising campaigns and B2B lead generation.
            </p>

            <div class="flex items-center justify-between">
              <span
                v-if="getIntegrationStatus('linkedin')"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <div v-if="getIntegrationStatus('linkedin')" class="flex items-center space-x-2">
                <button
                  @click="disconnectLinkedIn"
                  class="inline-flex items-center px-2 py-1 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                >
                  <TrashIcon class="h-3 w-3 mr-1" />
                  Disconnect
                </button>
              </div>
              <button
                v-else
                @click="connectPlatform(availablePlatforms.find(p => p.id === 'linkedin'))"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              >
                Connect
              </button>
            </div>

            <!-- Sync Metrics Button -->
            <div v-if="getIntegrationStatus('linkedin')" class="mt-3 pt-3 border-t border-gray-200">
              <button
                @click="openPlatformSyncModal('linkedin')"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              >
                <ArrowPathIcon class="h-4 w-4 mr-2" />
                Sync Metrics
              </button>
            </div>
          </div>
        </div>

        <!-- Twitter/X Ads Platform -->
        <div class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 focus-within:ring-2 focus-within:ring-gray-500 focus-within:border-gray-500">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                    <component :is="TwitterIcon" class="h-6 w-6 text-gray-900" />
                  </div>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">X/Twitter Ads</h3>
                  <p class="text-xs text-gray-500 mt-0.5">X for Business</p>
                </div>
              </div>
              <div v-if="getIntegrationStatus('twitter')" class="flex-shrink-0">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              </div>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              Connect X/Twitter Ads to track promoted tweets, engagement campaigns, and social media advertising.
            </p>

            <div class="flex items-center justify-between">
              <span
                v-if="getIntegrationStatus('twitter')"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200"
              >
                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                Connected
              </span>
              <button
                v-else
                @click="connectPlatform(availablePlatforms.find(p => p.id === 'twitter'))"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
              >
                Connect
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- Integration Stats -->
    <div v-if="integrations.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                <CheckCircleIcon class="h-5 w-5 text-green-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Integrations</dt>
                <dd class="text-lg font-medium text-gray-900">{{ activeIntegrationsCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                <CreditCardIcon class="h-5 w-5 text-blue-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Accounts</dt>
                <dd class="text-lg font-medium text-gray-900">{{ totalAccountsCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                <ClockIcon class="h-5 w-5 text-purple-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Last Sync</dt>
                <dd class="text-sm font-medium text-gray-900">{{ getLastSyncTime() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Issues</dt>
                <dd class="text-lg font-medium text-gray-900">{{ issuesCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Connected Integrations -->
    <div v-if="integrations.length > 0" class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 cursor-pointer hover:bg-gray-50 transition-colors" @click="toggleConnectedIntegrations">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <ChevronDownIcon v-if="!connectedIntegrationsCollapsed" class="h-5 w-5 text-gray-400" />
            <ChevronUpIcon v-else class="h-5 w-5 text-gray-400" />
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Connected Integrations
              </h3>
              <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Manage your connected advertising platforms
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-2" @click.stop>
            <button
              @click="toggleAutoRefresh"
              :class="[
                'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
                autoRefresh ? 'bg-green-50 border-green-200 text-green-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div :class="['h-2 w-2 rounded-full mr-2', autoRefresh ? 'bg-green-500' : 'bg-gray-400']"></div>
              Auto-refresh {{ autoRefresh ? 'ON' : 'OFF' }}
            </button>
          </div>
        </div>
      </div>

      <ul v-show="!connectedIntegrationsCollapsed" class="divide-y divide-gray-200">
        <li
          v-for="integration in integrations"
          :key="integration.id"
          class="px-4 py-4 sm:px-6"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <component
                  :is="getPlatformIcon(integration.platform)"
                  :class="['h-8 w-8', getPlatformColor(integration.platform)]"
                />
              </div>
              <div class="ml-4">
                <div class="flex items-center">
                  <p class="text-sm font-medium text-gray-900 capitalize">
                    {{ integration.platform }} Ads
                  </p>
                  <span
                    :class="[
                      'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      integration.status === 'active' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ integration.status }}
                  </span>
                </div>
                <div class="mt-1 flex items-center text-sm text-gray-500">
                  <CalendarIcon class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                  <p>
                    Connected {{ formatDate(integration.created_at) }}
                  </p>
                  <span class="mx-2">•</span>
                  <p>{{ integration.accounts_count || 0 }} accounts</p>
                  <span v-if="integration.user_name" class="mx-2">•</span>
                  <p v-if="integration.user_name">{{ integration.user_name }}</p>
                </div>
                
                <!-- Account Details with Sync Buttons -->
                <div v-if="integration.accounts && integration.accounts.length > 0" class="mt-2">
                  <div class="flex flex-wrap gap-2">
                    <Menu as="div" class="relative inline-block text-left" v-for="account in integration.accounts.slice(0, 5)" :key="account.id">
                      <MenuButton class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors cursor-pointer group">
                        {{ account.name }}
                        <span class="ml-1 text-gray-500">({{ account.currency }})</span>
                        <CloudArrowDownIcon class="ml-1 h-3 w-3 text-gray-400 group-hover:text-green-600" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                      >
                        <MenuItems class="absolute left-0 z-10 mt-1 w-40 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                          <div class="py-1">
                            <MenuItem v-slot="{ active }">
                              <button
                                @click="openAccountSyncModal(account, integration, 'all')"
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'flex items-center w-full px-3 py-2 text-sm']"
                              >
                                <ArrowPathIcon class="h-4 w-4 mr-2 text-purple-500" />
                                Sync All
                              </button>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                              <button
                                @click="openAccountSyncModal(account, integration, 'campaigns')"
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'flex items-center w-full px-3 py-2 text-sm']"
                              >
                                <PencilIcon class="h-4 w-4 mr-2 text-blue-500" />
                                Sync Campaigns
                              </button>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                              <button
                                @click="openAccountSyncModal(account, integration, 'metrics')"
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'flex items-center w-full px-3 py-2 text-sm']"
                              >
                                <CloudArrowDownIcon class="h-4 w-4 mr-2 text-green-500" />
                                Sync Metrics
                              </button>
                            </MenuItem>
                          </div>
                        </MenuItems>
                      </transition>
                    </Menu>
                    <span
                      v-if="integration.accounts.length > 5"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500"
                    >
                      +{{ integration.accounts.length - 5 }} more
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Health Status Indicator -->
            <div v-if="integrationHealth[integration.id]" class="mb-2">
              <div class="flex items-center text-xs text-gray-500">
                <span>Last check: {{ formatHealthCheck(integrationHealth[integration.id].last_check) }}</span>
                <span class="mx-1">•</span>
                <span :class="getHealthStatusColor(integrationHealth[integration.id].status)">
                  {{ integrationHealth[integration.id].status }}
                </span>
              </div>
              <div v-if="integrationHealth[integration.id].issues?.length" class="mt-1">
                <div class="text-xs text-red-600 bg-red-50 rounded px-2 py-1">
                  {{ integrationHealth[integration.id].issues[0] }}
                  <span v-if="integrationHealth[integration.id].issues.length > 1">
                    +{{ integrationHealth[integration.id].issues.length - 1 }} more
                  </span>
                </div>
              </div>
            </div>

            <!-- Real-time Sync Progress -->
            <div v-if="syncProgress[integration.id]" class="mb-3">
              <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                <span>{{ syncProgress[integration.id].status }}</span>
                <span>{{ syncProgress[integration.id].progress }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: syncProgress[integration.id].progress + '%' }"
                ></div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                @click="testIntegration(integration)"
                :disabled="testing === integration.id"
                :class="[
                  'inline-flex items-center px-3 py-2 border shadow-sm text-sm leading-4 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50',
                  getTestButtonColor(integration)
                ]"
              >
                <span v-if="testing === integration.id" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></span>
                <CheckCircleIcon v-else class="h-4 w-4 mr-1" />
                Test
              </button>
              <button
                @click="syncIntegration(integration)"
                :disabled="syncing === integration.id || syncProgress[integration.id]?.progress > 0"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
              >
                <span v-if="syncing === integration.id || syncProgress[integration.id]?.progress > 0" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></span>
                <ArrowPathIcon v-else class="h-4 w-4 mr-1" />
                {{ syncProgress[integration.id]?.progress > 0 ? 'Syncing...' : 'Sync' }}
              </button>
              <!-- Quick Actions -->
              <div class="flex space-x-1">
                <button
                  @click="quickSync(integration, 'accounts')"
                  :disabled="syncing === integration.id"
                  class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none disabled:opacity-50"
                  :title="$t('tooltips.sync_accounts_only')"
                >
                  {{ $t('labels.accounts') }}
                </button>
                <button
                  @click="quickSync(integration, 'campaigns')"
                  :disabled="syncing === integration.id"
                  class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none disabled:opacity-50"
                  :title="$t('tooltips.sync_campaigns_only')"
                >
                  {{ $t('labels.campaigns') }}
                </button>
              </div>
              <Menu as="div" class="relative inline-block text-left">
                <MenuButton class="inline-flex items-center p-2 border border-transparent rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </MenuButton>
                <transition
                  enter-active-class="transition duration-100 ease-out"
                  enter-from-class="transform scale-95 opacity-0"
                  enter-to-class="transform scale-100 opacity-100"
                  leave-active-class="transition duration-75 ease-in"
                  leave-from-class="transform scale-100 opacity-100"
                  leave-to-class="transform scale-95 opacity-0"
                >
                  <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                      <MenuItem v-slot="{ active }">
                        <button
                          @click="reauthorizeIntegration(integration)"
                          :class="[
                            active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                            'group flex w-full items-center px-4 py-2 text-sm'
                          ]"
                        >
                          <ArrowPathIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-primary-500" />
                          Re-authenticate
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button
                          @click="editIntegration(integration)"
                          :class="[
                            active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                            'group flex w-full items-center px-4 py-2 text-sm'
                          ]"
                        >
                          <PencilIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                          Edit
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button
                          @click="deleteIntegration(integration)"
                          :class="[
                            active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                            'group flex w-full items-center px-4 py-2 text-sm'
                          ]"
                        >
                          <TrashIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-red-500" />
                          Delete
                        </button>
                      </MenuItem>
                    </div>
                  </MenuItems>
                </transition>
              </Menu>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Add Integration Modal -->
    <IntegrationModal
      :show="showAddIntegration"
      :platform="selectedPlatform"
      @close="showAddIntegration = false"
      @success="handleIntegrationSuccess"
    />

    <!-- CSV Upload Modal (Facebook) -->
    <CsvUploadModal
      :show="showCsvUpload"
      platform="facebook"
      @close="showCsvUpload = false"
      @success="handleCsvUploadSuccess"
    />

    <!-- CSV Upload Modal (Google Ads) -->
    <CsvUploadModal
      :show="showGoogleAdsCsvUpload"
      platform="google"
      @close="showGoogleAdsCsvUpload = false"
      @success="handleGoogleAdsCsvUploadSuccess"
    />

    <!-- Platform Sync Modal -->
    <PlatformSyncModal
      :show="showPlatformSyncModal"
      :platform="syncPlatform"
      @close="showPlatformSyncModal = false"
      @success="handlePlatformSyncSuccess"
    />

    <!-- Account Sync Modal -->
    <AccountSyncModal
      :show="showAccountSyncModal"
      :account="selectedSyncAccount"
      :sync-type="accountSyncType"
      :platform="selectedSyncAccount?.platform || ''"
      @close="closeAccountSyncModal"
      @synced="handleAccountSynced"
    />

    <!-- Reconnect Platforms Modal -->
    <TransitionRoot :show="showReconnectModal" as="template">
      <Dialog as="div" class="relative z-50" @close="showReconnectModal = false">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6">
                <div>
                  <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                    <ExclamationTriangleIcon class="h-6 w-6 text-amber-600" aria-hidden="true" />
                  </div>
                  <div class="mt-3 text-center sm:mt-5">
                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                      {{ $t('pages.integrations.reconnect_title') }}
                    </DialogTitle>
                    <p class="mt-2 text-sm text-gray-500">
                      {{ $t('pages.integrations.reconnect_description') }}
                    </p>
                  </div>
                </div>

                <div class="mt-5 space-y-3">
                  <div
                    v-for="platform in platformsNeedingReconnection"
                    :key="platform.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div class="flex items-center space-x-3">
                      <component :is="getPlatformIcon(platform.platform)" :class="getPlatformColor(platform.platform)" />
                      <div>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ formatPlatformName(platform.platform) }}</p>
                        <p class="text-xs">
                          <span v-if="platform.status === 'expired'" class="text-red-600">{{ $t('pages.integrations.token_expired') }}</span>
                          <span v-else-if="platform.status === 'expiring_soon' && platform.days_until_expiry < 1" class="text-amber-600">
                            Expires in {{ Math.round(platform.hours_until_expiry) }} hour{{ Math.round(platform.hours_until_expiry) !== 1 ? 's' : '' }}
                          </span>
                          <span v-else-if="platform.status === 'expiring_soon'" class="text-amber-600">
                            {{ $t('pages.integrations.token_expiring', { days: platform.days_until_expiry }) }}
                          </span>
                          <span v-else class="text-gray-500">Status unknown</span>
                        </p>
                      </div>
                    </div>

                    <button
                      @click="reconnectPlatform(platform.platform)"
                      :disabled="reconnectingPlatform === platform.platform"
                      class="px-3 py-1.5 text-sm font-medium bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 transition-colors"
                    >
                      <span v-if="reconnectingPlatform === platform.platform">Connecting...</span>
                      <span v-else>Reconnect</span>
                    </button>
                  </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="showReconnectModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    {{ $t('common.close') }}
                  </button>
                  <button
                    v-if="platformsNeedingReconnection.length > 1"
                    type="button"
                    @click="reconnectAllPlatforms"
                    :disabled="isReconnectingAll"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  >
                    <span v-if="isReconnectingAll">Reconnecting...</span>
                    <span v-else>{{ $t('pages.integrations.reconnect_all') }}</span>
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, h } from 'vue'
import {
  PlusIcon,
  CalendarIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  EllipsisVerticalIcon,
  PencilIcon,
  TrashIcon,
  CreditCardIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  CloudArrowDownIcon
} from '@heroicons/vue/24/outline'
import { Menu, MenuButton, MenuItems, MenuItem, Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import IntegrationModal from '@/components/IntegrationModal.vue'
import CsvUploadModal from '@/components/CsvUploadModal.vue'
import PlatformSyncModal from '@/components/PlatformSyncModal.vue'
import AccountSyncModal from '@/components/AccountSyncModal.vue'
import { useNotificationStore } from '@/stores/notifications'
import axios from 'axios'

// Platform icons as SVG components
const FacebookIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'
  })
])

const GoogleIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z'
  }),
  h('path', {
    d: 'M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z'
  }),
  h('path', {
    d: 'M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z'
  }),
  h('path', {
    d: 'M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z'
  })
])

const TikTokIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z'
  })
])

const SnapchatIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.024-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.348-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-12C24.007 5.367 18.641.001 12.017.001z'
  })
])

const LinkedInIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'
  })
])

const TwitterIcon = () => h('svg', {
  viewBox: '0 0 24 24',
  fill: 'currentColor',
  class: 'w-6 h-6'
}, [
  h('path', {
    d: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'
  })
])

interface Platform {
  id: string
  name: string
  description: string
  icon: any
  color: string
}

interface Integration {
  id: string
  platform: string
  status: 'active' | 'inactive' | 'error'
  created_at: string
  accounts_count?: number
  user_name?: string
  user_email?: string
  connected_at?: string
  token_expires_at?: string
  accounts?: Array<{
    id: string
    name: string
    external_id: string
    status: string
    currency: string
  }>
}

const integrations = ref<Integration[]>([])
const showAddIntegration = ref(false)
const showCsvUpload = ref(false)
const showGoogleAdsCsvUpload = ref(false)
const showPlatformSyncModal = ref(false)
const syncPlatform = ref('')
const selectedPlatform = ref<Platform | null>(null)
const testing = ref<string | null>(null)
const syncing = ref<string | null>(null)
const refreshingAll = ref(false)
const autoRefresh = ref(false)
const autoRefreshInterval = ref<NodeJS.Timeout | null>(null)
const connectedIntegrationsCollapsed = ref(true) // Start collapsed by default

// Real-time status tracking
const integrationHealth = ref<Record<string, {
  status: 'healthy' | 'warning' | 'error'
  last_check: string
  response_time: number
  issues: string[]
}>>({})

const syncProgress = ref<Record<string, {
  status: string
  progress: number
  step: string
  total_steps: number
  current_step: number
}>>({})

// Google Sheets integration status
const googleSheetsStatus = ref({
  authenticated: false,
  auth_method: '',
  message: '',
  connecting: false,
  testing: false,
  error: null
})

// Google Ads integration status
const googleAdsStatus = ref({
  authenticated: false,
  accounts: [],
  connecting: false,
  testing: false,
  error: null,
  total_accounts: 0,
  active_accounts: 0
})

// Reconnection modal state
const showReconnectModal = ref(false)
const platformsNeedingReconnection = ref<Array<{
  id: number
  platform: string
  status: string
  expires_at: string | null
  days_until_expiry: number | null
  hours_until_expiry: number | null
}>>([])
const reconnectingPlatform = ref('')
const isReconnectingAll = ref(false)

// Account sync modal state
const showAccountSyncModal = ref(false)
const selectedSyncAccount = ref<{
  id: number
  name: string
  external_account_id?: string
  integration_id: number
  platform?: string
} | null>(null)
const accountSyncType = ref<'all' | 'campaigns' | 'metrics'>('all')

// Simple computed to force buttons to show
const showGoogleSheetsButton = computed(() => !googleSheetsStatus.value.authenticated && !googleSheetsStatus.value.connecting)
const showGoogleAdsButton = computed(() => !googleAdsStatus.value.authenticated && !googleAdsStatus.value.connecting)

// Computed properties for dashboard stats
const activeIntegrationsCount = computed(() => 
  integrations.value.filter(i => i.status === 'active').length
)

const totalAccountsCount = computed(() => 
  integrations.value.reduce((sum, i) => sum + (i.accounts_count || 0), 0)
)

const issuesCount = computed(() => 
  Object.values(integrationHealth.value).reduce((sum, health) => 
    sum + health.issues.length, 0
  )
)

const hasActiveIntegrations = computed(() => activeIntegrationsCount.value > 0)

const allIntegrationsHealthy = computed(() => 
  hasActiveIntegrations.value && 
  Object.values(integrationHealth.value).every(h => h.status === 'healthy')
)

const notificationStore = useNotificationStore()

const availablePlatforms: Platform[] = [
  {
    id: 'facebook',
    name: 'Facebook Ads',
    description: 'Connect Facebook and Instagram advertising',
    icon: FacebookIcon,
    color: 'text-blue-600'
  },
  {
    id: 'google-ads',
    name: 'Google Ads',
    description: 'Import campaigns and performance data',
    icon: GoogleIcon,
    color: 'text-red-600'
  },
  {
    id: 'tiktok',
    name: 'TikTok Ads',
    description: 'Connect TikTok for Business campaigns',
    icon: TikTokIcon,
    color: 'text-black'
  },
  {
    id: 'snapchat',
    name: 'Snapchat Ads',
    description: 'Connect Snapchat for Business campaigns and engagement metrics',
    icon: SnapchatIcon,
    color: 'text-yellow-500'
  },
  {
    id: 'linkedin',
    name: 'LinkedIn Ads',
    description: 'Connect LinkedIn for Business campaigns and B2B lead generation',
    icon: LinkedInIcon,
    color: 'text-blue-700'
  },
  {
    id: 'twitter',
    name: 'X/Twitter Ads',
    description: 'Connect X/Twitter Ads for promoted tweets and engagement campaigns',
    icon: TwitterIcon,
    color: 'text-gray-900'
  }
]

const fetchIntegrations = async () => {
  try {
    const response = await axios.get('/api/integrations')
    integrations.value = response.data.data
  } catch (error) {
    console.error('Error fetching integrations:', error)
  }
}

const connectPlatform = (platform: Platform) => {
  selectedPlatform.value = platform
  showAddIntegration.value = true
}

const testIntegration = async (integration: Integration) => {
  testing.value = integration.id
  try {
    await axios.post(`/api/integrations/${integration.id}/test`)
    // Show success message
  } catch (error) {
    console.error('Integration test failed:', error)
    // Show error message
  } finally {
    testing.value = null
  }
}

const syncIntegration = async (integration: Integration) => {
  syncing.value = integration.id
  
  // Initialize progress tracking
  syncProgress.value[integration.id] = {
    status: 'Initializing sync...',
    progress: 0,
    step: 'Starting',
    total_steps: 4,
    current_step: 1
  }
  
  try {
    // Simulate progressive sync steps
    const steps = [
      { name: 'Validating connection', progress: 25 },
      { name: 'Syncing accounts', progress: 50 },
      { name: 'Syncing campaigns', progress: 75 },
      { name: 'Updating metrics', progress: 100 }
    ]
    
    for (let i = 0; i < steps.length; i++) {
      syncProgress.value[integration.id] = {
        ...syncProgress.value[integration.id],
        status: steps[i].name,
        progress: steps[i].progress,
        current_step: i + 1
      }
      
      // Simulate async operation
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      if (i === steps.length - 1) {
        // Final API call
        await axios.post('/api/sync/run', { integration_id: integration.id })
      }
    }
    
    // Show success notification
    showNotification('success', 'Integration synced successfully')
    await fetchIntegrations()
    
  } catch (error) {
    console.error('Sync failed:', error)
    syncProgress.value[integration.id] = {
      ...syncProgress.value[integration.id],
      status: 'Sync failed',
      progress: 0
    }
    showNotification('error', 'Sync failed. Please check your connection and try again.')
  } finally {
    syncing.value = null
    // Clear progress after delay
    setTimeout(() => {
      delete syncProgress.value[integration.id]
    }, 3000)
  }
}

const reauthorizeIntegration = async (integration: Integration) => {
  // For Google Ads, directly initiate OAuth flow
  if (integration.platform === 'google') {
    initiateGoogleAdsAuth()
    return
  }

  // For Google Sheets
  if (integration.platform === 'google_sheets') {
    initiateGoogleAuth()
    return
  }

  // For LinkedIn, use OAuth initiate endpoint
  if (integration.platform === 'linkedin') {
    try {
      const response = await axios.post('/api/linkedin/oauth/initiate')
      if (response.data.authUrl) {
        window.location.href = response.data.authUrl
      }
    } catch (error) {
      console.error('Failed to initiate LinkedIn auth:', error)
      showNotification('error', 'Failed to initiate LinkedIn re-authentication')
    }
    return
  }

  // For Facebook, use OAuth initiate endpoint
  if (integration.platform === 'facebook') {
    try {
      const response = await axios.post('/api/facebook/oauth/initiate')
      if (response.data.authUrl) {
        window.location.href = response.data.authUrl
      }
    } catch (error) {
      console.error('Failed to initiate Facebook auth:', error)
      showNotification('error', 'Failed to initiate Facebook re-authentication')
    }
    return
  }

  // Fallback: open modal
  const platform = availablePlatforms.find(p => p.id === integration.platform)
  if (platform) {
    selectedPlatform.value = platform
    showAddIntegration.value = true
  }
}

const editIntegration = (integration: Integration) => {
  const platform = availablePlatforms.find(p => p.id === integration.platform)
  if (platform) {
    selectedPlatform.value = platform
    showAddIntegration.value = true
  }
}

const deleteIntegration = async (integration: Integration) => {
  if (confirm('Are you sure you want to delete this integration? This will also remove all associated data.')) {
    try {
      await axios.delete(`/api/integrations/${integration.id}`)
      integrations.value = integrations.value.filter(i => i.id !== integration.id)
    } catch (error) {
      console.error('Error deleting integration:', error)
    }
  }
}

const getIntegrationStatus = (platformId: string) => {
  return integrations.value.some(i => i.platform === platformId && i.status === 'active')
}

const getPlatformIcon = (platform: string) => {
  const platformData = availablePlatforms.find(p => p.id === platform)
  return platformData?.icon || FacebookIcon
}

const getPlatformColor = (platform: string) => {
  const platformData = availablePlatforms.find(p => p.id === platform)
  return platformData?.color || 'text-gray-600'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const quickSync = async (integration: Integration, type: 'accounts' | 'campaigns') => {
  syncing.value = integration.id
  try {
    if (type === 'accounts') {
      await axios.post(`/api/integrations/${integration.id}/sync-accounts`)
    } else {
      await axios.post(`/api/integrations/${integration.id}/sync-campaigns`)
    }
    showNotification('success', `${type} synced successfully`)
    await fetchIntegrations()
  } catch (error) {
    console.error('Quick sync failed:', error)
    showNotification('error', `Failed to sync ${type}`)
  } finally {
    syncing.value = null
  }
}

const refreshAllIntegrations = async () => {
  refreshingAll.value = true
  try {
    await Promise.all([
      fetchIntegrations(),
      checkAllIntegrationsHealth()
    ])
    showNotification('success', 'All integrations refreshed')
  } catch (error) {
    console.error('Refresh failed:', error)
    showNotification('error', 'Failed to refresh integrations')
  } finally {
    refreshingAll.value = false
  }
}

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value

  if (autoRefresh.value) {
    autoRefreshInterval.value = setInterval(() => {
      fetchIntegrations()
      checkAllIntegrationsHealth()
    }, 30000) // Refresh every 30 seconds
    showNotification('info', 'Auto-refresh enabled (30s intervals)')
  } else {
    if (autoRefreshInterval.value) {
      clearInterval(autoRefreshInterval.value)
      autoRefreshInterval.value = null
    }
    showNotification('info', 'Auto-refresh disabled')
  }
}

const toggleConnectedIntegrations = () => {
  connectedIntegrationsCollapsed.value = !connectedIntegrationsCollapsed.value
}

const checkAllIntegrationsHealth = async () => {
  try {
    const response = await axios.get('/api/integrations/health')
    const healthData = response.data.integrations
    
    // Update health data for each integration
    Object.keys(healthData).forEach(integrationId => {
      const health = healthData[integrationId]
      integrationHealth.value[integrationId] = {
        status: health.status,
        last_check: health.last_check,
        response_time: health.response_time,
        issues: health.issues || []
      }
    })
  } catch (error) {
    console.error('Failed to fetch health data:', error)
    // Fallback to individual checks
    for (const integration of integrations.value) {
      await checkIntegrationHealth(integration)
    }
  }
}

const checkIntegrationHealth = async (integration: Integration) => {
  try {
    const startTime = Date.now()
    const response = await axios.post(`/api/integrations/${integration.id}/test`)
    const responseTime = Date.now() - startTime
    
    integrationHealth.value[integration.id] = {
      status: response.data.status === 'success' ? 'healthy' : 'warning',
      last_check: new Date().toISOString(),
      response_time: responseTime,
      issues: response.data.status !== 'success' ? [response.data.message || 'Connection issues'] : []
    }
  } catch (error: any) {
    integrationHealth.value[integration.id] = {
      status: 'error',
      last_check: new Date().toISOString(),
      response_time: 0,
      issues: [error.response?.data?.message || 'Connection failed']
    }
  }
}

const getOverallStatus = () => {
  if (!hasActiveIntegrations.value) return 'No integrations'
  if (allIntegrationsHealthy.value) return 'All systems operational'
  if (issuesCount.value > 0) return `${issuesCount.value} issues detected`
  return 'Checking status...'
}

const getLastSyncTime = () => {
  const times = integrations.value.map(i => new Date(i.created_at).getTime())
  if (times.length === 0) return 'Never'
  const latest = new Date(Math.max(...times))
  return latest.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const getHealthStatusColor = (status: string) => {
  switch (status) {
    case 'healthy': return 'text-green-600'
    case 'warning': return 'text-yellow-600'
    case 'error': return 'text-red-600'
    default: return 'text-gray-600'
  }
}

const getTestButtonColor = (integration: Integration) => {
  const health = integrationHealth.value[integration.id]
  if (!health) return 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
  
  switch (health.status) {
    case 'healthy':
      return 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100'
    case 'warning':
      return 'border-yellow-300 text-yellow-700 bg-yellow-50 hover:bg-yellow-100'
    case 'error':
      return 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100'
    default:
      return 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
  }
}

const formatHealthCheck = (timestamp: string) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const showNotification = (type: 'success' | 'error' | 'info', message: string) => {
  notificationStore[type](message)
}

// Google Sheets Integration Functions
const checkGoogleSheetsStatus = async () => {
  try {
    const response = await axios.get('/api/google-auth/status')
    googleSheetsStatus.value.authenticated = response.data.authenticated
    googleSheetsStatus.value.auth_method = response.data.auth_method
    googleSheetsStatus.value.message = response.data.message
    googleSheetsStatus.value.error = null
  } catch (error: any) {
    googleSheetsStatus.value.error = error.response?.data?.error || 'Failed to check status'
    googleSheetsStatus.value.authenticated = false
  }
}

const initiateGoogleAuth = async () => {
  googleSheetsStatus.value.connecting = true
  try {
    const response = await axios.get('/api/google-auth/url')
    // Open authorization URL in new window
    window.open(response.data.auth_url, '_blank', 'width=500,height=600')

    // Poll for authorization completion
    const pollInterval = setInterval(async () => {
      await checkGoogleSheetsStatus()
      if (googleSheetsStatus.value.authenticated) {
        clearInterval(pollInterval)
        showNotification('success', 'Google Sheets connected successfully!')
        googleSheetsStatus.value.connecting = false
      }
    }, 3000)

    // Stop polling after 5 minutes
    setTimeout(() => {
      clearInterval(pollInterval)
      if (googleSheetsStatus.value.connecting) {
        googleSheetsStatus.value.connecting = false
        showNotification('info', 'Authorization window closed')
      }
    }, 300000)

  } catch (error: any) {
    googleSheetsStatus.value.error = error.response?.data?.error || 'Failed to get authorization URL'
    googleSheetsStatus.value.connecting = false
    showNotification('error', 'Failed to initiate Google authorization')
  }
}

const testGoogleConnection = async () => {
  googleSheetsStatus.value.testing = true
  try {
    const response = await axios.post('/api/google-auth/test')
    if (response.data.success) {
      showNotification('success', 'Google Sheets connection test successful!')
    } else if (response.data.requires_auth) {
      showNotification('warning', 'Authorization required for Google Sheets')
    } else {
      showNotification('error', response.data.message || 'Connection test failed')
    }
  } catch (error: any) {
    showNotification('error', error.response?.data?.message || 'Connection test failed')
  } finally {
    googleSheetsStatus.value.testing = false
  }
}

const refreshGoogleStatus = async () => {
  await checkGoogleSheetsStatus()
  showNotification('info', 'Google Sheets status refreshed')
}

// Google Ads Integration Functions
const checkGoogleAdsStatus = async () => {
  try {
    const response = await axios.get('/api/google-ads/status')
    // API returns 'connected' not 'authenticated'
    googleAdsStatus.value.authenticated = response.data.connected || false
    googleAdsStatus.value.accounts = response.data.accounts || []
    googleAdsStatus.value.total_accounts = response.data.accounts_count || 0
    googleAdsStatus.value.active_accounts = response.data.accounts_count || 0
    googleAdsStatus.value.error = null
  } catch (error: any) {
    googleAdsStatus.value.error = error.response?.data?.error || error.response?.data?.message || 'Failed to check status'
    googleAdsStatus.value.authenticated = false
  }
}

const initiateGoogleAdsAuth = async () => {
  googleAdsStatus.value.connecting = true
  try {
    const response = await axios.get('/api/google-ads/auth-url')

    if (response.data.success && response.data.oauth_url) {
      // Open authorization URL in new window
      window.open(response.data.oauth_url, '_blank', 'width=500,height=600')

      // Poll for authorization completion
      const pollInterval = setInterval(async () => {
        await checkGoogleAdsStatus()
        if (googleAdsStatus.value.authenticated) {
          clearInterval(pollInterval)
          showNotification('success', 'Google Ads connected successfully!')
          googleAdsStatus.value.connecting = false
        }
      }, 3000)

      // Stop polling after 5 minutes
      setTimeout(() => {
        clearInterval(pollInterval)
        if (googleAdsStatus.value.connecting) {
          googleAdsStatus.value.connecting = false
          showNotification('info', 'Authorization window closed')
        }
      }, 300000)
    } else {
      // Handle API error response
      const errorMessage = response.data.message || 'Failed to get authorization URL'
      googleAdsStatus.value.error = errorMessage
      googleAdsStatus.value.connecting = false
      showNotification('error', errorMessage)
    }

  } catch (error: any) {
    const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Failed to get authorization URL'
    googleAdsStatus.value.error = errorMessage
    googleAdsStatus.value.connecting = false
    showNotification('error', errorMessage)
  }
}

const testGoogleAdsConnection = async () => {
  googleAdsStatus.value.testing = true
  try {
    const response = await axios.post('/api/google-ads/test')
    if (response.data.success) {
      showNotification('success', 'Google Ads connection test successful!')
      await checkGoogleAdsStatus() // Refresh account data
    } else {
      showNotification('error', response.data.message || 'Connection test failed')
    }
  } catch (error: any) {
    showNotification('error', error.response?.data?.message || 'Connection test failed')
  } finally {
    googleAdsStatus.value.testing = false
  }
}

const syncGoogleAdsAccounts = async () => {
  try {
    const response = await axios.post('/api/google-ads/sync-accounts')
    showNotification('success', `Synced ${response.data.synced_count} Google Ads accounts`)
    await checkGoogleAdsStatus()
  } catch (error: any) {
    showNotification('error', error.response?.data?.message || 'Failed to sync accounts')
  }
}

const refreshGoogleAdsStatus = async () => {
  await checkGoogleAdsStatus()
  showNotification('info', 'Google Ads status refreshed')
}

// LinkedIn disconnect function
const disconnectLinkedIn = async () => {
  if (!confirm('Are you sure you want to disconnect LinkedIn? This will remove access to your ad accounts.')) {
    return
  }

  try {
    // Find LinkedIn integration
    const linkedInIntegration = integrations.value.find(i => i.platform === 'linkedin')
    if (!linkedInIntegration) {
      showNotification('error', 'LinkedIn integration not found')
      return
    }

    // Call disconnect endpoint
    await axios.post('/api/linkedin/disconnect')

    showNotification('success', 'LinkedIn disconnected successfully')

    // Refresh integrations list
    await fetchIntegrations()
  } catch (error: any) {
    console.error('LinkedIn disconnect failed:', error)
    showNotification('error', error.response?.data?.message || 'Failed to disconnect LinkedIn')
  }
}

const handleIntegrationSuccess = () => {
  showAddIntegration.value = false
  selectedPlatform.value = null
  fetchIntegrations()
}

const handleCsvUploadSuccess = () => {
  showCsvUpload.value = false
  fetchIntegrations()
  showNotification('success', 'Facebook CSV import completed successfully!')
}

const handleGoogleAdsCsvUploadSuccess = () => {
  showGoogleAdsCsvUpload.value = false
  checkGoogleAdsStatus()
  showNotification('success', 'Google Ads CSV import completed successfully!')
}

// Platform sync functions
const openPlatformSyncModal = (platform: string) => {
  syncPlatform.value = platform
  showPlatformSyncModal.value = true
}

const handlePlatformSyncSuccess = () => {
  showNotification('success', `${syncPlatform.value} metrics synced successfully!`)
  fetchIntegrations()
}

// Account sync modal functions
const openAccountSyncModal = (account: any, integration: Integration, type: 'all' | 'campaigns' | 'metrics') => {
  selectedSyncAccount.value = {
    id: account.id,
    name: account.name,
    external_account_id: account.external_id,
    integration_id: parseInt(integration.id),
    platform: integration.platform
  }
  accountSyncType.value = type
  showAccountSyncModal.value = true
}

const closeAccountSyncModal = () => {
  showAccountSyncModal.value = false
  selectedSyncAccount.value = null
}

const handleAccountSynced = () => {
  showNotification('success', `${selectedSyncAccount.value?.name} synced successfully!`)
  fetchIntegrations()
}

// Token status and reconnection functions
const checkTokenStatus = async () => {
  try {
    const response = await axios.get('/api/integrations/token-status')
    platformsNeedingReconnection.value = response.data.data.filter(
      (p: any) => ['expired', 'expiring_soon'].includes(p.status)
    )
  } catch (error) {
    console.error('Failed to check token status:', error)
  }
}

const formatPlatformName = (platform: string) => {
  const names: Record<string, string> = {
    'facebook': 'Facebook Ads',
    'google_ads': 'Google Ads',
    'google': 'Google Sheets',
    'snapchat': 'Snapchat Ads',
    'linkedin': 'LinkedIn Ads',
    'tiktok': 'TikTok Ads',
    'twitter': 'X/Twitter Ads'
  }
  return names[platform] || platform
}

const reconnectPlatform = async (platform: string) => {
  reconnectingPlatform.value = platform

  try {
    let authUrl = ''

    switch (platform) {
      case 'facebook':
        const fbResponse = await axios.post('/api/facebook/oauth/initiate')
        authUrl = fbResponse.data.url
        break
      case 'google_ads':
        const gaResponse = await axios.get('/api/google-ads/auth-url')
        authUrl = gaResponse.data.oauth_url || gaResponse.data.url
        break
      case 'google':
        const gsResponse = await axios.get('/api/google-auth/url')
        authUrl = gsResponse.data.auth_url
        break
      case 'snapchat':
        const snapResponse = await axios.post('/api/snapchat/oauth/redirect')
        authUrl = snapResponse.data.oauth_url
        break
      case 'linkedin':
        const liResponse = await axios.post('/api/linkedin/oauth/initiate')
        authUrl = liResponse.data.oauth_url
        break
      case 'tiktok':
        const ttResponse = await axios.post('/api/tiktok/oauth/redirect')
        authUrl = ttResponse.data.oauth_url
        break
    }

    if (authUrl) {
      // Close modal and redirect
      showReconnectModal.value = false
      window.location.href = authUrl
    } else {
      showNotification('error', `Failed to get authorization URL for ${formatPlatformName(platform)}`)
    }
  } catch (error: any) {
    console.error(`Failed to reconnect ${platform}:`, error)
    showNotification('error', error.response?.data?.message || `Failed to initiate ${formatPlatformName(platform)} reconnection`)
  } finally {
    reconnectingPlatform.value = ''
  }
}

const reconnectAllPlatforms = async () => {
  if (platformsNeedingReconnection.value.length === 0) return

  isReconnectingAll.value = true

  // Store platforms to reconnect in localStorage for sequential flow
  const platforms = platformsNeedingReconnection.value.map(p => p.platform)
  localStorage.setItem('pending_reconnections', JSON.stringify(platforms))

  // Start with first platform
  await reconnectPlatform(platforms[0])
}

const handlePendingReconnections = () => {
  const pending = localStorage.getItem('pending_reconnections')
  if (pending) {
    const platforms = JSON.parse(pending)
    const currentUrl = new URL(window.location.href)
    const success = currentUrl.searchParams.get('success')

    // If we just completed one (any success callback)
    if (success) {
      platforms.shift() // Remove completed one

      if (platforms.length > 0) {
        localStorage.setItem('pending_reconnections', JSON.stringify(platforms))
        showNotification('info', `Reconnecting ${formatPlatformName(platforms[0])}...`)
        // Auto-reconnect next platform after short delay
        setTimeout(() => reconnectPlatform(platforms[0]), 2000)
      } else {
        localStorage.removeItem('pending_reconnections')
        showNotification('success', 'All platforms reconnected successfully!')
      }
    }
  }
}

onMounted(async () => {
  await fetchIntegrations()
  await checkAllIntegrationsHealth()

  // Check for OAuth callback parameters
  const urlParams = new URLSearchParams(window.location.search)
  const success = urlParams.get('success')
  const error = urlParams.get('error')
  const accounts = urlParams.get('accounts')

  if (success === 'google_ads_connected') {
    const accountsCount = accounts ? parseInt(accounts) : 0
    showNotification('success', `Google Ads connected successfully! Synced ${accountsCount} accounts.`)
    // Clean URL without refresh
    window.history.replaceState({}, document.title, window.location.pathname)
  } else if (error) {
    showNotification('error', decodeURIComponent(error))
    // Clean URL without refresh
    window.history.replaceState({}, document.title, window.location.pathname)
  }

  // Check Google integrations status
  try {
    await checkGoogleSheetsStatus()
  } catch (error) {
    // Ensure button shows by default
    googleSheetsStatus.value.authenticated = false
    googleSheetsStatus.value.connecting = false
  }

  try {
    await checkGoogleAdsStatus()
  } catch (error) {
    // Ensure button shows by default
    googleAdsStatus.value.authenticated = false
    googleAdsStatus.value.connecting = false
  }

  // Check token status for reconnection button
  await checkTokenStatus()

  // Handle any pending reconnections from sequential flow
  handlePendingReconnections()
})

onUnmounted(() => {
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value)
  }
})
</script>
