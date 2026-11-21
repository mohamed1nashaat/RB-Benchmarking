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

class ImportGoogleAdsCsv extends Command
{
    protected $signature = 'google-ads:import-csv
                            {file : Path to the CSV file to import}
                            {--customer-id= : Google Ads Customer ID (e.g., 123-456-7890)}
                            {--tenant-id= : Tenant ID (optional, will use integration\'s tenant)}
                            {--integration-id= : Integration ID (optional)}
                            {--dry-run : Preview import without saving to database}';

    protected $description = 'Import historical Google Ads data from CSV export';

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
        $customerId = $this->option('customer-id');
        $isDryRun = $this->option('dry-run');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("=== Google Ads CSV Import ===");
        $this->info("File: {$filePath}");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Get or find integration
        $integration = $this->getIntegration();
        if (!$integration) {
            $this->error('No Google Ads integration found. Please specify --integration-id or ensure a Google integration exists.');
            return 1;
        }

        $this->info("Using Integration ID: {$integration->id} (Tenant: " . ($integration->tenant?->name ?? 'No Tenant') . ")");

        // Get or create ad account (from --customer-id option)
        $adAccount = null;
        if ($customerId) {
            $adAccount = $this->getOrCreateAdAccount($integration, $customerId, $isDryRun);
            if (!$adAccount && !$isDryRun) {
                $this->error("Failed to get/create ad account: {$customerId}");
                return 1;
            }
        }

        $this->newLine();
        $this->info("Starting CSV import...");
        $this->newLine();

        // Read file with UTF-16 auto-detection
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            $this->error("Could not read file: {$filePath}");
            return 1;
        }
        $content = $this->convertToUtf8($raw);

        // Split into lines and detect delimiter (tab vs comma)
        $lines = explode("\n", $content);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));

        if (empty($lines)) {
            $this->error("File is empty");
            return 1;
        }

        // Skip Google Ads export title/date-range header lines
        // Google exports start with report name ("Campaign report", etc.) then date range ("All time", "Jan 1 - Mar 12, 2026", etc.)
        // The real header row contains column names like "Campaign", "Day", "Clicks"
        $headerIndex = $this->findHeaderRow($lines);
        if ($headerIndex > 0) {
            $this->info("Skipped {$headerIndex} Google Ads header line(s)");
        }

        // Detect delimiter from header row
        $delimiter = $this->detectDelimiter($lines[$headerIndex]);

        // Parse headers
        $headers = str_getcsv($lines[$headerIndex], $delimiter);
        $headers = array_map(function($header) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header)));
        }, $headers);

        $this->info("Delimiter: " . ($delimiter === "\t" ? 'TAB' : 'COMMA'));
        $this->info("Detected columns: " . implode(', ', $headers));
        $this->newLine();

        // Check for multi-account column (MCC exports)
        $hasAccountColumn = $this->hasAccountColumn($headers);
        if ($hasAccountColumn) {
            $this->info("Multi-account CSV detected — will auto-match rows to accounts");
        }

        // Detect CSV format
        $csvFormat = $this->detectCsvFormat($headers);
        $this->info("Detected format: {$csvFormat}");
        $this->newLine();

        // Process data rows
        $dataLines = array_slice($lines, $headerIndex + 1);
        $progressBar = $this->output->createProgressBar(count($dataLines));
        $progressBar->start();

        // Cache for account lookups in multi-account mode
        $accountCache = [];

        foreach ($dataLines as $line) {
            $line = trim($line);
            if ($line === '') {
                $progressBar->advance();
                continue;
            }

            $fields = str_getcsv($line, $delimiter);

            // Pad or trim fields to match header count
            while (count($fields) < count($headers)) {
                $fields[] = '';
            }

            $data = array_combine($headers, array_slice($fields, 0, count($headers)));
            if (!$data) {
                $progressBar->advance();
                continue;
            }

            $this->stats['rows_processed']++;

            try {
                // Multi-account: resolve account from row data
                $rowAccount = $adAccount;
                if ($hasAccountColumn && !$rowAccount) {
                    $rowCustomerId = $this->extractCustomerIdFromRow($data);
                    if ($rowCustomerId) {
                        $normalizedId = str_replace('-', '', $rowCustomerId);
                        if (!isset($accountCache[$normalizedId])) {
                            $accountCache[$normalizedId] = $this->getOrCreateAdAccount($integration, $rowCustomerId, $isDryRun);
                        }
                        $rowAccount = $accountCache[$normalizedId];
                    }
                }

                if ($csvFormat === 'campaign_performance') {
                    $this->processCampaignPerformanceRow($data, $integration, $rowAccount, $isDryRun);
                } else {
                    $this->processGenericRow($data, $integration, $rowAccount, $isDryRun);
                }

            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('Google Ads CSV import row error', [
                    'row' => $this->stats['rows_processed'],
                    'error' => $e->getMessage(),
                    'data' => $data ?? null
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($isDryRun);

        return 0;
    }

    private function getIntegration()
    {
        $integrationId = $this->option('integration-id');

        if ($integrationId) {
            return Integration::where('id', $integrationId)
                ->where('platform', 'google')
                ->first();
        }

        // Get first active Google integration
        return Integration::where('platform', 'google')
            ->where('status', 'active')
            ->first();
    }

    private function convertToUtf8(string $content): string
    {
        // UTF-16 LE BOM
        if (str_starts_with($content, "\xFF\xFE")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        }
        // UTF-16 BE BOM
        if (str_starts_with($content, "\xFE\xFF")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }
        // UTF-16 without BOM (null bytes present)
        if (str_contains(substr($content, 0, 100), "\x00")) {
            return mb_convert_encoding($content, 'UTF-8', 'UTF-16');
        }
        // UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        return $content;
    }

    private function detectDelimiter(string $headerLine): string
    {
        $tabCount = substr_count($headerLine, "\t");
        $commaCount = substr_count($headerLine, ',');

        return $tabCount > $commaCount ? "\t" : ',';
    }

    private function findHeaderRow(array $lines): int
    {
        // Google Ads exports prepend title + date-range lines before the actual column headers.
        // The header row contains known column names like "campaign", "day", "clicks", "impr.", "cost".
        $knownColumns = ['campaign', 'day', 'clicks', 'impr.', 'cost', 'impressions', 'date', 'spend'];

        foreach ($lines as $i => $line) {
            $lower = strtolower($line);
            $matches = 0;
            foreach ($knownColumns as $col) {
                if (str_contains($lower, $col)) {
                    $matches++;
                }
            }
            // If at least 2 known column names found, treat as header
            if ($matches >= 2) {
                return $i;
            }
        }

        return 0; // fallback: first line is header
    }

    private function hasAccountColumn(array $headers): bool
    {
        $accountColumns = [
            'account external customer id',
            'customer id',
            'account id',
            'client customer id',
        ];
        foreach ($accountColumns as $col) {
            if (in_array($col, $headers)) {
                return true;
            }
        }
        return false;
    }

    private function extractCustomerIdFromRow(array $data): ?string
    {
        $columns = [
            'account external customer id',
            'customer id',
            'account id',
            'client customer id',
        ];
        foreach ($columns as $col) {
            if (isset($data[$col]) && !empty(trim($data[$col]))) {
                return trim($data[$col]);
            }
        }
        return null;
    }

    private function getOrCreateAdAccount(Integration $integration, string $customerId, bool $isDryRun): ?AdAccount
    {
        // Normalize customer ID (remove dashes)
        $normalizedId = str_replace('-', '', $customerId);

        // Try to find existing account
        $account = AdAccount::where('integration_id', $integration->id)
            ->where('external_account_id', $normalizedId)
            ->first();

        if ($account) {
            $this->info("Found existing ad account: {$account->account_name} ({$normalizedId})");
            return $account;
        }

        if ($isDryRun) {
            $this->warn("Dry run: Would create ad account for customer ID: {$customerId}");
            return null;
        }

        // Create new account
        $account = AdAccount::create([
            'tenant_id' => $integration->tenant_id,
            'integration_id' => $integration->id,
            'external_account_id' => $normalizedId,
            'account_name' => "Google Ads {$customerId}",
            'status' => 'active',
        ]);

        $this->info("Created new ad account: {$account->account_name} ({$normalizedId})");
        return $account;
    }

    private function detectCsvFormat(array $headers): string
    {
        // Check for Google Ads campaign performance report columns
        if (in_array('campaign', $headers) && in_array('day', $headers)) {
            return 'campaign_performance';
        }

        if (in_array('campaign name', $headers) && in_array('date', $headers)) {
            return 'campaign_performance';
        }

        return 'generic';
    }

    private function processCampaignPerformanceRow(array $data, Integration $integration, ?AdAccount $adAccount, bool $isDryRun)
    {
        // Extract campaign name
        $campaignName = $data['campaign'] ?? $data['campaign name'] ?? null;
        if (!$campaignName || $campaignName === 'Total') {
            $this->stats['skipped']++;
            return;
        }

        // Extract date
        $date = $this->extractDate($data);
        if (!$date) {
            $this->stats['skipped']++;
            return;
        }

        // Extract customer ID if available in row
        $customerId = $data['customer id'] ?? $data['account id'] ?? null;
        if ($customerId && !$adAccount) {
            $adAccount = $this->getOrCreateAdAccount($integration, $customerId, $isDryRun);
        }

        if (!$adAccount) {
            $this->stats['skipped']++;
            return;
        }

        // Get or create campaign
        $campaign = $this->getOrCreateCampaign($campaignName, $data, $adAccount, $isDryRun);
        if (!$campaign) {
            $this->stats['skipped']++;
            return;
        }

        // Extract metrics
        $metrics = $this->extractMetrics($data);

        if ($isDryRun) {
            $this->stats['metrics_created']++;
            return;
        }

        // Create or update metric
        $metricData = [
            'ad_campaign_id' => $campaign->id,
            'date' => $date,
            'impressions' => $metrics['impressions'] ?? 0,
            'clicks' => $metrics['clicks'] ?? 0,
            'spend' => $metrics['spend'] ?? 0,
            'conversions' => $metrics['conversions'] ?? 0,
            'reach' => $metrics['reach'] ?? null,
            'video_views' => $metrics['video_views'] ?? null,
            'engagement' => $metrics['engagement'] ?? null,
        ];

        $existing = AdMetric::where('ad_campaign_id', $campaign->id)
            ->where('date', $date)
            ->first();

        if ($existing) {
            $existing->update($metricData);
            $this->stats['metrics_updated']++;
        } else {
            AdMetric::create($metricData);
            $this->stats['metrics_created']++;
        }
    }

    private function processGenericRow(array $data, Integration $integration, ?AdAccount $adAccount, bool $isDryRun)
    {
        // Fallback for unknown formats
        $this->processCampaignPerformanceRow($data, $integration, $adAccount, $isDryRun);
    }

    private function getOrCreateCampaign(string $campaignName, array $data, AdAccount $adAccount, bool $isDryRun): ?AdCampaign
    {
        // Extract campaign ID if available
        $campaignId = $data['campaign id'] ?? null;

        // Try to find existing campaign
        $campaign = AdCampaign::where('ad_account_id', $adAccount->id)
            ->where(function($query) use ($campaignName, $campaignId) {
                $query->where('name', $campaignName);
                if ($campaignId) {
                    $query->orWhere('external_campaign_id', $campaignId);
                }
            })
            ->first();

        if ($campaign) {
            $this->stats['campaigns_updated']++;
            return $campaign;
        }

        if ($isDryRun) {
            $this->stats['campaigns_created']++;
            return null;
        }

        // Create new campaign
        $campaign = AdCampaign::create([
            'ad_account_id' => $adAccount->id,
            'external_campaign_id' => $campaignId ?? 'csv_' . md5($campaignName),
            'name' => $campaignName,
            'status' => 'active',
            'objective' => null,
        ]);

        $this->stats['campaigns_created']++;
        return $campaign;
    }

    private function extractDate(array $data): ?string
    {
        // Try various date column names (Google Ads and generic formats)
        $dateColumns = [
            'day',
            'date',
            'date (mm/dd/yyyy)',
            'date (yyyy-mm-dd)',
            'reporting starts',
            'reporting_starts',
            'segment.date',
            'segments.date',
        ];

        foreach ($dateColumns as $col) {
            if (isset($data[$col]) && !empty($data[$col])) {
                try {
                    // Handle various date formats
                    $dateStr = trim($data[$col]);

                    // Skip if it's a summary row
                    if (in_array(strtolower($dateStr), ['total', '--', 'n/a'])) {
                        continue;
                    }

                    $date = Carbon::parse($dateStr)->format('Y-m-d');
                    return $date;
                } catch (\Exception $e) {
                    Log::debug('Date parse failed', [
                        'column' => $col,
                        'value' => $data[$col] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return null;
    }

    private function extractMetrics(array $data): array
    {
        $metrics = [];

        // Impressions
        $metrics['impressions'] = $this->parseNumber($data['impr.'] ?? $data['impressions'] ?? 0);

        // Clicks
        $metrics['clicks'] = $this->parseNumber($data['clicks'] ?? 0);

        // Spend (Cost) with currency conversion
        $cost = $data['cost'] ?? $data['spend'] ?? $data['amount spent'] ?? 0;
        $costValue = $this->parseNumber($cost);

        // Detect and convert currency to SAR
        $currency = $this->detectCurrency($data);
        $metrics['spend'] = $this->convertToSAR($costValue, $currency);

        // Conversions
        $metrics['conversions'] = $this->parseNumber($data['conversions'] ?? $data['conv.'] ?? 0);

        // Optional metrics
        $metrics['reach'] = isset($data['reach']) ? $this->parseNumber($data['reach']) : null;
        $metrics['video_views'] = isset($data['video views']) ? $this->parseNumber($data['video views']) : null;
        $metrics['engagement'] = isset($data['engagements']) ? $this->parseNumber($data['engagements']) : null;

        return $metrics;
    }

    private function detectCurrency(array $data): string
    {
        // Check for explicit currency column
        $currencyColumns = ['currency', 'currency code', 'account currency'];

        foreach ($currencyColumns as $col) {
            if (isset($data[$col]) && !empty($data[$col])) {
                return strtoupper(trim($data[$col]));
            }
        }

        // Try to detect from cost field format
        $cost = $data['cost'] ?? $data['spend'] ?? $data['amount spent'] ?? '';
        if (is_string($cost)) {
            if (strpos($cost, 'SAR') !== false || strpos($cost, 'ر.س') !== false) {
                return 'SAR';
            }
            if (strpos($cost, 'USD') !== false || strpos($cost, '$') !== false) {
                return 'USD';
            }
            if (strpos($cost, 'TRY') !== false || strpos($cost, '₺') !== false) {
                return 'TRY';
            }
            if (strpos($cost, 'EUR') !== false || strpos($cost, '€') !== false) {
                return 'EUR';
            }
        }

        // Default to USD if cannot detect
        return 'USD';
    }

    private function convertToSAR(float $amount, string $currency): float
    {
        // Conversion rates to SAR
        $rates = [
            'SAR' => 1.0,
            'USD' => 3.75,
            'TRY' => 0.12,  // Turkish Lira
            'EUR' => 4.10,
            'GBP' => 4.85,
            'AED' => 1.02,  // UAE Dirham
        ];

        $rate = $rates[$currency] ?? 1.0;

        if ($rate !== 1.0) {
            Log::info('Currency conversion', [
                'original_amount' => $amount,
                'currency' => $currency,
                'rate' => $rate,
                'converted_sar' => $amount * $rate,
            ]);
        }

        return $amount * $rate;
    }

    private function parseNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove currency symbols, commas, spaces
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);

        return floatval($cleaned);
    }

    private function displaySummary(bool $isDryRun)
    {
        $this->newLine();
        $this->info("=== Import Summary ===");
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows Processed', number_format($this->stats['rows_processed'])],
                ['Campaigns Created', $this->stats['campaigns_created']],
                ['Campaigns Updated', $this->stats['campaigns_updated']],
                ['Metrics Created', number_format($this->stats['metrics_created'])],
                ['Metrics Updated', number_format($this->stats['metrics_updated'])],
                ['Errors', $this->stats['errors']],
                ['Skipped', $this->stats['skipped']],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('This was a DRY RUN - no data was written to the database.');
            $this->info('Run without --dry-run to actually import the data.');
        } else {
            $this->info('Import completed successfully!');
        }
    }
}
