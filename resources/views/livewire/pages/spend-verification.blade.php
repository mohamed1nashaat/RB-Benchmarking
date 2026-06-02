<div class="space-y-6">
    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ __('Spend Verification') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Compare synced spend with platform dashboards. All amounts shown in original currency.') }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="{{ route('ad-accounts') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                {{ __('Ad Accounts') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white shadow overflow-visible sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 space-y-4">
            <div class="flex flex-wrap gap-4">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.search') }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            placeholder="{{ __('Account name or ID...') }}"
                            class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
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
                        :options="['daily' => __('Daily'), 'monthly' => __('Monthly'), 'total' => __('Total')]"
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

    {{-- Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ __('Spend Data') }}
                    <span class="text-sm font-normal text-gray-500 ml-2">{{ number_format($rows->total()) }} {{ __('rows') }}</span>
                </h3>
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <x-heroicon-o-information-circle class="h-4 w-4" />
                    <span>{{ __('Spend & Revenue in original platform currency') }}</span>
                </div>
            </div>
        </div>

        <x-table-scroll max-height="700px" class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th wire:click="sortBy('account_name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50 min-w-[220px] sticky left-0 z-20 border-r border-gray-200">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('labels.account') }}</span>
                                @if($sortField === 'account_name')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('client_name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('labels.client') }}</span>
                                @if($sortField === 'client_name')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('platform')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center space-x-1">
                                <span>{{ __('labels.platform') }}</span>
                                @if($sortField === 'platform')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        @if($groupBy !== 'total')
                            <th wire:click="sortBy('period')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                <div class="flex items-center space-x-1">
                                    <span>{{ __('Period') }}</span>
                                    @if($sortField === 'period')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                    @endif
                                </div>
                            </th>
                        @endif
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Currency') }}</th>
                        <th wire:click="sortBy('total_spend')" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('Spend (Original)') }}</span>
                                @if($sortField === 'total_spend')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Spend (SAR)') }}</th>
                        <th wire:click="sortBy('total_revenue')" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center justify-end space-x-1">
                                <span>{{ __('Revenue (Original)') }}</span>
                                @if($sortField === 'total_revenue')
                                    <x-heroicon-o-chevron-up class="h-4 w-4 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }} text-gray-700" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Impressions') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Clicks') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Conversions') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Days') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Last Sync') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('labels.verification') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            {{-- Account --}}
                            <td class="px-4 py-3 whitespace-nowrap sticky left-0 z-10 sticky-col border-r border-gray-200">
                                <div>
                                    <a href="{{ route('ad-account-detail', ['id' => $row->ad_account_id]) }}" wire:navigate
                                        class="text-sm font-medium text-primary-600 hover:text-primary-900 hover:underline">
                                        {{ $row->account_name }}
                                    </a>
                                    <div class="text-xs text-gray-500 font-mono">{{ $row->external_account_id }}</div>
                                </div>
                            </td>

                            {{-- Client --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                {{ $row->client_name ?? '-' }}
                            </td>

                            {{-- Platform --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $platformClass = match($row->platform) {
                                        'facebook' => 'bg-blue-100 text-blue-800',
                                        'google' => 'bg-green-100 text-green-800',
                                        'tiktok' => 'bg-pink-100 text-pink-800',
                                        'linkedin' => 'bg-primary-100 text-primary-800',
                                        'snapchat' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $platformClass }}">
                                    {{ $row->platform }}
                                </span>
                            </td>

                            {{-- Period --}}
                            @if($groupBy !== 'total')
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-mono">
                                    {{ $row->period }}
                                </td>
                            @endif

                            {{-- Currency --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-center font-mono">
                                {{ $row->original_currency }}
                            </td>

                            {{-- Spend Original --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-gray-900">
                                {{ number_format((float) $row->total_spend, 2) }}
                            </td>

                            {{-- Spend SAR --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500">
                                @if($row->original_currency !== 'SAR')
                                    SR {{ number_format($row->spend_sar, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Revenue Original --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ number_format((float) $row->total_revenue, 2) }}
                            </td>

                            {{-- Impressions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ number_format((int) $row->total_impressions) }}
                            </td>

                            {{-- Clicks --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ number_format((int) $row->total_clicks) }}
                            </td>

                            {{-- Conversions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-center">
                                {{ number_format((int) $row->total_conversions) }}
                            </td>

                            {{-- Days with data --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-center">
                                {{ $row->days_with_data }}
                            </td>

                            {{-- Last Sync --}}
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                @if($row->last_metrics_sync_at)
                                    {{ \Carbon\Carbon::parse($row->last_metrics_sync_at)->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">{{ __('Never') }}</span>
                                @endif
                            </td>

                            {{-- Verification --}}
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @php
                                    $vStatus = $row->data_verification_status ?? 'pending';
                                    $vClass = match($vStatus) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'declined' => 'bg-red-100 text-red-800',
                                        default => 'bg-yellow-100 text-yellow-800',
                                    };
                                @endphp
                                <div class="inline-flex items-center space-x-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $vClass }}">
                                        {{ $vStatus }}
                                    </span>
                                    <button wire:click="openVerificationModal('approved', [{{ $row->ad_account_id }}])"
                                        title="{{ __('buttons.approve') }}"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded text-green-600 hover:bg-green-100 transition-colors">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                    </button>
                                    <button wire:click="openVerificationModal('declined', [{{ $row->ad_account_id }}])"
                                        title="{{ __('buttons.reject') }}"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded text-red-600 hover:bg-red-100 transition-colors">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $groupBy !== 'total' ? 14 : 13 }}" class="px-6 py-12 text-center text-gray-500">
                                {{ __('No spend data found. Adjust filters or sync metrics first.') }}
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
