{{-- AI Visibility Report, Part 1. Structure follows the agreed alshifa-ai-visibility-report.html; data from App\Services\AiVisibilityPartOne. --}}
@php
    $V = $av['visibility'];
    $T = $av['traffic'];
    $TR = $av['tracker'];
    $B = collect($av['brands'])->keyBy('key');
    $client = $av['client'];
    $int = fn ($v) => $v === null ? '—' : number_format(round($v));
    $pct = fn ($v, $d = 1) => $v === null ? '—' : number_format($v * 100, $d).'%';
    $flag = fn (string $cc) => strlen($cc) === 2 ? implode('', array_map(fn ($c) => mb_chr(127397 + ord($c)), str_split(strtoupper($cc)))) : '';
    $nb = fn ($v) => $v === null ? '—' : $v;
    $noSem = 'No Semrush capture covers '.$av['monthLabel'].'. Upload the brand topics and prompts exports in Data & settings.';

    // Bars: rows [[label, value, ?key]], value scaled to the largest unless $abs.
    $bars = function (array $rows, callable $fmt, array $o = []) {
        $max = max(array_map(fn ($r) => $r[1], $rows) ?: [0]) ?: 1;
        $h = '';
        foreach ($rows as $r) {
            $w = max(1, min(100, ! empty($o['abs']) ? $r[1] * 100 : $r[1] / $max * 100));
            $me = isset($o['me']) && ($o['me'])($r);
            $h .= '<div class="av-br'.($me ? ' me' : '').(! empty($o['cls']) ? ' '.$o['cls'] : '').'"><span class="lb">'.e($r[0]).'</span><span class="vl">'.e($fmt($r[1])).'</span>'
                .'<div class="tk"><div class="fl'.(! empty($o['fill']) ? ' '.$o['fill'] : '').'" style="width:'.round($w, 2).'%"></div></div></div>';
        }

        return $h;
    };

    // Donut / pie, 130px, slices [label, value, colour, tip].
    $donut = function (array $items, bool $inner, ?string $center = null) {
        $S = 130; $R = $S / 2; $ri = $inner ? $R - 18 : 0;
        $total = array_sum(array_column($items, 1));
        $svg = '<svg width="'.$S.'" height="'.$S.'" viewBox="0 0 '.$S.' '.$S.'" role="img">';
        $items = array_values(array_filter($items, fn ($d) => $d[1] > 0));
        if ($total > 0 && count($items) === 1) {
            $d = $items[0];
            $svg .= $inner
                ? '<path data-tip="'.e($d[3]).'" fill="'.$d[2].'" fill-rule="evenodd" d="M'.$R.',0A'.$R.','.$R.' 0 1 1 '.$R.','.$S.'A'.$R.','.$R.' 0 1 1 '.$R.',0ZM'.$R.','.($R - $ri).'A'.$ri.','.$ri.' 0 1 0 '.$R.','.($R + $ri).'A'.$ri.','.$ri.' 0 1 0 '.$R.','.($R - $ri).'Z"/>'
                : '<circle data-tip="'.e($d[3]).'" cx="'.$R.'" cy="'.$R.'" r="'.$R.'" fill="'.$d[2].'"/>';
        } elseif ($total > 0) {
            $a0 = -M_PI / 2;
            $p = fn ($r, $a) => round($R + $r * cos($a), 3).','.round($R + $r * sin($a), 3);
            foreach ($items as $d) {
                $a1 = $a0 + $d[1] / $total * M_PI * 2; $pad = 0.012;
                $s = $a0 + $pad; $e = $a1 - $pad; $large = ($e - $s) > M_PI ? 1 : 0;
                $path = $inner
                    ? 'M'.$p($R, $s).'A'.$R.','.$R.' 0 '.$large.' 1 '.$p($R, $e).'L'.$p($ri, $e).'A'.$ri.','.$ri.' 0 '.$large.' 0 '.$p($ri, $s).'Z'
                    : 'M'.$R.','.$R.'L'.$p($R, $s).'A'.$R.','.$R.' 0 '.$large.' 1 '.$p($R, $e).'Z';
                $svg .= '<path data-tip="'.e($d[3]).'" d="'.$path.'" fill="'.$d[2].'"/>';
                $a0 = $a1;
            }
        }
        if ($center !== null) {
            $svg .= '<text x="'.$R.'" y="'.$R.'" text-anchor="middle" dominant-baseline="central" style="font-size:17px;fill:var(--fg)">'.e($center).'</text>';
        }

        return $svg.'</svg>';
    };
    $legend = fn (array $items) => implode('', array_map(fn ($d) => '<div><i style="background:'.$d[2].'"></i><span'.(! empty($d[3]) ? ' class="me"' : '').'>'.e($d[0]).'</span><span class="v">'.e($d[1]).'</span></div>', $items));
    $keys = fn (array $labels) => implode('', array_map(fn ($t, $i) => '<span><i style="background:'.\App\Services\AiVisibilityPartOne::SERIES[$i % 5].'"></i>'.e($t).'</span>', $labels, array_keys($labels)));

    $niceMax = function ($v) {
        if ($v <= 0) return 1;
        $p = pow(10, floor(log10($v)));
        foreach ([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $m) if ($m * $p >= $v) return $m * $p;
        return 10 * $p;
    };
    // Grouped bars: groups = brands, bars = series. $values[brandKey][seriesIndex].
    $grouped = function (array $groupKeys, array $series, array $values, bool $isPct) use ($B, $niceMax, $int, $pct) {
        $W = 1100; $H = 240; $m = ['t' => 12, 'r' => 8, 'b' => 30, 'l' => 44];
        $all = []; foreach ($groupKeys as $k) foreach ($values[$k] ?? [] as $v) $all[] = $v;
        $max = $niceMax(max($all ?: [0]));
        $y = fn ($v) => $H - $m['b'] - $v / $max * ($H - $m['b'] - $m['t']);
        $svg = '<svg width="100%" viewBox="0 0 '.$W.' '.$H.'" role="img">';
        for ($i = 0; $i <= 4; $i++) {
            $v = $max * $i / 4; $yy = round($y($v), 2);
            $svg .= '<line x1="'.$m['l'].'" x2="'.($W - $m['r']).'" y1="'.$yy.'" y2="'.$yy.'" stroke="var(--grid)"'.($i ? ' stroke-dasharray="3 3"' : '').'/>';
            $svg .= '<text x="'.($m['l'] - 6).'" y="'.$yy.'" text-anchor="end" dominant-baseline="central">'.($isPct ? round($v * 100).'%' : $int($v)).'</text>';
        }
        $n = max(1, count($groupKeys));
        $gw = ($W - $m['l'] - $m['r']) / $n; $inner = $gw * 0.78; $bw = $inner / max(1, count($series));
        foreach (array_values($groupKeys) as $gi => $k) {
            $gx = $m['l'] + $gi * $gw + ($gw - $inner) / 2;
            $isMe = $B[$k]['client'] ?? false;
            $svg .= '<text x="'.round($m['l'] + $gi * $gw + $gw / 2, 2).'" y="'.($H - $m['b'] + 18).'" text-anchor="middle"'.($isMe ? ' style="fill:var(--fg);font-weight:600"' : '').'>'.e($B[$k]['name']).'</text>';
            foreach ($values[$k] ?? [] as $si => $v) {
                $svg .= '<rect data-tip="'.e($B[$k]['name'].' · '.$series[$si].': '.($isPct ? $pct($v) : $int($v))).'" x="'.round($gx + $si * $bw + $bw * 0.06, 2).'" width="'.round($bw * 0.88, 2).'" y="'.round($y($v), 2).'" height="'.round(max(0, $y(0) - $y($v)), 2).'" rx="2" fill="'.\App\Services\AiVisibilityPartOne::SERIES[$si % 5].'"/>';
            }
        }

        return $svg.'</svg>';
    };
    $heat = function (array $rows) use ($pct) {
        $h = '';
        foreach ($rows as [$topic, $vals]) {
            $lead = max(array_map(fn ($v) => $v ?? -1, $vals));
            $h .= '<tr><td>'.e($topic).'</td>';
            foreach ($vals as $v) {
                $h .= $v === null
                    ? '<td>—</td>'
                    : '<td class="'.($v === $lead ? 'top' : '').'" style="background:rgba(217,154,30,'.number_format(0.08 + 0.8 * min(1, $v / 0.5), 2).')">'.$pct($v, 0).'</td>';
            }
            $h .= '</tr>';
        }

        return $h;
    };
    $tip = 'x-data="{t:\'\',x:0,y:0,s:false,mv(ev){const e=ev.target.closest(\'[data-tip]\');if(!e){this.s=false;return;}const r=this.$root.getBoundingClientRect();this.t=e.dataset.tip;this.s=true;this.$nextTick(()=>{this.x=Math.max(0,Math.min(ev.clientX-r.left+12,this.$root.clientWidth-this.$refs.tip.offsetWidth-4));this.y=Math.max(0,ev.clientY-r.top-34);});}}" @pointermove="mv($event)" @pointerleave="s=false"';
    $tipBox = '<div class="av-tip" x-ref="tip" :style="`display:${s?\'block\':\'none\'};left:${x}px;top:${y}px`" x-text="t"></div>';
    $mk = $V['available'] ? $V['market'] : null;
    $marketNote = $mk && $mk['missing'] ? '<p class="av-note">No prompt export yet for '.e(implode(', ', $mk['missing'])).', so '.(count($mk['missing']) > 1 ? 'they are' : 'it is').' not in this chart.</p>' : '';
    $heatHead = '<tr><th>Topic</th>'.collect($av['brands'])->map(fn ($b) => '<th'.($b['client'] ? ' class="me"' : '').'>'.e($b['name']).'</th>')->implode('').'</tr>';
@endphp
<style>
.av {
  --ac: #D99A1E; --ac-soft: rgba(217,154,30,0.14);
  --bg: #1C1B19; --panel: #262522; --fg: #EDEBE4; --muted: #A3A097; --line: #3A3934;
  --ok: #5DB37C; --warn: #E0875C; --grid: #34332E; --bar: #55534C;
  --serif: "Iowan Old Style", "Palatino Linotype", Georgia, serif;
  --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
html[data-theme="light"] .av { --bg: #FAF9F5; --panel: #FFFFFF; --fg: #1F1E1B; --muted: #6B6A64; --line: #E6E3DA; --ok: #3E8E5A; --warn: #B4532A; --grid: #ECE9E0; --bar: #C9C6BC; }
.av, .av * { box-sizing: border-box; }
.av { color: var(--fg); font-family: var(--sans); font-size: 14px; max-width: 1200px; margin: 0 auto; padding: 32px 0 60px; display: flex; flex-direction: column; gap: 28px; }
.av-top { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px; }
.av-title { font-family: var(--serif); font-weight: 400; font-size: 2rem; margin: 0; color: var(--fg); }
.av-sub { color: var(--muted); font-size: 13px; margin: 6px 0 0; }
.av-pill { display: inline-flex; align-items: center; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; padding: 3px 10px; border-radius: 999px; border: 1px solid var(--line); color: var(--muted); white-space: nowrap; }
.av-pill.warn { color: var(--warn); border-color: var(--warn); }
.av-pill.real { color: var(--ok); border-color: var(--ok); }
.av-sec { display: flex; flex-direction: column; gap: 16px; }
.av-sh { font-size: 1.2rem; font-weight: 600; margin: 8px 0 0; color: var(--fg); }
.av-shrow { display: flex; flex-wrap: wrap; align-items: baseline; gap: 10px; }
.av-g4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.av-g2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(320px, 100%), 1fr)); gap: 16px; }
.av-mod { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.av-mt { font-weight: 600; margin: 0; }
.av-ms { font-size: 12px; color: var(--muted); margin: 2px 0 0; }
.av-vol { display: flex; flex-direction: column; gap: 14px; }
.av-vol div { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; border-bottom: 1px solid var(--line); padding-bottom: 10px; }
.av-vol div:last-child { border-bottom: 0; padding-bottom: 0; }
.av-vol .k { font-size: 13px; color: var(--muted); }
.av-vol .k small { display: block; font-size: 11px; }
.av-vol .v { font-family: var(--serif); font-size: 1.7rem; font-variant-numeric: tabular-nums; }
.av-big { font-family: var(--serif); font-size: 2.2rem; font-variant-numeric: tabular-nums; margin: 0; line-height: 1.1; }
.av-bars { display: flex; flex-direction: column; gap: 12px; }
.av-br { display: grid; grid-template-columns: 1fr auto; gap: 4px 10px; align-items: baseline; font-size: 13px; }
.av-br .lb { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; unicode-bidi: plaintext; }
.av-br .vl { font-variant-numeric: tabular-nums; color: var(--muted); }
.av-br .tk { grid-column: 1 / -1; height: 6px; border-radius: 999px; background: var(--line); overflow: hidden; }
.av-br .fl { height: 100%; border-radius: 999px; background: var(--bar); }
.av-br.me .lb { font-weight: 600; }
.av-br.me .vl { color: var(--ac); font-weight: 600; }
.av-br.me .fl, .av-br .fl.ac { background: var(--ac); }
.av-br .fl.ok { background: var(--ok); }
.av-br.sent .tk { height: 10px; }
.av-list { display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
.av-li { display: grid; grid-template-columns: 26px 1fr auto auto; gap: 10px; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 8px; }
.av-li:last-child { border-bottom: 0; }
.av-li .fg { font-size: 18px; line-height: 1; }
.av-li .n { font-variant-numeric: tabular-nums; text-align: right; }
.av-li .p { font-variant-numeric: tabular-nums; color: var(--muted); text-align: right; min-width: 52px; }
.av-chart { position: relative; width: 100%; }
.av-chart svg { display: block; height: auto; }
.av-chart svg text { font-size: 11px; fill: var(--muted); font-family: var(--sans); }
.av-donut { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.av-donut .av-chart { width: 130px; flex: 0 0 130px; min-height: 130px; }
.av-legend { display: flex; flex-direction: column; gap: 7px; font-size: 12px; flex: 1; min-width: 130px; }
.av-legend div { display: grid; grid-template-columns: 10px 1fr auto; gap: 8px; align-items: center; }
.av-legend i { width: 10px; height: 10px; border-radius: 3px; display: block; }
.av-legend .v { font-variant-numeric: tabular-nums; font-weight: 600; }
.av-legend .me { font-weight: 600; }
.av-keys { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 12px; color: var(--muted); }
.av-keys span { display: inline-flex; align-items: center; gap: 6px; }
.av-keys i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.av-tip { position: absolute; pointer-events: none; background: var(--panel); border: 1px solid var(--line); box-shadow: 0 4px 14px rgba(0,0,0,.12); border-radius: 8px; padding: 6px 10px; font-size: 12px; color: var(--fg); white-space: nowrap; z-index: 2; display: none; }
.av-tw { overflow-x: auto; }
.av-wide { min-width: 640px; }
.av-heat { width: 100%; border-collapse: separate; border-spacing: 3px; font-size: 12px; font-variant-numeric: tabular-nums; }
.av-heat th { text-align: center; font-weight: 600; color: var(--muted); padding: 6px; font-size: 11px; }
.av-heat th:first-child { text-align: left; }
.av-heat th.me { color: var(--ac); }
.av-heat td { text-align: center; padding: 8px 6px; border-radius: 6px; }
.av-heat td:first-child { text-align: left; white-space: nowrap; unicode-bidi: plaintext; }
.av-heat td.top { outline: 1.5px solid var(--ac); }
.av-note { font-size: 12px; color: var(--muted); margin: 0; line-height: 1.5; }
.av-pages { display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
.av-pg { display: grid; grid-template-columns: 22px 1fr auto; gap: 8px; align-items: baseline; border-bottom: 1px solid var(--line); padding-bottom: 8px; }
.av-pg:last-child { border-bottom: 0; }
.av-pg .r { color: var(--muted); font-variant-numeric: tabular-nums; }
.av-pg .u { unicode-bidi: plaintext; word-break: break-all; }
.av-pg .c { font-variant-numeric: tabular-nums; color: var(--muted); white-space: nowrap; }
.av-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); border: 1px solid var(--line); border-radius: 12px; background: var(--panel); overflow: hidden; }
.av-kpi { padding: 14px 20px; border-right: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.av-kpi:last-child { border-right: 0; }
.av-kl { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); margin: 0; }
.av-kv { font-size: 1.6rem; font-variant-numeric: tabular-nums; margin: 0; line-height: 1.15; }
.av-kn { font-size: 11px; color: var(--muted); margin: 0; }
.av-mini { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.av-mini .n { font-size: 1.3rem; font-variant-numeric: tabular-nums; margin: 2px 0 0; }
.av-mini .n.w { color: var(--warn); }
.av-status { display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
.av-status > div { display: grid; grid-template-columns: minmax(120px, 1fr) minmax(0, 1.4fr) minmax(0, 1.8fr); gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 8px; }
.av-status > div:last-child { border-bottom: 0; padding-bottom: 0; }
.av-status > div.hd { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
@media (max-width: 640px) { .av-status > div { grid-template-columns: 1fr; gap: 4px; } .av-status > div.hd { display: none; } .av-pill { white-space: normal; } }
.av-status .ok { color: var(--ok); } .av-status .part { color: var(--ac); } .av-status .no { color: var(--muted); }
.av-status small { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }
.av-stack { display: flex; flex-wrap: wrap; gap: 4px; align-content: flex-start; }
.av-stack b { font-weight: 400; font-size: 11px; padding: 1px 7px; border-radius: 999px; border: 1px solid var(--line); white-space: nowrap; }
.av-stack b.next { border-style: dashed; color: var(--muted); }
.av-pipe { font-size: 12px; color: var(--muted); margin: 0; line-height: 1.6; }
.av-pipe b { color: var(--fg); font-weight: 600; }
.av details { border: 1px solid var(--line); border-radius: 12px; padding: 14px 20px; background: var(--panel); }
.av summary { cursor: pointer; font-weight: 600; }
.av details ul { margin: 12px 0 0; padding-left: 1.2em; line-height: 1.6; font-size: 13px; list-style: disc; }
@media print { .av-mod, .av-kpis, .av details { break-inside: avoid; } }
</style>
<div class="av" data-testid="ai-visibility-report">
  <header class="av-top">
    <div>
      <h1 class="av-title">AI Visibility Report · {{ $client }}</h1>
      <p class="av-sub">Part 1: AI visibility, market and competitors · {{ $av['monthLabel'] }}@if($V['available']) · {{ number_format($V['nonBrandedPrompts']) }} non-branded prompts, {{ $V['models'] }} AI models, Semrush capture {{ $V['captured'] }}@endif</p>
    </div>
    <span class="av-pill {{ $V['available'] && $T['available'] ? 'real' : 'warn' }}">Visibility sections: {{ $V['available'] ? 'Semrush '.$V['captured'] : 'no capture' }} · Traffic: {{ $T['available'] ? 'GA4 live sync' : 'no data' }}</span>
  </header>

  <section class="av-sec" id="overview">
    <h2 class="av-sh">Overview</h2>
    <div class="av-g4">
      <div class="av-mod">
        <div><p class="av-mt">Volume metrics</p><p class="av-ms">{{ $client }}, all models</p></div>
        <div class="av-vol">
          <div><span class="k">Impressions<small>Estimated</small></span><span class="v">{{ $V['available'] ? $int($V['volume']['imp']) : '—' }}</span></div>
          <div><span class="k">Mentions<small>Answers naming {{ $client }}</small></span><span class="v">{{ $V['available'] ? $int($V['volume']['men']) : '—' }}</span></div>
          <div><span class="k">Citations<small>Links to {{ $client }} pages</small></span><span class="v">{{ $V['available'] ? $int($V['volume']['cit']) : '—' }}</span></div>
        </div>
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Share of voice</p><p class="av-ms">Mentions + citations, tracked brands</p></div>
        @if($V['available'] && $V['sov'])
          @php $meSov = collect($V['sov'])->firstWhere('key', 'b0'); @endphp
          <div class="av-donut"><div class="av-chart" {!! $tip !!}>{!! $donut(array_map(fn ($r) => [$B[$r['key']]['name'], $r['sov'], $B[$r['key']]['color'], $B[$r['key']]['name'].': '.$pct($r['sov'])], $V['sov']), true, $meSov ? $pct($meSov['sov']) : null) !!}{!! $tipBox !!}</div>
            <div class="av-legend">{!! $legend(array_map(fn ($r) => [$B[$r['key']]['name'], $pct($r['sov']), $B[$r['key']]['color'], $B[$r['key']]['client']], $V['sov'])) !!}</div></div>
        @else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Sentiment</p><p class="av-ms">Share of {{ $client }} mentions that are positive</p></div>
        @if($TR['sentiment'])
          <p class="av-big">{{ $pct($TR['sentiment']['s'], 0) }}</p>
          <div class="av-bars">{!! $bars([['Positive', $TR['sentiment']['s']]], fn () => $int($TR['sentiment']['pos']).' of '.$int($TR['sentiment']['men']), ['abs' => true, 'fill' => 'ok', 'cls' => 'sent']) !!}</div>
          <p class="av-note">Green is positive. The grey remainder is neutral or negative.</p>
        @else
          <p class="av-big">—</p>
          <p class="av-note">No sentiment for {{ $av['monthLabel'] }} yet. Semrush exports carry no sentiment; it comes from the weekly prompt tracker's answers, and none are stored for this month.</p>
        @endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Top 5 topics</p><p class="av-ms">{{ $client }} citations + mentions</p></div>
        @if($V['available'] && $V['topics'])<div class="av-bars">{!! $bars($V['topics'], $int, ['fill' => 'ac']) !!}</div>@else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Mentions by country</p><p class="av-ms">Top 5, share of {{ $client }} mentions</p></div>
        @if($V['available'] && $V['countries'])
          <div class="av-list">@foreach($V['countries'] as $c)<div class="av-li"><span class="fg">{{ $flag($c[0]) }}</span><span>{{ $c[1] }}</span><span class="n">{{ $int($c[2]) }}</span><span class="p">{{ $pct($c[3]) }}</span></div>@endforeach</div>
        @else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Citations by country</p><p class="av-ms">Same top 5, share of {{ $client }} citations</p></div>
        @if($V['available'] && $V['countries'])
          <div class="av-list">@foreach($V['countries'] as $c)<div class="av-li"><span class="fg">{{ $flag($c[0]) }}</span><span>{{ $c[1] }}</span><span class="n">{{ $int($c[4]) }}</span><span class="p">{{ $pct($c[5]) }}</span></div>@endforeach</div>
        @else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Mentions by LLM</p><p class="av-ms">Where {{ $client }} gets named</p></div>
        @if($V['available'] && $V['llm'])
          <div class="av-donut"><div class="av-chart" {!! $tip !!}>{!! $donut(array_map(fn ($r, $i) => [$r[0], $r[1], \App\Services\AiVisibilityPartOne::SERIES[$i % 5], $r[0].': '.$pct($r[1])], $V['llm'], array_keys($V['llm'])), true) !!}{!! $tipBox !!}</div>
            <div class="av-legend">{!! $legend(array_map(fn ($r, $i) => [$r[0], $pct($r[1]), \App\Services\AiVisibilityPartOne::SERIES[$i % 5]], $V['llm'], array_keys($V['llm']))) !!}</div></div>
        @else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Citations per LLM</p><p class="av-ms">Where {{ $client }} pages get linked</p></div>
        @if($V['available'] && $V['llm'])
          <div class="av-donut"><div class="av-chart" {!! $tip !!}>{!! $donut(array_map(fn ($r, $i) => [$r[0], $r[2], \App\Services\AiVisibilityPartOne::SERIES[$i % 5], $r[0].': '.$pct($r[2])], $V['llm'], array_keys($V['llm'])), false) !!}{!! $tipBox !!}</div>
            <div class="av-legend">{!! $legend(array_map(fn ($r, $i) => [$r[0], $pct($r[2]), \App\Services\AiVisibilityPartOne::SERIES[$i % 5]], $V['llm'], array_keys($V['llm']))) !!}</div></div>
        @else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
    </div>
  </section>

  <section class="av-sec" id="market">
    <h2 class="av-sh">Market and competitors</h2>
    <div class="av-g2">
      <div class="av-mod">
        <div><p class="av-mt">By mentions</p><p class="av-ms">Each brand's share of brand mentions, non-branded prompts only</p></div>
        @if($mk)<div class="av-bars">{!! $bars(array_map(fn ($r) => [$B[$r[0]]['name'], $r[1], $r[0]], $mk['bm']), $pct, ['me' => fn ($r) => $r[2] === 'b0']) !!}</div>{!! $marketNote !!}@else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">By citations</p><p class="av-ms">Each brand's share of brand citations, non-branded prompts only</p></div>
        @if($mk)<div class="av-bars">{!! $bars(array_map(fn ($r) => [$B[$r[0]]['name'], $r[1], $r[0]], $mk['bc']), $pct, ['me' => fn ($r) => $r[2] === 'b0']) !!}</div>{!! $marketNote !!}@else<p class="av-note">{{ $noSem }}</p>@endif
      </div>
    </div>
    @php $cNames = $mk ? array_map(fn ($c) => $flag($c[0]).' '.$c[1], $mk['countries']) : []; @endphp
    <div class="av-mod">
      <div><p class="av-mt">Top countries by mentions</p><p class="av-ms">Each brand's mentions in the 5 countries with the most mentions overall</p></div>
      @if($mk && $mk['countries'])
        <div class="av-keys">{!! $keys($cNames) !!}</div>
        <div class="av-tw"><div class="av-chart av-wide" {!! $tip !!}>{!! $grouped($mk['brands'], $cNames, $mk['cbm'], false) !!}{!! $tipBox !!}</div></div>{!! $marketNote !!}
      @else<p class="av-note">{{ $noSem }}</p>@endif
    </div>
    <div class="av-mod">
      <div><p class="av-mt">Top countries by citations</p><p class="av-ms">Each brand's citations in the same 5 countries</p></div>
      @if($mk && $mk['countries'])
        <div class="av-keys">{!! $keys($cNames) !!}</div>
        <div class="av-tw"><div class="av-chart av-wide" {!! $tip !!}>{!! $grouped($mk['brands'], $cNames, $mk['cbc'], false) !!}{!! $tipBox !!}</div></div>{!! $marketNote !!}
      @else<p class="av-note">{{ $noSem }}</p>@endif
    </div>
  </section>

  <section class="av-sec" id="topics">
    <h2 class="av-sh">Topics</h2>
    <div class="av-mod">
      <div><p class="av-mt">Top topics by mention</p><p class="av-ms">Each brand's share of the mentions in that topic. Outlined cell = topic leader</p></div>
      @if($V['available'] && $V['heatM'])<div class="av-tw"><table class="av-heat"><thead>{!! $heatHead !!}</thead><tbody>{!! $heat($V['heatM']) !!}</tbody></table></div>@if($mk['missing'])<p class="av-note">— = no prompt export for that brand.</p>@endif
      @else<p class="av-note">{{ $noSem }}</p>@endif
    </div>
    <div class="av-mod">
      <div><p class="av-mt">Top topics by citation</p><p class="av-ms">Each brand's share of the citations in that topic. Outlined cell = topic leader</p></div>
      @if($V['available'] && $V['heatC'])<div class="av-tw"><table class="av-heat"><thead>{!! $heatHead !!}</thead><tbody>{!! $heat($V['heatC']) !!}</tbody></table></div>@if($mk['missing'])<p class="av-note">— = no prompt export for that brand.</p>@endif
      @else<p class="av-note">{{ $noSem }}</p>@endif
    </div>
  </section>

  <section class="av-sec" id="competitors">
    <h2 class="av-sh">Competitors</h2>
    <div class="av-mod">
      <div><p class="av-mt">Competitor sentiment</p><p class="av-ms">Share of each brand's mentions that are positive. Grey = neutral or negative</p></div>
      <p class="av-note">No competitor sentiment for {{ $av['monthLabel'] }} yet. It needs the full answer text, which comes from the weekly prompt tracker; Semrush exports only carry a short excerpt and no sentiment.</p>
    </div>
    <div class="av-mod">
      <div><p class="av-mt">Share of voice by LLM</p><p class="av-ms">Each brand's share of voice inside each model</p></div>
      @if($V['available'] && $V['sovLlm'])
        @php $llmNames = array_keys($V['sovLlm']); $sovByBrand = collect($mk['brands'])->mapWithKeys(fn ($k) => [$k => array_map(fn ($l) => $V['sovLlm'][$l][$k] ?? 0, $llmNames)])->all(); @endphp
        <div class="av-keys">{!! $keys($llmNames) !!}</div>
        <div class="av-tw"><div class="av-chart av-wide" {!! $tip !!}>{!! $grouped($mk['brands'], $llmNames, $sovByBrand, true) !!}{!! $tipBox !!}</div></div>{!! $marketNote !!}
      @else<p class="av-note">{{ $noSem }}</p>@endif
    </div>
    <div class="av-g2">
      <div class="av-mod">
        <div><p class="av-mt">Top 5 pages cited for {{ $client }}</p><p class="av-ms">Own-domain pages Copilot links to (Clarity) · citations · share of brand</p></div>
        @if($T['pages'])
          <div class="av-pages">@foreach($T['pages'] as $i => $p)<div class="av-pg"><span class="r">{{ $i + 1 }}</span><span class="u">{{ $p[0] }}</span><span class="c">{{ $int($p[1]) }} · {{ $pct($p[2], 0) }}</span></div>@endforeach</div>
        @else<p class="av-note">No Clarity export for {{ $av['monthLabel'] }}{{ $T['clarityLabel'] ? ' (the latest is '.$T['clarityLabel'].')' : '' }}.</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">Top domains cited on non-branded topics</p><p class="av-ms">Third-party sources, share of all non-brand citations</p></div>
        @if($TR['domains'])<div class="av-bars">{!! $bars($TR['domains'], $pct) !!}</div>
        @else<p class="av-note">No source list for {{ $av['monthLabel'] }} yet. Semrush exports only count sources; the domains come from the weekly prompt tracker's answers.</p>@endif
      </div>
    </div>
  </section>

  <section class="av-sec" id="traffic">
    <div class="av-shrow"><h2 class="av-sh">AI traffic and behaviour</h2>@if($T['available'])<span class="av-pill real">Real data · GA4{{ $T['clarity'] ? ' and Clarity' : '' }} · {{ $av['shortMonth'] }}</span>@else<span class="av-pill warn">No GA4 data for {{ $av['shortMonth'] }}</span>@endif</div>
    <div class="av-kpis">
      <div class="av-kpi"><p class="av-kl">AI sessions</p><p class="av-kv">{{ $int($T['sessions']) }}</p><p class="av-kn">GA4 · floor</p></div>
      <div class="av-kpi"><p class="av-kl">Share of site</p><p class="av-kv">{{ $pct($T['share'], 2) }}</p><p class="av-kn">of all sessions</p></div>
      <div class="av-kpi"><p class="av-kl">Conversion vs site</p><p class="av-kv">{{ $T['lift'] !== null ? number_format($T['lift'], 1).'×' : '—' }}</p><p class="av-kn">{{ $pct($T['aiRate']) }} vs {{ $pct($T['siteRate']) }} site</p></div>
      <div class="av-kpi"><p class="av-kl">AI revenue</p><p class="av-kv">{{ $T['revenue'] !== null ? 'SAR '.$int($T['revenue']) : '—' }}</p><p class="av-kn">internal only</p></div>
      <div class="av-kpi"><p class="av-kl">New users from AI</p><p class="av-kv">{{ $int($T['newUsers']) }}</p><p class="av-kn">GA4 first-user channel</p></div>
      <div class="av-kpi"><p class="av-kl">AI referrals</p><p class="av-kv">{{ $int($T['referrals']) }}</p><p class="av-kn">{{ $T['clarity'] ? 'Clarity · never added to GA4' : 'Clarity · not captured' }}</p></div>
    </div>
    <div class="av-g2">
      <div class="av-mod">
        <div><p class="av-mt">Sessions by assistant</p><p class="av-ms">Where AI visits came from</p></div>
        @if($T['assistants'])
          <div class="av-bars">{!! $bars($T['assistants'], $int, ['fill' => 'ac', 'me' => fn ($r) => $r[0] === $T['assistants'][0][0]]) !!}</div>
          @if($T['silent'])<p class="av-note">{{ count($T['silent']) > 1 ? implode(', ', array_slice($T['silent'], 0, -1)).' and '.last($T['silent']) : $T['silent'][0] }} sent nothing GA4 could recognise this month.</p>@endif
        @else<p class="av-note">GA4 recorded no AI assistant sessions for {{ $av['monthLabel'] }}.</p>@endif
      </div>
      <div class="av-mod">
        <div><p class="av-mt">On-site behaviour</p><p class="av-ms">Clarity, AI visitors only</p></div>
        @if($T['behaviour'])
          @php $bh = $T['behaviour']; @endphp
          <div class="av-mini">
            <div><p class="av-kl">Sessions</p><p class="n">{{ $int($bh['sessions']) }}</p></div>
            <div><p class="av-kl">Scroll depth</p><p class="n">{{ $pct($bh['scroll']) }}</p></div>
            <div><p class="av-kl">Dead clicks</p><p class="n w">{{ $pct($bh['dead']) }}</p></div>
            <div><p class="av-kl">Quick backs</p><p class="n w">{{ $pct($bh['quick']) }}</p></div>
          </div>
          <p class="av-note">Whole site: {{ $int($bh['site']['sessions']) }} sessions, {{ $pct($bh['site']['scroll']) }} scroll depth, {{ $pct($bh['site']['dead']) }} dead clicks, {{ $pct($bh['site']['quick']) }} quick backs.</p>
        @else<p class="av-note">No Clarity export for {{ $av['monthLabel'] }}{{ $T['clarityLabel'] ? ' (the latest is '.$T['clarityLabel'].')' : '' }}.</p>@endif
      </div>
    </div>
    <div class="av-mod">
      <div><p class="av-mt">AI visitors against other channels</p><p class="av-ms">Left: key events per session. Right: revenue per session (SAR)</p></div>
      @if($T['kev'])
        <div class="av-mini"><div class="av-bars">{!! $bars($T['kev'], $pct, ['me' => fn ($r) => $r[0] === 'AI assistants']) !!}</div><div class="av-bars">{!! $bars($T['rps'], fn ($v) => number_format($v, 2), ['me' => fn ($r) => $r[0] === 'AI assistants']) !!}</div></div>
        <p class="av-note">@if($T['aiLeads'] && ($T['keyEvents'] ?? 0) < 30)Only {{ $int($T['keyEvents']) }} key events came from AI, so treat the lead as early. @endif AI traffic is a floor: Google AI Overviews clicks arrive as Google organic.</p>
      @else<p class="av-note">No GA4 channel data for {{ $av['monthLabel'] }}.</p>@endif
    </div>
  </section>

  <section class="av-sec" id="coverage">
    <div class="av-mod">
      <div><p class="av-mt">Data coverage and tech stack</p><p class="av-ms">What each section runs on today, what feeds it, and what replaces it. Solid tag = in use now, dashed = planned</p></div>
      <div class="av-status">
        <div class="hd"><span>Section</span><span>Data today</span><span>Tech stack</span></div>
        @foreach($av['coverage'] as [$sec, $what, $cls, $status, $detail, $stack])
          <div><span>{{ $sec }}@if($what)<small>{{ $what }}</small>@endif</span><span class="{{ $cls }}">{{ $status }}<small>{{ $detail }}</small></span><span class="av-stack">@foreach($stack as [$tag, $live])<b @class(['next' => ! $live])>{{ $tag }}</b>@endforeach</span></div>
        @endforeach
      </div>
      <p class="av-pipe"><b>Pipeline for every section:</b> source export or connector → upload or live sync in Data &amp; settings → stored in the <b>MySQL</b> database (Semrush reports, monthly GA4 rows, workbook tables, prompt tracker answers) → <b>AiVisibilityPartOne</b> works out every metric once → this report.</p>
    </div>
  </section>

  <details>
    <summary>Definitions and method</summary>
    <ul>
      <li><b>The visibility sections come from Semrush AI Visibility</b>: the brand topics and prompts exports uploaded in Data &amp; settings{{ $V['available'] ? ', captured '.$V['captured'] : '' }}. Competitors without a prompt export appear only in share of voice.</li>
      <li><b>Mention</b>: one answer (one prompt, one model, one country) that names the brand. <b>Citation</b>: a link to a page on the brand's own domain inside an answer.</li>
      <li><b>Share of voice</b>: the brand's mentions plus citations, divided by the mentions plus citations of all tracked brands with a Semrush summary.</li>
      <li><b>Sentiment</b>: positive mentions divided by all mentions of that brand, from the weekly prompt tracker's answers.</li>
      <li><b>Impressions (estimated)</b>: for each Semrush topic, its monthly demand times the share of answers that named {{ $client }}, summed. Observed impressions exist only for Google AI Overviews and AI Mode (Search Console).</li>
      <li><b>Country</b>: the location the prompt was run from, not where a reader sits. The report lists the top 5 by mentions.</li>
      <li><b>AI traffic and behaviour is real data</b> from {{ $client }}'s GA4 (live sync) and the Clarity export filtered to AI visitors. AI sessions are a floor: Google AI Overviews clicks arrive as Google organic, and some assistants send no referrer. Top cited pages come from Clarity, which reports Copilot citations.</li>
      <li>Topics are Semrush's topic names. Every market number counts only non-branded prompts (no tracked brand name, in any spelling, in the prompt or its topic): each brand's mentions and citations are counted inside those answers.</li>
    </ul>
  </details>
</div>
