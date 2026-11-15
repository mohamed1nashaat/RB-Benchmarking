{{-- Analyst note --}}
<section class="card air-card air-note" aria-labelledby="air-comm-h">
    <div class="flex items-center gap-2"><p class="kpi-label">Analyst note</p><x-ai-help :h="$help['note']" /></div>
    <h3 id="air-comm-h">{{ $aiReport['commentary']['heading'] }}</h3>
    <p>{!! $aiReport['commentary']['html'] !!}</p>
</section>
