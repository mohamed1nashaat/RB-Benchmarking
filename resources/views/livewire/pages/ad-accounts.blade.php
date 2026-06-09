<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ __('pages.ad_accounts.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.ad_accounts.description') }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="{{ route('ad-accounts-export-excel', array_filter([
                    'search' => $search,
                    'platform' => $filterPlatform,
                    'status' => $filterStatus,
                    'industry' => $filterIndustry,
                    'country' => $filterCountry,
                    'verification' => $filterVerification,
                    'date_from' => $filterDateFrom ?: null,
                    'date_to' => $filterDateTo ?: null,
                    'client' => $filterClientId,
                ])) }}"
                target="_blank"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4 mr-2" />
                {{ __('Export XLSX') }}
            </a>

            <button wire:click="openCreateClientModal"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-o-plus class="h-4 w-4 mr-2" />
                {{ __('New Client') }}
            </button>

            @if(count($selectedAccounts) > 0)
                <button wire:click="openBulkModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-pencil class="h-4 w-4 mr-2" />
                    {{ __('pages.ad_accounts.bulk_edit', ['count' => count($selectedAccounts)]) }}
                </button>
            @endif

            @if($isAdmin && count($selectedAccounts) > 0)
                <button wire:click="openVerificationModal('approved', {{ json_encode($selectedAccounts) }})"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <x-heroicon-o-check-circle class="h-4 w-4 mr-2" />
                    {{ __('buttons.approve') }} {{ count($selectedAccounts) }}
                </button>
                <button wire:click="openVerificationModal('declined', {{ json_encode($selectedAccounts) }})"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                    <x-heroicon-o-x-circle class="h-4 w-4 mr-2" />
                    {{ __('buttons.reject') }} {{ count($selectedAccounts) }}
                </button>
            @endif
        </div>
    </div>

    {{-- Permission Denied Warning Banner --}}
    @php
        $permissionDeniedCount = $accounts->getCollection()->where('status', 'permission_denied')->count();
    @endphp
    @if($permissionDeniedCount > 0 && empty($selectedAccounts))
        <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">{{ __('Google Ads access denied for :count accounts', ['count' => $permissionDeniedCount]) }}</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        {{ __('These accounts were removed from the linked manager account or access was revoked. Select them and click "Reset to Active" to retry, or remove them if no longer needed.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(count($selectedAccounts) > 0 && collect($accounts->items())->whereIn('id', $selectedAccounts)->where('status', 'permission_denied')->count() > 0)
        <button wire:click="resetPermissionDenied" wire:confirm="{{ __('Reset selected permission-denied accounts to active? Next sync will retry access.') }}"
            class="inline-flex items-center px-4 py-2 border border-amber-300 rounded-md shadow-sm text-sm font-medium text-amber-700 bg-white hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
            <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" />
            {{ __('Reset to Active') }}
        </button>
    @endif

    {{-- Search & Filters --}}
    <div class="bg-white shadow overflow-visible sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 space-y-4">
            {{-- Search Bar --}}
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">{{ __('labels.search') }}</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <input id="search" wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ __('pages.ad_accounts.search_placeholder') }}"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                </div>
            </div>

            {{-- Filter Row --}}
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterPlatform"
                        :label="__('labels.platform')"
                        :placeholder="__('filters.all_platforms')"
                        :options="collect($platforms)->mapWithKeys(fn($p) => [$p => ucfirst($p)])->all()"
                    />
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterStatus"
                        :label="__('labels.status')"
                        :placeholder="__('filters.all_statuses')"
                        :options="['active' => __('status.active'), 'inactive' => __('status.inactive'), 'permission_denied' => __('Access Denied')]"
                    />
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterIndustry"
                        :label="__('labels.industry')"
                        :placeholder="__('filters.all_industries')"
                        :options="collect($industries)->mapWithKeys(fn($ind) => [$ind->name => $ind->display_name])->merge(['unset' => __('filters.not_set')])->all()"
                    />
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterCountry"
                        :label="__('labels.country')"
                        :placeholder="__('filters.all_countries')"
                        :options="array_merge($countryOptions, ['unset' => __('filters.not_set')])"
                    />
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterVerification"
                        :label="__('labels.verification')"
                        :placeholder="__('filters.all_statuses')"
                        :options="['pending' => __('status.pending'), 'approved' => __('status.approved'), 'declined' => __('status.declined')]"
                    />
                </div>

                <div class="flex-1 min-w-[200px]">
                    <x-date-range-picker
                        wire-model-from="filterDateFrom"
                        wire-model-to="filterDateTo"
                        :label="__('labels.date_range')"
                        align="left"
                    />
                </div>

                <div class="flex-1 min-w-[150px]">
                    <x-select2
                        wire-model="filterClientId"
                        :label="__('labels.client')"
                        :placeholder="__('filters.all_clients')"
                        :options="collect(['unassigned' => __('filters.unassigned')])->merge(collect($clients)->mapWithKeys(fn($c) => [$c->id => $c->name]))->all()"
                    />
                </div>

                <div class="flex items-end">
                    @if($this->hasActiveFilters)
                        <button wire:click="clearAllFilters"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            {{ __('filters.clear_filters') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                            <x-heroicon-o-building-office class="h-7 w-7 text-blue-600" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.ad_accounts.total_accounts') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalAccountsCount }}</dd>
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
                            <x-heroicon-o-check-circle class="h-7 w-7 text-green-600" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.ad_accounts.active_accounts') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $activeAccountsCount }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    {{ $totalAccountsCount > 0 ? round(($activeAccountsCount / $totalAccountsCount) * 100) : 0 }}% {{ __('pages.ad_accounts.of_total') }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-lg bg-orange-100 flex items-center justify-center">
                            <x-heroicon-o-briefcase class="h-7 w-7 text-orange-600" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.ad_accounts.with_industry') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $accountsWithIndustryCount }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    {{ $totalAccountsCount > 0 ? round(($accountsWithIndustryCount / $totalAccountsCount) * 100) : 0 }}% {{ __('pages.ad_accounts.categorized') }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                            <x-heroicon-o-rocket-launch class="h-7 w-7 text-purple-600" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.ad_accounts.total_campaigns') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totalCampaignsCount) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    {{ $totalAccountsCount > 0 ? number_format($totalCampaignsCount / $totalAccountsCount, 1) : 0 }} {{ __('pages.ad_accounts.avg_per_account') }}
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-md rounded-lg hover:shadow-lg transition-shadow duration-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <x-heroicon-o-currency-dollar class="h-7 w-7 text-emerald-600" />
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('labels.total_spend') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-gray-900 break-words">SR {{ number_format($totalSpend, 2) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    {{ __('pages.ad_accounts.across_all_accounts', ['count' => $totalAccountsCount]) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Ad Accounts Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('pages.ad_accounts.title') }}</h3>
                <div class="flex items-center space-x-2">
                    <input type="checkbox"
                        wire:click="toggleSelectAll"
                        @checked($accounts->count() > 0 && count($selectedAccounts) === $accounts->total())
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                    <span class="text-sm text-gray-500">{{ __('pages.ad_accounts.select_all') }}</span>
                </div>
            </div>
        </div>

        <x-table-scroll max-height="600px" class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                            {{ __('labels.select') }}
                        </th>
                        <th scope="col" wire:click="sortBy('account_name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50 min-w-[200px] sticky left-0 z-20 border-r border-gray-200">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('labels.account') }}</span>
                                @if($sortField === 'account_name')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.client') }}</th>
                        <th scope="col" wire:click="sortBy('total_spend')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('labels.total_spend') }}</span>
                                @if($sortField === 'total_spend')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('platform')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('labels.platform') }}</span>
                                @if($sortField === 'platform')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1"><span>{{ __('labels.status') }}</span></div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.verification') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.industry') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.country') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.category') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.sub_category') }}</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.campaigns') }}</th>
                        <th scope="col" wire:click="sortBy('total_revenue')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('labels.revenue') }}</span>
                                @if($sortField === 'total_revenue')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('total_purchases')" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-center space-x-1">
                                <span>{{ __('labels.purchases') }}</span>
                                @if($sortField === 'total_purchases')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('roas')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end space-x-1">
                                <span>ROAS</span>
                                @if($sortField === 'roas')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-700" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-700" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('labels.atc') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <input type="checkbox" value="{{ $account->id }}" wire:model.live="selectedAccounts"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap sticky left-0 z-10 sticky-col border-r border-gray-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <x-heroicon-o-building-office class="h-4 w-4 text-gray-500" />
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('ad-account-detail', ['id' => $account->id]) }}" wire:navigate
                                            class="text-sm font-medium text-primary-600 hover:text-primary-900 hover:underline">
                                            {{ $account->account_name }}
                                        </a>
                                        <div class="text-xs text-gray-500">{{ $account->external_account_id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-select2
                                    uid="client-{{ $account->id }}"
                                    wire-change="updateAccountClient({{ $account->id }})"
                                    :initial-value="$account->tenant_id ? (string) $account->tenant_id : ''"
                                    :placeholder="__('Select')"
                                    :options="$clientOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                @if($account->total_spend_sar !== null)
                                    SR {{ number_format($account->total_spend_sar, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $platformClass = match($account->platform_name) {
                                        'facebook' => 'bg-blue-100 text-blue-800',
                                        'google' => 'bg-green-100 text-green-800',
                                        'tiktok' => 'bg-pink-100 text-pink-800',
                                        'linkedin' => 'bg-primary-100 text-primary-800',
                                        'snapchat' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $platformClass }}">
                                    {{ $account->platform_name }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $statusBadge = match($account->status) {
                                        'active' => ['class' => 'bg-green-100 text-green-800', 'label' => __('status.active')],
                                        'inactive' => ['class' => 'bg-gray-100 text-gray-800', 'label' => __('status.inactive')],
                                        'permission_denied' => ['class' => 'bg-amber-100 text-amber-800', 'label' => __('Access Denied')],
                                        default => ['class' => 'bg-red-100 text-red-800', 'label' => $account->status],
                                    };
                                @endphp
                                <span title="{{ $account->status === 'permission_denied' ? __('Google Ads API returned PERMISSION_DENIED. The manager account no longer has access to this account.') : '' }}" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge['class'] }}">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $vStatus = $account->data_verification_status ?? 'pending';
                                    $vClass = match($vStatus) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'declined' => 'bg-red-100 text-red-800',
                                        default => 'bg-yellow-100 text-yellow-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $vClass }}">
                                    {{ $vStatus }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-select2
                                    uid="industry-{{ $account->id }}"
                                    wire-change="updateAccountIndustry({{ $account->id }})"
                                    :initial-value="$account->industry ?? ''"
                                    :placeholder="__('Select')"
                                    :options="collect($industries)->mapWithKeys(fn($ind) => [$ind->name => $ind->display_name])->all()"
                                    :searchable="true"
                                />
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-select2
                                    uid="country-{{ $account->id }}"
                                    wire-change="updateAccountCountry({{ $account->id }})"
                                    :initial-value="$account->country ?? ''"
                                    :placeholder="__('Select')"
                                    :options="$countryOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-select2
                                    uid="category-{{ $account->id }}"
                                    wire-change="updateAccountCategory({{ $account->id }})"
                                    :initial-value="$account->category ?? ''"
                                    :placeholder="__('Select')"
                                    :options="$accountCategoryOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @if(!empty($account->available_sub_categories))
                                    <x-select2
                                        uid="sub-category-{{ $account->id }}"
                                        wire-change="updateAccountSubCategory({{ $account->id }})"
                                        :initial-value="$account->sub_industry ?? ''"
                                        :placeholder="__('Select')"
                                        :options="$account->available_sub_categories"
                                        :searchable="true"
                                    />
                                @else
                                    <span class="text-xs text-gray-400">{{ $account->category ? '-' : 'Set category' }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                                {{ $account->ad_campaigns_count }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                SR {{ number_format($account->total_revenue_sar, 2) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                                {{ number_format($account->total_purchases) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                {{ number_format($account->roas, 2) }}x
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-center">
                                {{ number_format($account->total_atc) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="px-6 py-12 text-center text-gray-500">
                                {{ __('pages.ad_accounts.no_accounts') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-scroll>

        {{-- Pagination --}}
        <x-pagination-wrapper :paginator="$accounts" :per-page-options="[10, 25, 50, 100]" />
    </div>

    {{-- Bulk Update Modal --}}
    @if($showBulkModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data x-on:keydown.escape.window="$wire.closeBulkModal()">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('pages.ad_accounts.bulk_update_title', ['count' => count($selectedAccounts)]) }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <x-select2
                                wire-model="bulkIndustry"
                                :label="__('labels.industry')"
                                :placeholder="__('pages.ad_accounts.keep_current')"
                                :options="collect($industries)->mapWithKeys(fn($ind) => [$ind->name => $ind->display_name])->all()"
                            />
                        </div>

                        <div>
                            <x-select2
                                wire-model="bulkCountry"
                                :label="__('labels.country')"
                                :placeholder="__('pages.ad_accounts.keep_current')"
                                :options="$countryOptions"
                            />
                        </div>

                        <div>
                            <x-select2
                                wire-model="bulkCategory"
                                :label="__('labels.category')"
                                :placeholder="__('pages.ad_accounts.keep_current')"
                                :options="$accountCategoryOptions"
                            />
                        </div>

                        <div>
                            <x-select2
                                wire-model="bulkSubCategory"
                                :label="__('labels.sub_category')"
                                :placeholder="__('pages.ad_accounts.keep_current')"
                                :options="$bulkAvailableSubCategories"
                            />
                        </div>

                        <div>
                            <x-select2
                                wire-model="bulkStatus"
                                :label="__('labels.status')"
                                :placeholder="__('pages.ad_accounts.keep_current')"
                                :options="['active' => __('status.active'), 'inactive' => __('status.inactive'), 'permission_denied' => __('Access Denied')]"
                            />
                        </div>

                        <div>
                            <div class="flex items-center mb-1">
                                <input type="checkbox" wire:model.live="bulkChangeClient" id="bulkChangeClient"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                                <label for="bulkChangeClient" class="ml-2 block text-sm font-medium text-gray-700">
                                    {{ __('Change Client') }}
                                </label>
                            </div>
                            @if($bulkChangeClient)
                                <x-select2
                                    wire-model="bulkClientId"
                                    :placeholder="__('pages.ad_accounts.keep_current')"
                                    :options="$clientOptions"
                                    :searchable="true"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button wire:click="closeBulkModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="performBulkUpdate"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                            {{ __('pages.ad_accounts.update_accounts') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Create Client Modal --}}
    @if($showCreateClientModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data x-on:keydown.escape.window="$wire.closeCreateClientModal()">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('New Client') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="newClientName" class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.name') }}</label>
                            <input id="newClientName" type="text" wire:model="newClientName" wire:keydown.enter="quickCreateClient"
                                placeholder="{{ __('Client name') }}"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                            @error('newClientName')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 mt-6">
                        <button wire:click="closeCreateClientModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="quickCreateClient"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                            {{ __('buttons.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Verification Modal --}}
    @if($showVerificationModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data x-on:keydown.escape.window="$wire.closeVerificationModal()">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $verificationAction === 'approved' ? __('buttons.approve') : __('buttons.reject') }}
                        {{ count($accountsToVerify) }} {{ __('pages.ad_accounts.accounts') }}
                    </h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('pages.ad_accounts.notes_optional') }}</label>
                        <textarea wire:model="verificationNotes" rows="3"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="{{ __('pages.ad_accounts.add_verification_notes') }}"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <button wire:click="closeVerificationModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="performVerification"
                            class="px-4 py-2 text-sm font-medium text-white rounded-md {{ $verificationAction === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                            {{ $verificationAction === 'approved' ? __('buttons.approve') : __('buttons.reject') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
