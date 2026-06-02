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

    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ __('pages.clients.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.clients.description') }}
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <button wire:click="openWizard"
                class="inline-flex items-center px-4 py-2 border border-primary-600 text-sm font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-o-plus class="h-5 w-5 mr-2" />
                {{ __('pages.clients.create_from_accounts') }}
            </button>
            <button wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-o-plus class="h-5 w-5 mr-2" />
                {{ __('pages.clients.add_manually') }}
            </button>
        </div>
    </div>

    {{-- Overview Statistics --}}
    @if(!empty($overview))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Clients --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                        <x-heroicon-o-user-group class="h-6 w-6 text-primary-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $overview['totals']['clients'] ?? 0 }}</div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    {{ $overview['totals']['active_clients'] ?? 0 }} active
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Ad Accounts --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                        <x-heroicon-o-building-office class="h-6 w-6 text-blue-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ad Accounts</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ $overview['totals']['ad_accounts'] ?? 0 }}</div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    {{ $overview['totals']['connected_accounts'] ?? 0 }} connected
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Total Spend --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                        <x-heroicon-o-currency-dollar class="h-6 w-6 text-green-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Spend</dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                SR {{ number_format($overview['totals']['total_spend'] ?? 0, 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-gray-900">Filters</h3>
            @if($this->hasActiveFilters)
                <button wire:click="clearAllFilters" class="text-sm text-primary-600 hover:text-primary-700">
                    Clear all
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.search') }}</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ __('pages.clients.search_placeholder') }}"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                </div>
            </div>

            {{-- Date Range Filter --}}
            <div>
                <x-date-range-picker
                    wire-model-from="filterStartDate"
                    wire-model-to="filterEndDate"
                    :label="__('Date Range')"
                    align="left"
                />
            </div>

            {{-- Status Filter --}}
            <div>
                <x-select2
                    wire-model="filterStatus"
                    :label="__('labels.status')"
                    :placeholder="__('filters.all_statuses')"
                    :options="['active' => __('status.active'), 'inactive' => __('status.inactive'), 'suspended' => __('status.suspended')]"
                />
            </div>

            {{-- Industry Filter --}}
            <div>
                <x-select2
                    wire-model="filterIndustry"
                    :label="__('labels.industry')"
                    :placeholder="__('filters.all_industries')"
                    :options="collect($industries)->mapWithKeys(fn($ind) => [$ind->name => $ind->display_name])->all()"
                />
            </div>

            {{-- Subscription Tier Filter --}}
            <div>
                <x-select2
                    wire-model="filterTier"
                    :label="__('labels.subscription')"
                    :placeholder="__('filters.all_tiers')"
                    :options="['basic' => __('subscription_tiers.basic'), 'pro' => __('subscription_tiers.pro'), 'enterprise' => __('subscription_tiers.enterprise')]"
                />
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if(count($selectedClients) > 0)
        <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-primary-900">
                        {{ count($selectedClients) }} client{{ count($selectedClients) !== 1 ? 's' : '' }} selected
                    </span>
                    <button wire:click="clearSelection" class="text-sm text-primary-600 hover:text-primary-800">
                        Clear selection
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <button wire:click="bulkUpdateStatus('active')"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        Activate
                    </button>
                    <button wire:click="bulkUpdateStatus('inactive')"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                        Deactivate
                    </button>
                    <button wire:click="bulkDelete"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Clients Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <x-table-scroll max-height="600px" class="hover-primary">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="w-12 px-4 py-3 bg-gray-100">
                            <input type="checkbox" wire:click="toggleSelectAll"
                                @checked($clients->count() > 0 && count($selectedClients) === $clients->total())
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                        </th>
                        <th scope="col" wire:click="sortBy('name')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none bg-gray-100 sticky left-0 z-10 border-r border-gray-200">
                            <div class="flex items-center gap-1">
                                {{ __('labels.client') }}
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-primary-600" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-primary-600" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('industry')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none bg-gray-100">
                            <div class="flex items-center gap-1">
                                {{ __('labels.industry') }}
                                @if($sortField === 'industry')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-primary-600" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-primary-600" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('status')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none bg-gray-100">
                            <div class="flex items-center gap-1">
                                {{ __('labels.status') }}
                                @if($sortField === 'status')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-primary-600" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-primary-600" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('subscription_tier')"
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 select-none bg-gray-100">
                            <div class="flex items-center gap-1">
                                {{ __('labels.subscription') }}
                                @if($sortField === 'subscription_tier')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-primary-600" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-primary-600" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-100">
                            {{ __('labels.ad_accounts') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-100">
                            {{ __('labels.total_spend') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-100">
                            {{ __('labels.contract') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-100">
                            {{ __('labels.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clients as $client)
                        <tr class="even:bg-gray-50 hover:bg-primary-50 transition-colors duration-150 cursor-pointer border-l-2 border-l-transparent hover:border-l-primary-500"
                            wire:click="viewClientDashboard({{ $client->id }})">
                            {{-- Checkbox --}}
                            <td class="px-4 py-4" wire:click.stop>
                                <input type="checkbox" value="{{ $client->id }}" wire:model.live="selectedClients"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                            </td>

                            {{-- Client Name --}}
                            <td class="px-4 py-4 sticky left-0 z-10 sticky-col border-r border-gray-200">
                                <div class="flex items-center">
                                    @if($client->logo_url)
                                        <img src="{{ $client->logo_url }}" alt="{{ $client->name }}"
                                            class="h-8 w-8 rounded-full object-cover mr-3 flex-shrink-0" />
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center mr-3 flex-shrink-0">
                                            <span class="text-xs font-medium text-primary-600">{{ strtoupper(substr($client->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                            {{ $client->name }}
                                        </div>
                                        @if($client->contact_email)
                                            <div class="text-xs text-gray-500">{{ $client->contact_email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Industry (inline editable) --}}
                            <td class="px-4 py-4 text-sm text-gray-500" wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'industry', $event.target.value)"
                                    class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1">
                                    <option value="">-</option>
                                    @foreach($industries as $ind)
                                        <option value="{{ $ind->name }}" @selected($client->industry === $ind->name)>{{ $ind->display_name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Status (inline editable) --}}
                            <td class="px-4 py-4" wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'status', $event.target.value)"
                                    class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1">
                                    <option value="active" @selected($client->status === 'active')>Active</option>
                                    <option value="inactive" @selected($client->status === 'inactive')>Inactive</option>
                                    <option value="suspended" @selected($client->status === 'suspended')>Suspended</option>
                                </select>
                            </td>

                            {{-- Subscription Tier (inline editable) --}}
                            <td class="px-4 py-4 text-sm text-gray-500" wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'subscription_tier', $event.target.value)"
                                    class="text-xs border-gray-300 focus:ring-primary-500 focus:border-primary-500 rounded-md py-1">
                                    <option value="">-</option>
                                    <option value="basic" @selected($client->subscription_tier === 'basic')>Basic</option>
                                    <option value="pro" @selected($client->subscription_tier === 'pro')>Pro</option>
                                    <option value="enterprise" @selected($client->subscription_tier === 'enterprise')>Enterprise</option>
                                </select>
                            </td>

                            {{-- Ad Accounts Count --}}
                            <td class="px-4 py-4 text-sm text-gray-900 font-medium">
                                {{ $client->ad_accounts_count ?? 0 }}
                            </td>

                            {{-- Total Spend --}}
                            <td class="px-4 py-4 text-sm text-gray-900 font-medium">
                                SR {{ number_format($client->total_spend ?? 0, 2) }}
                            </td>

                            {{-- Contract --}}
                            <td class="px-4 py-4">
                                @if($client->contract_end_date)
                                    @php
                                        $daysLeft = $client->days_until_contract_expires;
                                        if ($daysLeft === null) {
                                            $contractClass = 'text-gray-400';
                                            $contractText = 'N/A';
                                        } elseif ($daysLeft < 0) {
                                            $contractClass = 'text-red-600';
                                            $contractText = 'Expired';
                                        } elseif ($daysLeft <= 30) {
                                            $contractClass = 'text-orange-600';
                                            $contractText = $daysLeft . ' days left';
                                        } elseif ($daysLeft <= 90) {
                                            $contractClass = 'text-yellow-600';
                                            $contractText = $daysLeft . ' days left';
                                        } else {
                                            $contractClass = 'text-green-600';
                                            $contractText = $daysLeft . ' days left';
                                        }
                                    @endphp
                                    <span class="text-sm font-medium {{ $contractClass }}">
                                        {{ $contractText }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">{{ __('labels.no_contract') }}</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-4 text-right" wire:click.stop>
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="viewClientDashboard({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 rounded hover:bg-gray-100"
                                        title="{{ __('tooltips.view_dashboard') }}">
                                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                                    </button>
                                    <button wire:click="openEditModal({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 rounded hover:bg-gray-100"
                                        title="{{ __('tooltips.edit_client') }}">
                                        <x-heroicon-o-pencil class="w-5 h-5" />
                                    </button>
                                    <button wire:click="confirmDelete({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100"
                                        title="Delete client">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <x-heroicon-o-user-group class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.clients.no_clients') }}</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    @if($this->hasActiveFilters)
                                        {{ __('pages.clients.adjust_filters') }}
                                    @else
                                        {{ __('pages.clients.get_started') }}
                                    @endif
                                </p>
                                <div class="mt-6">
                                    <button wire:click="openCreateModal"
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                        <x-heroicon-o-plus class="h-5 w-5 mr-2" />
                                        {{ __('pages.clients.add_manually') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-scroll>

        {{-- Pagination --}}
        <x-pagination-wrapper :paginator="$clients" :per-page-options="[12, 25, 50, 100]" />
    </div>

    {{-- Create/Edit Form Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            x-data x-on:keydown.escape.window="$wire.closeFormModal()">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $editingClientId ? 'Edit Client' : 'Create New Client' }}
                    </h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="saveClient" class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label for="formName" class="block text-sm font-medium text-gray-700">Name *</label>
                        <input wire:model="formName" type="text" id="formName"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="Client name" />
                        @error('formName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact Email --}}
                    <div>
                        <label for="formEmail" class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <input wire:model="formEmail" type="email" id="formEmail"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="client@example.com" />
                        @error('formEmail')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Country --}}
                    <div>
                        <label for="formCountry" class="block text-sm font-medium text-gray-700">Country</label>
                        <input wire:model="formCountry" type="text" id="formCountry"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            placeholder="e.g. SA, AE, US" />
                        @error('formCountry')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="formStatus" class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="formStatus" id="formStatus"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('formStatus')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" wire:click="closeFormModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            {{ $editingClientId ? 'Update Client' : 'Create Client' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Dialog --}}
    @if($showDeleteDialog)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            x-data x-on:keydown.escape.window="$wire.closeDeleteDialog()">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Delete {{ $deletingClientName }}?</h3>
                    @if($deletingClientAccountsCount > 0)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4">
                            <p class="text-sm text-yellow-800">
                                This client has <strong>{{ $deletingClientAccountsCount }}</strong> ad account(s).
                                The client will be deactivated instead of deleted.
                            </p>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to delete this client? This action cannot be undone.
                        </p>
                    @endif
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="closeDeleteDialog"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="deleteClient"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                        {{ $deletingClientAccountsCount > 0 ? 'Deactivate' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Client Wizard Modal --}}
    @if($showWizard)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            x-data x-on:keydown.escape.window="$wire.closeWizard()">
            <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-lg rounded-md bg-white mb-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Create Client from Ad Accounts</h3>
                    <button wire:click="closeWizard" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Step Indicator --}}
                <div class="mb-8">
                    <div class="flex items-center justify-center space-x-4">
                        @php
                            $steps = ['Select Accounts', 'Review Suggestions', 'Complete Details', 'Review & Create'];
                        @endphp
                        @foreach($steps as $index => $stepLabel)
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium
                                    {{ $wizardStep > $index ? 'bg-green-500 text-white' : ($wizardStep === $index ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600') }}">
                                    @if($wizardStep > $index)
                                        <x-heroicon-o-check class="h-5 w-5" />
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <span class="ml-2 text-sm {{ $wizardStep === $index ? 'text-primary-600 font-medium' : 'text-gray-500' }}">
                                    {{ $stepLabel }}
                                </span>
                                @if($index < count($steps) - 1)
                                    <div class="mx-4 w-12 h-0.5 {{ $wizardStep > $index ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Step 0: Select Accounts --}}
                @if($wizardStep === 0)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search unassigned accounts</label>
                            <input wire:model.live.debounce.300ms="wizardAccountSearch" type="text"
                                placeholder="Search by name..."
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                        </div>

                        @if(count($unassignedAccounts) === 0)
                            <div class="text-center py-8 text-gray-500">
                                <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-300 mb-2" />
                                <p>No unassigned ad accounts available.</p>
                            </div>
                        @else
                            <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-200">
                                @foreach($unassignedAccounts as $account)
                                    @if(!$wizardAccountSearch || str_contains(strtolower($account['account_name']), strtolower($wizardAccountSearch)))
                                        <label class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" value="{{ $account['id'] }}" wire:model.live="wizardSelectedAccountIds"
                                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" />
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $account['account_name'] }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ ucfirst($account['platform']) }}
                                                    @if($account['industry'])
                                                        &bull; {{ $account['industry'] }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $account['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $account['status'] }}
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            @if(count($wizardSelectedAccountIds) > 0)
                                <p class="text-sm text-primary-600 font-medium">
                                    {{ count($wizardSelectedAccountIds) }} account(s) selected
                                </p>
                            @endif
                        @endif
                    </div>
                @endif

                {{-- Step 1: Review Suggestions --}}
                @if($wizardStep === 1)
                    <div class="space-y-4">
                        @if($wizardSuggestions && isset($wizardSuggestions['suggested']))
                            <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <x-heroicon-o-light-bulb class="h-5 w-5 text-green-500 mt-0.5" />
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-green-800">Auto-detected information</h4>
                                        <p class="text-xs text-green-600 mt-1">
                                            The following details were suggested based on the selected ad accounts.
                                            You can modify them in the next step.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <dl class="space-y-3">
                                @if(!empty($wizardSuggestions['suggested']['name']))
                                    <div class="bg-gray-50 rounded-md p-3">
                                        <dt class="text-xs font-medium text-gray-500">Suggested Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $wizardSuggestions['suggested']['name'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardSuggestions['suggested']['industry']))
                                    <div class="bg-gray-50 rounded-md p-3">
                                        <dt class="text-xs font-medium text-gray-500">Industry</dt>
                                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $wizardSuggestions['suggested']['industry']) }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardSuggestions['suggested']['description']))
                                    <div class="bg-gray-50 rounded-md p-3">
                                        <dt class="text-xs font-medium text-gray-500">Description</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $wizardSuggestions['suggested']['description'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        @else
                            <div class="text-center py-4 text-gray-500">
                                <p class="text-sm">No suggestions could be generated. You can enter the details manually.</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 2: Complete Details --}}
                @if($wizardStep === 2)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Client Name *</label>
                            <input wire:model="wizardFormData.name" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Client name" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model="wizardFormData.description" rows="2"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Brief description"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Industry</label>
                            <select wire:model="wizardFormData.industry"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <option value="">Select Industry</option>
                                @foreach($industries as $ind)
                                    <option value="{{ $ind->name }}">{{ $ind->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subscription Tier</label>
                            <select wire:model="wizardFormData.subscription_tier"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <option value="basic">Basic</option>
                                <option value="pro">Pro</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Monthly Budget</label>
                            <input wire:model="wizardFormData.monthly_budget" type="number" step="0.01"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="0.00" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                            <input wire:model="wizardFormData.contact_email" type="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="email@example.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                            <input wire:model="wizardFormData.contact_person" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Full name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact Phone</label>
                            <input wire:model="wizardFormData.contact_phone" type="tel"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="+966..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Website</label>
                            <input wire:model="wizardFormData.website" type="url"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="https://..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Billing Email</label>
                            <input wire:model="wizardFormData.billing_email" type="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="billing@example.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contract Start Date</label>
                            <input wire:model="wizardFormData.contract_start_date" type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contract End Date</label>
                            <input wire:model="wizardFormData.contract_end_date" type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea wire:model="wizardFormData.notes" rows="2"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Review & Create --}}
                @if($wizardStep === 3)
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Review Client Information</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $wizardFormData['name'] }}</dd>
                                </div>
                                @if(!empty($wizardFormData['industry']))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Industry</dt>
                                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $wizardFormData['industry']) }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['subscription_tier']))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Tier</dt>
                                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $wizardFormData['subscription_tier'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['contact_email']))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $wizardFormData['contact_email'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['contact_person']))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500">Contact Person</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $wizardFormData['contact_person'] }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Ad Accounts</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ count($wizardSelectedAccountIds) }} account(s)</dd>
                                </div>
                            </dl>
                        </div>

                        @if(!empty($wizardFormData['description']))
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-xs font-medium text-gray-500 mb-1">Description</dt>
                                <dd class="text-sm text-gray-900">{{ $wizardFormData['description'] }}</dd>
                            </div>
                        @endif

                        {{-- Selected Accounts Preview --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-xs font-medium text-gray-500 mb-2">Selected Ad Accounts</h4>
                            <div class="space-y-2">
                                @foreach($unassignedAccounts as $account)
                                    @if(in_array($account['id'], $wizardSelectedAccountIds))
                                        <div class="flex items-center justify-between py-1">
                                            <span class="text-sm text-gray-900">{{ $account['account_name'] }}</span>
                                            <span class="text-xs text-gray-500 capitalize">{{ $account['platform'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Wizard Navigation --}}
                <div class="flex items-center justify-between mt-8 pt-4 border-t border-gray-200">
                    <div>
                        @if($wizardStep > 0)
                            <button wire:click="wizardPreviousStep"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Previous
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center space-x-3">
                        <button wire:click="closeWizard"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        @if($wizardStep < 3)
                            <button wire:click="wizardNextStep"
                                @disabled($wizardStep === 0 && count($wizardSelectedAccountIds) === 0)
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        @else
                            <button wire:click="createClientFromWizard"
                                @disabled($wizardCreating || empty($wizardFormData['name']))
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="createClientFromWizard">Create Client</span>
                                <span wire:loading wire:target="createClientFromWizard">Creating...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
