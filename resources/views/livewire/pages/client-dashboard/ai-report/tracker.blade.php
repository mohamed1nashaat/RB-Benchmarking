{{-- Live prompt tracking: our own weekly runs on ChatGPT, Gemini and Perplexity, plus an optional simulated open model --}}
@php
    $tk = $aiTracker ?? null;
    $tkHelp = ['AI prompt tracking', 'Our own weekly run: each tracked prompt is asked to ChatGPT, Gemini and Perplexity through their APIs with live web search. Each answer is checked for the brand and every competitor in the Competitors tab. An open model on Ollama can also be asked, using Ollama web search. It is not one of the assistants shoppers use, so its answers are shown separately as simulated and are not counted in the headline numbers.', 'This is the one AI visibility number we measure ourselves every week, for every assistant, with the same prompts each time, so week-on-week changes are real changes in the answers.'];
    $tkPct = fn ($v) => $v === null ? '—' : number_format($v, 0).'%';
    $tkDelta = function ($d, bool $lowerIsBetter = false, string $unit = ' pts') {
        if ($d === null) return null;
        $good = $lowerIsBetter ? $d < 0 : $d > 0;
        return ['text' => ($d >= 0 ? '+' : '−').number_format(abs($d), $unit === '' ? 1 : 0).$unit, 'good' => abs($d) < 0.05 ? null : $good];
    };
