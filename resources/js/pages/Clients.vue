<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('pages.clients.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('pages.clients.description') }}
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="openWizard"
            class="inline-flex items-center px-4 py-2 border border-primary-600 text-sm font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            {{ $t('pages.clients.create_from_accounts') }}
          </button>
          <button
            @click="handleCreateManually"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            {{ $t('pages.clients.add_manually') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Overview Statistics -->
    <div v-if="overview" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <!-- Total Clients -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
            <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
              <dd class="flex items-baseline">
                <div class="text-2xl font-semibold text-gray-900">{{ overview.totals.clients }}</div>
                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                  {{ overview.totals.active_clients }} active
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Ad Accounts -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Ad Accounts</dt>
              <dd class="flex items-baseline">
                <div class="text-2xl font-semibold text-gray-900">{{ overview.totals.ad_accounts }}</div>
                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                  {{ overview.totals.connected_accounts }} connected
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Total Spend -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Total Spend</dt>
              <dd class="text-2xl font-semibold text-gray-900">
                {{ formatCurrency(overview.totals.total_spend) }}
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-gray-900">Filters</h3>
        <button
          v-if="hasActiveFilters"
          @click="clearAllFilters"
          class="text-sm text-primary-600 hover:text-primary-700"
        >
          Clear all
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.search') }}</label>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="$t('pages.clients.search_placeholder')"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.status') }}</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="fetchClients"
          >
            <option value="">{{ $t('filters.all_statuses') }}</option>
            <option value="active">{{ $t('status.active') }}</option>
            <option value="inactive">{{ $t('status.inactive') }}</option>
            <option value="suspended">{{ $t('status.suspended') }}</option>
          </select>
        </div>

        <!-- Industry Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.industry') }}</label>
          <select
            v-model="filters.industry"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="fetchClients"
          >
            <option value="">{{ $t('filters.all_industries') }}</option>
            <option v-for="industry in industries" :key="industry.name" :value="industry.name">
              {{ industry.display_name }}
            </option>
          </select>
        </div>

        <!-- Subscription Tier Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.subscription') }}</label>
          <select
            v-model="filters.subscription_tier"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @change="fetchClients"
          >
            <option value="">{{ $t('filters.all_tiers') }}</option>
            <option value="basic">{{ $t('subscription_tiers.basic') }}</option>
            <option value="pro">{{ $t('subscription_tiers.pro') }}</option>
            <option value="enterprise">{{ $t('subscription_tiers.enterprise') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading Skeletons -->
    <div v-if="loading" class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th scope="col" class="w-12 px-4 py-3">
              <div class="h-4 w-4 bg-gray-200 rounded"></div>
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.client') }}
            </th>
            <th v-if="columnVisibility.industry" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.industry') }}
            </th>
            <th v-if="columnVisibility.status" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.status') }}
            </th>
            <th v-if="columnVisibility.subscription" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.subscription') }}
            </th>
            <th v-if="columnVisibility.adAccounts" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.ad_accounts') }}
            </th>
            <th v-if="columnVisibility.totalSpend" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.total_spend') }}
            </th>
            <th v-if="columnVisibility.contract" scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.contract') }}
            </th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ $t('labels.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="i in 6" :key="i" class="animate-pulse">
            <td class="px-4 py-4">
              <div class="h-4 w-4 bg-gray-200 rounded"></div>
            </td>
            <td class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            </td>
            <td v-if="columnVisibility.industry" class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </td>
            <td v-if="columnVisibility.status" class="px-4 py-4">
              <div class="h-6 bg-gray-200 rounded-full w-16"></div>
            </td>
            <td v-if="columnVisibility.subscription" class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </td>
            <td v-if="columnVisibility.adAccounts" class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-8"></div>
            </td>
            <td v-if="columnVisibility.totalSpend" class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-20"></div>
            </td>
            <td v-if="columnVisibility.contract" class="px-4 py-4">
              <div class="h-4 bg-gray-200 rounded w-16"></div>
            </td>
            <td class="px-4 py-4">
              <div class="flex justify-end space-x-2">
                <div class="h-8 w-8 bg-gray-200 rounded"></div>
                <div class="h-8 w-8 bg-gray-200 rounded"></div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedClients.length > 0" class="bg-primary-50 border border-primary-200 rounded-lg p-4 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <span class="text-sm font-medium text-primary-900">
            {{ selectedClients.length }} client{{ selectedClients.length !== 1 ? 's' : '' }} selected
          </span>
          <button
            @click="clearSelection"
            class="text-sm text-primary-600 hover:text-primary-800"
          >
            Clear selection
          </button>
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="bulkUpdateStatus('active')"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
          >
            Activate
          </button>
          <button
            @click="bulkUpdateStatus('inactive')"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700"
          >
            Deactivate
          </button>
          <button
            @click="bulkUpdateTier"
            class="inline-flex items-center px-3 py-2 border border-primary-600 text-sm font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50"
          >
            Change Tier
          </button>
          <button
            @click="bulkExport"
            class="inline-flex items-center px-3 py-2 border border-primary-600 text-sm font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50"
          >
            Export
          </button>
          <button
            @click="bulkDelete"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Clients Table -->
    <div v-if="!loading && clients.length > 0" class="bg-white shadow rounded-lg relative max-h-[600px] overflow-x-auto overflow-y-auto">
      <!-- Table Loading Overlay -->
      <div v-if="tableLoading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
          <tr>
            <th scope="col" class="w-12 px-4 py-3">
              <input
                type="checkbox"
                :checked="allClientsSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
            </th>
            <th
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('name')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.client') }}
                <ChevronUpIcon v-if="filters.sort_by === 'name' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'name' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.industry"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('industry')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.industry') }}
                <ChevronUpIcon v-if="filters.sort_by === 'industry' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'industry' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.status"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('status')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.status') }}
                <ChevronUpIcon v-if="filters.sort_by === 'status' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'status' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.subscription"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('subscription_tier')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.subscription') }}
                <ChevronUpIcon v-if="filters.sort_by === 'subscription_tier' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'subscription_tier' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.adAccounts"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('ad_accounts_count')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.ad_accounts') }}
                <ChevronUpIcon v-if="filters.sort_by === 'ad_accounts_count' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'ad_accounts_count' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.totalSpend"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('total_spend')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.total_spend') }}
                <ChevronUpIcon v-if="filters.sort_by === 'total_spend' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'total_spend' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th
              v-if="columnVisibility.contract"
              scope="col"
              class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none"
              @click="handleSort('contract_end_date')"
            >
              <div class="flex items-center gap-1">
                {{ $t('labels.contract') }}
                <ChevronUpIcon v-if="filters.sort_by === 'contract_end_date' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                <ChevronDownIcon v-else-if="filters.sort_by === 'contract_end_date' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
              </div>
            </th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <div class="flex items-center justify-end gap-2">
                <span>{{ $t('labels.actions') }}</span>
                <!-- Column Visibility Selector -->
                <div class="relative" @click.stop>
                  <button
                    @click="showColumnSelector = !showColumnSelector"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
                    title="Show/Hide Columns"
                  >
                    <Bars3BottomLeftIcon class="h-4 w-4" />
                  </button>
                  <div
                    v-if="showColumnSelector"
                    class="absolute right-0 mt-2 bg-white shadow-lg rounded-lg p-3 z-50 border border-gray-200 min-w-[180px]"
                  >
                    <div class="text-xs font-medium text-gray-500 uppercase mb-2">Show/Hide Columns</div>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.industry" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Industry</span>
                    </label>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.status" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Status</span>
                    </label>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.subscription" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Subscription</span>
                    </label>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.adAccounts" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Ad Accounts</span>
                    </label>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.totalSpend" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Total Spend</span>
                    </label>
                    <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                      <input type="checkbox" v-model="columnVisibility.contract" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                      <span class="text-sm text-gray-700">Contract</span>
                    </label>
                  </div>
                </div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="client in clients"
            :key="client.id"
            class="even:bg-gray-50 hover:bg-primary-50 transition-colors duration-150 cursor-pointer border-l-2 border-l-transparent hover:border-l-primary-500"
            @click="viewClientDashboard(client.id)"
          >
            <td class="px-4 py-4" @click.stop>
              <input
                type="checkbox"
                :checked="selectedClients.includes(client.id)"
                @change="toggleClientSelection(client.id)"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
            </td>
            <td class="px-4 py-4">
              <div>
                <router-link
                  :to="{ name: 'client-dashboard', params: { id: client.id } }"
                  class="text-sm font-medium text-gray-900 hover:text-primary-600 hover:underline"
                >
                  {{ client.name }}
                </router-link>
                <div v-if="client.contact_email" class="text-xs text-gray-500">
                  {{ client.contact_email }}
                </div>
              </div>
            </td>
            <td v-if="columnVisibility.industry" class="px-4 py-4 text-sm text-gray-500" @click.stop>
              <div v-if="editingCell?.clientId === client.id && editingCell?.field === 'industry'" class="relative">
                <select
                  ref="editSelect"
                  v-model="editValue"
                  @change="saveEdit(client)"
                  class="w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="">-</option>
                  <option v-for="industry in industries" :key="industry.name" :value="industry.name">
                    {{ industry.display_name }}
                  </option>
                </select>
                <button
                  @click="cancelEdit"
                  class="absolute -right-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  type="button"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
              <span
                v-else
                class="cursor-pointer hover:text-primary-600 hover:underline capitalize"
                @click="startEditing(client.id, 'industry', client.industry || '')"
              >
                {{ client.industry ? formatIndustry(client.industry) : '-' }}
              </span>
            </td>
            <td v-if="columnVisibility.status" class="px-4 py-4" @click.stop>
              <div v-if="editingCell?.clientId === client.id && editingCell?.field === 'status'" class="relative">
                <select
                  ref="editSelect"
                  v-model="editValue"
                  @change="saveEdit(client)"
                  class="w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
                <button
                  @click="cancelEdit"
                  class="absolute -right-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  type="button"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
              <span
                v-else
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-opacity duration-150 hover:opacity-80"
                :class="getStatusClass(client.status)"
                @click="startEditing(client.id, 'status', client.status)"
              >
                {{ client.status }}
              </span>
            </td>
            <td v-if="columnVisibility.subscription" class="px-4 py-4 text-sm text-gray-500" @click.stop>
              <div v-if="editingCell?.clientId === client.id && editingCell?.field === 'subscription_tier'" class="relative">
                <select
                  ref="editSelect"
                  v-model="editValue"
                  @change="saveEdit(client)"
                  class="w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="">-</option>
                  <option value="basic">Basic</option>
                  <option value="pro">Pro</option>
                  <option value="enterprise">Enterprise</option>
                </select>
                <button
                  @click="cancelEdit"
                  class="absolute -right-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  type="button"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
              <span
                v-else
                class="cursor-pointer hover:text-primary-600 hover:underline capitalize"
                @click="startEditing(client.id, 'subscription_tier', client.subscription_tier || '')"
              >
                {{ client.subscription_tier || '-' }}
              </span>
            </td>
            <td v-if="columnVisibility.adAccounts" class="px-4 py-4 text-sm text-gray-900 font-medium">
              {{ client.ad_accounts_count || 0 }}
            </td>
            <td v-if="columnVisibility.totalSpend" class="px-4 py-4 text-sm text-gray-900 font-medium">
              {{ formatTableCurrency(client.total_spend || 0) }}
            </td>
            <td v-if="columnVisibility.contract" class="px-4 py-4">
              <div v-if="client.contract_end_date">
                <span
                  class="text-sm font-medium"
                  :class="getDaysUntilExpiryClass(client.days_until_contract_expires)"
                >
                  {{ formatDaysUntilExpiry(client.days_until_contract_expires) }}
                </span>
              </div>
              <span v-else class="text-sm text-gray-400">{{ $t('labels.no_contract') }}</span>
            </td>
            <td class="px-4 py-4 text-right" @click.stop>
              <div class="flex items-center justify-end space-x-2">
                <button
                  @click="viewClientDashboard(client.id)"
                  class="p-1.5 text-gray-400 hover:text-primary-600 rounded hover:bg-gray-100"
                  :title="$t('tooltips.view_dashboard')"
                >
                  <ChartBarIcon class="w-5 h-5" />
                </button>
                <button
                  @click="editClient(client)"
                  class="p-1.5 text-gray-400 hover:text-primary-600 rounded hover:bg-gray-100"
                  :title="$t('tooltips.edit_client')"
                >
                  <PencilIcon class="w-5 h-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && clients.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.clients.no_clients') }}</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ filters.search || filters.status || filters.industry || filters.subscription_tier
          ? $t('pages.clients.adjust_filters')
          : $t('pages.clients.get_started')
        }}
      </p>
      <div class="mt-6">
        <button
          @click="handleCreateManually"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          {{ $t('pages.clients.add_manually') }}
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="mt-6 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <div class="text-sm text-gray-700">
          {{ $t('pages.clients.showing', { from: pagination.from, to: pagination.to, total: pagination.total }) }}
        </div>
        <div class="flex items-center space-x-2">
          <label class="text-sm text-gray-700">Show:</label>
          <select
            v-model="filters.per_page"
            @change="handlePerPageChange"
            class="rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="12">12</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
      <div v-if="pagination.last_page > 1" class="flex space-x-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          {{ $t('buttons.previous') }}
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="changePage(page)"
          :class="[
            'px-3 py-2 text-sm border rounded-md',
            page === pagination.current_page
              ? 'bg-primary-600 text-white border-primary-600'
              : 'border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          {{ $t('buttons.next') }}
        </button>
      </div>
    </div>

    <!-- Client Wizard -->
    <ClientWizard
      :open="showWizard"
      :accounts="accounts"
      @close="showWizard = false"
      @created="handleClientCreated"
    />

    <!-- Create/Edit Form Modal -->
    <ClientFormModal
      :open="showFormModal"
      :client="editingClient"
      @close="showFormModal = false"
      @saved="handleFormSaved"
    />

    <!-- Delete Confirmation Dialog -->
    <DeleteConfirmDialog
      :open="showDeleteDialog"
      :loading="deleteLoading"
      :title="deletingClient ? `Delete ${deletingClient.name}?` : 'Delete Client?'"
      :message="deletingClient ? `Are you sure you want to delete ${deletingClient.name}? This action cannot be undone.` : 'Are you sure you want to delete this client?'"
      :has-ad-accounts="!!deletingClient?.ad_accounts_count"
      :ad-accounts-count="deletingClient?.ad_accounts_count || 0"
      @close="showDeleteDialog = false"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, ChartBarIcon, PencilIcon, Bars3BottomLeftIcon, XMarkIcon, ChevronUpIcon, ChevronDownIcon, ChevronUpDownIcon } from '@heroicons/vue/24/outline'
