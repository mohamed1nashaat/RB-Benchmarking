@php
    $data    = $widgetData->dataForReport($report);
    $kpis    = $data['kpis'];
    $trend   = $data['trend'];
    $splits  = $data['platform_split'];
    $camps   = $data['campaigns'];
    $bmarks  = $data['benchmarks'];

    $platformColors = [
        'facebook'  => '#3b82f6',
        'google'    => '#ef4444',
        'tiktok'    => '#a855f7',
        'snapchat'  => '#eab308',
        'linkedin'  => '#0ea5e9',
    ];
    $platformLabels = [
        'facebook'  => 'Meta',
        'google'    => 'Google',
        'tiktok'    => 'TikTok',
        'snapchat'  => 'Snapchat',
        'linkedin'  => 'LinkedIn',
    ];

    $weeklyTrend = (function(array $t): array {
        $buckets = []; $keys = ['spend','clicks','leads','impr'];
        foreach ($t['labels'] as $i => $lbl) {
            $week = \Carbon\Carbon::createFromFormat('d M', $lbl . ' ' . now()->year)->startOfWeek()->format('d M');
            if (!isset($buckets[$week])) { $buckets[$week] = array_fill_keys($keys, 0); }
            foreach ($keys as $k) { $buckets[$week][$k] += ($t[$k][$i] ?? 0); }
        }
        $out = ['labels'=>[], 'spend'=>[], 'clicks'=>[], 'leads'=>[], 'impr'=>[]];
        foreach ($buckets as $w => $vals) { $out['labels'][] = $w; foreach ($keys as $k) { $out[$k][] = $vals[$k]; } }
        return $out;
    })($trend);
@endphp

