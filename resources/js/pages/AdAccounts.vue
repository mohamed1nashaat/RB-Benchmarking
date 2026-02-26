<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('pages.ad_accounts.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('pages.ad_accounts.description') }}
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          v-if="selectedAccounts.length > 0"
          @click="showBulkModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PencilIcon class="h-4 w-4 mr-2" />
          {{ $t('pages.ad_accounts.bulk_edit', { count: selectedAccounts.length }) }}
        </button>

        <!-- Bulk Approve (Admin Only) -->
        <button
          v-if="isAdmin && selectedAccounts.length > 0"
          @click="openVerificationModal('bulk', 'approved', selectedAccounts)"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          <CheckCircleIcon class="h-4 w-4 mr-2" />
          Approve {{ selectedAccounts.length }}
        </button>

        <!-- Bulk Reject (Admin Only) -->
        <button
          v-if="isAdmin && selectedAccounts.length > 0"
          @click="openVerificationModal('bulk', 'declined', selectedAccounts)"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          <XCircleIcon class="h-4 w-4 mr-2" />
          Reject {{ selectedAccounts.length }}
        </button>

        <!-- Export Dropdown -->
        <div class="relative" ref="exportDropdownRef">
          <button
            @click="showExportDropdown = !showExportDropdown"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
            Export
          </button>
          <div
            v-if="showExportDropdown"
            class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
          >
            <div class="py-1">
              <button
                @click="exportData('csv')"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Export as CSV
              </button>
              <button
                @click="exportData('xlsx')"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Export as Excel
              </button>
            </div>
          </div>
        </div>

        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']"
            aria-hidden="true"
          />
          {{ $t('buttons.refresh') }}
        </button>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div class="px-4 py-5 sm:px-6 space-y-4">
        <!-- Search Bar -->
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Accounts</label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              placeholder="Search by account name or ID..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
          </div>
        </div>

        <!-- Filter Row -->
        <div class="flex flex-wrap gap-4">
          <div class="flex-1 min-w-[150px]">
            <label for="platform-filter" class="block text-sm font-medium text-gray-700">{{ $t('labels.platform') }}</label>
            <select
              id="platform-filter"
              v-model="filters.platform"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">{{ $t('filters.all_platforms') }}</option>
              <option v-for="platform in availablePlatforms" :key="platform" :value="platform">
                {{ platform.charAt(0).toUpperCase() + platform.slice(1) }}
              </option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="status-filter" class="block text-sm font-medium text-gray-700">{{ $t('labels.status') }}</label>
            <select
              id="status-filter"
              v-model="filters.status"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">{{ $t('filters.all_statuses') }}</option>
              <option value="active">{{ $t('status.active') }}</option>
              <option value="inactive">{{ $t('status.inactive') }}</option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="industry-filter" class="block text-sm font-medium text-gray-700">{{ $t('labels.industry') }}</label>
            <select
              id="industry-filter"
              v-model="filters.industry"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">{{ $t('filters.all_industries') }}</option>
              <option v-for="[value, label] in availableIndustries" :key="value" :value="value">
                {{ label }}
              </option>
              <option value="unset">{{ $t('filters.not_set') }}</option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="country-filter" class="block text-sm font-medium text-gray-700">{{ $t('labels.country') }}</label>
            <select
              id="country-filter"
              v-model="filters.country"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">{{ $t('filters.all_countries') }}</option>
              <option v-for="c in availableCountries" :key="c.code" :value="c.code">
                {{ c.name }}
              </option>
              <option value="unset">{{ $t('filters.not_set') }}</option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="verification-filter" class="block text-sm font-medium text-gray-700">Verification Status</label>
            <select
              id="verification-filter"
              v-model="filters.verificationStatus"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="declined">Declined</option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="year-filter" class="block text-sm font-medium text-gray-700">Year</label>
            <select
              id="year-filter"
              v-model="filters.year"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">All Years</option>
              <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>

          <div class="flex-1 min-w-[150px]">
            <label for="client-filter" class="block text-sm font-medium text-gray-700">Client</label>
            <select
              id="client-filter"
              v-model="filters.clientId"
              @change="applyFilters"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
            >
              <option value="">All Clients</option>
              <option value="unassigned">Unassigned</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">
                {{ client.name }}
              </option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              v-if="hasActiveFilters"
              @click="clearAllFilters"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <BuildingOfficeIcon class="h-7 w-7 text-blue-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('pages.ad_accounts.total_accounts') }}</dt>
                <dd class="mt-1 text-xl sm:text-2xl xl:text-3xl font-semibold text-gray-900 truncate">{{ filteredAccounts.length }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                <CheckCircleIcon class="h-7 w-7 text-green-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('pages.ad_accounts.active_accounts') }}</dt>
                <dd class="mt-1 text-xl sm:text-2xl xl:text-3xl font-semibold text-gray-900 truncate">{{ activeAccountsCount }}</dd>
              </dl>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-500">
            {{ ((activeAccountsCount / filteredAccounts.length) * 100 || 0).toFixed(0) }}% of total
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-lg bg-orange-100 flex items-center justify-center">
                <BriefcaseIcon class="h-7 w-7 text-orange-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">With Industry</dt>
                <dd class="mt-1 text-xl sm:text-2xl xl:text-3xl font-semibold text-gray-900 truncate">{{ accountsWithIndustryCount }}</dd>
              </dl>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-500">
            {{ ((accountsWithIndustryCount / filteredAccounts.length) * 100 || 0).toFixed(0) }}% categorized
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <RocketLaunchIcon class="h-7 w-7 text-purple-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('pages.ad_accounts.total_campaigns') }}</dt>
                <dd class="mt-1 text-xl sm:text-2xl xl:text-3xl font-semibold text-gray-900 truncate">{{ formatNumber(totalCampaignsCount) }}</dd>
              </dl>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-500">
            {{ (totalCampaignsCount / filteredAccounts.length || 0).toFixed(1) }} avg per account
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <CurrencyDollarIcon class="h-7 w-7 text-emerald-600" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Spend (All-Time)</dt>
                <dd class="mt-1 text-lg sm:text-xl xl:text-2xl font-semibold text-gray-900 break-words">{{ formatCurrency(totalSpend, 'SAR') }}</dd>
              </dl>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-500">
            Across all {{ filteredAccounts.length }} accounts
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <!-- Ad Accounts Table -->
    <div v-if="!loading" class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Ad Accounts</h3>
          <div class="flex items-center space-x-4">
            <ColumnToggle
              :columns="tableColumns"
              :storage-key="COLUMNS_STORAGE_KEY"
              v-model="visibleColumns"
            />
            <div class="flex items-center space-x-2">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              >
              <span class="text-sm text-gray-500">Select All</span>
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-auto max-h-[600px] relative border-t border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 sticky top-0 z-20 shadow-sm">
            <tr>
              <th v-if="visibleColumns.select" scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50 sticky left-0 z-30">
                Select
              </th>
              <th v-if="visibleColumns.account_name" scope="col" @click="sortBy('account_name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50 sticky left-12 z-30 min-w-[200px] border-r border-gray-200">
                <div class="flex items-center space-x-1">
                  <span>Account</span>
                  <ChevronUpDownIcon v-if="sortField !== 'account_name'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.client" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Client
              </th>
              <th v-if="visibleColumns.platform" scope="col" @click="sortBy('platform')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center space-x-1">
                  <span>Platform</span>
                  <ChevronUpDownIcon v-if="sortField !== 'platform'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.status" scope="col" @click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center space-x-1">
                  <span>Status</span>
                  <ChevronUpDownIcon v-if="sortField !== 'status'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.verification_status" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Verification
              </th>
              <th v-if="visibleColumns.industry" scope="col" @click="sortBy('industry')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center space-x-1">
                  <span>Industry</span>
                  <ChevronUpDownIcon v-if="sortField !== 'industry'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.country" scope="col" @click="sortBy('country')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center space-x-1">
                  <span>Country</span>
                  <ChevronUpDownIcon v-if="sortField !== 'country'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.category" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <th v-if="visibleColumns.currency" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Currency
              </th>
              <th v-if="visibleColumns.campaigns_count" scope="col" @click="sortBy('campaigns_count')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center space-x-1">
                  <span>Campaigns</span>
                  <ChevronUpDownIcon v-if="sortField !== 'campaigns_count'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
              <th v-if="visibleColumns.total_spend" scope="col" @click="sortBy('total_spend')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                <div class="flex items-center justify-end space-x-1">
                  <span>Total Spend</span>
                  <ChevronUpDownIcon v-if="sortField !== 'total_spend'" class="h-4 w-4 text-gray-400" />
                  <ChevronUpIcon v-else-if="sortDirection === 'asc'" class="h-4 w-4 text-gray-700" />
                  <ChevronDownIcon v-else class="h-4 w-4 text-gray-700" />
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="account in paginatedAccounts" :key="account.id" class="hover:bg-gray-50 transition-colors duration-150 group">
              <td v-if="visibleColumns.select" class="px-4 py-3 whitespace-nowrap bg-white group-hover:bg-gray-50 sticky left-0 z-10">
                <input
                  type="checkbox"
                  :value="account.id"
                  v-model="selectedAccounts"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                >
              </td>

              <td v-if="visibleColumns.account_name" class="px-4 py-3 whitespace-nowrap bg-white group-hover:bg-gray-50 sticky left-12 z-10 min-w-[200px] border-r border-gray-100">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                      <BuildingOfficeIcon class="h-4 w-4 text-gray-500" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      <router-link
                        :to="{ name: 'ad-account-detail', params: { id: account.id } }"
                        class="text-primary-600 hover:text-primary-900 hover:underline"
                      >
                        {{ account.account_name }}
                      </router-link>
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ account.external_account_id }}
                    </div>
                  </div>
                </div>
              </td>

              <td v-if="visibleColumns.client" class="px-4 py-3 whitespace-nowrap">
                <ClientCombobox
                  :model-value="account.tenant ? { id: account.tenant.id, name: account.tenant.name } : null"
                  :clients="clients"
                  :disabled="updating === account.id"
                  placeholder="Select client..."
                  class="w-48"
                  @change="(client) => updateAccountClient(account.id, client?.id || null)"
                  @add-client="openAddClientModal(account.id)"
                />
              </td>

              <td v-if="visibleColumns.platform" class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                      :class="getPlatformBadgeClass(account.platform)">
                  {{ account.platform }}
                </span>
              </td>

              <td v-if="visibleColumns.status" class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="account.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ account.status }}
                </span>
              </td>

              <td v-if="visibleColumns.verification_status" class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="{
                    'bg-yellow-100 text-yellow-800': !account.data_verification_status || account.data_verification_status === 'pending',
                    'bg-green-100 text-green-800': account.data_verification_status === 'approved',
                    'bg-red-100 text-red-800': account.data_verification_status === 'declined'
                  }"
                >
                  {{ account.data_verification_status || 'pending' }}
                </span>
              </td>

              <td v-if="visibleColumns.industry" class="px-4 py-3 whitespace-nowrap">
                <select
                  :value="account.industry"
                  @change="updateAccountIndustry(account.id, $event.target.value)"
                  class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1"
                  :disabled="updating === account.id"
                >
                  <option value="">Select</option>
                  <option v-for="(label, value) in industries" :key="value" :value="value">
                    {{ label }}
                  </option>
                </select>
              </td>

              <td v-if="visibleColumns.country" class="px-4 py-3 whitespace-nowrap">
                <select
                  :value="account.country"
                  @change="updateAccountCountry(account.id, $event.target.value)"
                  class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1"
                  :disabled="updating === account.id"
                >
                  <option value="">Select</option>
                  <option v-for="c in countries" :key="c.code" :value="c.code">
                    {{ c.name }}
                  </option>
                </select>
              </td>

              <td v-if="visibleColumns.category" class="px-4 py-3 whitespace-nowrap">
                <select
                  v-if="account.industry && account.available_categories && account.available_categories.length > 0"
                  :value="account.category"
                  @change="updateAccountCategory(account.id, $event.target.value)"
                  class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1"
                  :disabled="updating === account.id"
                >
                  <option value="">Select</option>
                  <option v-for="category in account.available_categories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </select>
                <span v-else class="text-xs text-gray-400">
                  {{ account.industry ? '-' : 'Set industry' }}
                </span>
              </td>

              <td v-if="visibleColumns.currency" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                {{ account.currency }}
              </td>

              <td v-if="visibleColumns.campaigns_count" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                {{ account.campaigns_count }}
              </td>

              <td v-if="visibleColumns.total_spend" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                <span v-if="account.total_spend !== undefined && account.total_spend !== null">
                  {{ formatCurrency(account.total_spend, 'SAR') }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>

            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-white px-4 py-3 sm:px-6 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex items-center justify-between sm:hidden">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <div class="text-sm text-gray-700">
              Page {{ currentPage }} of {{ totalPages }}
            </div>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="relative ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ startRecord }}</span>
                to
                <span class="font-medium">{{ endRecord }}</span>
                of
                <span class="font-medium">{{ totalRecords }}</span>
                results
              </p>
            </div>
            <div class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <label for="per-page" class="text-sm text-gray-700">Per page:</label>
                <select
                  id="per-page"
                  v-model="perPage"
                  @change="currentPage = 1"
                  class="border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500"
                >
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="previousPage"
                  :disabled="currentPage === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Previous</span>
                  <ChevronLeftIcon class="h-5 w-5" />
                </button>

                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === currentPage
                      ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                      : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>

                <button
                  @click="nextPage"
                  :disabled="currentPage === totalPages"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Next</span>
                  <ChevronRightIcon class="h-5 w-5" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Update Modal -->
    <div v-if="showBulkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Bulk Update {{ selectedAccounts.length }} Accounts
          </h3>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Industry</label>
              <select
                v-model="bulkUpdateData.industry"
                @change="onBulkIndustryChange"
                class="mt-1 block w-full border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md"
              >
                <option value="">Keep Current</option>
                <option v-for="(label, value) in industries" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Country</label>
              <select
                v-model="bulkUpdateData.country"
                class="mt-1 block w-full border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md"
              >
                <option value="">Keep Current</option>
                <option v-for="c in countries" :key="c.code" :value="c.code">
                  {{ c.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Category</label>
              <select
                v-model="bulkUpdateData.category"
                class="mt-1 block w-full border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md"
                :disabled="!bulkUpdateData.industry || bulkAvailableCategories.length === 0"
              >
                <option value="">Keep Current</option>
                <option v-for="category in bulkAvailableCategories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <select
                v-model="bulkUpdateData.status"
                class="mt-1 block w-full border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md"
              >
                <option value="">Keep Current</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="change-client"
                  v-model="bulkChangeClient"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <label for="change-client" class="text-sm text-gray-600">Change client assignment</label>
              </div>
              <div v-if="bulkChangeClient" class="mt-2">
                <ClientCombobox
                  v-model="bulkSelectedClient"
                  :clients="clients"
                  placeholder="Select client or leave empty to unassign..."
                  :show-add-option="false"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 mt-6">
            <button
              @click="closeBulkModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              @click="performBulkUpdate"
              :disabled="bulkUpdating"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
            >
              <span v-if="bulkUpdating">Updating...</span>
              <span v-else>Update Accounts</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Verification Modal -->
    <div v-if="showVerificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Header -->
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ verificationAction === 'approved' ? 'Approve' : 'Reject' }}
            {{ verificationMode === 'single' ? 'Account' : `${accountsToVerify.length} Accounts` }}
          </h3>

          <!-- Total Spend Display -->
          <div class="mb-4 p-4 bg-gray-50 rounded-md">
            <div class="text-sm text-gray-600 mb-1">Total Spend</div>
            <div class="text-2xl font-bold text-gray-900">
              {{ formatCurrency(calculateTotalSpend(accountsToVerify), 'SAR') }}
            </div>
          </div>

          <!-- Notes Field -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notes (Optional)
            </label>
            <textarea
              v-model="verificationNotes"
              rows="3"
              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              placeholder="Add notes about this verification..."
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end space-x-3">
            <button
              @click="closeVerificationModal"
              :disabled="verifying"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="performVerification"
              :disabled="verifying"
              :class="[
                'px-4 py-2 text-sm font-medium text-white rounded-md disabled:opacity-50',
                verificationAction === 'approved'
                  ? 'bg-green-600 hover:bg-green-700'
                  : 'bg-red-600 hover:bg-red-700'
              ]"
            >
              <span v-if="verifying">Processing...</span>
              <span v-else>{{ verificationAction === 'approved' ? 'Approve' : 'Reject' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Client Form Modal -->
    <ClientFormModal
      v-if="showClientModal"
      :open="showClientModal"
      :client="editingClient"
      @close="closeClientModal"
      @saved="handleClientCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  ArrowPathIcon,
  ArrowDownTrayIcon,
  BuildingOfficeIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  XCircleIcon,
  CurrencyDollarIcon,
  PencilIcon,
  RocketLaunchIcon,
  MagnifyingGlassIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronUpDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { exportToCSV, exportToExcel } from '@/utils/exportUtils'
import { countries, getCountryName } from '@/utils/countries'
import ColumnToggle from '@/components/ColumnToggle.vue'
import ClientFormModal from '@/components/ClientFormModal.vue'
import ClientCombobox from '@/components/ClientCombobox.vue'
import { useAuthStore } from '@/stores/auth'

interface Campaign {
  id: number
  name: string
  objective?: string
}

interface Client {
  id: number
  name: string
  ad_accounts_count?: number
}

interface AdAccount {
  id: number
  account_name: string
  external_account_id: string
  platform: string
  status: string
  industry: string | null
  country: string | null
  category: string | null
  available_categories?: string[]
  currency: string
  tenant_id: number | null
  tenant: Client | null
  campaigns_count: number
  campaigns?: Campaign[]
  created_at: string
  updated_at: string
  total_spend?: number
  data_verification_status?: 'pending' | 'approved' | 'declined'
  verification_notes?: string | null
  verified_at?: string | null
}

const loading = ref(false)
const updating = ref<number | null>(null)
const bulkUpdating = ref(false)
const showBulkModal = ref(false)
const showExportDropdown = ref(false)
const exportDropdownRef = ref<HTMLElement | null>(null)

// Verification modal state
const showVerificationModal = ref(false)
const verificationMode = ref<'single' | 'bulk'>('single')
const verificationAction = ref<'approved' | 'declined'>('approved')
const verificationNotes = ref('')
const accountsToVerify = ref<number[]>([])
const verifying = ref(false)

const accounts = ref<AdAccount[]>([])
const selectedAccounts = ref<number[]>([])
const industries = ref<Record<string, string>>({})
const clients = ref<Client[]>([])

// Client modal state
const showClientModal = ref(false)
const pendingClientAccountId = ref<number | null>(null)
const editingClient = ref<Client | null>(null)

// Auth store for admin check
const authStore = useAuthStore()
const isAdmin = computed(() => {
  const adminStatus = authStore.isAdmin
  return adminStatus
})

// localStorage key for filter persistence
const FILTERS_STORAGE_KEY = 'ad_accounts_filters'

// Load saved filters from localStorage
const loadSavedFilters = () => {
  try {
    const saved = localStorage.getItem(FILTERS_STORAGE_KEY)
    if (saved) {
      return JSON.parse(saved)
    }
  } catch (e) {
    console.error('Error loading saved filters:', e)
  }
  return null
}

const savedFilters = loadSavedFilters()

const filters = ref({
  platform: savedFilters?.platform || '',
  status: savedFilters?.status || '',
  industry: savedFilters?.industry || '',
  country: savedFilters?.country || '',
  verificationStatus: savedFilters?.verificationStatus || '',
  year: savedFilters?.year || '',
  clientId: savedFilters?.clientId || ''
})

// Year filter options
const currentYear = new Date().getFullYear()
const yearOptions = computed(() => {
  const years = []
  for (let i = currentYear; i >= 2020; i--) {
    years.push(i)
  }
  return years
})

// Save filters to localStorage
const saveFilters = () => {
  try {
    localStorage.setItem(FILTERS_STORAGE_KEY, JSON.stringify({
      platform: filters.value.platform,
      status: filters.value.status,
      industry: filters.value.industry,
      country: filters.value.country,
      verificationStatus: filters.value.verificationStatus,
      year: filters.value.year,
      clientId: filters.value.clientId,
      searchQuery: searchQuery.value
    }))
  } catch (e) {
    console.error('Error saving filters:', e)
  }
}

const bulkUpdateData = ref({
  industry: '',
  country: '',
  category: '',
  status: ''
})

const bulkAvailableCategories = ref<string[]>([])
const bulkChangeClient = ref(false)
const bulkSelectedClient = ref<Client | null>(null)

// Column visibility configuration
const COLUMNS_STORAGE_KEY = 'ad_accounts_visible_columns_v2'

const tableColumns = [
  { key: 'select', label: 'Select', defaultVisible: true },
  { key: 'account_name', label: 'Account Name', defaultVisible: true },
  { key: 'client', label: 'Client', defaultVisible: true },
  { key: 'platform', label: 'Platform', defaultVisible: true },
  { key: 'status', label: 'Status', defaultVisible: true },
  { key: 'verification_status', label: 'Verification', defaultVisible: true },
  { key: 'industry', label: 'Industry', defaultVisible: true },
  { key: 'country', label: 'Country', defaultVisible: true },
  { key: 'category', label: 'Category', defaultVisible: true },
  { key: 'currency', label: 'Currency', defaultVisible: false },
  { key: 'campaigns_count', label: 'Campaigns', defaultVisible: true },
  { key: 'total_spend', label: 'Total Spend', defaultVisible: true }
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

// Search, Sort, Pagination
const searchQuery = ref(savedFilters?.searchQuery || '')
const sortField = ref<string>('account_name')
const sortDirection = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const perPage = ref(25)

const filteredAccounts = computed(() => {
  return accounts.value.filter(account => {
    // Platform filter
    if (filters.value.platform && account.platform !== filters.value.platform) {
      return false
    }
    // Status filter
    if (filters.value.status && account.status !== filters.value.status) {
      return false
    }
    // Industry filter
    if (filters.value.industry) {
      if (filters.value.industry === 'unset') {
        if (account.industry) return false
      } else {
        if (account.industry !== filters.value.industry) return false
      }
    }
    // Country filter
    if (filters.value.country) {
      if (filters.value.country === 'unset') {
        if (account.country) return false
      } else {
        if (account.country !== filters.value.country) return false
      }
    }
    // Verification status filter
    if (filters.value.verificationStatus) {
      const status = account.data_verification_status || 'pending'
      if (status !== filters.value.verificationStatus) {
        return false
      }
    }
    // Client filter
    if (filters.value.clientId) {
      if (filters.value.clientId === 'unassigned') {
        if (account.tenant_id) return false
      } else {
        if (account.tenant_id !== parseInt(filters.value.clientId as string)) return false
      }
    }
    // Search filter
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      if (!account.account_name.toLowerCase().includes(query) &&
          !account.external_account_id.toLowerCase().includes(query)) {
        return false
      }
    }
    return true
  })
})

const sortedAccounts = computed(() => {
  const sorted = [...filteredAccounts.value]

  sorted.sort((a, b) => {
    let aVal = a[sortField.value as keyof AdAccount]
    let bVal = b[sortField.value as keyof AdAccount]

    // Handle null/undefined values
    if (aVal === null || aVal === undefined) aVal = ''
    if (bVal === null || bVal === undefined) bVal = ''

    // Convert to string for comparison
    const aStr = String(aVal).toLowerCase()
    const bStr = String(bVal).toLowerCase()

    if (aStr < bStr) return sortDirection.value === 'asc' ? -1 : 1
    if (aStr > bStr) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })

  return sorted
})

const paginatedAccounts = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return sortedAccounts.value.slice(start, end)
})

