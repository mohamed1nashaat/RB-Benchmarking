<div class="ai-section-heading"><div><h2>Data &amp; settings</h2><p>Manage connections, imports and monthly snapshots.</p></div><button type="button" wire:click="openAiSnapshotForm()" class="btn-secondary">Edit monthly snapshot</button></div>
    {{-- ── Google Connection Panel ─────────────────────────────────────────── --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <h3 class="text-sm font-semibold text-white">Google Connection</h3>
                @if($seoHasIntegration)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-500/15 text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                        Connected
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-amber-500/15 text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                        Not connected
                    </span>
                @endif
                @if($seoSaved)
                    <span class="text-xs text-emerald-400 font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Saved
                    </span>
                @endif
            </div>
            @if(!$seoHasIntegration)
                <a href="{{ route('integrations') }}" wire:navigate class="text-xs text-brand-400 hover:text-brand-300 underline">Connect Google →</a>
            @else
                <button wire:click="refreshGoogleLists" wire:loading.attr="disabled"
                    class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 transition-colors disabled:opacity-50">
                    <svg wire:loading wire:target="refreshGoogleLists" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="refreshGoogleLists" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh
                </button>
            @endif
        </div>

        @if(!$seoHasIntegration)
            <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-500/8 border border-amber-500/20 text-xs text-amber-300 mb-0">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <span>Connect a Google account under <a href="{{ route('integrations') }}" wire:navigate class="underline font-medium">Integrations</a> to enable live data sync from GA4 and Search Console.</span>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Search Console Site --}}
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1">Search Console Site</label>
                    <div class="relative">
                        <select wire:model="seoSearchConsoleSite" class="input-field pe-8">
                            <option value="">— Select site —</option>
                            @foreach($seoSearchConsoleSites as $site)
                                <option value="{{ $site['site_url'] }}">{{ $site['site_url'] }}</option>
                            @endforeach
                        </select>
                        @if($seoLoadingSites)
                            <div class="absolute end-8 top-2.5">
                                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- GA4 Property --}}
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1">GA4 Property</label>
                    <div class="relative">
                        <select wire:model="seoGa4PropertyId" class="input-field pe-8">
                            <option value="">— Select property —</option>
                            @foreach($seoGa4Properties as $prop)
                                <option value="{{ $prop['property_id'] }}">{{ $prop['display_name'] }} ({{ $prop['account_name'] ?? '' }})</option>
                            @endforeach
                        </select>
                        @if($seoLoadingProperties)
                            <div class="absolute end-8 top-2.5">
                                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3 flex-wrap">
                <button type="button" wire:click="saveAiGoogleSettings"
                    wire:loading.attr="disabled" wire:target="saveAiGoogleSettings"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity disabled:opacity-50"
                    style="background: var(--brand-500, #ef4444);">
                    <svg wire:loading wire:target="saveAiGoogleSettings" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="saveAiGoogleSettings" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span wire:loading.remove wire:target="saveAiGoogleSettings">Save &amp; Sync (24 months)</span>
                    <span wire:loading wire:target="saveAiGoogleSettings">Syncing…</span>
                </button>
                <p class="text-xs text-gray-600">Saves selection and pulls historical data from GA4 + Search Console.</p>
            </div>
            @if($aiSyncSuccess)
                <div class="mt-3 flex items-center gap-2 text-xs text-emerald-400">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    {{ $aiSyncSuccess }}
                </div>
            @endif
            @if($aiSyncError)
                <div class="mt-3 flex items-center gap-2 text-xs text-red-400">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    {{ $aiSyncError }}
                </div>
            @endif
        @endif
    </div>

    {{-- ── Upload Panel ──────────────────────────────────────────────────── --}}
    <div class="card p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="max-w-2xl">
                <h3 class="text-base font-semibold tracking-tight text-white">Upload monthly AI visibility report</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">Upload everything for the month in one go: the Excel workbook (Search Console, GA4, Microsoft Clarity and the Semrush overview) and the Semrush AI Visibility exports, <span class="text-gray-300">Brand topics</span> and <span class="text-gray-300">Prompts</span> CSVs for the client and each competitor. Each file is identified by its columns and checked; nothing is saved until you press Import.</p>
            </div>
            <label for="ai-import-files" class="btn-secondary cursor-pointer">
                <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                <span wire:loading.remove wire:target="aiImportFiles">{{ $aiImportFiles ? 'Choose other files' : 'Choose files' }}</span>
                <span wire:loading wire:target="aiImportFiles">Checking…</span>
            </label>
            <input type="file" id="ai-import-files" wire:model="aiImportFiles" multiple accept=".xlsx,.csv" class="hidden">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 max-w-2xl">
            <label class="block">
                <span class="micro-label">Semrush capture date</span>
                <input type="date" wire:model.live="aiImportDate" max="{{ now()->toDateString() }}" class="input-field mt-1.5">
            </label>
            <label class="block">
                <span class="micro-label">Client website</span>
                <input type="text" wire:model.live.debounce.500ms="aiImportDomain" placeholder="alshifahoney.com" class="input-field mt-1.5">
            </label>
        </div>
        @error('aiImportFiles.*')<p class="mt-3 text-xs text-red-400">{{ $message }}</p>@enderror

        @if($aiImportReport)
            @php $rep = $aiImportReport; $blocking = count($rep['errors']) + collect($rep['files'])->sum(fn ($f) => count($f['errors'])); @endphp
            <div class="mt-5 overflow-x-auto rounded-xl air-upload" wire:loading.class="opacity-50" wire:target="aiImportFiles,aiImportDate,aiImportDomain,removeAiImportFile">
                <table class="data-table w-full">
                    <thead><tr><th>File</th><th>Detected as</th><th>Brand</th><th class="text-end">Rows</th><th>Checks</th><th></th></tr></thead>
                    <tbody>
                    @foreach($rep['files'] as $i => $f)
                        <tr>
                            <td class="font-medium">
                                <span class="inline-flex items-center gap-2">
                                    @if($f['errors'])<x-heroicon-o-x-circle class="w-4 h-4 text-red-400 flex-shrink-0" />
                                    @elseif($f['warnings'])<x-heroicon-o-exclamation-triangle class="w-4 h-4 text-amber-400 flex-shrink-0" />
                                    @else<x-heroicon-o-check-circle class="w-4 h-4 text-green-400 flex-shrink-0" />@endif
                                    <span class="truncate max-w-[220px]" title="{{ $f['name'] }}">{{ $f['name'] }}</span>
                                </span>
                            </td>
                            <td>{{ $f['label'] }}</td>
                            <td>@if($f['domain']){{ $f['domain'] }} <span class="text-xs text-gray-500">{{ $f['role'] === 'client' ? 'client' : ($f['role'] === 'competitor' ? 'competitor' : '') }}</span>@else<span class="text-gray-500">—</span>@endif</td>
                            <td class="text-end">{{ $f['rows'] !== null ? number_format($f['rows']) : '—' }}</td>
                            <td class="air-checks">
                                @if($f['summary'])<p class="text-xs text-gray-400">{{ $f['summary'] }}</p>@endif
                                @foreach($f['errors'] as $e)<p class="text-xs text-red-400 mt-1">{{ $e }}</p>@endforeach
                                @foreach($f['warnings'] as $w)<p class="text-xs text-amber-400 mt-1">{{ $w }}</p>@endforeach
                            </td>
                            <td class="text-end"><button type="button" wire:click="removeAiImportFile({{ $i }})" class="text-xs text-gray-500 hover:text-white" aria-label="Remove {{ $f['name'] }}">Remove</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($rep['errors'] || $rep['warnings'])
                <div class="mt-4 space-y-1.5">
                    @foreach($rep['errors'] as $e)<p class="flex items-start gap-2 text-xs text-red-400"><x-heroicon-o-x-circle class="w-4 h-4 flex-shrink-0" />{{ $e }}</p>@endforeach
                    @foreach($rep['warnings'] as $w)<p class="flex items-start gap-2 text-xs text-amber-400"><x-heroicon-o-exclamation-triangle class="w-4 h-4 flex-shrink-0" />{{ $w }}</p>@endforeach
                </div>
            @endif

            <div class="mt-5 flex items-center gap-3 flex-wrap">
                <button type="button" wire:click="confirmAiImport" wire:loading.attr="disabled" wire:target="confirmAiImport" class="btn-primary disabled:opacity-50" @disabled(!$rep['ok'])>
                    <span wire:loading.remove wire:target="confirmAiImport">Import {{ count($rep['files']) }} {{ count($rep['files']) === 1 ? 'file' : 'files' }}{{ $rep['month'] ? ' for '.\Carbon\Carbon::parse($rep['month'].'-01')->format('F Y') : '' }}</span>
                    <span wire:loading wire:target="confirmAiImport">Importing…</span>
                </button>
                <button type="button" wire:click="clearAiImport" class="btn-ghost">Cancel</button>
                <p class="text-xs {{ $blocking ? 'text-red-400' : 'text-gray-500' }}">
                    {{ $blocking ? $blocking.' '.($blocking === 1 ? 'problem' : 'problems').' to fix first' : 'All checks passed. Everything is saved together, or nothing is.' }}
                </p>
            </div>
        @endif
        @if($aiImportError)
            <p class="mt-3 flex items-center gap-2 text-xs text-red-400"><x-heroicon-o-x-circle class="w-4 h-4 flex-shrink-0" />{{ $aiImportError }}</p>
        @endif
        @if($aiImportResult)
            <p class="mt-3 flex items-center gap-2 text-xs text-green-400"><x-heroicon-o-check-circle class="w-4 h-4 flex-shrink-0" />{{ $aiImportResult }}</p>
        @endif
        {{-- Sync from Google button --}}
        <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-gray-400 font-medium">Sync live data from Google</p>
                <p class="text-xs text-gray-600 mt-0.5">Pulls AI Direct sessions from GA4 + AI Overview impressions from Search Console (24 months history).</p>
            </div>
            <button wire:click="pullLiveAiVisibilityData" wire:loading.attr="disabled"
                class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-medium text-white border border-white/15 bg-white/5 hover:border-white/30 hover:bg-white/10 transition-colors disabled:opacity-50">
                <svg wire:loading wire:target="pullLiveAiVisibilityData" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg wire:loading.remove wire:target="pullLiveAiVisibilityData" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span wire:loading.remove wire:target="pullLiveAiVisibilityData">Sync from Google</span>
                <span wire:loading wire:target="pullLiveAiVisibilityData">Syncing…</span>
            </button>
        </div>
        @if($aiSyncSuccess)
            <div class="mt-3 flex items-center gap-2 text-xs text-emerald-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ $aiSyncSuccess }}
            </div>
        @endif
        @if($aiSyncError)
            <div class="mt-3 flex items-center gap-2 text-xs text-red-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                {{ $aiSyncError }}
            </div>
        @endif
        @if($aiUploadSuccess)
            <div class="mt-3 flex items-center gap-2 text-xs text-emerald-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ $aiUploadSuccess }}
            </div>
        @endif
        @if($aiUploadError)
            <div class="mt-3 flex items-center gap-2 text-xs text-red-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                {{ $aiUploadError }}
            </div>
        @endif
    </div>
