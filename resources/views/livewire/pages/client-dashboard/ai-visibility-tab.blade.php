@if($activeTabIndex === 3)
@php
    $allMonths = collect($aiMonths ?? []);
    $months = $aiPeriodMonths > 0 ? $allMonths->take(-$aiPeriodMonths) : $allMonths;
    $latestM = $months->last();
    $prevM = $months->slice(-2, 1)->first();
    $monthLabels = $months->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values();
    $totalAiImpr = $months->sum('ai_impressions');
    $totalAiSess = $months->sum('ai_sessions');
    $totalAiRev = $months->sum('ai_revenue');
    $totalAiKE = $months->sum('ai_key_events');
    $clarityCit = $aiSnapshot['clarity_citations'] ?? null;
    $aiDelta = fn($a, $b) => (!isset($a) || !isset($b) || (float)$b == 0.0) ? null : round(($a - $b) / $b * 100, 1);
    $impressionsDelta = $aiDelta($latestM['ai_impressions'] ?? null, $prevM['ai_impressions'] ?? null);
    $sessionsDelta = $aiDelta($latestM['ai_sessions'] ?? null, $prevM['ai_sessions'] ?? null);
    $revDelta = $aiDelta($latestM['ai_revenue'] ?? null, $prevM['ai_revenue'] ?? null);
    $keDelta = $aiDelta($latestM['ai_key_events'] ?? null, $prevM['ai_key_events'] ?? null);
    // Overview and comparisons need summary fields only, not the full response exports.
    $comparisonReportMonth = \App\Models\SemrushReport::where('tenant_id', $clientId)->max('report_month');
    $comparisonReports = \App\Models\SemrushReport::where('tenant_id', $clientId)->where('report_month', $comparisonReportMonth)
        ->get(['domain', 'is_client', 'metrics', 'report_month'])->keyBy('domain');
    $clientReport = $comparisonReports->firstWhere('is_client', true);
    $clientSemrush = $clientReport?->metrics ?? [];
    $clientSnapshotMatches = !$comparisonReportMonth || ($aiSnapshot['report_month'] ?? null) === $comparisonReportMonth;
    $semrushScore = ($clientSnapshotMatches ? ($aiSnapshot['semrush_score'] ?? null) : null) ?? $clientSemrush['overall_score'] ?? null;
    $snapshotLabel = $comparisonReportMonth ?? $aiSnapshot['report_month'] ?? 'No snapshot';
    $trafficLabel = $months->isNotEmpty() ? \Carbon\Carbon::parse($months->first()['month'])->format('M Y').' – '.\Carbon\Carbon::parse($months->last()['month'])->format('M Y') : 'No traffic data';
    $metricSum = fn($rows, $key) => collect($rows)->contains(fn($row) => isset($row[$key])) ? collect($rows)->sum($key) : null;
    $allEntities = collect([[
        'name' => $dashboardData['client']['name'] ?? 'Client', 'is_client' => true, 'comp_id' => null,
        'domain' => $clientReport?->domain, 'semrush_score' => $semrushScore,
        'topics' => ($clientSnapshotMatches ? ($aiSnapshot['semrush_topics_count'] ?? null) : null) ?? $clientSemrush['topics'] ?? null,
        'prompts' => ($clientSnapshotMatches ? ($aiSnapshot['semrush_prompts_count'] ?? null) : null) ?? $clientSemrush['prompts'] ?? null,
        'mentions' => $clientSemrush['mentions'] ?? null, 'citations' => $clientSemrush['citations'] ?? null,
        'cited_pages' => $clientSemrush['cited_pages'] ?? null, 'cited_sources' => $clientSemrush['cited_sources'] ?? null,
        'monthly_audience' => $clientSemrush['monthly_audience'] ?? null, 'estimated_gemini_visits' => $clientSemrush['estimated_gemini_visits'] ?? null,
        'ai_impressions' => $metricSum($aiMonths ?? [], 'ai_impressions'), 'ai_sessions' => $metricSum($aiMonths ?? [], 'ai_sessions'), 'clarity' => $clarityCit,
    ]]);
    foreach ($competitors ?? [] as $comp) {
        $host = preg_replace('/^www\./', '', strtolower(parse_url(str_contains($comp['website'] ?? '', '://') ? $comp['website'] : 'https://'.($comp['website'] ?? ''), PHP_URL_HOST) ?? ''));
        $m = $comparisonReports->get($host)?->metrics ?? [];
        $v = $comp['latest_visibility'] ?? [];
        $matchingVisibility = !$comparisonReportMonth || ($v['report_month'] ?? null) === $comparisonReportMonth;
        $allEntities->push([
            'name' => $comp['name'], 'is_client' => false, 'comp_id' => $comp['id'], 'domain' => $host,
            'semrush_score' => ($matchingVisibility ? ($v['semrush_score'] ?? null) : null) ?? $m['overall_score'] ?? null,
            'topics' => ($matchingVisibility ? ($v['semrush_topics_count'] ?? null) : null) ?? $m['topics'] ?? null, 'prompts' => $m['prompts'] ?? null,
            'mentions' => $m['mentions'] ?? null, 'citations' => $m['citations'] ?? null,
            'cited_pages' => $m['cited_pages'] ?? null, 'cited_sources' => $m['cited_sources'] ?? null,
            'monthly_audience' => $m['monthly_audience'] ?? null, 'estimated_gemini_visits' => $m['estimated_gemini_visits'] ?? null,
            'ai_impressions' => $metricSum($comp['visibility'] ?? [], 'ai_impressions'), 'ai_sessions' => $metricSum($comp['visibility'] ?? [], 'ai_sessions'), 'clarity' => $v['clarity_citations'] ?? null,
        ]);
    }
    $entitiesWithScore = $allEntities->filter(fn($e) => $e['semrush_score'] !== null)->sortByDesc('semrush_score')->values();
