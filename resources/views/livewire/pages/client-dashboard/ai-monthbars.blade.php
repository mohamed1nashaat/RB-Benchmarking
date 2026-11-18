{{-- Monthly columns: $mbars = [['label'=>, 'v'=>, 'cur'=>bool]] --}}
@php
    $W = 720; $H = 230; $L = 44; $R = 8; $T = 18; $B = 26;
    $mmax = $airNice(max(1, max(array_column($mbars, 'v'))) * 1.1);
    $band = ($W - $L - $R) / max(count($mbars), 4); $bw = min(64, $band * 0.55);
@endphp
<div class="air-legend"><span><i style="background:var(--air-bar)"></i>{{ $mbarCur ?? 'Selected month' }}</span><span><i style="background:var(--air-bar-dim)"></i>{{ $mbarPrev ?? 'Earlier months' }}</span></div>
<div class="air-chart">
    <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $aria ?? 'By month' }}">
        @for($g = 0; $g <= 4; $g++)
            @php $gy = $T + ($H - $T - $B) * (1 - $g / 4); $gv = $mmax * $g / 4; @endphp
            <line class="air-gl" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $gy }}" y2="{{ $gy }}" />
            <text class="air-ax" x="{{ $L - 6 }}" y="{{ $gy + 4 }}" text-anchor="end">{{ $gv >= 1000 ? rtrim(rtrim(number_format($gv / 1000, 1), '0'), '.').'k' : round($gv) }}</text>
        @endfor
        @foreach($mbars as $k => $p)
            @php $bh = ($H - $T - $B) * $p['v'] / $mmax; $bx = $L + $band * $k + ($band - $bw) / 2; @endphp
            <rect x="{{ round($bx, 1) }}" y="{{ round($H - $B - $bh, 1) }}" width="{{ round($bw, 1) }}" height="{{ round($bh, 1) }}" rx="4" fill="var({{ $p['cur'] ? '--air-bar' : '--air-bar-dim' }})"><title>{{ $p['label'] }}: {{ number_format($p['v']) }}</title></rect>
            <text class="air-ax" x="{{ round($bx + $bw / 2, 1) }}" y="{{ round($H - $B - $bh - 6, 1) }}" text-anchor="middle" style="fill:var(--ai-text);font-weight:700">{{ number_format($p['v']) }}</text>
            <text class="air-ax" x="{{ round($bx + $bw / 2, 1) }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $p['label'] }}</text>
        @endforeach
    </svg>
</div>
