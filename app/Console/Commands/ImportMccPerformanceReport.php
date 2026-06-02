<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportMccPerformanceReport extends Command
{
    protected $signature = 'google-ads:import-mcc-report
                            {file : Path to the MCC Performance Report CSV (UTF-16 tab-delimited)}
                            {--dry-run : Preview import without saving to database}
                            {--integration-id=21 : Google Ads integration ID}';

    protected $description = 'Import account-level aggregate metrics from Google Ads MCC Performance Report CSV';

    private array $stats = [
        'rows_processed' => 0,
        'accounts_matched' => 0,
        'accounts_created' => 0,
        'campaigns_created' => 0,
        'metrics_created' => 0,
        'skipped_no_spend' => 0,
        'skipped_manager' => 0,
        'skipped_total_row' => 0,
        'errors' => 0,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $integrationId = (int) $this->option('integration-id');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $integration = Integration::find($integrationId);
        if (!$integration) {
            $this->error("Integration ID {$integrationId} not found.");
            return 1;
        }

        $this->info("=== MCC Performance Report Import ===");
        $this->info("File: {$filePath}");
        $this->info("Integration: #{$integration->id} ({$integration->platform})");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Read file, convert UTF-16 to UTF-8
        $raw = file_get_contents($filePath);
        $content = $this->convertToUtf8($raw);

        // Split into lines and skip header lines ("Performance report", "All time")
        $lines = explode("\n", $content);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));

        // Find the header row (first row containing "Account" tab-separated columns)
        $headerIndex = null;
        foreach ($lines as $i => $line) {
            if (stripos($line, "Account Status") !== false && stripos($line, "Clicks") !== false) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            $this->error("Could not find column header row (expected 'Account Status' + 'Clicks').");
            return 1;
        }

        // Parse headers
        $headers = str_getcsv($lines[$headerIndex], "\t");
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $this->info("Detected " . count($headers) . " columns: " . implode(', ', $headers));
        $this->newLine();

        // Process data rows
        $dataLines = array_slice($lines, $headerIndex + 1);

        foreach ($dataLines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $fields = str_getcsv($line, "\t");

            // Pad fields if shorter than headers
            while (count($fields) < count($headers)) {
                $fields[] = '';
            }

            $data = array_combine($headers, array_slice($fields, 0, count($headers)));
            if (!$data) {
                continue;
            }

            $this->stats['rows_processed']++;

            try {
                $this->processRow($data, $integration, $isDryRun);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("Row error: {$e->getMessage()}");
                Log::error('MCC report import row error', [
                    'row' => $this->stats['rows_processed'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->displaySummary($isDryRun);

        return 0;
    }

    private function processRow(array $data, Integration $integration, bool $isDryRun): void
    {
        $accountName = trim($data['account name'] ?? '');
        $accountStatus = trim($data['account status'] ?? '');
        $customerId = trim($data['account external customer id'] ?? '');
        $accountType = trim($data['account type'] ?? '');

        // Skip total/summary rows
        if (str_starts_with($accountName, 'Total:') || str_starts_with($accountStatus, 'Total:')) {
            $this->stats['skipped_total_row']++;
            return;
        }

        // Skip manager account rows
        if (stripos($accountType, 'Manager') !== false) {
            $this->stats['skipped_manager']++;
            return;
        }

        // Normalize customer ID (strip dashes)
        $externalId = str_replace('-', '', $customerId);
        if (empty($externalId)) {
            $this->stats['skipped_total_row']++;
            return;
        }

        // Parse metrics
        $clicks = $this->parseNumber($data['clicks'] ?? '0');
        $impressions = $this->parseNumber($data['impr.'] ?? '0');
        $cost = $this->parseNumber($data['cost'] ?? '0');
        $conversions = $this->parseNumber($data['conversions'] ?? '0');
        $currency = strtoupper(trim($data['currency code'] ?? 'USD'));
        $ctr = trim($data['ctr'] ?? '');
        $avgCpc = $this->parseNumber($data['avg. cpc'] ?? '0');

        // Skip rows with no spend and no clicks
        if ($clicks == 0 && $cost == 0) {
            $this->stats['skipped_no_spend']++;
            if ($isDryRun) {
                $this->line("  SKIP (no spend/clicks): {$accountName} [{$externalId}]");
            }
            return;
        }

        // Find or create AdAccount
        $adAccount = AdAccount::where('integration_id', $integration->id)
            ->where('external_account_id', $externalId)
            ->first();

        $isNew = false;
        if ($adAccount) {
            $this->stats['accounts_matched']++;
        } else {
            $this->stats['accounts_created']++;
            $isNew = true;
        }

        $statusMap = [
            'active' => 'active',
            'inactive' => 'canceled',
        ];
        $mappedStatus = $statusMap[strtolower($accountStatus)] ?? 'canceled';

        if ($isDryRun) {
            $action = $isNew ? 'CREATE' : 'UPDATE';
            $this->line("  {$action} Account: {$accountName} [{$externalId}] ({$mappedStatus}) — {$currency} {$cost} spend, {$clicks} clicks, {$impressions} impr, {$conversions} conv");
            return;
        }

        // Create account if needed
        if ($isNew) {
            $adAccount = AdAccount::create([
                'tenant_id' => null,
                'integration_id' => $integration->id,
                'external_account_id' => $externalId,
                'account_name' => $accountName,
                'currency' => $currency,
                'status' => $mappedStatus,
            ]);
            $this->info("  Created account: {$accountName} [{$externalId}]");
        }

        // Store aggregate in account_config
        $config = $adAccount->account_config ?? [];
        $config['csv_aggregate'] = [
            'clicks' => $clicks,
            'impressions' => $impressions,
            'cost' => $cost,
            'conversions' => $conversions,
            'currency' => $currency,
            'ctr' => $ctr,
            'avg_cpc' => $avgCpc,
            'imported_at' => now()->toISOString(),
            'source' => 'mcc_performance_report',
        ];
        $adAccount->update([
            'account_config' => $config,
            'currency' => $currency,
        ]);

        // Create placeholder campaign + metric for accounts with spend
        if ($cost > 0) {
            $this->createPlaceholderCampaignAndMetric($adAccount, $accountName, [
                'clicks' => (int) $clicks,
                'impressions' => (int) $impressions,
                'spend' => $cost,
                'conversions' => (int) $conversions,
            ]);
        }
    }

    private function createPlaceholderCampaignAndMetric(AdAccount $adAccount, string $accountName, array $metrics): void
    {
        $placeholderName = "{$accountName} - Historical (CSV)";

        // Idempotent: check for existing placeholder campaign
        $campaign = AdCampaign::where('ad_account_id', $adAccount->id)
            ->where('name', 'like', '%Historical (CSV)%')
            ->first();

        if (!$campaign) {
            $campaign = AdCampaign::create([
                'ad_account_id' => $adAccount->id,
                'tenant_id' => $adAccount->tenant_id,
                'external_campaign_id' => 'csv_mcc_' . $adAccount->external_account_id,
                'name' => $placeholderName,
                'status' => 'archived',
                'objective' => null,
            ]);
            $this->stats['campaigns_created']++;
            $this->info("    Created placeholder campaign: {$placeholderName}");
        }

        // Idempotent: check for existing metric on placeholder date
        $placeholderDate = '2020-01-01';
        $existing = AdMetric::where('ad_campaign_id', $campaign->id)
            ->where('ad_account_id', $adAccount->id)
            ->where('date', $placeholderDate)
            ->first();

        if ($existing) {
            $existing->update([
                'clicks' => $metrics['clicks'],
                'impressions' => $metrics['impressions'],
                'spend' => $metrics['spend'],
                'conversions' => $metrics['conversions'],
            ]);
            $this->line("    Updated existing metric for {$placeholderDate}");
        } else {
            AdMetric::create([
                'ad_campaign_id' => $campaign->id,
                'ad_account_id' => $adAccount->id,
                'tenant_id' => $adAccount->tenant_id,
                'platform' => 'google',
                'date' => $placeholderDate,
                'clicks' => $metrics['clicks'],
                'impressions' => $metrics['impressions'],
                'spend' => $metrics['spend'],
                'conversions' => $metrics['conversions'],
            ]);
            $this->stats['metrics_created']++;
            $this->info("    Created metric: spend={$metrics['spend']}, clicks={$metrics['clicks']}, impr={$metrics['impressions']}, conv={$metrics['conversions']}");
        }
    }

    private function convertToUtf8(string $content): string
    {
        // Detect BOM and encoding
        if (str_starts_with($content, "\xFF\xFE")) {
            // UTF-16 LE BOM
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        }
        if (str_starts_with($content, "\xFE\xFF")) {
            // UTF-16 BE BOM
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }
        // Check for null bytes (UTF-16 without BOM)
        if (str_contains(substr($content, 0, 100), "\x00")) {
            return mb_convert_encoding($content, 'UTF-8', 'UTF-16');
        }
        // UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }

        return $content;
    }

    private function parseNumber(string $value): float
    {
        $value = trim($value);
        if ($value === '' || $value === '--' || $value === ' --') {
            return 0;
        }

        // Remove quotes, commas, currency symbols, percent signs, spaces
        $cleaned = str_replace(['"', ',', '%', ' '], '', $value);
        $cleaned = preg_replace('/[^0-9.\-]/', '', $cleaned);

        return floatval($cleaned);
    }

    private function displaySummary(bool $isDryRun): void
    {
        $this->newLine();
        $this->info("=== Import Summary ===");
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows Processed', $this->stats['rows_processed']],
                ['Accounts Matched (existing)', $this->stats['accounts_matched']],
                ['Accounts Created (new)', $this->stats['accounts_created']],
                ['Placeholder Campaigns Created', $this->stats['campaigns_created']],
                ['Metrics Created', $this->stats['metrics_created']],
                ['Skipped (no spend/clicks)', $this->stats['skipped_no_spend']],
                ['Skipped (manager rows)', $this->stats['skipped_manager']],
                ['Skipped (total/summary rows)', $this->stats['skipped_total_row']],
                ['Errors', $this->stats['errors']],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN — no data was written to the database.');
            $this->info('Run without --dry-run to import.');
        } else {
            $this->info('Import completed successfully!');
        }
    }
}
