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
                    <input
                        type="file"
                        wire:model="aiVisibilityFile"
                        accept=".xlsx"
                        class="hidden"
                        id="ai-vis-file-input"
                    >
                    <label for="ai-vis-file-input"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-white/15 bg-white/5 text-xs text-gray-300 cursor-pointer hover:border-white/30 transition-colors">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                        </svg>
                        <span>{{ $aiVisibilityFile ? $aiVisibilityFile->getClientOriginalName() : 'Choose .xlsx file' }}</span>
                    </label>
                </label>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity"
                    style="background: var(--brand-500, #ef4444);"
                    :class="{ 'opacity-50 cursor-not-allowed': !$wire.aiVisibilityFile }"
                >
                    <svg wire:loading wire:target="uploadAiVisibility" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="uploadAiVisibility">Import</span>
                    <span wire:loading wire:target="uploadAiVisibility">Importing…</span>
                </button>
            </form>
        </div>

        @if($aiUploadSuccess)
            <div class="mt-3 flex items-center gap-2 text-xs text-emerald-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ $aiUploadSuccess }}
            </div>
        @endif
        @if($aiUploadError)
            <div class="mt-3 flex items-center gap-2 text-xs text-red-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                {{ $aiUploadError }}
            </div>
        @endif
    </div>

    @if(empty($aiMonths) && !$aiSnapshot)
        {{-- ── Empty state ───────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.02] flex flex-col items-center justify-center py-20 text-center px-6">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style="background: rgba(239,68,68,0.12);">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#ef4444">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-white">No AI visibility data yet</h3>
            <p class="text-xs text-gray-500 mt-1 max-w-sm">Upload the monthly Excel report above to start tracking this client's AI citation presence across Google AI Overviews, ChatGPT, Gemini, Perplexity, and more.</p>
        </div>
    @else

    {{-- ── KPI Row ───────────────────────────────────────────────────────── --}}
    @php
        $latestMonth = collect($aiMonths)->last();
        $prevMonth   = collect($aiMonths)->slice(-2, 1)->first();
        $totalAiImpressions = collect($aiMonths)->sum('ai_impressions');
        $totalAiSessions    = collect($aiMonths)->sum('ai_sessions');
        $semrushScore       = $aiSnapshot['semrush_score'] ?? null;
        $clarityCitations   = $aiSnapshot['clarity_citations'] ?? null;

        function aiDelta($cur, $prev): ?float {
            if (!$cur || !$prev || $prev == 0) return null;
            return round(($cur - $prev) / $prev * 100, 1);
        }
        $impressionsDelta = aiDelta($latestMonth['ai_impressions'] ?? null, $prevMonth['ai_impressions'] ?? null);
        $sessionsDelta    = aiDelta($latestMonth['ai_sessions'] ?? null, $prevMonth['ai_sessions'] ?? null);
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Google AI Impressions --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">AI Impressions (GSC)</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">
                {{ $totalAiImpressions ? number_format($totalAiImpressions) : '—' }}
            </p>
            <p class="text-[11px] text-gray-600 mt-0.5">Google AI Overviews · total</p>
            @if($latestMonth && $impressionsDelta !== null)
                <p class="text-[11px] mt-1 {{ $impressionsDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $impressionsDelta >= 0 ? '▲' : '▼' }} {{ abs($impressionsDelta) }}% vs prev month
                </p>
            @endif
        </div>

        {{-- AI Sessions --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">AI Sessions (GA4)</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">
                {{ $totalAiSessions ? number_format($totalAiSessions) : '—' }}
            </p>
            <p class="text-[11px] text-gray-600 mt-0.5">ChatGPT, Gemini, Perplexity…</p>
            @if($latestMonth && $sessionsDelta !== null)
                <p class="text-[11px] mt-1 {{ $sessionsDelta >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $sessionsDelta >= 0 ? '▲' : '▼' }} {{ abs($sessionsDelta) }}% vs prev month
                </p>
            @endif
        </div>

        {{-- Semrush AI Score --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">Semrush AI Score</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">
                {{ $semrushScore !== null ? $semrushScore . ' / 100' : '—' }}
            </p>
            <p class="text-[11px] text-gray-600 mt-0.5">
                @if($aiSnapshot['semrush_topics_count'] ?? null)
                    {{ $aiSnapshot['semrush_topics_count'] }} topics · {{ $aiSnapshot['semrush_prompts_count'] ?? '?' }} prompts
                @else
                    AI brand visibility score
                @endif
            </p>
        </div>

        {{-- Clarity Citations --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <p class="text-[11px] text-gray-500 uppercase tracking-wide font-medium">Copilot Citations</p>
            <p class="text-2xl font-bold text-white mt-1 tabular-nums">
                {{ $clarityCitations !== null ? number_format($clarityCitations) : '—' }}
            </p>
            <p class="text-[11px] text-gray-600 mt-0.5">Microsoft Clarity · last 30d</p>
        </div>
    </div>

    {{-- ── Google AI Impressions Trend ──────────────────────────────────── --}}
    @if(count($aiMonths) > 1)
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <h3 class="text-sm font-semibold text-white mb-4">Google AI Overviews — Impressions by Month</h3>
        <div x-data="{
            init() {
                const months = @js(collect($aiMonths)->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values());
                const values = @js(collect($aiMonths)->pluck('ai_impressions')->map(fn($v) => (int)($v ?? 0))->values());
                const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';

                new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'AI Impressions',
                            data: values,
                            borderColor: brandColor,
                            backgroundColor: brandColor + '20',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: brandColor,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                        }
                    }
                });
            }
        }">
            <div style="height: 220px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- ── AI Sessions by Platform + Funnel ────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- AI Sessions Trend --}}
        @if(count($aiMonths) > 1)
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-4">AI Assistant Sessions by Month</h3>
            <div x-data="{
                init() {
                    const months = @js(collect($aiMonths)->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values());
                    const sessions = @js(collect($aiMonths)->pluck('ai_sessions')->map(fn($v) => (int)($v ?? 0))->values());
                    const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';

                    new Chart(this.$refs.canvas, {
                        type: 'bar',
                        data: {
                            labels: months,
                            datasets: [{
                                label: 'AI Sessions',
                                data: sessions,
                                backgroundColor: brandColor + '55',
                                borderColor: brandColor,
                                borderWidth: 1.5,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                            }
                        }
                    });
                }
            }">
                <div style="height: 200px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- AI Platform Breakdown --}}
        @if(!empty($aiSnapshot['ai_platforms']))
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-4">Sessions by AI Platform</h3>
            @php
                $platforms = collect($aiSnapshot['ai_platforms'])->take(6);
                $totalPlatformSessions = $platforms->sum('total_sessions') ?: 1;
                $platformColors = ['#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7'];
            @endphp
            <div class="space-y-2.5">
                @foreach($platforms as $i => $p)
                    @php $pct = round(($p['total_sessions'] ?? 0) / $totalPlatformSessions * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-300">{{ $p['platform'] }}</span>
                            <span class="text-gray-500 tabular-nums">{{ number_format($p['total_sessions'] ?? 0) }} sessions</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $platformColors[$i % count($platformColors)] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── AI Shopping Funnel ───────────────────────────────────────────── --}}
    @if(!empty($aiSnapshot['ai_funnel']))
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <h3 class="text-sm font-semibold text-white mb-4">AI Assistant — Shopping Funnel</h3>
        @php
            $funnel = collect($aiSnapshot['ai_funnel'])->filter(fn($f) => ($f['total'] ?? 0) > 0);
            $topFunnelValue = $funnel->max('total') ?: 1;
        @endphp
        <div class="space-y-2">
            @foreach($funnel as $step)
                @php $pct = round(($step['total'] ?? 0) / $topFunnelValue * 100); @endphp
                <div class="flex items-center gap-3">
                    <div class="w-32 text-xs text-gray-400 text-right flex-shrink-0">{{ $step['event'] }}</div>
                    <div class="flex-1 h-6 rounded bg-white/5 overflow-hidden relative">
                        <div class="h-full rounded transition-all" style="width: {{ $pct }}%; background: rgba(239,68,68,0.5);"></div>
                        <span class="absolute inset-0 flex items-center ps-2 text-[11px] text-white/80 tabular-nums">{{ number_format($step['total'] ?? 0) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Top AI Pages + Countries ─────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Top AI Pages --}}
        @if(!empty($aiSnapshot['top_ai_pages']))
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-3">Top Pages in Google AI Features</h3>
            <div class="space-y-2">
                @php $maxPageImpressions = collect($aiSnapshot['top_ai_pages'])->max('impressions') ?: 1; @endphp
                @foreach(collect($aiSnapshot['top_ai_pages'])->take(8) as $page)
                    @php
                        $url = $page['url'] ?? '';
                        $shortUrl = preg_replace('#^https?://[^/]+#', '', $url);
                        $shortUrl = strlen($shortUrl) > 50 ? substr($shortUrl, 0, 50) . '…' : $shortUrl;
                        $pct = round(($page['impressions'] ?? 0) / $maxPageImpressions * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="text-gray-400 truncate max-w-[70%]" title="{{ $url }}">{{ $shortUrl ?: $url }}</span>
                            <span class="text-gray-500 tabular-nums flex-shrink-0 ms-2">{{ number_format($page['impressions'] ?? 0) }}</span>
                        </div>
                        <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-500/60" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Top AI Countries --}}
        @if(!empty($aiSnapshot['top_ai_countries']))
        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <h3 class="text-sm font-semibold text-white mb-3">AI Impressions by Country</h3>
            <div class="space-y-2">
                @php $maxCountryImpressions = collect($aiSnapshot['top_ai_countries'])->max('impressions') ?: 1; @endphp
                @foreach(collect($aiSnapshot['top_ai_countries'])->take(8) as $country)
                    @php $pct = round(($country['impressions'] ?? 0) / $maxCountryImpressions * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-[11px] mb-0.5">
                            <span class="text-gray-400">{{ $country['country'] ?? '—' }}</span>
                            <span class="text-gray-500 tabular-nums">{{ number_format($country['impressions'] ?? 0) }}</span>
                        </div>
                        <div class="h-1 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-500/60" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── Semrush Topics ───────────────────────────────────────────────── --}}
    @if(!empty($aiSnapshot['semrush_top_topics']))
    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
        <h3 class="text-sm font-semibold text-white mb-3">
            Semrush AI Topics
            @if($aiSnapshot['semrush_topics_count'] ?? null)
                <span class="text-xs font-normal text-gray-500 ms-2">{{ $aiSnapshot['semrush_topics_count'] }} topics tracked</span>
            @endif
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="pb-2 text-gray-500 font-medium">Topic</th>
                        <th class="pb-2 text-gray-500 font-medium">Market</th>
                        <th class="pb-2 text-gray-500 font-medium text-right">Visibility</th>
                        <th class="pb-2 text-gray-500 font-medium text-right">Mentions</th>
                        <th class="pb-2 text-gray-500 font-medium text-right">Volume</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach(collect($aiSnapshot['semrush_top_topics'])->take(10) as $topic)
                        <tr>
                            <td class="py-2 text-gray-200">{{ $topic['topic'] }}</td>
                            <td class="py-2 text-gray-500">{{ $topic['market'] ?? '—' }}</td>
                            <td class="py-2 text-right">
                                @php $vis = $topic['visibility'] ?? 0; @endphp
                                <span class="tabular-nums {{ $vis >= 50 ? 'text-emerald-400' : ($vis >= 20 ? 'text-yellow-400' : 'text-gray-500') }}">{{ $vis }}</span>
                            </td>
                            <td class="py-2 text-gray-400 tabular-nums text-right">{{ $topic['mentions'] ?? '—' }}</td>
                            <td class="py-2 text-gray-500 tabular-nums text-right">{{ number_format($topic['volume'] ?? 0) }}</td>
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
            <button
                wire:click="openCompetitorForm()"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-opacity"
                style="background: var(--brand-500, #ef4444);"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Competitor
            </button>
        </div>

        {{-- Add / Edit Competitor modal --}}
        @if($competitorFormOpen)
        <div class="rounded-xl border border-brand-500/30 bg-white/[0.03] p-5 mb-4" x-data>
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
                <button wire:click="saveCompetitor"
                    class="px-4 py-1.5 rounded-lg text-xs font-medium text-white"
                    style="background: var(--brand-500, #ef4444);">
                    {{ $editingCompetitorId ? 'Update' : 'Save' }}
                </button>
                <button wire:click="$set('competitorFormOpen', false)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">
                    Cancel
                </button>
            </div>
        </div>
        @endif

        {{-- Competitor Visibility Entry modal --}}
        @if($competitorVisFormOpen)
        <div class="rounded-xl border border-white/15 bg-white/[0.03] p-5 mb-4">
            <h4 class="text-sm font-semibold text-white mb-3">Log Metrics</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
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
                <button wire:click="saveCompetitorVisibility"
                    class="px-4 py-1.5 rounded-lg text-xs font-medium text-white"
                    style="background: var(--brand-500, #ef4444);">
                    Save Metrics
                </button>
                <button wire:click="$set('competitorVisFormOpen', false)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-white transition-colors">
                    Cancel
                </button>
            </div>
        </div>
        @endif

        {{-- Competitor list / comparison ──────────────────────────────────── --}}
        @if(empty($competitors))
            <div class="rounded-xl border border-white/10 bg-white/[0.02] flex flex-col items-center justify-center py-14 text-center px-6">
                <svg class="w-8 h-8 text-gray-700 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <p class="text-xs text-gray-500">No competitors added yet. Click <strong class="text-gray-300">Add Competitor</strong> to start tracking.</p>
            </div>
        @else

        {{-- Comparison table ─────────────────────────────────────────────── --}}
        @php
            $latestClientMonth = collect($aiMonths ?? [])->last();
            $clientSnap = $aiSnapshot ?? [];
            $compMonth = collect($aiMonths ?? [])->max('month') ?? now()->format('Y-m');
        @endphp

        {{-- Summary comparison cards ──────────────────────────────────────── --}}
        @php
            $allEntities = array_merge(
                [[
                    'name'           => $dashboardData['tenant']['name'] ?? 'Client',
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
            $maxImpressions = max(1, collect($allEntities)->max('ai_impressions'));
            $maxSessions    = max(1, collect($allEntities)->max('ai_sessions'));
        @endphp

        {{-- Comparison bar chart for Semrush AI Score ─────────────────────── --}}
        @php
            $entitiesWithScore = collect($allEntities)->filter(fn($e) => $e['semrush_score'] !== null)->values();
        @endphp
        @if($entitiesWithScore->count() > 1)
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
                            <span class="tabular-nums {{ $entity['semrush_score'] >= 50 ? 'text-emerald-400' : ($entity['semrush_score'] >= 20 ? 'text-yellow-400' : 'text-gray-500') }}">
                                {{ $entity['semrush_score'] }} / 100
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                style="width: {{ $pct }}%; background: {{ $entity['is_client'] ? 'var(--brand-500,#ef4444)' : 'rgba(255,255,255,0.25)' }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Full competitor table ─────────────────────────────────────────── --}}
        <div class="rounded-xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-4 py-3 text-gray-500 font-medium">Competitor</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">AI Impressions</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">AI Sessions</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Semrush Score</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Topics</th>
                        <th class="px-4 py-3 text-gray-500 font-medium text-right">Copilot Citations</th>
                        <th class="px-4 py-3 text-gray-500 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    {{-- Client row --}}
                    <tr class="bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <div class="font-medium text-white">{{ $dashboardData['tenant']['name'] ?? 'Client' }}</div>
                            <div class="text-[10px] text-brand-400 mt-0.5">you · total period</div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-200">{{ number_format(collect($aiMonths ?? [])->sum('ai_impressions')) ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-200">{{ number_format(collect($aiMonths ?? [])->sum('ai_sessions')) ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(($clientSnap['semrush_score'] ?? null) !== null)
                                <span class="tabular-nums {{ $clientSnap['semrush_score'] >= 50 ? 'text-emerald-400' : ($clientSnap['semrush_score'] >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">
                                    {{ $clientSnap['semrush_score'] }}
                                </span>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $clientSnap['semrush_topics_count'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $clientSnap['clarity_citations'] ? number_format($clientSnap['clarity_citations']) : '—' }}</td>
                        <td class="px-4 py-3"></td>
                    </tr>

                    {{-- Competitor rows --}}
                    @foreach($competitors as $comp)
                    @php
                        $latestVis = $comp['latest_visibility'] ?? null;
                        $totalCompImpr = collect($comp['visibility'] ?? [])->sum('ai_impressions');
                        $totalCompSess = collect($comp['visibility'] ?? [])->sum('ai_sessions');
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-gray-200 font-medium">{{ $comp['name'] }}</div>
                            @if($comp['website'] ?? null)
                                <div class="text-[10px] text-gray-600 mt-0.5">{{ $comp['website'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $totalCompImpr ? number_format($totalCompImpr) : '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $totalCompSess ? number_format($totalCompSess) : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(($latestVis['semrush_score'] ?? null) !== null)
                                <span class="tabular-nums {{ $latestVis['semrush_score'] >= 50 ? 'text-emerald-400' : ($latestVis['semrush_score'] >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">
                                    {{ $latestVis['semrush_score'] }}
                                </span>
                            @else
                                <span class="text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ $latestVis['semrush_topics_count'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-400">{{ ($latestVis['clarity_citations'] ?? null) ? number_format($latestVis['clarity_citations']) : '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 justify-end">
                                <button wire:click="openCompetitorVisForm({{ $comp['id'] }}, '{{ $compMonth }}')"
                                    title="Log metrics"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-500 hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                                <button wire:click="openCompetitorForm({{ $comp['id'] }})"
                                    title="Edit competitor"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-500 hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="deleteCompetitor({{ $comp['id'] }})"
                                    wire:confirm="Remove {{ $comp['name'] }}?"
                                    title="Delete"
                                    class="h-6 w-6 rounded flex items-center justify-center text-gray-600 hover:text-red-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Monthly AI Impressions trend — client vs competitors ───────────── --}}
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
                        data: clientData,
                        borderColor: brandColor,
                        backgroundColor: brandColor + '20',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: brandColor,
                    }];

                    @foreach($competitorsWithHistory as $ci => $comp)
                    @php
                        $compMonthMap = collect($comp['visibility'])->keyBy('report_month');
                    @endphp
                    datasets.push({
                        label: @js($comp['name']),
                        data: @js(collect($clientMonthLabels)->map(function($label) use ($compMonthMap) {
                            $month = \Carbon\Carbon::createFromFormat('M Y', $label)->format('Y-m');
                            return (int)(($compMonthMap[$month]['ai_impressions'] ?? null) ?? 0);
                        })->toArray()),
                        borderColor: compColors[{{ $ci }} % compColors.length],
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 3,
                        borderDash: [4, 3],
                    });
                    @endforeach

                    new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: { labels, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: { color: '#9ca3af', font: { size: 11 }, boxWidth: 24 }
                                }
                            },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                            }
                        }
                    });
                }
            }">
                <div style="height: 240px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
        @endif

        @endif {{-- end if competitors not empty --}}
    </div>

</div>
@endif
