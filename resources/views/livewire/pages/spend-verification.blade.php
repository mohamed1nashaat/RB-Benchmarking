<div class="space-y-6">
    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="section-title">
                {{ __('pages.spend_verification.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.spend_verification.description') }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ms-4">
            <a href="{{ route('ad-accounts') }}" wire:navigate class="btn-secondary">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                {{ __('labels.ad_accounts') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-bar">
        {{-- Search --}}
        <div class="flex-1 min-w-[200px]">
            <label class="micro-label block mb-1">{{ __('labels.search') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="{{ __('placeholders.account_name_or_id') }}"
                    class="input-field ps-9 pe-3" />
            </div>
        </div>

        {{-- Platform --}}
        <div class="flex-1 min-w-[150px]">
            <x-select2
                wire-model="filterPlatform"
                :label="__('labels.platform')"
                :placeholder="__('filters.all_platforms')"
                :options="collect($platforms)->mapWithKeys(fn($p) => [$p => ucfirst($p)])->all()"
            />
        </div>

        {{-- Date Range --}}
        <div class="flex-1 min-w-[200px]">
            <x-date-range-picker
                wire-model-from="filterDateFrom"
                wire-model-to="filterDateTo"
                :label="__('labels.date_range')"
                align="left"
            />
        </div>

        {{-- Verification Status --}}
        <div class="flex-1 min-w-[150px]">
            <x-select2
                wire-model="filterVerification"
                :label="__('labels.verification')"
                :placeholder="__('filters.all_statuses')"
                :options="['pending' => __('status.pending'), 'approved' => __('status.approved'), 'declined' => __('status.declined')]"
            />
        </div>

        {{-- Client --}}
        <div class="flex-1 min-w-[150px]">
            <x-select2
                wire-model="filterClientId"
                :label="__('labels.client')"
                :placeholder="__('filters.all_clients')"
                :options="collect(['unassigned' => __('filters.unassigned')])->merge(collect($clients)->mapWithKeys(fn($c) => [$c->id => $c->name]))->all()"
            />
        </div>

        {{-- Group By --}}
        <div class="flex-1 min-w-[130px]">
            <x-select2
                wire-model="groupBy"
                :label="__('Group By')"
                :placeholder="__('Select')"
                :options="['daily' => __('common.daily'), 'monthly' => __('common.monthly'), 'total' => __('common.total')]"
            />
        </div>

        @if($this->hasActiveFilters)
            <button wire:click="clearAllFilters" class="btn-secondary">
                {{ __('filters.clear_filters') }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 sm:px-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold tracking-tight text-gray-900">
                    {{ __('pages.spend_verification.spend_data') }}
                    <span class="micro-label ms-2 normal-case">{{ trans_choice('common.rows', $rows->total()) }}</span>
                </h3>
                <div class="flex items-center gap-x-2 text-xs text-gray-400">
                    <x-heroicon-o-information-circle class="h-4 w-4" />
                    <span>{{ __('pages.spend_verification.original_currency_hint') }}</span>
                </div>
            </div>
        </div>

        <x-table-scroll max-height="700px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th wire:click="sortBy('account_name')" class="min-w-[220px] sticky start-0 z-20 border-e border-gray-200 cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-x-1">
                                <span>{{ __('labels.account') }}</span>
                                @if($sortField === 'account_name')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('client_name')" class="cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-x-1">
                                <span>{{ __('labels.client') }}</span>
                                @if($sortField === 'client_name')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('platform')" class="cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-x-1">
                                <span>{{ __('labels.platform') }}</span>
                                @if($sortField === 'platform')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        @if($groupBy !== 'total')
                            <th wire:click="sortBy('period')" class="cursor-pointer hover:bg-gray-100">
                                <div class="flex items-center gap-x-1">
                                    <span>{{ __('labels.period') }}</span>
                                    @if($sortField === 'period')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                    @endif
                                </div>
                            </th>
                        @endif
                        <th class="text-center">{{ __('labels.currency') }}</th>
                        <th wire:click="sortBy('total_spend')" class="text-end cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end gap-x-1">
                                <span>{{ __('pages.spend_verification.spend_original') }}</span>
                                @if($sortField === 'total_spend')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th class="text-end">{{ __('pages.spend_verification.spend_sar') }}</th>
                        <th wire:click="sortBy('total_revenue')" class="text-end cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end gap-x-1">
                                <span>{{ __('pages.spend_verification.revenue_original') }}</span>
                                @if($sortField === 'total_revenue')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th class="text-center">{{ __('alerts.metrics.impressions') }}</th>
                        <th class="text-center">{{ __('alerts.metrics.clicks') }}</th>
                        <th class="text-center">{{ __('alerts.metrics.conversions') }}</th>
                        <th class="text-center">{{ __('labels.days') }}</th>
                        <th>{{ __('labels.last_sync') }}</th>
                        <th class="text-center">{{ __('labels.verification') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            {{-- Account --}}
                            <td class="sticky start-0 z-10 sticky-col border-e border-gray-200">
                                <div>
                                    <a href="{{ route('ad-account-detail', ['id' => $row->ad_account_id]) }}" wire:navigate
                                        class="text-sm font-medium text-gray-900 hover:underline">
                                        {{ $row->account_name }}
                                    </a>
                                    <div class="text-xs text-gray-400 font-mono">{{ $row->external_account_id }}</div>
                                </div>
                            </td>

                            {{-- Client --}}
                            <td class="whitespace-nowrap">
                                {{ $row->client_name ?? '-' }}
                            </td>

                            {{-- Platform --}}
                            <td class="whitespace-nowrap">
                                @php
                                    $platformClass = match($row->platform) {
                                        'facebook' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                        'google' => 'bg-[#EDF3EC] text-[#346538]',
                                        'tiktok' => 'bg-[#F1F1EF] text-[#6B6B66]',
                                        'linkedin' => 'bg-[#E1F3FE] text-[#1F6C9F]',
                                        'snapchat' => 'bg-[#FBF3DB] text-[#956400]',
                                        default => 'bg-[#F1F1EF] text-[#6B6B66]',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 capitalize {{ $platformClass }}">
                                    {{ $row->platform }}
                                </span>
                            </td>

                            {{-- Period --}}
                            @if($groupBy !== 'total')
                                <td class="whitespace-nowrap font-mono">
                                    {{ $row->period }}
                                </td>
                            @endif

                            {{-- Currency --}}
                            <td class="whitespace-nowrap text-center text-gray-500 font-mono">
                                {{ $row->original_currency }}
                            </td>

                            {{-- Spend Original --}}
                            <td class="whitespace-nowrap text-end font-semibold text-gray-900">
                                {{ number_format((float) $row->total_spend, 2) }}
                            </td>

                            {{-- Spend SAR --}}
                            <td class="whitespace-nowrap text-end text-gray-500">
                                @if($row->original_currency !== 'SAR')
                                    SR {{ number_format($row->spend_sar, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Revenue Original --}}
                            <td class="whitespace-nowrap text-end text-gray-900">
                                {{ number_format((float) $row->total_revenue, 2) }}
                            </td>

                            {{-- Impressions --}}
                            <td class="whitespace-nowrap text-center">
                                {{ number_format((int) $row->total_impressions) }}
                            </td>

                            {{-- Clicks --}}
                            <td class="whitespace-nowrap text-center">
                                {{ number_format((int) $row->total_clicks) }}
                            </td>

                            {{-- Conversions --}}
                            <td class="whitespace-nowrap text-center">
                                {{ number_format((int) $row->total_conversions) }}
                            </td>

                            {{-- Days with data --}}
                            <td class="whitespace-nowrap text-center text-gray-500">
                                {{ $row->days_with_data }}
                            </td>

                            {{-- Last Sync --}}
                            <td class="whitespace-nowrap text-xs text-gray-500">
                                @if($row->last_metrics_sync_at)
                                    {{ \Carbon\Carbon::parse($row->last_metrics_sync_at)->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">{{ __('common.never') }}</span>
                                @endif
                            </td>

                            {{-- Verification --}}
                            <td class="whitespace-nowrap text-center">
                                @php
                                    $vStatus = $row->data_verification_status ?? 'pending';
                                    $vClass = match($vStatus) {
                                        'approved' => 'bg-[#EDF3EC] text-[#346538]',
                                        'declined' => 'bg-[#FDEBEC] text-[#9F2F2D]',
                                        default => 'bg-[#FBF3DB] text-[#956400]',
                                    };
                                @endphp
                                <div class="inline-flex items-center gap-x-1.5">
                                    <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $vClass }}">
                                        {{ $vStatus }}
                                    </span>
                                    <button wire:click="openVerificationModal('approved', [{{ $row->ad_account_id }}])"
                                        title="{{ __('buttons.approve') }}"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[#346538] hover:bg-[#EDF3EC] transition-colors">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                    </button>
                                    <button wire:click="openVerificationModal('declined', [{{ $row->ad_account_id }}])"
                                        title="{{ __('buttons.reject') }}"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[#9F2F2D] hover:bg-[#FDEBEC] transition-colors">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $groupBy !== 'total' ? 14 : 13 }}">
                                <div class="empty-state">
                                    <div class="empty-title">{{ __('pages.spend_verification.no_spend_data') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-scroll>

        <div class="px-4 py-3 border-t border-gray-200">
            <x-pagination-wrapper :paginator="$rows" :per-page-options="[10, 25, 50, 100]" />
        </div>
    </div>

    {{-- Verification Modal --}}
    @if($showVerificationModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-data x-on:keydown.escape.window="$wire.closeVerificationModal()">
            <div class="relative top-20 mx-auto p-5 w-96 border border-gray-200 rounded-xl bg-white">
                <div class="mt-3">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900 mb-4">
                        {{ $verificationAction === 'approved' ? __('buttons.approve') : __('buttons.reject') }}
                        {{ count($accountsToVerify) }} {{ __('pages.ad_accounts.accounts') }}
                    </h3>

                    <div class="mb-4">
                        <label class="micro-label block mb-2">{{ __('pages.ad_accounts.notes_optional') }}</label>
                        <textarea wire:model="verificationNotes" rows="3"
                            class="input-field"
                            placeholder="{{ __('pages.ad_accounts.add_verification_notes') }}"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-x-3">
                        <button wire:click="closeVerificationModal" class="btn-secondary">
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
