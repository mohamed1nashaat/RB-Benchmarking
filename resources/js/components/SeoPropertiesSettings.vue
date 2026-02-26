<template>
  <div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between cursor-pointer" @click="isOpen = !isOpen">
      <div class="flex items-center space-x-2">
        <Cog6ToothIcon class="w-5 h-5 text-gray-500" />
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('client_dashboard.seo.settings_title') }}</h3>
      </div>
      <ChevronDownIcon
        class="w-5 h-5 text-gray-400 transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </div>

    <!-- No Integration -->
    <div v-if="!status?.has_integration" class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
      <p class="text-sm font-medium text-gray-900">{{ $t('client_dashboard.seo.no_integration') }}</p>
      <p class="text-sm text-gray-500 mt-1">{{ $t('client_dashboard.seo.no_integration_desc') }}</p>
      <div class="mt-4">
        <button @click="connectGoogle" :disabled="connecting"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200 disabled:opacity-50">
          <ArrowPathIcon v-if="connecting" class="animate-spin -ml-1 mr-1.5 h-4 w-4" />
          {{ connecting ? $t('client_dashboard.seo.connecting') : $t('client_dashboard.seo.connect_google_btn') }}
        </button>
      </div>
      <router-link to="/integrations" class="text-xs text-gray-500 hover:text-primary-600 mt-3 inline-block">
        {{ $t('client_dashboard.seo.or_go_to_integrations') }}
      </router-link>
    </div>

    <!-- Needs Re-auth -->
    <div v-else-if="!status?.has_search_console_scope" class="mt-4 bg-primary-50 border border-primary-200 rounded-lg p-6 text-center">
      <p class="text-sm font-medium text-primary-800">{{ $t('client_dashboard.seo.needs_reauth') }}</p>
      <p class="text-sm text-primary-700 mt-1">{{ $t('client_dashboard.seo.needs_reauth_desc') }}</p>
      <div class="mt-4">
        <button @click="connectGoogle" :disabled="connecting"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200 disabled:opacity-50">
          <ArrowPathIcon v-if="connecting" class="animate-spin -ml-1 mr-1.5 h-4 w-4" />
          {{ connecting ? $t('client_dashboard.seo.connecting') : $t('client_dashboard.seo.reauthorize_btn') }}
        </button>
      </div>
    </div>

    <!-- Settings Panel -->
    <transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="max-h-0 opacity-0"
      enter-to-class="max-h-96 opacity-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="max-h-96 opacity-100"
      leave-to-class="max-h-0 opacity-0"
    >
      <div v-if="isOpen && status?.has_integration" class="mt-4 space-y-4 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search Console Site -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('client_dashboard.seo.search_console_site') }}
            </label>
            <select
              v-model="form.search_console_site"
              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
              :disabled="loadingSites"
            >
              <option :value="null">{{ loadingSites ? $t('common.loading') : $t('client_dashboard.seo.select_site') }}</option>
              <option v-for="site in scSites" :key="site.site_url" :value="site.site_url">
                {{ site.site_url }}
              </option>
            </select>
          </div>

          <!-- GA4 Property -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('client_dashboard.seo.ga4_property') }}
            </label>
            <select
              v-model="form.ga4_property_id"
              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
              :disabled="loadingProperties"
              @change="onGA4PropertyChange"
            >
              <option :value="null">{{ loadingProperties ? $t('common.loading') : $t('client_dashboard.seo.select_property') }}</option>
              <option v-for="prop in ga4Properties" :key="prop.property_id" :value="prop.property_id">
                {{ prop.display_name }} ({{ prop.account_name }})
              </option>
            </select>
          </div>

          <!-- PageSpeed URL -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('client_dashboard.seo.pagespeed_url') }}
            </label>
            <input
              v-model="form.pagespeed_url"
              type="url"
              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="https://example.com"
            />
          </div>
        </div>

        <div class="flex justify-end">
          <button
            @click="saveProperties"
            :disabled="saving"
            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
          >
            <CheckIcon v-if="!saving" class="w-4 h-4 mr-1.5" />
            <ArrowPathIcon v-else class="animate-spin w-4 h-4 mr-1.5" />
            {{ saving ? $t('common.saving') : $t('common.save') }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import {
  Cog6ToothIcon,
  ChevronDownIcon,
  ArrowPathIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'
import type { SeoStatus, SeoProperties, SearchConsoleSite, GA4Property } from '@/types/seo'

const props = defineProps<{
  tenantId: number | string
}>()

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const isOpen = ref(false)
const status = ref<SeoStatus | null>(null)
const form = ref<SeoProperties>({
  search_console_site: null,
  ga4_property_id: null,
  ga4_property_name: null,
  pagespeed_url: null,
})

const scSites = ref<SearchConsoleSite[]>([])
const ga4Properties = ref<GA4Property[]>([])
const loadingSites = ref(false)
const loadingProperties = ref(false)
const saving = ref(false)
const connecting = ref(false)
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null)

const fetchStatus = async () => {
  try {
    const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/status`)
    status.value = data
  } catch (error) {
    console.error('Failed to fetch SEO status:', error)
  }
}

const fetchProperties = async () => {
  try {
    const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/properties`)
    form.value = data
  } catch (error) {
    console.error('Failed to fetch SEO properties:', error)
  }
}