const totalRecords = computed(() => sortedAccounts.value.length)
const totalPages = computed(() => Math.ceil(totalRecords.value / perPage.value) || 1)
const startRecord = computed(() => totalRecords.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1)
const endRecord = computed(() => Math.min(currentPage.value * perPage.value, totalRecords.value))

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const hasActiveFilters = computed(() => {
  return filters.value.platform !== '' ||
         filters.value.status !== '' ||
         filters.value.industry !== '' ||
         filters.value.country !== '' ||
         filters.value.verificationStatus !== '' ||
         filters.value.year !== '' ||
         filters.value.clientId !== '' ||
         searchQuery.value !== ''
})

const allSelected = computed(() => {
  return filteredAccounts.value.length > 0 && selectedAccounts.value.length === filteredAccounts.value.length
})

const activeAccountsCount = computed(() => {
  return filteredAccounts.value.filter(a => a.status === 'active').length
})

const accountsWithIndustryCount = computed(() => {
  return filteredAccounts.value.filter(a => a.industry).length
})

const totalCampaignsCount = computed(() => {
  return filteredAccounts.value.reduce((sum, account) => sum + account.campaigns_count, 0)
})

const totalSpend = computed(() => {
  return filteredAccounts.value.reduce((sum, account) => {
    return sum + (account.total_spend || 0)
  }, 0)
})

