<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-xl border border-green-500/30 bg-green-500/15 p-4">
            <div class="flex items-center">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm font-medium text-green-400">{{ session('message') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl border border-red-500/30 bg-red-500/15 p-4">
            <div class="flex items-center">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                <p class="ms-3 text-sm font-medium text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight text-white">
                {{ __('pages.clients.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.clients.description') }}
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button wire:click="openWizard"
                class="btn-secondary">
                <x-heroicon-o-plus class="h-5 w-5 me-2" />
                {{ __('pages.clients.create_from_accounts') }}
            </button>
            <button wire:click="openCreateModal"
                class="btn-primary">
                <x-heroicon-o-plus class="h-5 w-5 me-2" />
                {{ __('pages.clients.add_manually') }}
            </button>
        </div>
    </div>

    {{-- Overview Statistics --}}
    @if(!empty($overview))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Total Clients --}}
            <div class="kpi-card">
                <div class="kpi-label">{{ __('pages.clients.total_clients') }}</div>
                <div class="flex items-baseline gap-2">
                    <div class="kpi-value">{{ $overview['totals']['clients'] ?? 0 }}</div>
                    <div class="kpi-change positive">
                        {{ $overview['totals']['active_clients'] ?? 0 }} {{ __('status.active') }}
                    </div>
                </div>
            </div>

            {{-- Ad Accounts --}}
            <div class="kpi-card">
                <div class="kpi-label">{{ __('labels.ad_accounts') }}</div>
                <div class="flex items-baseline gap-2">
                    <div class="kpi-value">{{ $overview['totals']['ad_accounts'] ?? 0 }}</div>
                    <div class="kpi-change positive">
                        {{ $overview['totals']['connected_accounts'] ?? 0 }} {{ __('common.connected') }}
                    </div>
                </div>
            </div>

            {{-- Total Spend --}}
            <div class="kpi-card">
                <div class="kpi-label">{{ __('labels.total_spend') }}</div>
                <div class="kpi-value">
                    SR {{ number_format($overview['totals']['total_spend'] ?? 0, 2) }}
                </div>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="filter-bar">
        <div class="flex items-center justify-between mb-4">
            <h3 class="micro-label">{{ __('common.filters') }}</h3>
            @if($this->hasActiveFilters)
                <button wire:click="clearAllFilters" class="text-sm text-gray-500 hover:text-white">{{ __('common.clear_all') }}</button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div>
                <label class="micro-label block mb-1">{{ __('labels.search') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ __('pages.clients.search_placeholder') }}"
                        class="input-field ps-10" />
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
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-x-3">
                    <span class="text-sm font-medium text-white">
                        {{ trans_choice('common.selected_count', count($selectedClients)) }}
                    </span>
                    <button wire:click="clearSelection" class="text-sm text-gray-500 hover:text-white">{{ __('common.clear_selection') }}</button>
                </div>
                <div class="flex items-center gap-x-2">
                    <button wire:click="bulkUpdateStatus('active')"
                        class="btn-secondary">
                        {{ __('common.activate') }}
                    </button>
                    <button wire:click="bulkUpdateStatus('inactive')"
                        class="btn-secondary">
                        {{ __('common.deactivate') }}
                    </button>
                    <button wire:click="bulkDelete"
                        class="btn-danger">
                        {{ __('common.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Clients Table --}}
    <div class="bg-gray-900 rounded-xl border border-white/10 overflow-hidden">
        <x-table-scroll max-height="600px" class="hover-primary">
            <table class="data-table">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="w-12">
                            <input type="checkbox" wire:click="toggleSelectAll"
                                @checked($clients->count() > 0 && count($selectedClients) === $clients->total())
                                class="h-4 w-4 text-white border-white/15 rounded" />
                        </th>
                        <th scope="col" wire:click="sortBy('name')"
                            class="text-start cursor-pointer hover:bg-white/5 select-none sticky start-0 z-10">
                            <div class="flex items-center gap-1">
                                {{ __('labels.client') }}
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-white" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-white" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('industry')"
                            class="text-start cursor-pointer hover:bg-white/5 select-none">
                            <div class="flex items-center gap-1">
                                {{ __('labels.industry') }}
                                @if($sortField === 'industry')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-white" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-white" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('status')"
                            class="text-start cursor-pointer hover:bg-white/5 select-none">
                            <div class="flex items-center gap-1">
                                {{ __('labels.status') }}
                                @if($sortField === 'status')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-white" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-white" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" wire:click="sortBy('subscription_tier')"
                            class="text-start cursor-pointer hover:bg-white/5 select-none">
                            <div class="flex items-center gap-1">
                                {{ __('labels.subscription') }}
                                @if($sortField === 'subscription_tier')
                                    @if($sortDirection === 'asc')
                                        <x-heroicon-o-chevron-up class="h-4 w-4 text-white" />
                                    @else
                                        <x-heroicon-o-chevron-down class="h-4 w-4 text-white" />
                                    @endif
                                @else
                                    <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="text-start">
                            {{ __('labels.ad_accounts') }}
                        </th>
                        <th scope="col" class="text-start">
                            {{ __('labels.total_spend') }}
                        </th>
                        <th scope="col" class="text-start">
                            {{ __('labels.contract') }}
                        </th>
                        <th scope="col" class="text-end">
                            {{ __('labels.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr class="cursor-pointer"
                            wire:click="viewClientDashboard({{ $client->id }})">
                            {{-- Checkbox --}}
                            <td wire:click.stop>
                                <input type="checkbox" value="{{ $client->id }}" wire:model.live="selectedClients"
                                    class="h-4 w-4 text-white border-white/15 rounded" />
                            </td>

                            {{-- Client Name --}}
                            <td class="sticky start-0 z-10 sticky-col bg-gray-900">
                                <div class="flex items-center">
                                    @if($client->logo_url)
                                        <img src="{{ $client->logo_url }}" alt="{{ $client->name }}"
                                            class="h-8 w-8 rounded-full object-cover me-3 flex-shrink-0" />
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-white/5 flex items-center justify-center me-3 flex-shrink-0">
                                            <span class="text-xs font-medium text-gray-500">{{ strtoupper(substr($client->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-white">
                                            {{ $client->name }}
                                        </div>
                                        @if($client->contact_email)
                                            <div class="text-xs text-gray-500">{{ $client->contact_email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Industry (inline editable) --}}
                            <td class="text-sm text-gray-500" wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'industry', $event.target.value)"
                                    class="input-field text-xs py-1">
                                    <option value="">-</option>
                                    @foreach($industries as $ind)
                                        <option value="{{ $ind->name }}" @selected($client->industry === $ind->name)>{{ $ind->display_name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Status (inline editable) --}}
                            <td wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'status', $event.target.value)"
                                    class="input-field text-xs py-1">
                                    <option value="active" @selected($client->status === 'active')>{{ __('status.active') }}</option>
                                    <option value="inactive" @selected($client->status === 'inactive')>{{ __('status.inactive') }}</option>
                                    <option value="suspended" @selected($client->status === 'suspended')>{{ __('status.suspended') }}</option>
                                </select>
                            </td>

                            {{-- Subscription Tier (inline editable) --}}
                            <td class="text-sm text-gray-500" wire:click.stop>
                                <select wire:change="updateClientField({{ $client->id }}, 'subscription_tier', $event.target.value)"
                                    class="input-field text-xs py-1">
                                    <option value="">-</option>
                                    <option value="basic" @selected($client->subscription_tier === 'basic')>{{ __('subscription_tiers.basic') }}</option>
                                    <option value="pro" @selected($client->subscription_tier === 'pro')>{{ __('subscription_tiers.pro') }}</option>
                                    <option value="enterprise" @selected($client->subscription_tier === 'enterprise')>{{ __('subscription_tiers.enterprise') }}</option>
                                </select>
                            </td>

                            {{-- Ad Accounts Count --}}
                            <td class="text-sm text-white font-medium">
                                {{ $client->ad_accounts_count ?? 0 }}
                            </td>

                            {{-- Total Spend --}}
                            <td class="text-sm text-white font-medium">
                                SR {{ number_format($client->total_spend ?? 0, 2) }}
                            </td>

                            {{-- Contract --}}
                            <td>
                                @if($client->contract_end_date)
                                    @php
                                        $daysLeft = $client->days_until_contract_expires;
                                        if ($daysLeft === null) {
                                            $contractClass = 'text-gray-400';
                                            $contractText = __('N/A');
                                        } elseif ($daysLeft < 0) {
                                            $contractClass = 'text-red-600';
                                            $contractText = __('Expired');
                                        } else {
                                            $contractClass = $daysLeft <= 30 ? 'text-orange-600' : ($daysLeft <= 90 ? 'text-yellow-600' : 'text-green-600');
                                            $contractText = trans_choice('pages.clients.days_left', $daysLeft, ['count' => $daysLeft]);
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
                            <td class="text-end" wire:click.stop>
                                <div class="flex items-center justify-end gap-x-2">
                                    <button wire:click="viewClientDashboard({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-white/5"
                                        title="{{ __('tooltips.view_dashboard') }}">
                                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                                    </button>
                                    <a href="{{ route('client-dashboard', ['id' => $client->id]) }}?reports"
                                        class="p-1.5 text-gray-400 hover:text-brand-400 rounded hover:bg-white/5"
                                        title="Report Designer">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                                        </svg>
                                    </a>
                                    <button wire:click="openEditModal({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-white/5"
                                        title="{{ __('tooltips.edit_client') }}">
                                        <x-heroicon-o-pencil class="w-5 h-5" />
                                    </button>
                                    <button wire:click="confirmDelete({{ $client->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-white/5"
                                        title="{{ __('pages.clients.delete_client') }}">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12">
                                <div class="empty-state">
                                    <x-heroicon-o-user-group class="mx-auto h-12 w-12 text-gray-300" />
                                    <h3 class="empty-title">{{ __('pages.clients.no_clients') }}</h3>
                                    <p class="empty-message">
                                        @if($this->hasActiveFilters)
                                            {{ __('pages.clients.adjust_filters') }}
                                        @else
                                            {{ __('pages.clients.get_started') }}
                                        @endif
                                    </p>
                                    <div class="mt-6">
                                        <button wire:click="openCreateModal"
                                            class="btn-primary">
                                            <x-heroicon-o-plus class="h-5 w-5 me-2" />
                                            {{ __('pages.clients.add_manually') }}
                                        </button>
                                    </div>
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
            <div class="relative top-20 mx-auto p-5 border border-white/10 rounded-xl w-full max-w-lg bg-gray-900">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold tracking-tight text-white">
                        {{ $editingClientId ? __('Edit Client') : __('modals.create_client') }}
                    </h3>
                    <button wire:click="closeFormModal" class="text-gray-400 hover:text-gray-300">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="saveClient" class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label for="formName" class="micro-label block mb-1">{{ __('labels.name') }} <span class="text-red-500">*</span></label>
                        <input wire:model="formName" type="text" id="formName"
                            class="input-field"
                            placeholder="{{ __('placeholders.client_name') }}" />
                        @error('formName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact Email --}}
                    <div>
                        <label for="formEmail" class="micro-label block mb-1">{{ __('labels.contact_email') }}</label>
                        <input wire:model="formEmail" type="email" id="formEmail"
                            class="input-field"
                            placeholder="client@example.com" />
                        @error('formEmail')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Country --}}
                    <div>
                        <label for="formCountry" class="micro-label block mb-1">{{ __('labels.country') }}</label>
                        <input wire:model="formCountry" type="text" id="formCountry"
                            class="input-field"
                            placeholder="{{ __('placeholders.country_example') }}" />
                        @error('formCountry')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="formStatus" class="micro-label block mb-1">{{ __('labels.status') }}</label>
                        <select wire:model="formStatus" id="formStatus"
                            class="input-field">
                            <option value="active">{{ __('status.active') }}</option>
                            <option value="inactive">{{ __('status.inactive') }}</option>
                            <option value="suspended">{{ __('status.suspended') }}</option>
                        </select>
                        @error('formStatus')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-end gap-x-3 pt-4 border-t border-white/10">
                        <button type="button" wire:click="closeFormModal"
                            class="btn-secondary">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                            class="btn-primary">
                            {{ $editingClientId ? __('Update Client') : __('Create Client') }}
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
            <div class="relative top-20 mx-auto p-5 border border-white/10 rounded-xl w-full max-w-md bg-gray-900">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/15 mb-4">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-400" />
                    </div>
                    <h3 class="text-lg font-semibold tracking-tight text-white mb-2">{{ __('pages.clients.delete_title', ['name' => $deletingClientName]) }}</h3>
                    @if($deletingClientAccountsCount > 0)
                        <div class="bg-amber-500/15 border border-amber-500/30 rounded-xl p-3 mb-4">
                            <p class="text-sm text-amber-400">{{ trans_choice('pages.clients.deactivate_instead_of_delete_message', $deletingClientAccountsCount, ['count' => $deletingClientAccountsCount]) }}</p>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mb-4">{{ __('pages.clients.delete_confirm') }}</p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-x-3">
                    <button wire:click="closeDeleteDialog"
                        class="btn-secondary">
                        Cancel
                    </button>
                    <button wire:click="deleteClient"
                        class="btn-danger">
                        {{ $deletingClientAccountsCount > 0 ? __('common.deactivate') : __('common.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Client Wizard Modal --}}
    @if($showWizard)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            x-data x-on:keydown.escape.window="$wire.closeWizard()">
            <div class="relative top-10 mx-auto p-6 border border-white/10 rounded-xl w-full max-w-3xl bg-gray-900 mb-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.clients.create_from_accounts') }}</h3>
                    <button wire:click="closeWizard" class="text-gray-400 hover:text-gray-300">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Step Indicator --}}
                <div class="mb-8">
                    <div class="flex items-center justify-center gap-x-4">
                        @php
                            $steps = [__('Select Accounts'), __('Review Suggestions'), __('Complete Details'), __('Review & Create')];
                        @endphp
                        @foreach($steps as $index => $stepLabel)<div class="flex items-center">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $wizardStep > $index ? 'bg-brand-500/15 text-brand-400' : ($wizardStep === $index ? 'bg-brand-600 text-white' : 'bg-white/8 text-gray-300') }}">
                                    @if($wizardStep > $index)
                                        <x-heroicon-o-check class="h-5 w-5" />
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <span class="ms-2 text-sm {{ $wizardStep === $index ? 'text-white font-medium' : 'text-gray-500' }}">
                                    {{ $stepLabel }}
                                </span>
                                @if($index < count($steps) - 1)
                                    <div class="mx-4 w-12 h-0.5 {{ $wizardStep > $index ? 'bg-brand-500' : 'bg-white/8' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Step 0: Select Accounts --}}
                @if($wizardStep === 0)
                    <div class="space-y-4">
                        <div>
                            <label class="micro-label block mb-1">{{ __('pages.clients.search_unassigned_accounts') }}</label>
                            <input wire:model.live.debounce.300ms="wizardAccountSearch" type="text"
                                placeholder="{{ __('placeholders.search_by_name') }}"
                                class="input-field" />
                        </div>

                        @if(count($unassignedAccounts) === 0)
                            <div class="empty-state">
                                <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-300" />
                                <p class="empty-message">{{ __('pages.clients.no_unassigned_accounts') }}</p>
                            </div>
                        @else
                            <div class="max-h-64 overflow-y-auto border border-white/10 rounded-xl divide-y divide-white/10">
                                @foreach($unassignedAccounts as $account)
                                    @if(!$wizardAccountSearch || str_contains(strtolower($account['account_name']), strtolower($wizardAccountSearch)))
                                        <label class="flex items-center px-4 py-3 hover:bg-gray-950 cursor-pointer">
                                            <input type="checkbox" value="{{ $account['id'] }}" wire:model.live="wizardSelectedAccountIds"
                                                class="h-4 w-4 text-white border-white/15 rounded" />
                                            <div class="ms-3 flex-1">
                                                <p class="text-sm font-medium text-white">{{ $account['account_name'] }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ ucfirst($account['platform']) }}
                                                    @if($account['industry'])
                                                        &bull; {{ $account['industry'] }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ $account['status'] === 'active' ? 'bg-green-500/15 text-green-400' : 'badge-neutral' }}">
                                                {{ __('status.'.$account['status']) }}
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            @if(count($wizardSelectedAccountIds) > 0)
                                <p class="text-sm text-white font-medium">
                                    {{ trans_choice('pages.clients.accounts_selected', count($wizardSelectedAccountIds)) }}
                                </p>
                            @endif
                        @endif
                    </div>
                @endif

                {{-- Step 1: Review Suggestions --}}
                @if($wizardStep === 1)
                    <div class="space-y-4">
                        @if($wizardSuggestions && isset($wizardSuggestions['suggested']))
                            <div class="bg-brand-500/10 border border-brand-500/20 rounded-xl p-4 mb-4">
                                <div class="flex">
                                    <x-heroicon-o-light-bulb class="h-5 w-5 text-green-400 mt-0.5" />
                                    <div class="ms-3">
                                        <h4 class="text-sm font-medium text-green-400">{{ __('pages.clients.auto_detected_info') }}</h4>
                                        <p class="text-xs text-green-400 mt-1">
                                            {{ __('pages.clients.suggestions_description') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <dl class="space-y-3">
                                @if(!empty($wizardSuggestions['suggested']['name']))
                                    <div class="bg-gray-900 border border-white/10 rounded-xl p-3">
                                        <dt class="micro-label">{{ __('pages.clients.suggested_name') }}</dt>
                                        <dd class="mt-1 text-sm text-white">{{ $wizardSuggestions['suggested']['name'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardSuggestions['suggested']['industry']))
                                    <div class="bg-gray-900 border border-white/10 rounded-xl p-3">
                                        <dt class="micro-label">{{ __('labels.industry') }}</dt>
                                        <dd class="mt-1 text-sm text-white capitalize">{{ str_replace('_', ' ', $wizardSuggestions['suggested']['industry']) }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardSuggestions['suggested']['description']))
                                    <div class="bg-gray-900 border border-white/10 rounded-xl p-3">
                                        <dt class="micro-label">{{ __('labels.description') }}</dt>
                                        <dd class="mt-1 text-sm text-white">{{ $wizardSuggestions['suggested']['description'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        @else
                            <div class="empty-state">
                                <p class="empty-message">{{ __('pages.clients.no_suggestions') }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 2: Complete Details --}}
                @if($wizardStep === 2)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="micro-label block mb-1">{{ __('labels.client_name') }} <span class="text-red-500">*</span></label>
                            <input wire:model="wizardFormData.name" type="text"
                                class="input-field"
                                placeholder="{{ __('placeholders.client_name') }}" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="micro-label block mb-1">{{ __('Description') }}</label>
                            <textarea wire:model="wizardFormData.description" rows="2"
                                class="input-field"
                                placeholder="{{ __('placeholders.brief_description') }}"></textarea>
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.industry') }}</label>
                            <select wire:model="wizardFormData.industry"
                                class="input-field">
                                <option value="">{{ __('placeholders.select_industry') }}</option>
                                @foreach($industries as $ind)
                                    <option value="{{ $ind->name }}">{{ $ind->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.subscription') }}</label>
                            <select wire:model="wizardFormData.subscription_tier"
                                class="input-field">
                                <option value="basic">{{ __('subscription_tiers.basic') }}</option>
                                <option value="pro">{{ __('subscription_tiers.pro') }}</option>
                                <option value="enterprise">{{ __('subscription_tiers.enterprise') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.monthly_budget') }}</label>
                            <input wire:model="wizardFormData.monthly_budget" type="number" step="0.01"
                                class="input-field"
                                placeholder="0.00" />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.contact_email') }}</label>
                            <input wire:model="wizardFormData.contact_email" type="email"
                                class="input-field"
                                placeholder="email@example.com" />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.contact_person') }}</label>
                            <input wire:model="wizardFormData.contact_person" type="text"
                                class="input-field"
                                placeholder="{{ __('placeholders.full_name') }}" />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.contact_phone') }}</label>
                            <input wire:model="wizardFormData.contact_phone" type="tel"
                                class="input-field"
                                placeholder="+966..." />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.website') }}</label>
                            <input wire:model="wizardFormData.website" type="url"
                                class="input-field"
                                placeholder="https://..." />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.billing_email') }}</label>
                            <input wire:model="wizardFormData.billing_email" type="email"
                                class="input-field"
                                placeholder="billing@example.com" />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.contract_start_date') }}</label>
                            <input wire:model="wizardFormData.contract_start_date" type="date"
                                class="input-field" />
                        </div>
                        <div>
                            <label class="micro-label block mb-1">{{ __('labels.contract_end_date') }}</label>
                            <input wire:model="wizardFormData.contract_end_date" type="date"
                                class="input-field" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="micro-label block mb-1">{{ __('labels.notes') }}</label>
                            <textarea wire:model="wizardFormData.notes" rows="2"
                                class="input-field"
                                placeholder="{{ __('placeholders.additional_notes') }}"></textarea>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Review & Create --}}
                @if($wizardStep === 3)
                    <div class="space-y-4">
                        <div class="bg-gray-900 border border-white/10 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-white mb-3">{{ __('pages.clients.review_client_info') }}</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <dt class="micro-label">{{ __('labels.name') }}</dt>
                                    <dd class="mt-1 text-sm text-white">{{ $wizardFormData['name'] }}</dd>
                                </div>
                                @if(!empty($wizardFormData['industry']))
                                    <div>
                                        <dt class="micro-label">{{ __('labels.industry') }}</dt>
                                        <dd class="mt-1 text-sm text-white capitalize">{{ str_replace('_', ' ', $wizardFormData['industry']) }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['subscription_tier']))
                                    <div>
                                        <dt class="micro-label">{{ __('labels.tier') }}</dt>
                                        <dd class="mt-1 text-sm text-white capitalize">{{ $wizardFormData['subscription_tier'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['contact_email']))
                                    <div>
                                        <dt class="micro-label">{{ __('labels.email') }}</dt>
                                        <dd class="mt-1 text-sm text-white">{{ $wizardFormData['contact_email'] }}</dd>
                                    </div>
                                @endif
                                @if(!empty($wizardFormData['contact_person']))
                                    <div>
                                        <dt class="micro-label">{{ __('labels.contact_person') }}</dt>
                                        <dd class="mt-1 text-sm text-white">{{ $wizardFormData['contact_person'] }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="micro-label">{{ __('labels.ad_accounts') }}</dt>
                                    <dd class="mt-1 text-sm text-white">{{ trans_choice('common.accounts', count($wizardSelectedAccountIds)) }}</dd>
                                </div>
                            </dl>
                        </div>

                        @if(!empty($wizardFormData['description']))
                            <div class="bg-gray-900 border border-white/10 rounded-xl p-4">
                                <dt class="micro-label mb-1">{{ __('labels.description') }}</dt>
                                <dd class="text-sm text-white">{{ $wizardFormData['description'] }}</dd>
                            </div>
                        @endif

                        {{-- Selected Accounts Preview --}}
                        <div class="bg-gray-900 border border-white/10 rounded-xl p-4">
                            <h4 class="micro-label mb-2">{{ __('pages.clients.selected_ad_accounts') }}</h4>
                            <div class="space-y-2">
                                @foreach($unassignedAccounts as $account)
                                    @if(in_array($account['id'], $wizardSelectedAccountIds))
                                        <div class="flex items-center justify-between py-1">
                                            <span class="text-sm text-white">{{ $account['account_name'] }}</span>
                                            <span class="text-xs text-gray-500 capitalize">{{ __('platforms.'.$account['platform']) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Wizard Navigation --}}
                <div class="flex items-center justify-between mt-8 pt-4 border-t border-white/10">
                    <div>
                        @if($wizardStep > 0)
                            <button wire:click="wizardPreviousStep"
                                class="btn-secondary">
                                {{ __('buttons.previous') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-x-3">
                        <button wire:click="closeWizard"
                            class="btn-secondary">
                            {{ __('Cancel') }}
                        </button>
                        @if($wizardStep < 3)
                            <button wire:click="wizardNextStep"
                                @disabled($wizardStep === 0 && count($wizardSelectedAccountIds) === 0)
                                class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ __('buttons.next') }}
                            </button>
                        @else
                            <button wire:click="createClientFromWizard"
                                @disabled($wizardCreating || empty($wizardFormData['name']))
                                class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="createClientFromWizard">{{ __('Create Client') }}</span>
                                <span wire:loading wire:target="createClientFromWizard">{{ __('common.creating') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
