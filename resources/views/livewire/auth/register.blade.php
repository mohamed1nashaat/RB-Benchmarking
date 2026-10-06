<div class="min-h-screen flex items-center justify-center bg-[#F7F6F3] px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-zinc-900">
                {{ __('auth.register') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-500">
                {{ __('auth.register_tagline') }}
            </p>
        </div>

        <div class="bg-white border border-[#EAEAEA] rounded-xl p-8">
            <form class="space-y-5" wire:submit="register">
                <div>
                    <label for="name" class="block text-[13px] font-medium text-zinc-700 mb-1.5">
                        {{ __('auth.name') }}
                    </label>
                    <input
                        id="name"
                        wire:model="name"
                        name="name"
                        type="text"
                        autocomplete="name"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="{{ __('auth.name') }}"
                    />
                    @error('name')
                        <p class="mt-1.5 text-xs text-[#9F2F2D]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-[13px] font-medium text-zinc-700 mb-1.5">
                        {{ __('auth.email') }}
                    </label>
                    <input
                        id="email"
                        wire:model="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="you@redbananas.com"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-[#9F2F2D]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[13px] font-medium text-zinc-700 mb-1.5">
                        {{ __('auth.password') }}
                    </label>
                    <input
                        id="password"
                        wire:model="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="••••••••"
                    />
                    @error('password')
                        <p class="mt-1.5 text-xs text-[#9F2F2D]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-[13px] font-medium text-zinc-700 mb-1.5">
                        {{ __('auth.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        wire:model="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="••••••••"
                    />
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading wire:target="register" class="inline-block">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="register">{{ __('auth.register') }}</span>
                    <span wire:loading wire:target="register">{{ __('common.loading') }}</span>
                </button>

                @if($error)
                    <div class="flex items-start gap-2.5 rounded-lg bg-[#FDEBEC] px-3.5 py-3" role="alert">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 mt-0.5 shrink-0 text-[#9F2F2D]" />
                        <p class="text-[13px] font-medium text-[#9F2F2D]">{{ $error }}</p>
                    </div>
                @endif
            </form>
        </div>

        <div class="mt-6 text-center space-y-1">
            <p class="text-sm text-zinc-500">
                {{ __('auth.already_have_account') }}
                <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 transition-colors">
                    {{ __('auth.login') }}
                </a>
            </p>
            <a href="{{ route('privacy-policy') }}" class="block text-[13px] text-zinc-400 hover:text-zinc-600 transition">
                {{ __('common.privacy_policy') }}
            </a>
        </div>
    </div>
</div>