// Computed properties for filters - only show options with actual data
const availablePlatforms = computed(() => {
  const platforms = new Set(accounts.value.map(a => a.platform).filter(Boolean))
  return Array.from(platforms)
})

const availableIndustries = computed(() => {
  const industrySet = new Set(accounts.value.map(a => a.industry).filter(Boolean))
  return Object.entries(industries.value).filter(([key]) => industrySet.has(key))
})

const availableCountries = computed(() => {
  const countrySet = new Set(accounts.value.map(a => a.country).filter(Boolean))
  return countries.filter(c => countrySet.has(c.code))
})

const fetchAccounts = async () => {
  loading.value = true
  try {
    
    // Check authentication first
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
    const tenantId = localStorage.getItem('current_tenant_id') || sessionStorage.getItem('current_tenant_id')
    
    const params = new URLSearchParams()
    if (filters.value.platform) params.append('platform', filters.value.platform)
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.year) {
      params.append('from', `${filters.value.year}-01-01`)
      params.append('to', `${filters.value.year}-12-31`)
    }

    const url = `/api/ad-accounts?${params.toString()}`
    
    const response = await window.axios.get(url)
    
    accounts.value = response.data.data || []
    
    if (accounts.value.length > 0) {
    } else {
    }
    
  } catch (error) {
    console.error('Error fetching ad accounts:', error)
    console.error('Error response:', error.response)
    console.error('Error status:', error.response?.status)
    console.error('Error data:', error.response?.data)
    
    if (error.response?.status === 401) {
      alert('Authentication required. Please log in again.')
    } else if (error.response?.status === 403) {
      alert('Access denied. Please check your permissions.')
    } else {
      alert(`Failed to load ad accounts: ${error.response?.data?.message || error.message}`)
    }
    
    // Set empty array on error
    accounts.value = []
  } finally {
    loading.value = false
  }
}

