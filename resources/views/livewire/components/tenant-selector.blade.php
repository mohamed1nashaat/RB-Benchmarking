<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button
        @click="open = !open"
        class="relative w-48 cursor-default rounded-lg bg-gray-900 py-2 ps-3 pe-10 text-start shadow-md focus:outline-none focus-visible:border-primary-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
    >
        <span class="block truncate">
            {{ $currentTenant?->name ?? __('labels.select_tenant') }}
        </span>
        <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-2">
            <x-heroicon-s-chevron-up-down class="h-5 w-5 text-gray-400" />
        </span>
    </button>

    <div
        x-show="open"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-gray-900 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        x-cloak
    >
        @foreach($tenants as $tenant)
            <button
                wire:click="switchTenant({{ $tenant->id }})"
                @click="open = false"
                class="relative cursor-default select-none py-2 ps-10 pe-4 w-full text-start {{ $selectedTenantId == $tenant->id ? 'bg-amber-100 text-amber-900' : 'text-white hover:bg-amber-50' }}"
            >
                <div class="flex flex-col">
                    <span class="block truncate {{ $selectedTenantId == $tenant->id ? 'font-medium' : 'font-normal' }}">
                        {{ $tenant->name }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ trans_choice('common.accounts', $tenant->ad_accounts_count ?? 0) }}
                    </span>
                </div>
                @if($selectedTenantId == $tenant->id)
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-amber-600">
                        <x-heroicon-s-check class="h-5 w-5" />
                    </span>
                @endif
            </button>
        @endforeach
    </div>
</div>
