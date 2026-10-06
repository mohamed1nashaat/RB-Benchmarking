<div class="min-h-screen flex items-center justify-center bg-[#F7F6F3] px-4 py-12">
    <div class="w-full max-w-sm">
        @if($status === 'loading')
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-10 text-center">
                <span class="animate-spin inline-block h-7 w-7 border-b-2 border-brand-600 rounded-full"></span>
                <p class="mt-4 text-sm text-zinc-500">{{ __('pages.invitation.verifying') }}</p>
            </div>
        @elseif($status === 'expired')
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-10 text-center">
                <x-heroicon-o-clock class="h-12 w-12 text-[#956400] mx-auto" />
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-zinc-900">{{ __('pages.invitation.expired_title') }}</h2>
                <p class="mt-2 text-sm text-zinc-500">{{ __('pages.invitation.expired_message') }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 transition">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'invalid')
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-10 text-center">
                <x-heroicon-o-exclamation-triangle class="h-12 w-12 text-[#9F2F2D] mx-auto" />
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-zinc-900">{{ __('pages.invitation.invalid_title') }}</h2>
                <p class="mt-2 text-sm text-zinc-500">{{ __('pages.invitation.invalid_message') }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 transition">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'success')
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-10 text-center">
                <x-heroicon-o-check-circle class="h-12 w-12 text-[#346538] mx-auto" />
                <h2 class="mt-4 text-lg font-semibold tracking-tight text-zinc-900">{{ __('pages.invitation.success_title') }}</h2>
                <p class="mt-2 text-sm text-zinc-500">{{ str_replace('{tenant}', $invitation['tenant_name'], __('pages.invitation.success_message')) }}</p>
                <a href="/login" class="mt-6 inline-block px-4 py-2 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 transition">{{ __('auth.login') }}</a>
            </div>
        @elseif($status === 'valid' && $invitation)
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-8">
                <h2 class="text-center text-xl font-semibold tracking-tight text-zinc-900">{{ __('app.name') }}</h2>
                <div class="mt-3 text-center text-sm text-zinc-500">
                    <p>
                        <strong class="text-zinc-700">{{ $invitation['invited_by'] }}</strong>
                        {{ __('pages.invitation.invited_you_to') }}
                        <strong class="text-zinc-700">{{ $invitation['tenant_name'] }}</strong>
                        {{ __('pages.invitation.as') }}
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-brand-100 text-brand-700">
                            {{ $invitation['role'] }}
                        </span>
                    </p>
                </div>

                <form class="mt-7 space-y-5" wire:submit="accept">
                    @if(!$invitation['is_existing_user'])
                        <p class="text-[13px] text-zinc-500 text-center">{{ __('pages.invitation.new_user_message') }}</p>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('labels.name') }}</label>
                                <input wire:model="name" id="name" type="text" required
                                    class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                            <div>
                                <label for="invite-email" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('labels.email') }}</label>
                                <input id="invite-email" type="email" value="{{ $invitation['email'] }}" disabled
                                    class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-400 bg-[#F7F6F3]" />
                            </div>
                            <div>
                                <label for="invite-password" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('labels.password') }}</label>
                                <input wire:model="password" id="invite-password" type="password" required
                                    class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                            <div>
                                <label for="invite-password-confirm" class="block text-[13px] font-medium text-zinc-700 mb-1.5">{{ __('labels.confirm_password') }}</label>
                                <input wire:model="password_confirmation" id="invite-password-confirm" type="password" required
                                    class="block w-full px-3 py-2.5 border border-[#EAEAEA] rounded-lg text-sm text-zinc-900 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition" />
                            </div>
                        </div>
                    @else
                        <p class="text-[13px] text-zinc-500 text-center">{{ __('pages.invitation.existing_user_message') }}</p>
                    @endif

                    @if($error)
                        <div class="rounded-lg bg-[#FDEBEC] px-3.5 py-3">
                            <p class="text-[13px] font-medium text-[#9F2F2D]">{{ $error }}</p>
                        </div>
                    @endif

                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full py-2.5 rounded-md text-sm font-semibold text-white bg-zinc-900 hover:bg-zinc-700 active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed">
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