const fetchIndustries = async () => {
  try {

    // First try the main industries API which we know works
    let response
    try {
      response = await window.axios.get('/api/industries')

      // Convert from the main API format to the format expected by ad accounts
      const industriesData = response.data.data || []
      const industriesMap = {}
      industriesData.forEach(industry => {
        industriesMap[industry.name] = industry.display_name
      })
      industries.value = industriesMap

    } catch (mainError) {

      // Fallback to the original endpoint
      response = await window.axios.get('/api/ad-accounts/industries')
      industries.value = response.data.industries || {}
    }

  } catch (error) {
    console.error('Error fetching industries:', error)
    console.error('Error response:', error.response)
    console.error('Error status:', error.response?.status)
    console.error('Error data:', error.response?.data)

    // Don't let industries failure break the page - provide fallback
    industries.value = {
      'automotive': 'Automotive',
      'technology': 'Technology',
      'ecommerce': 'E-commerce',
      'finance': 'Finance',
      'healthcare': 'Healthcare',
      'retail': 'Retail',
      'other': 'Other'
    }
  }
}

const fetchClients = async () => {
  try {
    const response = await window.axios.get('/api/clients', {
      params: { per_page: 500 } // Get all clients for dropdown
    })
    clients.value = (response.data.data || []).map((client: any) => ({
      id: client.id,
      name: client.name,
      ad_accounts_count: client.ad_accounts_count || 0
    }))
  } catch (error) {
    console.error('Error fetching clients:', error)
    clients.value = []
  }
}

