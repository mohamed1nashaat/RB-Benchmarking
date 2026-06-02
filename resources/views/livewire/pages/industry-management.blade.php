<div class="py-6" x-data="{ collapsedIndustries: new Set() }">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ml-3 text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                <p class="ml-3 text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.industry_management.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.industry_management.description') }}</p>
            </div>
            <div class="flex gap-3">
                <button
                    @click="collapsedIndustries = new Set()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                    <x-heroicon-o-arrows-pointing-out class="w-4 h-4 mr-2" />
                    {{ __('pages.industry_management.expand_all') }}
                </button>
                <button
                    @click="() => { let s = new Set(); @this.industries.forEach(i => s.add(i.id)); collapsedIndustries = s; }"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                    <x-heroicon-o-arrows-pointing-in class="w-4 h-4 mr-2" />
                    {{ __('pages.industry_management.collapse_all') }}
                </button>
                <button
                    wire:click="openAddIndustryModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                >
                    <x-heroicon-o-plus class="w-5 h-5 mr-2" />
                    {{ __('pages.industry_management.add_industry') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Total Industries --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                    <x-heroicon-o-building-office class="h-6 w-6 text-primary-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_management.total_industries') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $this->industries->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Account Categories --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                    <x-heroicon-o-tag class="h-6 w-6 text-primary-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_management.account_categories') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $this->totalSubIndustries }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sub-Categories --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-secondary-100 rounded-md p-3">
                    <x-heroicon-o-clipboard-document-list class="h-6 w-6 text-secondary-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_management.campaign_categories') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $this->totalCampaignCategories }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Total Categories --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
                    <x-heroicon-o-squares-2x2 class="h-6 w-6 text-gray-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">{{ __('pages.industry_management.total_categories') }}</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $this->totalSubIndustries + $this->totalCampaignCategories }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pages.industry_management.search') }}</label>
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        wire:model.live.debounce.300ms="searchQuery"
                        type="text"
                        class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="{{ __('pages.industry_management.search_placeholder') }}"
                    />
                </div>
            </div>

            {{-- Status Filter --}}
            <div>
                <x-select2
                    wire-model="statusFilter"
                    :label="__('pages.industry_management.status')"
                    :options="['all' => __('pages.industry_management.all'), 'active' => __('pages.industry_management.active'), 'inactive' => __('pages.industry_management.inactive')]"
                    :allow-clear="false"
                    :searchable="false"
                />
            </div>

            {{-- Sort By --}}
            <div>
                <x-select2
                    wire-model="sortBy"
                    :label="__('pages.industry_management.sort_by')"
                    :options="['sort_order' => __('pages.industry_management.custom_order'), 'name' => __('pages.industry_management.name'), 'categories' => __('pages.industry_management.most_categories')]"
                    :allow-clear="false"
                    :searchable="false"
                />
            </div>
        </div>
    </div>

    {{-- Industries List --}}
    @if($this->filteredIndustries->count() > 0)
        <div class="space-y-4">
            @foreach($this->filteredIndustries as $industry)
                <div class="bg-white rounded-lg shadow overflow-hidden" wire:key="industry-{{ $industry->id }}">
                    {{-- Industry Header --}}
                    <div
                        class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50"
                        @click="collapsedIndustries.has({{ $industry->id }}) ? collapsedIndustries.delete({{ $industry->id }}) : collapsedIndustries.add({{ $industry->id }}); collapsedIndustries = new Set(collapsedIndustries)"
                    >
                        <div class="flex items-center gap-4">
                            {{-- Collapse Arrow --}}
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="{ '-rotate-90': collapsedIndustries.has({{ $industry->id }}) }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>

                            {{-- Industry Icon --}}
                            <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                                <span class="text-lg font-bold text-primary-600">{{ substr($industry->display_name, 0, 1) }}</span>
                            </div>

                            {{-- Industry Info --}}
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $industry->display_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $industry->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4" @click.stop>
                            {{-- Category Counts --}}
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700">
                                    {{ $industry->subIndustries->count() }} {{ __('pages.industry_management.account') }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-700">
                                    {{ $industry->campaignCategories->count() }} {{ __('pages.industry_management.campaign') }}
                                </span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="editIndustry({{ $industry->id }})"
                                    class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-md"
                                    title="{{ __('pages.industry_management.edit') }}"
                                >
                                    <x-heroicon-o-pencil-square class="w-5 h-5" />
                                </button>
                                <button
                                    wire:click="confirmDeleteIndustry({{ $industry->id }})"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md"
                                    title="{{ __('pages.industry_management.delete') }}"
                                >
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Categories Content --}}
                    <div
                        x-show="!collapsedIndustries.has({{ $industry->id }})"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="border-t border-gray-200"
                    >
                        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                            {{-- Account Categories --}}
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                        <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                                        {{ __('pages.industry_management.account_categories') }}
                                    </h4>
                                    <button
                                        wire:click="openAddCategoryModal({{ $industry->id }}, 'sub_industry')"
                                        class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                                    >
                                        + {{ __('pages.industry_management.add') }}
                                    </button>
                                </div>

                                @forelse($industry->subIndustries as $cat)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md hover:bg-gray-100 group mb-2" wire:key="sub-{{ $cat->id }}">
                                        <span class="text-sm text-gray-700">{{ $cat->display_name }}</span>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100">
                                            <button wire:click="editCategory({{ $cat->id }}, 'sub_industry')" class="p-1 text-gray-400 hover:text-primary-600 rounded">
                                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                                            </button>
                                            <button wire:click="confirmDeleteCategory({{ $cat->id }}, 'sub_industry')" class="p-1 text-gray-400 hover:text-red-600 rounded">
                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400 italic py-4 text-center">
                                        {{ __('pages.industry_management.no_account_categories') }}
                                    </p>
                                @endforelse
                            </div>

                            {{-- Sub-Categories --}}
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                        <div class="w-2 h-2 bg-secondary-500 rounded-full"></div>
                                        {{ __('pages.industry_management.campaign_categories') }}
                                    </h4>
                                    <button
                                        wire:click="openAddCategoryModal({{ $industry->id }}, 'campaign_category')"
                                        class="text-sm text-secondary-600 hover:text-secondary-700 font-medium"
                                    >
                                        + {{ __('pages.industry_management.add') }}
                                    </button>
                                </div>

                                @forelse($industry->campaignCategories as $cat)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md hover:bg-gray-100 group mb-2" wire:key="camp-{{ $cat->id }}">
                                        <span class="text-sm text-gray-700">{{ $cat->display_name }}</span>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100">
                                            <button wire:click="editCategory({{ $cat->id }}, 'campaign_category')" class="p-1 text-gray-400 hover:text-secondary-600 rounded">
                                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                                            </button>
                                            <button wire:click="confirmDeleteCategory({{ $cat->id }}, 'campaign_category')" class="p-1 text-gray-400 hover:text-red-600 rounded">
                                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400 italic py-4 text-center">
                                        {{ __('pages.industry_management.no_campaign_categories') }}
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12">
            <x-heroicon-o-building-office class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.industry_management.no_industries') }}</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ $searchQuery ? __('pages.industry_management.try_adjusting_filters') : __('pages.industry_management.get_started') }}
            </p>
            <div class="mt-6">
                <button
                    wire:click="openAddIndustryModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                >
                    <x-heroicon-o-plus class="w-5 h-5 mr-2" />
                    {{ __('pages.industry_management.add_industry') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Add/Edit Industry Modal --}}
    @if($showIndustryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeIndustryModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingIndustryId ? __('pages.industry_management.edit_industry') : __('pages.industry_management.add_industry') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="industryDisplayName"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{{ __('pages.industry_management.display_name_placeholder') }}"
                                wire:keydown.enter="saveIndustry"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="industryDescription"
                                rows="2"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeIndustryModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveIndustry"
                            @if(empty($industryDisplayName) || $saving) disabled @endif
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCategoryModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingCategoryId ? __('pages.industry_management.edit_category') : __('pages.industry_management.add_category') }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pages.industry_management.display_name') }}</label>
                            <input
                                wire:model="categoryDisplayName"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{{ __('pages.industry_management.category_name_placeholder') }}"
                                wire:keydown.enter="saveCategory"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pages.industry_management.description_optional') }}</label>
                            <textarea
                                wire:model="categoryDescription"
                                rows="2"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="{{ __('pages.industry_management.description_placeholder') }}"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            wire:click="closeCategoryModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveCategory"
                            @if(empty($categoryDisplayName) || $saving) disabled @endif
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
                        >
                            {{ $saving ? __('common.saving') : ($editingCategoryId ? __('common.save_changes') : __('pages.industry_management.add_category')) }}
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDeleteModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ $deleteType === 'industry' ? __('pages.industry_management.delete_industry') : __('pages.industry_management.delete_category') }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ __('pages.industry_management.delete_confirm', ['name' => $deleteName]) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            wire:click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="executeDelete"
                            @if($saving) disabled @endif
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ $saving ? __('common.deleting') : __('common.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
