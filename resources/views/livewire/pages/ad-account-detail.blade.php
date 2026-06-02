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
    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(!$adAccount)
        <div class="text-center py-12">
            <p class="text-gray-500">Ad account not found.</p>
            <a href="{{ route('ad-accounts') }}" wire:navigate class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-800">
                <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                Back to accounts
            </a>
        </div>
    @else
        {{-- Header with Back Button --}}
        <div class="flex items-center space-x-4">
            <a href="{{ route('ad-accounts') }}" wire:navigate
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                {{ __('pages.ad_accounts.back_to_accounts') }}
            </a>

            <div class="flex-1">
                <div class="flex items-center space-x-3">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $adAccount['account_name'] }}</h1>
                        <p class="text-sm text-gray-500">{{ $adAccount['platform'] }} &bull; {{ $adAccount['external_account_id'] }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $adAccount['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $adAccount['status'] }}
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <x-date-range-picker
                    wire-model-from="filterStartDate"
                    wire-model-to="filterEndDate"
                    :label="__('Date Range')"
                />
            </div>
        </div>

        {{-- Account Details Card --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('pages.ad_accounts.account_info') }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Account details and classification</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('pages.ad_accounts.external_account_id') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $adAccount['external_account_id'] }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('labels.platform') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 capitalize">{{ $adAccount['platform'] }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('labels.industry') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <select wire:model="editableAccount.industry" wire:change="updateAccountInfo"
                                class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">{{ __('placeholders.select_industry') }}</option>
                                @foreach($industries as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('labels.category') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <select wire:model="editableAccount.category" wire:change="updateAccountInfo"
                                class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select Category</option>
                                @foreach($availableCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('labels.currency') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $adAccount['currency'] }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">{{ __('pages.ad_accounts.total_campaigns') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ count($campaigns) }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Total Spend (SAR)</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="font-semibold text-green-600">SR {{ number_format($adAccount['total_spend_sar'] ?? 0, 2) }}</span>
                            @if($filterStartDate && $filterEndDate)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $filterStartDate }} to {{ $filterEndDate }}
                                </span>
                            @else
                                <span class="ml-2 text-xs text-gray-400">(All Time)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Last Metrics Sync</dt>
                        <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                            @if($adAccount['last_metrics_sync_at'])
                                <span class="text-gray-900">{{ \Carbon\Carbon::parse($adAccount['last_metrics_sync_at'])->format('M d, Y H:i') }}</span>
                                <span class="text-gray-500 text-xs ml-2">({{ \Carbon\Carbon::parse($adAccount['last_metrics_sync_at'])->diffForHumans() }})</span>
                            @else
                                <span class="text-gray-400 italic">Never synced</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Campaigns Section --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('pages.ad_accounts.campaigns_section') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">Configure campaign objectives, funnel stages, and user journeys</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button wire:click="autoDetectCategories" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                            <x-heroicon-o-sparkles class="h-4 w-4 mr-2" />
                            Auto-detect Categories
                        </button>
                        @if($syncPolling || $syncProgress)
                            <button wire:click="$set('showSyncModal', true)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm bg-white space-x-2 hover:bg-gray-50 cursor-pointer">
                                @if(($syncProgress['status'] ?? '') === 'queued')
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-yellow-500"></span>
                                    </span>
                                    <span class="text-yellow-700">Queued...</span>
                                @elseif(($syncProgress['status'] ?? '') === 'running')
                                    <x-heroicon-o-arrow-path class="h-4 w-4 text-primary-600 animate-spin" />
                                    <span class="text-primary-700 truncate max-w-[180px]">{{ $syncProgress['current_step'] ?? 'Syncing...' }}</span>
                                @elseif(($syncProgress['status'] ?? '') === 'completed')
                                    <x-heroicon-s-check-circle class="h-4 w-4 text-green-500" />
                                    <span class="text-green-700">Sync complete</span>
                                @elseif(($syncProgress['status'] ?? '') === 'failed')
                                    <x-heroicon-s-x-circle class="h-4 w-4 text-red-500" />
                                    <span class="text-red-700">Sync failed</span>
                                @endif
                            </button>
                        @else
                            <button wire:click="syncCampaigns" wire:loading.attr="disabled"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                                <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" wire:loading.class="animate-spin" wire:target="syncCampaigns" />
                                <span wire:loading.remove wire:target="syncCampaigns">Sync Campaigns</span>
                                <span wire:loading wire:target="syncCampaigns">Syncing...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bulk Actions Bar --}}
            @if(count($selectedCampaigns) > 0)
                <div class="mx-4 mb-4 mt-4 p-3 bg-primary-50 border border-primary-200 rounded-lg flex items-center gap-4">
                    <span class="text-sm font-medium text-primary-700">
                        {{ count($selectedCampaigns) }} campaign(s) selected
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Sub-Category:</label>
                        <select wire:model="bulkCategory"
                            class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Select category...</option>
                            @foreach($campaignCategories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <button wire:click="bulkUpdateCampaignCategory" @disabled(!$bulkCategory)
                            class="px-3 py-1.5 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Apply
                        </button>
                        <button wire:click="autoDetectCategories"
                            class="px-3 py-1.5 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700">
                            Auto-detect
                        </button>
                    </div>
                    <button wire:click="$set('selectedCampaigns', [])" class="ml-auto text-sm text-gray-500 hover:text-gray-700">
                        Clear selection
                    </button>
                </div>
            @endif

            {{-- Campaigns Table --}}
            <div class="overflow-auto max-h-[600px] relative">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-20 shadow-sm">
                        <tr>
                            <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50 w-10">
                                <input type="checkbox" wire:click="toggleSelectAll" @checked($selectAll)
                                    class="h-4 w-4 text-primary-600 border-gray-300 rounded cursor-pointer" />
                            </th>
                            <th scope="col" wire:click="sortBy('name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50 min-w-[250px]">
                                <div class="flex items-center space-x-1">
                                    <span>Campaign Name</span>
                                    @if($sortField === 'name')
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
                            <th scope="col" wire:click="sortBy('total_spend')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                                <div class="flex items-center space-x-1"><span>Spend</span></div>
                            </th>
                            <th scope="col" wire:click="sortBy('total_spend_sar')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 bg-gray-50">
                                <div class="flex items-center space-x-1"><span>SAR</span></div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Funnel</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Sub-Category</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Journey</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Status</th>
                            <th scope="col" class="relative px-4 py-3 bg-gray-50">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($sortedCampaigns as $campaign)
                            <tr class="group hover:bg-gray-50">
                                <td class="px-2 py-3 whitespace-nowrap">
                                    <input type="checkbox" value="{{ $campaign['id'] }}" wire:model.live="selectedCampaigns"
                                        class="h-4 w-4 text-primary-600 border-gray-300 rounded cursor-pointer" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('campaign-metrics', ['campaignId' => $campaign['id']]) }}" wire:navigate
                                        class="text-sm font-medium text-primary-600 hover:text-primary-800 cursor-pointer hover:underline">
                                        {{ $campaign['name'] }}
                                    </a>
                                    <div class="text-xs text-gray-500">ID: {{ $campaign['external_campaign_id'] }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ $adAccount['currency'] }} {{ number_format($campaign['total_spend'], 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-green-600">
                                        SR {{ number_format($campaign['total_spend_sar'], 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'funnel_stage', $event.target.value)"
                                        class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="" @selected(empty($campaign['funnel_stage']))>Select</option>
                                        <option value="TOF" @selected(($campaign['funnel_stage'] ?? '') === 'TOF')>TOF</option>
                                        <option value="MOF" @selected(($campaign['funnel_stage'] ?? '') === 'MOF')>MOF</option>
                                        <option value="BOF" @selected(($campaign['funnel_stage'] ?? '') === 'BOF')>BOF</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'sub_industry', $event.target.value)"
                                        class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="" @selected(empty($campaign['sub_industry']))>Select</option>
                                        @foreach($campaignCategories as $category)
                                            <option value="{{ $category }}" @selected(($campaign['sub_industry'] ?? '') === $category)>{{ $category }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select wire:change="updateCampaign({{ $campaign['id'] }}, 'user_journey', $event.target.value)"
                                        class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="" @selected(empty($campaign['user_journey']))>Select</option>
                                        <option value="instant_form" @selected(($campaign['user_journey'] ?? '') === 'instant_form')>Instant Form</option>
                                        <option value="landing_page" @selected(($campaign['user_journey'] ?? '') === 'landing_page')>Landing Page</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $campaign['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $campaign['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-medium">
                                    <a href="{{ route('campaign-metrics', ['campaignId' => $campaign['id']]) }}" wire:navigate
                                        class="text-primary-600 hover:text-primary-900">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No campaigns found. Try syncing campaigns from the platform.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($campaigns) > 0)
                        <tfoot class="bg-gray-100 font-semibold sticky bottom-0">
                            <tr>
                                <td class="px-2 py-3 bg-gray-100"></td>
                                <td class="px-4 py-3 whitespace-nowrap bg-gray-100 text-sm text-gray-900">
                                    Total ({{ count($campaigns) }} items)
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-blue-700">
                                    {{ $adAccount['currency'] }} {{ number_format($this->totalSpendOriginal, 2) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-green-700">
                                    SR {{ number_format($this->totalSpendSar, 2) }}
                                </td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeSyncModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    @php
                        $progressStatus = $syncProgress['status'] ?? null;
                        $platformName = ucfirst($adAccount['platform'] ?? 'Platform');
                    @endphp

                    {{-- QUEUED STATE --}}
                    @if($progressStatus === 'queued')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                                <div class="h-5 w-5 rounded-full bg-yellow-400 animate-pulse"></div>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $platformName }} Sync
                                </h3>
                                <p class="mt-2 text-sm text-yellow-600 font-medium">
                                    Queued, starting shortly...
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Waiting for queue worker to pick up the job
                                </p>
                            </div>
                        </div>

                    {{-- RUNNING STATE --}}
                    @elseif($progressStatus === 'running')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                                <x-heroicon-o-arrow-path class="h-6 w-6 text-blue-600 animate-spin" />
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $platformName }} Sync
                                </h3>
                                <p class="mt-2 text-sm text-blue-600 font-medium">
                                    {{ $syncProgress['current_step'] ?? 'Syncing...' }}
                                </p>
                            </div>

                            {{-- Step log --}}
                            @if(!empty($syncProgress['steps']))
                                <div class="mt-4 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50" x-data x-effect="$el.scrollTop = $el.scrollHeight">
                                    <div class="space-y-2">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-3 w-3 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-700">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                    {{-- COMPLETED STATE --}}
                    @elseif($progressStatus === 'completed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <x-heroicon-s-check-circle class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Sync Complete
                            </h3>
                            <p class="mt-1 text-sm text-green-600 font-medium">
                                {{ $platformName }} campaigns synced successfully.
                            </p>
                        </div>

                        {{-- Stats grid --}}
                        @if(!empty($syncProgress['stats']))
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @if(isset($syncProgress['stats']['accounts_processed']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ $syncProgress['stats']['accounts_processed'] }}</p>
                                        <p class="text-xs text-gray-500">Accounts</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['campaigns_synced']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ $syncProgress['stats']['campaigns_synced'] }}</p>
                                        <p class="text-xs text-gray-500">Campaigns</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['metrics_synced']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ number_format($syncProgress['stats']['metrics_synced']) }}</p>
                                        <p class="text-xs text-gray-500">Metrics Synced</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['errors']) && $syncProgress['stats']['errors'] > 0)
                                    <div class="bg-red-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-red-600">{{ $syncProgress['stats']['errors'] }}</p>
                                        <p class="text-xs text-red-500">Errors</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Show sync log ({{ count($syncProgress['steps']) }} steps)</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-600">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6">
                            <button wire:click="closeSyncModal" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                Done
                            </button>
                        </div>

                    {{-- FAILED STATE --}}
                    @elseif($progressStatus === 'failed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <x-heroicon-s-x-circle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Sync Failed
                            </h3>
                            <p class="mt-2 text-sm text-red-600">
                                {{ $syncProgress['error'] ?? 'An unknown error occurred.' }}
                            </p>
                        </div>

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Show sync log ({{ count($syncProgress['steps']) }} steps)</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-600">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6 flex space-x-3">
                            <button wire:click="closeSyncModal" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Close
                            </button>
                            <button wire:click="retrySyncCampaigns" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" />
                                Retry
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