const updateAccountClient = async (accountId: number, tenantId: number | null) => {
  updating.value = accountId
  try {
    const response = await window.axios.put(`/api/ad-accounts/${accountId}`, { tenant_id: tenantId })

    // Update local data
    const account = accounts.value.find(a => a.id === accountId)
    if (account && response.data.data) {
      account.tenant_id = response.data.data.tenant_id
      account.tenant = response.data.data.tenant
    }

    // Refresh clients to update account counts
    await fetchClients()
  } catch (error) {
    console.error('Error updating account client:', error)
    alert('Failed to update client. Please try again.')
  } finally {
    updating.value = null
  }
}

const openAddClientModal = (accountId: number | null = null) => {
  // Blur any focused element immediately
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  // Delay to allow Combobox to fully close before Dialog opens
  setTimeout(() => {
    // Blur again in case focus returned
    if (document.activeElement instanceof HTMLElement && document.activeElement.tagName !== 'BODY') {
      document.activeElement.blur()
    }
    pendingClientAccountId.value = accountId
    editingClient.value = null
    showClientModal.value = true
  }, 150)
}

const handleClientCreated = async (newClient: any) => {
  // Refresh clients list
  await fetchClients()

  // If there was a pending account, assign the new client to it
  if (pendingClientAccountId.value && newClient?.id) {
    await updateAccountClient(pendingClientAccountId.value, newClient.id)
    pendingClientAccountId.value = null
  }

  showClientModal.value = false
}

