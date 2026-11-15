{{-- Competitive watch --}}
<section aria-labelledby="air-d4">
    <div class="air-sec-h"><h2 id="air-d4"><x-heroicon-o-eye />Topic gaps and tone <x-ai-help :h="$help['competitive']" /></h2><p>Simulated · Semrush prompt set and topics</p></div>
    <div class="air-grid air-g-2">
        <div class="card air-card">
            <h3>How AI talks about {{ $brand }} <x-ai-help :h="$help['sentiment']" /></h3>
            <div class="air-sub">Tone of answers that name the brand, by assistant</div>
            @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => 'Semrush prompts, scored by the analyst', 'flag' => 'Not scored'])
            <div class="air-legend"><span><i style="background:var(--air-good)"></i>Positive</span><span><i style="background:var(--air-g)"></i>Neutral</span><span><i style="background:var(--air-bad)"></i>Cautious</span></div>
            <div class="air-empty"><b>Not scored for this month.</b> The analyst scores the Semrush answers that name the brand as positive, neutral or cautious. Once that score sheet is in the workbook, this chart shows the tone by assistant.</div>
        </div>
        <div class="card air-card">
            <h3>Topic gap watch list <x-ai-help :h="$help['gaps']" /></h3>
            <div class="air-sub">High-demand topics where AI never mentions {{ $brand }}</div>
            @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'sim', 'stage' => 'Pre-click', 'src' => $cw['src']])
            @if($cw['captured'] && $cw['gaps']['rows'])
                <div class="air-tbl"><table class="data-table">
                    <thead><tr><th>Topic</th><th>Market</th><th>Monthly searches</th></tr></thead>
                    <tbody>
                    @foreach($cw['gaps']['rows'] as $g)
                        <tr><td class="wrap"><span class="air-ar" dir="auto">{{ $g['topic'] }}</span></td><td>{{ $g['market'] }}</td><td>{{ number_format($g['volume'] ?? 0) }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
                <p class="air-src" style="margin:0">{{ $cw['gaps']['zero'] }} of {{ $cw['gaps']['tracked'] }} tracked topics have 0 visibility · {{ number_format($cw['gaps']['volume']) }} monthly searches</p>
            @elseif($cw['captured'])
                <div class="air-empty">No zero-visibility topics in the latest Semrush capture.</div>
            @else
                {!! $notCaptured !!}
            @endif
        </div>
    </div>
</section>
