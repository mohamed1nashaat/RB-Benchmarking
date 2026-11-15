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
    // Report helpers shared by the Overview / Diagnostics partials.
    $airNice = function ($v) {
        if ($v <= 0) return 1;
        $p = pow(10, floor(log10($v)));
        $n = $v / $p;
        return ($n <= 1 ? 1 : ($n <= 2 ? 2 : ($n <= 2.5 ? 2.5 : ($n <= 5 ? 5 : 10)))) * $p;
    };
    $airFmt = fn($v, string $f) => match ($f) {
        'k' => number_format($v / 1000, 1).'k',
        'pct1' => number_format($v, 1).'%',
        'pct2' => number_format($v, 2).'%',
        'sar' => 'SAR '.number_format($v, 2),
        'fr_pct' => number_format($v * 100, 2).'%',
        'fr_pct1' => number_format($v * 100, 1).'%',
        'fr_dec' => number_format($v, 2),
        'fr_sec' => number_format($v, 1).' s',
        'fr_secs' => number_format($v).' s',
        'fr_ms' => number_format($v).' ms',
        'fr_cls' => number_format($v, 3),
        'fr_int' => number_format($v),
        default => number_format($v),
    };
@endphp
<div class="ai-workspace space-y-6" data-testid="ai-visibility-workspace">
    <div class="ai-workspace-header">
        <div>
            <h2 class="section-title">AI visibility report</h2>
            <p>Is the brand showing up inside AI answers, and is anyone arriving from them? Use the ? next to any section to see where its numbers come from.</p>
        </div>
        @if($aiVisibilitySection === 'settings')
            <button type="button" wire:click="setAiVisibilitySection('overview')" class="btn-secondary">Back to report</button>
        @else
            <button type="button" wire:click="setAiVisibilitySection('settings')" class="btn-secondary">Data &amp; settings</button>
        @endif
    </div>

    @if($aiVisibilitySection === 'settings')
        <div class="space-y-6" data-ai-section="settings">
            @include('livewire.pages.client-dashboard.ai-data-settings')
        </div>
    @else
    <div wire:key="ai-report-{{ $aiReportMonth }}-{{ $aiHeroMetric }}" class="space-y-6" data-ai-section="report">
        @if(!$aiReport || $aiReport['empty'])
            <div class="card ai-empty-state"><h3>No AI visibility data yet</h3><p>Connect Google or import the monthly workbook in Data &amp; settings.</p></div>
        @else
            <div class="air-filters" aria-label="Filters">
                <label for="air-month" class="micro-label">Month</label>
                <select id="air-month" wire:model.live="aiReportMonth" class="input-field">
                    @foreach($aiReport['months'] as $ym => $lbl)
                        <option value="{{ $ym }}" @selected($ym === $aiReport['ctx']['sel'])>{{ $lbl }}</option>
                    @endforeach
                </select>
                <span class="air-fresh">{{ $aiReport['fresh']['line'] }}</span>
            </div>
            @include('livewire.pages.client-dashboard.ai-report-overview')
            <div class="ai-section-heading" id="ai-diagnostics"><div><h2>Diagnostics</h2><p>The why behind the numbers for the month selected above.</p></div></div>
            @include('livewire.pages.client-dashboard.ai-report-diagnostics')
        @endif
    </div>

    <div class="space-y-6" id="ai-competitors">
        @include('livewire.pages.client-dashboard.ai-competitors')
    </div>

    <div class="space-y-6" id="ai-research">
        @if($comparisonReports->isNotEmpty())
            @include('livewire.pages.client-dashboard.semrush-exports')
        @elseif(!empty($aiSnapshot['semrush_top_topics']))
            @include('livewire.pages.client-dashboard.ai-legacy-topics')
        @else
            <div class="card ai-empty-state"><h3>No topic or prompt exports yet</h3><p>Imported Semrush records will appear here.</p></div>
        @endif
    </div>

    @if($aiReport && !$aiReport['empty'])
        <section aria-label="How to read this report" class="card air-card">
            <div class="air-foot">
                <div><h4><span class="kpi-label">Observed</span><x-ai-help :h="$aiReport['help']['legend']" /></h4><p>Recorded by the platform from real events in its own logs: Search Console, Bing (via Clarity), Clarity, GA4.</p></div>
                <div><h4><span class="kpi-label">Simulated</span></h4><p>Produced by asking AI models a fixed set of prompts each month and recording the answers (Semrush). Directional, never mixed with observed figures.</p></div>
                <div><h4><span class="kpi-label">Refresh</span></h4><p>{{ $aiReport['fresh']['refresh'] }}</p></div>
            </div>
        </section>
    @endif
    @endif
    @include('livewire.pages.client-dashboard.ai-snapshot-form')
</div>
@endif