const closeClientModal = () => {
  showClientModal.value = false
  pendingClientAccountId.value = null
  editingClient.value = null
}

const updateAccountIndustry = async (accountId: number, industry: string) => {
  updating.value = accountId
  try {
    const response = await window.axios.put(`/api/ad-accounts/${accountId}`, { industry })

    // Update local data with new industry and available categories
    const account = accounts.value.find(a => a.id === accountId)
    if (account && response.data.data) {
      account.industry = industry
      account.category = null // Reset category when industry changes

      // Fetch updated account data to get available categories
      const updatedResponse = await window.axios.get(`/api/ad-accounts/${accountId}`)
      if (updatedResponse.data.data) {
        account.available_categories = updatedResponse.data.data.available_categories
      }
    }
  } catch (error) {
    console.error('Error updating account:', error)
  } finally {
    updating.value = null
  }
}

const updateAccountCountry = async (accountId: number, country: string) => {
  updating.value = accountId
  try {
    await window.axios.put(`/api/ad-accounts/${accountId}`, { country })

    const account = accounts.value.find(a => a.id === accountId)
    if (account) {
      account.country = country || null
    }
  } catch (error) {
    console.error('Error updating account country:', error)
  } finally {
    updating.value = null
  }
}

const updateAccountCategory = async (accountId: number, category: string) => {
  updating.value = accountId
  try {
    await window.axios.put(`/api/ad-accounts/${accountId}`, { category })

    // Update local data
    const account = accounts.value.find(a => a.id === accountId)
    if (account) {
      account.category = category
    }
  } catch (error) {
    console.error('Error updating account category:', error)
  } finally {
    updating.value = null
  }
}

