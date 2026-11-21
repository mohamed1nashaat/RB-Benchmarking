<?php

namespace App\Console\Commands;

use App\Exceptions\OllamaException;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\AiVector;
use App\Models\IndustryBenchmark;
use App\Models\Tenant;
use App\Services\OllamaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmbedAdMetrics extends Command
{
    protected $signature = 'ai:embed-metrics
                            {--tenant= : Tenant ID to process (all tenants if omitted)}
                            {--days=30 : Number of days of history to embed}';

    protected $description = 'Embed ad metrics and industry benchmarks into the AI vector store';

    public function handle(OllamaService $ollama): int
    {
        $days     = (int) $this->option('days');
        $tenantId = $this->option('tenant');
        $since    = now()->subDays($days)->toDateString();

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::where('status', 'active')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $this->info("Embedding ad metrics for {$tenants->count()} tenant(s), last {$days} days...");

        foreach ($tenants as $tenant) {
            $this->embedTenantMetrics($tenant, $ollama, $since, $days);
        }

        $this->info('Embedding industry benchmarks...');
        $this->embedBenchmarks($ollama);

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function embedTenantMetrics(Tenant $tenant, OllamaService $ollama, string $since, int $days): void
    {
        $this->line("  Tenant [{$tenant->id}] {$tenant->name}");

        // Group metrics by campaign + platform + ISO week
        $rows = AdMetric::query()
            ->where('tenant_id', $tenant->id)
            ->where('date', '>=', $since)
            ->selectRaw("
                ad_campaign_id,
                platform,
                YEAR(date) as yr,
                WEEK(date, 3) as wk,
                SUM(spend)       as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks)      as total_clicks,
                SUM(leads)       as total_leads,
                SUM(conversions) as total_conversions,
                SUM(link_clicks) as total_link_clicks
            ")
            ->groupBy('ad_campaign_id', 'platform', 'yr', 'wk')
            ->get();

        if ($rows->isEmpty()) {
            $this->line("    No metrics found.");
            return;
        }

        // Fetch campaign industries in one query
        $campaignIndustries = DB::table('ad_campaigns')
            ->where('tenant_id', $tenant->id)
            ->pluck('sub_industry', 'id')
            ->toArray();

        $accountIndustries = AdAccount::where('tenant_id', $tenant->id)
            ->pluck('industry', 'id')
            ->toArray();

        $campaignAccountMap = DB::table('ad_campaigns')
            ->where('tenant_id', $tenant->id)
            ->pluck('ad_account_id', 'id')
            ->toArray();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $skipped = 0;

        foreach ($rows as $row) {
            $spend       = round((float) $row->total_spend, 2);
            $impressions = (int) $row->total_impressions;
            $clicks      = (int) $row->total_clicks;
            $leads       = (int) $row->total_leads;
            $conversions = (int) $row->total_conversions;

            $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
            $cpc = $clicks > 0      ? round($spend / $clicks, 2) : 0;
            $cpl = $leads > 0       ? round($spend / $leads, 2) : null;

            $accountId = $campaignAccountMap[$row->ad_campaign_id] ?? null;
            $industry  = $campaignIndustries[$row->ad_campaign_id]
                ?? ($accountId ? ($accountIndustries[$accountId] ?? 'unknown') : 'unknown');

            $week = sprintf('%04d-W%02d', $row->yr, $row->wk);

            $parts = [
                "Platform: {$row->platform}",
                "Industry: {$industry}",
                "Week: {$week}",
                "Spend: SAR " . number_format($spend, 2),
                "Impressions: " . $this->shortNum($impressions),
                "Clicks: " . $this->shortNum($clicks),
                "CTR: {$ctr}%",
                "CPC: SAR {$cpc}",
                $cpl !== null ? "CPL: SAR {$cpl}" : null,
                "Conversions: " . number_format($conversions),
            ];

            $text = implode(', ', array_filter($parts));

            // Deterministic entity_id from campaign + week hash
            $entityId = crc32($row->ad_campaign_id . '_' . $week);

            try {
                $vector = $ollama->embed($text);

                AiVector::updateOrCreate(
                    [
                        'tenant_id'   => $tenant->id,
                        'entity_type' => 'campaign_metrics',
                        'entity_id'   => abs($entityId),
                    ],
                    [
                        'content' => $text,
                        'vector'  => $vector,
                        'model'   => config('services.ollama.embed_model', 'nomic-embed-text'),
                    ]
                );
            } catch (OllamaException $e) {
                Log::warning('EmbedAdMetrics: embed failed', ['error' => $e->getMessage(), 'text' => substr($text, 0, 100)]);
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($skipped > 0) {
            $this->warn("    Skipped {$skipped} rows due to Ollama errors.");
        }
    }

    private function embedBenchmarks(OllamaService $ollama): void
    {
        $benchmarks = IndustryBenchmark::all();

        if ($benchmarks->isEmpty()) {
            $this->line('  No benchmarks to embed.');
            return;
        }

        $bar = $this->output->createProgressBar($benchmarks->count());
        $bar->start();

        $skipped = 0;

        foreach ($benchmarks as $bm) {
            $text = implode(', ', [
                "Industry benchmark",
                "Industry: {$bm->industry}",
                "Platform: {$bm->platform}",
                "Metric: " . strtoupper($bm->metric),
                "P10: {$bm->percentile_10}",
                "P25: {$bm->percentile_25}",
                "P50: {$bm->percentile_50}",
                "P75: {$bm->percentile_75}",
                "P90: {$bm->percentile_90}",
            ]);

            try {
                $vector = $ollama->embed($text);

                AiVector::updateOrCreate(
                    [
                        'tenant_id'   => null,
                        'entity_type' => 'benchmark',
                        'entity_id'   => $bm->id,
                    ],
                    [
                        'content' => $text,
                        'vector'  => $vector,
                        'model'   => config('services.ollama.embed_model', 'nomic-embed-text'),
                    ]
                );
            } catch (OllamaException $e) {
                Log::warning('EmbedAdMetrics: benchmark embed failed', ['id' => $bm->id, 'error' => $e->getMessage()]);
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($skipped > 0) {
            $this->warn("  Skipped {$skipped} benchmarks due to Ollama errors.");
        }
    }

    private function shortNum(int $n): string
    {
        if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
        return (string) $n;
    }
}