import { Line, Doughnut, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import ClientCard from '@/components/ClientCard.vue'
import ClientWizard from '@/components/ClientWizard.vue'
import ClientFormModal from '@/components/ClientFormModal.vue'
import DeleteConfirmDialog from '@/components/DeleteConfirmDialog.vue'
import type { Client, ClientFilters, PaginatedClients } from '@/types/client'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const router = useRouter()

const clients = ref<Client[]>([])
const accounts = ref<any[]>([])
const industries = ref<{ name: string; display_name: string }[]>([])
const overview = ref<any>(null)
const loading = ref(false)
const tableLoading = ref(false)
const showWizard = ref(false)
const showFormModal = ref(false)
const editingClient = ref<Client | null>(null)
const showDeleteDialog = ref(false)
const deletingClient = ref<Client | null>(null)
const deleteLoading = ref(false)
const pagination = ref<PaginatedClients | null>(null)
const selectedClients = ref<number[]>([])

// Inline editing state
const editingCell = ref<{ clientId: number; field: string } | null>(null)
const editValue = ref<string>('')

// Column visibility state
const showColumnSelector = ref(false)
const columnVisibility = ref({
  industry: true,
  status: true,
  subscription: true,
  adAccounts: true,
  totalSpend: true,
  contract: true,
})

const filters = ref<ClientFilters>({
  search: '',
  status: '',
  industry: '',
  subscription_tier: '',
  sort_by: 'name',
  sort_order: 'asc',
  page: 1,
  per_page: 12,
})

let debounceTimer: NodeJS.Timeout | null = null

const debouncedSearch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    filters.value.page = 1
    fetchClients()
  }, 500)
}

