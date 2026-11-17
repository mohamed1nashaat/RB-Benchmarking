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
                @if(!empty($M['valueNote']))<p class="air-src" style="margin:2px 0 0">{{ $M['valueNote'] }}</p>@endif
                @include('livewire.pages.client-dashboard.ai-delta', ['delta' => $M['delta']])
            @endif
            @if(!empty($M['platforms']))
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--ai-border)">
                    <p class="air-src" style="margin:0 0 6px">Other assistants · simulated · Semrush prompt runs</p>
                    @foreach($M['platforms'] as $pl)
                        <div style="display:flex;justify-content:space-between;gap:12px;padding:3px 0;font-size:14px;color:var(--ai-text)">
                            <span>{{ $pl['l'] }}</span>
                            <span style="font-variant-numeric:tabular-nums"><b>{{ number_format($pl['cited']) }}</b> <span style="color:var(--ai-muted)">of {{ number_format($pl['of']) }} answers</span></span>
                        </div>
                    @endforeach
                </div>
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
            @elseif($heroKey === 'cit' && !empty($aiReport['platformCitations']['pie']))
                @php $pcH = $aiReport['platformCitations']; @endphp
                <h3>{{ $dashboardData['client']['name'] ?? 'Client' }} · AI citations by platform <x-ai-help :h="$help['platcit']" /></h3>
                <div class="air-grid air-g-2" style="align-items:center">
                    <div>
                        <p class="air-src" style="margin:0 0 8px;font-weight:600">Copilot · observed</p>
                        @if($hero['monthly']['cit'])
                            @include('livewire.pages.client-dashboard.ai-monthbars', ['mbars' => $hero['monthly']['cit'], 'aria' => 'Copilot citations by month'])
                        @else
                            {!! $notCaptured !!}
                        @endif
                    </div>
                    <div>
                        <p class="air-src" style="margin:0 0 8px;font-weight:600">ChatGPT, Gemini, Google AI · simulated{{ $pcH['semLabel'] ? ' · '.$pcH['semLabel'] : '' }}</p>
                        @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $pcH['pie'], 'donut' => true, 'center' => number_format($pcH['semTotal']), 'centerSub' => 'of '.number_format($pcH['semPrompts']).' answers', 'aria' => 'Semrush citations by assistant'])
                    </div>
                </div>
                <div class="air-caveat">Copilot citations are logged by Clarity as a 30-day total, one bar per monthly capture. The other assistants come from Semrush running a fixed prompt set, so they show direction and are never added to Copilot's count.</div>
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