@endphp
@if($tk)
<section aria-labelledby="air-trk" data-testid="ai-tracker">
    <div class="air-sec-h"><h2 id="air-trk"><x-heroicon-o-magnifying-glass />AI prompt tracking <x-ai-help :h="$tkHelp" /></h2><p>{{ $tk['available'] ? $tk['weekLabel'].' · '.$tk['promptCount'].' prompts · '.implode(', ', $tk['engineLabels']) : 'ChatGPT, Gemini and Perplexity, asked every week' }}</p></div>

    @if(!$tk['available'])
        <div class="card air-card">
            <div class="air-empty">
                @if(!$tk['setup']['engines'])
                    <b>Waiting for API keys.</b> Tracking is set up with {{ $tk['setup']['activePrompts'] }} prompts. It starts on the next Monday run once an OpenAI, Google Gemini or Perplexity API key is added on the server.
                @elseif(!$tk['setup']['activePrompts'])
                    <b>No prompts yet.</b> Add prompts in Data &amp; settings → AI prompt tracking.
                @else
                    <b>First run pending.</b> {{ $tk['setup']['activePrompts'] }} prompts will be asked on the next Monday run.
                @endif
            </div>
        </div>
    @else
        @php $k = $tk['kpis']; $d = $tk['deltas']; @endphp
        <div class="air-grid air-g-3" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
            @foreach([
                ['Visibility', $tkPct($k['visibility']), 'of answers name the brand', $tkDelta($d['visibility'])],
                ['Cited', $tkPct($k['citation']), 'of answers link to the site', $tkDelta($d['citation'])],
                ['Average position', $k['rank'] ? '#'.number_format($k['rank'], 1) : '—', 'when the brand is named', $tkDelta($d['rank'], true, '')],
                ['Share of voice', $tkPct($k['sov']), 'of brand mentions among tracked brands', null],
            ] as [$lbl, $val, $sub, $dl])
                <div class="card air-card">
                    <div class="kpi-label">{{ $lbl }}</div>
                    <div class="air-big" style="margin-top:6px">{{ $val }}</div>
                    <p class="air-src" style="margin:4px 0 0">{{ $sub }}</p>
                    @if($dl)<p style="margin:6px 0 0;font-size:13px;color:{{ $dl['good'] === null ? 'var(--ai-muted)' : ($dl['good'] ? 'var(--air-good)' : 'var(--air-bad)') }}">{{ $dl['text'] }} vs week of {{ $tk['prevLabel'] }}</p>@endif
                </div>
            @endforeach
        </div>
        <p class="air-src" style="margin:8px 0 0">
            @if($tk['basis'] === 'simulated')
                Simulated · {{ implode(', ', $tk['simulatedLabels']) }} only, not ChatGPT, Gemini or Perplexity · {{ number_format($tk['answers']) }} answers · last asked {{ $tk['lastRun'] }}
            @else
                Measured · our weekly run · {{ number_format($tk['answers']) }} answers · last asked {{ $tk['lastRun'] }}
                @if($tk['simulatedLabels']) · {{ implode(', ', $tk['simulatedLabels']) }} is simulated and not counted here @endif
            @endif
        </p>

        <div class="air-grid air-g-2" style="margin-top:14px">
            <div class="card air-card">
                <h3>By assistant</h3>
                <div class="air-sub">Visibility, citations and position in each assistant's answers</div>
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Assistant</th><th>Visibility</th><th>Cited</th><th>Avg position</th><th>Answers</th></tr></thead>
                    <tbody>
                    @foreach($tk['engines'] as $e)
                        <tr><td>{{ $e['engine'] }}@if($e['simulated'])<span class="air-tag"> · simulated</span>@endif</td><td><b>{{ $tkPct($e['visibility']) }}</b></td><td>{{ $tkPct($e['citation']) }}</td><td>{{ $e['rank'] ? '#'.number_format($e['rank'], 1) : '—' }}</td><td>{{ number_format($e['answers']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div>
            <div class="card air-card">
                <h3>Visibility by week</h3>
                <div class="air-sub">Share of answers that name the brand, all assistants</div>
                @if(count($tk['trend']) > 1)
                    @include('livewire.pages.client-dashboard.ai-monthbars', ['mbars' => $tk['trend'], 'aria' => 'Visibility by week', 'mbarCur' => 'This week', 'mbarPrev' => 'Earlier weeks'])
                @else
                    <div class="air-empty">The trend starts with next week's run.</div>
                @endif
            </div>
        </div>

        <div class="air-grid air-g-2" style="margin-top:14px">
            <div class="card air-card">
                <h3>Share of voice</h3>
                <div class="air-sub">Mentions of each tracked brand across this week's answers</div>
                @if($tk['sovPie'])
                    @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $tk['sovPie'], 'donut' => true, 'center' => $tkPct($k['sov']), 'centerSub' => 'share of voice', 'aria' => 'Share of voice'])
                @else
                    <div class="air-empty">No tracked brand was named this week.</div>
                @endif
                <details style="margin-top:8px"><summary class="air-src" style="cursor:pointer">Show as table</summary>
                    <div class="air-tbl"><table class="data-table">
                        <thead><tr><th>Brand</th><th>Named in</th><th>Named first</th><th>Avg position</th><th>Site cited</th></tr></thead>
                        <tbody>
                        @foreach($tk['brands'] as $b)
                            <tr><td>{!! $b['client'] ? '<b>'.e($b['name']).'</b>' : e($b['name']) !!}</td><td>{{ $tkPct($b['visibility']) }}</td><td>{{ number_format($b['first']) }}</td><td>{{ $b['avgRank'] ? '#'.number_format($b['avgRank'], 1) : '—' }}</td><td>{{ number_format($b['cited']) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </details>
            </div>
            <div class="card air-card">
                <h3>Sites the assistants cite</h3>
                <div class="air-sub">How many answers linked to each site this week. Sites that keep coming up are where to get listed or reviewed.</div>
                @if($tk['sources'])
                    @include('livewire.pages.client-dashboard.ai-hbars', ['bars' => collect($tk['sources'])->map(fn ($s) => $s + ['c' => $s['own'] ? '' : 'org'])->all(), 'barFmt' => 'int'])
                @else
                    <div class="air-empty">No sources were returned this week.</div>
                @endif
            </div>
        </div>

        @if($tk['gaps'])
            <div class="card air-card" style="margin-top:14px">
                <h3>Prompts where only competitors are named</h3>
                <div class="air-sub">No assistant named the brand, but at least one named a competitor. These are the first questions to win.</div>
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Prompt</th><th>Competitors named</th><th>Volume</th></tr></thead>
                    <tbody>
                    @foreach(array_slice($tk['gaps'], 0, 10) as $g)
                        <tr><td class="wrap"><span dir="auto">{{ $g['prompt'] }}</span></td><td class="wrap">{{ implode(', ', $g['rivals']) }}</td><td>{{ $g['volume'] ? number_format($g['volume']) : '—' }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif

        <details class="card air-card" style="margin-top:14px">
            <summary style="cursor:pointer;font-weight:600;color:var(--ai-text)">Every prompt and each assistant's answer ({{ count($tk['rows']) }})</summary>
            <div class="air-tbl" style="margin-top:10px"><table class="data-table">
                <thead><tr><th>Prompt</th>@foreach($tk['engineLabels'] as $ek => $lbl)<th>{{ $lbl }}{{ \App\Services\AiTracking\AiEngineClient::simulated($ek) ? ' · simulated' : '' }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($tk['rows'] as $r)
                    <tr>
                        <td class="wrap"><span dir="auto">{{ $r['prompt'] }}</span>@if($r['country'])<span class="air-tag"> · {{ strtoupper($r['country']) }}</span>@endif</td>
                        @foreach($tk['engineKeys'] as $e)
                            @php $c = $r['cells'][$e] ?? null; @endphp
                            <td style="white-space:nowrap">
                                @if(!$c) —
                                @elseif($c['mentioned'])<b style="color:var(--air-good)">#{{ $c['rank'] }}</b>{{ $c['cited'] ? ' · cited' : '' }}{{ $c['sentiment'] ? ' · '.$c['sentiment'] : '' }}
                                @elseif($c['cited'])<span>Cited only</span>
                                @else<span style="color:var(--ai-muted)">Not named</span>@endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </details>
    @endif
</section>
@endif
