<div
    class="py-6"
    x-data="{
        collapsedIndustries: new Set(),
        collapsedCategories: new Set(),
        allIndustryIds: {{ json_encode($this->hierarchyTree->pluck('industry.id')->values()) }}
    }"
>
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 rounded-xl bg-green-500/10 border border-green-500/20 p-4">
            <div class="flex items-center gap-3">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="text-sm text-green-300">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-xl bg-red-500/10 border border-red-500/20 p-4">
            <div class="flex items-center gap-3">
                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                <p class="text-sm text-red-300">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="section-title">{{ __('pages.industry_management.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.industry_management.description') }}</p>
            </div>
            <div class="flex gap-3">
                <button
                    @click="collapsedIndustries = new Set()"
                    class="btn-secondary"
                >
                    <x-heroicon-o-arrows-pointing-out class="w-4 h-4" />
                    {{ __('pages.industry_management.expand_all') }}
                </button>
                <button
                    @click="collapsedIndustries = new Set(allIndustryIds)"
                    class="btn-secondary"
                >
                    <x-heroicon-o-arrows-pointing-in class="w-4 h-4" />
                    {{ __('pages.industry_management.collapse_all') }}
                </button>
                <button
                    wire:click="openAddIndustryModal"
                    class="btn-primary"
                >
                    <x-heroicon-o-plus class="w-4 h-4" />
                    {{ __('pages.industry_management.add_industry') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="kpi-card">
            <div class="kpi-label">{{ __('pages.industry_management.total_industries') }}</div>
            <div class="kpi-value">{{ $this->industries->count() }}</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">{{ __('pages.industry_management.total_account_categories') }}</div>
            <div class="kpi-value">{{ $this->totalAccountCategories }}</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">{{ __('pages.industry_management.total_account_sub_categories') }}</div>
            <div class="kpi-value">{{ $this->totalAccountSubCategories }}</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">{{ __('pages.industry_management.total_categories') }}</div>
            <div class="kpi-value">{{ $this->totalAccountCategories + $this->totalAccountSubCategories }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-bar mb-6">
        {{-- Search --}}
        <div class="flex-1 min-w-[220px] md:min-w-[320px]">
            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.search') }}</label>
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    wire:model.live.debounce.300ms="searchQuery"
                    type="text"
                    class="input-field ps-9"
                    placeholder="{{ __('pages.industry_management.search_placeholder') }}"
                />
            </div>
        </div>

        {{-- Status Filter --}}
        <div class="w-full sm:w-48">
            <x-select2
                wire-model="statusFilter"
                :label="__('pages.industry_management.status')"
                :options="['all' => __('pages.industry_management.all'), 'active' => __('pages.industry_management.active'), 'inactive' => __('pages.industry_management.inactive')]"
                :allow-clear="false"
                :searchable="false"
            />
        </div>

        {{-- Sort By --}}
        <div class="w-full sm:w-48">
            <x-select2
                wire-model="sortBy"
                :label="__('pages.industry_management.sort_by')"
                :options="['sort_order' => __('pages.industry_management.custom_order'), 'name' => __('pages.industry_management.name'), 'categories' => __('pages.industry_management.most_categories')]"
                :allow-clear="false"
                :searchable="false"
            />
        </div>
    </div>

    {{-- Unified Hierarchy Tree --}}
    @if($this->hierarchyTree->count() > 0)
        <div class="card overflow-hidden mb-6">
            <div class="divide-y divide-white/10">
                @foreach($this->hierarchyTree as $node)
                    @php
                        $industry = $node['industry'];
                        $accountCategories = $node['accountCategories'];
                    @endphp
                    <div class="bg-gray-900" wire:key="hier-ind-{{ $industry->id }}">
                        {{-- Level 1: Industry --}}
                        <div
                            class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-950 bg-gray-950"
                            @click="collapsedIndustries.has({{ $industry->id }}) ? collapsedIndustries.delete({{ $industry->id }}) : collapsedIndustries.add({{ $industry->id }}); collapsedIndustries = new Set(collapsedIndustries)"
                        >
                            <div class="flex items-center gap-4">
                                <svg
                                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{ '-rotate-90': collapsedIndustries.has({{ $industry->id }}) }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                <div class="flex-shrink-0 bg-white/5 rounded-lg p-2.5">
                                    <x-heroicon-o-building-office class="h-5 w-5 text-gray-500" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold tracking-tight text-white">{{ $industry->display_name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $industry->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4" @click.stop>
                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-brand-500/15 text-brand-400">
                                    {{ $accountCategories->count() }} {{ __('pages.industry_management.account_categories') }}
                                </span>
                                <span class="badge-neutral">
                                    {{ $accountCategories->sum(fn($c) => $c->subCategories->count()) }} {{ __('pages.industry_management.sub_categories') }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="openAddAccountCategoryModal({{ $industry->id }})"
                                        @click.stop
                                        class="btn-secondary"
                                    >
                                        <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                        {{ __('pages.industry_management.add_account_category') }}
                                    </button>
                                    <button
                                        wire:click="editIndustry({{ $industry->id }})"
                                        class="p-2 text-gray-400 hover:text-gray-200 hover:bg-white/5 rounded-md"
                                        title="{{ __('pages.industry_management.edit') }}"
                                    >
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                    </button>
                                    <button
                                        wire:click="confirmDeleteIndustry({{ $industry->id }})"
                                        class="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-md"
                                        title="{{ __('pages.industry_management.delete') }}"
                                    >
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Level 2 & 3: Account Categories + Sub-Categories --}}
                        <div
                            x-show="!collapsedIndustries.has({{ $industry->id }})"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        >
                            @forelse($accountCategories as $accountCategory)
                                <div class="border-t border-white/8" wire:key="hier-cat-{{ $industry->id }}-{{ $accountCategory->id }}">
                                    {{-- Level 2: Account Category --}}
                                    <div
                                        class="flex items-center justify-between ps-14 pe-6 py-3 cursor-pointer hover:bg-gray-950 bg-gray-900"
                                        @click="collapsedCategories.has({{ $accountCategory->id }}) ? collapsedCategories.delete({{ $accountCategory->id }}) : collapsedCategories.add({{ $accountCategory->id }}); collapsedCategories = new Set(collapsedCategories)"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg
                                                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                :class="{ '-rotate-90': collapsedCategories.has({{ $accountCategory->id }}) }"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                            <div class="flex-shrink-0 bg-white/5 rounded p-1.5">
                                                <x-heroicon-o-folder class="h-4 w-4 text-gray-500" />
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-semibold tracking-tight text-gray-100">{{ $accountCategory->display_name }}</h4>
                                                <p class="text-xs text-gray-400">{{ $accountCategory->name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4" @click.stop>
                                            <span class="badge-neutral">
                                                {{ $accountCategory->subCategories->count() }} {{ __('pages.industry_management.sub_categories') }}
                                            </span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    wire:click="editAccountCategory({{ $accountCategory->id }})"
                                                    class="p-1.5 text-gray-400 hover:text-gray-200 hover:bg-white/5 rounded"
                                                    title="{{ __('pages.industry_management.edit') }}"
                                                >
                                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                                </button>
                                                <button
                                                    wire:click="confirmDeleteAccountCategory({{ $accountCategory->id }})"
                                                    class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded"
                                                    title="{{ __('pages.industry_management.delete') }}"
                                                >
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Level 3: Sub-Categories --}}
                                    <div
                                        x-show="!collapsedCategories.has({{ $accountCategory->id }})"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                    >
                                        <div class="ps-24 pe-6 py-2 bg-gray-950/50 border-t border-white/8">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="micro-label">{{ __('pages.industry_management.sub_categories') }}</span>
                                                <button
                                                    wire:click="openAddAccountSubCategoryModal({{ $accountCategory->id }})"
                                                    class="text-xs font-semibold text-gray-300 hover:text-white"
                                                >
                                                    + {{ __('pages.industry_management.add') }}
                                                </button>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                @forelse($accountCategory->subCategories as $sub)
                                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-900 border border-white/10 rounded-full hover:border-white/15 hover:bg-gray-950 group" wire:key="hier-sub-{{ $accountCategory->id }}-{{ $sub->id }}">
                                                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white/8 text-gray-400 text-[11px] font-semibold">
                                                            {{ substr($sub->display_name, 0, 1) }}
                                                        </span>
                                                        <span class="text-sm text-gray-200">{{ $sub->display_name }}</span>
                                                        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <button wire:click="editAccountSubCategory({{ $sub->id }})" class="p-0.5 text-gray-400 hover:text-gray-200 rounded">
                                                                <x-heroicon-o-pencil-square class="w-3 h-3" />
                                                            </button>
                                                            <button wire:click="confirmDeleteAccountSubCategory({{ $sub->id }})" class="p-0.5 text-gray-400 hover:text-red-600 rounded">
                                                                <x-heroicon-o-x-mark class="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-gray-400 italic">{{ __('pages.industry_management.no_sub_categories') }}</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="ps-14 pe-6 py-4 border-t border-white/8 bg-gray-900">
                                    <p class="text-sm text-gray-400 italic">{{ __('pages.industry_management.no_account_categories') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="card">
            <div class="empty-state">
                <x-heroicon-o-building-office class="mx-auto h-10 w-10 text-gray-300" />
                <h3 class="empty-title mt-3">{{ __('pages.industry_management.no_industries') }}</h3>
                <p class="empty-message">{{ $searchQuery ? __('pages.industry_management.try_adjusting_filters') : __('pages.industry_management.get_started') }}</p>
                <div class="mt-6">
                    <button
                        wire:click="openAddIndustryModal"
                        class="btn-primary"
                    >
                        <x-heroicon-o-plus class="w-4 h-4" />
                        {{ __('pages.industry_management.add_industry') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add/Edit Industry Modal --}}
    @if($showIndustryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeIndustryModal"></div>
                <div class="relative bg-gray-900 rounded-xl border border-white/10 max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-semibold tracking-tight text-white mb-4">
                        {{ $editingIndustryId ? __('pages.industry_management.edit_industry') : __('pages.industry_management.add_industry') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="industryDisplayName"
                                type="text"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.display_name_placeholder') }}"
                                wire:keydown.enter="saveIndustry"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="industryDescription"
                                rows="2"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeIndustryModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveIndustry"
                            @if(empty($industryDisplayName) || $saving) disabled @endif
                            class="btn-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            {{ $saving ? __('common.saving') : ($editingIndustryId ? __('common.save_changes') : __('pages.industry_management.add_industry')) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Add/Edit Category Modal --}}
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeCategoryModal"></div>
                <div class="relative bg-gray-900 rounded-xl border border-white/10 max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-semibold tracking-tight text-white mb-4">
                        {{ $editingCategoryId ? __('pages.industry_management.edit_category') : __('pages.industry_management.add_category') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="categoryDisplayName"
                                type="text"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.category_name_placeholder') }}"
                                wire:keydown.enter="saveCategory"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="categoryDescription"
                                rows="2"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeCategoryModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveCategory"
                            @if(empty($categoryDisplayName) || $saving) disabled @endif
                            class="btn-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            {{ $saving ? __('common.saving') : ($editingCategoryId ? __('common.save_changes') : __('pages.industry_management.add_category')) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Add/Edit Account Category Modal --}}
    @if($showAccountCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeAccountCategoryModal"></div>
                <div class="relative bg-gray-900 rounded-xl border border-white/10 max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-semibold tracking-tight text-white mb-4">
                        {{ $editingAccountCategoryId ? __('pages.industry_management.edit_account_category') : __('pages.industry_management.add_account_category') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <x-select2
                                wire-model="accountCategoryIndustryId"
                                :label="__('labels.industry')"
                                :options="collect($this->industries)->mapWithKeys(fn($ind) => [$ind->id => $ind->display_name])->all()"
                                :searchable="true"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="accountCategoryDisplayName"
                                type="text"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.display_name_placeholder') }}"
                                wire:keydown.enter="saveAccountCategory"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="accountCategoryDescription"
                                rows="2"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeAccountCategoryModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveAccountCategory"
                            @if(empty($accountCategoryDisplayName) || empty($accountCategoryIndustryId) || $saving) disabled @endif
                            class="btn-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            {{ $saving ? __('common.saving') : ($editingAccountCategoryId ? __('common.save_changes') : __('pages.industry_management.add_account_category')) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Add/Edit Account Sub-Category Modal --}}
    @if($showAccountSubCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeAccountSubCategoryModal"></div>
                <div class="relative bg-gray-900 rounded-xl border border-white/10 max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-semibold tracking-tight text-white mb-4">
                        {{ $editingAccountSubCategoryId ? __('pages.industry_management.edit_account_sub_category') : __('pages.industry_management.add_account_sub_category') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.parent_category') }}</label>
                            <x-select2
                                wire-model="accountSubCategoryCategoryId"
                                :options="collect($this->accountCategories)->mapWithKeys(fn($c) => [$c->id => $c->display_name])->all()"
                                :searchable="true"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="accountSubCategoryDisplayName"
                                type="text"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.category_name_placeholder') }}"
                                wire:keydown.enter="saveAccountSubCategory"
                            />
                        </div>
                        <div>
                            <label class="micro-label block mb-1.5">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="accountSubCategoryDescription"
                                rows="2"
                                class="input-field"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeAccountSubCategoryModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveAccountSubCategory"
                            @if(empty($accountSubCategoryDisplayName) || empty($accountSubCategoryCategoryId) || $saving) disabled @endif
                            class="btn-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            {{ $saving ? __('common.saving') : ($editingAccountSubCategoryId ? __('common.save_changes') : __('pages.industry_management.add_account_sub_category')) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeDeleteModal"></div>
                <div class="relative bg-gray-900 rounded-xl border border-white/10 max-w-md w-full mx-auto p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 bg-red-500/15 rounded-full p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-400" />
                        </div>
                        <div class="text-start">
                            <h3 class="text-lg font-semibold tracking-tight text-white">
                                @if(in_array($deleteType, ['account_category', 'account_sub_category']))
                                    {{ $deleteType === 'account_category' ? __('pages.industry_management.delete_account_category') : __('pages.industry_management.delete_account_sub_category') }}
                                @else
                                    {{ $deleteType === 'industry' ? __('pages.industry_management.delete_industry') : __('pages.industry_management.delete_category') }}
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ __('pages.industry_management.delete_confirm', ['name' => $deleteName]) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            wire:click="closeDeleteModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="executeDelete"
                            @if($saving) disabled @endif
                            class="btn-danger disabled:opacity-50 disabled:pointer-events-none"
                        >
                            {{ $saving ? __('common.deleting') : __('common.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
