<template>
  <div class="relative">
    <Popover class="relative">
      <PopoverButton
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <FunnelIcon class="h-4 w-4 mr-2" aria-hidden="true" />
        {{ $t('dashboard.filters') }}
        <span v-if="activeFiltersCount > 0" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
          {{ activeFiltersCount }}
        </span>
        <ChevronDownIcon class="ml-2 -mr-1 h-4 w-4" aria-hidden="true" />
      </PopoverButton>

      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
      >
        <PopoverPanel
          class="absolute z-10 mt-3 w-80 max-w-sm bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        >
          <div class="p-4">
            <div class="space-y-4">
              <!-- Platform Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Platform
                </label>
                <select
                  v-model="localFilters.platform"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
                  <option value="">All Platforms</option>
                  <option value="facebook">{{ $t('platforms.facebook') }}</option>
                  <option value="google">{{ $t('platforms.google') }}</option>
                  <option value="tiktok">{{ $t('platforms.tiktok') }}</option>
                </select>
              </div>

              <!-- Account Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Ad Account
                </label>
                <select
                  v-model="localFilters.account_id"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  :disabled="loadingAccounts"
                >
                  <option value="">All Accounts</option>
                  <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                  >
                    {{ account.account_name }}
                  </option>
                </select>
              </div>

              <!-- Campaign Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Campaign
                </label>
                <select
                  v-model="localFilters.campaign_id"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  :disabled="loadingCampaigns || !localFilters.account_id"
                >
                  <option value="">All Campaigns</option>
                  <option
                    v-for="campaign in campaigns"
                    :key="campaign.id"
                    :value="campaign.id"
                  >
                    {{ campaign.name }}
                  </option>
                </select>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-2 pt-4 border-t border-gray-200">
                <button
                  @click="applyFilters"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  {{ $t('common.apply') }}
                </button>
                <button
                  @click="clearFilters"
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  {{ $t('common.clear') }}
                </button>
              </div>
            </div>
          </div>
        </PopoverPanel>
      </transition>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { FunnelIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'
import axios from 'axios'

const dashboardStore = useDashboardStore()

const localFilters = reactive({
  platform: dashboardStore.filters.platform || '',
  account_id: dashboardStore.filters.account_id || '',
  campaign_id: dashboardStore.filters.campaign_id || ''
})

const accounts = ref([])
const campaigns = ref([])
const loadingAccounts = ref(false)
const loadingCampaigns = ref(false)

const activeFiltersCount = computed(() => {
  return Object.values(dashboardStore.filters).filter(value => value !== undefined && value !== '').length
})

const fetchAccounts = async () => {
  loadingAccounts.value = true
  try {
    const response = await axios.get('/api/ad-accounts')
    accounts.value = response.data.data
  } catch (error) {
    console.error('Error fetching accounts:', error)
  } finally {
    loadingAccounts.value = false
  }
}

const fetchCampaigns = async (accountId: string) => {
  if (!accountId) {
    campaigns.value = []
    return
  }

  loadingCampaigns.value = true
  try {
    const response = await axios.get('/api/ad-campaigns', {
      params: { account_id: accountId }
    })
    campaigns.value = response.data.data
  } catch (error) {
    console.error('Error fetching campaigns:', error)
  } finally {
    loadingCampaigns.value = false
  }
}

const applyFilters = () => {
  const filters: any = {}
  
  if (localFilters.platform) {
    filters.platform = localFilters.platform
  }
  
  if (localFilters.account_id) {
    filters.account_id = parseInt(localFilters.account_id)
  }
  
  if (localFilters.campaign_id) {
    filters.campaign_id = parseInt(localFilters.campaign_id)
  }

  dashboardStore.setFilters(filters)
}

const clearFilters = () => {
  localFilters.platform = ''
  localFilters.account_id = ''
  localFilters.campaign_id = ''
  campaigns.value = []
  dashboardStore.clearFilters()
}

// Watch for account changes to fetch campaigns
watch(() => localFilters.account_id, (newAccountId) => {
  localFilters.campaign_id = '' // Reset campaign selection
  if (newAccountId) {
    fetchCampaigns(newAccountId)
  } else {
    campaigns.value = []
  }
})

onMounted(() => {
  fetchAccounts()
  
  // If there's an existing account filter, fetch its campaigns
  if (localFilters.account_id) {
    fetchCampaigns(localFilters.account_id)
  }
})
</script>
