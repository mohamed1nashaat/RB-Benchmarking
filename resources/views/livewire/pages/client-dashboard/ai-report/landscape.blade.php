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
            @if(!$aiReport['competitors']['available'])<div class="air-caveat">Competitor share of voice needs a Semrush brand summary for each competitor. Add the brands in Competitor comparison below.</div>@endif
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
