<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-start">
    <button
        @click="open = !open"
        class="flex items-center px-3 py-2 rounded-md hover:bg-white/5 transition-colors"
        aria-label="{{ __('common.change_language') }}"
    >
        <span class="text-sm font-medium text-gray-200">
            {{ $currentLocale === 'ar' ? 'العربية' : 'EN' }}
        </span>
        <svg class="w-4 h-4 ms-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute end-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-gray-900 border border-white/10 focus:outline-none"
        x-cloak
    >
        <div class="py-1">
            <button
                wire:click="switchLocale('en')"
                @click="open = false"
                class="flex items-center w-full px-4 py-2 text-sm transition-colors {{ $currentLocale === 'en' ? 'bg-brand-500/15 text-brand-400' : 'text-gray-200 hover:bg-white/5' }}"
            >
                <span>{{ __('languages.en') }}</span>
                @if($currentLocale === 'en')
                    <svg class="w-4 h-4 ms-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </button>
            <button
                wire:click="switchLocale('ar')"
                @click="open = false"
                class="flex items-center w-full px-4 py-2 text-sm transition-colors {{ $currentLocale === 'ar' ? 'bg-brand-500/15 text-brand-400' : 'text-gray-200 hover:bg-white/5' }}"
            >
                <span>{{ __('languages.ar') }}</span>
                @if($currentLocale === 'ar')
                    <svg class="w-4 h-4 ms-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </button>
        </div>
    </div>
</div>
