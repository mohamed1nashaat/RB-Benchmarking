<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
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
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
                  <component v-if="platform" :is="platform.icon" :class="['h-6 w-6', platform.color]" />
                </div>
                <div class="mt-3 text-center sm:mt-5">
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                    Connect {{ platform?.name }}
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Enter your {{ platform?.name }} credentials to connect your advertising account.
                    </p>
                  </div>
                </div>
              </div>

              <div class="mt-6">
                <!-- Connection Progress Indicator -->
                <div v-if="connectionProgress.step > 0" class="mb-6">
                  <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                    <span>{{ connectionProgress.message }}</span>
                    <span>{{ connectionProgress.step }}/{{ connectionProgress.totalSteps }}</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                      :style="{ width: (connectionProgress.step / connectionProgress.totalSteps * 100) + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Facebook Ads OAuth Flow -->
                <div v-if="platform?.id === 'facebook'" class="space-y-4">
                  <div class="rounded-md bg-primary-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-primary-400" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-primary-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-primary-700">
                          <p>
                            Click the button below to securely connect your Facebook Ads account.
                            You'll be redirected to Facebook to authorize access to your advertising data.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 1 ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your advertising campaigns and performance data</li>
                        <li>Access campaign insights and metrics</li>
                        <li>View connected ad accounts</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Google Ads OAuth Flow -->
                <div v-if="platform?.id === 'google-ads'" class="space-y-4">
                  <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                          <p>
                            Click the button below to securely connect your Google Ads account.
                            You'll be redirected to Google to authorize access to your advertising data and metrics.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 'active' ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your Google Ads campaigns and performance data</li>
                        <li>Access campaign metrics and customer accounts</li>
                        <li>View connected Google Ads accounts</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- TikTok Ads OAuth Flow -->
                <div v-if="platform?.id === 'tiktok'" class="space-y-4">
                  <div class="rounded-md bg-gray-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-gray-700">
                          <p>
                            Click the button below to securely connect your TikTok for Business account.
                            You'll be redirected to TikTok to authorize access to your advertising data and metrics.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 'active' ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your TikTok advertising campaigns and performance data</li>
                        <li>Access video ad metrics and engagement data</li>
                        <li>View connected TikTok business accounts</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Snapchat Ads OAuth Flow -->
                <div v-if="platform?.id === 'snapchat'" class="space-y-4">
                  <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                          <p>
                            Click the button below to securely connect your Snapchat for Business account.
                            You'll be redirected to Snapchat to authorize access to your advertising data and metrics.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 'ACTIVE' ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your Snapchat advertising campaigns and performance data</li>
                        <li>Access Story ads and engagement metrics</li>
                        <li>View connected ad accounts and statistics</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- LinkedIn Ads OAuth Flow -->
                <div v-if="platform?.id === 'linkedin'" class="space-y-4">
                  <div class="rounded-md bg-blue-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                          <p>
                            Click the button below to securely connect your LinkedIn for Business account.
                            You'll be redirected to LinkedIn to authorize access to your advertising data and B2B lead metrics.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 'ACTIVE' ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your LinkedIn advertising campaigns and performance data</li>
                        <li>Access sponsored content and lead generation metrics</li>
                        <li>View connected LinkedIn business accounts and analytics</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Twitter/X Ads OAuth Flow -->
                <div v-if="platform?.id === 'twitter'" class="space-y-4">
                  <div class="rounded-md bg-gray-50 p-4">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-800">
                          Secure OAuth Connection
                        </h3>
                        <div class="mt-2 text-sm text-gray-700">
                          <p>
                            Click the button below to securely connect your X/Twitter Ads account.
                            You'll be redirected to X to authorize access to your advertising data and engagement metrics.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Live Preview of Detected Accounts -->
                  <div v-if="previewAccounts.length > 0" class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Preview - Detected Ad Accounts:</h4>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto">
                      <div v-for="account in previewAccounts.slice(0, 5)" :key="account.id"
                           class="flex items-center justify-between py-1 text-xs">
                        <span class="font-medium">{{ account.name }}</span>
                        <div class="flex items-center space-x-2">
                          <span class="text-gray-500">{{ account.currency }}</span>
                          <div :class="[
                            'h-2 w-2 rounded-full',
                            account.status === 'ACTIVE' ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                        </div>
                      </div>
                      <div v-if="previewAccounts.length > 5" class="text-xs text-gray-500 mt-1">
                        +{{ previewAccounts.length - 5 }} more accounts detected
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div class="text-sm text-gray-600">
                      <strong>What permissions will be requested:</strong>
                      <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Read your X/Twitter advertising campaigns and performance data</li>
                        <li>Access promoted tweets and engagement metrics</li>
                        <li>View connected ad accounts and analytics</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <form @submit.prevent="handleSubmit" class="mt-6 space-y-4">
                <!-- Manual form for other platforms -->

                <!-- Google Ads Fields -->
                <div v-if="platform?.id === 'google'" class="space-y-4">
                  <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                    <input
                      id="client_id"
                      v-model="form.client_id"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="Your Google Ads Client ID"
                    />
                  </div>
                  <div>
                    <label for="client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                    <input
                      id="client_secret"
                      v-model="form.client_secret"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="Your Google Ads Client Secret"
                    />
                  </div>
                  <div>
                    <label for="developer_token" class="block text-sm font-medium text-gray-700">Developer Token</label>
                    <input
                      id="developer_token"
                      v-model="form.developer_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="Your Google Ads Developer Token"
                    />
                  </div>
                  <div>
                    <label for="refresh_token" class="block text-sm font-medium text-gray-700">Refresh Token</label>
                    <input
                      id="refresh_token"
                      v-model="form.refresh_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="Your Google Ads Refresh Token"
                    />
                  </div>
                </div>

                <!-- TikTok uses OAuth - no manual fields needed -->

                <!-- Error Message -->
                <div v-if="error" class="rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-red-800">
                        {{ error }}
                      </h3>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                  <!-- Facebook OAuth Button -->
                  <button
                    v-if="platform?.id === 'facebook'"
                    type="button"
                    @click="handleFacebookOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with Facebook') }}
                  </button>

                  <!-- Google Ads OAuth Button -->
                  <button
                    v-if="platform?.id === 'google-ads'"
                    type="button"
                    @click="handleGoogleAdsOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with Google Ads') }}
                  </button>

                  <!-- TikTok Ads OAuth Button -->
                  <button
                    v-if="platform?.id === 'tiktok'"
                    type="button"
                    @click="handleTikTokOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with TikTok Ads') }}
                  </button>

                  <!-- Snapchat OAuth Button -->
                  <button
                    v-if="platform?.id === 'snapchat'"
                    type="button"
                    @click="handleSnapchatOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-yellow-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.024-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.348-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-12C24.007 5.367 18.641.001 12.017.001z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with Snapchat') }}
                  </button>

                  <!-- LinkedIn OAuth Button -->
                  <button
                    v-if="platform?.id === 'linkedin'"
                    type="button"
                    @click="handleLinkedInOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with LinkedIn') }}
                  </button>

                  <!-- Twitter/X OAuth Button -->
                  <button
                    v-if="platform?.id === 'twitter'"
                    type="button"
                    @click="handleTwitterOAuth"
                    :disabled="loading || !isAuthenticated"
                    class="inline-flex w-full justify-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    <svg v-else class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    {{ loading ? 'Connecting...' : (!isAuthenticated ? 'Please Login First' : 'Connect with X/Twitter') }}
                  </button>
                  
                  <!-- Regular form submit for other platforms -->
                  <button
                    v-else
                    type="submit"
                    :disabled="loading"
                    class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    {{ loading ? 'Connecting...' : 'Connect' }}
                  </button>
                  
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