const fetchSCSites = async () => {
  if (!status.value?.has_integration) return
  loadingSites.value = true
  try {
    const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/search-console/sites`)
    scSites.value = data.sites || []
  } catch (error: any) {
    if (error.response?.status === 403) {
      if (status.value) status.value.has_search_console_scope = false
    }
    console.error('Failed to fetch SC sites:', error)
  } finally {
    loadingSites.value = false
  }
}

const fetchGA4Properties = async () => {
  if (!status.value?.has_integration) return
  loadingProperties.value = true
  try {
    const { data } = await window.axios.get(`/api/clients/${props.tenantId}/seo/ga4/properties`)
    ga4Properties.value = data.properties || []
  } catch (error) {
    console.error('Failed to fetch GA4 properties:', error)
  } finally {
    loadingProperties.value = false
  }
}

const onGA4PropertyChange = () => {
  const selected = ga4Properties.value.find(p => p.property_id === form.value.ga4_property_id)
  form.value.ga4_property_name = selected ? selected.display_name : null
}

const stopPolling = () => {
  if (pollTimer.value) {
    clearInterval(pollTimer.value)
    pollTimer.value = null
  }
}

const connectGoogle = async () => {
  connecting.value = true
  try {
    const response = await window.axios.get('/api/google-ads/auth-url', {
      headers: { 'X-Tenant-ID': String(props.tenantId) }
    })

    if (response.data.success && response.data.oauth_url) {
      window.open(response.data.oauth_url, '_blank', 'width=500,height=600')

      // Poll for authorization completion every 3 seconds
      pollTimer.value = setInterval(async () => {
        await fetchStatus()
        if (status.value?.has_integration) {
          stopPolling()
          connecting.value = false
          fetchSCSites()
          fetchGA4Properties()
        }
      }, 3000)

      // Stop polling after 5 minutes
      setTimeout(() => {
        if (connecting.value) {
          stopPolling()
          connecting.value = false
        }
      }, 5 * 60 * 1000)
    } else {
      connecting.value = false
    }
  } catch (error) {
    console.error('Failed to initiate Google auth:', error)
    connecting.value = false
  }
}

const saveProperties = async () => {
  saving.value = true
  try {
    await window.axios.put(`/api/clients/${props.tenantId}/seo/properties`, form.value)
    emit('saved')
  } catch (error) {
    console.error('Failed to save SEO properties:', error)
  } finally {
    saving.value = false
  }
}

watch(() => status.value?.has_integration, (hasIntegration) => {
  if (hasIntegration) {
    fetchSCSites()
    fetchGA4Properties()
  }
})

onBeforeUnmount(() => {
  stopPolling()
})

onMounted(async () => {
  await fetchStatus()
  await fetchProperties()
})
</script>
