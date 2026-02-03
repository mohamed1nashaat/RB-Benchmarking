<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DiagnoseFacebookPermissions extends Command
{
    protected $signature = 'facebook:diagnose-permissions {--limit=20 : Number of accounts to test}';
    protected $description = 'Test Facebook API access for failing accounts to diagnose permission issues';

    public function handle()
    {
        $this->info("=== Testing Facebook API Access for Failing Accounts ===\n");

        // Get integration and access token
        $integration = Integration::find(4);
        $config = is_string($integration->app_config) ? json_decode($integration->app_config, true) : $integration->app_config;
        $accessToken = $config['access_token'];

        $this->info("Using Integration ID: 4 (Demo Company)");
        $this->info("Access Token: " . substr($accessToken, 0, 20) . "...\n");

        // Get accounts without metrics that have campaigns
        $failingAccountIds = AdAccount::whereNotIn('id', function($query) {
            $query->select('ad_account_id')->distinct()->from('ad_metrics')->where('platform', 'facebook');
        })->pluck('id')->toArray();

        $results = [
            'success' => [],
            'empty_data' => [],
            'api_error' => [],
            'no_campaigns' => []
        ];

        $testCount = 0;
        $maxTests = $this->option('limit');

        foreach ($failingAccountIds as $accountId) {
            if ($testCount >= $maxTests) break;

            $account = AdAccount::find($accountId);
            if (!$account) continue;

            // Get a campaign for this account
            $campaign = AdCampaign::where('ad_account_id', $accountId)
                ->where('channel_type', 'Facebook')
                ->first();

            if (!$campaign) {
                $results['no_campaigns'][] = [
                    'account_id' => $accountId,
                    'account_name' => $account->account_name,
                    'tenant' => $account->tenant->name
                ];
                continue;
            }

            $testCount++;
            $this->line("\nTesting {$testCount}/{$maxTests}: <fg=cyan>{$account->tenant->name}</> - {$account->account_name}");
            $this->line("  Campaign: {$campaign->name}");
            $this->line("  External ID: {$campaign->external_campaign_id}");
            $this->line("  Status: {$campaign->status}");

            // Test Facebook API call
            $url = "https://graph.facebook.com/v23.0/{$campaign->external_campaign_id}/insights";
            $params = [
                'access_token' => $accessToken,
                'time_range' => json_encode([
                    'since' => '2023-01-01',
                    'until' => date('Y-m-d')
                ]),
                'time_increment' => 1,
                'level' => 'campaign',
                'fields' => 'date_start,impressions,spend,clicks',
                'limit' => 5
            ];

            try {
                $response = Http::timeout(10)->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $metricsData = $data['data'] ?? [];

                    if (empty($metricsData)) {
                        $this->warn("  ⚠ Result: SUCCESS but EMPTY DATA (campaign may have no spend in date range)");
                        $results['empty_data'][] = [
                            'account_id' => $accountId,
                            'account_name' => $account->account_name,
                            'tenant' => $account->tenant->name,
                            'campaign' => $campaign->name,
                            'external_id' => $campaign->external_campaign_id,
                            'status' => $campaign->status
                        ];
                    } else {
                        $firstMetric = $metricsData[0];
                        $spend = $firstMetric['spend'] ?? 0;
                        $impressions = $firstMetric['impressions'] ?? 0;

                        $this->info("  ✓ Result: SUCCESS - Found " . count($metricsData) . " metrics!");
                        $this->line("    Sample: Date {$firstMetric['date_start']}, Spend: \${$spend}, Impressions: {$impressions}");

                        $results['success'][] = [
                            'account_id' => $accountId,
                            'account_name' => $account->account_name,
                            'tenant' => $account->tenant->name,
                            'campaign' => $campaign->name,
                            'metrics_count' => count($metricsData),
                            'sample_spend' => $spend
                        ];
                    }
                } else {
                    $error = $response->json();
                    $errorMsg = $error['error']['message'] ?? 'Unknown error';
                    $errorCode = $error['error']['code'] ?? 'N/A';
                    $errorType = $error['error']['type'] ?? 'N/A';

                    $this->error("  ✗ Result: API ERROR");
                    $this->error("    Code: {$errorCode} ({$errorType})");
                    $this->error("    Message: {$errorMsg}");

                    $results['api_error'][] = [
                        'account_id' => $accountId,
                        'account_name' => $account->account_name,
                        'tenant' => $account->tenant->name,
                        'campaign' => $campaign->name,
                        'external_id' => $campaign->external_campaign_id,
                        'error_code' => $errorCode,
                        'error_type' => $errorType,
                        'error_message' => $errorMsg
                    ];
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Result: EXCEPTION - {$e->getMessage()}");
                $results['api_error'][] = [
                    'account_id' => $accountId,
                    'account_name' => $account->account_name,
                    'tenant' => $account->tenant->name,
                    'exception' => $e->getMessage()
                ];
            }

            usleep(500000); // 0.5 second delay between requests
        }

        // Print summary
        $this->newLine(2);
        $this->info("=== TEST SUMMARY ===");
        $this->newLine();
        $this->line("<fg=green>✓ API Success (has metrics):</> " . count($results['success']));
        $this->line("<fg=yellow>⚠ API Success but Empty Data:</> " . count($results['empty_data']));
        $this->line("<fg=red>✗ API Errors:</> " . count($results['api_error']));
        $this->line("<fg=gray>○ No Campaigns:</> " . count($results['no_campaigns']));

        if (!empty($results['success'])) {
            $this->newLine();
            $this->warn("--- Accounts That WORKED (but have no metrics in database!) ---");
            foreach ($results['success'] as $item) {
                $this->line("  • {$item['tenant']} - {$item['account_name']}");
                $this->line("    Campaign: {$item['campaign']}");
                $this->line("    Metrics found: {$item['metrics_count']}, Sample spend: \${$item['sample_spend']}");
            }
            $this->newLine();
            $this->error("⚠ ACTION REQUIRED: These accounts CAN be synced but aren't!");
            $this->error("   Run backfill for these specific account IDs:");
            foreach ($results['success'] as $item) {
                $this->line("   php artisan facebook:backfill-metrics --account-id={$item['account_id']}");
            }
        }

        if (!empty($results['empty_data'])) {
            $this->newLine();
            $this->info("--- Accounts with Empty Data (may have no spend in date range) ---");
            foreach ($results['empty_data'] as $item) {
                $this->line("  • {$item['tenant']} - {$item['account_name']}");
                $this->line("    Campaign: {$item['campaign']} ({$item['status']})");
            }
        }

        if (!empty($results['api_error'])) {
            $this->newLine();
            $this->error("--- Accounts with API Errors (permission issues) ---");
            $errorTypes = [];
            foreach ($results['api_error'] as $item) {
                $this->line("  • {$item['tenant']} - {$item['account_name']}");
                if (isset($item['error_code'])) {
                    $this->line("    Error {$item['error_code']} ({$item['error_type']}): {$item['error_message']}");
                    $errorTypes[$item['error_code']] = ($errorTypes[$item['error_code']] ?? 0) + 1;
                } else {
                    $this->line("    Exception: {$item['exception']}");
                }
            }

            if (!empty($errorTypes)) {
                $this->newLine();
                $this->line("Error Code Distribution:");
                foreach ($errorTypes as $code => $count) {
                    $this->line("  Code {$code}: {$count} accounts");
                }
            }
        }

        // Save results to JSON file
        $jsonResults = json_encode($results, JSON_PRETTY_PRINT);
        file_put_contents('/tmp/facebook-permission-test-results.json', $jsonResults);

        $this->newLine();
        $this->info("✓ Full results saved to: /tmp/facebook-permission-test-results.json");

        return 0;
    }
}