// Column sorting
const handleSort = (field: string) => {
  if (filters.value.sort_by === field) {
    // Toggle sort order if same field
    filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
  } else {
    // New field, default to ascending
    filters.value.sort_by = field
    filters.value.sort_order = 'asc'
  }
  filters.value.page = 1
  fetchClients(true) // Use table loading
}

// Chart data computed properties
const chartData = computed(() => {
  if (!overview.value) return {}

  return {
    monthlyTrend: {
      labels: overview.value.monthly_trend?.map((item: any) => {
        const [year, month] = item.month.split('-')
        return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
      }) || [],
      datasets: [{
        label: 'Spend (SAR)',
        data: overview.value.monthly_trend?.map((item: any) => item.total_spend) || [],
        borderColor: 'rgb(99, 102, 241)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        fill: true,
        tension: 0.4,
      }]
    },
    platformBreakdown: {
      labels: overview.value.platform_breakdown?.map((item: any) => {
        return item.platform.charAt(0).toUpperCase() + item.platform.slice(1)
      }) || [],
      datasets: [{
        data: overview.value.platform_breakdown?.map((item: any) => item.count) || [],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(168, 85, 247, 0.8)',
        ],
        borderWidth: 2,
        borderColor: '#fff',
      }]
    },
    industryBreakdown: {
      labels: overview.value.industry_breakdown?.map((item: any) => {
        return item.industry.split('_').map((word: string) =>
          word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ')
      }) || [],
      datasets: [{
        label: 'Clients',
        data: overview.value.industry_breakdown?.map((item: any) => item.count) || [],
        backgroundColor: 'rgba(99, 102, 241, 0.8)',
        borderColor: 'rgb(99, 102, 241)',
        borderWidth: 1,
      }]
    }
  }
})

