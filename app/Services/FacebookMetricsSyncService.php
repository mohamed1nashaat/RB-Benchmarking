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
            \App\Helpers\SecureLogger::exception('error', 'Facebook metrics sync failed', $e, [
                'account_id' => $account->id,
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

                // Calculate checksum for deduplication (stable, not based on mutable values)
                $checksum = md5("{$account->id}:{$campaign->id}:facebook:{$date}");

                AdMetric::updateOrCreate(
                    [
                        'ad_account_id' => $account->id,
                        'ad_campaign_id' => $campaign->id,
                        'platform' => 'facebook',
                        'date' => $date,
                    ],
                    [
                        'tenant_id' => $account->tenant_id,
                        'checksum' => $checksum,
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
                        'link_clicks' => $actions['link_clicks'] ?? 0,
                        'instant_leads' => $actions['instant_leads'] ?? 0,
                        'native_leads' => $actions['native_leads'] ?? 0,
                        'blended_leads' => $actions['blended_leads'] ?? 0,
                        'sign_ups' => $actions['sign_ups'] ?? 0,
                        'website_leads' => $actions['website_leads'] ?? 0,
                        'on_facebook_leads' => $actions['on_facebook_leads'] ?? 0,
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
            'landing_page_view' => 0,
            'link_clicks' => 0,
            'instant_leads' => 0,
            'native_leads' => 0,
            'website_leads' => 0,
            'on_facebook_leads' => 0,
            'sign_ups' => 0,
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
                    $result['instant_leads'] += $value;
                    $result['conversions'] += $value;
                    break;
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
                case 'link_click':
                    $result['link_clicks'] += $value;
                    break;
                case 'instant_form_opens':
                case 'instant_form_submits':
                    $result['instant_leads'] += $value;
                    break;
                case 'onsite_conversion.lead_grouped':
                case 'leadgen_grouped':
                case 'onsite_conversion.messaging_conversation_started_7d':
                    $result['on_facebook_leads'] += $value;
                    break;
                case 'offsite_conversion.lead':
                    $result['website_leads'] += $value;
                    break;
                case 'offsite_conversion.sign_up':
                case 'complete_registration':
                case 'omni_complete_registration':
                    $result['sign_ups'] += $value;
                    $result['conversions'] += $value;
                    break;
            }
        }

        // Derive blended and native leads
        $result['native_leads'] = $result['on_facebook_leads'];
        $result['blended_leads'] = $result['leads'] + $result['instant_leads'] + $result['website_leads'] + $result['on_facebook_leads'];

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
     * Sync ad creatives for a specific ad account
     */
    public function syncCreativesForAccount(AdAccount $account, string $accessToken): array
    {
        if (empty($account->tenant_id)) {
            return ['success' => false, 'error' => 'Ad account has no tenant_id assigned. Skipping creative sync.'];
        }

        try {
            // Build lookup map of external campaign IDs to internal campaign IDs
            $campaignMap = \App\Models\AdCampaign::where('ad_account_id', $account->id)
                ->pluck('id', 'external_campaign_id')
                ->toArray();

            $actId = str_starts_with($account->external_account_id, 'act_') ? $account->external_account_id : "act_{$account->external_account_id}";
            $url = self::API_BASE_URL . "/{$actId}/ads";
            $params = [
                'access_token' => $accessToken,
                'fields' => 'id,name,campaign_id,creative{thumbnail_url,object_story_spec{photo_data,video_data{image_url}},asset_feed_spec{images{url}}},status',
                'limit' => 100,
            ];

            $ads = [];
            do {
                $response = Http::get($url, $params);
                if (!$response->successful()) {
                    Log::error('Facebook creatives API request failed', [
                        'account_id' => $account->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return ['success' => false, 'error' => $response->body()];
                }

                $data = $response->json();
                $ads = array_merge($ads, $data['data'] ?? []);
                $after = $data['paging']['cursors']['after'] ?? null;
                $params['after'] = $after;
            } while ($after);

            $synced = 0;

            foreach ($ads as $ad) {
                $creative = $ad['creative'] ?? [];
                $thumbnailUrl = $creative['thumbnail_url'] ?? null;
                $imageUrl = null;
                $videoUrl = null;

                // Try object_story_spec first
                $story = $creative['object_story_spec'] ?? [];
                if (!empty($story['photo_data'])) {
                    $photoData = $story['photo_data'];
                    if (!empty($photoData['images'])) {
                        $images = $photoData['images'];
                        $imageUrl = $images['1200x628']['url'] ?? ($images['1080x1080']['url'] ?? reset($images)['url'] ?? null);
                    } elseif (!empty($photoData['url'])) {
                        $imageUrl = $photoData['url'];
                    }
                }
                if (!empty($story['video_data']['image_url'])) {
                    $videoUrl = $story['video_data']['image_url'];
                }

                // Fallback to asset_feed_spec for dynamic creatives
                if (empty($imageUrl) && !empty($creative['asset_feed_spec']['images'])) {
                    $feedImages = $creative['asset_feed_spec']['images'];
                    $imageUrl = $feedImages[0]['url'] ?? reset($feedImages)['url'] ?? null;
                }

                $externalCampaignId = $ad['campaign_id'] ?? null;
                $adCampaignId = $externalCampaignId ? ($campaignMap[$externalCampaignId] ?? null) : null;

                \App\Models\AdCreative::updateOrCreate(
                    [
                        'platform' => 'facebook',
                        'external_creative_id' => $ad['id'],
                    ],
                    [
                        'tenant_id' => $account->tenant_id,
                        'ad_account_id' => $account->id,
                        'ad_campaign_id' => $adCampaignId,
                        'name' => $ad['name'] ?? null,
                        'thumbnail_url' => $thumbnailUrl,
                        'image_url' => $imageUrl,
                        'video_url' => $videoUrl,
                        'status' => $ad['status'] ?? null,
                        'metadata' => ['creative_id' => $creative['id'] ?? null, 'external_campaign_id' => $externalCampaignId],
                    ]
                );
                $synced++;
            }

            // Batch-update thumbnails to 320x320 for all synced creatives
            $this->updateCreativeThumbnails($account, $accessToken, $ads);

            Log::info('Facebook creatives sync completed', [
                'account_id' => $account->id,
                'synced' => $synced,
            ]);

            return ['success' => true, 'synced' => $synced];
        } catch (\Exception $e) {
            Log::error('Facebook creatives sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Batch-update creative thumbnails to 320x320 via the /adcreatives endpoint.
     * The /ads endpoint returns 64x64 thumbnails by default; this fetches larger ones.
     */
    private function updateCreativeThumbnails(AdAccount $account, string $accessToken, array $ads): void
    {
        $creativeIds = [];
        $idMap = [];
        foreach ($ads as $ad) {
            $creativeId = $ad['creative']['id'] ?? null;
            $externalAdId = $ad['id'] ?? null;
            if ($creativeId && $externalAdId) {
                $creativeIds[] = $creativeId;
                $idMap[$creativeId] = $externalAdId;
            }
        }

        if (empty($creativeIds)) {
            return;
        }

        $batches = array_chunk($creativeIds, 50);
        foreach ($batches as $batch) {
            $batchRequests = [];
            foreach ($batch as $creativeId) {
                $batchRequests[] = [
                    'method' => 'GET',
                    'relative_url' => $creativeId . '?fields=thumbnail_url&thumbnail_width=1200&thumbnail_height=1200',
                ];
            }

            try {
                $response = Http::post(self::API_BASE_URL . '/', [
                    'access_token' => $accessToken,
                    'batch' => json_encode($batchRequests),
                ]);

                if (!$response->successful()) {
                    Log::warning('Facebook thumbnail batch update failed', [
                        'account_id' => $account->id,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $results = $response->json();
                foreach ($results as $idx => $result) {
                    $body = json_decode($result['body'] ?? '{}', true);
                    $thumbnailUrl = $body['thumbnail_url'] ?? null;
                    $creativeId = $batch[$idx];
                    $externalAdId = $idMap[$creativeId] ?? null;

                    if ($thumbnailUrl && $externalAdId) {
                        \App\Models\AdCreative::where('platform', 'facebook')
                            ->where('external_creative_id', $externalAdId)
                            ->update(['thumbnail_url' => $thumbnailUrl]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Facebook thumbnail batch update exception', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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

    /**
     * Fetch total lifetime spend for an ad account directly from Facebook API.
     * Returns spend in the account's original currency.
     */
    public function fetchAccountTotalSpend(AdAccount $account): ?float
    {
        try {
            $integration = $account->integration;
            if (!$integration) {
                Log::warning('Facebook fetchAccountTotalSpend: no integration', ['account_id' => $account->id]);
                return null;
            }

            $config = $integration->app_config;
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            $accessToken = $config['access_token'] ?? null;
            if (!$accessToken) {
                Log::warning('Facebook fetchAccountTotalSpend: no access token', ['account_id' => $account->id]);
                return null;
            }

            $accountId = $account->external_account_id;
            if (!str_starts_with($accountId, 'act_')) {
                $accountId = 'act_' . $accountId;
            }

            $url = self::API_BASE_URL . "/{$accountId}/insights";
            $response = Http::get($url, [
                'access_token' => $accessToken,
                'date_preset' => 'maximum',
                'level' => 'account',
                'fields' => 'spend',
            ]);

            if (!$response->successful()) {
                Log::warning('Facebook fetchAccountTotalSpend API failed', [
                    'account_id' => $account->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $spend = (float) ($data['data'][0]['spend'] ?? 0);

            Log::info('Facebook fetchAccountTotalSpend success', [
                'account_id' => $account->id,
                'spend' => $spend,
            ]);

            return $spend;
        } catch (\Exception $e) {
            Log::error('Facebook fetchAccountTotalSpend error', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
