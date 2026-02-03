<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFacebookMetrics extends Command
{
    protected $signature = 'facebook:sync-metrics {integration_id?} {--days=30} {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)} {--all : Sync all available historical data (37 months max for Facebook)} {--account-id= : Specific ad account ID to sync}';
    protected $description = 'Sync metrics from Facebook for campaigns';

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
            $this->error('No Facebook integrations found.');
            return 1;
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
            $config = $integration->app_config;
            
            // Ensure config is an array
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            
            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("Invalid app config for integration {$integration->id}");
                return;
            }
            
            $accessToken = $config['access_token'];

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
            $this->info("Found {$adAccounts->count()} ad accounts to sync");

            $totalSynced = 0;
            foreach ($adAccounts as $adAccount) {
                $campaigns = $adAccount->adCampaigns;
                $this->info("Syncing metrics for {$campaigns->count()} campaigns in account: {$adAccount->account_name}");
                
                foreach ($campaigns as $campaign) {
                    $this->info("Syncing metrics for campaign: {$campaign->name}");
                    $synced = $this->syncCampaignMetrics($campaign, $accessToken, $startDate, $endDate);
                    $totalSynced += $synced;
                }
            }

            $this->info("Successfully synced $totalSynced metric records for integration {$integration->id}");

        } catch (\Exception $e) {
            $this->error("Error syncing metrics: " . $e->getMessage());
            Log::error('Facebook metrics sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
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
                'fields' => 'spend,impressions,reach,clicks,actions,unique_clicks,cpm,cpc,ctr,frequency',
                'limit' => 100,
            ];

            $this->info("Making insights API call for campaign: {$campaign->external_campaign_id}");
            
            $response = Http::get($url, $params);

            if (!$response->successful()) {
                $this->error("API call failed for campaign {$campaign->external_campaign_id}: " . $response->body());
                return 0;
            }

            $insights = $response->json()['data'] ?? [];
            $this->info("Found " . count($insights) . " daily metrics for campaign {$campaign->name}");

            $synced = 0;
            foreach ($insights as $insight) {
                $date = $insight['date_start'];
                
                // Process actions (conversions, leads, etc.)
                $actions = $insight['actions'] ?? [];
                $conversions = 0;
                $leads = 0;
                $purchases = 0;
                $calls = 0;

                foreach ($actions as $action) {
                    switch ($action['action_type']) {
                        case 'lead':
                        case 'offsite_conversion.fb_pixel_lead':
                        case 'onsite_conversion.lead_grouped':
                            $leads += intval($action['value'] ?? 0);
                            break;
                        case 'purchase':
                        case 'offsite_conversion.fb_pixel_purchase':
                        case 'onsite_conversion.purchase':
                            $purchases += intval($action['value'] ?? 0);
                            break;
                        case 'phone_call':
                        case 'call_confirm':
                            $calls += intval($action['value'] ?? 0);
                            break;
                        // Only count actual conversion events
                        case 'offsite_conversion':
                        case 'onsite_conversion.messaging_conversation_started_7d':
                        case 'onsite_conversion.post_save':
                        case 'omni_complete_registration':
                        case 'omni_initiated_checkout':
                        case 'omni_add_to_cart':
                        case 'offsite_conversion.fb_pixel_add_to_cart':
                        case 'offsite_conversion.fb_pixel_initiate_checkout':
                        case 'offsite_conversion.fb_pixel_complete_registration':
                        case 'offsite_conversion.fb_pixel_custom':
                            $conversions += intval($action['value'] ?? 0);
                            break;
                        // Do NOT count engagement actions (link_click, post_engagement, etc.)
                        default:
                            // Ignore other action types
                            break;
                    }
                }

                // Total conversions = leads + purchases + calls + other conversions
                $totalConversions = $leads + $purchases + $calls + $conversions;

                // Generate checksum for this metric record
                $checksum = md5("{$campaign->tenant_id}:{$campaign->ad_account_id}:{$campaign->id}:facebook:{$date}");
                
                AdMetric::updateOrCreate(
                    [
                        'tenant_id' => $campaign->tenant_id,
                        'ad_account_id' => $campaign->ad_account_id,
                        'ad_campaign_id' => $campaign->id,
                        'platform' => 'facebook',
                        'date' => $date,
                    ],
                    [
                        'spend' => floatval($insight['spend'] ?? 0),
                        'impressions' => intval($insight['impressions'] ?? 0),
                        'reach' => intval($insight['reach'] ?? 0),
                        'clicks' => intval($insight['clicks'] ?? 0),
                        'video_views' => 0, // Video views would need separate API call
                        'conversions' => $totalConversions, // Total of all conversion types
                        'leads' => $leads,
                        'purchases' => $purchases,
                        'calls' => $calls,
                        'revenue' => 0, // Would need additional data or calculation
                        'sessions' => intval($insight['unique_clicks'] ?? 0), // Approximation
                        'atc' => 0, // Would need specific tracking
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
}