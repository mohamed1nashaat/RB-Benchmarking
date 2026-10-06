<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-xl bg-[#EDF3EC] p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-[#346538]" />
                <p class="ms-3 text-sm font-medium text-[#346538]">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-semibold tracking-tight text-white">
                {{ __('pages.ad_accounts.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.ad_accounts.description') }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ms-4 gap-x-3">
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
                class="btn-secondary">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4 me-2" />
                {{ __('common.export_as_xlsx') }}
            </a>

            @if($isAdmin)
                <button wire:click="openCreateClientModal"
                    class="btn-secondary">
                    <x-heroicon-o-plus class="h-4 w-4 me-2" />
                    {{ __('pages.clients.new_client') }}
                </button>
            @endif

            @if(count($selectedAccounts) > 0)
                <button wire:click="openBulkModal"
                    class="btn-primary">
                    <x-heroicon-o-pencil class="h-4 w-4 me-2" />
                    {{ __('pages.ad_accounts.bulk_edit', ['count' => count($selectedAccounts)]) }}
                </button>
            @endif

            @if($isAdmin && count($selectedAccounts) > 0)
                <button wire:click="openVerificationModal('approved', {{ json_encode($selectedAccounts) }})"
                    class="btn-secondary">
                    <x-heroicon-o-check-circle class="h-4 w-4 me-2" />
                    {{ __('buttons.approve') }} {{ count($selectedAccounts) }}
                </button>
                <button wire:click="openVerificationModal('declined', {{ json_encode($selectedAccounts) }})"
                    class="btn-danger">
                    <x-heroicon-o-x-circle class="h-4 w-4 me-2" />
                    {{ __('buttons.reject') }} {{ count($selectedAccounts) }}
                </button>
            @endif
        </div>
    </div>

    {{-- Permission Denied Warning Banner (admin only) --}}
    @if($isAdmin)
        @php
            $permissionDeniedCount = $accounts->getCollection()->where('status', 'permission_denied')->count();
        @endphp
        @if($permissionDeniedCount > 0 && empty($selectedAccounts))
            <div class="rounded-xl border border-yellow-200 bg-[#FBF3DB] p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-[#956400]" />
                    </div>
                    <div class="ms-3">
                        <h3 class="text-sm font-medium text-[#956400]">{{ __('pages.ad_accounts.google_ads_access_denied', ['count' => $permissionDeniedCount]) }}</h3>
                        <p class="mt-1 text-sm text-[#956400]/80">
                            {{ __('pages.ad_accounts.permission_denied_help') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(count($selectedAccounts) > 0 && collect($accounts->items())->whereIn('id', $selectedAccounts)->where('status', 'permission_denied')->count() > 0)
            <button wire:click="resetPermissionDenied" wire:confirm="{{ __('pages.ad_accounts.reset_permission_denied_confirm') }}"
                class="btn-secondary">
                <x-heroicon-o-arrow-path class="h-4 w-4 me-2" />
                {{ __('pages.ad_accounts.reset_to_active') }}
            </button>
        @endif
    @endif

    {{-- Search & Filters --}}
    <div class="filter-bar flex-col items-stretch gap-5">
        {{-- Search Bar --}}
        <div>
            <label for="search" class="micro-label block mb-2">{{ __('labels.search') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input id="search" wire:model.live.debounce.300ms="search" type="text"
                    placeholder="{{ __('pages.ad_accounts.search_placeholder') }}"
                    class="input-field ps-10" />
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
                    :options="['active' => __('status.active'), 'inactive' => __('status.inactive'), 'permission_denied' => __('status.access_denied')]"
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
                        class="btn-ghost">
                        {{ __('filters.clear_filters') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <div class="kpi-card">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-[#F1F1EF] flex items-center justify-center">
                        <x-heroicon-o-building-office class="h-5 w-5 text-[#6B6B66]" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="kpi-label">{{ __('pages.ad_accounts.total_accounts') }}</div>
                    <div class="kpi-value">{{ $totalAccountsCount }}</div>
                </div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-[#EDF3EC] flex items-center justify-center">
                        <x-heroicon-o-check-circle class="h-5 w-5 text-[#346538]" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="kpi-label">{{ __('pages.ad_accounts.active_accounts') }}</div>
                    <div class="kpi-value">{{ $activeAccountsCount }}</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500">
                {{ $totalAccountsCount > 0 ? round(($activeAccountsCount / $totalAccountsCount) * 100) : 0 }}% {{ __('pages.ad_accounts.of_total') }}
            </div>
        </div>

        <div class="kpi-card">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-[#F1F1EF] flex items-center justify-center">
                        <x-heroicon-o-briefcase class="h-5 w-5 text-[#6B6B66]" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="kpi-label">{{ __('pages.ad_accounts.with_industry') }}</div>
                    <div class="kpi-value">{{ $accountsWithIndustryCount }}</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500">
                {{ $totalAccountsCount > 0 ? round(($accountsWithIndustryCount / $totalAccountsCount) * 100) : 0 }}% {{ __('pages.ad_accounts.categorized') }}
            </div>
        </div>

        <div class="kpi-card">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-[#F1F1EF] flex items-center justify-center">
                        <x-heroicon-o-rocket-launch class="h-5 w-5 text-[#6B6B66]" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="kpi-label">{{ __('pages.ad_accounts.total_campaigns') }}</div>
                    <div class="kpi-value">{{ number_format($totalCampaignsCount) }}</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500">
                {{ $totalAccountsCount > 0 ? number_format($totalCampaignsCount / $totalAccountsCount, 1) : 0 }} {{ __('pages.ad_accounts.avg_per_account') }}
            </div>
        </div>

        <div class="kpi-card">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-[#EDF3EC] flex items-center justify-center">
                        <x-heroicon-o-currency-dollar class="h-5 w-5 text-[#346538]" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="kpi-label">{{ __('labels.total_spend') }}</div>
                    <div class="kpi-value">SR {{ number_format($totalSpend, 2) }}</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500">
                {{ __('pages.ad_accounts.across_all_accounts', ['count' => $totalAccountsCount]) }}
            </div>
        </div>
    </div>

    {{-- Ad Accounts Table --}}
    <div class="card overflow-hidden">
        <div class="p-5 border-b border-white/10">
            <div class="flex items-center justify-between">
                <h3 class="section-title">{{ __('pages.ad_accounts.title') }}</h3>
                <div class="flex items-center gap-x-2">
                    <input type="checkbox"
                        wire:click="toggleSelectAll"
                        @checked($accounts->count() > 0 && count($selectedAccounts) === $accounts->total())
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-white/15 rounded" />
                    <span class="text-sm text-gray-500">{{ __('pages.ad_accounts.select_all') }}</span>
                </div>
            </div>
        </div>

        <x-table-scroll max-height="600px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">
                            {{ __('labels.select') }}
                        </th>
                        <th scope="col" wire:click="sortBy('account_name')" class="min-w-[200px] sticky start-0 z-20 border-e border-white/10 cursor-pointer hover:bg-white/5">
                            <div class="flex items-center gap-x-1">
                                <span>{{ __('labels.account') }}</span>
                                @if($sortField === 'account_name')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col">{{ __('labels.client') }}</th>
                        <th scope="col" wire:click="sortBy('total_spend')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center justify-end gap-x-1">
                                <span>{{ __('labels.total_spend') }}</span>
                                @if($sortField === 'total_spend')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('platform')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center gap-x-1">
                                <span>{{ __('labels.platform') }}</span>
                                @if($sortField === 'platform')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('status')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center gap-x-1"><span>{{ __('labels.status') }}</span></div>
                        </th>
                        <th scope="col">{{ __('labels.verification') }}</th>
                        <th scope="col">{{ __('labels.industry') }}</th>
                        <th scope="col">{{ __('labels.country') }}</th>
                        <th scope="col">{{ __('labels.category') }}</th>
                        <th scope="col">{{ __('labels.campaigns') }}</th>
                        <th scope="col" wire:click="sortBy('total_revenue')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center justify-end gap-x-1">
                                <span>{{ __('labels.revenue') }}</span>
                                @if($sortField === 'total_revenue')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('total_purchases')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center justify-center gap-x-1">
                                <span>{{ __('labels.purchases') }}</span>
                                @if($sortField === 'total_purchases')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('roas')" class="cursor-pointer hover:bg-white/5">
                            <div class="flex items-center justify-end gap-x-1">
                                <span>ROAS</span>
                                @if($sortField === 'roas')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-gray-200" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-200" />
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th scope="col">
                            {{ __('labels.atc') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $account->id }}" wire:model.live="selectedAccounts"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-white/15 rounded" />
                            </td>

                            <td class="sticky start-0 z-10 sticky-col border-e border-white/10">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-white/8 flex items-center justify-center">
                                            <x-heroicon-o-building-office class="h-4 w-4 text-gray-500" />
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <a href="{{ route('ad-account-detail', ['id' => $account->id]) }}" wire:navigate
                                            class="text-sm font-medium text-white hover:underline">
                                            {{ $account->account_name }}
                                        </a>
                                        <div class="text-xs text-gray-500">{{ $account->external_account_id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <x-select2
                                    uid="client-{{ $account->id }}"
                                    wire-change="updateAccountClient({{ $account->id }})"
                                    :initial-value="$account->tenant_id ? (string) $account->tenant_id : ''"
                                    :placeholder="__('Select')"
                                    :options="$clientOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td class="text-end font-semibold">
                                @if($account->total_spend_sar !== null)
                                    SR {{ number_format($account->total_spend_sar, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $platformClass = match($account->platform_name) {
                                        'facebook' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                        'google' => 'bg-[#EDF3EC] text-[#346538]',
                                        'tiktok' => 'bg-[#F1F1EF] text-[#6B6B66]',
                                        'linkedin' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                        'snapchat' => 'bg-[#FBF3DB] text-[#956400]',
                                        default => 'bg-[#F1F1EF] text-[#6B6B66]',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 capitalize {{ $platformClass }}">
                                    {{ $account->platform_name }}
                                </span>
                            </td>

                            <td>
                                @php
                                    $statusBadge = match($account->status) {
                                        'active' => ['class' => 'bg-[#EDF3EC] text-[#346538]', 'label' => __('status.active')],
                                        'inactive' => ['class' => 'bg-[#F1F1EF] text-[#6B6B66]', 'label' => __('status.inactive')],
                                        'permission_denied' => ['class' => 'bg-[#FBF3DB] text-[#956400]', 'label' => __('Access Denied')],
                                        default => ['class' => 'bg-[#FDEBEC] text-[#9F2F2D]', 'label' => $account->status],
                                    };
                                @endphp
                                <span title="{{ $account->status === 'permission_denied' ? __('Google Ads API returned PERMISSION_DENIED. The manager account no longer has access to this account.') : '' }}" class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $statusBadge['class'] }}">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </td>

                            <td>
                                @php
                                    $vStatus = $account->data_verification_status ?? 'pending';
                                    $vClass = match($vStatus) {
                                        'approved' => 'bg-[#EDF3EC] text-[#346538]',
                                        'declined' => 'bg-[#FDEBEC] text-[#9F2F2D]',
                                        default => 'bg-[#FBF3DB] text-[#956400]',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $vClass }}">
                                    {{ $vStatus }}
                                </span>
                            </td>

                            <td>
                                <x-select2
                                    uid="industry-{{ $account->id }}"
                                    wire-change="updateAccountIndustry({{ $account->id }})"
                                    :initial-value="$account->industry ?? ''"
                                    :placeholder="__('Select')"
                                    :options="collect($industries)->mapWithKeys(fn($ind) => [$ind->name => $ind->display_name])->all()"
                                    :searchable="true"
                                />
                            </td>

                            <td>
                                <x-select2
                                    uid="country-{{ $account->id }}"
                                    wire-change="updateAccountCountry({{ $account->id }})"
                                    :initial-value="$account->country ?? ''"
                                    :placeholder="__('Select')"
                                    :options="$countryOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td>
                                <x-select2
                                    uid="category-{{ $account->id }}"
                                    wire-change="updateAccountCategory({{ $account->id }})"
                                    :initial-value="$account->category ?? ''"
                                    :placeholder="__('Select')"
                                    :options="$accountCategoryOptions"
                                    :searchable="true"
                                />
                            </td>

                            <td class="text-center">
                                {{ $account->ad_campaigns_count }}
                            </td>

                            <td class="text-end">
                                SR {{ number_format($account->total_revenue_sar, 2) }}
                            </td>

                            <td class="text-center">
                                {{ number_format($account->total_purchases) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($account->roas, 2) }}x
                            </td>

                            <td class="text-center">
                                {{ number_format($account->total_atc) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15">
                                <div class="empty-state">
                                    <div class="empty-title">{{ __('pages.ad_accounts.no_accounts') }}</div>
                                </div>
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
        <div class="fixed inset-0 z-50 overflow-y-auto h-full w-full bg-gray-900/50" x-data x-on:keydown.escape.window="$wire.closeBulkModal()">
            <div class="relative top-20 mx-auto w-full max-w-md card p-6">
                <div class="mt-3">
                    <h3 class="section-title mb-4">
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
                                :options="['active' => __('status.active'), 'inactive' => __('status.inactive'), 'permission_denied' => __('status.access_denied')]"
                            />
                        </div>

                        <div>
                            <div class="flex items-center mb-1">
                                <input type="checkbox" wire:model.live="bulkChangeClient" id="bulkChangeClient"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-white/15 rounded" />
                                <label for="bulkChangeClient" class="ms-2 block text-sm font-medium text-gray-200">
                                    {{ __('pages.ad_accounts.change_client') }}
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

                    <div class="flex items-center justify-end gap-x-3 mt-6">
                        <button wire:click="closeBulkModal"
                            class="btn-secondary">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="performBulkUpdate"
                            class="btn-primary">
                            {{ __('pages.ad_accounts.update_accounts') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Create Client Modal --}}
    @if($showCreateClientModal)
        <div class="fixed inset-0 z-50 overflow-y-auto h-full w-full bg-gray-900/50" x-data x-on:keydown.escape.window="$wire.closeCreateClientModal()">
            <div class="relative top-20 mx-auto w-full max-w-md card p-6">
                <div class="mt-3">
                    <h3 class="section-title mb-4">{{ __('pages.clients.new_client') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="newClientName" class="micro-label block mb-1">{{ __('labels.name') }}</label>
                            <input id="newClientName" type="text" wire:model="newClientName" wire:keydown.enter="quickCreateClient"
                                placeholder="{{ __('placeholders.client_name') }}"
                                class="input-field" />
                            @error('newClientName')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-x-3 mt-6">
                        <button wire:click="closeCreateClientModal"
                            class="btn-secondary">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="quickCreateClient"
                            class="btn-primary">
                            {{ __('buttons.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Verification Modal --}}
    @if($showVerificationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto h-full w-full bg-gray-900/50" x-data x-on:keydown.escape.window="$wire.closeVerificationModal()">
            <div class="relative top-20 mx-auto w-full max-w-md card p-6">
                <div class="mt-3">
                    <h3 class="section-title mb-4">
                        {{ __('pages.ad_accounts.verify_accounts_title', ['action' => $verificationAction === 'approved' ? __('buttons.approve') : __('buttons.reject'), 'count' => count($accountsToVerify)]) }}
                    </h3>

                    <div class="mb-4">
                        <label class="micro-label block mb-2">{{ __('pages.ad_accounts.notes_optional') }}</label>
                        <textarea wire:model="verificationNotes" rows="3"
                            class="input-field"
                            placeholder="{{ __('pages.ad_accounts.add_verification_notes') }}"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-x-3">
                        <button wire:click="closeVerificationModal"
                            class="btn-secondary">
                            {{ __('buttons.cancel') }}
                        </button>
                        <button wire:click="performVerification"
                            class="{{ $verificationAction === 'approved' ? 'btn-primary' : 'btn-danger' }}">
                            {{ $verificationAction === 'approved' ? __('buttons.approve') : __('buttons.reject') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
