{{-- AI Visibility report · Diagnostics (the why behind the numbers). --}}
@php
    $ctx = $aiReport['ctx'];
    $help = $aiReport['help'];
    $tq = $aiReport['traffic'];
    $gr = $aiReport['grounding'];
    $fr = $aiReport['friction'];
    $cw = $aiReport['competitive'];
    $captureMonth = $aiReport['hero']['captureMonth'];
    $notCaptured = '<div class="air-empty"><b>Not captured for this month.</b> This source is captured once a month'.($captureMonth ? '; the latest capture is '.e($captureMonth) : '').'.</div>';
    $brand = $dashboardData['client']['name'] ?? 'the brand';
@endphp
<div class="air-panel" data-testid="ai-report-diagnostics">

    {{-- Traffic quality --}}
    <section aria-labelledby="air-d1">
        <div class="air-sec-h"><h2 id="air-d1"><x-heroicon-o-arrow-trending-up />Traffic quality <x-ai-help :h="$help['traffic']" /></h2><p>Observed · GA4 and Clarity · {{ $ctx['label'] }}</p></div>
        <div class="air-grid air-g-2">
            <div class="card air-card">
                <h3>AI-sourced sessions by platform <x-ai-help :h="$help['platsess']" /></h3>
                <div class="air-sub">Where AI visitors actually come from</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => $tq['platSrc']])
                @if($tq['platforms'])
                    @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => $tq['platforms'], 'barFmt' => 'int'])
                    @if(collect($tq['platforms'])->contains(fn($p) => $p['d'] !== null))<p class="air-src" style="margin:0">Change {{ $ctx['partial'] ? 'is daily pace ' : '' }}vs {{ $ctx['prev_label'] }}.</p>@endif
                @else
                    <div class="air-empty">No AI sessions recorded for this month.</div>
                @endif
            </div>
            <div class="card air-card">
                <h3>AI visitors vs {{ $tq['cmp'] ? strtolower($tq['cmp']['against']) : 'organic visitors' }} <x-ai-help :h="$help['cmp']" /></h3>
                <div class="air-sub">{{ $tq['cmp']['sub'] ?? 'Same site, same month' }}</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => 'Google Analytics 4'])
                @if($tq['cmp'])
                    <div class="air-lead">{{ $tq['cmp']['lead'] }}</div>
                    <div>
                        @foreach($tq['cmp']['rows'] as $r)
                            @php $mult = $r['o'] ? $r['a'] / $r['o'] : 0; $mx = max($r['a'], $r['o']) ?: 1; @endphp
                            <div class="air-cmp-row">
                                <div class="air-cmp-h"><span>{{ $r['m'] }}</span><span class="air-cmp-x {{ $mult < 1 ? 'down' : '' }}">{{ number_format($mult, 1) }}x</span></div>
                                @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => [['l' => 'AI visitors', 'v' => $r['a']], ['l' => $tq['cmp']['against'], 'v' => $r['o'], 'c' => 'org']], 'barMax' => $mx, 'barFmt' => $r['fmt'] === 'pct' ? 'pct2' : 'sar'])
                            </div>
                        @endforeach
                    </div>
                    <div class="air-caveat">Organic Search also contains clicks from Google AI Overviews and AI Mode, which arrive with the same referrer and can't be split out. AI Assistant samples are small (a handful of orders a month), so read the ratio as direction, not precision.</div>
                @else
                    <div class="air-empty">Not enough GA4 data for this month to compare.</div>
                @endif
            </div>
        </div>
        <div class="card air-card" style="margin-top:14px">
            <h3>Top AI landing pages <x-ai-help :h="$help['landing']" /></h3>
            <div class="air-sub">Where visitors from AI assistants land, {{ $ctx['label'] }}</div>
            @if($tq['landing'])
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => 'Google Analytics 4 · AI Assistant channel · landing page'])
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Landing page</th><th>AI sessions</th><th>Conversions</th><th>Revenue</th></tr></thead>
                    <tbody>
                    @foreach($tq['landing'] as $lp)
                        <tr><td class="wrap"><span class="air-ar" dir="auto" title="{{ $lp['page'] }}">{{ $lp['short'] }}</span></td><td>{{ number_format($lp['sessions']) }}</td><td>{{ number_format($lp['key_events']) }}</td><td>{{ $lp['revenue'] ? 'SAR '.number_format($lp['revenue']) : '—' }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            @else
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => 'Google Analytics 4 · AI Assistant channel', 'flag' => 'Not synced'])
                <div class="air-empty"><b>No landing-page data for this month yet.</b> Run "Sync from Google" in Data &amp; settings to pull AI landing pages from GA4.</div>
            @endif
        </div>
    </section>

    {{-- Grounding and citations --}}
    <section aria-labelledby="air-d2">
        <div class="air-sec-h"><h2 id="air-d2"><x-heroicon-o-link />Grounding and citations <x-ai-help :h="$help['grounding']" /></h2><p>Observed · Search Console, Bing (via Clarity), Clarity</p></div>
        <div class="card air-card">
            <h3>Top grounding queries and our citation share <x-ai-help :h="$help['gq']" /></h3>
            <div class="air-sub">The searches Copilot ran to build its answers, and how often it cited us</div>
            @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => $gr['src']])
            @if($gr['captured'] && $gr['queries'])
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Grounding query</th><th>Type</th><th>Citations</th><th>Citation share</th></tr></thead>
                    <tbody>
                    @foreach($gr['queries'] as $q)
                        @php $sh = $q['share'] !== null ? $q['share'] * 100 : null; @endphp
                        <tr><td class="wrap"><span class="air-ar" dir="auto">{{ $q['query'] }}</span></td><td><span class="air-tag">{{ $q['type'] }}</span></td><td>{{ $q['citations'] !== null ? number_format($q['citations']) : '—' }}</td><td class="{{ $sh === null ? '' : ($sh >= 30 ? 'ok' : ($sh < 15 ? 'warn' : '')) }}">{{ $sh !== null ? number_format($sh, 1).'%' : '—' }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
                @if($gr['queryFoot'])<p class="air-src" style="margin:0">{{ $gr['queryFoot'] }}</p>@endif
                <div class="air-caveat">Grounding queries are a representative sample, not a complete log. Citation share is our slice of all citations shown for that query.</div>
            @else
                {!! $notCaptured !!}
            @endif
        </div>

        {{-- AI citations as pie charts --}}
        <div class="card air-card" style="margin-top:14px">
            <h3>AI citations breakdown <x-ai-help :h="$help['citpie']" /></h3>
            <div class="air-sub">{{ $gr['total'] ? number_format($gr['total']).' Copilot citations in the last 30 days, split three ways' : 'Copilot citations, split three ways' }}</div>
            @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Microsoft Clarity · AI Visibility · last 30 days'])
            @if($gr['captured'] && ($gr['pies']['type'] || $gr['pies']['topic']))
                <div class="air-grid air-g-3">
                    <div><p class="air-src" style="margin:0 0 8px;font-weight:600">By query type</p>@include('livewire.pages.client-dashboard.ai-pie', ['rows' => $gr['pies']['type'], 'firstIsClient' => true, 'aria' => 'AI citations by query type'])</div>
                    <div><p class="air-src" style="margin:0 0 8px;font-weight:600">By topic</p>@include('livewire.pages.client-dashboard.ai-pie', ['rows' => $gr['pies']['topic'], 'aria' => 'AI citations by topic'])</div>
                    <div><p class="air-src" style="margin:0 0 8px;font-weight:600">By cited page</p>@include('livewire.pages.client-dashboard.ai-pie', ['rows' => $gr['pies']['page'], 'aria' => 'AI citations by cited page'])</div>
                </div>
            @else
                {!! $notCaptured !!}
            @endif
        </div>

        <div class="air-grid air-g-2" style="margin-top:14px">
            <div class="card air-card">
                <h3>Pages Google shows in AI answers <x-ai-help :h="$help['aipages']" /></h3>
                <div class="air-sub">{{ $gr['aiPagesSub'] }}</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Google Search Console · Generative AI features'])
                @if($gr['aiPages'])
                    @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => $gr['aiPages'], 'barFmt' => 'k'])
                @else
                    <div class="air-empty">No AI page export yet.</div>
                @endif
            </div>
            <div class="card air-card">
                <h3>Pages Copilot cites <x-ai-help :h="$help['citpages']" /></h3>
                <div class="air-sub">Citations per page, last 30 days</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Microsoft Clarity · AI Visibility'])
                @if($gr['captured'] && $gr['citPages'])
                    @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => $gr['citPages'], 'barFmt' => 'int'])
                @else
                    {!! $notCaptured !!}
                @endif
            </div>
        </div>

        <div class="air-grid air-g-2" style="margin-top:14px">
            <div class="card air-card">
                <h3>AI crawler visits <x-ai-help :h="$help['crawler']" /></h3>
                <div class="air-sub">Requests from AI bots to the site</div>
                @php $botOn = $gr['bot'] && !str_contains(strtolower($gr['bot']), 'not'); @endphp
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Microsoft Clarity · Bot activity', 'flag' => $botOn ? null : 'Not connected'])
                @if($botOn)
                    <div class="air-empty"><b>Connected:</b> {{ $gr['bot'] }}. Bot visit counts will appear here once they are included in the monthly export.</div>
                @else
                    <div class="air-empty"><b>Not connected yet.</b> Clarity's Bot activity view needs a CDN integration (Cloudflare, Akamai or similar) before it can count GPTBot, ClaudeBot, PerplexityBot and the rest. Until then we can't say which key pages AI crawlers miss.</div>
                @endif
            </div>
            <div class="card air-card">
                <h3>Share of Authority: branded vs non-branded <x-ai-help :h="$help['soacard']" /></h3>
                <div class="air-sub">Our share of all citations Copilot showed, last 30 days</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Pre-click', 'src' => 'Microsoft Clarity · AI Visibility'])
                @if($gr['captured'] && ($gr['soa']['branded'] || $gr['soa']['non_branded']))
                    @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => array_values(array_filter([
                        $gr['soa']['branded'] ? ['l' => 'Branded', 's' => number_format($gr['soa']['branded']['citations'] ?? 0).' citations', 'v' => $gr['soa']['branded']['share'], 'c' => 'org'] : null,
                        $gr['soa']['non_branded'] ? ['l' => 'Non-branded', 's' => number_format($gr['soa']['non_branded']['citations'] ?? 0).' citations', 'v' => $gr['soa']['non_branded']['share']] : null,
                    ])), 'barMax' => 50, 'barFmt' => 'pct1'])
                    @if($gr['soa']['topics'])
                        <h3 style="margin-top:6px">By topic</h3>
                        @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => collect($gr['soa']['topics'])->filter(fn($t) => $t['share'] !== null)->map(fn($t) => ['l' => $t['l'], 's' => number_format($t['citations']).' citations', 'v' => $t['share']])->values()->all(), 'barMax' => 50, 'barFmt' => 'pct1'])
                    @endif
                @else
                    {!! $notCaptured !!}
                @endif
            </div>
        </div>
    </section>

    {{-- On-site experience --}}
    <section aria-labelledby="air-d3">
        <div class="air-sec-h"><h2 id="air-d3"><x-heroicon-o-cursor-arrow-rays />On-site experience <x-ai-help :h="$help['onsite']" /></h2><p>Observed · Clarity, AI visitors vs the whole site, last 30 days</p></div>
        <div class="card air-card">
            <h3>Friction on AI landing pages <x-ai-help :h="$help['friction']" /></h3>
            <div class="air-sub">What happens after someone arrives from an AI answer</div>
            @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => $fr['src']])
            @if($fr['captured'] && $fr['rows'])
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Metric (share of sessions)</th><th>AI visitors</th><th>Whole site</th><th>Read</th></tr></thead>
                    <tbody>
                    @foreach($fr['rows'] as $r)
                        <tr>
                            <td>{{ $r['l'] }}</td>
                            <td>{{ $airFmt($r['a'], 'fr_'.$r['fmt']) }}</td>
                            <td>{{ $r['s'] !== null ? $airFmt($r['s'], 'fr_'.$r['fmt']) : '—' }}</td>
                            <td class="{{ $r['good'] === null ? '' : ($r['good'] ? 'ok' : 'warn') }}">@if($r['good'] !== null)<span aria-hidden="true">{{ $r['good'] ? '✓' : '!' }}</span> {{ $r['good'] ? 'Better' : 'Worse' }}@else — @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @else
                {!! $notCaptured !!}
            @endif
        </div>
    </section>

    {{-- Competitive watch --}}
    <section aria-labelledby="air-d4">
        <div class="air-sec-h"><h2 id="air-d4"><x-heroicon-o-eye />Competitive watch <x-ai-help :h="$help['competitive']" /></h2><p>Simulated · Semrush prompt set and topics</p></div>
        <div class="air-grid air-g-2">
            <div class="card air-card">
                <h3>How AI talks about {{ $brand }} <x-ai-help :h="$help['sentiment']" /></h3>
                <div class="air-sub">Tone of answers that name the brand, by assistant</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => 'Semrush prompts, scored by the analyst', 'flag' => 'Not scored'])
                <div class="air-legend"><span><i style="background:var(--air-good)"></i>Positive</span><span><i style="background:var(--air-g)"></i>Neutral</span><span><i style="background:var(--air-bad)"></i>Cautious</span></div>
                <div class="air-empty"><b>Not scored for this month.</b> The analyst scores the Semrush answers that name the brand as positive, neutral or cautious. Once that score sheet is in the workbook, this chart shows the tone by assistant.</div>
            </div>
            <div class="card air-card">
                <h3>Topic gap watch list <x-ai-help :h="$help['gaps']" /></h3>
                <div class="air-sub">High-demand topics where AI never mentions {{ $brand }}</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cw['src']])
                @if($cw['captured'] && $cw['gaps']['rows'])
                    <div class="air-tbl"><table class="data-table">
                        <thead><tr><th>Topic</th><th>Market</th><th>Monthly searches</th></tr></thead>
                        <tbody>
                        @foreach($cw['gaps']['rows'] as $g)
                            <tr><td class="wrap"><span class="air-ar" dir="auto">{{ $g['topic'] }}</span></td><td>{{ $g['market'] }}</td><td>{{ number_format($g['volume'] ?? 0) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <p class="air-src" style="margin:0">{{ $cw['gaps']['zero'] }} of {{ $cw['gaps']['tracked'] }} tracked topics have 0 visibility · {{ number_format($cw['gaps']['volume']) }} monthly searches</p>
                @elseif($cw['captured'])
                    <div class="air-empty">No zero-visibility topics in the latest Semrush capture.</div>
                @else
                    {!! $notCaptured !!}
                @endif
            </div>
        </div>
    </section>
</div>
