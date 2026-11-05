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
</div>
@endif
