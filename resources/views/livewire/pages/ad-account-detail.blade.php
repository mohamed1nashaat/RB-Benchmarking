<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm font-medium text-green-400">{{ session('message') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl bg-red-500/10 border border-red-500/20 p-4">
            <div class="flex">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                <p class="ms-3 text-sm font-medium text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(!$adAccount)
        <div class="empty-state">
            <p class="empty-message">{{ __('pages.ad_accounts.ad_account_not_found') }}</p>
            <a href="{{ route('ad-accounts') }}" wire:navigate class="btn-secondary mt-4 inline-flex">
                <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />
                {{ __('pages.ad_accounts.back_to_accounts') }}
            </a>
        </div>
    @else
        {{-- Header with Back Button --}}
        <div class="flex items-center gap-x-4">
            <a href="{{ route('ad-accounts') }}" wire:navigate class="btn-secondary">
                <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />
                {{ __('pages.ad_accounts.back_to_accounts') }}
            </a>

            <div class="flex-1">
                <div class="flex items-center gap-x-3">
                    <div>
                        <h1 class="section-title">{{ $adAccount['account_name'] }}</h1>
                        <p class="text-sm text-gray-500">{{ $adAccount['platform'] }} &bull; {{ $adAccount['external_account_id'] }}</p>
                    </div>
                    <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $adAccount['status'] === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-white/8 text-gray-400' }}">
                        {{ $adAccount['status'] }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-x-3">
                {{-- Quick action CTAs --}}
                <a href="{{ route('report-builder') }}?ad_account_id={{ $accountId }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-brand-500/10 text-brand-400 hover:bg-brand-500/20 border border-brand-500/20 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                    </svg>
                    New Report
                </a>
                <a href="{{ route('media-planner-create') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-white/5 text-gray-300 hover:bg-white/10 border border-white/10 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                    New Plan
                </a>
                <x-date-range-picker
                    wire-model-from="filterStartDate"
                    wire-model-to="filterEndDate"
                    :label="__('Date Range')"
                />
            </div>
        </div>

        {{-- Account Details Card --}}
        <div class="card p-6">
            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.ad_accounts.account_info') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('pages.ad_accounts.account_info_description') }}</p>
            <dl class="mt-6 border-t border-white/8">
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('pages.ad_accounts.external_account_id') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ $adAccount['external_account_id'] }}</dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('labels.platform') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2 capitalize">{{ $adAccount['platform'] }}</dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('labels.industry') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                        <select wire:model="editableAccount.industry" wire:change="updateAccountInfo"
                            class="input-field">
                            <option value="">{{ __('placeholders.select_industry') }}</option>
                            @foreach($industries as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('labels.category') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                        <select wire:model="editableAccount.category" wire:change="updateAccountInfo"
                            class="input-field">
                            <option value="">{{ __('pages.ad_accounts.select_category') }}</option>
                            @foreach($availableCategories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('labels.currency') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ $adAccount['currency'] }}</dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('pages.ad_accounts.total_campaigns') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">{{ count($campaigns) }}</dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('pages.ad_accounts.total_spend_sar') }}</dt>
                    <dd class="mt-1 text-sm text-white sm:mt-0 sm:col-span-2">
                        <span class="font-semibold text-white">SR {{ number_format($adAccount['total_spend_sar'] ?? 0, 2) }}</span>
                        @if($filterStartDate && $filterEndDate)
                            <span class="ms-2 rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-brand-500/15 text-brand-400">
                                {{ $filterStartDate }} {{ __('common.to') }} {{ $filterEndDate }}
                            </span>
                        @else
                            <span class="ms-2 text-xs text-gray-400">({{ __('date.all_time') }})</span>
                        @endif
                    </dd>
                </div>
                <div class="py-4 border-b border-white/8 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="micro-label">{{ __('pages.ad_accounts.last_metrics_sync') }}</dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                        @if($adAccount['last_metrics_sync_at'])
                            <span class="text-white">{{ \Carbon\Carbon::parse($adAccount['last_metrics_sync_at'])->format('M d, Y H:i') }}</span>
                            <span class="text-gray-500 text-xs ms-2">({{ \Carbon\Carbon::parse($adAccount['last_metrics_sync_at'])->diffForHumans() }})</span>
                        @else
                            <span class="text-gray-400 italic">{{ __('pages.ad_accounts.never_synced') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Campaigns Section --}}
        <div class="card overflow-hidden">
            <div class="p-6 border-b border-white/8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.ad_accounts.campaigns_section') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('pages.ad_accounts.campaigns_subtitle') }}</p>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <button wire:click="autoDetectCategories" wire:loading.attr="disabled"
                            class="btn-secondary">
                            <x-heroicon-o-sparkles class="h-4 w-4 me-2" />
                            {{ __('pages.ad_accounts.auto_detect_categories') }}
                        </button>
                        @if($syncPolling || $syncProgress)
                            <button wire:click="$set('showSyncModal', true)"
                                class="btn-secondary gap-x-2 cursor-pointer">
                                @if(($syncProgress['status'] ?? '') === 'queued')
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                                    </span>
                                    <span class="text-amber-400">{{ __('pages.ad_accounts.queued') }}</span>
                                @elseif(($syncProgress['status'] ?? '') === 'running')
                                    <x-heroicon-o-arrow-path class="h-4 w-4 text-white animate-spin" />
                                    <span class="text-gray-200 truncate max-w-[180px]">{{ $syncProgress['current_step'] ?? __('pages.ad_accounts.syncing') }}</span>
                                @elseif(($syncProgress['status'] ?? '') === 'completed')
                                    <x-heroicon-s-check-circle class="h-4 w-4 text-green-400" />
                                    <span class="text-green-400">{{ __('pages.ad_accounts.sync_complete') }}</span>
                                @elseif(($syncProgress['status'] ?? '') === 'failed')
                                    <x-heroicon-s-x-circle class="h-4 w-4 text-red-400" />
                                    <span class="text-red-400">{{ __('pages.ad_accounts.sync_failed') }}</span>
                                @endif
                            </button>
                        @else
                            <button wire:click="syncCampaigns" wire:loading.attr="disabled"
                                class="btn-primary">
                                <x-heroicon-o-arrow-path class="h-4 w-4 me-2" wire:loading.class="animate-spin" wire:target="syncCampaigns" />
                                <span wire:loading.remove wire:target="syncCampaigns">{{ __('pages.ad_accounts.sync_campaigns') }}</span>
                                <span wire:loading wire:target="syncCampaigns">{{ __('pages.ad_accounts.syncing') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(count($selectedCampaigns) > 0)
                <div class="mx-4 mb-4 mt-4 p-3 bg-brand-500/10 border border-brand-500/20 rounded-xl flex items-center gap-4">
                    <span class="text-sm font-medium text-brand-400">
                        {{ __('pages.ad_accounts.campaigns_selected', ['count' => count($selectedCampaigns)]) }}
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="micro-label">{{ __('Sub-Category') }}:</label>
                        <select wire:model="bulkCategory"
                            class="input-field">
                            <option value="">{{ __('pages.ad_accounts.select_category_placeholder') }}</option>
                            @foreach($campaignCategories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <button wire:click="bulkUpdateCampaignCategory" @disabled(!$bulkCategory)
                            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('common.apply') }}
                        </button>
                        <button wire:click="autoDetectCategories"
                            class="btn-secondary">
                            {{ __('table.auto_detect') }}
                        </button>
                    </div>
                    <button wire:click="$set('selectedCampaigns', [])" class="ms-auto btn-ghost">
                        {{ __('pages.ad_accounts.clear_selection') }}
                    </button>
                </div>
            @endif

            {{-- Campaigns Table --}}
            <div class="overflow-auto max-h-[600px] relative">
                <table class="data-table">
                    <thead class="sticky top-0 z-20">
                        <tr>
                            <th scope="col" class="w-10">
                                <input type="checkbox" wire:click="toggleSelectAll" @checked($selectAll)
                                    class="h-4 w-4 rounded border-white/15 text-white cursor-pointer" />
                            </th>
                            <th scope="col" wire:click="sortBy('name')" class="min-w-[250px] cursor-pointer">
                                <div class="flex items-center gap-x-1">
                                    <span>{{ __('labels.campaign_name') }}</span>
                                    @if($sortField === 'name')
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
                            <th scope="col" wire:click="sortBy('total_spend')" class="cursor-pointer">
                                <div class="flex items-center gap-x-1"><span>{{ __('kpis.spend') }}</span></div>
                            </th>
                            <th scope="col" wire:click="sortBy('total_spend_sar')" class="cursor-pointer">
                                <div class="flex items-center gap-x-1"><span>SAR</span></div>
                            </th>
                            <th scope="col">{{ __('pages.ad_accounts.funnel') }}</th>
                            <th scope="col">{{ __('labels.sub_category') }}</th>
                            <th scope="col">{{ __('pages.ad_accounts.journey') }}</th>
                            <th scope="col">{{ __('labels.status') }}</th>
                            <th scope="col" class="relative">
                                <span class="sr-only">{{ __('labels.actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sortedCampaigns as $campaign)
                            <tr class="group">
                                <td>
                                    <input type="checkbox" value="{{ $campaign['id'] }}" wire:model.live="selectedCampaigns"
                                        class="h-4 w-4 rounded border-white/15 text-white cursor-pointer" />
                                </td>
                                <td>
                                    <a href="{{ route('campaign-metrics', ['campaignId' => $campaign['id']]) }}" wire:navigate
                                        class="text-sm font-medium text-white hover:underline cursor-pointer">
                                        {{ $campaign['name'] }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ __('common.id') }}: {{ $campaign['external_campaign_id'] }}</div>
                                </td>
                                <td>
                                    <span class="text-sm font-semibold text-white">
                                        {{ $adAccount['currency'] }} {{ number_format($campaign['total_spend'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-sm font-semibold text-white">
                                        SR {{ number_format($campaign['total_spend_sar'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'funnel_stage', $event.target.value)"
                                        class="input-field">
                                        <option value="" @selected(empty($campaign['funnel_stage']))>{{ __('labels.select') }}</option>
                                        <option value="TOF" @selected(($campaign['funnel_stage'] ?? '') === 'TOF')>TOF</option>
                                        <option value="MOF" @selected(($campaign['funnel_stage'] ?? '') === 'MOF')>MOF</option>
                                        <option value="BOF" @selected(($campaign['funnel_stage'] ?? '') === 'BOF')>BOF</option>
                                    </select>
                                </td>
                                <td>
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'sub_industry', $event.target.value)"
                                        class="input-field"
                                        title="{{ __('tooltips.sub_categories_source') }}">
                                        <option value="" @selected(empty($campaign['sub_industry']))>{{ __('labels.select') }}</option>
                                        @if(!empty($campaign['sub_industry']) && !in_array($campaign['sub_industry'], $campaignCategories))
                                            <option value="{{ $campaign['sub_industry'] }}" selected>{{ $campaign['sub_industry'] }} {{ __('common.legacy') }}</option>
                                        @endif
                                        @foreach($campaignCategories as $category)
                                            <option value="{{ $category }}" @selected(($campaign['sub_industry'] ?? '') === $category)>{{ $category }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'user_journey', $event.target.value)"
                                        class="input-field">
                                        <option value="" @selected(empty($campaign['user_journey']))>{{ __('labels.select') }}</option>
                                        <option value="instant_form" @selected(($campaign['user_journey'] ?? '') === 'instant_form')>{{ __('user_journeys.instant_form') }}</option>
                                        <option value="landing_page" @selected(($campaign['user_journey'] ?? '') === 'landing_page')>{{ __('user_journeys.landing_page') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $campaign['status'] === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-white/8 text-gray-400' }}">
                                        {{ $campaign['status'] }}
                                    </span>
                                </td>
                                <td class="text-end text-xs font-medium">
                                    <a href="{{ route('campaign-metrics', ['campaignId' => $campaign['id']]) }}" wire:navigate
                                        class="text-white hover:underline">
                                        {{ __('common.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <p class="empty-message">{{ __('pages.ad_accounts.no_campaigns_found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($campaigns) > 0)
                        <tfoot class="sticky bottom-0 bg-gray-950 font-semibold text-sm text-white">
                            <tr>
                                <td></td>
                                <td>
                                    {{ __('pages.ad_accounts.total_items', ['count' => count($campaigns)]) }}
                                </td>
                                <td>
                                    {{ $adAccount['currency'] }} {{ number_format($this->totalSpendOriginal, 2) }}
                                </td>
                                <td>
                                    SR {{ number_format($this->totalSpendSar, 2) }}
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- Sync Progress Modal --}}
    @if($showSyncModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape.window="$wire.closeSyncModal()">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeSyncModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-gray-900 border border-white/10 rounded-xl px-4 pt-5 pb-4 text-start overflow-hidden sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    @php
                        $progressStatus = $syncProgress['status'] ?? null;
                        $platformName = ucfirst($adAccount['platform'] ?? __('labels.platform'));
                    @endphp

                    {{-- QUEUED STATE --}}
                    @if($progressStatus === 'queued')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-500/15">
                                <div class="h-5 w-5 rounded-full bg-amber-500 animate-pulse"></div>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-semibold tracking-tight text-white">
                                    {{ __('pages.ad_accounts.sync_for_platform', ['platform' => $platformName]) }}
                                </h3>
                                <p class="mt-2 text-sm text-amber-400 font-medium">
                                    {{ __('pages.ad_accounts.sync_queued') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ __('pages.ad_accounts.sync_waiting') }}
                                </p>
                            </div>
                        </div>

                    {{-- RUNNING STATE --}}
                    @elseif($progressStatus === 'running')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-brand-500/15">
                                <x-heroicon-o-arrow-path class="h-6 w-6 text-brand-400 animate-spin" />
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-semibold tracking-tight text-white">
                                    {{ __('pages.ad_accounts.sync_for_platform', ['platform' => $platformName]) }}
                                </h3>
                                <p class="mt-2 text-sm text-brand-400 font-medium">
                                    {{ $syncProgress['current_step'] ?? __('pages.ad_accounts.syncing') }}
                                </p>
                            </div>

                            {{-- Step log --}}
                            @if(!empty($syncProgress['steps']))
                                <div class="mt-4 max-h-48 overflow-y-auto border border-white/10 rounded-xl p-3 bg-gray-950" x-data x-effect="$el.scrollTop = $el.scrollHeight">
                                    <div class="space-y-2">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 me-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                                    @else
                                                        <div class="h-3 w-3 rounded-full bg-gray-500"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-200">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                    {{-- COMPLETED STATE --}}
                    @elseif($progressStatus === 'completed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-500/15">
                            <x-heroicon-s-check-circle class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __('pages.ad_accounts.sync_complete') }}</h3>
                            <p class="mt-1 text-sm text-green-400 font-medium">
                                {{ __('pages.ad_accounts.sync_success_message', ['platform' => $platformName]) }}
                            </p>
                        </div>

                        {{-- Stats grid --}}
                        @if(!empty($syncProgress['stats']))
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @if(isset($syncProgress['stats']['accounts_processed']))
                                    <div class="bg-gray-950 rounded-xl p-3 text-center">
                                        <p class="stat-num">{{ $syncProgress['stats']['accounts_processed'] }}</p>
                                        <p class="text-xs text-gray-500">{{ __('labels.accounts') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['campaigns_synced']))
                                    <div class="bg-gray-950 rounded-xl p-3 text-center">
                                        <p class="stat-num">{{ $syncProgress['stats']['campaigns_synced'] }}</p>
                                        <p class="text-xs text-gray-500">{{ __('labels.campaigns') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['metrics_synced']))
                                    <div class="bg-gray-950 rounded-xl p-3 text-center">
                                        <p class="stat-num">{{ number_format($syncProgress['stats']['metrics_synced']) }}</p>
                                        <p class="text-xs text-gray-500">{{ __('pages.ad_accounts.metrics_synced') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['errors']) && $syncProgress['stats']['errors'] > 0)
                                    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-center">
                                        <p class="stat-num text-red-400">{{ $syncProgress['stats']['errors'] }}</p>
                                        <p class="text-xs text-red-400">{{ __('common.errors') }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-200">{{ __('pages.ad_accounts.show_sync_log', ['count' => count($syncProgress['steps'])]) }}</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-white/10 rounded-xl p-3 bg-gray-950">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 me-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-500"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-gray-500"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-300">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6">
                            <button wire:click="closeSyncModal" class="btn-primary w-full justify-center">
                                {{ __('common.done') }}
                            </button>
                        </div>

                    {{-- FAILED STATE --}}
                    @elseif($progressStatus === 'failed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/15">
                            <x-heroicon-s-x-circle class="h-6 w-6 text-red-400" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-semibold tracking-tight text-white">{{ __("Sync Failed") }}</h3>
                            <p class="mt-2 text-sm text-red-400">
                                {{ $syncProgress['error'] ?? __('pages.ad_accounts.sync_unknown_error') }}
                            </p>
                        </div>

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-200">{{ trans_choice('pages.ad_accounts.show_sync_log', count($syncProgress['steps'])) }}</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-white/10 rounded-xl p-3 bg-gray-950">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 me-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-500"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-gray-500"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-300">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6 flex gap-x-3">
                            <button wire:click="closeSyncModal" class="btn-secondary flex-1 justify-center">
                                {{ __('common.close') }}
                            </button>
                            <button wire:click="retrySyncCampaigns" class="btn-danger flex-1 justify-center">
                                <x-heroicon-o-arrow-path class="h-4 w-4 me-2" />
                                {{ __('common.retry') }}
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
