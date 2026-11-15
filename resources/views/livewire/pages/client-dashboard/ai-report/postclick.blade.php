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
