<div class="space-y-6">
    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    {{ __('navigation.integrations') }}
                </h2>
                <div class="ml-3 flex items-center">
                    @php
                        $allHealthy = collect($integrations)->where('status', 'active')->count() === count($integrations) && count($integrations) > 0;
                        $hasActive = collect($integrations)->where('status', 'active')->count() > 0;
                        $needsReauth = collect($integrations)->where('status', 'needs_reauth')->count() > 0;
                    @endphp
                    <div class="h-3 w-3 rounded-full {{ $allHealthy ? 'bg-green-500' : ($needsReauth ? 'bg-amber-500' : ($hasActive ? 'bg-yellow-500' : 'bg-gray-400')) }}"></div>
                    <span class="ml-1 text-sm font-medium {{ $allHealthy ? 'text-green-700' : ($needsReauth ? 'text-amber-700' : ($hasActive ? 'text-yellow-700' : 'text-gray-500')) }}">
                        {{ $allHealthy ? __('All Healthy') : ($needsReauth ? __('Re-auth Required') : ($hasActive ? __('Some Issues') : __('No Integrations'))) }}
                    </span>
                </div>
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ __('Connect your ad platforms to start importing data') }}</p>
        </div>
        <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
            <button wire:click="refreshAllIntegrations" wire:loading.attr="disabled" wire:target="refreshAllIntegrations" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                <span wire:loading wire:target="refreshAllIntegrations" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></span>
                <x-heroicon-o-arrow-path wire:loading.remove wire:target="refreshAllIntegrations" class="h-4 w-4 mr-2" />
                {{ __('Refresh All') }}
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Needs Re-auth Warning Banner --}}
    @if(collect($integrations)->where('status', 'needs_reauth')->isNotEmpty())
        <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-amber-800">{{ __('Platform authorization expired') }}</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        {{ __('One or more platforms need to be re-authorized. Click "Re-authorize" on the affected platform below.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Available Platforms --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('Available Platforms') }}</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ __('Connect to these advertising platforms to import your campaign data') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 p-6">
            @php
                $platforms = [
                    ['id' => 'facebook', 'name' => 'Facebook Ads', 'subtitle' => 'Meta Platforms', 'desc' => 'Connect Facebook and Instagram advertising campaigns to import performance data and metrics.', 'iconBgClass' => 'bg-blue-50', 'iconTextClass' => 'text-blue-600', 'btnClass' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'],
                    ['id' => 'google_sheets', 'name' => 'Google Sheets', 'subtitle' => 'Google Workspace', 'desc' => 'Export campaign data and reports directly to Google Drive folders and spreadsheets.', 'iconBgClass' => 'bg-green-50', 'iconTextClass' => 'text-green-600', 'btnClass' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500'],
                    ['id' => 'google_ads', 'name' => 'Google Ads', 'subtitle' => 'Google Ads', 'desc' => 'Import Google Ads campaigns, ad groups, and performance metrics for comprehensive analysis.', 'iconBgClass' => 'bg-red-50', 'iconTextClass' => 'text-red-600', 'btnClass' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500'],
                    ['id' => 'tiktok', 'name' => 'TikTok Ads', 'subtitle' => 'TikTok for Business', 'desc' => 'Connect TikTok for Business campaigns to track video ad performance and user engagement metrics.', 'iconBgClass' => 'bg-gray-50', 'iconTextClass' => 'text-gray-800', 'btnClass' => 'bg-gray-800 hover:bg-gray-900 focus:ring-gray-500'],
                    ['id' => 'snapchat', 'name' => 'Snapchat Ads', 'subtitle' => 'Snapchat for Business', 'desc' => 'Connect Snapchat for Business to track Story ads, Snap campaigns, and user engagement metrics.', 'iconBgClass' => 'bg-yellow-50', 'iconTextClass' => 'text-yellow-400', 'btnClass' => 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500'],
                    ['id' => 'linkedin', 'name' => 'LinkedIn Ads', 'subtitle' => 'LinkedIn for Business', 'desc' => 'Connect LinkedIn for Business to track professional advertising campaigns and B2B lead generation.', 'iconBgClass' => 'bg-blue-50', 'iconTextClass' => 'text-blue-700', 'btnClass' => 'bg-blue-700 hover:bg-blue-800 focus:ring-blue-500'],
                    ['id' => 'twitter', 'name' => 'X/Twitter Ads', 'subtitle' => 'X for Business', 'desc' => 'Connect X/Twitter Ads to track promoted tweets, engagement campaigns, and social media advertising.', 'iconBgClass' => 'bg-gray-50', 'iconTextClass' => 'text-gray-900', 'btnClass' => 'bg-gray-900 hover:bg-gray-800 focus:ring-gray-500'],
                ];

                $connectedPlatforms = collect($integrations)->where('status', 'active')->pluck('platform')->toArray();
                $needsReauthPlatforms = collect($integrations)->where('status', 'needs_reauth')->pluck('platform')->toArray();
            @endphp

            @foreach($platforms as $p)
                @php
                    $isConnected = in_array($p['id'], $connectedPlatforms)
                        || ($p['id'] === 'google_sheets' && $googleSheetsStatus['authenticated'])
                        || ($p['id'] === 'google_ads' && $googleAdsStatus['authenticated']);
                    $needsReauth = in_array($p['id'], $needsReauthPlatforms);
                @endphp
                <div class="group relative bg-white rounded-lg shadow-sm border {{ $needsReauth ? 'border-amber-200 bg-amber-50/30' : ($isConnected ? 'border-green-200 bg-green-50/30' : 'border-gray-200') }} hover:shadow-md transition-all duration-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 {{ $p['iconBgClass'] }} rounded-lg flex items-center justify-center">
                                    @switch($p['id'])
                                        @case('facebook')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        @break
                                        @case('google_sheets')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M19.385 2H6.615C5.17 2 4 3.17 4 4.615v14.77C4 20.83 5.17 22 6.615 22h12.77C20.83 22 22 20.83 22 19.385V4.615C22 3.17 20.83 2 19.385 2zM8 18v-2h3v2H8zm0-4v-2h3v2H8zm0-4V8h3v2H8zm5 8v-2h3v2h-3zm0-4v-2h3v2h-3zm0-4V8h3v2h-3z"/></svg>
                                        @break
                                        @case('google_ads')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M3.292 16.794l6.474-11.218a3.2 3.2 0 014.374-1.17 3.2 3.2 0 011.17 4.374L8.835 19.997a3.2 3.2 0 01-5.543-3.203z" fill="#FBBC04"/><path d="M20.708 16.794L14.234 5.576a3.2 3.2 0 00-4.374-1.17 3.2 3.2 0 00-1.17 4.374l6.475 11.217a3.2 3.2 0 005.543-3.203z" fill="#4285F4"/><circle cx="6.036" cy="18.396" r="3.2" fill="#34A853"/></svg>
                                        @break
                                        @case('tiktok')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.81a8.23 8.23 0 004.76 1.5V6.86a4.85 4.85 0 01-1-.17z"/></svg>
                                        @break
                                        @case('snapchat')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.922-.214.04-.012.06-.012.06-.012.076 0 .166.045.226.135a.34.34 0 01.046.296c-.093.285-.3.51-.478.63a3.08 3.08 0 01-.226.136c-.12.06-.226.12-.33.195-.195.135-.27.315-.225.525.06.27.254.57.449.87.09.135.195.285.3.435.658.9 1.484 1.635 1.92 1.845.09.045.165.09.225.135.135.09.21.21.21.361a.46.46 0 01-.165.33c-.39.345-1.11.585-1.875.72a4.04 4.04 0 01-.285.045c-.09.015-.18.03-.27.061-.15.06-.195.18-.21.272v.015a1.14 1.14 0 01-.045.21c-.06.135-.195.24-.4.299a5.5 5.5 0 01-.975.18c-.24.03-.48.06-.705.12-.33.09-.615.315-.975.615-.495.405-1.11.915-2.19.915h-.03c-1.08 0-1.695-.51-2.19-.915-.36-.3-.645-.525-.975-.615-.225-.06-.465-.09-.705-.12a5.78 5.78 0 01-.975-.18c-.21-.06-.345-.165-.4-.3v-.014a1.32 1.32 0 01-.046-.211v-.016c-.015-.09-.06-.21-.21-.27a2.75 2.75 0 00-.27-.06c-.105-.015-.21-.03-.285-.045-.765-.135-1.485-.375-1.875-.72a.46.46 0 01-.165-.33c0-.15.075-.27.21-.36l.015-.015c.06-.045.135-.09.225-.135.435-.21 1.26-.945 1.92-1.845.105-.15.21-.3.3-.435.195-.3.39-.6.449-.87.046-.21-.03-.39-.225-.525-.105-.075-.21-.135-.33-.195a3.08 3.08 0 01-.225-.135c-.18-.12-.39-.345-.48-.63a.34.34 0 01.046-.296c.06-.09.15-.135.226-.135 0 0 .015 0 .06.012.263.094.622.23.922.214.198 0 .326-.045.4-.09l-.002-.06-.003-.06c-.104-1.628-.23-3.654.299-4.847C7.653 1.069 11.016.793 12.006.793h.2z"/></svg>
                                        @break
                                        @case('linkedin')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                        @break
                                        @case('twitter')
                                            <svg class="h-6 w-6 {{ $p['iconTextClass'] }}" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        @break
                                    @endswitch
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $p['name'] }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $p['subtitle'] }}</p>
                            </div>
                        </div>
                        @if($needsReauth)
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                            </div>
                        @elseif($isConnected)
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            </div>
                        @endif
                    </div>

                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $p['desc'] }}</p>

                    @if($p['id'] === 'google_ads' && $googleAdsStatus['authenticated'])
                        <div class="mb-3">
                            <p class="text-xs text-green-700 bg-green-100 rounded px-2 py-1">
                                {{ $googleAdsStatus['total_accounts'] }} {{ __('accounts connected') }}
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        @if($needsReauth)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 border border-amber-200">
                                <div class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5"></div>
                                {{ __('Needs Re-auth') }}
                            </span>
                            <button wire:click="reauthorizePlatform('{{ $p['id'] }}')" class="inline-flex items-center px-3 py-1.5 border border-amber-300 text-sm font-medium rounded-md text-amber-700 bg-white hover:bg-amber-50 transition-colors duration-200">
                                <x-heroicon-o-key class="h-4 w-4 mr-1" />
                                {{ __('Re-authorize') }}
                            </button>
                        @elseif($isConnected)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></div>
                                {{ __('Connected') }}
                            </span>
                            <button wire:click="reauthorizePlatform('{{ $p['id'] }}')" title="{{ __('Reconnect') }}" class="opacity-0 group-hover:opacity-100 inline-flex items-center p-1 border border-gray-200 rounded-md text-gray-400 hover:text-amber-600 hover:border-amber-300 hover:bg-amber-50 transition-all duration-200">
                                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                            </button>
                        @else
                            <button wire:click="connectPlatform('{{ $p['id'] }}')" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white {{ $p['btnClass'] }} transition-colors duration-200">
                                {{ __('Connect') }}
                            </button>
                        @endif
                    </div>

                    {{-- Sync & Import Buttons --}}
                    @if(($isConnected || $needsReauth) && !in_array($p['id'], ['google_sheets']))
                        <div class="mt-3 pt-3 border-t border-gray-200 space-y-2">
                            @if(isset($activeSyncs[$p['id']]))
                                <button wire:click="openPlatformSyncModal('{{ $p['id'] }}')" class="w-full inline-flex items-center justify-center px-3 py-2 border border-amber-300 text-sm font-medium rounded-md text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors duration-200">
                                    <svg class="animate-spin h-4 w-4 mr-2 flex-shrink-0 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span class="flex flex-col items-start text-left">
                                        <span>{{ __('Syncing...') }}</span>
                                        <span class="text-xs text-amber-500 truncate max-w-[180px]">{{ $activeSyncs[$p['id']]['current_step'] ?? '' }}</span>
                                    </span>
                                </button>
                            @else
                                <button wire:click="openPlatformSyncModal('{{ $p['id'] }}')" class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white {{ $p['btnClass'] }} transition-colors duration-200">
                                    <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" />
                                    {{ __('Sync Metrics') }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Integration Stats --}}
    @if(count($integrations) > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                                <x-heroicon-s-check-circle class="h-5 w-5 text-green-600" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Active Integrations') }}</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $activeIntegrationsCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <x-heroicon-s-credit-card class="h-5 w-5 text-blue-600" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Total Accounts') }}</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $totalAccountsCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <x-heroicon-s-clock class="h-5 w-5 text-purple-600" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Last Sync') }}</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $this->getLastSyncTime() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-600" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('Issues') }}</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $issuesCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Connected Integrations List --}}
    @if(count($integrations) > 0)
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 cursor-pointer hover:bg-gray-50 transition-colors" wire:click="toggleConnectedIntegrations">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        @if($connectedIntegrationsCollapsed)
                            <x-heroicon-o-chevron-up class="h-5 w-5 text-gray-400" />
                        @else
                            <x-heroicon-o-chevron-down class="h-5 w-5 text-gray-400" />
                        @endif
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('Connected Integrations') }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ __('Manage your connected advertising platforms') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2" wire:click.stop>
                        <button wire:click="toggleAutoRefresh" class="inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 {{ $autoRefresh ? 'bg-green-50 border-green-200 text-green-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            <div class="h-2 w-2 rounded-full mr-2 {{ $autoRefresh ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                            {{ __('Auto-refresh') }} {{ $autoRefresh ? 'ON' : 'OFF' }}
                        </button>
                    </div>
                </div>
            </div>

            @if(!$connectedIntegrationsCollapsed)
                <ul class="divide-y divide-gray-200">
                    @foreach($integrations as $integration)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @switch($integration['platform'])
                                            @case('facebook')
                                                <svg class="h-8 w-8 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                            @break
                                            @case('google_sheets')
                                                <svg class="h-8 w-8 text-green-600" viewBox="0 0 24 24" fill="currentColor"><path d="M19.385 2H6.615C5.17 2 4 3.17 4 4.615v14.77C4 20.83 5.17 22 6.615 22h12.77C20.83 22 22 20.83 22 19.385V4.615C22 3.17 20.83 2 19.385 2zM8 18v-2h3v2H8zm0-4v-2h3v2H8zm0-4V8h3v2H8zm5 8v-2h3v2h-3zm0-4v-2h3v2h-3zm0-4V8h3v2h-3z"/></svg>
                                            @break
                                            @case('google_ads')
                                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none"><path d="M3.292 16.794l6.474-11.218a3.2 3.2 0 014.374-1.17 3.2 3.2 0 011.17 4.374L8.835 19.997a3.2 3.2 0 01-5.543-3.203z" fill="#FBBC04"/><path d="M20.708 16.794L14.234 5.576a3.2 3.2 0 00-4.374-1.17 3.2 3.2 0 00-1.17 4.374l6.475 11.217a3.2 3.2 0 005.543-3.203z" fill="#4285F4"/><circle cx="6.036" cy="18.396" r="3.2" fill="#34A853"/></svg>
                                            @break
                                            @case('tiktok')
                                                <svg class="h-8 w-8 text-gray-800" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.81a8.23 8.23 0 004.76 1.5V6.86a4.85 4.85 0 01-1-.17z"/></svg>
                                            @break
                                            @case('snapchat')
                                                <svg class="h-8 w-8 text-yellow-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.922-.214.04-.012.06-.012.06-.012.076 0 .166.045.226.135a.34.34 0 01.046.296c-.093.285-.3.51-.478.63a3.08 3.08 0 01-.226.136c-.12.06-.226.12-.33.195-.195.135-.27.315-.225.525.06.27.254.57.449.87.09.135.195.285.3.435.658.9 1.484 1.635 1.92 1.845.09.045.165.09.225.135.135.09.21.21.21.361a.46.46 0 01-.165.33c-.39.345-1.11.585-1.875.72a4.04 4.04 0 01-.285.045c-.09.015-.18.03-.27.061-.15.06-.195.18-.21.272v.015a1.14 1.14 0 01-.045.21c-.06.135-.195.24-.4.299a5.5 5.5 0 01-.975.18c-.24.03-.48.06-.705.12-.33.09-.615.315-.975.615-.495.405-1.11.915-2.19.915h-.03c-1.08 0-1.695-.51-2.19-.915-.36-.3-.645-.525-.975-.615-.225-.06-.465-.09-.705-.12a5.78 5.78 0 01-.975-.18c-.21-.06-.345-.165-.4-.3v-.014a1.32 1.32 0 01-.046-.211v-.016c-.015-.09-.06-.21-.21-.27a2.75 2.75 0 00-.27-.06c-.105-.015-.21-.03-.285-.045-.765-.135-1.485-.375-1.875-.72a.46.46 0 01-.165-.33c0-.15.075-.27.21-.36l.015-.015c.06-.045.135-.09.225-.135.435-.21 1.26-.945 1.92-1.845.105-.15.21-.3.3-.435.195-.3.39-.6.449-.87.046-.21-.03-.39-.225-.525-.105-.075-.21-.135-.33-.195a3.08 3.08 0 01-.225-.135c-.18-.12-.39-.345-.48-.63a.34.34 0 01.046-.296c.06-.09.15-.135.226-.135 0 0 .015 0 .06.012.263.094.622.23.922.214.198 0 .326-.045.4-.09l-.002-.06-.003-.06c-.104-1.628-.23-3.654.299-4.847C7.653 1.069 11.016.793 12.006.793h.2z"/></svg>
                                            @break
                                            @case('linkedin')
                                                <svg class="h-8 w-8 text-blue-700" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                            @break
                                            @case('twitter')
                                                <svg class="h-8 w-8 text-gray-900" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                            @break
                                            @default
                                                <x-heroicon-o-signal class="h-8 w-8 text-gray-500" />
                                        @endswitch
                                    </div>
                                    <div class="ml-4">
                                        @php $platformNames = collect($platforms)->pluck('name', 'id'); @endphp
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-gray-900">{{ $platformNames[$integration['platform']] ?? (ucfirst($integration['platform']) . ' Ads') }}</p>
                                            @if($integration['status'] === 'needs_reauth')
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    {{ __('needs re-auth') }}
                                                </span>
                                            @elseif($integration['status'] === 'active')
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $integration['status'] }}
                                                </span>
                                            @else
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    {{ $integration['status'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex items-center text-sm text-gray-500">
                                            <x-heroicon-o-calendar class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                                            <p>{{ __('Connected') }} {{ $integration['created_at'] ? \Carbon\Carbon::parse($integration['created_at'])->diffForHumans() : '' }}</p>
                                            <span class="mx-2">&bull;</span>
                                            <p>{{ $integration['accounts_count'] }} {{ __('accounts') }}</p>
                                            @if($integration['user_name'])
                                                <span class="mx-2">&bull;</span>
                                                <p>{{ $integration['user_name'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($integration['status'] === 'needs_reauth')
                                        <button wire:click="reauthorizePlatform('{{ $integration['platform'] }}')" class="inline-flex items-center px-3 py-1.5 border border-amber-300 text-sm font-medium rounded-md text-amber-700 bg-white hover:bg-amber-50">
                                            <x-heroicon-o-key class="h-4 w-4 mr-1" />
                                            {{ __('Re-authorize') }}
                                        </button>
                                    @elseif($integration['status'] === 'active')
                                        <button wire:click="reauthorizePlatform('{{ $integration['platform'] }}')" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-600 bg-white hover:bg-gray-50">
                                            <x-heroicon-o-arrow-path class="h-4 w-4 mr-1" />
                                            {{ __('Reconnect') }}
                                        </button>
                                    @endif
                                    @if(isset($activeSyncs[$integration['platform']]))
                                        <button wire:click="openPlatformSyncModal('{{ $integration['platform'] }}')" class="inline-flex items-center px-3 py-1.5 border border-amber-300 text-sm font-medium rounded-md text-amber-700 bg-amber-50 hover:bg-amber-100">
                                            <svg class="animate-spin h-4 w-4 mr-1 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            {{ __('Syncing...') }}
                                        </button>
                                    @else
                                        <button wire:click="openPlatformSyncModal('{{ $integration['platform'] }}')" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <x-heroicon-o-arrow-path class="h-4 w-4 mr-1" />
                                            {{ __('Sync') }}
                                        </button>
                                    @endif
                                    <button wire:click="disconnectPlatform('{{ $integration['platform'] }}')" wire:confirm="{{ __('Are you sure you want to disconnect this platform?') }}" class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                        <x-heroicon-o-trash class="h-4 w-4 mr-1" />
                                        {{ __('Disconnect') }}
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Platform Sync Modal --}}
    @if($showPlatformSyncModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape.window="$wire.closePlatformSyncModal()">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePlatformSyncModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    @php
                        $syncPlatformNames = collect($platforms)->pluck('name', 'id');
                        $progressStatus = $syncProgress['status'] ?? null;
                    @endphp

                    {{-- INITIAL STATE: Confirmation --}}
                    @if(!$syncProgress)
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <x-heroicon-o-arrow-path class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ __('Sync') }} {{ $syncPlatformNames[$syncPlatform] ?? ucfirst($syncPlatform) }} {{ __('Metrics') }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ __('This will sync all metrics data from the platform. The process runs in the background and you can track its progress here.') }}
                            </p>
                        </div>
                        <div class="mt-5 sm:mt-6 flex space-x-3">
                            <button wire:click="closePlatformSyncModal" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </button>
                            <button wire:click="startPlatformSync" wire:loading.attr="disabled" wire:target="startPlatformSync" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                                <span wire:loading wire:target="startPlatformSync" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                                {{ __('Start Sync') }}
                            </button>
                        </div>

                    {{-- QUEUED STATE --}}
                    @elseif($progressStatus === 'queued')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                                <div class="h-5 w-5 rounded-full bg-yellow-400 animate-pulse"></div>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $syncPlatformNames[$syncPlatform] ?? ucfirst($syncPlatform) }} {{ __('Sync') }}
                                </h3>
                                <p class="mt-2 text-sm text-yellow-600 font-medium">
                                    {{ __('Queued, starting shortly...') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ __('Waiting for queue worker to pick up the job') }}
                                </p>
                            </div>
                        </div>

                    {{-- RUNNING STATE --}}
                    @elseif($progressStatus === 'running')
                        <div wire:poll.2s="pollSyncProgress">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                                <x-heroicon-o-arrow-path class="h-6 w-6 text-blue-600 animate-spin" />
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $syncPlatformNames[$syncPlatform] ?? ucfirst($syncPlatform) }} {{ __('Sync') }}
                                </h3>
                                <p class="mt-2 text-sm text-blue-600 font-medium">
                                    {{ $syncProgress['current_step'] ?? __('Syncing...') }}
                                </p>
                            </div>

                            {{-- Step log --}}
                            @if(!empty($syncProgress['steps']))
                                <div class="mt-4 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50" x-data x-effect="$el.scrollTop = $el.scrollHeight">
                                    <div class="space-y-2">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-3 w-3 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-3 w-3 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-3 w-3 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-700">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                    {{-- COMPLETED STATE --}}
                    @elseif($progressStatus === 'completed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <x-heroicon-s-check-circle class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ __('Sync Complete') }}
                            </h3>
                            <p class="mt-1 text-sm text-green-600 font-medium">
                                {{ $syncPlatformNames[$syncPlatform] ?? ucfirst($syncPlatform) }} {{ __('metrics synced successfully.') }}
                            </p>
                        </div>

                        {{-- Stats grid --}}
                        @if(!empty($syncProgress['stats']))
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @if(isset($syncProgress['stats']['accounts_processed']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ $syncProgress['stats']['accounts_processed'] }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Accounts') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['metrics_synced']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ number_format($syncProgress['stats']['metrics_synced']) }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Metrics Synced') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['campaigns_synced']))
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-gray-900">{{ $syncProgress['stats']['campaigns_synced'] }}</p>
                                        <p class="text-xs text-gray-500">{{ __('Campaigns') }}</p>
                                    </div>
                                @endif
                                @if(isset($syncProgress['stats']['errors']) && $syncProgress['stats']['errors'] > 0)
                                    <div class="bg-red-50 rounded-lg p-3 text-center">
                                        <p class="text-xl font-bold text-red-600">{{ $syncProgress['stats']['errors'] }}</p>
                                        <p class="text-xs text-red-500">{{ __('Errors') }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">{{ __('Show sync log') }} ({{ count($syncProgress['steps']) }} {{ __('steps') }})</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-600">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6">
                            <button wire:click="closePlatformSyncModal" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                {{ __('Done') }}
                            </button>
                        </div>

                    {{-- FAILED STATE --}}
                    @elseif($progressStatus === 'failed')
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <x-heroicon-s-x-circle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ __('Sync Failed') }}
                            </h3>
                            <p class="mt-2 text-sm text-red-600">
                                {{ $syncProgress['error'] ?? __('An unknown error occurred.') }}
                            </p>
                        </div>

                        {{-- Step log (collapsed) --}}
                        @if(!empty($syncProgress['steps']))
                            <details class="mt-3">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">{{ __('Show sync log') }} ({{ count($syncProgress['steps']) }} {{ __('steps') }})</summary>
                                <div class="mt-2 max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="space-y-1">
                                        @foreach($syncProgress['steps'] as $step)
                                            <div class="flex items-start text-xs">
                                                <div class="flex-shrink-0 mt-0.5 mr-2">
                                                    @if(($step['type'] ?? 'info') === 'success')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-green-400"></div>
                                                    @elseif(($step['type'] ?? 'info') === 'error')
                                                        <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                                                    @else
                                                        <div class="h-2.5 w-2.5 rounded-full bg-blue-400"></div>
                                                    @endif
                                                </div>
                                                <span class="text-gray-600">{{ $step['message'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        <div class="mt-5 sm:mt-6 flex space-x-3">
                            <button wire:click="closePlatformSyncModal" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('Close') }}
                            </button>
                            <button wire:click="retrySyncPlatform" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" />
                                {{ __('Retry') }}
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
