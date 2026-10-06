<div class="min-h-screen flex items-center justify-center bg-[#F7F6F3] px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-8 space-y-6">
            <div class="text-center">
                <h2 class="text-xl font-semibold tracking-tight text-zinc-900">
                    {{ __('app.name') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ __('app.tagline') }}
                </p>
            </div>

            <form class="space-y-5" wire:submit="login">
                <div>
                    <label for="email" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('auth.email') }}</label>
                    <input
                        id="email"
                        wire:model="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="you@company.com"
                    />
                </div>

                <div>
                    <label for="password" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('auth.password') }}</label>
                    <input
                        id="password"
                        wire:model="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                        placeholder="••••••••"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember-me" class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                        <input
                            id="remember-me"
                            wire:model="remember"
                            name="remember-me"
                            type="checkbox"
                            class="h-4 w-4 rounded border-[#EAEAEA] accent-brand-600"
                        />
                        {{ __('auth.remember_me') }}
                    </label>

                    <a href="#" class="text-[13px] font-medium text-brand-600 hover:text-brand-700">
                        {{ __('auth.forgot_password') }}
                    </a>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading wire:target="login">
                        <span class="animate-spin inline-block h-4 w-4 border-b-2 border-white rounded-full"></span>
                    </span>
                    <span wire:loading.remove wire:target="login">{{ __('auth.login') }}</span>
                    <span wire:loading wire:target="login">{{ __('common.loading') }}</span>
                </button>

                @if($error)
                    <div class="flex items-start gap-2.5 rounded-lg bg-[#FDEBEC] px-3.5 py-3">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 mt-0.5 shrink-0 text-[#9F2F2D]" />
                        <p class="text-[13px] font-medium text-[#9F2F2D]">{{ $error }}</p>
                    </div>
                @endif
            </form>

            <div class="text-center">
                <p class="text-sm text-zinc-500">
                    {{ __('auth.dont_have_account') }}
                    <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-700">
                        {{ __('auth.register') }}
                    </a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('privacy-policy') }}" class="text-[13px] text-zinc-400 hover:text-zinc-600 transition">
                {{ __('common.privacy_policy') }}
            </a>
        </div>
    </div>
</div>
