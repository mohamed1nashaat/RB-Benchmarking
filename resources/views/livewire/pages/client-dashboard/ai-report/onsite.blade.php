{{-- On-site experience --}}
<section aria-labelledby="air-d3">
    <div class="air-sec-h"><h2 id="air-d3"><x-heroicon-o-cursor-arrow-rays />On-site experience <x-ai-help :h="$help['onsite']" /></h2><p>Observed · Clarity, AI visitors vs the whole site, last 30 days</p></div>
    <div class="card air-card">
        <h3>Friction on AI landing pages <x-ai-help :h="$help['friction']" /></h3>
        <div class="air-sub">What happens after someone arrives from an AI answer</div>
        @include('livewire.pages.client-dashboard.ai-chips', ['type' => 'obs', 'stage' => 'Post-click', 'src' => $fr['src']])
        @if($fr['captured'] && $fr['rows'])
            <div class="air-tbl"><table class="data-table">
                <thead><tr><th>Metric (share of sessions)</th><th>AI visitors</th><th>Whole site</th><th>Read</th></tr></thead>
                <tbody>
                @foreach($fr['rows'] as $r)
                    <tr>
                        <td>{{ $r['l'] }}</td>
                        <td>{{ $airFmt($r['a'], 'fr_'.$r['fmt']) }}</td>
                        <td>{{ $r['s'] !== null ? $airFmt($r['s'], 'fr_'.$r['fmt']) : '—' }}</td>
                        <td class="{{ $r['good'] === null ? '' : ($r['good'] ? 'ok' : 'warn') }}">@if($r['good'] !== null)<span aria-hidden="true">{{ $r['good'] ? '✓' : '!' }}</span> {{ $r['good'] ? 'Better' : 'Worse' }}@else — @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @else
            {!! $notCaptured !!}
        @endif
    </div>
</section>
