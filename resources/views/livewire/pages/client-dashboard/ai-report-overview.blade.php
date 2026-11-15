{{-- AI Visibility report · Overview (for the client). Data comes from App\Services\AiVisibilityReport. --}}
@php
    $ctx = $aiReport['ctx'];
    $help = $aiReport['help'];
    $hero = $aiReport['hero'];
    $heroKey = in_array($aiHeroMetric, ['imp', 'cit', 'men'], true) ? $aiHeroMetric : 'imp';
    $M = $hero['metrics'][$heroKey];
    $otherKey = $heroKey === 'imp' ? 'cit' : 'imp';
    $O = $hero['metrics'][$otherKey];
    $notCaptured = '<div class="air-empty"><b>Not captured for this month.</b> This source is captured once a month'.($hero['captureMonth'] ? '; the latest capture is '.e($hero['captureMonth']) : '').'.</div>';
@endphp
<div class="air-panel" data-testid="ai-report-overview">

    {{-- Analyst note --}}
    <section class="card air-card air-note" aria-labelledby="air-comm-h">
        <div class="flex items-center gap-2"><p class="kpi-label">Analyst note</p><x-ai-help :h="$help['note']" /></div>
        <h3 id="air-comm-h">{{ $aiReport['commentary']['heading'] }}</h3>
        <p>{!! $aiReport['commentary']['html'] !!}</p>
    </section>

    {{-- Headline --}}
    <section aria-labelledby="air-h1">
        <div class="air-sec-h"><h2 id="air-h1"><x-heroicon-o-chart-bar-square />Headline <x-ai-help :h="$help['headline']" /></h2><p>Is the brand showing up inside AI answers, and is anyone arriving from them?</p></div>
        <div class="air-grid air-g-hero">
            <div class="card air-card">
                <div class="air-seg" role="group" aria-label="Headline metric">
                    @foreach(['imp' => 'Impressions', 'cit' => 'Citations', 'men' => 'Mentions'] as $k => $lbl)
                        <button type="button" wire:click="setAiHeroMetric('{{ $k }}')" aria-pressed="{{ $heroKey === $k ? 'true' : 'false' }}">{{ $lbl }}</button>
                    @endforeach
                </div>
                <h3>{{ $M['label'] }} <x-ai-help :h="$help['hero_'.$heroKey]" /></h3>
                <div class="air-sub">{{ $M['sub'] }}</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => $M['type'], 'stage' => 'Pre-click', 'src' => $M['src']])
                <div class="air-spacer"></div>
                @if($M['value'] === null)
                    {!! $notCaptured !!}
                @else
                    <div class="air-big">{{ number_format($M['value']) }}</div>
                    @include('livewire.pages.client-dashboard.ai-delta', ['delta' => $M['delta']])
                @endif
            </div>

            <div class="card air-card">
                @if($heroKey === 'imp')
                    @php $ch = $hero['chart']; @endphp
                    <h3>{{ $dashboardData['client']['name'] ?? 'Client' }} · AI impressions by {{ $ch['kind'] === 'daily' ? 'day, '.\Carbon\Carbon::parse($ctx['sel'].'-01')->format('F') : 'month' }} <x-ai-help :h="$help['daily']" /></h3>
                    @if($ch['kind'] === 'daily')
                        <div class="air-legend"><span><i style="background:var(--air-bar);opacity:.55"></i>Daily</span><span><i class="ln" style="background:var(--air-avg)"></i>7-day average</span></div>
                        @php
                            $W = 720; $H = 230; $L = 44; $R = 8; $T = 10; $B = 26;
                            $rows = $ch['rows']; $n = count($rows);
                            $max = $airNice(max(array_column($rows, 'v')) * 1.05);
                            $band = ($W - $L - $R) / max(1, $n); $bw = max(2, $band - 2);
                            $line = '';
                            foreach ($rows as $k => $r) {
                                $cx = $L + $band * $k + $band / 2; $cy = $H - $B - ($H - $T - $B) * $r['avg'] / $max;
                                $line .= ($k ? 'L' : 'M').round($cx, 1).','.round($cy, 1);
                            }
                            $last = end($rows);
                        @endphp
                        <div class="air-chart">
                            <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="AI impressions by day">
                                @for($g = 0; $g <= 4; $g++)
                                    @php $gy = $T + ($H - $T - $B) * (1 - $g / 4); $gv = $max * $g / 4; @endphp
                                    <line class="air-gl" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" />
                                    <text class="air-ax" x="{{ $L - 6 }}" y="{{ $gy + 4 }}" text-anchor="end">{{ $gv >= 1000 ? rtrim(rtrim(number_format($gv / 1000, 1), '0'), '.').'k' : round($gv) }}</text>
                                @endfor
                                @foreach($rows as $k => $r)
                                    @php $bh = ($H - $T - $B) * $r['v'] / $max; $bx = $L + $band * $k + 1; @endphp
                                    <rect x="{{ round($bx, 1) }}" y="{{ round($H - $B - $bh, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($bh, 1) }}" rx="{{ min(3, $bw / 2) }}" fill="var(--air-bar)" opacity=".55" />
                                    @if($r['day'] === 1 || $r['day'] % 5 === 0)
                                        <text class="air-ax" x="{{ round($L + $band * $k + $band / 2, 1) }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $r['day'] }}</text>
                                    @endif
                                @endforeach
                                <path d="{{ $line }}" fill="none" stroke="var(--air-avg)" stroke-width="2" stroke-linejoin="round" />
                                <circle cx="{{ round($L + $band * ($n - 1) + $band / 2, 1) }}" cy="{{ round($H - $B - ($H - $T - $B) * $last['avg'] / $max, 1) }}" r="4" fill="var(--air-avg)" stroke="var(--air-pie-gap)" stroke-width="2" />
                                @foreach($rows as $k => $r)
                                    <rect class="air-hit" x="{{ round($L + $band * $k, 1) }}" y="{{ $T }}" width="{{ round($band, 1) }}" height="{{ $H - $T - $B }}" fill="transparent"><title>{{ $r['date'] }}: {{ number_format($r['v']) }} AI impressions · 7-day avg {{ number_format($r['avg']) }}{{ $r['all'] ? ' · all web impressions '.number_format($r['all']) : '' }}</title></rect>
                                @endforeach
                            </svg>
                        </div>
                    @else
                        @include('livewire.pages.client-dashboard.ai-monthbars', ['mbars' => $ch['rows'], 'aria' => 'AI impressions by month'])
                    @endif
                    @if(!empty($ch['note']))<div class="air-caveat">{{ $ch['note'] }}</div>@endif
                @else
                    <h3>{{ $dashboardData['client']['name'] ?? 'Client' }} · {{ $M['label'] }} by month <x-ai-help :h="$help['hero_'.$heroKey]" /></h3>
                    @if($hero['monthly'][$heroKey])
                        @include('livewire.pages.client-dashboard.ai-monthbars', ['mbars' => $hero['monthly'][$heroKey], 'aria' => $M['label'].' by month'])
                    @else
                        {!! $notCaptured !!}
                    @endif
                    <div class="air-caveat">{{ $heroKey === 'men' ? 'Mentions are simulated and captured once a month, so this view is monthly. The earlier month is back-calculated from Semrush\'s month-on-month change.' : 'Clarity gives a 30-day citation total, not a daily series. Each monthly capture adds a bar to this chart.' }}</div>
                @endif
            </div>
        </div>

        <div class="air-grid air-g-3" style="margin-top:14px">
            {{-- Second observed metric --}}
            <div class="card air-card">
                <h3>{{ $O['label'] }} <x-ai-help :h="$help['hero_'.$otherKey]" /></h3>
                <div class="air-sub">{{ $O['sub'] }}</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => $O['src']])
                @if($O['value'] === null)
                    {!! $notCaptured !!}
                @else
                    <div class="air-mid">{{ number_format($O['value']) }}</div>
                    @include('livewire.pages.client-dashboard.ai-delta', ['delta' => $O['delta']])
                @endif
            </div>

            {{-- Non-branded Share of Authority --}}
            @php $soa = $aiReport['tiles']['soa']; @endphp
            <div class="card air-card">
                <h3>Non-branded Share of Authority <x-ai-help :h="$help['soa']" /></h3>
                <div class="air-sub">Our share of Copilot citations on queries that don't name the brand</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Microsoft Clarity · last 30 days'])
                @if($soa)
                    <div class="air-mid">{{ number_format($soa['value'], 1) }}%</div>
                    <div class="air-delta"><span class="vs">{{ $soa['note'] }}</span></div>
                    @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $soa['pie'], 'small' => true, 'aria' => 'AI citations: branded vs non-branded'])
                @else
                    {!! $notCaptured !!}
                @endif
            </div>

            {{-- AI-sourced sessions --}}
            @php $ses = $aiReport['tiles']['sessions']; @endphp
            <div class="card air-card">
                <h3>AI-sourced sessions <x-ai-help :h="$help['sessions']" /></h3>
                <div class="air-sub">Visits arriving from ChatGPT, Gemini, Copilot, Perplexity and other assistants</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => 'Google Analytics 4 · AI Assistant channel'])
                @if($ses)
                    <div class="air-mid">{{ number_format($ses['value']) }}</div>
                    @include('livewire.pages.client-dashboard.ai-delta', ['delta' => $ses['delta']])
                @else
                    <div class="air-empty"><b>No GA4 data for this month.</b> Connect GA4 in Data &amp; settings.</div>
                @endif
            </div>
        </div>
    </section>

    {{-- Landscape --}}
    @php $land = $aiReport['landscape']; @endphp
    <section aria-labelledby="air-h2">
        <div class="air-sec-h"><h2 id="air-h2"><x-heroicon-o-globe-alt />Landscape <x-ai-help :h="$help['landscape']" /></h2><p>Simulated: Semrush asks the models a fixed prompt set each month</p></div>
        <div class="air-grid air-g-land">
            <div class="card air-card">
                <h3>Where AI mentions {{ $dashboardData['client']['name'] ?? 'the brand' }} <x-ai-help :h="$help['country']" /></h3>
                <div class="air-sub">Share of brand mentions by the country the prompt was asked in</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $land['src']])
                @if($land['captured'] && $land['countries'])
                    @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $land['countries'], 'donut' => true, 'center' => number_format(array_sum(array_column($land['countries'], 1))), 'centerSub' => 'mentions', 'firstIsClient' => true, 'aria' => 'Mentions by country'])
                @else
                    {!! $notCaptured !!}
                @endif
                @if(!$aiReport['competitors']['available'])<div class="air-caveat">Competitor share of voice needs a Semrush brand summary for each competitor. Add the brands on the Competitors tab.</div>@endif
            </div>
            <div class="card air-card">
                <h3>Mentions by AI platform <x-ai-help :h="$help['platform']" /></h3>
                <div class="air-sub">{{ $dashboardData['client']['name'] ?? 'Brand' }} mentions per platform, same prompt set each month</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $land['src']])
                @if($land['captured'] && $land['platforms'])
                    @php $platColors = ['var(--air-bar)']; @endphp
                    <div class="air-hbs">
                        @foreach($land['platforms'] as $k => $p)
                            <div class="air-hb"><span class="air-hb-l">{{ $p['name'] }}</span><span class="air-hb-t"><span class="air-hb-f" style="width:{{ number_format($p['v'] / max(1, $land['platforms'][0]['v']) * 100, 1, '.', '') }}%;background:{{ $platColors[0] }}"></span></span><span class="air-hb-v">{{ number_format($p['v']) }}</span><span class="air-hb-d">{{ round($p['pct']) }}%</span></div>
                        @endforeach
                    </div>
                    @if($land['footnote'])<p class="air-src" style="margin:4px 0 0">{{ $land['footnote'] }}</p>@endif
                    <details class="air-details"><summary>Show as table</summary>
                        <div class="air-tbl" style="margin-top:8px"><table class="data-table"><thead><tr><th>Platform</th><th>Mentions</th><th>Share</th></tr></thead><tbody>
                            @foreach($land['platforms'] as $p)<tr><td>{{ $p['name'] }}</td><td>{{ number_format($p['v']) }}</td><td>{{ number_format($p['pct'], 1) }}%</td></tr>@endforeach
                        </tbody></table></div>
                    </details>
                @else
                    {!! $notCaptured !!}
                @endif
            </div>
        </div>
    </section>

    {{-- Competitors --}}
    @php $cmp = $aiReport['competitors']; @endphp
    <section aria-labelledby="air-h-cmp">
        <div class="air-sec-h"><h2 id="air-h-cmp"><x-heroicon-o-user-group />Competitors <x-ai-help :h="$help['competitors']" /></h2><p>Simulated: Semrush runs a prompt set for each tracked brand</p></div>
        @if($cmp['available'])
            <div class="air-grid air-g-land">
                <div class="card air-card">
                    <h3>AI share of voice <x-ai-help :h="$help['sov']" /></h3>
                    <div class="air-sub">Share of brand mentions across the tracked brands</div>
                    @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cmp['src']])
                    @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $cmp['sov'], 'donut' => true, 'center' => $cmp['share'] !== null ? number_format($cmp['share'], 1).'%' : '—', 'centerSub' => 'our share', 'firstIsClient' => true, 'aria' => 'AI share of voice by brand'])
                </div>
                <div class="card air-card">
                    <h3>AI visibility score by brand <x-ai-help :h="$help['cmpscore']" /></h3>
                    <div class="air-sub">Semrush AI Visibility score out of 100, highest first</div>
                    @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cmp['src']])
                    @include('livewire.pages.client-dashboard.ai-hbars', [
                        'bars' => collect($cmp['scores'])->map(fn($r) => ['l' => $r['name'], 's' => $r['is_client'] ? 'You' : $r['domain'], 'v' => $r['score'], 'c' => $r['is_client'] ? '' : 'org'])->all(),
                        'barMax' => 100,
                    ])
                    @if($cmp['summary'])<p class="air-fsum" style="margin-top:12px">{!! $cmp['summary'] !!}</p>@endif
                    <details class="air-details"><summary>Show as table</summary>
                        <div class="air-tbl" style="margin-top:8px"><table class="data-table"><thead><tr><th>Brand</th><th>AI score</th><th>Mentions</th><th>Citations</th><th>Cited pages</th><th>Prompts</th></tr></thead><tbody>
                            @foreach($cmp['rows'] as $r)
                                <tr @if($r['is_client']) style="font-weight:600" @endif><td>{{ $r['name'] }}@if($r['is_client']) <span class="text-xs" style="color:var(--ai-muted)">You</span>@endif</td>
                                @foreach(['score', 'mentions', 'citations', 'cited_pages', 'prompts'] as $k)<td class="tabular-nums">{{ $r[$k] !== null ? number_format($r[$k]) : '—' }}</td>@endforeach</tr>
                            @endforeach
                        </tbody></table></div>
                    </details>
                </div>
            </div>
            <div class="air-caveat">@if($cmp['note']){{ $cmp['note'] }} @endif Each brand has its own Semrush prompt set, so read shares as a direction. Add or edit brands on the Competitors tab.</div>
        @else
            <div class="air-empty"><b>No competitor data yet.</b> Add competitors on the Competitors tab and upload a Semrush brand summary for each one.</div>
        @endif
    </section>

    {{-- Post-click --}}
    @php $fun = $aiReport['funnel']; @endphp
    <section aria-labelledby="air-h3">
        <div class="air-sec-h"><h2 id="air-h3"><x-heroicon-o-shopping-cart />Post-click <x-ai-help :h="$help['postclick']" /></h2><p>{{ $fun['sub'] }}</p></div>
        @if($fun['available'])
            <div class="air-grid air-g-4">
                @foreach($fun['steps'] as $k => $s)
                    <div class="card air-card">
                        <p class="kpi-label">{{ $s['label'] }}</p>
                        <div class="air-mid">{{ number_format($s['v']) }}</div>
                        <div class="air-track"><div class="air-fill" style="width:{{ number_format($s['width'], 1, '.', '') }}%"></div></div>
                        <div class="air-conv">{{ $s['conv'] }}</div>
                        @if($k === 3 && $fun['revenue'] !== null)<div class="air-rev">Revenue <b class="tabular-nums">SAR {{ number_format($fun['revenue']) }}</b></div>@endif
                    </div>
                @endforeach
            </div>
            @if($fun['summary'])<p class="air-fsum">{!! $fun['summary'] !!}</p>@endif
        @else
            <div class="air-empty"><b>No funnel export for this month.</b> The funnel comes from the GA4 ecommerce events in the monthly workbook (AI_Funnel sheet).</div>
        @endif
    </section>
</div>