const verifyAccountData = async (
  accountId: number,
  status: 'approved' | 'declined',
  notes: string = ''
) => {
  updating.value = accountId
  try {
    const response = await window.axios.post(
      `/api/ad-accounts/${accountId}/verify`,
      { status, notes }
    )

    // Update local data
    const account = accounts.value.find(a => a.id === accountId)
    if (account && response.data.data) {
      account.data_verification_status = response.data.data.data_verification_status
      account.verified_at = response.data.data.verified_at
      account.verification_notes = notes
    }
  } catch (error) {
    console.error('Error verifying account data:', error)
    throw error
  } finally {
    updating.value = null
  }
}

const onBulkIndustryChange = async () => {
  // Fetch categories for the selected industry
  if (bulkUpdateData.value.industry) {
    try {
      // Get first account to fetch categories for this industry
      const response = await window.axios.get('/api/ad-accounts')
      const accountWithIndustry = response.data.data?.find((acc: AdAccount) =>
        acc.industry === bulkUpdateData.value.industry
      )

      if (accountWithIndustry && accountWithIndustry.available_categories) {
        bulkAvailableCategories.value = accountWithIndustry.available_categories
      } else {
        bulkAvailableCategories.value = []
      }
    } catch (error) {
      console.error('Error fetching categories for bulk update:', error)
      bulkAvailableCategories.value = []
    }
  } else {
    bulkAvailableCategories.value = []
  }

  // Reset category selection
  bulkUpdateData.value.category = ''
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedAccounts.value = []
  } else {
    selectedAccounts.value = filteredAccounts.value.map(a => a.id)
  }
}

const performBulkUpdate = async () => {
  if (selectedAccounts.value.length === 0) return

  bulkUpdating.value = true
  try {
    const updateData: any = { account_ids: selectedAccounts.value }

    if (bulkUpdateData.value.industry) {
      updateData.industry = bulkUpdateData.value.industry
    }

    if (bulkUpdateData.value.country) {
      updateData.country = bulkUpdateData.value.country
    }

    if (bulkUpdateData.value.category) {
      updateData.category = bulkUpdateData.value.category
    }

    if (bulkUpdateData.value.status) {
      updateData.status = bulkUpdateData.value.status
    }

    if (bulkChangeClient.value) {
      updateData.tenant_id = bulkSelectedClient.value?.id || null
    }

    await window.axios.put('/api/ad-accounts/bulk-update', updateData)

    // Refresh accounts and clients data
    await Promise.all([fetchAccounts(), fetchClients()])

    // Close modal and reset
    closeBulkModal()
  } catch (error) {
    console.error('Error bulk updating accounts:', error)
  } finally {
    bulkUpdating.value = false
  }
}

const closeBulkModal = () => {
  showBulkModal.value = false
  selectedAccounts.value = []
  bulkUpdateData.value = { industry: '', country: '', category: '', status: '' }
  bulkAvailableCategories.value = []
  bulkChangeClient.value = false
  bulkSelectedClient.value = null
}

const editAccount = (account: AdAccount) => {
  // Placeholder for future edit functionality
}

const applyFilters = () => {
  selectedAccounts.value = []
  fetchAccounts()
}

const refreshData = () => {
  fetchAccounts()
}

