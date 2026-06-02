<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        @if($status === 'loading')
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">{{ __('pages.invitation.verifying') }}</p>
            </div>
        @elseif($status === 'expired')
            <div class="text-center">
                <x-heroicon-o-clock class="h-16 w-16 text-yellow-500 mx-auto" />
                <h2 class="mt-4 text-2xl font-bold text-gray-900">{{ __('pages.invitation.expired_title') }}</h2>
                <p class="mt-2 text-gray-600">{{ __('pages.invitation.expired_message') }}</p>
                <a href="/login" class="mt-6 inline-block btn-primary">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'invalid')
            <div class="text-center">
                <x-heroicon-o-exclamation-triangle class="h-16 w-16 text-red-500 mx-auto" />
                <h2 class="mt-4 text-2xl font-bold text-gray-900">{{ __('pages.invitation.invalid_title') }}</h2>
                <p class="mt-2 text-gray-600">{{ __('pages.invitation.invalid_message') }}</p>
                <a href="/login" class="mt-6 inline-block btn-primary">{{ __('pages.invitation.go_to_login') }}</a>
            </div>
        @elseif($status === 'success')
            <div class="text-center">
                <x-heroicon-o-check-circle class="h-16 w-16 text-green-500 mx-auto" />
                <h2 class="mt-4 text-2xl font-bold text-gray-900">{{ __('pages.invitation.success_title') }}</h2>
                <p class="mt-2 text-gray-600">{{ str_replace('{tenant}', $invitation['tenant_name'], __('pages.invitation.success_message')) }}</p>
                <a href="/login" class="mt-6 inline-block btn-primary">{{ __('auth.login') }}</a>
            </div>
        @elseif($status === 'valid' && $invitation)
            <div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">{{ __('app.name') }}</h2>
                <div class="mt-4 text-center text-gray-600">
                    <p>
                        <strong>{{ $invitation['invited_by'] }}</strong>
                        {{ __('pages.invitation.invited_you_to') }}
                        <strong>{{ $invitation['tenant_name'] }}</strong>
                        {{ __('pages.invitation.as') }}
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                            {{ $invitation['role'] }}
                        </span>
                    </p>
                </div>

                <form class="mt-8 space-y-6" wire:submit="accept">
                    @if(!$invitation['is_existing_user'])
                        <p class="text-sm text-gray-500 text-center">{{ __('pages.invitation.new_user_message') }}</p>
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('labels.name') }}</label>
                                <input wire:model="name" id="name" type="text" required class="mt-1 input-field" />
                            </div>
                            <div>
                                <label for="invite-email" class="block text-sm font-medium text-gray-700">{{ __('labels.email') }}</label>
                                <input id="invite-email" type="email" value="{{ $invitation['email'] }}" disabled class="mt-1 input-field bg-gray-100" />
                            </div>
                            <div>
                                <label for="invite-password" class="block text-sm font-medium text-gray-700">{{ __('labels.password') }}</label>
                                <input wire:model="password" id="invite-password" type="password" required class="mt-1 input-field" />
                            </div>
                            <div>
                                <label for="invite-password-confirm" class="block text-sm font-medium text-gray-700">{{ __('labels.confirm_password') }}</label>
                                <input wire:model="password_confirmation" id="invite-password-confirm" type="password" required class="mt-1 input-field" />
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center">{{ __('pages.invitation.existing_user_message') }}</p>
                    @endif

                    @if($error)
                        <div class="rounded-md bg-red-50 p-4">
                            <p class="text-sm text-red-800">{{ $error }}</p>
                        </div>
                    @endif

                    <button type="submit" wire:loading.attr="disabled" class="w-full btn-primary py-3">
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
