{{-- Horizontal bar rows. $bars = [['l'=>, 's'=>?, 'v'=>, 'd'=>?(pct change), 'x'=>?(text), 'c'=>?(class), 'full'=>?(title)]], $barMax, $barFmt --}}
@php $barMax = max(1e-9, (float)($barMax ?? collect($bars)->max('v'))); @endphp
<div class="air-hbs">
    @foreach($bars as $b)
        <div class="air-hb">
            <span class="air-hb-l air-ar" @if(!empty($b['full'])) title="{{ $b['full'] }}" @endif>{{ $b['l'] }}@if(!empty($b['s']))<small>{{ $b['s'] }}</small>@endif</span>
            <span class="air-hb-t"><span class="air-hb-f {{ $b['c'] ?? '' }}" style="width:{{ number_format(max(1, $b['v'] / $barMax * 100), 1, '.', '') }}%"></span></span>
            <span class="air-hb-v">{{ $airFmt($b['v'], $barFmt ?? 'int') }}</span>
            @php $bd = $b['d'] ?? null; @endphp
            <span class="air-hb-d {{ $bd === null ? '' : ($bd >= 0 ? 'up' : 'down') }}">@if($bd !== null)<span aria-hidden="true">{{ $bd >= 0 ? '▲' : '▼' }}</span> {{ number_format(abs($bd), 0) }}%@else{{ $b['x'] ?? '' }}@endif</span>
        </div>
    @endforeach
</div>
