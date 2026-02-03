<template>
  <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 p-6">
    <div class="flex items-start justify-between">
      <!-- Client Logo & Info -->
      <div class="flex items-center space-x-4 flex-1">
        <div class="flex-shrink-0">
          <div v-if="client.logo_url" class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100">
            <img :src="client.logo_url" :alt="client.name" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-16 h-16 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
            <span class="text-white text-2xl font-bold">{{ getInitials(client.name) }}</span>
          </div>
        </div>

        <div class="flex-1 min-w-0">
          <router-link
            :to="{ name: 'client-dashboard', params: { id: client.id } }"
            class="text-lg font-semibold text-gray-900 truncate hover:text-primary-600 hover:underline">
            {{ client.name }}
          </router-link>
          <p v-if="client.industry" class="text-sm text-gray-500 capitalize">
            {{ formatIndustry(client.industry) }}
          </p>
          <div class="flex items-center space-x-4 mt-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="getStatusClass(client.status)">
              {{ client.status }}
            </span>
            <span v-if="client.subscription_tier" class="text-xs text-gray-500 capitalize">
              {{ client.subscription_tier }} {{ $t('labels.tier') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-2 ml-4">
        <button
          @click="$emit('view', client.id)"
          class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100"
          :title="$t('tooltips.view_dashboard')"
        >
          <ChartBarIcon class="w-5 h-5" />
        </button>
        <button
          @click="$emit('edit', client)"
          class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100"
          :title="$t('tooltips.edit_client')"
        >
          <PencilIcon class="w-5 h-5" />
        </button>
        <button
          @click="$emit('delete', client)"
          class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
          :title="$t('tooltips.delete_client')"
        >
          <TrashIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="mt-6 grid grid-cols-3 gap-4">
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-900">
          {{ client.ad_accounts_count || 0 }}
        </div>
        <div class="text-xs text-gray-500">{{ $t('labels.ad_accounts') }}</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-900">
          {{ formatCurrency(client.total_spend || 0) }}
        </div>
        <div class="text-xs text-gray-500">{{ $t('labels.total_spend') }}</div>
      </div>
      <div class="text-center">
        <div v-if="client.contract_end_date" class="text-sm font-semibold"
          :class="getDaysUntilExpiryClass(client.days_until_contract_expires)">
          {{ formatDaysUntilExpiry(client.days_until_contract_expires) }}
        </div>
        <div v-else class="text-sm text-gray-400">{{ $t('labels.no_contract') }}</div>
        <div class="text-xs text-gray-500">{{ $t('labels.contract') }}</div>
      </div>
    </div>

    <!-- Contact Info -->
    <div v-if="client.contact_email || client.contact_phone" class="mt-4 pt-4 border-t border-gray-200">
      <div class="flex items-center justify-between text-sm">
        <div v-if="client.contact_person" class="text-gray-600">
          <span class="font-medium">{{ client.contact_person }}</span>
        </div>
        <div class="flex items-center space-x-3 text-gray-500">
          <a v-if="client.contact_email" :href="`mailto:${client.contact_email}`"
            class="hover:text-primary-600" :title="client.contact_email">
            <EnvelopeIcon class="w-4 h-4" />
          </a>
          <a v-if="client.contact_phone" :href="`tel:${client.contact_phone}`"
            class="hover:text-primary-600" :title="client.contact_phone">
            <PhoneIcon class="w-4 h-4" />
          </a>
          <a v-if="client.website" :href="client.website" target="_blank"
            class="hover:text-primary-600" :title="$t('tooltips.visit_website')">
            <GlobeAltIcon class="w-4 h-4" />
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { ChartBarIcon, PencilIcon, TrashIcon, EnvelopeIcon, PhoneIcon, GlobeAltIcon } from '@heroicons/vue/24/outline'
import type { Client } from '@/types/client'

const { t } = useI18n()

interface Props {
  client: Client
}

defineProps<Props>()

defineEmits<{
  view: [id: number]
  edit: [client: Client]
  delete: [client: Client]
}>()

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatIndustry = (industry: string): string => {
  return industry.replace(/_/g, ' ')
}

const formatCurrency = (amount: number): string => {
  if (amount >= 1000000) {
    return `${(amount / 1000000).toFixed(1)}M SAR`
  } else if (amount >= 1000) {
    return `${(amount / 1000).toFixed(1)}K SAR`
  }
  return `${amount.toFixed(0)} SAR`
}

const getStatusClass = (status: string): string => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    suspended: 'bg-red-100 text-red-800',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const formatDaysUntilExpiry = (days: number | null | undefined): string => {
  if (days === null || days === undefined) return t('status.not_available')
  if (days < 0) return t('labels.expired')
  if (days === 0) return t('labels.expires_today')
  if (days === 1) return `1 ${t('labels.day')}`
  return `${days} ${t('labels.days')}`
}

const getDaysUntilExpiryClass = (days: number | null | undefined): string => {
  if (days === null || days === undefined) return 'text-gray-500'
  if (days < 0) return 'text-red-600'
  if (days <= 30) return 'text-orange-600'
  return 'text-green-600'
}
</script>
