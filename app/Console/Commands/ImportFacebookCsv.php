<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportFacebookCsv extends Command
{
    protected $signature = 'facebook:import-csv
                            {file : Path to the CSV file to import}
                            {--account-id= : Facebook Ad Account ID (e.g., act_123456789)}
                            {--tenant-id= : Tenant ID (optional, will use integration\'s tenant)}
                            {--integration-id= : Integration ID (optional)}
                            {--dry-run : Preview import without saving to database}';

    protected $description = 'Import historical Facebook advertising data from CSV export';

    private $stats = [
        'rows_processed' => 0,
        'campaigns_created' => 0,
        'campaigns_updated' => 0,
        'metrics_created' => 0,
        'metrics_updated' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    public function handle()
    {
        $filePath = $this->argument('file');
        $accountId = $this->option('account-id');
        $isDryRun = $this->option('dry-run');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("=== Facebook CSV Import ===");
        $this->info("File: {$filePath}");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Get or find integration
        $integration = $this->getIntegration();
        if (!$integration) {
            $this->error('No Facebook integration found. Please specify --integration-id or ensure a Facebook integration exists.');
            return 1;
        }

        $this->info("Using Integration ID: {$integration->id} (Tenant: {$integration->tenant->name})");

        // Get or create ad account
        $adAccount = null;
        if ($accountId) {
            $adAccount = $this->getOrCreateAdAccount($integration, $accountId, $isDryRun);
            if (!$adAccount && !$isDryRun) {
                $this->error("Failed to get/create ad account: {$accountId}");
                return 1;
            }
        }

        $this->newLine();
        $this->info("Starting CSV import...");
        $this->newLine();

        // Open and read CSV
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return 1;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error("Could not read CSV headers");
            fclose($handle);
            return 1;
        }

        // Normalize headers (remove BOM, trim, lowercase)
        $headers = array_map(function($header) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header)));
        }, $headers);

        $this->info("Detected columns: " . implode(', ', $headers));
        $this->newLine();

        // Detect CSV format
        $csvFormat = $this->detectCsvFormat($headers);
        $this->info("Detected format: {$csvFormat}");
        $this->newLine();

        // Process rows
        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $this->stats['rows_processed']++;

            try {
                $data = array_combine($headers, $row);

                if ($csvFormat === 'campaign_insights') {
                    $this->processCampaignInsightRow($data, $integration, $adAccount, $isDryRun);
                } elseif ($csvFormat === 'ad_insights') {
                    $this->processAdInsightRow($data, $integration, $adAccount, $isDryRun);
                } else {
                    $this->processGenericRow($data, $integration, $adAccount, $isDryRun);
                }

            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('CSV import row error', [
                    'row' => $this->stats['rows_processed'],
                    'error' => $e->getMessage(),
                    'data' => $data ?? null
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        fclose($handle);

        // Display summary
        $this->displaySummary($isDryRun);

        return 0;
    }

    private function getIntegration()
    {
        $integrationId = $this->option('integration-id');

        if ($integrationId) {
            return Integration::where('id', $integrationId)
                ->where('platform', 'facebook')
                ->first();
        }

        // Get first active Facebook integration
        return Integration::where('platform', 'facebook')
            ->where('status', 'active')
            ->first();
    }

    private function getOrCreateAdAccount(Integration $integration, string $externalAccountId, bool $isDryRun)
    {
        $adAccount = AdAccount::where('integration_id', $integration->id)
            ->where('external_account_id', $externalAccountId)
            ->first();

        if (!$adAccount && !$isDryRun) {
            $this->info("Creating new ad account: {$externalAccountId}");

            $adAccount = AdAccount::create([
                'tenant_id' => $integration->tenant_id,
                'integration_id' => $integration->id,
                'external_account_id' => $externalAccountId,
                'account_name' => $externalAccountId,
                'currency' => 'SAR', // Will be updated from CSV if available
                'status' => 'active',
            ]);
        }

        return $adAccount;
    }

    private function detectCsvFormat(array $headers): string
    {
        // Check for campaign-level insights
        if (in_array('campaign name', $headers) && in_array('reach', $headers)) {
            return 'campaign_insights';
        }

        // Check for ad-level insights
        if (in_array('ad name', $headers) || in_array('ad id', $headers)) {
            return 'ad_insights';
        }

        return 'generic';
    }

    private function processCampaignInsightRow(array $data, Integration $integration, $adAccount, bool $isDryRun)
    {
        // Extract campaign info
        $campaignName = $data['campaign name'] ?? $data['campaign_name'] ?? null;
        $campaignId = $data['campaign id'] ?? $data['campaign_id'] ?? null;
        $date = $this->parseDate($data);

        if (!$campaignName || !$date) {
            $this->stats['skipped']++;
            return;
        }

        // Get or create campaign
        $campaign = $this->getOrCreateCampaign($integration, $adAccount, $campaignId, $campaignName, $isDryRun);

        if (!$campaign) {
            $this->stats['skipped']++;
            return;
        }

        // Parse metrics
        $spend = $this->parseNumber($data['amount spent (sar)'] ?? $data['amount spent'] ?? $data['spend'] ?? 0);
        $impressions = $this->parseNumber($data['impressions'] ?? 0);
        $reach = $this->parseNumber($data['reach'] ?? 0);
        $clicks = $this->parseNumber($data['link clicks'] ?? $data['clicks'] ?? 0);

        // Parse conversions from various possible columns
        $conversions = 0;
        $leads = 0;
        $purchases = 0;

        if (isset($data['results'])) {
            $conversions += $this->parseNumber($data['results']);
        }
        if (isset($data['leads'])) {
            $leads += $this->parseNumber($data['leads']);
        }
        if (isset($data['purchases'])) {
            $purchases += $this->parseNumber($data['purchases']);
        }

        // Save metric
        if (!$isDryRun) {
            $this->saveMetric($campaign, $date, $spend, $impressions, $reach, $clicks, $conversions, $leads, $purchases);
        }
    }

    private function processAdInsightRow(array $data, Integration $integration, $adAccount, bool $isDryRun)
    {
        // Similar to campaign insights but at ad level
        $campaignName = $data['campaign name'] ?? $data['campaign_name'] ?? 'Unknown Campaign';
        $campaignId = $data['campaign id'] ?? $data['campaign_id'] ?? null;
        $date = $this->parseDate($data);

        if (!$date) {
            $this->stats['skipped']++;
            return;
        }

        $campaign = $this->getOrCreateCampaign($integration, $adAccount, $campaignId, $campaignName, $isDryRun);

        if (!$campaign) {
            $this->stats['skipped']++;
            return;
        }

        // Parse metrics (same as campaign insights)
        $spend = $this->parseNumber($data['amount spent (sar)'] ?? $data['amount spent'] ?? $data['spend'] ?? 0);
        $impressions = $this->parseNumber($data['impressions'] ?? 0);
        $reach = $this->parseNumber($data['reach'] ?? 0);
        $clicks = $this->parseNumber($data['link clicks'] ?? $data['clicks'] ?? 0);

        if (!$isDryRun) {
            $this->saveMetric($campaign, $date, $spend, $impressions, $reach, $clicks, 0, 0, 0);
        }
    }

    private function processGenericRow(array $data, Integration $integration, $adAccount, bool $isDryRun)
    {
        // Flexible parser for various CSV formats
        $this->processCampaignInsightRow($data, $integration, $adAccount, $isDryRun);
    }

    private function getOrCreateCampaign(Integration $integration, $adAccount, $externalCampaignId, string $campaignName, bool $isDryRun)
    {
        if (!$adAccount) {
            return null;
        }

        // Try to find by external ID first
        if ($externalCampaignId) {
            $campaign = AdCampaign::where('ad_account_id', $adAccount->id)
                ->where('external_campaign_id', $externalCampaignId)
                ->first();

            if ($campaign) {
                return $campaign;
            }
        }

        // Try to find by name
        $campaign = AdCampaign::where('ad_account_id', $adAccount->id)
            ->where('name', $campaignName)
            ->first();

        if ($campaign) {
            return $campaign;
        }

        // Create new campaign
        if ($isDryRun) {
            return (object)[
                'id' => null,
                'tenant_id' => $integration->tenant_id,
                'ad_account_id' => $adAccount->id,
                'name' => $campaignName,
            ];
        }

        $this->stats['campaigns_created']++;

        return AdCampaign::create([
            'tenant_id' => $integration->tenant_id,
            'ad_account_id' => $adAccount->id,
            'external_campaign_id' => $externalCampaignId ?? 'csv_' . md5($campaignName),
            'name' => $campaignName,
            'status' => 'active',
            'objective' => 'Unknown',
        ]);
    }

    private function saveMetric($campaign, string $date, float $spend, int $impressions, int $reach, int $clicks, int $conversions, int $leads, int $purchases)
    {
        $checksum = md5("{$campaign->tenant_id}:{$campaign->ad_account_id}:{$campaign->id}:facebook:{$date}");

        $metric = AdMetric::updateOrCreate(
            [
                'tenant_id' => $campaign->tenant_id,
                'ad_account_id' => $campaign->ad_account_id,
                'ad_campaign_id' => $campaign->id,
                'platform' => 'facebook',
                'date' => $date,
            ],
            [
                'spend' => $spend,
                'impressions' => $impressions,
                'reach' => $reach,
                'clicks' => $clicks,
                'conversions' => $conversions + $leads + $purchases,
                'leads' => $leads,
                'purchases' => $purchases,
                'checksum' => $checksum,
                'video_views' => 0,
                'calls' => 0,
                'revenue' => 0,
                'sessions' => 0,
                'atc' => 0,
            ]
        );

        if ($metric->wasRecentlyCreated) {
            $this->stats['metrics_created']++;
        } else {
            $this->stats['metrics_updated']++;
        }
    }

    private function parseDate(array $data): ?string
    {
        // Try various date column names
        $dateFields = ['reporting starts', 'reporting_starts', 'date', 'day', 'date_start', 'reporting start'];

        foreach ($dateFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    return Carbon::parse($data[$field])->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    private function parseNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove currency symbols, commas, spaces
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    private function displaySummary(bool $isDryRun)
    {
        $this->info("=== Import Summary ===");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows Processed', number_format($this->stats['rows_processed'])],
                ['Campaigns Created', number_format($this->stats['campaigns_created'])],
                ['Campaigns Updated', number_format($this->stats['campaigns_updated'])],
                ['Metrics Created', number_format($this->stats['metrics_created'])],
                ['Metrics Updated', number_format($this->stats['metrics_updated'])],
                ['Errors', number_format($this->stats['errors'])],
                ['Skipped', number_format($this->stats['skipped'])],
            ]
        );

        if ($isDryRun) {
            $this->warn("\nDRY RUN MODE - No data was saved to database");
            $this->info("Run without --dry-run to import data");
        } else {
            $this->info("\n✓ Import completed successfully!");

            // Show updated totals
            $totalSpend = AdMetric::sum('spend');
            $this->info("\nTotal spend in database: SAR " . number_format($totalSpend, 2));
            $this->info("In USD: $" . number_format($totalSpend / 3.75, 2));
        }
    }
}
