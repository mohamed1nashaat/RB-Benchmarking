<?php

namespace App\Services;

use App\Models\AdMetric;
use App\Models\IndustryBenchmark;
use App\Models\Report;
use Illuminate\Support\Facades\Log;

class ReportService
{
    public function __construct(private OllamaService $ollama) {}

    public function generate(Report $report): Report
    {
        $report->update(['status' => 'generating']);

        try {
            $metrics = $this->getMetrics($report);
            $report->update(['performance_data' => $metrics]);

            $systemPrompt = $this->buildSystemPrompt($report, $metrics);

            $userMessage = 'Generate a complete marketing performance report with:
1. Executive Summary (2-3 sentences, key headline numbers)
2. Performance Analysis (detailed breakdown vs benchmarks)
3. Three Specific Recommendations (each with expected SAR impact)

Format each section clearly with the heading on its own line followed by the content.';

            $narrative = $this->ollama->chat($systemPrompt, [
                ['role' => 'user', 'content' => $userMessage],
            ]);

            if (empty($narrative)) {
                throw new \RuntimeException('Ollama returned empty narrative.');
            }

            $sections = $this->parseNarrativeSections($narrative);

            $report->update([
                'status'            => 'ready',
                'executive_summary' => $sections['executive_summary'] ?? $narrative,
                'recommendations'   => $sections['recommendations'] ?? [],
                'sections'          => $sections,
                'metadata'          => [
                    'generated_at' => now()->toIso8601String(),
                    'model'        => config('services.ollama.model', 'llama3.1:70b'),
                    'period'       => $report->periodLabel(),
                    'word_count'   => str_word_count($narrative),
                ],
            ]);
        } catch (\Throwable $e) {
            $report->update([
                'status'   => 'failed',
                'metadata' => ['error' => $e->getMessage(), 'failed_at' => now()->toIso8601String()],
            ]);
            Log::error('ReportService::generate failed', [
                'report_id' => $report->id,
                'error'     => $e->getMessage(),
            ]);
        }

        return $report->fresh();
    }