<div class="flex flex-col h-screen overflow-hidden" style="background:#0d0f16;">

    {{-- TOP BAR --}}
    <div class="flex items-center gap-4 px-6 h-14 border-b border-white/8 flex-shrink-0"
         style="background:#161b22;">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-base font-bold text-white truncate">{{ $report->name }}</p>
                @if($report->client_name)
                    <span class="text-sm text-gray-500">— {{ $report->client_name }}</span>
                @endif
                <span class="text-xs font-mono text-gray-600">{{ $report->periodLabel() }}</span>
            </div>
        </div>

        {{-- Right side actions --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            {{-- Print / Save as PDF --}}
            <button onclick="window.print()"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-white/8 text-gray-300 hover:bg-white/12 hover:text-white transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Export PDF
            </button>

            <div class="h-4 w-px bg-white/10"></div>

            {{-- Watermark --}}
            <span class="text-[11px] text-gray-600">Powered by</span>
            <span class="text-sm font-bold text-brand-400 tracking-tight">RB Benchmarks</span>
        </div>
    </div>

    {{-- CANVAS --}}
    <div class="flex-1 overflow-y-auto px-6 py-8">
        <div class="max-w-5xl mx-auto space-y-4">

            @forelse($layout['blocks'] as $block)
            @php $cfg = $block['config'] ?? []; @endphp

            <div class="rounded-xl border border-white/8" style="background:#161b22;">

                {{-- KPI ROW --}}
                @if($block['type'] === 'kpi_row')
                <div class="p-5">
                    @if(!empty($cfg['title']))
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">{{ $cfg['title'] }}</p>
                    @endif
                    <div class="grid gap-4" style="grid-template-columns: repeat({{ min(count($cfg['metrics'] ?? ['spend','impressions','ctr','cpl']), 4) }}, 1fr)">
                        @foreach($cfg['metrics'] ?? ['spend','impressions','ctr','cpl'] as $metric)
                        @php
                            $lbl  = $kpiLabels[$metric] ?? ['label'=>strtoupper($metric),'format'=>'number'];
                            $val  = $kpis[$metric] ?? 0;
                            $fmt  = $lbl['format'] ?? 'number';
                            $disp = $fmt === 'currency' ? 'SAR '.number_format($val,0) : ($fmt === 'percent' ? $val.'%' : number_format($val,0));
                            $bm   = $bmarks[$metric] ?? null;
                            $p50  = $bm ? (float)$bm['p50'] : null;
                            $costM = in_array($metric, ['cpc','cpm','cpl','cpa']);
                            $aboveBm = null;
                            if ($p50 !== null && $p50 > 0) {
                                $aboveBm = $costM ? ($val <= $p50) : ($val >= $p50);
                            }
                        @endphp
                        <div class="rounded-lg bg-white/4 border border-white/8 px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $lbl['label'] }}</p>
                                @if($aboveBm !== null)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold {{ $aboveBm ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                                        {{ $aboveBm ? '▲ Good' : '▼ Low' }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-2xl font-bold font-mono text-white">{{ $disp }}</p>
                            @if($p50 !== null)
                                <p class="text-[10px] text-gray-600 mt-1">Benchmark P50: {{ $fmt === 'currency' ? 'SAR '.number_format($p50,0) : ($fmt === 'percent' ? $p50.'%' : number_format($p50,0)) }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- TREND LINE CHART --}}
                @elseif($block['type'] === 'chart_line')
                @php
                    $chartMetric  = $cfg['metric'] ?? 'spend';
                    $chartTitle   = $cfg['title'] ?? 'Trend';
                    $chartGrain   = $cfg['grain'] ?? 'day';
                    $trendSrc     = $chartGrain === 'week' ? $weeklyTrend : $trend;
                    $chartData    = json_encode($trendSrc[$chartMetric] ?? []);
                    $chartLabels  = json_encode($trendSrc['labels'] ?? []);
                    $chartId      = 'chart_' . $block['id'];
                @endphp
                <div class="p-5">
                    <p class="text-sm font-semibold text-gray-100 mb-4">{{ $chartTitle }}</p>
                    <div class="h-48" wire:ignore>
                        <canvas id="{{ $chartId }}"
                            x-data x-init="
                                const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: {{ $chartLabels }},
                                        datasets: [{
                                            data: {{ $chartData }},
                                            borderColor: '#ef4444',
                                            backgroundColor: 'rgba(239,68,68,0.08)',
                                            borderWidth: 2,
                                            pointRadius: 3,
                                            pointBackgroundColor: '#ef4444',
                                            fill: true,
                                            tension: 0.4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }
                                        }
                                    }
                                });
                            ">
                        </canvas>
                    </div>
                </div>

                {{-- BAR CHART --}}
                @elseif($block['type'] === 'chart_bar')
                @php
                    $barMetric  = $cfg['metric'] ?? 'spend';
                    $barTitle   = $cfg['title'] ?? 'Platform Comparison';
                    $barLabels  = json_encode(collect($splits)->pluck('platform')->map(fn($p) => $platformLabels[$p] ?? $p)->values()->all());
                    $barValues  = json_encode(collect($splits)->pluck($barMetric)->values()->all());
                    $barColors  = json_encode(collect($splits)->pluck('platform')->map(fn($p) => $platformColors[$p] ?? '#6b7280')->values()->all());
                    $barId      = 'chart_' . $block['id'];
                @endphp
                <div class="p-5">
                    <p class="text-sm font-semibold text-gray-100 mb-4">{{ $barTitle }}</p>
                    <div class="h-48" wire:ignore>
                        <canvas id="{{ $barId }}"
                            x-data x-init="
                                const ctx = document.getElementById('{{ $barId }}').getContext('2d');
                                new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: {{ $barLabels }},
                                        datasets: [{ data: {{ $barValues }}, backgroundColor: {{ $barColors }}, borderRadius: 6, borderSkipped: false }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } }
                                        }
                                    }
                                });
                            ">
                        </canvas>
                    </div>
                </div>

                {{-- BENCHMARK TABLE --}}
                @elseif($block['type'] === 'benchmark_table')
                @php
                    $bmMetrics = $cfg['metrics'] ?? ['ctr','cpc','cpm','cpl'];
                    $bmTitle   = $cfg['title'] ?? 'Benchmark Comparison';
                @endphp
                <div class="p-5">
                    <p class="text-sm font-semibold text-gray-100 mb-4">{{ $bmTitle }}</p>
                    @if(empty($bmarks))
                        <p class="text-xs text-gray-500 py-4 text-center">No benchmark data available for this report.</p>
                    @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/8">
                                <th class="text-start pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Metric</th>
                                <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Your Result</th>
                                <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">P25</th>
                                <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-yellow-500/70">P50 Avg</th>
                                <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-green-500/70">P75 Best</th>
                                <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">vs Median</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($bmMetrics as $m)
                            @if(isset($bmarks[$m]))
                            @php
                                $lbl2  = $kpiLabels[$m] ?? ['label'=>strtoupper($m),'format'=>'number'];
                                $val2  = $kpis[$m] ?? 0;
                                $bm2   = $bmarks[$m];
                                $p502  = (float) $bm2['p50'];
                                $costM = in_array($m, ['cpc','cpm','cpl','cpa']);
                                $beats = $costM ? ($val2 > 0 && $val2 <= $p502) : ($val2 >= $p502);
                                $fmt2  = $lbl2['format'] ?? 'number';
                                $fmtV  = fn($v) => match($fmt2) {
                                    'currency' => 'SAR '.number_format($v,0),
                                    'percent'  => number_format($v,2).'%',
                                    default    => number_format($v,0)
                                };
                                $diff     = $p502 > 0 ? round((($val2 - $p502) / $p502) * 100) : 0;
                                $diffDisp = ($diff > 0 ? '+' : '') . $diff . '%';
                            @endphp
                            <tr>
                                <td class="py-2.5 font-medium text-gray-200">{{ $lbl2['label'] }}</td>
                                <td class="py-2.5 text-center font-mono text-white font-semibold">{{ $fmtV($val2) }}</td>
                                <td class="py-2.5 text-center font-mono text-gray-400 text-xs">{{ $fmtV($bm2['p25']) }}</td>
                                <td class="py-2.5 text-center font-mono text-yellow-400 text-xs font-semibold">{{ $fmtV($bm2['p50']) }}</td>
                                <td class="py-2.5 text-center font-mono text-green-400 text-xs">{{ $fmtV($bm2['p75']) }}</td>
                                <td class="py-2.5 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $beats ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                                        {{ $diffDisp }}
                                    </span>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

                {{-- CAMPAIGN TABLE --}}
                @elseif($block['type'] === 'campaign_table')
                @php
                    $campRows  = array_slice($camps, 0, $cfg['rows'] ?? 10);
                    $campTitle = $cfg['title'] ?? 'Top Campaigns';
                @endphp
                <div class="p-5">
                    <p class="text-sm font-semibold text-gray-100 mb-4">{{ $campTitle }}</p>
                    @if(empty($campRows))
                        <p class="text-xs text-gray-500 py-4 text-center">No campaign data for this period.</p>
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/8">
                                    <th class="text-start pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Campaign</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Spend</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Impressions</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Clicks</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">CTR</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Leads</th>
                                    <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">CPL</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach($campRows as $camp)
                                <tr>
                                    <td class="py-2.5 font-medium text-gray-200 max-w-xs truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full flex-shrink-0" style="background:{{ $platformColors[$camp['platform']] ?? '#6b7280' }}"></span>
                                            {{ $camp['name'] }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 text-end font-mono text-gray-200">{{ number_format($camp['spend'],0) }}</td>
                                    <td class="py-2.5 text-end font-mono text-gray-400 text-xs">{{ number_format($camp['impressions'],0) }}</td>
                                    <td class="py-2.5 text-end font-mono text-gray-400 text-xs">{{ number_format($camp['clicks'],0) }}</td>
                                    <td class="py-2.5 text-end font-mono text-gray-300">{{ $camp['ctr'] }}%</td>
                                    <td class="py-2.5 text-end font-mono text-gray-300">{{ number_format($camp['leads'],0) }}</td>
                                    <td class="py-2.5 text-end font-mono text-gray-300">{{ $camp['cpl'] > 0 ? number_format($camp['cpl'],0) : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- PLATFORM SPLIT --}}
                @elseif($block['type'] === 'platform_split')
                @php
                    $splitMetric = $cfg['metric'] ?? 'spend';
                    $splitTitle  = $cfg['title'] ?? 'Budget by Platform';
                    $maxSplit    = collect($splits)->max($splitMetric) ?: 1;
                @endphp
                <div class="p-5">
                    <p class="text-sm font-semibold text-gray-100 mb-4">{{ $splitTitle }}</p>
                    <div class="space-y-3">
                        @foreach($splits as $sp)
                        @php $spVal = $sp[$splitMetric] ?? 0; $spPct = $maxSplit > 0 ? round(($spVal/$maxSplit)*100) : 0; @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-24 text-sm text-gray-300 flex-shrink-0">{{ $platformLabels[$sp['platform']] ?? $sp['platform'] }}</div>
                            <div class="flex-1 h-6 bg-white/5 rounded-md overflow-hidden">
                                <div class="h-full rounded-md" style="width:{{ $spPct }}%; background:{{ $platformColors[$sp['platform']] ?? '#6b7280' }}20; border-right: 2px solid {{ $platformColors[$sp['platform']] ?? '#6b7280' }}"></div>
                            </div>
                            <div class="w-28 text-right font-mono text-sm text-white flex-shrink-0">{{ $splitMetric === 'spend' ? 'SAR '.number_format($spVal,0) : number_format($spVal,0) }}</div>
                            <div class="w-10 text-right text-xs text-gray-500">{{ $sp['pct'] }}%</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- AI SUMMARY --}}
                @elseif($block['type'] === 'ai_summary')
                @php
                    $aiTitle = $cfg['title'] ?? 'Executive Summary';
                    $aiText  = $report->executive_summary;
                @endphp
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-6 w-6 rounded-lg bg-brand-500/15 flex items-center justify-center flex-shrink-0">
                            <svg class="h-3.5 w-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-100">{{ $aiTitle }}</p>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-brand-500/10 text-brand-400 font-medium">AI</span>
                    </div>
                    @if($aiText)
                        <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-line">{{ $aiText }}</p>
                    @else
                        <p class="text-xs text-gray-500 italic">AI analysis not yet generated for this report.</p>
                    @endif
                </div>

                {{-- TEXT HEADING --}}
                @elseif($block['type'] === 'text_heading')
                <div class="px-5 py-4">
                    @php $level = $cfg['level'] ?? 'h2'; @endphp
                    @if($level === 'h1')
                        <h1 class="text-2xl font-bold text-white">{{ $cfg['content'] ?? '' }}</h1>
                    @elseif($level === 'h3')
                        <h3 class="text-base font-semibold text-gray-200">{{ $cfg['content'] ?? '' }}</h3>
                    @else
                        <h2 class="text-lg font-bold text-white">{{ $cfg['content'] ?? '' }}</h2>
                    @endif
                </div>

                {{-- DIVIDER --}}
                @elseif($block['type'] === 'divider')
                <div class="py-3 px-5">
                    <div class="border-t border-white/8"></div>
                </div>
                @endif

            </div>
            @empty
            <div class="flex flex-col items-center justify-center h-64 text-center">
                <p class="text-sm text-gray-500">This report has no content blocks.</p>
            </div>
            @endforelse

            {{-- Powered by footer --}}
            <div class="pt-8 pb-4 text-center">
                <p class="text-xs text-gray-700">Generated with <span class="text-brand-500/60 font-semibold">RB Benchmarks</span> · {{ now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
