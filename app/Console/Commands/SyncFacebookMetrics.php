<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\SyncMonitoringService;
use App\Traits\RefreshesFacebookToken;
use App\Traits\TracksSyncProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFacebookMetrics extends Command
{
    use TracksSyncProgress, RefreshesFacebookToken;
    protected $signature = 'facebook:sync-metrics {integration_id?} {--days=30} {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)} {--all : Sync all available historical data (37 months max for Facebook)} {--account-id= : Specific ad account ID to sync}';
    protected $description = 'Sync metrics from Facebook for campaigns';

    private SyncMonitoringService $monitoringService;

    public function __construct(SyncMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $integrationId = $this->argument('integration_id');
        $days = $this->option('days');
        $startDateOption = $this->option('start-date');
        $endDateOption = $this->option('end-date');
        $allTime = $this->option('all');
        $accountId = $this->option('account-id');
        
        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'facebook')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'facebook')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->info('No active Facebook integrations found — nothing to sync');
            return 0;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing metrics for integration ID: {$integration->id}");
            $this->syncMetricsForIntegration($integration, $days, $startDateOption, $endDateOption, $allTime, $accountId);
        }

        return 0;
    }

    private function syncMetricsForIntegration(Integration $integration, int $days, ?string $startDateOption = null, ?string $endDateOption = null, bool $allTime = false, ?string $accountId = null)
    {
        try {
            $this->initSyncProgress($integration->id);

            $config = $integration->app_config;

            // Ensure config is an array
            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("Invalid app config for integration {$integration->id}");
                return;
            }

            $accessToken = $this->ensureValidFacebookToken($integration);
            if (!$accessToken) {
                $this->error("No valid access token for integration {$integration->id}");
                $integration->update(['status' => 'needs_reauth']);
                $this->monitoringService->recordFailure('facebook', $integration->id, 'No valid access token — re-authorization required');
                $this->failSyncProgress('No valid access token');
                return;
            }

            // Get date range - use explicit dates if provided, otherwise use days
            // Facebook API has a 37-month limit for historical data
            $endDate = Carbon::now();

            if ($allTime) {
                // Facebook's maximum historical data is 37 months
                $startDate = $endDate->copy()->subMonths(36);
                $this->warn("All-time sync enabled - fetching maximum 37 months of data (Facebook API limit)");
            } elseif ($startDateOption && $endDateOption) {
                $startDate = Carbon::parse($startDateOption);
                $endDate = Carbon::parse($endDateOption);

                // Validate against Facebook's 37-month limit
                $maxStartDate = Carbon::now()->subMonths(36);
                if ($startDate->lt($maxStartDate)) {
                    $this->warn("Start date {$startDate->format('Y-m-d')} exceeds Facebook's 37-month limit. Adjusting to {$maxStartDate->format('Y-m-d')}");
                    $startDate = $maxStartDate;
                }
            } else {
                $startDate = $endDate->copy()->subDays($days);
            }

            $this->info("Syncing metrics from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

            // Filter by specific account ID if provided
            $query = $integration->adAccounts()->with('adCampaigns');
            if ($accountId) {
                $query->where('id', $accountId);
                $this->info("Filtering to specific account ID: {$accountId}");
            }
            $adAccounts = $query->get();
            $this->reportStep("Found {$adAccounts->count()} ad accounts to sync");

            $totalSynced = 0;
            $authFailures = 0;
            $totalCampaigns = 0;
            $accountIndex = 0;
            $accountTotal = $adAccounts->count();
            $authAborted = false;

            foreach ($adAccounts as $adAccount) {
                $accountIndex++;
                $campaigns = $adAccount->adCampaigns;
                $this->reportCurrentStep("Syncing account {$accountIndex}/{$accountTotal}: {$adAccount->account_name}");
                $this->reportStep("Syncing {$campaigns->count()} campaigns in account: {$adAccount->account_name}");

                foreach ($campaigns as $campaign) {
                    $totalCampaigns++;
                    $this->info("Syncing metrics for campaign: {$campaign->name}");
                    $result = $this->syncCampaignMetrics($campaign, $accessToken, $startDate, $endDate);

                    if ($result === -1) {
                        $authFailures++;
                    } else {
                        $totalSynced += $result;
                    }

                    // Early exit: if 3+ auth failures, the token is dead — stop wasting API calls
                    if ($authFailures >= 3) {
                        $this->warn("3+ auth failures — token is invalid, stopping sync early");
                        $authAborted = true;
                        break 2;
                    }
                }

                // Update last metrics sync timestamp on the ad account
                $adAccount->update(['last_metrics_sync_at' => now()]);
                $this->reportStep("Account {$adAccount->account_name} complete", 'success');
            }

            // Detect silent auth failure: if we processed campaigns but got zero metrics
            // across ALL accounts AND zero auth failures, the token may be silently expired
            if ($totalSynced === 0 && $authFailures === 0 && $totalCampaigns > 0) {
                $this->warn("Zero metrics synced across {$totalCampaigns} campaigns — possible silent auth failure");
                $this->monitoringService->recordFailure('facebook', $integration->id, "Zero metrics across {$totalCampaigns} campaigns — possible token expiration");
            }

            // If ALL campaigns failed with auth errors, record as failure
            if ($authFailures > 0 && $totalSynced === 0) {
                $this->error("OAuth token invalidated — all {$authFailures} campaign(s) failed with auth errors");
                $this->monitoringService->recordFailure('facebook', $integration->id, 'OAuth token invalidated — re-authorization required');
                $integration->update(['status' => 'needs_reauth']);
                $this->failSyncProgress('OAuth token invalidated');
                return;
            }

            $this->info("Successfully synced $totalSynced metric records for integration {$integration->id}");

            $stats = [
                'accounts_processed' => $authAborted ? $accountIndex : $adAccounts->count(),
                'metrics_synced' => $totalSynced,
                'auth_failures' => $authFailures,
                'total_campaigns' => $totalCampaigns,
            ];

            // Partial success: some campaigns worked but some had auth errors
            if ($authFailures > 0 && $totalSynced > 0) {
                $this->monitoringService->recordPartialSuccess('facebook', $integration->id, $stats, "{$authFailures} campaigns failed with auth errors");
            } else {
                $this->monitoringService->recordSuccess('facebook', $integration->id, $stats);
            }
            $integration->update(['last_sync_at' => now()]);
            $this->completeSyncProgress($stats);

        } catch (\Exception $e) {
            $this->error("Error syncing metrics: " . $e->getMessage());
            Log::error('Facebook metrics sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            $this->monitoringService->recordFailure('facebook', $integration->id, $e->getMessage());
            $this->failSyncProgress($e->getMessage());
        }
    }

    private function syncCampaignMetrics(AdCampaign $campaign, string $accessToken, Carbon $startDate, Carbon $endDate): int
    {
        try {
            $url = "https://graph.facebook.com/v23.0/{$campaign->external_campaign_id}/insights";
            $params = [
                'access_token' => $accessToken,
                'time_range' => json_encode([
                    'since' => $startDate->format('Y-m-d'),
                    'until' => $endDate->format('Y-m-d')
                ]),
                'time_increment' => 1, // Daily data
                'fields' => 'spend,impressions,reach,clicks,actions,action_values,unique_clicks,video_p100_watched_actions,cpm,cpc,ctr,frequency',
                'limit' => 100,
            ];

            $this->info("Making insights API call for campaign: {$campaign->external_campaign_id}");

            // Pagination loop to fetch ALL insights
            $insights = [];
            $nextUrl = null;
            $pageCount = 0;

            do {
                $response = Http::get($nextUrl ?? $url, $nextUrl ? [] : $params);

                if (!$response->successful()) {
                    $body = $response->json();
                    $errorCode = $body['error']['code'] ?? 0;

                    if ($errorCode === 190) {
                        $this->error("Auth token invalid for campaign {$campaign->external_campaign_id}: " . ($body['error']['message'] ?? 'OAuthException'));
                        return -1; // Auth failure sentinel
                    }

                    $this->error("API call failed for campaign {$campaign->external_campaign_id}: " . $response->body());
                    return 0;
                }

                $responseData = $response->json();
                $pageInsights = $responseData['data'] ?? [];
                $insights = array_merge($insights, $pageInsights);
                $pageCount++;

                $nextUrl = $responseData['paging']['next'] ?? null;
            } while ($nextUrl);

            $this->info("Found " . count($insights) . " daily metrics for campaign {$campaign->name} across {$pageCount} pages");

            $synced = 0;
            foreach ($insights as $insight) {
                $date = $insight['date_start'];
                
                // Process actions (conversions, leads, etc.)
                $actions = $insight['actions'] ?? [];
                $conversions = 0;
                $leads = 0;
                $purchases = 0;
                $calls = 0;
                $atc = 0;

                $linkClicks = 0;
                $instantLeads = 0;
                $nativeLeads = 0;
                $websiteLeads = 0;
                $onFacebookLeads = 0;
                $signUps = 0;

                foreach ($actions as $action) {
                    $val = intval($action['value'] ?? 0);
                    switch ($action['action_type']) {
                        case 'lead':
                            $instantLeads += $val;
                            break;
                        case 'offsite_conversion.fb_pixel_lead':
                            $leads += $val;
                            break;
                        case 'onsite_conversion.lead_grouped':
                            $onFacebookLeads += $val;
                            break;
                        case 'purchase':
                        case 'offsite_conversion.fb_pixel_purchase':
                        case 'onsite_conversion.purchase':
                            $purchases += $val;
                            break;
                        case 'phone_call':
                        case 'call_confirm':
                            $calls += $val;
                            break;
                        case 'add_to_cart':
                        case 'offsite_conversion.fb_pixel_add_to_cart':
                        case 'omni_add_to_cart':
                            $atc += $val;
                            $conversions += $val;
                            break;
                        case 'link_click':
                            $linkClicks += $val;
                            break;
                        case 'instant_form_opens':
                        case 'instant_form_submits':
                            $instantLeads += $val;
                            break;
                        case 'offsite_conversion.lead':
                            $websiteLeads += $val;
                            break;
                        case 'offsite_conversion.sign_up':
                        case 'omni_complete_registration':
                        case 'offsite_conversion.fb_pixel_complete_registration':
                            $signUps += $val;
                            $conversions += $val;
                            break;
                        // Only count actual conversion events
                        case 'offsite_conversion':
                        case 'onsite_conversion.messaging_conversation_started_7d':
                        case 'onsite_conversion.post_save':
                        case 'omni_initiated_checkout':
                        case 'offsite_conversion.fb_pixel_initiate_checkout':
                        case 'offsite_conversion.fb_pixel_custom':
                            $conversions += $val;
                            break;
                        default:
                            // Ignore other action types
                            break;
                    }
                }

                $nativeLeads = $onFacebookLeads;
                $blendedLeads = $leads + $instantLeads + $websiteLeads + $onFacebookLeads;

                // Total conversions = leads + purchases + calls + other conversions + sign_ups + blended lead sources
                $totalConversions = $leads + $purchases + $calls + $conversions + $signUps;

                // Extract revenue from action_values (purchase value)
                $revenue = 0;
                $actionValues = $insight['action_values'] ?? [];
                foreach ($actionValues as $av) {
                    if (in_array($av['action_type'] ?? '', ['omni_purchase', 'purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                        $revenue += floatval($av['value'] ?? 0);
                    }
                }

                // Generate checksum for this metric record
                $checksum = md5("{$campaign->ad_account_id}:{$campaign->id}:facebook:{$date}");

                AdMetric::updateOrCreate(
                    [
                        'ad_account_id' => $campaign->ad_account_id,
                        'ad_campaign_id' => $campaign->id,
                        'platform' => 'facebook',
                        'date' => $date,
                    ],
                    [
                        'tenant_id' => $campaign->tenant_id,
                        'spend' => floatval($insight['spend'] ?? 0),
                        'impressions' => intval($insight['impressions'] ?? 0),
                        'reach' => intval($insight['reach'] ?? 0),
                        'clicks' => intval($insight['clicks'] ?? 0),
                        'video_views' => $this->extractVideoViews($insight['video_p100_watched_actions'] ?? []),
                        'conversions' => $totalConversions,
                        'leads' => $leads,
                        'purchases' => $purchases,
                        'calls' => $calls,
                        'revenue' => $revenue,
                        'sessions' => intval($insight['unique_clicks'] ?? 0),
                        'atc' => $atc,
                        'link_clicks' => $linkClicks,
                        'instant_leads' => $instantLeads,
                        'native_leads' => $nativeLeads,
                        'blended_leads' => $blendedLeads,
                        'sign_ups' => $signUps,
                        'website_leads' => $websiteLeads,
                        'on_facebook_leads' => $onFacebookLeads,
                        'checksum' => $checksum,
                    ]
                );
                
                $synced++;
            }

            return $synced;

        } catch (\Exception $e) {
            $this->error("Error syncing metrics for campaign {$campaign->name}: " . $e->getMessage());
            return 0;
        }
    }

    private function extractVideoViews(array $videoActions): int
    {
        $total = 0;
        foreach ($videoActions as $action) {
            $total += intval($action['value'] ?? 0);
        }
        return $total;
    }
}