@endphp
<div class="ai-workspace" data-testid="ai-visibility-workspace">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:10px">
        @if($aiVisibilitySection === 'settings')
            <button type="button" wire:click="setAiVisibilitySection('overview')" class="btn-secondary">Back to report</button>
        @else
            @if(!empty($aiPartOne['months']))
                <label for="av-month" class="micro-label">Month</label>
                <select id="av-month" wire:model.live="aiReportMonth" class="input-field" style="width:auto">
                    @foreach($aiPartOne['months'] as $ym => $lbl)
                        <option value="{{ $ym }}" @selected($ym === $aiPartOne['month'])>{{ $lbl }}</option>
                    @endforeach
                </select>
            @endif
            <button type="button" wire:click="setAiVisibilitySection('settings')" class="btn-secondary">Data &amp; settings</button>
        @endif
    </div>

    @if($aiVisibilitySection === 'settings')
        <div class="space-y-6 mt-6" data-ai-section="settings">
            @include('livewire.pages.client-dashboard.ai-data-settings')
            <div>@include('livewire.pages.client-dashboard.ai-competitors')</div>
            <div x-data="{ open: false }">
                <button type="button" class="btn-secondary" @click="open = !open" :aria-expanded="open.toString()" aria-controls="air-explorer"><span x-text="open ? 'Hide topic & prompt explorer' : 'Show topic & prompt explorer'">Show topic &amp; prompt explorer</span></button>
                <div id="air-explorer" x-show="open" x-cloak class="mt-4">
                    @if($comparisonReports->isNotEmpty())
                        @include('livewire.pages.client-dashboard.semrush-exports')
                    @elseif(!empty($aiSnapshot['semrush_top_topics']))
                        @include('livewire.pages.client-dashboard.ai-legacy-topics')
                    @else
                        <div class="card ai-empty-state"><h3>No topic or prompt exports yet</h3><p>Imported Semrush records will appear here.</p></div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($aiPartOne)
        <div wire:key="av-{{ $aiPartOne['month'] }}" data-ai-section="report">
            @include('livewire.pages.client-dashboard.ai-report.av', ['av' => $aiPartOne])
        </div>
    @endif
    @include('livewire.pages.client-dashboard.ai-snapshot-form')
</div>
@endif
