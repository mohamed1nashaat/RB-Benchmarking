{{-- Grounding and citations --}}
<section aria-labelledby="air-d2">
    <div class="air-sec-h"><h2 id="air-d2"><x-heroicon-o-link />Grounding and citations <x-ai-help :h="$help['grounding']" /></h2><p>Search Console, Clarity (Copilot), Semrush (ChatGPT, Gemini, Google AI) and GA4</p></div>

    {{-- Every assistant side by side: Clarity, Semrush and GA4 --}}
    @php $pc = $aiReport['platformCitations'] ?? ['available' => false]; @endphp
    <div class="card air-card" style="margin-bottom:14px">
        <h3>AI citations by platform <x-ai-help :h="$help['platcit']" /></h3>
        <div class="air-sub">Where each assistant links to the site, and the visits it sends</div>
        <p class="air-src">Observed · Clarity (Copilot) and GA4 (visits) · Simulated · Semrush prompt runs{{ $pc['semLabel'] ?? null ? ' · '.$pc['semLabel'] : '' }}</p>
        @if($pc['available'])
            <div class="air-tbl"><table class="data-table">
                <thead><tr><th>Assistant</th><th>Citations</th><th>Citation source</th><th>AI visits · {{ $pc['gaMonth'] }}</th></tr></thead>
                <tbody>
                @foreach($pc['rows'] as $r)
                    <tr>
                        <td>{{ $r['platform'] }}</td>
                        <td>@if($r['citations'] !== null)<b>{{ number_format($r['citations']) }}</b>@if($r['of']) <span class="air-tag">of {{ number_format($r['of']) }} answers</span>@endif @else — @endif</td>
                        <td>@if($r['type'])<span class="air-tag">{{ $r['type'] === 'obs' ? 'Observed' : 'Simulated' }} · {{ $r['src'] }}</span>@else<span class="air-tag">No citation data</span>@endif</td>
                        <td>{{ $r['visits'] !== null ? number_format($r['visits']) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="air-grid air-g-2" style="align-items:center;margin-top:14px">
                <div>
                    <p class="air-src" style="margin:0 0 8px;font-weight:600">Semrush answers citing the site, by assistant</p>
                    @if($pc['pie'])
                        @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $pc['pie'], 'donut' => true, 'center' => number_format($pc['semTotal']), 'centerSub' => 'of '.number_format($pc['semPrompts']).' answers', 'aria' => 'Semrush citations by assistant'])
                    @else
                        <div class="air-empty">No Semrush prompt export yet. Upload the prompts CSV in Data &amp; settings.</div>
                    @endif
                </div>
                @if($pc['summary'])<p style="margin:0;font-size:14px;line-height:1.6;color:var(--ai-text)">{!! $pc['summary'] !!}</p>@endif
            </div>
            <div class="air-caveat">Copilot counts are real citations logged by Clarity. The other assistants' counts come from Semrush asking a fixed prompt set, so they show direction, not totals. The two are never added together. GA4 counts visits from Google AI Overviews and AI Mode as Google organic search, so those rows show no visits; their reach is the AI impressions figure from Search Console.</div>
        @else
            {!! $notCaptured !!}
        @endif
    </div>

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
