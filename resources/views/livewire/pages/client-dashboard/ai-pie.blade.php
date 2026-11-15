{{-- SVG pie/donut. Expects $rows = [[label, value], …]; optional $donut, $center, $centerSub, $small, $aria, $percentDecimals. --}}
@php
    $pieColors = ['#EF4444', '#787774', '#A8A8A4', '#5C5C58', '#D4D4D2', '#3F3F3C', '#C4C4C0', '#8F8F8B'];
    $pieRows = array_values(array_filter($rows ?? [], fn($r) => (float)$r[1] > 0));
    $pieTotal = array_sum(array_map(fn($r) => (float)$r[1], $pieRows));
    $isDonut = $donut ?? false;
    $pcx = 90; $pcy = 90; $pro = 84; $pri = $isDonut ? 56 : 0;
    $pt = fn($r, $t) => number_format($pcx + $r * cos($t), 2, '.', '').','.number_format($pcy + $r * sin($t), 2, '.', '');
    $angle = -M_PI / 2;
    $slices = [];
    foreach ($pieRows as $k => $r) {
        $a2 = $angle + ($pieTotal ? $r[1] / $pieTotal : 0) * 2 * M_PI;
        $large = ($a2 - $angle) > M_PI ? 1 : 0;
        if (count($pieRows) === 1) {
            $d = $isDonut
                ? "M{$pcx},".($pcy - $pro)."A{$pro},{$pro} 0 1 1 ".($pcx - 0.01).",".($pcy - $pro)."ZM{$pcx},".($pcy - $pri)."A{$pri},{$pri} 0 1 0 ".($pcx - 0.01).",".($pcy - $pri)."Z"
                : "M{$pcx},".($pcy - $pro)."A{$pro},{$pro} 0 1 1 ".($pcx - 0.01).",".($pcy - $pro)."Z";
        } elseif ($isDonut) {
            $d = 'M'.$pt($pro, $angle)."A{$pro},{$pro} 0 {$large} 1 ".$pt($pro, $a2).'L'.$pt($pri, $a2)."A{$pri},{$pri} 0 {$large} 0 ".$pt($pri, $angle).'Z';
        } else {
            $d = "M{$pcx},{$pcy}L".$pt($pro, $angle)."A{$pro},{$pro} 0 {$large} 1 ".$pt($pro, $a2).'Z';
        }
        $slices[] = ['d' => $d, 'color' => $pieColors[$k % count($pieColors)], 'label' => $r[0], 'v' => $r[1], 'pct' => $pieTotal ? $r[1] / $pieTotal * 100 : 0];
        $angle = $a2;
    }
@endphp
@if($pieTotal > 0)
<div class="air-pie {{ !empty($small) ? 'sm' : '' }}">
    <svg viewBox="0 0 180 180" role="img" aria-label="{{ $aria ?? 'Pie chart' }}">
        @foreach($slices as $s)
            <path d="{{ $s['d'] }}" fill="{{ $s['color'] }}" fill-rule="evenodd" stroke="var(--air-pie-gap)" stroke-width="{{ count($slices) > 1 ? 1.5 : 0 }}"><title>{{ $s['label'] }}: {{ number_format($s['v']) }} ({{ number_format($s['pct'], 1) }}%)</title></path>
        @endforeach
        @if($isDonut && isset($center))
            <text x="90" y="90" text-anchor="middle" style="fill:var(--ai-text);font-weight:700;font-size:26px">{{ $center }}</text>
            <text x="90" y="108" text-anchor="middle" class="air-ax">{{ $centerSub ?? '' }}</text>
        @endif
    </svg>
    @if(empty($noLegend))
    <ul class="air-sov">
        @foreach($slices as $k => $s)
            <li class="{{ $k === 0 && !empty($firstIsClient) ? 'me' : '' }}"><i style="background:{{ $s['color'] }}"></i><span class="air-ar">{{ $s['label'] }}</span><span class="tabular-nums">{{ number_format($s['pct'], $percentDecimals ?? 1) }}%</span><span class="pts tabular-nums">{{ number_format($s['v']) }}</span></li>
        @endforeach
    </ul>
    @endif
</div>
@else
<div class="air-empty">No values to chart.</div>
@endif
