<div x-data="{ open: false }" @click.outside="open = false" class="relative w-full">
    <button
        @click="open = !open"
        class="flex w-full items-center justify-between gap-2 rounded-lg bg-white/5 border border-white/10 px-3 py-2 text-sm text-start hover:bg-white/8 transition-colors focus:outline-none focus:ring-1 focus:ring-red-500/40"
    >
        <span class="flex items-center gap-2 min-w-0">
            <span class="h-2 w-2 rounded-full bg-red-400 flex-shrink-0"></span>
            <span class="truncate font-medium text-white">
                {{ $currentTenant?->name ?? __('labels.select_tenant') }}
            </span>
        </span>
        <x-heroicon-s-chevron-up-down class="h-4 w-4 text-gray-500 flex-shrink-0" />
    </button>

    <div
        x-show="open"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute start-0 top-full z-50 mt-1 w-full max-h-64 overflow-y-auto rounded-lg border border-white/10 bg-gray-900 shadow-xl"
        x-cloak
    >
        @foreach($tenants as $tenant)
            <button
                wire:click="switchTenant({{ $tenant->id }})"
                @click="open = false"
                class="flex w-full items-center gap-3 px-3 py-2.5 text-start hover:bg-white/5 transition-colors {{ $selectedTenantId == $tenant->id ? 'bg-red-500/10' : '' }}"
            >
                @if($selectedTenantId == $tenant->id)
                    <x-heroicon-s-check class="h-3.5 w-3.5 text-red-400 flex-shrink-0" />
                @else
                    <span class="h-3.5 w-3.5 flex-shrink-0"></span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium {{ $selectedTenantId == $tenant->id ? 'text-red-400' : 'text-white' }}">
                        {{ $tenant->name }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ trans_choice('common.accounts', $tenant->ad_accounts_count ?? 0) }}
                    </p>
                </div>
            </button>
        @endforeach
    </div>
</div>
