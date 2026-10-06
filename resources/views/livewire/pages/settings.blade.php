<div class="space-y-6">
    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="section-title">
                {{ __('pages.settings.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('pages.settings.description') }}
            </p>
        </div>
    </div>

    {{-- Settings Tabs --}}
    <div class="card p-0">
        {{-- Mobile select --}}
        <div class="sm:hidden p-3">
            <x-select2
                wire-model="activeTab"
                :options="['general' => __('pages.settings.tabs.general')]"
                :allow-clear="false"
                :searchable="false"
            />
        </div>
        {{-- Desktop tabs --}}
        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-x-8 px-4" aria-label="Tabs">
                    <button
                        wire:click="$set('activeTab', 'general')"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'general' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        {{ __('pages.settings.tabs.general') }}
                    </button>
                </nav>
            </div>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="mt-6">
        @if($activeTab === 'general')
            <div class="space-y-6">
                {{-- Profile Settings --}}
                <div class="card p-0 divide-y divide-gray-200">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold tracking-tight text-gray-900">{{ __('pages.settings.profile.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('pages.settings.profile.description') }}</p>
                    </div>

                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        {{-- Avatar with upload --}}
                        <div class="flex items-center gap-x-6">
                            <div class="relative group">
                                {{-- Avatar Image or Initials --}}
                                @if($this->user->avatar_url)
                                    <div class="w-20 h-20 rounded-full overflow-hidden ring-2 ring-gray-200">
                                        <img
                                            src="{{ $this->user->avatar_url }}"
                                            alt="Profile"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                @else
                                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center ring-2 ring-gray-200">
                                        <span class="text-2xl font-semibold text-gray-600">{{ $this->userInitials }}</span>
                                    </div>
                                @endif

                                {{-- Upload Overlay --}}
                                <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                                    <x-heroicon-o-camera class="w-6 h-6 text-white" />
                                    <input
                                        type="file"
                                        wire:model="avatarUpload"
                                        accept="image/*"
                                        class="hidden"
                                        @if($uploadingAvatar) disabled @endif
                                    />
                                </label>

                                {{-- Loading spinner --}}
                                @if($uploadingAvatar)
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full">
                                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent"></div>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $this->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $this->user->email }}</p>
                                <div class="mt-2 flex items-center gap-x-3">
                                    <label class="text-sm text-gray-900 hover:text-gray-600 cursor-pointer font-medium">
                                        {{ __('pages.settings.profile.change_photo') }}
                                        <input
                                            type="file"
                                            wire:model="avatarUpload"
                                            accept="image/*"
                                            class="hidden"
                                            @if($uploadingAvatar) disabled @endif
                                        />
                                    </label>
                                    @if($this->user->avatar_url)
                                        <button
                                            wire:click="removeAvatar"
                                            @if($uploadingAvatar) disabled @endif
                                            class="text-sm text-red-600 hover:text-red-700 font-medium"
                                        >
                                            {{ __('pages.settings.profile.remove_photo') }}
                                        </button>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-gray-400">{{ __('pages.settings.profile.photo_hint') }}</p>
                            </div>
                        </div>

                        {{-- Avatar Success/Error messages --}}
                        @if($avatarSuccess)
                            <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                <p class="text-sm text-green-800">{{ __('pages.settings.profile.avatar_success') }}</p>
                            </div>
                        @endif
                        @if($avatarError)
                            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                <p class="text-sm text-red-800">{{ $avatarError }}</p>
                            </div>
                        @endif

                        {{-- Profile Success/Error messages --}}
                        @if($profileSuccess)
                            <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                <p class="text-sm text-green-800">{{ __('pages.settings.profile.update_success') }}</p>
                            </div>
                        @endif
                        @if($profileError)
                            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                <p class="text-sm text-red-800">{{ $profileError }}</p>
                            </div>
                        @endif

                        {{-- Name field --}}
                        <div>
                            <label class="micro-label block">{{ __('pages.settings.profile.name') }}</label>
                            <input
                                wire:model="profileName"
                                type="text"
                                class="input-field mt-1"
                            />
                        </div>

                        {{-- Email field (read-only) --}}
                        <div>
                            <label class="micro-label block">{{ __('pages.settings.profile.email') }}</label>
                            <input
                                value="{{ $this->user->email }}"
                                type="email"
                                disabled
                                class="input-field mt-1 opacity-60 cursor-not-allowed"
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ __('pages.settings.profile.email_hint') }}</p>
                        </div>

                        {{-- Save button --}}
                        <div class="flex justify-end">
                            <button
                                wire:click="saveProfile"
                                @if($savingProfile) disabled @endif
                                class="btn-primary"
                            >
                                {{ $savingProfile ? __('common.saving') : __('common.save_changes') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Change Password --}}
                <div class="card p-0 divide-y divide-gray-200">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold tracking-tight text-gray-900">{{ __('pages.settings.password.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('pages.settings.password.description') }}</p>
                    </div>

                    <div class="px-4 py-5 sm:p-6 space-y-4">
                        {{-- Success/Error messages --}}
                        @if($passwordSuccess)
                            <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                <p class="text-sm text-green-800">{{ __('pages.settings.password.update_success') }}</p>
                            </div>
                        @endif
                        @if($passwordError)
                            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                <p class="text-sm text-red-800">{{ $passwordError }}</p>
                            </div>
                        @endif

                        <div>
                            <label class="micro-label block">{{ __('pages.settings.password.current') }}</label>
                            <input
                                wire:model="currentPassword"
                                type="password"
                                class="input-field mt-1"
                            />
                        </div>
                        <div>
                            <label class="micro-label block">{{ __('pages.settings.password.new') }}</label>
                            <input
                                wire:model="newPassword"
                                type="password"
                                class="input-field mt-1"
                            />
                        </div>
                        <div>
                            <label class="micro-label block">{{ __('pages.settings.password.confirm') }}</label>
                            <input
                                wire:model="newPasswordConfirmation"
                                type="password"
                                class="input-field mt-1"
                            />
                        </div>
                        <div class="flex justify-end">
                            <button
                                wire:click="changePassword"
                                @if($changingPassword || empty($currentPassword) || empty($newPassword)) disabled @endif
                                class="btn-primary"
                            >
                                {{ $changingPassword ? __('common.updating') : __('pages.settings.password.update_button') }}
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Notification Settings --}}
                <div class="card p-0 divide-y divide-gray-200">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold tracking-tight text-gray-900">{{ __('pages.settings.notifications.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('pages.settings.notifications.description') }}</p>
                    </div>

                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        {{-- Email Alerts --}}
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ __('pages.settings.notifications.email_alerts') }}</p>
                                <p class="text-sm text-gray-500">{{ __('pages.settings.notifications.email_alerts_desc') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('emailAlerts')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 {{ $emailAlerts ? 'bg-gray-900' : 'bg-gray-200' }}"
                                role="switch"
                                aria-checked="{{ $emailAlerts ? 'true' : 'false' }}"
                            >
                                <span
                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white ring-0 transition duration-200 ease-in-out {{ $emailAlerts ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0' }}"
                                ></span>
                            </button>
                        </div>

                        {{-- In-App Notifications --}}
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ __('pages.settings.notifications.in_app') }}</p>
                                <p class="text-sm text-gray-500">{{ __('pages.settings.notifications.in_app_desc') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('inAppNotifications')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 {{ $inAppNotifications ? 'bg-gray-900' : 'bg-gray-200' }}"
                                role="switch"
                                aria-checked="{{ $inAppNotifications ? 'true' : 'false' }}"
                            >
                                <span
                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white ring-0 transition duration-200 ease-in-out {{ $inAppNotifications ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0' }}"
                                ></span>
                            </button>
                        </div>

                        {{-- Weekly Summary --}}
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ __('pages.settings.notifications.weekly_summary') }}</p>
                                <p class="text-sm text-gray-500">{{ __('pages.settings.notifications.weekly_summary_desc') }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('weeklySummary')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 {{ $weeklySummary ? 'bg-gray-900' : 'bg-gray-200' }}"
                                role="switch"
                                aria-checked="{{ $weeklySummary ? 'true' : 'false' }}"
                            >
                                <span
                                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white ring-0 transition duration-200 ease-in-out {{ $weeklySummary ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0' }}"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Display Preferences --}}
                <div class="card p-0 divide-y divide-gray-200">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold tracking-tight text-gray-900">{{ __('pages.settings.display.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('pages.settings.display.description') }}</p>
                    </div>

                    <div class="px-4 py-5 sm:p-6 space-y-6">
                        {{-- Default Date Range --}}
                        <div>
                            <x-select2
                                wire-model="defaultDateRange"
                                :label="__('pages.settings.display.default_date_range')"
                                :options="['7d' => __('pages.settings.display.date_range_7d'), '30d' => __('pages.settings.display.date_range_30d'), '90d' => __('pages.settings.display.date_range_90d'), '1y' => __('pages.settings.display.date_range_1y'), 'all' => __('pages.settings.display.date_range_all')]"
                                :allow-clear="false"
                                :searchable="false"
                            />
                        </div>

                        {{-- Currency Display (read-only info) --}}
                        <div>
                            <label class="micro-label block">{{ __('pages.settings.display.currency') }}</label>
                            <div class="mt-1 flex items-center text-sm text-gray-600">
                                <span class="bg-gray-100 px-3 py-2 rounded-md font-medium">SAR ({{ __('pages.settings.display.saudi_riyal') }})</span>
                                <span class="ms-2 text-xs text-gray-500">{{ __('pages.settings.display.currency_hint') }}</span>
                            </div>
                        </div>

                        {{-- Theme (Coming Soon) --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="micro-label block">{{ __('pages.settings.display.theme') }}</label>
                                    <p class="text-sm text-gray-500">{{ __('pages.settings.display.theme_light') }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-[#F1F1EF] text-[#6B6B66]">
                                    {{ __('common.coming_soon') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
