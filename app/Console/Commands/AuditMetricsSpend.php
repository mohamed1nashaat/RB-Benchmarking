<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Services\GoogleAdsService;
use App\Services\SnapchatAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditMetricsSpend extends Command
{
    protected $signature = 'metrics:audit
                            {--start-date= : Start date (Y-m-d), defaults to first of current month}
                            {--end-date= : End date (Y-m-d), defaults to today}
                            {--platform= : Filter by platform (facebook, google, tiktok, snapchat, linkedin)}
                            {--account-id= : Specific ad account ID}
                            {--fix : Re-sync mismatched accounts}
                            {--threshold=1 : Minimum SAR difference to flag}';

    protected $description = 'Audit DB metrics spend vs platform API totals and optionally re-sync mismatched accounts';

    public function handle(): int
    {
        $startDate = $this->option('start-date') ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->option('end-date') ?? Carbon::now()->format('Y-m-d');
        $platformFilter = $this->option('platform');
        $accountIdFilter = $this->option('account-id');
        $fix = $this->option('fix');
        $threshold = (float) $this->option('threshold');

        $this->info("Metrics Audit: {$startDate} to {$endDate}");
        $this->info("Threshold: {$threshold} | Fix: " . ($fix ? 'Yes' : 'No'));
        $this->newLine();

        // Load accounts with spend in the date range
        $query = AdAccount::query()
            ->with(['integration', 'integration.tenant'])
            ->whereHas('adMetrics', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->where('spend', '>', 0);
            });

        if ($platformFilter) {
            $query->whereHas('integration', fn ($q) => $q->where('platform', $platformFilter));
        }

        if ($accountIdFilter) {
            $query->where('id', $accountIdFilter);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No accounts with spend found for the given date range.');
            return 0;
        }

        $this->info("Found {$accounts->count()} account(s) with spend in date range.");
        $this->newLine();

        // Group by integration to reuse tokens
        $grouped = $accounts->groupBy('integration_id');

        // Track Google Ads external IDs to deduplicate (same ext ID exists across integrations 15+21)
        $googleAudited = [];

        $results = [];
        $mismatched = [];

        foreach ($grouped as $integrationId => $integrationAccounts) {
            $integration = $integrationAccounts->first()->integration;

            if (!$integration) {
                $this->warn("Integration {$integrationId} not found, skipping.");
                continue;
            }

            $platform = $integration->platform;
            $this->info("--- Integration #{$integration->id} ({$platform}) ---");

            foreach ($integrationAccounts as $account) {
                $dbSpend = (float) AdMetric::where('ad_account_id', $account->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('spend');

                $apiSpend = null;
                $error = null;

                try {
                    switch ($platform) {
                        case 'facebook':
                            $apiSpend = $this->fetchFacebookSpend($integration, $account, $startDate, $endDate);
                            break;

                        case 'google':
                            // Deduplicate: same external_account_id exists across integrations 15+21
                            // Each DB account has identical metrics, so only audit one per external ID
                            $extId = $account->external_account_id;
                            if (isset($googleAudited[$extId])) {
                                continue 3; // skip this account entirely (already reported)
                            }
                            $googleAudited[$extId] = true;

                            $apiSpend = $this->fetchGoogleSpend($integration, $account, $startDate, $endDate);
                            // DB spend stays as single-account (not summed across duplicates)
                            break;

                        case 'tiktok':
                            $apiSpend = $this->fetchTikTokSpend($integration, $account, $startDate, $endDate);
                            break;

                        case 'snapchat':
                            $apiSpend = $this->fetchSnapchatSpend($integration, $account, $startDate, $endDate);
                            break;

                        case 'linkedin':
                            $apiSpend = $this->fetchLinkedInSpend($integration, $account, $startDate, $endDate);
                            break;

                        default:
                            $error = "Unknown platform: {$platform}";
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    Log::error("Audit API error for account {$account->id}", [
                        'platform' => $platform,
                        'error' => $error,
                    ]);
                }

                $diff = $apiSpend !== null ? round($apiSpend - $dbSpend, 2) : null;
                $pctDiff = ($apiSpend !== null && $apiSpend > 0)
                    ? round(($diff / $apiSpend) * 100, 2)
                    : null;

                $isMismatch = $diff !== null && abs($diff) >= $threshold;

                $status = 'ERROR';
                if ($error) {
                    $status = 'ERROR';
                } elseif ($isMismatch) {
                    $status = 'MISMATCH';
                } else {
                    $status = 'OK';
                }

                $results[] = [
                    'account' => $account,
                    'integration' => $integration,
                    'platform' => $platform,
                    'db_spend' => $dbSpend,
                    'api_spend' => $apiSpend,
                    'diff' => $diff,
                    'pct_diff' => $pctDiff,
                    'status' => $status,
                    'error' => $error,
                ];

                if ($isMismatch) {
                    $mismatched[] = end($results);
                }
            }
        }

        // Output results table
        $this->newLine();
        $this->table(
            ['ID', 'Account', 'Platform', 'DB Spend', 'API Spend', 'Diff', '%Diff', 'Status'],
            collect($results)->map(fn ($r) => [
                $r['account']->id,
                mb_substr($r['account']->account_name, 0, 30),
                $r['platform'],
                $r['api_spend'] !== null ? number_format($r['db_spend'], 2) : number_format($r['db_spend'], 2),
                $r['api_spend'] !== null ? number_format($r['api_spend'], 2) : ($r['error'] ? 'ERR' : 'N/A'),
                $r['diff'] !== null ? ($r['diff'] >= 0 ? '+' : '') . number_format($r['diff'], 2) : '-',
                $r['pct_diff'] !== null ? $r['pct_diff'] . '%' : '-',
                $r['status'],
            ])->toArray()
        );

        // Summary
        $okCount = collect($results)->where('status', 'OK')->count();
        $mismatchCount = collect($results)->where('status', 'MISMATCH')->count();
        $errorCount = collect($results)->where('status', 'ERROR')->count();

        $this->newLine();
        $this->info("Summary: {$okCount} OK, {$mismatchCount} mismatched, {$errorCount} errors");

        // Fix mismatched accounts
        if ($fix && !empty($mismatched)) {
            $this->newLine();
            $this->info("=== Re-syncing {$mismatchCount} mismatched account(s) ===");

            foreach ($mismatched as $entry) {
                $this->resyncAccount($entry, $startDate, $endDate);
            }

            $this->newLine();
            $this->info('Re-sync complete. Run metrics:audit again to verify.');
        } elseif (!$fix && !empty($mismatched)) {
            $this->newLine();
            $this->warn("Run with --fix to re-sync mismatched accounts.");
        }

        return $mismatchCount > 0 || $errorCount > 0 ? 1 : 0;
    }

    /**
     * Facebook: GET /{ext_id}/insights?level=account
     */
    private function fetchFacebookSpend(Integration $integration, AdAccount $account, string $start, string $end): ?float
    {
        $config = $integration->getCredentials();
        $token = $config['access_token'] ?? null;

        if (!$token) {
            throw new \Exception('No Facebook access token');
        }

        $extId = $account->external_account_id;

        // Ensure act_ prefix
        if (!str_starts_with($extId, 'act_')) {
            $extId = 'act_' . $extId;
        }

        $response = Http::get("https://graph.facebook.com/v23.0/{$extId}/insights", [
            'access_token' => $token,
            'time_range' => json_encode(['since' => $start, 'until' => $end]),
            'fields' => 'spend',
            'level' => 'account',
        ]);

        if (!$response->successful()) {
            throw new \Exception("Facebook API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $spend = $data['data'][0]['spend'] ?? null;

        return $spend !== null ? (float) $spend : 0.0;
    }

    /**
     * Google Ads: GAQL query for account-level spend via metrics.cost_micros
     */
    private function fetchGoogleSpend(Integration $integration, AdAccount $account, string $start, string $end): ?float
    {
        $config = $integration->getCredentials();
        $refreshToken = $config['refresh_token'] ?? null;
        $accessToken = $config['access_token'] ?? null;

        // Refresh the access token using the refresh token (Google access tokens expire after 1 hour)
        if ($refreshToken) {
            $googleService = app(GoogleAdsService::class);
            $tokenData = $googleService->refreshAccessToken($refreshToken);
            if ($tokenData && isset($tokenData['access_token'])) {
                $accessToken = $tokenData['access_token'];
            }
        }

        if (!$accessToken) {
            throw new \Exception('No Google access token');
        }

        $customerId = $account->account_config['customer_id'] ?? $account->external_account_id;
        $customerId = str_replace('-', '', $customerId);

        $loginCustomerId = $account->account_config['parent_manager_id'] ?? null;
        if ($loginCustomerId) {
            $loginCustomerId = str_replace('-', '', $loginCustomerId);
        }

        $developerToken = config('services.google_ads.developer_token') ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', '');
        if (!$developerToken) {
            throw new \Exception('Google Ads developer token not configured');
        }

        $startFormatted = str_replace('-', '', $start);
        $endFormatted = str_replace('-', '', $end);

        $query = "SELECT metrics.cost_micros FROM campaign WHERE segments.date BETWEEN \"{$startFormatted}\" AND \"{$endFormatted}\"";

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'developer-token' => $developerToken,
        ];

        if ($loginCustomerId) {
            $headers['login-customer-id'] = $loginCustomerId;
        }

        $totalMicros = 0;
        $nextPageToken = null;

        do {
            $body = ['query' => $query];
            if ($nextPageToken) {
                $body['pageToken'] = $nextPageToken;
            }

            $response = Http::withHeaders($headers)
                ->post("https://googleads.googleapis.com/v22/customers/{$customerId}/googleAds:search", $body);

            if (!$response->successful()) {
                throw new \Exception("Google Ads API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();
            foreach ($data['results'] ?? [] as $row) {
                $totalMicros += (int) ($row['metrics']['costMicros'] ?? 0);
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken);

        return round($totalMicros / 1_000_000, 2);
    }

    /**
     * TikTok: POST report/integrated/get/ with AUCTION_ADVERTISER
     */
    private function fetchTikTokSpend(Integration $integration, AdAccount $account, string $start, string $end): ?float
    {
        $config = $integration->getCredentials();
        $token = $config['access_token'] ?? null;

        if (!$token) {
            throw new \Exception('No TikTok access token');
        }

        $extId = $account->external_account_id;

        $response = Http::withHeaders(['Access-Token' => $token])
            ->get('https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/', [
                'advertiser_id' => $extId,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_ADVERTISER',
                'dimensions' => json_encode(['stat_time_day']),
                'metrics' => json_encode(['spend']),
                'start_date' => $start,
                'end_date' => $end,
                'page_size' => 1000,
            ]);

        if (!$response->successful()) {
            throw new \Exception("TikTok API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            throw new \Exception("TikTok API error code {$data['code']}: " . ($data['message'] ?? 'Unknown'));
        }

        $totalSpend = 0.0;
        foreach ($data['data']['list'] ?? [] as $row) {
            $totalSpend += (float) ($row['metrics']['spend'] ?? 0);
        }

        return round($totalSpend, 2);
    }

    /**
     * Snapchat: getCampaignTotalStats() with TOTAL granularity
     */
    private function fetchSnapchatSpend(Integration $integration, AdAccount $account, string $start, string $end): ?float
    {
        $snapchatService = app(SnapchatAdsService::class);

        $token = $snapchatService->ensureValidToken($integration);
        if (!$token) {
            throw new \Exception('No Snapchat access token');
        }

        $extId = $account->external_account_id;

        // Get campaign IDs for this account
        $campaignIds = AdCampaign::where('ad_account_id', $account->id)
            ->pluck('external_campaign_id')
            ->toArray();

        if (empty($campaignIds)) {
            return 0.0;
        }

        // Snapchat end_time is exclusive — add 1 day so the end date's data is included
        $snapEnd = Carbon::parse($end)->addDay()->format('Y-m-d');
        $stats = $snapchatService->getCampaignTotalStats($extId, $token, $campaignIds, $start, $snapEnd);

        $totalSpend = $stats->sum('spend');
        return round($totalSpend, 2);
    }

    /**
     * LinkedIn: analytics API with ALL granularity, summing costInLocalCurrency
     */
    private function fetchLinkedInSpend(Integration $integration, AdAccount $account, string $start, string $end): ?float
    {
        $config = $integration->getCredentials();
        $accessToken = $config['access_token'] ?? null;

        if (!$accessToken) {
            throw new \Exception('No LinkedIn access token');
        }

        // Verify token is still valid before making analytics calls
        $linkedinService = app(\App\Services\LinkedInAdsService::class);
        $verification = $linkedinService->verifyMarketingAccess($accessToken);
        if (!$verification['success']) {
            throw new \Exception('LinkedIn token expired or invalid: ' . ($verification['message'] ?? 'unknown'));
        }

        // Get campaigns for this account
        $campaigns = AdCampaign::where('ad_account_id', $account->id)->get();

        if ($campaigns->isEmpty()) {
            return 0.0;
        }

        $totalSpend = 0.0;

        foreach ($campaigns as $campaign) {
            $metrics = $linkedinService->getCampaignTotalMetrics($integration, $campaign, $start, $end);

            foreach ($metrics as $metric) {
                $totalSpend += (float) ($metric['costInLocalCurrency'] ?? 0);
            }
        }

        return round($totalSpend, 2);
    }

    /**
     * Re-sync a mismatched account using the appropriate platform sync command
     */
    private function resyncAccount(array $entry, string $startDate, string $endDate): void
    {
        $account = $entry['account'];
        $integration = $entry['integration'];
        $platform = $entry['platform'];

        $this->info("Re-syncing #{$account->id} ({$account->account_name}) [{$platform}]...");

        try {
            switch ($platform) {
                case 'facebook':
                    Artisan::call('facebook:sync-metrics', [
                        'integration_id' => $integration->id,
                        '--account-id' => $account->id,
                        '--start-date' => $startDate,
                        '--end-date' => $endDate,
                    ]);
                    break;

                case 'google':
                    Artisan::call('google-ads:sync', [
                        'integration_id' => $integration->id,
                        '--metrics' => true,
                        '--start-date' => $startDate,
                        '--end-date' => $endDate,
                    ]);
                    break;

                case 'tiktok':
                    Artisan::call('tiktok:sync-metrics', [
                        'integration_id' => $integration->id,
                        '--account-id' => $account->id,
                        '--start-date' => $startDate,
                        '--end-date' => $endDate,
                    ]);
                    break;

                case 'snapchat':
                    Artisan::call('snapchat:sync-metrics', [
                        '--integration-id' => $integration->id,
                        '--account-id' => $account->id,
                        '--start-date' => $startDate,
                        '--end-date' => $endDate,
                    ]);
                    break;

                case 'linkedin':
                    // LinkedIn command uses --days, so we calculate the span
                    $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                    Artisan::call('sync:linkedin-campaigns', [
                        '--integration-id' => $integration->id,
                        '--tenant-id' => $account->tenant_id,
                        '--days' => $days,
                    ]);
                    break;
            }

            $this->info("  Done.");
        } catch (\Exception $e) {
            $this->error("  Failed: {$e->getMessage()}");
            Log::error("Audit re-sync failed for account {$account->id}", [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
