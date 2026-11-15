{{-- Competitors --}}
@php $cmp = $aiReport['competitors']; @endphp
<section aria-labelledby="air-h-cmp">
    <div class="air-sec-h"><h2 id="air-h-cmp"><x-heroicon-o-user-group />Share of voice and AI score <x-ai-help :h="$help['competitors']" /></h2><p>Simulated: Semrush runs a prompt set for each tracked brand</p></div>
    @if($cmp['available'])
        <div class="air-grid air-g-land">
            <div class="card air-card">
                <h3>AI share of voice <x-ai-help :h="$help['sov']" /></h3>
                <div class="air-sub">Share of brand mentions across the tracked brands</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cmp['src']])
                @include('livewire.pages.client-dashboard.ai-pie', ['rows' => $cmp['sov'], 'donut' => true, 'center' => $cmp['share'] !== null ? number_format($cmp['share'], 1).'%' : '—', 'centerSub' => 'our share', 'firstIsClient' => true, 'aria' => 'AI share of voice by brand'])
            </div>
            <div class="card air-card">
                <h3>AI visibility score by brand <x-ai-help :h="$help['cmpscore']" /></h3>
                <div class="air-sub">Semrush AI Visibility score out of 100, highest first</div>
                @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cmp['src']])
                @include('livewire.pages.client-dashboard.ai-hbars', [
                    'bars' => collect($cmp['scores'])->map(fn($r) => ['l' => $r['name'], 's' => $r['is_client'] ? 'You' : $r['domain'], 'v' => $r['score'], 'c' => $r['is_client'] ? '' : 'org'])->all(),
                    'barMax' => 100,
                ])
                @if($cmp['summary'])<p class="air-fsum" style="margin-top:12px">{!! $cmp['summary'] !!}</p>@endif
                <details class="air-details"><summary>Show as table</summary>
                    <div class="air-tbl" style="margin-top:8px"><table class="data-table"><thead><tr><th>Brand</th><th>AI score</th><th>Mentions</th><th>Citations</th><th>Cited pages</th><th>Prompts</th></tr></thead><tbody>
                        @foreach($cmp['rows'] as $r)
                            <tr @if($r['is_client']) style="font-weight:600" @endif><td>{{ $r['name'] }}@if($r['is_client']) <span class="text-xs" style="color:var(--ai-muted)">You</span>@endif</td>
                            @foreach(['score', 'mentions', 'citations', 'cited_pages', 'prompts'] as $k)<td class="tabular-nums">{{ $r[$k] !== null ? number_format($r[$k]) : '—' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody></table></div>
                </details>
            </div>
        </div>
        <div class="air-caveat">@if($cmp['note']){{ $cmp['note'] }} @endif Each brand has its own Semrush prompt set, so read shares as a direction. Add or edit brands in Competitor comparison below.</div>
    @else
        <div class="air-empty"><b>No competitor data yet.</b> Add competitors in Competitor comparison below and upload a Semrush brand summary for each one.</div>
    @endif
</section>
