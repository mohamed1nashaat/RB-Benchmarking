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
