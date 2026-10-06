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
@endphp

<div class="flex h-screen overflow-hidden" style="background:#0d0f16;" x-data="reportDesigner()">

    {{-- ═══ TOP BAR ═══════════════════════════════════════════════════════════ --}}
    <div class="fixed top-0 left-0 right-0 z-50 flex items-center gap-3 px-4 h-12 border-b border-white/8"
         style="background:#161b22;">
        {{-- Back --}}
        <a href="{{ route('reports') }}" class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-white transition-colors me-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Reports
        </a>
        <div class="h-4 w-px bg-white/10"></div>

        {{-- Report name --}}
        <span class="text-sm font-semibold text-white truncate max-w-[220px]">{{ $report->name }}</span>
        <span class="text-[11px] text-gray-500 font-mono">{{ $report->periodLabel() }}</span>
        @if($report->client_name)
            <span class="text-[11px] text-gray-500">· {{ $report->client_name }}</span>
        @endif

        <div class="flex-1"></div>

        {{-- PDF export --}}
        <a href="{{ route('report-pdf', $report->id) }}" target="_blank"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-white/8 text-gray-300 hover:bg-white/12 hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            PDF
        </a>

        {{-- Generate AI --}}
        @if($report->ad_account_id)
        <button wire:click="generateAi"
                wire:loading.attr="disabled"
                wire:target="generateAi"
                x-data
                x-on:ai-generating.window="$el.querySelector('span').textContent = 'Queued ✓'; setTimeout(() => $el.querySelector('span').textContent = 'Generate AI', 3000)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-white/8 text-gray-300 hover:bg-white/12 hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
            </svg>
            <span>Generate AI</span>
        </button>
        @endif

        {{-- Preview toggle --}}
        <button wire:click="togglePreview"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-colors {{ $preview ? 'bg-brand-600 text-white' : 'bg-white/8 text-gray-300 hover:bg-white/12 hover:text-white' }}">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $preview ? 'M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88' : 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' }}"/>
            </svg>
            {{ $preview ? 'Edit' : 'Preview' }}
        </button>

        {{-- Share --}}
        <button
            wire:click="generateShareLink"
            x-data
            x-on:share-link-copied.window="navigator.clipboard.writeText($event.detail.url).then(() => { $el.querySelector('span').textContent = 'Copied!'; setTimeout(() => $el.querySelector('span').textContent = 'Share', 2000) })"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-white/8 text-gray-300 hover:bg-white/12 hover:text-white transition-colors">
            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>
            </svg>
            <span>Share</span>
        </button>

        {{-- Save indicator --}}
        <div wire:loading wire:target="save,addBlock,removeBlock,reorderBlocks"
             class="flex items-center gap-1 text-[11px] text-gray-500">
            <span class="h-1 w-1 rounded-full bg-amber-400 animate-pulse"></span> Saving…
        </div>
        <div x-data="{show:false}" x-on:block-saved.window="show=true; clearTimeout($data._t); $data._t=setTimeout(()=>show=false,2000)" x-show="show"
             class="text-[11px] text-green-400 flex items-center gap-1">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            Saved
        </div>
    </div>

    {{-- ═══ LAYOUT (below top bar) ════════════════════════════════════════════ --}}
    <div class="flex w-full pt-12 h-full overflow-hidden">

        {{-- LEFT SIDEBAR — widget palette (hidden in preview) --}}
        @unless($preview)
        <div class="w-56 flex-shrink-0 overflow-y-auto border-r border-white/8 py-4"
             style="background:#161b22;">
            <p class="px-3 text-[10px] font-semibold uppercase tracking-widest text-gray-600 mb-3">Add Block</p>

            @foreach($widgetTypes as $group => $types)
            <div class="mb-4">
                <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-600 mb-1">{{ ucfirst($group) }}</p>
                @foreach($types as $wt)
                <button wire:click="addBlock('{{ $wt['type'] }}')"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-gray-300 hover:bg-white/5 hover:text-white transition-colors rounded-md mx-1 text-start">
                    <svg class="h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $wt['icon'] }}"/>
                    </svg>
                    {{ $wt['label'] }}
                </button>
                @endforeach
            </div>
            @endforeach
            <div class="mt-4 px-3 pb-2">
                <p class="text-[10px] text-gray-700 leading-relaxed">Drag blocks to reorder. Click Edit to configure. Changes auto-save.</p>
            </div>
        </div>
        @endunless

        {{-- CENTER CANVAS --}}
        <div class="flex-1 overflow-y-auto px-6 py-6"
             id="report-canvas"
             x-ref="canvas"
             wire:ignore.self>

            @if(empty($layout['blocks']))
            <div class="flex flex-col items-center justify-center h-64 text-center">
                <div class="h-16 w-16 rounded-2xl bg-white/5 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-400">Click a block in the left panel to add it</p>
                <p class="text-xs text-gray-600 mt-1">Your report canvas is empty</p>
            </div>
            @endif

            <div id="blocks-sortable" class="space-y-4">
                @foreach($layout['blocks'] as $block)
                @php
                    $isSelected = $selectedBlockId === $block['id'];
                    $cfg = $block['config'] ?? [];
                @endphp

                <div
                    data-block-id="{{ $block['id'] }}"
                    class="relative group rounded-xl border transition-all {{ $preview ? 'border-transparent' : ($isSelected ? 'border-brand-500/60 ring-2 ring-brand-500/20' : 'border-white/8 hover:border-white/15') }}"
                    style="background:#161b22;"
                >
                    {{-- Block toolbar (edit mode only) --}}
                    @unless($preview)
                    <div class="absolute -top-2.5 right-3 z-10 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity {{ $isSelected ? 'opacity-100' : '' }}">
                        {{-- Drag handle --}}
                        <div class="drag-handle cursor-grab flex items-center px-2 py-0.5 rounded bg-gray-800 border border-white/10 text-gray-500 hover:text-gray-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6h16.5"/>
                            </svg>
                        </div>
                        {{-- Settings --}}
                        <button wire:click="selectBlock('{{ $block['id'] }}')"
                                class="flex items-center px-2 py-0.5 rounded text-[11px] font-medium gap-1 {{ $isSelected ? 'bg-brand-600 text-white' : 'bg-gray-800 border border-white/10 text-gray-400 hover:text-white' }}">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            Edit
                        </button>
                        {{-- Delete --}}
                        <button wire:click="removeBlock('{{ $block['id'] }}')"
                                class="flex items-center px-2 py-0.5 rounded text-[11px] bg-gray-800 border border-white/10 text-gray-500 hover:text-red-400 hover:border-red-500/30">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endunless

                    {{-- ── BLOCK RENDERERS ── --}}

                    {{-- KPI ROW --}}
                    @if($block['type'] === 'kpi_row')
                    <div class="p-5">
                        @if(!empty($cfg['title']))
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-4">{{ $cfg['title'] }}</p>
                        @endif
                        <div class="grid gap-4" style="grid-template-columns: repeat({{ min(count($cfg['metrics'] ?? ['spend','impressions','ctr','cpl']), 4) }}, 1fr)">
                            @foreach($cfg['metrics'] ?? ['spend','impressions','ctr','cpl'] as $metric)
                            @php
                                $lbl   = $kpiLabels[$metric] ?? ['label'=>strtoupper($metric),'format'=>'number','icon'=>''];
                                $val   = $kpis[$metric] ?? 0;
                                $fmt   = $lbl['format'] ?? 'number';
                                $disp  = $fmt === 'currency' ? 'SAR ' . number_format($val, 0) : ($fmt === 'percent' ? $val . '%' : number_format($val, 0));
                                $bm    = $bmarks[$metric] ?? null;
                                $p50   = $bm ? (float)$bm['p50'] : null;
                                // for cost metrics lower=better; for ctr/leads higher=better
                                $costMetrics = ['cpc','cpm','cpl','cpa'];
                                $aboveBm = null;
                                if ($p50 !== null && $p50 > 0) {
                                    $aboveBm = in_array($metric, $costMetrics) ? ($val <= $p50) : ($val >= $p50);
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
                        $chartMetric = $cfg['metric'] ?? 'spend';
                        $chartTitle  = $cfg['title'] ?? 'Trend';
                        $chartData   = json_encode($trend[$chartMetric] ?? []);
                        $chartLabels = json_encode($trend['labels'] ?? []);
                        $chartId     = 'chart_' . $block['id'];
                    @endphp
                    <div class="p-5">
                        <p class="text-sm font-semibold text-gray-100 mb-4">{{ $chartTitle }}</p>
                        <div class="h-48" wire:ignore>
                            <canvas id="{{ $chartId }}"
                                x-data
                                x-init="
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
                                            plugins: { legend: { display: false }, tooltip: { mode: 'index' } },
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

                    {{-- BAR CHART (Platform comparison) --}}
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
                                x-data
                                x-init="
                                    const ctx = document.getElementById('{{ $barId }}').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: {{ $barLabels }},
                                            datasets: [{
                                                data: {{ $barValues }},
                                                backgroundColor: {{ $barColors }},
                                                borderRadius: 6,
                                                borderSkipped: false
                                            }]
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
                            <p class="text-xs text-gray-500 py-4 text-center">No benchmark data — set industry & platform on the report.</p>
                        @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/8">
                                    <th class="text-start pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Metric</th>
                                    <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Your Result</th>
                                    <th class="text-center pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">P25 Good</th>
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
                                        'currency' => 'SAR '.number_format($v, 0),
                                        'percent'  => number_format($v, 2).'%',
                                        default    => number_format($v, 0)
                                    };
                                    $diff  = $p502 > 0 ? round((($val2 - $p502) / $p502) * 100) : 0;
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
                                        <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Impr.</th>
                                        <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Clicks</th>
                                        <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">CTR</th>
                                        <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Leads</th>
                                        <th class="text-end pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">CPL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach($campRows as $camp)
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="py-2.5 font-medium text-gray-200 max-w-xs truncate">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full flex-shrink-0" style="background: {{ $platformColors[$camp['platform']] ?? '#6b7280' }}"></span>
                                                {{ $camp['name'] }}
                                            </div>
                                        </td>
                                        <td class="py-2.5 text-end font-mono text-gray-200">{{ number_format($camp['spend'], 0) }}</td>
                                        <td class="py-2.5 text-end font-mono text-gray-400 text-xs">{{ number_format($camp['impressions'], 0) }}</td>
                                        <td class="py-2.5 text-end font-mono text-gray-400 text-xs">{{ number_format($camp['clicks'], 0) }}</td>
                                        <td class="py-2.5 text-end font-mono text-gray-300">{{ $camp['ctr'] }}%</td>
                                        <td class="py-2.5 text-end font-mono text-gray-300">{{ number_format($camp['leads'], 0) }}</td>
                                        <td class="py-2.5 text-end font-mono text-gray-300">
                                            {{ $camp['cpl'] > 0 ? number_format($camp['cpl'], 0) : '—' }}
                                        </td>
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
                            @php
                                $spVal = $sp[$splitMetric] ?? 0;
                                $spPct = $maxSplit > 0 ? round(($spVal / $maxSplit) * 100) : 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-24 text-sm text-gray-300 flex-shrink-0">
                                    {{ $platformLabels[$sp['platform']] ?? $sp['platform'] }}
                                </div>
                                <div class="flex-1 h-6 bg-white/5 rounded-md overflow-hidden">
                                    <div class="h-full rounded-md transition-all"
                                         style="width:{{ $spPct }}%; background:{{ $platformColors[$sp['platform']] ?? '#6b7280' }}20; border-right: 2px solid {{ $platformColors[$sp['platform']] ?? '#6b7280' }}"></div>
                                </div>
                                <div class="w-28 text-right font-mono text-sm text-white flex-shrink-0">
                                    {{ $splitMetric === 'spend' ? 'SAR '.number_format($spVal, 0) : number_format($spVal, 0) }}
                                </div>
                                <div class="w-10 text-right text-xs text-gray-500">{{ $sp['pct'] }}%</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- AI SUMMARY --}}
                    @elseif($block['type'] === 'ai_summary')
                    @php
                        $aiSection = $cfg['section'] ?? 'executive_summary';
                        $aiTitle   = $cfg['title'] ?? 'Executive Summary';
                        $aiText    = match($aiSection) {
                            'executive_summary' => $report->executive_summary,
                            'full_narrative'    => $report->sections['full_narrative'] ?? null,
                            default             => $report->executive_summary,
                        };
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
                            <p class="text-xs text-gray-500 italic">Generate the AI report first to populate this block.</p>
                        @endif
                    </div>

                    {{-- TEXT HEADING --}}
                    @elseif($block['type'] === 'text_heading')
                    <div class="px-5 py-4">
                        @php $level = $cfg['level'] ?? 'h2'; @endphp
                        @if($level === 'h1')
                            <h1 class="text-2xl font-bold text-white">{{ $cfg['content'] ?? 'Section' }}</h1>
                        @elseif($level === 'h3')
                            <h3 class="text-base font-semibold text-gray-200">{{ $cfg['content'] ?? 'Section' }}</h3>
                        @else
                            <h2 class="text-lg font-bold text-white">{{ $cfg['content'] ?? 'Section' }}</h2>
                        @endif
                    </div>

                    {{-- DIVIDER --}}
                    @elseif($block['type'] === 'divider')
                    <div class="py-3 px-5">
                        <div class="border-t border-white/8"></div>
                    </div>

                    @endif
                </div>
                @endforeach
            </div>

            {{-- Bottom padding --}}
            <div class="h-16"></div>
        </div>

        {{-- RIGHT CONFIG PANEL --}}
        @unless($preview)
        @if($selectedBlockId)
        @php $selBlock = collect($layout['blocks'])->firstWhere('id', $selectedBlockId); @endphp
        @if($selBlock)
        <div class="w-72 flex-shrink-0 overflow-y-auto border-l border-white/8 py-5 px-4"
             style="background:#161b22;">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Block Settings</p>
                <button wire:click="$set('selectedBlockId', null)" class="text-gray-600 hover:text-gray-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Type label --}}
            <div class="mb-4 px-3 py-2 rounded-lg bg-white/5 border border-white/8">
                <p class="text-xs text-gray-400">Block type: <span class="font-semibold text-gray-200">{{ str_replace('_', ' ', ucfirst($selBlock['type'])) }}</span></p>
            </div>

            {{-- Title field (all types except divider) --}}
            @if(!in_array($selBlock['type'], ['divider', 'text_heading']))
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Title / Label</label>
                <input wire:model="cfgTitle" type="text" placeholder="Leave blank to hide"
                       class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white placeholder-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30">
            </div>
            @endif

            {{-- KPI Metrics selector --}}
            @if($selBlock['type'] === 'kpi_row' || $selBlock['type'] === 'benchmark_table')
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-2">Metrics (pick up to 4)</label>
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach(array_keys($kpiLabels) as $m)
                    <label class="flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer
                        {{ in_array($m, $cfgMetrics) ? 'bg-brand-500/10 border border-brand-500/30' : 'bg-white/3 border border-white/8 hover:bg-white/6' }}">
                        <input type="checkbox" wire:model="cfgMetrics" value="{{ $m }}"
                               class="rounded border-white/20 text-brand-500 focus:ring-brand-500/30 h-3 w-3">
                        <span class="text-[11px] font-medium {{ in_array($m, $cfgMetrics) ? 'text-brand-300' : 'text-gray-400' }}">
                            {{ $kpiLabels[$m]['label'] }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Chart metric selector --}}
            @if(in_array($selBlock['type'], ['chart_line', 'chart_bar', 'platform_split']))
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Primary Metric</label>
                <select wire:model="cfgMetric"
                        class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-200 focus:outline-none focus:border-brand-500">
                    @foreach($kpiLabels as $mk => $ml)
                        <option value="{{ $mk }}">{{ $ml['label'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Trend granularity --}}
            @if($selBlock['type'] === 'chart_line')
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Time Granularity</label>
                <div class="flex rounded-md overflow-hidden border border-white/10">
                    @foreach(['day' => 'Daily', 'week' => 'Weekly'] as $g => $gl)
                    <button wire:click="$set('cfgGrain', '{{ $g }}')"
                            class="flex-1 py-1.5 text-xs font-medium transition-colors {{ $cfgGrain === $g ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-400 hover:text-white' }}">
                        {{ $gl }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Campaign rows --}}
            @if($selBlock['type'] === 'campaign_table')
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Max Rows</label>
                <select wire:model="cfgRows"
                        class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-200 focus:outline-none focus:border-brand-500">
                    @foreach([5,10,15,20] as $n)
                        <option value="{{ $n }}">{{ $n }} rows</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Text heading content --}}
            @if($selBlock['type'] === 'text_heading')
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Heading Text</label>
                <input wire:model="cfgContent" type="text"
                       class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white placeholder-gray-600 focus:outline-none focus:border-brand-500">
            </div>
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">Heading Level</label>
                <div class="flex rounded-md overflow-hidden border border-white/10">
                    @foreach(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'] as $lv => $lbl)
                    <button wire:click="$set('cfgContent', cfgContent)"
                            class="flex-1 py-1.5 text-xs font-medium bg-white/5 text-gray-400 hover:text-white transition-colors">
                        {{ $lbl }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- AI section selector --}}
            @if($selBlock['type'] === 'ai_summary')
            <div class="mb-4">
                <label class="block text-[12px] font-medium text-gray-400 mb-1.5">AI Section</label>
                <select wire:model.live="cfgMetric"
                        class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-200 focus:outline-none focus:border-brand-500">
                    <option value="executive_summary">Executive Summary</option>
                    <option value="full_narrative">Full Analysis</option>
                </select>
            </div>
            @endif

            {{-- Apply button --}}
            <button wire:click="saveConfig"
                    class="w-full mt-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-md transition-colors">
                Apply Changes
            </button>

            {{-- Delete block --}}
            <button wire:click="removeBlock('{{ $selectedBlockId }}')"
                    class="w-full mt-2 px-4 py-2 bg-white/5 hover:bg-red-500/10 text-gray-400 hover:text-red-400 text-sm rounded-md transition-colors border border-white/8 hover:border-red-500/20">
                Remove Block
            </button>
        </div>
        @endif
        @endif
        @endunless

    </div>{{-- end flex row --}}
</div>

{{-- Sortable.js for drag-and-drop reorder --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>

<script>
function reportDesigner() {
    return {
        init() {
            this.$nextTick(() => this.initSortable());
        },
        initSortable() {
            const el = document.getElementById('blocks-sortable');
            if (!el) return;
            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'opacity-30',
                onEnd: (evt) => {
                    const ids = Array.from(el.querySelectorAll('[data-block-id]'))
                                     .map(e => e.dataset.blockId);
                    @this.reorderBlocks(ids);
                }
            });
        }
    };
}
</script>