// Chart options
const chartOptions = {
  line: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            return `${formatCurrency(context.parsed.y)}`
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: any) => {
            return new Intl.NumberFormat('en-US', {
              notation: 'compact',
              compactDisplay: 'short'
            }).format(value)
          }
        }
      }
    }
  },
  doughnut: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: { padding: 10, boxWidth: 12 }
      }
    }
  },
  bar: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { stepSize: 1 }
      }
    }
  }
}

const fetchOverview = async () => {
  try {
    const response = await window.axios.get('/api/clients/overview')
    overview.value = response.data.data
  } catch (error) {
    console.error('Error fetching overview:', error)
  }
}

const fetchClients = async (useTableLoading = false) => {
  if (useTableLoading) {
    tableLoading.value = true
  } else {
    loading.value = true
  }
  try {
    const params = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) params.append(key, value.toString())
    })

    const response = await window.axios.get(`/api/clients?${params.toString()}`)

    clients.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
      from: response.data.from,
      to: response.data.to,
      data: response.data.data,
    }
  } catch (error) {
    console.error('Error fetching clients:', error)
  } finally {
    loading.value = false
    tableLoading.value = false
  }
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'SAR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount || 0)
}

const formatTableCurrency = (amount: number | string | null | undefined): string => {
  const num = Number(amount) || 0
  if (num >= 1000000) {
    return `${(num / 1000000).toFixed(1)}M SAR`
  } else if (num >= 1000) {
    return `${(num / 1000).toFixed(1)}K SAR`
  }
  return `${num.toFixed(0)} SAR`
}

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

const getStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    suspended: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDaysUntilExpiry = (days: number | null | undefined): string => {
  if (days === null || days === undefined) return '-'
  if (days < 0) return 'Expired'
  if (days === 0) return 'Today'
  if (days === 1) return '1 day'
  return `${days} days`
}

const getDaysUntilExpiryClass = (days: number | null | undefined): string => {
  if (days === null || days === undefined) return 'text-gray-500'
  if (days < 0) return 'text-red-600'
  if (days <= 30) return 'text-orange-600'
  return 'text-green-600'
}

// Inline editing functions
const startEditing = (clientId: number, field: string, currentValue: string) => {
  editingCell.value = { clientId, field }
  editValue.value = currentValue
}

const saveEdit = async (client: Client) => {
  if (!editingCell.value) return

  try {
    await window.axios.put(`/api/clients/${client.id}`, {
      [editingCell.value.field]: editValue.value || null
    })
    await fetchClients()
  } catch (error) {
    console.error('Error saving edit:', error)
    alert('Failed to save changes. Please try again.')
  } finally {
    editingCell.value = null
  }
}

const cancelEdit = () => {
  editingCell.value = null
}

const hasActiveFilters = computed(() => {
  return filters.value.search ||
    filters.value.status ||
    filters.value.industry ||
    filters.value.subscription_tier
})