    private function getMetrics(Report $report): array
    {
        $base = AdMetric::query()
            ->where('tenant_id', $report->tenant_id)
            ->whereBetween('date', [$report->period_start, $report->period_end]);

        if ($report->ad_account_id) {
            $base->where('ad_account_id', $report->ad_account_id);
        }
        if ($report->platform) {
            $base->where('platform', $report->platform);
        }

        $agg = $base->selectRaw('
            SUM(spend)       as total_spend,
            SUM(impressions) as total_impressions,
            SUM(clicks)      as total_clicks,
            SUM(leads)       as total_leads,
            SUM(conversions) as total_conversions,
            COUNT(DISTINCT ad_campaign_id) as campaign_count
        ')->first();

        $spend       = (float) ($agg->total_spend ?? 0);
        $impressions = (int)   ($agg->total_impressions ?? 0);
        $clicks      = (int)   ($agg->total_clicks ?? 0);
        $leads       = (int)   ($agg->total_leads ?? 0);
        $campaigns   = (int)   ($agg->campaign_count ?? 0);

        $ctr  = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $cpc  = $clicks > 0      ? round($spend / $clicks, 2) : 0;
        $cpm  = $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0;
        $cpl  = $leads > 0       ? round($spend / $leads, 2) : 0;

        $benchmarks = [];
        if ($report->industry && $report->platform) {
            $bms = IndustryBenchmark::where('industry', $report->industry)
                ->where('platform', $report->platform)
                ->get()
                ->keyBy('metric');

            foreach ($bms as $metric => $bm) {
                $benchmarks[$metric] = [
                    'p25' => $bm->percentile_25,
                    'p50' => $bm->percentile_50,
                    'p75' => $bm->percentile_75,
                ];
            }
        }

        return compact('spend', 'impressions', 'clicks', 'leads', 'campaigns', 'ctr', 'cpc', 'cpm', 'cpl', 'benchmarks');
    }

    private function buildSystemPrompt(Report $report, array $metrics): string
    {
        $bm = $metrics['benchmarks'] ?? [];
        $lines = [
            'You are an expert marketing analyst at Red Bananas, a MENA-focused performance marketing agency.',
            'You write clear, data-driven client reports in English with specific numbers and actionable advice.',
            'Currency is SAR (Saudi Riyal). Never use generic global averages — always compare to MENA benchmarks.',
            '',
            '=== REPORT CONTEXT ===',
            'Client: ' . ($report->adAccount?->account_name ?? 'All Accounts'),
            'Period: ' . $report->periodLabel(),
            'Platform: ' . ucfirst($report->platform ?? 'All Platforms'),
            'Industry: ' . ($report->industry ?? 'Not specified'),
            '',
            '=== CAMPAIGN PERFORMANCE ===',
            'Active campaigns: ' . $metrics['campaigns'],
            'Total spend: SAR ' . number_format($metrics['spend'], 2),
            'Impressions: ' . number_format($metrics['impressions']),
            'Clicks: ' . number_format($metrics['clicks']),
            'CTR: ' . $metrics['ctr'] . '%',
            'CPC: SAR ' . $metrics['cpc'],
            'CPM: SAR ' . $metrics['cpm'],
            'Leads / Conversions: ' . number_format($metrics['leads']),
            $metrics['cpl'] > 0 ? 'CPL: SAR ' . $metrics['cpl'] : 'CPL: N/A (no leads tracked)',
        ];

        if (!empty($bm)) {
            $lines[] = '';
            $lines[] = '=== MENA INDUSTRY BENCHMARKS ===';
            foreach ($bm as $metric => $percentiles) {
                $lines[] = strtoupper($metric) . ': P25=' . $percentiles['p25'] . '  P50=' . $percentiles['p50'] . '  P75=' . $percentiles['p75'];
            }

            $lines[] = '';
            $lines[] = '=== BENCHMARK COMPARISON ===';
            if (isset($bm['ctr'])) {
                $p50 = (float) $bm['ctr']['p50'];
                $status = $metrics['ctr'] >= $p50 ? 'ABOVE' : 'BELOW';
                $lines[] = 'CTR ' . $metrics['ctr'] . '% is ' . $status . ' industry median (' . $p50 . '%)';
            }
            if (isset($bm['cpl']) && $metrics['cpl'] > 0) {
                $p50 = (float) $bm['cpl']['p50'];
                $status = $metrics['cpl'] <= $p50 ? 'BETTER THAN' : 'WORSE THAN';
                $lines[] = 'CPL SAR ' . $metrics['cpl'] . ' is ' . $status . ' industry median (SAR ' . $p50 . ')';
            }
        }

        $lines[] = '';
        $lines[] = 'Today: ' . now()->format('Y-m-d') . '. Write for a marketing manager, not a technical audience.';

        return implode("\n", $lines);
    }

    private function parseNarrativeSections(string $narrative): array
    {
        $sections = ['full_narrative' => $narrative];

        // Executive Summary
        if (preg_match('/executive\s+summary[:\s]*\n(.*?)(?=\n[A-Z][A-Za-z\s]+:|\n##|\z)/si', $narrative, $m)) {
            $sections['executive_summary'] = trim($m[1]);
        } else {
            $paragraphs = explode("\n\n", trim($narrative));
            $sections['executive_summary'] = trim($paragraphs[0] ?? $narrative);
        }

        // Recommendations — capture numbered list items
        $recs = [];
        if (preg_match('/recommendation[s]?[:\s]*\n(.*)/si', $narrative, $m)) {
            preg_match_all('/\d+[\.\)]\s+(.+?)(?=\d+[\.\)]|\z)/s', $m[1], $items);
            foreach ($items[1] as $item) {
                $recs[] = trim($item);
            }
        }
        $sections['recommendations'] = $recs;

        return $sections;
    }
}
