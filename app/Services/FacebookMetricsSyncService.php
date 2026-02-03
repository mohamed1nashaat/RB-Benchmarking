<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FacebookMetricsSyncService
{
    private const API_BASE_URL = 'https://graph.facebook.com/v23.0';
    private const BATCH_SIZE = 50;

    /**
     * Sync metrics for a specific ad account
     */
    public function syncMetricsForAccount(AdAccount $account, string $accessToken, string $startDate, string $endDate): array
    {
        try {
            Log::info('Starting Facebook metrics sync', [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Get all campaigns for this account
            $campaigns = AdCampaign::where('ad_account_id', $account->id)->get();

            if ($campaigns->isEmpty()) {
                Log::warning('No campaigns found for account', ['account_id' => $account->id]);
                return [
                    'success' => true,
                    'campaigns_processed' => 0,
                    'metrics_synced' => 0
                ];
            }

            $metricsCount = 0;
            $campaignsProcessed = 0;

            foreach ($campaigns as $campaign) {
                try {
                    $metrics = $this->fetchCampaignInsights($campaign->external_campaign_id, $accessToken, $startDate, $endDate);

                    if (!empty($metrics)) {
                        $this->saveCampaignMetrics($account, $campaign, $metrics);
                        $metricsCount += count($metrics);
                        $campaignsProcessed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to sync metrics for campaign', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Facebook metrics sync completed', [
                'account_id' => $account->id,
                'campaigns_processed' => $campaignsProcessed,
                'metrics_synced' => $metricsCount
            ]);

            return [
                'success' => true,
                'campaigns_processed' => $campaignsProcessed,
                'metrics_synced' => $metricsCount
            ];

        } catch (\Exception $e) {
            Log::error('Facebook metrics sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch campaign insights from Facebook API
     */
    private function fetchCampaignInsights(string $campaignId, string $accessToken, string $startDate, string $endDate): array
    {
        $url = self::API_BASE_URL . "/{$campaignId}/insights";

        $params = [
            'access_token' => $accessToken,
            'time_range' => json_encode([
                'since' => $startDate,
                'until' => $endDate
            ]),
            'time_increment' => 1, // Daily breakdown
            'level' => 'campaign',
            'fields' => implode(',', [
                'date_start',
                'date_stop',
                'impressions',
                'reach',
                'clicks',
                'spend',
                'actions', // Contains conversions, leads, purchases, etc.
                'action_values', // Revenue data
                'video_p100_watched_actions', // Video views
                'cost_per_action_type'
            ]),
            'limit' => 100
        ];

        Log::debug('Fetching Facebook insights', [
            'campaign_id' => $campaignId,
            'url' => $url
        ]);

        $response = Http::get($url, $params);

        if (!$response->successful()) {
            Log::error('Facebook API request failed', [
                'campaign_id' => $campaignId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Facebook API request failed: " . $response->body());
        }

        $data = $response->json();
        return $data['data'] ?? [];
    }

    /**
     * Save campaign metrics to database
     */
    private function saveCampaignMetrics(AdAccount $account, AdCampaign $campaign, array $metricsData): void
    {
        DB::beginTransaction();

        try {
            foreach ($metricsData as $dayMetric) {
                $date = $dayMetric['date_start'];

                // Parse actions array for conversions, leads, purchases, etc.
                $actions = $this->parseActions($dayMetric['actions'] ?? []);
                $actionValues = $this->parseActionValues($dayMetric['action_values'] ?? []);

                // Calculate checksum for deduplication
                $checksum = md5(json_encode([
                    'platform' => 'facebook',
                    'account_id' => $account->id,
                    'campaign_id' => $campaign->id,
                    'date' => $date,
                    'impressions' => $dayMetric['impressions'] ?? 0,
                    'spend' => $dayMetric['spend'] ?? 0
                ]));

                AdMetric::updateOrCreate(
                    [
                        'checksum' => $checksum
                    ],
                    [
                        'tenant_id' => $account->tenant_id,
                        'date' => $date,
                        'platform' => 'facebook',
                        'ad_account_id' => $account->id,
                        'ad_campaign_id' => $campaign->id,
                        'objective' => $campaign->objective,
                        'funnel_stage' => $campaign->funnel_stage,
                        'user_journey' => $campaign->user_journey,
                        'has_pixel_data' => $campaign->has_pixel_data,
                        // Core metrics
                        'spend' => (float) ($dayMetric['spend'] ?? 0),
                        'impressions' => (int) ($dayMetric['impressions'] ?? 0),
                        'reach' => (int) ($dayMetric['reach'] ?? 0),
                        'clicks' => (int) ($dayMetric['clicks'] ?? 0),
                        // Conversion metrics from actions
                        'conversions' => $actions['conversions'] ?? 0,
                        'leads' => $actions['leads'] ?? 0,
                        'purchases' => $actions['purchases'] ?? 0,
                        'app_installs' => $actions['app_installs'] ?? 0,
                        'atc' => $actions['add_to_cart'] ?? 0,
                        'page_views' => $actions['landing_page_view'] ?? 0,
                        // Revenue
                        'revenue' => $actionValues['purchase_value'] ?? 0,
                        // Video metrics
                        'video_views' => $this->parseVideoViews($dayMetric['video_p100_watched_actions'] ?? []),
                        // Calculated metrics
                        'cost_per_result' => $this->calculateCostPerResult($dayMetric['cost_per_action_type'] ?? [])
                    ]
                );
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save Facebook metrics', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Parse Facebook actions array into our metrics format
     */
    private function parseActions(array $actions): array
    {
        $result = [
            'conversions' => 0,
            'leads' => 0,
            'purchases' => 0,
            'app_installs' => 0,
            'add_to_cart' => 0,
            'landing_page_view' => 0
        ];

        foreach ($actions as $action) {
            $actionType = $action['action_type'] ?? '';
            $value = (int) ($action['value'] ?? 0);

            switch ($actionType) {
                case 'omni_purchase':
                case 'purchase':
                case 'offsite_conversion.fb_pixel_purchase':
                    $result['purchases'] += $value;
                    $result['conversions'] += $value;
                    break;
                case 'lead':
                case 'offsite_conversion.fb_pixel_lead':
                    $result['leads'] += $value;
                    $result['conversions'] += $value;
                    break;
                case 'mobile_app_install':
                case 'app_install':
                    $result['app_installs'] += $value;
                    $result['conversions'] += $value;
                    break;
                case 'add_to_cart':
                case 'offsite_conversion.fb_pixel_add_to_cart':
                    $result['add_to_cart'] += $value;
                    break;
                case 'landing_page_view':
                    $result['landing_page_view'] += $value;
                    break;
                case 'omni_complete_registration':
                case 'complete_registration':
                    $result['conversions'] += $value;
                    break;
            }
        }

        return $result;
    }

    /**
     * Parse action values (revenue data)
     */
    private function parseActionValues(array $actionValues): array
    {
        $result = [
            'purchase_value' => 0
        ];

        foreach ($actionValues as $actionValue) {
            $actionType = $actionValue['action_type'] ?? '';
            $value = (float) ($actionValue['value'] ?? 0);

            if (in_array($actionType, ['omni_purchase', 'purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                $result['purchase_value'] += $value;
            }
        }

        return $result;
    }

    /**
     * Parse video completion views (100% watched)
     */
    private function parseVideoViews(array $videoActions): int
    {
        $totalViews = 0;

        foreach ($videoActions as $action) {
            $totalViews += (int) ($action['value'] ?? 0);
        }

        return $totalViews;
    }

    /**
     * Calculate cost per result from Facebook's cost_per_action_type
     */
    private function calculateCostPerResult(array $costPerActionType): float
    {
        if (empty($costPerActionType)) {
            return 0;
        }

        // Get the first meaningful cost per action
        foreach ($costPerActionType as $cost) {
            if (isset($cost['value']) && $cost['value'] > 0) {
                return (float) $cost['value'];
            }
        }

        return 0;
    }

    /**
     * Sync metrics for multiple accounts
     */
    public function syncMetricsForAccounts(array $accounts, string $accessToken, string $startDate, string $endDate): array
    {
        $results = [];

        foreach ($accounts as $account) {
            try {
                $result = $this->syncMetricsForAccount($account, $accessToken, $startDate, $endDate);
                $results[$account->id] = array_merge($result, [
                    'account_name' => $account->account_name
                ]);
            } catch (\Exception $e) {
                $results[$account->id] = [
                    'success' => false,
                    'account_name' => $account->account_name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
