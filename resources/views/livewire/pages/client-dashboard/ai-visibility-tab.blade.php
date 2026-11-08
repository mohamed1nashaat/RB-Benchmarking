{{-- AI Visibility Tab — index 3 --}}
@if($activeTabIndex === 3)
<div class="space-y-6">

    {{-- ── Upload Panel ──────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h3 class="text-sm font-semibold text-white">Upload Monthly AI Visibility Report</h3>
                <p class="text-xs text-gray-500 mt-0.5">Upload the Excel file exported from your AI visibility tracker. Data is merged — existing months are updated.</p>
            </div>
            <form wire:submit.prevent="uploadAiVisibility" class="flex items-center gap-3 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="file" wire:model="aiVisibilityFile" accept=".xlsx" class="hidden" id="ai-vis-file-input">
                    <label for="ai-vis-file-input"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-white/15 bg-white/5 text-xs text-gray-300 cursor-pointer hover:border-white/30 transition-colors">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                        </svg>
                        <span>{{ $aiVisibilityFile ? $aiVisibilityFile->getClientOriginalName() : 'Choose .xlsx file' }}</span>
                    </label>
                </label>
                <button type="submit" wire:loading.attr="disabled"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity"
                    style="background: var(--brand-500, #ef4444);">
                    <svg wire:loading wire:target="uploadAiVisibility" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="uploadAiVisibility">Import</span>
                    <span wire:loading wire:target="uploadAiVisibility">Importing…</span>
                </button>
            </form>
        </div>
        {{-- Sync from Google button --}}
        <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-gray-400 font-medium">Sync live data from Google</p>
                <p class="text-xs text-gray-600 mt-0.5">Pulls AI Direct sessions from GA4 + AI Overview impressions from Search Console (last 13 months).</p>
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

    @if(empty($aiMonths) && !$aiSnapshot)
    {{-- ── Empty state ───────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-white/10 bg-white/[0.02] flex flex-col items-center justify-center py-20 text-center px-6">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(239,68,68,0.12);">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ef4444">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-white">No AI visibility data yet</h3>
        <p class="text-xs text-gray-500 mt-1 max-w-sm">Upload the monthly Excel report above to start tracking this client's AI citation presence across Google AI Overviews, ChatGPT, Gemini, Perplexity, and more.</p>
    </div>
    @endif

    @if(!empty($aiMonths) || $aiSnapshot)

    @php
        $months     = collect($aiMonths);
        $latestM    = $months->last();
        $prevM      = $months->slice(-2, 1)->first();
        $monthLabels = $months->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values();

        $totalAiImpr  = $months->sum('ai_impressions');
        $totalAiSess  = $months->sum('ai_sessions');
        $totalAiRev   = $months->sum('ai_revenue');
        $totalAiKE    = $months->sum('ai_key_events');
        $semrushScore = $aiSnapshot['semrush_score'] ?? null;
        $clarityCit   = $aiSnapshot['clarity_citations'] ?? null;

        $aiDelta = fn($a, $b) => (!isset($a) || !isset($b) || (float)$b == 0.0) ? null : round(($a - $b) / $b * 100, 1);
        $impressionsDelta = $aiDelta($latestM['ai_impressions'] ?? null, $prevM['ai_impressions'] ?? null);
        $sessionsDelta    = $aiDelta($latestM['ai_sessions'] ?? null, $prevM['ai_sessions'] ?? null);
        $revDelta         = $aiDelta($latestM['ai_revenue'] ?? null, $prevM['ai_revenue'] ?? null);
        $keDelta          = $aiDelta($latestM['ai_key_events'] ?? null, $prevM['ai_key_events'] ?? null);
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── KPI Row ───────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        {{-- AI Impressions --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">AI Impressions</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalAiImpr ? number_format($totalAiImpr) : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">Google AI Overviews</p>
            @if($impressionsDelta !== null)
                <p class="text-[11px] mt-1 {{ $impressionsDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $impressionsDelta >= 0 ? '▲' : '▼' }} {{ abs($impressionsDelta) }}%</p>
            @endif
        </div>
        {{-- AI Sessions --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">AI Sessions</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalAiSess ? number_format($totalAiSess) : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">ChatGPT · Gemini · etc.</p>
            @if($sessionsDelta !== null)
                <p class="text-[11px] mt-1 {{ $sessionsDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $sessionsDelta >= 0 ? '▲' : '▼' }} {{ abs($sessionsDelta) }}%</p>
            @endif
        </div>
        {{-- AI Revenue --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">AI Revenue</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalAiRev ? 'SAR ' . number_format($totalAiRev, 0) : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">From AI-referred sessions</p>
            @if($revDelta !== null)
                <p class="text-[11px] mt-1 {{ $revDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $revDelta >= 0 ? '▲' : '▼' }} {{ abs($revDelta) }}%</p>
            @endif
        </div>
        {{-- AI Key Events --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">AI Conversions</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalAiKE ? number_format($totalAiKE) : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">Key events from AI traffic</p>
            @if($keDelta !== null)
                <p class="text-[11px] mt-1 {{ $keDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $keDelta >= 0 ? '▲' : '▼' }} {{ abs($keDelta) }}%</p>
            @endif
        </div>
        {{-- Semrush AI Score --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">Semrush AI Score</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $semrushScore !== null ? $semrushScore . ' / 100' : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">
                @if($aiSnapshot['semrush_topics_count'] ?? null){{ $aiSnapshot['semrush_topics_count'] }} topics · {{ $aiSnapshot['semrush_prompts_count'] ?? '?' }} prompts
                @else AI brand visibility
                @endif
            </p>
        </div>
        {{-- Copilot Citations --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium leading-tight">AI Citations</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $clarityCit !== null ? number_format($clarityCit) : '—' }}</p>
            <p class="text-[11px] text-gray-600 mt-0.5">Microsoft Clarity · Copilot</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Google AI Search (GSC) ────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(count($aiMonths) > 1)
    <div>
        <h2 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
            Google AI Overviews — Search Console
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- AI Impressions trend --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-3">AI Impressions by Month</h3>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_impressions')->map(fn($v) => (int)($v ?? 0))->values());
                        const color  = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';
                        new Chart(this.$refs.cv, { type: 'line', data: { labels, datasets: [{ label: 'AI Impressions', data: vals, borderColor: color, backgroundColor: color + '18', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 4, pointBackgroundColor: color }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 }, callback: v => v >= 1000 ? (v/1000).toFixed(0)+'K' : v } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
            {{-- AI Impression Share % trend --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-1">AI Impression Share %</h3>
                <p class="text-[11px] text-gray-600 mb-3">What % of total GSC impressions appear in AI Overviews</p>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_impression_share')->map(fn($v) => $v ? round($v * 100, 2) : 0)->values());
                        const color  = '#60a5fa';
                        new Chart(this.$refs.cv, { type: 'line', data: { labels, datasets: [{ label: 'AI Share %', data: vals, borderColor: color, backgroundColor: color + '18', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 4, pointBackgroundColor: color }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 }, callback: v => v + '%' } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── GA4 AI Traffic ────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(count($aiMonths) > 1)
    <div>
        <h2 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
            AI Sessions & Revenue — Google Analytics 4
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Sessions by month --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-3">AI Sessions by Month</h3>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_sessions')->map(fn($v) => (int)($v ?? 0))->values());
                        const color  = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';
                        new Chart(this.$refs.cv, { type: 'bar', data: { labels, datasets: [{ label: 'AI Sessions', data: vals, backgroundColor: color + '55', borderColor: color, borderWidth: 1.5, borderRadius: 4 }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
            {{-- Revenue by month --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-1">AI Revenue by Month</h3>
                <p class="text-[11px] text-gray-600 mb-3">Revenue generated from AI-referred sessions (SAR)</p>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_revenue')->map(fn($v) => round((float)($v ?? 0), 0))->values());
                        const color  = '#34d399';
                        new Chart(this.$refs.cv, { type: 'bar', data: { labels, datasets: [{ label: 'AI Revenue (SAR)', data: vals, backgroundColor: color + '55', borderColor: color, borderWidth: 1.5, borderRadius: 4 }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 }, callback: v => 'SAR ' + v } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            {{-- Key Events --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-1">AI Key Events (Conversions)</h3>
                <p class="text-[11px] text-gray-600 mb-3">Purchase / lead events from AI-referred sessions</p>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_key_events')->map(fn($v) => (int)($v ?? 0))->values());
                        const color  = '#fbbf24';
                        new Chart(this.$refs.cv, { type: 'bar', data: { labels, datasets: [{ label: 'Key Events', data: vals, backgroundColor: color + '55', borderColor: color, borderWidth: 1.5, borderRadius: 4 }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
            {{-- AI Session Share % --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-1">AI Session Share %</h3>
                <p class="text-[11px] text-gray-600 mb-3">AI-referred sessions as % of total sessions (GA4)</p>
                <div x-data="{
                    init() {
                        const labels = @js($monthLabels);
                        const vals   = @js($months->pluck('ai_session_share')->map(fn($v) => $v ? round($v * 100, 2) : 0)->values());
                        const color  = '#a78bfa';
                        new Chart(this.$refs.cv, { type: 'line', data: { labels, datasets: [{ label: 'Session Share %', data: vals, borderColor: color, backgroundColor: color + '18', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 4, pointBackgroundColor: color }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 }, callback: v => v + '%' } } } } });
                    }
                }"><div style="height:200px;"><canvas x-ref="cv"></canvas></div></div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── AI Platform Breakdown ─────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($aiSnapshot['ai_platforms']))
    @php
        $platforms = collect($aiSnapshot['ai_platforms'])->take(6);
        $totalPlatSess = max(1, $platforms->sum('total_sessions'));
        $totalPlatRev  = max(1, $platforms->sum('revenue'));
        $platformColors = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7'];
    @endphp
    <div>
        <h2 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" /></svg>
            AI Platform Breakdown
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Sessions --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-4">Sessions by Platform</h3>
                <div class="space-y-3">
                    @foreach($platforms as $i => $p)
                        @php $pct = round(($p['total_sessions'] ?? 0) / $totalPlatSess * 100); @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-300">{{ $p['platform'] }}</span>
                                <span class="text-gray-500 tabular-nums">{{ number_format($p['total_sessions'] ?? 0) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $platformColors[$i % 6] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- Revenue --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-4">Revenue by Platform (SAR)</h3>
                <div class="space-y-3">
                    @foreach($platforms as $i => $p)
                        @php
                            $rev = (float)($p['revenue'] ?? 0);
                            $pct = $totalPlatRev > 1 ? round($rev / $totalPlatRev * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-300">{{ $p['platform'] }}</span>
                                <span class="text-gray-500 tabular-nums">{{ $rev > 0 ? 'SAR ' . number_format($rev, 0) : '—' }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $platformColors[$i % 6] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- Key Events --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-4">Key Events by Platform</h3>
                @php $maxKE = max(1, $platforms->max('key_events')); @endphp
                <div class="space-y-3">
                    @foreach($platforms as $i => $p)
                        @php $ke = (int)($p['key_events'] ?? 0); $pct = round($ke / $maxKE * 100); @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-300">{{ $p['platform'] }}</span>
                                <span class="text-gray-500 tabular-nums">{{ $ke > 0 ? $ke . ' events' : '—' }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $platformColors[$i % 6] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── AI Shopping Funnel ────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($aiSnapshot['ai_funnel']))
    @php
        $funnel = collect($aiSnapshot['ai_funnel'])->filter(fn($f) => ($f['total'] ?? 0) > 0)->values();
        $topVal = $funnel->max('total') ?: 1;
    @endphp
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-white">AI Assistant — Shopping Funnel</h3>
                <p class="text-xs text-gray-500 mt-0.5">Conversion steps for sessions originating from AI assistants</p>
            </div>
            @if($funnel->count() >= 2)
                @php
                    $overall = round(($funnel->last()['total'] / $funnel->first()['total']) * 100, 1);
                @endphp
                <div class="text-right flex-shrink-0">
                    <p class="text-[11px] text-gray-500">Overall conversion</p>
                    <p class="text-lg font-bold text-white tabular-nums">{{ $overall }}%</p>
                </div>
            @endif
        </div>
        <div class="space-y-2">
            @foreach($funnel as $idx => $step)
                @php
                    $pct = round(($step['total'] ?? 0) / $topVal * 100);
                    $stepLabel = match($step['event'] ?? '') {
                        'session_start'  => 'Session Start',
                        'view_item'      => 'View Item',
                        'add_to_cart'    => 'Add to Cart',
                        'view_cart'      => 'View Cart',
                        'begin_checkout' => 'Begin Checkout',
                        'purchase'       => 'Purchase',
                        default          => $step['event'] ?? '',
                    };
                    $prevStep = $funnel->get($idx - 1);
                    $dropRate = ($idx > 0 && $prevStep && $prevStep['total'] > 0)
                        ? round(($step['total'] / $prevStep['total']) * 100, 1)
                        : null;
                @endphp
                <div class="flex items-center gap-3">
                    <div class="w-28 text-xs text-gray-400 text-right flex-shrink-0">
                        {{ $stepLabel }}
                        @if($dropRate !== null)
                            <span class="text-[10px] text-gray-600 block">↳ {{ $dropRate }}%</span>
                        @endif
                    </div>
                    <div class="flex-1 h-7 rounded-lg bg-white/5 overflow-hidden relative">
                        <div class="h-full rounded-lg transition-all" style="width:{{ $pct }}%;background:rgba(239,68,68,{{ 0.3 + ($pct / 100) * 0.4 }});"></div>
                        <span class="absolute inset-0 flex items-center ps-3 text-[11px] text-white/90 tabular-nums font-medium">{{ number_format($step['total'] ?? 0) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Top AI Pages + Countries ─────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if(!empty($aiSnapshot['top_ai_pages']))
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-3">Top Pages in Google AI Overviews</h3>
            <p class="text-[11px] text-gray-600 mb-3">Pages with the most AI impression appearances</p>
            @php $maxPageImpr = max(1, collect($aiSnapshot['top_ai_pages'])->max('impressions')); @endphp
            <div class="space-y-2.5">
                @foreach(collect($aiSnapshot['top_ai_pages'])->take(10) as $page)
                    @php
                        $url = $page['url'] ?? '';
                        $path = preg_replace('#^https?://[^/]+#', '', $url);
                        $path = strlen($path) > 55 ? substr($path, 0, 55) . '…' : $path;
                        $pct  = round(($page['impressions'] ?? 0) / $maxPageImpr * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="text-gray-400 truncate max-w-[70%]" title="{{ $url }}">{{ $path ?: $url }}</span>
                            <span class="text-gray-500 tabular-nums flex-shrink-0 ms-2">{{ number_format($page['impressions'] ?? 0) }}</span>
                        </div>
                        <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ $pct }}%;background:var(--brand-500,#ef4444);opacity:0.6;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($aiSnapshot['top_ai_countries']))
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-3">AI Impressions by Country</h3>
            <p class="text-[11px] text-gray-600 mb-3">Geographic distribution of Google AI Overviews appearances</p>
            @php $maxCountryImpr = max(1, collect($aiSnapshot['top_ai_countries'])->max('impressions')); @endphp
            <div class="space-y-2.5">
                @foreach(collect($aiSnapshot['top_ai_countries'])->take(10) as $idx => $country)
                    @php
                        $pct = round(($country['impressions'] ?? 0) / $maxCountryImpr * 100);
                        $share = $totalAiImpr > 0 ? round(($country['impressions'] ?? 0) / $totalAiImpr * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="text-gray-400">{{ $country['country'] ?? '—' }}</span>
                            <span class="text-gray-500 tabular-nums">{{ number_format($country['impressions'] ?? 0) }} <span class="text-gray-700">({{ $share }}%)</span></span>
                        </div>
                        <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7','#ec4899','#06b6d4','#84cc16','#f59e0b'][$idx % 10] }};opacity:0.7;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Clarity / Copilot AI Citations ───────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($aiSnapshot['clarity_data']))
    @php
        $cd = $aiSnapshot['clarity_data'];
        $totalCit    = (int)($cd['Citations'] ?? $clarityCit ?? 0);
        $brandedCit  = (int)($cd['Branded citations'] ?? 0);
        $nonBrandCit = (int)($cd['Non-branded citations'] ?? 0);
        $aiSessClarity = (int)($cd['AI-referred sessions (Clarity)'] ?? 0);
        $shareAuth   = isset($cd['Share of authority (all citations)']) ? round((float)$cd['Share of authority (all citations)'] * 100, 1) : null;
        $brandShare  = isset($cd['Branded share of authority']) ? round((float)$cd['Branded share of authority'] * 100, 1) : null;
        $nonBrandShare = isset($cd['Non-branded share of authority']) ? round((float)$cd['Non-branded share of authority'] * 100, 1) : null;
        $citOtherDomains = (int)($cd['Citations to other domains'] ?? 0);
        $perfScore   = $cd['Performance score'] ?? null;
        $lcp         = $cd['LCP (sec)'] ?? null;
        $inp         = $cd['INP (ms)'] ?? null;
        $cls         = $cd['CLS'] ?? null;
        $pagesPerSess = $cd['Pages per session'] ?? null;
        $activeTime  = $cd['Active time (sec)'] ?? null;

        // Topic categories — numeric values in clarity_data are citation counts per category
        $topicCategories = collect($cd)->filter(fn($v) => is_numeric($v) && (int)$v > 10 && !in_array(array_search($v, $cd), [
            'Citations', 'Branded citations', 'Non-branded citations',
            'AI-referred sessions (Clarity)', 'Citations to other domains',
            '/', '/ar', '/en',
        ]))->map(fn($v, $k) => ['name' => $k, 'count' => (int)$v])
          ->filter(fn($item) => !str_starts_with($item['name'], '/'))
          ->sortByDesc('count')->values()->take(6);
    @endphp
    <div>
        <h2 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
            Microsoft Clarity — Copilot & AI Citations
        </h2>

        {{-- KPI tiles --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">Total Citations</p>
                <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $totalCit ? number_format($totalCit) : '—' }}</p>
                <p class="text-[11px] text-gray-600 mt-0.5">Times cited by AI</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">Branded Citations</p>
                <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $brandedCit ? number_format($brandedCit) : '—' }}</p>
                <p class="text-[11px] text-gray-600 mt-0.5">{{ $totalCit > 0 ? round($brandedCit / $totalCit * 100) . '% of total' : 'Brand queries' }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">Non-Branded</p>
                <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $nonBrandCit ? number_format($nonBrandCit) : '—' }}</p>
                <p class="text-[11px] text-gray-600 mt-0.5">{{ $totalCit > 0 ? round($nonBrandCit / $totalCit * 100) . '% of total' : 'Generic queries' }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">AI Sessions</p>
                <p class="text-2xl font-bold text-white mt-1 tabular-nums">{{ $aiSessClarity ? number_format($aiSessClarity) : '—' }}</p>
                <p class="text-[11px] text-gray-600 mt-0.5">Via Clarity tracking</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Share of Authority + Branded vs Non-Branded visual --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-4">Share of Authority</h3>
                <div class="space-y-3">
                    @if($shareAuth !== null)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-400">Overall (all citations)</span>
                            <span class="text-white font-medium tabular-nums">{{ $shareAuth }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ min(100,$shareAuth) }}%;background:var(--brand-500,#ef4444);"></div>
                        </div>
                    </div>
                    @endif
                    @if($brandShare !== null)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-400">Branded queries</span>
                            <span class="text-white tabular-nums">{{ $brandShare }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ min(100,$brandShare) }}%;background:#3b82f6;"></div>
                        </div>
                    </div>
                    @endif
                    @if($nonBrandShare !== null)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-400">Non-branded queries</span>
                            <span class="text-white tabular-nums">{{ $nonBrandShare }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width:{{ min(100,$nonBrandShare) }}%;background:#a78bfa;"></div>
                        </div>
                    </div>
                    @endif
                    @if($citOtherDomains)
                    <div class="pt-2 border-t border-white/5 mt-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Citations to other domains (context)</span>
                            <span class="text-gray-400 tabular-nums">{{ number_format($citOtherDomains) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Topic categories --}}
            @if($topicCategories->count() > 0)
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <h3 class="text-xs font-semibold text-gray-300 mb-4">Citations by Topic Category</h3>
                @php $maxTopicCit = max(1, $topicCategories->max('count')); @endphp
                <div class="space-y-3">
                    @foreach($topicCategories as $i => $tc)
                        @php $pct = round($tc['count'] / $maxTopicCit * 100); @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-300">{{ $tc['name'] }}</span>
                                <span class="text-gray-500 tabular-nums">{{ number_format($tc['count']) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7'][$i % 6] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Core Web Vitals --}}
        @if($perfScore || $lcp || $inp || $cls)
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5 mt-4">
            <h3 class="text-xs font-semibold text-gray-300 mb-4">Core Web Vitals — AI-Cited Pages</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if($perfScore !== null)
                <div class="text-center">
                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">Performance Score</p>
                    @php $perfColor = $perfScore >= 90 ? '#22c55e' : ($perfScore >= 50 ? '#eab308' : '#ef4444'); @endphp
                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color:{{ $perfColor }}">{{ $perfScore }}</p>
                    <p class="text-[11px] text-gray-600">/ 100</p>
                </div>
                @endif
                @if($lcp !== null)
                <div class="text-center">
                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">LCP</p>
                    @php $lcpColor = $lcp <= 2.5 ? '#22c55e' : ($lcp <= 4 ? '#eab308' : '#ef4444'); @endphp
                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color:{{ $lcpColor }}">{{ $lcp }}s</p>
                    <p class="text-[11px] text-gray-600">{{ $lcp <= 2.5 ? 'Good' : ($lcp <= 4 ? 'Needs work' : 'Poor') }}</p>
                </div>
                @endif
                @if($inp !== null)
                <div class="text-center">
                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">INP</p>
                    @php $inpColor = $inp <= 200 ? '#22c55e' : ($inp <= 500 ? '#eab308' : '#ef4444'); @endphp
                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color:{{ $inpColor }}">{{ $inp }}ms</p>
                    <p class="text-[11px] text-gray-600">{{ $inp <= 200 ? 'Good' : ($inp <= 500 ? 'Needs work' : 'Poor') }}</p>
                </div>
                @endif
                @if($cls !== null)
                <div class="text-center">
                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">CLS</p>
                    @php $clsColor = $cls <= 0.1 ? '#22c55e' : ($cls <= 0.25 ? '#eab308' : '#ef4444'); @endphp
                    <p class="text-2xl font-bold mt-1 tabular-nums" style="color:{{ $clsColor }}">{{ $cls }}</p>
                    <p class="text-[11px] text-gray-600">{{ $cls <= 0.1 ? 'Good' : ($cls <= 0.25 ? 'Needs work' : 'Poor') }}</p>
                </div>
                @endif
            </div>
            @if($pagesPerSess || $activeTime)
            <div class="flex items-center gap-6 mt-4 pt-4 border-t border-white/5">
                @if($pagesPerSess)
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="text-white font-medium">{{ $pagesPerSess }}</span> pages / session
                </div>
                @endif
                @if($activeTime)
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="text-white font-medium">{{ $activeTime }}s</span> active time / session
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Semrush AI Topics ─────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($aiSnapshot['semrush_top_topics']))
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-white">Semrush AI Topics</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($aiSnapshot['semrush_topics_count'] ?? null)
                        {{ $aiSnapshot['semrush_topics_count'] }} topics tracked ·
                        {{ $aiSnapshot['semrush_prompts_count'] ?? '?' }} AI prompts ·
                        showing top {{ count($aiSnapshot['semrush_top_topics']) }}
                    @endif
                </p>
            </div>
            @if($semrushScore !== null)
            <div class="flex-shrink-0 text-right">
                <p class="text-[11px] text-gray-500">AI Score</p>
                <p class="text-xl font-bold tabular-nums {{ $semrushScore >= 50 ? 'text-emerald-400' : ($semrushScore >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">
                    {{ $semrushScore }}<span class="text-sm text-gray-600 font-normal"> / 100</span>
                </p>
            </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="pb-2.5 text-gray-500 font-medium pr-4">Topic</th>
                        <th class="pb-2.5 text-gray-500 font-medium pr-4">Market</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">AI Visibility</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">Difficulty</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">Mentions</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right">Search Vol.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach(collect($aiSnapshot['semrush_top_topics']) as $topic)
                        @php
                            $vis  = $topic['visibility'] ?? 0;
                            $diff = $topic['difficulty'] ?? 0;
                            $visColor = $vis >= 50 ? '#22c55e' : ($vis >= 20 ? '#eab308' : '#6b7280');
                            $diffColor = $diff <= 30 ? '#22c55e' : ($diff <= 60 ? '#eab308' : '#ef4444');
                        @endphp
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="py-2.5 text-gray-200 pr-4">{{ $topic['topic'] }}</td>
                            <td class="py-2.5 text-gray-500 pr-4">{{ $topic['market'] ?? '—' }}</td>
                            <td class="py-2.5 text-right pr-4">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full rounded-full" style="width:{{ $vis }}%;background:{{ $visColor }};"></div>
                                    </div>
                                    <span class="tabular-nums font-medium w-6 text-right" style="color:{{ $visColor }}">{{ $vis }}</span>
                                </div>
                            </td>
                            <td class="py-2.5 text-right pr-4">
                                <span class="tabular-nums" style="color:{{ $diffColor }}">{{ $diff }}</span>
                            </td>
                            <td class="py-2.5 text-gray-400 tabular-nums text-right pr-4">{{ $topic['mentions'] ?? '—' }}</td>
                            <td class="py-2.5 text-gray-500 tabular-nums text-right">{{ number_format($topic['volume'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @endif {{-- end if has data --}}

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Competitor Analysis ────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="pt-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-white">Competitor Analysis</h2>
                <p class="text-xs text-gray-500 mt-0.5">Track competitor AI visibility metrics for side-by-side comparison.</p>
            </div>
            <button wire:click="openCompetitorForm()"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity"
                style="background: var(--brand-500, #ef4444);">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Competitor
            </button>
        </div>

        {{-- Add / Edit Competitor modal --}}
        @if($competitorFormOpen)
        <div class="rounded-xl border border-brand-500/30 bg-white/[0.03] p-5 mb-4">
            <h4 class="text-sm font-semibold text-white mb-3">{{ $editingCompetitorId ? 'Edit Competitor' : 'Add Competitor' }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Name <span class="text-red-400">*</span></label>
                    <input wire:model="competitorName" type="text" placeholder="e.g. Sidr Honey"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Website</label>
                    <input wire:model="competitorWebsite" type="text" placeholder="e.g. sidrhoney.com"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Industry</label>
                    <input wire:model="competitorIndustry" type="text" placeholder="e.g. e-commerce / honey"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Notes</label>
                    <input wire:model="competitorNotes" type="text" placeholder="Optional"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button wire:click="saveCompetitor" class="px-4 py-1.5 rounded-lg text-xs font-medium text-white" style="background: var(--brand-500, #ef4444);">
                    {{ $editingCompetitorId ? 'Update' : 'Save' }}
                </button>
                <button wire:click="$set('competitorFormOpen', false)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">Cancel</button>
            </div>
        </div>
        @endif

        {{-- Competitor Visibility Entry modal --}}
        @if($competitorVisFormOpen)
        <div class="rounded-xl border border-white/15 bg-white/[0.03] p-5 mb-4">
            <h4 class="text-sm font-semibold text-white mb-3">Log Metrics</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Month <span class="text-red-400">*</span></label>
                    <input wire:model="competitorVisMonth" type="month"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">AI Impressions (GSC)</label>
                    <input wire:model.number="competitorVisAiImpressions" type="number" placeholder="e.g. 45000"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">AI Sessions (GA4)</label>
                    <input wire:model.number="competitorVisAiSessions" type="number" placeholder="e.g. 320"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Semrush AI Score</label>
                    <input wire:model.number="competitorVisSemrushScore" type="number" min="0" max="100" placeholder="0–100"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Semrush Topics #</label>
                    <input wire:model.number="competitorVisSemrushTopics" type="number" placeholder="e.g. 55"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">Copilot Citations</label>
                    <input wire:model.number="competitorVisClarityCitations" type="number" placeholder="e.g. 120"
                        class="w-full rounded-lg px-3 py-2 text-xs bg-white/5 border border-white/15 text-white focus:outline-none focus:border-brand-500/50">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button wire:click="saveCompetitorVisibility" class="px-4 py-1.5 rounded-lg text-xs font-medium text-white" style="background: var(--brand-500, #ef4444);">Save Metrics</button>
                <button wire:click="$set('competitorVisFormOpen', false)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">Cancel</button>
            </div>
        </div>
        @endif

        {{-- Competitor list / comparison --}}
        @if(empty($competitors))
            <div class="rounded-xl border border-white/10 bg-white/[0.02] flex flex-col items-center justify-center py-14 text-center px-6">
                <svg class="w-8 h-8 text-gray-700 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                <p class="text-xs text-gray-500">No competitors added yet. Click <strong class="text-gray-300">Add Competitor</strong> to start tracking.</p>
            </div>
        @else
        @php
            $latestClientMonth = collect($aiMonths ?? [])->last();
            $clientSnap = $aiSnapshot ?? [];
            $compMonth = collect($aiMonths ?? [])->max('month') ?? now()->format('Y-m');
            $allEntities = array_merge(
                [[
                    'name'           => $dashboardData['client']['name'] ?? 'Client',
                    'is_client'      => true,
                    'ai_impressions' => collect($aiMonths ?? [])->sum('ai_impressions'),
                    'ai_sessions'    => collect($aiMonths ?? [])->sum('ai_sessions'),
                    'semrush_score'  => $clientSnap['semrush_score'] ?? null,
                    'semrush_topics' => $clientSnap['semrush_topics_count'] ?? null,
                    'clarity'        => $clientSnap['clarity_citations'] ?? null,
                ]],
                collect($competitors)->map(fn($c) => [
                    'name'           => $c['name'],
                    'is_client'      => false,
                    'website'        => $c['website'] ?? null,
                    'ai_impressions' => collect($c['visibility'] ?? [])->sum('ai_impressions'),
                    'ai_sessions'    => collect($c['visibility'] ?? [])->sum('ai_sessions'),
                    'semrush_score'  => $c['latest_visibility']['semrush_score'] ?? null,
                    'semrush_topics' => $c['latest_visibility']['semrush_topics_count'] ?? null,
                    'clarity'        => $c['latest_visibility']['clarity_citations'] ?? null,
                    'comp_id'        => $c['id'],
                ])->toArray()
            );
        @endphp

        {{-- Semrush AI Score comparison --}}
        @php $entitiesWithScore = collect($allEntities)->filter(fn($e) => $e['semrush_score'] !== null)->values(); @endphp
        @if($entitiesWithScore->count() >= 1)
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5 mb-4">
            <h3 class="text-sm font-semibold text-white mb-4">Semrush AI Score Comparison</h3>
            <div class="space-y-3">
                @foreach($entitiesWithScore as $entity)
                    @php $pct = round(($entity['semrush_score'] ?? 0) / 100 * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="{{ $entity['is_client'] ? 'text-white font-medium' : 'text-gray-300' }}">
                                {{ $entity['name'] }}
                                @if($entity['is_client']) <span class="text-[10px] text-brand-400 ms-1">you</span>@endif
                            </span>
                            <span class="tabular-nums {{ ($entity['semrush_score'] ?? 0) >= 50 ? 'text-emerald-400' : (($entity['semrush_score'] ?? 0) >= 20 ? 'text-yellow-400' : 'text-gray-500') }}">
                                {{ $entity['semrush_score'] }} / 100
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                style="width:{{ $pct }}%;background:{{ $entity['is_client'] ? 'var(--brand-500,#ef4444)' : 'rgba(255,255,255,0.25)' }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Full competitor table --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-4 py-3 text-gray-500 font-medium">Competitor</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">AI Impressions</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">AI Sessions</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Semrush Score</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Topics</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Citations</th>
                        <th class="px-4 py-3 text-gray-500 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr class="bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <div class="font-medium text-white">{{ $dashboardData['client']['name'] ?? 'Client' }}</div>
                            <div class="text-[10px] text-brand-400 mt-0.5">you · total period</div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-200">{{ number_format(collect($aiMonths ?? [])->sum('ai_impressions')) ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-200">{{ number_format(collect($aiMonths ?? [])->sum('ai_sessions')) ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(($clientSnap['semrush_score'] ?? null) !== null)
                                <span class="tabular-nums {{ $clientSnap['semrush_score'] >= 50 ? 'text-emerald-400' : ($clientSnap['semrush_score'] >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">{{ $clientSnap['semrush_score'] }}</span>
                            @else <span class="text-gray-600">—</span> @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $clientSnap['semrush_topics_count'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $clientSnap['clarity_citations'] ? number_format($clientSnap['clarity_citations']) : '—' }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                    @foreach($competitors as $comp)
                    @php
                        $latestVis   = $comp['latest_visibility'] ?? null;
                        $totalCompI  = collect($comp['visibility'] ?? [])->sum('ai_impressions');
                        $totalCompS  = collect($comp['visibility'] ?? [])->sum('ai_sessions');
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-gray-200 font-medium">{{ $comp['name'] }}</div>
                            @if($comp['website'] ?? null)<div class="text-[10px] text-gray-600 mt-0.5">{{ $comp['website'] }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $totalCompI ? number_format($totalCompI) : '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $totalCompS ? number_format($totalCompS) : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(($latestVis['semrush_score'] ?? null) !== null)
                                <span class="tabular-nums {{ $latestVis['semrush_score'] >= 50 ? 'text-emerald-400' : ($latestVis['semrush_score'] >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">{{ $latestVis['semrush_score'] }}</span>
                            @else <span class="text-gray-600">—</span> @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $latestVis['semrush_topics_count'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ ($latestVis['clarity_citations'] ?? null) ? number_format($latestVis['clarity_citations']) : '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 justify-end">
                                <button wire:click="openCompetitorVisForm({{ $comp['id'] }}, '{{ $compMonth }}')" title="Log metrics"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-500 hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </button>
                                <button wire:click="openCompetitorForm({{ $comp['id'] }})" title="Edit"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-500 hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                </button>
                                <button wire:click="deleteCompetitor({{ $comp['id'] }})" wire:confirm="Remove {{ $comp['name'] }}?" title="Delete"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-600 hover:text-red-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Monthly AI Impressions — client vs competitors --}}
        @php
            $competitorsWithHistory = collect($competitors)->filter(fn($c) => count($c['visibility'] ?? []) > 0);
            $clientMonthLabels = collect($aiMonths ?? [])->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values()->toArray();
        @endphp
        @if(count($aiMonths ?? []) > 1 && $competitorsWithHistory->count() > 0)
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5 mt-4">
            <h3 class="text-sm font-semibold text-white mb-4">AI Impressions — Client vs Competitors</h3>
            <div x-data="{
                init() {
                    const labels = @js($clientMonthLabels);
                    const clientData = @js(collect($aiMonths ?? [])->pluck('ai_impressions')->map(fn($v) => (int)($v ?? 0))->values()->toArray());
                    const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';
                    const compColors = ['#60a5fa','#34d399','#fbbf24','#a78bfa','#fb923c','#f472b6'];
                    const datasets = [{
                        label: @js($dashboardData['tenant']['name'] ?? 'Client'),
                        data: clientData, borderColor: brandColor, backgroundColor: brandColor + '20',
                        borderWidth: 2.5, fill: false, tension: 0.3, pointRadius: 4, pointBackgroundColor: brandColor,
                    }];
                    @foreach($competitorsWithHistory as $ci => $comp)
                    @php $compMonthMap = collect($comp['visibility'])->keyBy('report_month'); @endphp
                    datasets.push({
                        label: @js($comp['name']),
                        data: @js(collect($clientMonthLabels)->map(function($label) use ($compMonthMap) {
                            $month = \Carbon\Carbon::createFromFormat('M Y', $label)->format('Y-m');
                            return (int)(($compMonthMap[$month]['ai_impressions'] ?? null) ?? 0);
                        })->toArray()),
                        borderColor: compColors[{{ $ci }} % compColors.length], borderWidth: 1.5,
                        fill: false, tension: 0.3, pointRadius: 3, borderDash: [4, 3],
                    });
                    @endforeach
                    new Chart(this.$refs.cv, { type: 'line', data: { labels, datasets },
                        options: { responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: true, labels: { color: '#9ca3af', font: { size: 11 }, boxWidth: 24 } } },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                            }
                        }
                    });
                }
            }"><div style="height:240px;"><canvas x-ref="cv"></canvas></div></div>
        </div>
        @endif

        @endif {{-- end if competitors not empty --}}
    </div>

</div>
@endif