const clearAllFilters = () => {
  filters.value = {
    search: '',
    status: '',
    industry: '',
    subscription_tier: '',
    sort_by: 'name',
    sort_order: 'asc',
    page: 1,
    per_page: 12,
  }
  fetchClients()
}

// Bulk operations
const allClientsSelected = computed(() => {
  return clients.value.length > 0 && selectedClients.value.length === clients.value.length
})

const toggleSelectAll = () => {
  if (allClientsSelected.value) {
    selectedClients.value = []
  } else {
    selectedClients.value = clients.value.map(c => c.id)
  }
}

const toggleClientSelection = (clientId: number) => {
  const index = selectedClients.value.indexOf(clientId)
  if (index > -1) {
    selectedClients.value.splice(index, 1)
  } else {
    selectedClients.value.push(clientId)
  }
}

const clearSelection = () => {
  selectedClients.value = []
}

const bulkUpdateStatus = async (status: string) => {
  if (!confirm(`Are you sure you want to ${status === 'active' ? 'activate' : 'deactivate'} ${selectedClients.value.length} client(s)?`)) {
    return
  }

  try {
    await Promise.all(
      selectedClients.value.map(id =>
        window.axios.put(`/api/clients/${id}`, { status })
      )
    )

    await fetchClients()
    clearSelection()
    alert(`Successfully updated ${selectedClients.value.length} client(s)`)
  } catch (error) {
    console.error('Error updating clients:', error)
    alert('Failed to update some clients. Please try again.')
  }
}

const bulkUpdateTier = async () => {
  const tier = prompt('Enter new subscription tier (basic, pro, or enterprise):')
  if (!tier || !['basic', 'pro', 'enterprise'].includes(tier)) {
    return
  }

  if (!confirm(`Are you sure you want to change ${selectedClients.value.length} client(s) to ${tier} tier?`)) {
    return
  }

  try {
    await Promise.all(
      selectedClients.value.map(id =>
        window.axios.put(`/api/clients/${id}`, { subscription_tier: tier })
      )
    )

    await fetchClients()
    clearSelection()
    alert(`Successfully updated ${selectedClients.value.length} client(s)`)
  } catch (error) {
    console.error('Error updating clients:', error)
    alert('Failed to update some clients. Please try again.')
  }
}