interface Platform {
  id: string
  name: string
  description: string
  icon: any
  color: string
}

interface Props {
  show: boolean
  platform: Platform | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'success'])

const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)

const loading = ref(false)
const error = ref('')
const previewAccounts = ref<any[]>([])
const connectionProgress = ref({
  step: 0,
  totalSteps: 4,
  message: ''
})

const form = reactive({
  // Facebook fields
  app_id: '',
  app_secret: '',
  access_token: '',
  
  // Google fields
  client_id: '',
  client_secret: '',
  developer_token: '',
  refresh_token: '',
  
  // TikTok fields
  secret: ''
})

const resetForm = () => {
  Object.keys(form).forEach(key => {
    form[key as keyof typeof form] = ''
  })
  error.value = ''
  previewAccounts.value = []
  connectionProgress.value = {
    step: 0,
    totalSteps: 4,
    message: ''
  }
}

const simulateProgress = (steps: { message: string; duration: number }[]) => {
  let currentStep = 0
  connectionProgress.value.step = 0
  connectionProgress.value.totalSteps = steps.length
  
  const processStep = () => {
    if (currentStep < steps.length) {
      connectionProgress.value.step = currentStep + 1
      connectionProgress.value.message = steps[currentStep].message
      currentStep++
      setTimeout(processStep, steps[currentStep - 1].duration)
    }
  }
  
  processStep()
}

const handleFacebookOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting Facebook.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing OAuth request...', duration: 800 },
    { message: 'Connecting to Facebook...', duration: 1200 },
    { message: 'Validating permissions...', duration: 1000 },
    { message: 'Redirecting to Facebook...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating Facebook OAuth')

    // Get OAuth URL from backend
    const response = await axios.post('/api/facebook/oauth/initiate')
    const { oauth_url } = response.data

    console.log('IntegrationModal - got OAuth URL, redirecting to Facebook')

    // Brief delay to show final step
    await new Promise(resolve => setTimeout(resolve, 2000))

    // Redirect to Facebook OAuth
    window.location.href = oauth_url
  } catch (err: any) {
    console.error('Facebook OAuth error:', err)
    error.value = err.response?.data?.message || 'Failed to connect to Facebook. Please try again.'
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const handleGoogleAdsOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting Google Ads.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing Google Ads OAuth request...', duration: 800 },
    { message: 'Connecting to Google Ads...', duration: 1200 },
    { message: 'Validating API permissions...', duration: 1000 },
    { message: 'Redirecting to Google...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating Google Ads OAuth')

    // Get OAuth URL from backend
    const response = await axios.post('/api/google-ads/oauth/redirect')

    if (response.data.success && response.data.oauth_url) {
      console.log('IntegrationModal - got OAuth URL, redirecting to Google')

      // Brief delay to show final step
      await new Promise(resolve => setTimeout(resolve, 1500))

      // Redirect to Google OAuth URL
      window.location.href = response.data.oauth_url
    } else {
      throw new Error('Invalid OAuth response from server')
    }

  } catch (err: any) {
    console.error('Google Ads OAuth error:', err)
    error.value = err.response?.data?.message || 'Failed to connect to Google Ads. Please try again.'
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const handleTikTokOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting TikTok Ads.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing TikTok OAuth request...', duration: 800 },
    { message: 'Connecting to TikTok for Business...', duration: 1200 },
    { message: 'Validating business API permissions...', duration: 1000 },
    { message: 'Redirecting to TikTok...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating TikTok OAuth')

    // Get OAuth URL from backend
    const response = await axios.post('/api/tiktok/oauth/redirect')

    if (response.data.success && response.data.oauth_url) {
      console.log('IntegrationModal - got OAuth URL, redirecting to TikTok')

      // Brief delay to show final step
      await new Promise(resolve => setTimeout(resolve, 1500))

      // Redirect to TikTok OAuth URL
      window.location.href = response.data.oauth_url
    } else {
      throw new Error('Invalid OAuth response from server')
    }

  } catch (err: any) {
    console.error('TikTok OAuth error:', err)
    error.value = err.response?.data?.message || 'Failed to connect to TikTok Ads. Please try again.'
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const handleSnapchatOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting Snapchat.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing Snapchat OAuth request...', duration: 800 },
    { message: 'Connecting to Snapchat for Business...', duration: 1200 },
    { message: 'Validating marketing API permissions...', duration: 1000 },
    { message: 'Redirecting to Snapchat...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating Snapchat OAuth')

    // Get OAuth URL from backend (use new API endpoint)
    const response = await axios.post('/api/snapchat/oauth/redirect')

    if (response.data.success && response.data.oauth_url) {
      console.log('IntegrationModal - got OAuth URL, redirecting to Snapchat')

      // Brief delay to show final step
      await new Promise(resolve => setTimeout(resolve, 1500))

      // Redirect to Snapchat OAuth URL
      window.location.href = response.data.oauth_url
    } else {
      throw new Error('Invalid OAuth response from server')
    }

  } catch (err: any) {
    console.error('Snapchat OAuth error:', err)

    // Show specific error message based on error type
    let errorMessage = 'Failed to connect to Snapchat. Please try again.'

    if (err.response?.status === 401) {
      errorMessage = 'Please ensure you are logged in before connecting Snapchat.'
    } else if (err.response?.status === 500) {
      errorMessage = err.response?.data?.message || 'Server error occurred. Please try again.'
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }

    error.value = errorMessage
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const handleLinkedInOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting LinkedIn.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing LinkedIn OAuth request...', duration: 800 },
    { message: 'Connecting to LinkedIn for Business...', duration: 1200 },
    { message: 'Validating business API permissions...', duration: 1000 },
    { message: 'Redirecting to LinkedIn...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating LinkedIn OAuth')

    // Get OAuth URL from backend
    const response = await axios.post('/api/linkedin/oauth/initiate')

    if (response.data.oauth_url) {
      console.log('IntegrationModal - got OAuth URL, redirecting to LinkedIn')

      // Brief delay to show final step
      await new Promise(resolve => setTimeout(resolve, 1500))

      // Redirect to LinkedIn OAuth URL
      window.location.href = response.data.oauth_url
    } else {
      throw new Error('Invalid OAuth response from server')
    }

  } catch (err: any) {
    console.error('LinkedIn OAuth error:', err)
    error.value = err.response?.data?.message || 'Failed to connect to LinkedIn. Please try again.'
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const handleTwitterOAuth = async () => {
  // Check authentication first
  if (!isAuthenticated.value) {
    error.value = 'Please ensure you are logged in before connecting X/Twitter.'
    return
  }

  loading.value = true
  error.value = ''

  // Start progress simulation
  simulateProgress([
    { message: 'Preparing X/Twitter OAuth request...', duration: 800 },
    { message: 'Connecting to X for Business...', duration: 1200 },
    { message: 'Validating advertising API permissions...', duration: 1000 },
    { message: 'Redirecting to X...', duration: 500 }
  ])

  try {
    console.log('IntegrationModal - initiating Twitter OAuth')

    // Get OAuth URL from backend
    const response = await axios.post('/api/twitter/oauth/initiate')

    if (response.data.oauth_url) {
      console.log('IntegrationModal - got OAuth URL, redirecting to Twitter')

      // Brief delay to show final step
      await new Promise(resolve => setTimeout(resolve, 1500))

      // Redirect to Twitter OAuth URL
      window.location.href = response.data.oauth_url
    } else {
      throw new Error('Invalid OAuth response from server')
    }

  } catch (err: any) {
    console.error('Twitter OAuth error:', err)
    error.value = err.response?.data?.message || 'Failed to connect to X/Twitter. Please try again.'
    loading.value = false
    connectionProgress.value = {
      step: 0,
      totalSteps: 4,
      message: ''
    }
  }
}

const validateCredentials = async (platform: string, config: any) => {
  // Simulate credential validation
  connectionProgress.value.message = 'Validating credentials...'
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  // Simulate account discovery for preview
  if (platform === 'google') {
    connectionProgress.value.message = 'Discovering Google Ads accounts...'
    await new Promise(resolve => setTimeout(resolve, 1500))
    previewAccounts.value = [
      { id: '123-456-7890', name: 'Main Campaign Account', currency: 'USD', status: 1 },
      { id: '098-765-4321', name: 'Brand Awareness', currency: 'USD', status: 1 }
    ]
  } else if (platform === 'tiktok') {
    connectionProgress.value.message = 'Discovering TikTok ad accounts...'
    await new Promise(resolve => setTimeout(resolve, 1500))
    previewAccounts.value = [
      { id: 'tt_123456', name: 'TikTok Business Account', currency: 'USD', status: 1 }
    ]
  }
}

const handleSubmit = async () => {
  if (!props.platform) return

  // All platforms use OAuth flow now
  if (props.platform.id === 'facebook') {
    return handleFacebookOAuth()
  }

  if (props.platform.id === 'google-ads') {
    return handleGoogleAdsOAuth()
  }

  if (props.platform.id === 'tiktok') {
    return handleTikTokOAuth()
  }

  if (props.platform.id === 'snapchat') {
    return handleSnapchatOAuth()
  }

  if (props.platform.id === 'linkedin') {
    return handleLinkedInOAuth()
  }

  if (props.platform.id === 'twitter') {
    return handleTwitterOAuth()
  }

  loading.value = true
  error.value = ''
  previewAccounts.value = []
  
  // Start progress tracking
  simulateProgress([
    { message: 'Preparing connection...', duration: 500 },
    { message: 'Testing credentials...', duration: 1200 },
    { message: 'Discovering accounts...', duration: 1800 },
    { message: 'Setting up integration...', duration: 1000 }
  ])

  try {
    const config: any = {}

    // Build config based on platform
    if (props.platform.id === 'google') {
      config.client_id = form.client_id
      config.client_secret = form.client_secret
      config.developer_token = form.developer_token
      config.refresh_token = form.refresh_token
    } else if (props.platform.id === 'tiktok') {
      config.app_id = form.app_id
      config.secret = form.secret
      config.access_token = form.access_token
    }

    // Validate credentials and preview accounts
    await validateCredentials(props.platform.id, config)
    
    // Create integration
    connectionProgress.value.message = 'Creating integration...'
    const response = await axios.post('/api/integrations', {
      platform: props.platform.id,
      app_config: config
    })

    connectionProgress.value.message = 'Integration created successfully!'
    
    setTimeout(() => {
      emit('success', response.data)
      resetForm()
    }, 1000)
    
  } catch (err: any) {
    connectionProgress.value.step = 0
    error.value = err.response?.data?.message || 'Failed to connect integration. Please check your credentials.'
  } finally {
    loading.value = false
  }
}

// Reset form when modal opens/closes or platform changes
watch([() => props.show, () => props.platform], () => {
  if (props.show) {
    resetForm()
  }
})
</script>
