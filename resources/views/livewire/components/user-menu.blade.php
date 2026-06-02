<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
    <button
        @click="open = !open"
        class="inline-flex items-center gap-2 rounded-full bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        @if($user->avatar_url ?? false)
            <img src="{{ $user->avatar_url }}" alt="Profile" class="w-8 h-8 rounded-full object-cover">
        @else
            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-primary-700">
                    {{ collect(explode(' ', $user->name))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('') }}
                </span>
            </div>
        @endif
        <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[120px] truncate">
            {{ $user->name }}
        </span>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="transform scale-95 opacity-0"
        x-transition:enter-end="transform scale-100 opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="transform scale-100 opacity-100"
        x-transition:leave-end="transform scale-95 opacity-0"
        class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        x-cloak
    >
        <!-- User Info -->
        <div class="px-4 py-3">
            <p class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
            @if($currentTenantRole && $currentTenantName)
                <p class="text-xs text-gray-400 mt-1 capitalize">{{ $currentTenantRole }} &bull; {{ $currentTenantName }}</p>
            @endif
        </div>

        <!-- Main Navigation -->
        <div class="py-1">
            <a href="/ad-accounts" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-heroicon-s-chart-bar class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('navigation.ad_accounts') }}
            </a>
            <a href="/industry-management" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-heroicon-s-building-office class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('navigation.industry_management') }}
            </a>
            <a href="/clients" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-heroicon-s-user-group class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('navigation.clients') }}
            </a>
        </div>

        <!-- User Management (Admin Only) -->
        @if($isAdmin || $isSuperAdmin)
            <div class="py-1">
                <a href="/users" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <x-heroicon-s-users class="mr-3 h-5 w-5 text-gray-400" />
                    {{ __('navigation.users') }}
                </a>
            </div>
        @endif

        <!-- Settings -->
        <div class="py-1">
            <a href="/integrations" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-heroicon-s-plus class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('navigation.integrations') }}
            </a>
            <a href="/settings" @click="open = false" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <x-heroicon-s-cog-6-tooth class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('navigation.settings') }}
            </a>
        </div>

        <!-- Logout -->
        <div class="py-1">
            <button
                wire:click="logout"
                @click="open = false"
                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
                <x-heroicon-s-arrow-right-on-rectangle class="mr-3 h-5 w-5 text-gray-400" />
                {{ __('auth.logout') }}
            </button>
        </div>
    </div>
</div>