const bulkExport = () => {
  const selectedClientData = clients.value.filter(c => selectedClients.value.includes(c.id))
  const csv = [
    ['Name', 'Industry', 'Status', 'Tier', 'Accounts', 'Monthly Budget'],
    ...selectedClientData.map(c => [
      c.name,
      c.industry || '',
      c.status,
      c.subscription_tier || '',
      c.ad_accounts_count || 0,
      c.monthly_budget || 0
    ])
  ].map(row => row.join(',')).join('\n')

  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `clients-export-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

const bulkDelete = async () => {
  if (!confirm(`Are you sure you want to delete ${selectedClients.value.length} client(s)? This cannot be undone.`)) {
    return
  }

  // Check if any have ad accounts
  const clientsWithAccounts = clients.value.filter(
    c => selectedClients.value.includes(c.id) && c.ad_accounts_count > 0
  )

  if (clientsWithAccounts.length > 0) {
    alert(`Cannot delete ${clientsWithAccounts.length} client(s) that have ad accounts. Remove their accounts first.`)
    return
  }

  try {
    await Promise.all(
      selectedClients.value.map(id =>
        window.axios.delete(`/api/clients/${id}`)
      )
    )

    await fetchClients()
    clearSelection()
    alert(`Successfully deleted ${selectedClients.value.length} client(s)`)
  } catch (error) {
    console.error('Error deleting clients:', error)
    alert('Failed to delete some clients. Please try again.')
  }
}

const changePage = (page: number) => {
  if (page < 1 || (pagination.value && page > pagination.value.last_page)) return
  filters.value.page = page
  fetchClients()
}

const handlePerPageChange = () => {
  filters.value.page = 1  // Reset to first page
  fetchClients()
}

const visiblePages = computed(() => {
  if (!pagination.value) return []

  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: number[] = []

  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push(last)
    } else if (current >= last - 3) {
      pages.push(1)
      for (let i = last - 4; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push(last)
    }
  }

  return pages
})

const viewClientDashboard = (clientId: number) => {
  router.push({ name: 'client-dashboard', params: { id: clientId } })
}

const editClient = (client: Client) => {
  editingClient.value = client
  showFormModal.value = true
}

const handleCreateManually = () => {
  editingClient.value = null
  showFormModal.value = true
}

const handleFormSaved = (client: Client) => {
  // Refresh the clients list
  fetchClients()
}

const confirmDelete = (client: Client) => {
  deletingClient.value = client
  showDeleteDialog.value = true
}

const handleDelete = async () => {
  if (!deletingClient.value) return

  deleteLoading.value = true
  try {
    await window.axios.delete(`/api/clients/${deletingClient.value.id}`)
    showDeleteDialog.value = false
    deletingClient.value = null
    // Refresh the clients list
    await fetchClients()
  } catch (error: any) {
    console.error('Error deleting client:', error)
    alert(error.response?.data?.message || 'An error occurred while deleting the client')
  } finally {
    deleteLoading.value = false
  }
}

const fetchAccounts = async () => {
  try {
    const response = await window.axios.get('/api/ad-accounts')
    accounts.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching accounts:', error)
  }
}

const fetchIndustries = async () => {
  try {
    const response = await window.axios.get('/api/industries')
    industries.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching industries:', error)
  }
}

const openWizard = async () => {
  await fetchAccounts()
  showWizard.value = true
}

const handleClientCreated = (client: Client) => {
  // Refresh the clients list
  fetchClients()
  // Optionally navigate to the new client's dashboard
  router.push({ name: 'client-dashboard', params: { id: client.id } })
}

// Click outside handler for column selector
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (showColumnSelector.value && !target.closest('.relative')) {
    showColumnSelector.value = false
  }
}

onMounted(() => {
  fetchOverview()
  fetchClients()
  fetchIndustries()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
