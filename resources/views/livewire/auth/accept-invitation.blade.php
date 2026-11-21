<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background-color: #0d0f16;">
    <div class="w-full max-w-sm">
        @if($status === 'loading')
            <div class="rounded-xl p-10 text-center" style="background: linear-gradient(160deg,#1c2130,#161b22); border: 1px solid rgba(255,255,255,0.08); border-top-color: rgba(255,255,255,0.13);">
                <span class="animate-spin inline-block h-7 w-7 border-b-2 border-brand-500 rounded-full"></span>
                <p class="mt-4 text-sm text-gray-400">{{ __('pages.invitation.verifying') }}</p>
            </div>
        @elseif($status === 'expired')
            <div class="rounded-xl p-10 text-center" style="background: linear-gradient(160deg,#1c2130,#161b22); border: 1px solid rgba(255,255,255,0.08); border-top-color: rgba(255,255,255,0.13);">
                <div class="w-14 h-14 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mx-auto">
                    <x-heroicon-o-clock class="h-7 w-7 text-amber-400" />
                </div>
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-white">{{ __('pages.invitation.expired_title') }}</h2>
                <p class="mt-2 text-sm text-gray-400">{{ __('pages.invitation.expired_message') }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-brand-500 hover:bg-brand-400 transition">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'invalid')
            <div class="rounded-xl p-10 text-center" style="background: linear-gradient(160deg,#1c2130,#161b22); border: 1px solid rgba(255,255,255,0.08); border-top-color: rgba(255,255,255,0.13);">
                <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto">
                    <x-heroicon-o-exclamation-triangle class="h-7 w-7 text-red-400" />
                </div>
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-white">{{ __('pages.invitation.invalid_title') }}</h2>
                <p class="mt-2 text-sm text-gray-400">{{ __('pages.invitation.invalid_message') }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-brand-500 hover:bg-brand-400 transition">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'success')
            <div class="rounded-xl p-10 text-center" style="background: linear-gradient(160deg,#1c2130,#161b22); border: 1px solid rgba(255,255,255,0.08); border-top-color: rgba(255,255,255,0.13);">
                <div class="w-14 h-14 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto">
                    <x-heroicon-o-check-circle class="h-7 w-7 text-green-400" />
                </div>
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-white">{{ __('pages.invitation.success_title') }}</h2>
                <p class="mt-2 text-sm text-gray-400">{{ str_replace('{tenant}', $invitation['tenant_name'], __('pages.invitation.success_message')) }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-brand-500 hover:bg-brand-400 transition">{{ __('auth.login') }}</a>
            </div>
        @elseif($status === 'valid' && $invitation)
            <div class="rounded-xl p-8" style="background: linear-gradient(160deg,#1c2130,#161b22); border: 1px solid rgba(255,255,255,0.08); border-top-color: rgba(255,255,255,0.13);">
                <div class="text-center mb-5">
                    <a href="/">
                        <img src="/logo-dark.svg" alt="{{ __('app.name') }}" class="h-7 w-auto mx-auto mb-4">
                    </a>
                </div>
                <div class="text-center text-sm text-gray-400 mb-6">
                    <p>
                        <strong class="text-gray-200">{{ $invitation['invited_by'] }}</strong>
                        {{ __('pages.invitation.invited_you_to') }}
                        <strong class="text-gray-200">{{ $invitation['tenant_name'] }}</strong>
                        {{ __('pages.invitation.as') }}
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-brand-500/15 text-brand-400">
                            {{ $invitation['role'] }}
                        </span>
                    </p>
                </div>

                <form class="space-y-5" wire:submit="accept">
                    @if(!$invitation['is_existing_user'])
                        <p class="text-[13px] text-gray-400 text-center">{{ __('pages.invitation.new_user_message') }}</p>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-[13px] font-medium text-gray-300 mb-1.5">{{ __('labels.name') }}</label>
                                <input wire:model="name" id="name" type="text" required
                                    class="block w-full px-3 py-2.5 border border-white/10 rounded-lg text-sm text-white bg-white/5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                            <div>
                                <label for="invite-email" class="block text-[13px] font-medium text-gray-300 mb-1.5">{{ __('labels.email') }}</label>
                                <input id="invite-email" type="email" value="{{ $invitation['email'] }}" disabled
                                    class="block w-full px-3 py-2.5 border border-white/10 rounded-lg text-sm text-gray-500 bg-white/5 opacity-60" />
                            </div>
                            <div>
                                <label for="invite-password" class="block text-[13px] font-medium text-gray-300 mb-1.5">{{ __('labels.password') }}</label>
                                <input wire:model="password" id="invite-password" type="password" required
                                    class="block w-full px-3 py-2.5 border border-white/10 rounded-lg text-sm text-white bg-white/5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                            <div>
                                <label for="invite-password-confirm" class="block text-[13px] font-medium text-gray-300 mb-1.5">{{ __('labels.confirm_password') }}</label>
                                <input wire:model="password_confirmation" id="invite-password-confirm" type="password" required
                                    class="block w-full px-3 py-2.5 border border-white/10 rounded-lg text-sm text-white bg-white/5 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                        </div>
                    @else
                        <p class="text-[13px] text-gray-400 text-center">{{ __('pages.invitation.existing_user_message') }}</p>
                    @endif

                    @if($error)
                        <div class="flex items-start gap-2.5 rounded-lg bg-red-500/10 border border-red-500/20 px-3.5 py-3">
                            <x-heroicon-o-exclamation-triangle class="h-4 w-4 mt-0.5 shrink-0 text-red-400" />
                            <p class="text-[13px] font-medium text-red-300">{{ $error }}</p>
                        </div>
                    @endif

                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full py-2.5 rounded-md text-sm font-semibold text-white bg-brand-500 hover:bg-brand-400 active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="accept">
                            {{ $invitation['is_existing_user'] ? __('pages.invitation.accept') : __('pages.invitation.create_account_and_join') }}
                        </span>
                        <span wire:loading wire:target="accept">
                            {{ $invitation['is_existing_user'] ? __('pages.invitation.accepting') : __('pages.invitation.creating_account') }}
                        </span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
