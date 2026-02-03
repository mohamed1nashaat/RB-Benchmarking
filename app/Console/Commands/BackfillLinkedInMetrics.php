<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Services\LinkedInAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackfillLinkedInMetrics extends Command
{
    protected $signature = 'linkedin:backfill-metrics
                            {--start-date= : Start date for backfill (YYYY-MM-DD)}
                            {--end-date= : End date for backfill (YYYY-MM-DD)}
                            {--full-history : Fetch data from 10+ years ago}
                            {--account-id= : Specific ad account ID to sync}
                            {--quick : Use ALL granularity for fast verification (no daily breakdown)}';

    protected $description = 'Backfill historical LinkedIn Ads metrics (supports 10+ years of data)';

    private LinkedInAdsService $linkedInService;
    private array $stats = [
        'metrics_created' => 0,
        'metrics_updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function __construct(LinkedInAdsService $linkedInService)
    {
        parent::__construct();
        $this->linkedInService = $linkedInService;
    }

    public function handle()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $fullHistory = $this->option('full-history');
        $accountId = $this->option('account-id');
        $quickMode = $this->option('quick');

        if ($quickMode) {
            $this->warn("Quick mode enabled - using ALL granularity (no daily breakdown)");
        }

        // Set date range
        if ($fullHistory) {
            // Detect earliest campaign date for smart start date
            $earliestDate = $this->getEarliestCampaignDate($accountId);
            if ($earliestDate) {
                // Go back 30 days before earliest campaign to catch any pre-campaign data
                $startDate = Carbon::parse($earliestDate)->subDays(30)->format('Y-m-d');
                $this->info("Full history mode - detected earliest campaign: {$earliestDate}");
                $this->info("Syncing from: {$startDate}");
            } else {
                // Fallback to 3 years if no campaigns found
                $startDate = Carbon::now()->subYears(3)->format('Y-m-d');
                $this->info('Full history mode - no campaigns found, using 3-year lookback.');
            }
            $endDate = Carbon::now()->format('Y-m-d');
        } elseif (!$startDate) {
            // Default: backfill last 90 days
            $startDate = Carbon::now()->subDays(90)->format('Y-m-d');
            $endDate = Carbon::now()->format('Y-m-d');
        } elseif (!$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        $this->info("=== LinkedIn Historical Backfill ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("========================================");
        $this->newLine();

        // Get all LinkedIn integrations
        $integrations = Integration::where('platform', 'linkedin')
            ->where('status', 'active')
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn('No active LinkedIn integrations found.');
            return 1;
        }

        $this->info("Found {$integrations->count()} LinkedIn integration(s) to backfill");
        $this->newLine();

        foreach ($integrations as $integration) {
            $this->processIntegration($integration, $startDate, $endDate, $accountId, $quickMode);
        }

        // Display summary
        $this->displaySummary($startDate, $endDate);

        return 0;
    }

    private function processIntegration(Integration $integration, string $startDate, string $endDate, ?string $accountId = null, bool $quickMode = false): void
    {
        $this->info("Processing Integration ID: {$integration->id}");
        $this->info("Tenant: {$integration->tenant->name}");

        // Get ad accounts for this integration (filter by specific ID if provided)
        $query = AdAccount::where('integration_id', $integration->id);
        if ($accountId) {
            $query->where('id', $accountId);
            $this->info("Filtering to specific account ID: {$accountId}");
        }
        $adAccounts = $query->get();

        if ($adAccounts->isEmpty()) {
            $this->warn("  No ad accounts found for integration {$integration->id}");
            return;
        }

        $this->info("Found {$adAccounts->count()} ad account(s)");
        $this->newLine();

        foreach ($adAccounts as $adAccount) {
            $this->processAdAccount($integration, $adAccount, $startDate, $endDate, $quickMode);
        }
    }

    private function processAdAccount(Integration $integration, AdAccount $adAccount, string $startDate, string $endDate, bool $quickMode = false): void
    {
        $this->info("  Account: {$adAccount->account_name} (ID: {$adAccount->id})");

        // Get all campaigns for this account
        $campaigns = AdCampaign::where('ad_account_id', $adAccount->id)->get();

        if ($campaigns->isEmpty()) {
            $this->warn("    No campaigns found for account {$adAccount->account_name}");
            $this->newLine();
            return;
        }

        $this->info("    Found {$campaigns->count()} campaigns");

        // Quick mode: use ALL granularity (single API call for entire date range per campaign)
        if ($quickMode) {
            $this->info("    Processing in quick mode (ALL granularity)");

            $progressBar = $this->output->createProgressBar($campaigns->count());
            $progressBar->start();

            foreach ($campaigns as $campaign) {
                try {
                    $this->backfillCampaignMetricsQuick($integration, $campaign, $startDate, $endDate);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error('LinkedIn backfill error (quick mode)', [
                        'campaign_id' => $campaign->id,
                        'date_range' => $startDate . ' to ' . $endDate,
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            $this->info("    ✓ Metrics Created: {$this->stats['metrics_created']}");
            $this->info("    ✓ Metrics Updated: {$this->stats['metrics_updated']}");
            $this->info("    ✓ Errors: {$this->stats['errors']}");
            $this->newLine();
            return;
        }

        // Normal mode: Break date range into yearly chunks (LinkedIn API returns daily data regardless of range size)
        $chunks = $this->getDateChunks($startDate, $endDate, 365);
        $this->info("    Processing {$chunks->count()} chunks (365-day periods)");

        $progressBar = $this->output->createProgressBar($chunks->count() * $campaigns->count());
        $progressBar->start();

        foreach ($chunks as $index => $chunk) {
            foreach ($campaigns as $campaign) {
                try {
                    $this->backfillCampaignMetrics($integration, $campaign, $chunk['start'], $chunk['end']);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error('LinkedIn backfill error', [
                        'campaign_id' => $campaign->id,
                        'date_range' => $chunk['start'] . ' to ' . $chunk['end'],
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("    ✓ Metrics Created: {$this->stats['metrics_created']}");
        $this->info("    ✓ Metrics Updated: {$this->stats['metrics_updated']}");
        $this->info("    ✓ Errors: {$this->stats['errors']}");
        $this->newLine();
    }

    private function backfillCampaignMetrics(Integration $integration, AdCampaign $campaign, string $startDate, string $endDate): void
    {
        // Get metrics from LinkedIn API for date range
        $metricsData = $this->linkedInService->getCampaignMetrics($integration, $campaign, $startDate, $endDate);

        if (empty($metricsData)) {
            $this->stats['skipped']++;
            return;
        }

        // LinkedIn returns daily metrics, process each day
        foreach ($metricsData as $dailyMetrics) {
            // Extract date from metrics (LinkedIn returns dateRange in the response)
            $dateRange = $dailyMetrics['dateRange'] ?? null;
            if (!$dateRange) {
                continue;
            }

            // Convert LinkedIn date format to Y-m-d
            $date = $this->extractDateFromRange($dateRange);
            if (!$date) {
                continue;
            }

            // Save metrics directly without making another API call
            try {
                $this->linkedInService->saveMetricsFromApiResponse($integration, $campaign, $dailyMetrics, $date);
                $this->stats['metrics_created']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('LinkedIn metric save error', [
                    'campaign_id' => $campaign->id,
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function backfillCampaignMetricsQuick(Integration $integration, AdCampaign $campaign, string $startDate, string $endDate): void
    {
        // Get metrics from LinkedIn API using ALL granularity (single total for entire date range)
        $metricsData = $this->linkedInService->getCampaignTotalMetrics($integration, $campaign, $startDate, $endDate);

        if (empty($metricsData)) {
            $this->stats['skipped']++;
            return;
        }

        // ALL granularity returns a single total, use end date as the metric date
        foreach ($metricsData as $totalMetrics) {
            // For ALL granularity, store with end date
            try {
                $this->linkedInService->saveMetricsFromApiResponse($integration, $campaign, $totalMetrics, $endDate);
                $this->stats['metrics_created']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('LinkedIn metric save error (quick mode)', [
                    'campaign_id' => $campaign->id,
                    'date_range' => $startDate . ' to ' . $endDate,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function extractDateFromRange(array $dateRange): ?string
    {
        try {
            $start = $dateRange['start'] ?? null;
            if (!$start) {
                return null;
            }

            $year = $start['year'] ?? null;
            $month = $start['month'] ?? null;
            $day = $start['day'] ?? null;

            if (!$year || !$month || !$day) {
                return null;
            }

            return Carbon::create($year, $month, $day)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error('LinkedIn date extraction error', ['dateRange' => $dateRange, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function getDateChunks(string $startDate, string $endDate, int $chunkDays): \Illuminate\Support\Collection
    {
        $chunks = collect();
        $currentStart = Carbon::parse($startDate);
        $finalEnd = Carbon::parse($endDate);

        while ($currentStart->lte($finalEnd)) {
            $currentEnd = $currentStart->copy()->addDays($chunkDays - 1);

            if ($currentEnd->gt($finalEnd)) {
                $currentEnd = $finalEnd;
            }

            $chunks->push([
                'start' => $currentStart->format('Y-m-d'),
                'end' => $currentEnd->format('Y-m-d'),
            ]);

            $currentStart = $currentEnd->copy()->addDay();
        }

        return $chunks;
    }

    private function getEarliestCampaignDate(?string $accountId = null): ?string
    {
        // Get LinkedIn integrations
        $integrations = Integration::where('platform', 'linkedin')
            ->where('status', 'active')
            ->get();

        if ($integrations->isEmpty()) {
            return null;
        }

        $earliestDate = null;

        foreach ($integrations as $integration) {
            $query = AdAccount::where('integration_id', $integration->id);
            if ($accountId) {
                $query->where('id', $accountId);
            }
            $adAccounts = $query->get();

            foreach ($adAccounts as $adAccount) {
                // Get campaigns and find earliest by parsing campaign name for date
                $campaigns = AdCampaign::where('ad_account_id', $adAccount->id)->get();

                foreach ($campaigns as $campaign) {
                    // Try to extract date from campaign name (format: "Campaign Name - Mon DD, YYYY")
                    if (preg_match('/(\w+)\s+(\d{1,2}),\s+(\d{4})$/', $campaign->name, $matches)) {
                        try {
                            $dateStr = $matches[1] . ' ' . $matches[2] . ', ' . $matches[3];
                            $campaignDate = Carbon::parse($dateStr)->format('Y-m-d');

                            if (!$earliestDate || $campaignDate < $earliestDate) {
                                $earliestDate = $campaignDate;
                            }
                        } catch (\Exception $e) {
                            // Ignore parsing errors
                        }
                    }

                    // Also check campaign created_at as fallback
                    $createdDate = $campaign->created_at?->format('Y-m-d');
                    if ($createdDate && (!$earliestDate || $createdDate < $earliestDate)) {
                        // Only use created_at if it's older than 1 year (to avoid using recent sync dates)
                        if (Carbon::parse($createdDate)->lt(Carbon::now()->subYear())) {
                            $earliestDate = $createdDate;
                        }
                    }
                }
            }
        }

        return $earliestDate;
    }

    private function displaySummary(string $startDate, string $endDate): void
    {
        $this->newLine();
        $this->info("=== Backfill Summary ===");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->info("Total Metrics Created: {$this->stats['metrics_created']}");
        $this->info("Total Metrics Updated: {$this->stats['metrics_updated']}");
        $this->info("Total Skipped: {$this->stats['skipped']}");
        $this->info("Total Errors: {$this->stats['errors']}");
        $this->newLine();
        $this->info("✓ Backfill completed successfully!");
    }
}