// Export functionality
const exportData = (format: 'csv' | 'xlsx') => {
  showExportDropdown.value = false

  // Prepare data for export
  const exportRows = filteredAccounts.value.map(account => ({
    'Account Name': account.account_name,
    'External ID': account.external_account_id,
    'Platform': account.platform,
    'Status': account.status,
    'Verification Status': account.data_verification_status || 'pending',
    'Industry': account.industry || '',
    'Country': account.country ? getCountryName(account.country) : '',
    'Category': account.category || '',
    'Currency': account.currency || '',
    'Campaigns': account.campaigns_count,
    'Total Spend (SAR)': account.total_spend?.toFixed(2) || '0.00'
  }))

  const filename = `ad-accounts-export-${new Date().toISOString().split('T')[0]}`

  if (format === 'csv') {
    const headers = ['Account Name', 'External ID', 'Platform', 'Status', 'Verification Status', 'Industry', 'Country', 'Category', 'Currency', 'Campaigns', 'Total Spend (SAR)']
    exportToCSV(exportRows, `${filename}.csv`, headers)
  } else {
    exportToExcel([{
      title: 'Ad Accounts',
      headers: ['Account Name', 'External ID', 'Platform', 'Status', 'Verification Status', 'Industry', 'Country', 'Category', 'Currency', 'Campaigns', 'Total Spend (SAR)'],
      data: exportRows
    }], `${filename}.xlsx`)
  }
}

const getPlatformBadgeClass = (platform: string) => {
  const classes = {
    facebook: 'bg-blue-100 text-blue-800',
    google: 'bg-green-100 text-green-800',
    tiktok: 'bg-pink-100 text-pink-800',
    linkedin: 'bg-indigo-100 text-indigo-800',
    snapchat: 'bg-yellow-100 text-yellow-800'
  }
  return classes[platform as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

// Sorting
const sortBy = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

// Pagination
const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const goToPage = (page: number) => {
  currentPage.value = page
}

// Filters
const clearAllFilters = () => {
  filters.value.platform = ''
  filters.value.status = ''
  filters.value.industry = ''
  filters.value.country = ''
  filters.value.verificationStatus = ''
  filters.value.year = ''
  filters.value.clientId = ''
  searchQuery.value = ''
  currentPage.value = 1
  fetchAccounts()
}

// Formatting
const formatNumber = (num: number) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const formatCurrency = (amount: number, currency: string = 'SAR') => {
  const currencySymbols: Record<string, string> = {
    'SAR': 'SR',
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'AED': 'AED'
  }

  const symbol = currencySymbols[currency] || currency
  const formatted = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)

  return `${symbol} ${formatted}`
}

// Verification helper functions
// Open verification modal
const openVerificationModal = (
  mode: 'single' | 'bulk',
  action: 'approved' | 'declined',
  accountIds: number[]
) => {
  verificationMode.value = mode
  verificationAction.value = action
  accountsToVerify.value = accountIds
  verificationNotes.value = ''
  showVerificationModal.value = true
}

// Close verification modal
const closeVerificationModal = () => {
  showVerificationModal.value = false
  verificationNotes.value = ''
  accountsToVerify.value = []
}

// Calculate total spend for selected accounts
const calculateTotalSpend = (accountIds: number[]) => {
  return accounts.value
    .filter(acc => accountIds.includes(acc.id))
    .reduce((sum, acc) => sum + (acc.total_spend || 0), 0)
}

// Perform verification (single or bulk)
const performVerification = async () => {
  if (accountsToVerify.value.length === 0) return

  verifying.value = true
  try {
    // Process each account
    const promises = accountsToVerify.value.map(accountId =>
      verifyAccountData(accountId, verificationAction.value, verificationNotes.value)
    )

    await Promise.all(promises)

    // Refresh accounts list
    await fetchAccounts()

    // Close modal and clear selection
    closeVerificationModal()
    selectedAccounts.value = []

    // Success notification
    alert(`Successfully ${verificationAction.value} ${accountsToVerify.value.length} account(s)`)

  } catch (error) {
    console.error('Error performing verification:', error)
    alert('Failed to update verification status. Please try again.')
  } finally {
    verifying.value = false
  }
}

// Removed objective detection for now to simplify debugging

// Close export dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (exportDropdownRef.value && !exportDropdownRef.value.contains(event.target as Node)) {
    showExportDropdown.value = false
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)

  // Load accounts first (priority)
  try {
    await fetchAccounts()
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }

  // Load industries and clients in parallel
  await Promise.all([
    fetchIndustries().catch(error => {
      console.error('Failed to load industries, using fallback:', error)
      if (Object.keys(industries.value).length === 0) {
        industries.value = {
          'automotive': 'Automotive',
          'technology': 'Technology',
          'ecommerce': 'E-commerce',
          'finance': 'Finance',
          'healthcare': 'Healthcare',
          'retail': 'Retail',
          'other': 'Other'
        }
      }
    }),
    fetchClients().catch(error => {
      console.error('Failed to load clients:', error)
    })
  ])

})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watch for filter changes and save to localStorage
watch([filters, searchQuery], () => {
  saveFilters()
}, { deep: true })
</script>