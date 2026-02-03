<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateCampaignObjectives extends Command
{
    protected $signature = 'campaigns:update-objectives
                            {--platform= : Filter by platform (google_ads, facebook, snapchat, linkedin, tiktok)}
                            {--account-id= : Update only for specific account ID}
                            {--dry-run : Show what would be updated without making changes}
                            {--from-name : Detect objectives from campaign names (fallback when API unavailable)}';

    protected $description = 'Update campaign objectives by fetching from ad platform APIs';

    private int $updated = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle()
    {
        $this->info('Starting campaign objectives update...');

        $platform = $this->option('platform');
        $accountId = $this->option('account-id');
        $dryRun = $this->option('dry-run');
        $fromName = $this->option('from-name');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // If --from-name flag, detect objectives from campaign names
        if ($fromName) {
            $this->updateFromCampaignNames($dryRun);
            return;
        }

        // Get accounts to process
        $query = AdAccount::with('integration');

        if ($accountId) {
            $query->where('id', $accountId);
        }

        if ($platform) {
            $query->whereHas('integration', function ($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        $accounts = $query->get();
        $this->info("Found {$accounts->count()} accounts to process");

        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        foreach ($accounts as $account) {
            try {
                $this->processAccount($account, $dryRun);
            } catch (\Exception $e) {
                $this->error("\nError processing account {$account->id}: {$e->getMessage()}");
                Log::error('UpdateCampaignObjectives error', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Summary:");
        $this->info("  Updated: {$this->updated}");
        $this->info("  Skipped: {$this->skipped}");
        $this->info("  Failed: {$this->failed}");
    }

    private function updateFromCampaignNames(bool $dryRun): void
    {
        $this->info('Detecting objectives from campaign names...');

        // Get campaigns with empty objectives
        $campaigns = AdCampaign::whereNull('objective')
            ->orWhere('objective', '')
            ->get();

        $this->info("Found {$campaigns->count()} campaigns without objectives");

        $bar = $this->output->createProgressBar($campaigns->count());
        $bar->start();

        foreach ($campaigns as $campaign) {
            $detected = $this->detectObjectiveFromName($campaign->name);

            if ($detected) {
                if ($dryRun) {
                    $this->line("\n  Would update: {$campaign->name} -> {$detected}");
                    $this->updated++;
                } else {
                    try {
                        $campaign->update([
                            'objective' => $detected,
                            'funnel_stage' => $this->determineFunnelStage($detected)
                        ]);
                        $this->updated++;
                    } catch (\Exception $e) {
                        $this->failed++;
                    }
                }
            } else {
                $this->skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Summary:");
        $this->info("  Updated: {$this->updated}");
        $this->info("  Skipped (no detection): {$this->skipped}");
        $this->info("  Failed: {$this->failed}");
    }

    private function detectObjectiveFromName(string $name): ?string
    {
        $nameLower = strtolower($name);

        // Keyword patterns for each objective
        $patterns = [
            'conversions' => ['conversion', 'purchase', 'sale', 'sales', 'buy', 'order', 'checkout', 'transaction', 'revenue', 'roas', 'catalog'],
            'leads' => ['lead', 'form', 'signup', 'sign up', 'register', 'inquiry', 'contact', 'submit', 'generation', 'gen'],
            'traffic' => ['traffic', 'click', 'visit', 'landing', 'website', 'link', 'cpc'],
            'awareness' => ['awareness', 'brand', 'reach', 'impression', 'branding', 'top of funnel', 'tof', 'upper funnel'],
            'engagement' => ['engagement', 'engage', 'like', 'comment', 'share', 'interaction', 'social'],
            'video_views' => ['video', 'view', 'watch', 'youtube', 'vv', 'thruplay'],
            'app_installs' => ['app', 'install', 'download', 'mobile app', 'application'],
            'messages' => ['message', 'messenger', 'whatsapp', 'chat', 'dm'],
        ];

        $scores = [];

        foreach ($patterns as $objective => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($nameLower, $keyword)) {
                    $score += strlen($keyword); // Longer matches score higher
                }
            }
            if ($score > 0) {
                $scores[$objective] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Return objective with highest score
        arsort($scores);
        return array_key_first($scores);
    }

    private function processAccount(AdAccount $account, bool $dryRun): void
    {
        $platform = $account->integration->platform ?? null;

        if (!$platform) {
            $this->skipped++;
            return;
        }

        $campaigns = match ($platform) {
            'google_ads' => $this->fetchGoogleAdsCampaigns($account),
            'facebook' => $this->fetchFacebookCampaigns($account),
            'snapchat' => $this->fetchSnapchatCampaigns($account),
            'linkedin' => $this->fetchLinkedInCampaigns($account),
            'tiktok' => $this->fetchTikTokCampaigns($account),
            default => []
        };

        foreach ($campaigns as $campaignData) {
            $this->updateCampaign($account, $campaignData, $platform, $dryRun);
        }
    }

    private function fetchGoogleAdsCampaigns(AdAccount $account): array
    {
        $integration = $account->integration;
        $credentials = $integration->credentials ?? [];

        if (empty($credentials['access_token'])) {
            return [];
        }

        try {
            $customerId = str_replace('-', '', $account->external_account_id);

            $query = "SELECT campaign.id, campaign.name, campaign.advertising_channel_type, campaign.bidding_strategy_type FROM campaign WHERE campaign.status != 'REMOVED'";

            $response = Http::withToken($credentials['access_token'])
                ->withHeaders([
                    'developer-token' => config('services.google_ads.developer_token'),
                    'login-customer-id' => config('services.google_ads.manager_customer_id'),
                ])
                ->post("https://googleads.googleapis.com/v18/customers/{$customerId}/googleAds:searchStream", [
                    'query' => $query
                ]);

            if (!$response->successful()) {
                return [];
            }

            $campaigns = [];
            $results = $response->json();

            foreach ($results as $batch) {
                foreach ($batch['results'] ?? [] as $row) {
                    $campaign = $row['campaign'] ?? [];
                    $campaigns[] = [
                        'id' => $campaign['id'] ?? null,
                        'name' => $campaign['name'] ?? '',
                        'objective' => $this->mapGoogleAdsObjective(
                            $campaign['advertisingChannelType'] ?? null,
                            $campaign['biddingStrategyType'] ?? null
                        )
                    ];
                }
            }

            return $campaigns;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Google Ads campaigns', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchFacebookCampaigns(AdAccount $account): array
    {
        $integration = $account->integration;
        $credentials = $integration->credentials ?? [];

        if (empty($credentials['access_token'])) {
            return [];
        }

        try {
            $externalId = $account->external_account_id;
            if (!str_starts_with($externalId, 'act_')) {
                $externalId = 'act_' . $externalId;
            }

            $campaigns = [];
            $url = "https://graph.facebook.com/v23.0/{$externalId}/campaigns";
            $params = [
                'access_token' => $credentials['access_token'],
                'fields' => 'id,name,objective,status',
                'limit' => 500
            ];

            do {
                $response = Http::get($url, $params);

                if (!$response->successful()) {
                    break;
                }

                $data = $response->json();

                foreach ($data['data'] ?? [] as $campaign) {
                    $campaigns[] = [
                        'id' => $campaign['id'] ?? null,
                        'name' => $campaign['name'] ?? '',
                        'objective' => $this->mapFacebookObjective($campaign['objective'] ?? null)
                    ];
                }

                // Handle pagination
                $url = $data['paging']['next'] ?? null;
                $params = [];
            } while ($url);

            return $campaigns;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Facebook campaigns', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchSnapchatCampaigns(AdAccount $account): array
    {
        $integration = $account->integration;
        $credentials = $integration->credentials ?? [];

        if (empty($credentials['access_token'])) {
            return [];
        }

        try {
            $response = Http::withToken($credentials['access_token'])
                ->get("https://adsapi.snapchat.com/v1/adaccounts/{$account->external_account_id}/campaigns");

            if (!$response->successful()) {
                return [];
            }

            $campaigns = [];
            $data = $response->json();

            foreach ($data['campaigns'] ?? [] as $item) {
                $campaign = $item['campaign'] ?? $item;
                $campaigns[] = [
                    'id' => $campaign['id'] ?? null,
                    'name' => $campaign['name'] ?? '',
                    'objective' => $this->mapSnapchatObjective($campaign['objective'] ?? null)
                ];
            }

            return $campaigns;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Snapchat campaigns', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchLinkedInCampaigns(AdAccount $account): array
    {
        $integration = $account->integration;
        $credentials = $integration->credentials ?? [];

        if (empty($credentials['access_token'])) {
            return [];
        }

        try {
            $response = Http::withToken($credentials['access_token'])
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->get("https://api.linkedin.com/v2/adCampaignsV2", [
                    'q' => 'search',
                    'search.account.values[0]' => "urn:li:sponsoredAccount:{$account->external_account_id}"
                ]);

            if (!$response->successful()) {
                return [];
            }

            $campaigns = [];
            $data = $response->json();

            foreach ($data['elements'] ?? [] as $campaign) {
                $campaigns[] = [
                    'id' => $campaign['id'] ?? null,
                    'name' => $campaign['name'] ?? '',
                    'objective' => $this->mapLinkedInObjective($campaign['objectiveType'] ?? $campaign['objective'] ?? null)
                ];
            }

            return $campaigns;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch LinkedIn campaigns', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchTikTokCampaigns(AdAccount $account): array
    {
        $integration = $account->integration;
        $credentials = $integration->credentials ?? [];

        if (empty($credentials['access_token'])) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Access-Token' => $credentials['access_token']
            ])->get("https://business-api.tiktok.com/open_api/v1.3/campaign/get/", [
                'advertiser_id' => $account->external_account_id,
                'page_size' => 1000
            ]);

            if (!$response->successful()) {
                return [];
            }

            $campaigns = [];
            $data = $response->json();

            foreach ($data['data']['list'] ?? [] as $campaign) {
                $campaigns[] = [
                    'id' => $campaign['campaign_id'] ?? null,
                    'name' => $campaign['campaign_name'] ?? '',
                    'objective' => $this->mapTikTokObjective($campaign['objective_type'] ?? $campaign['objective'] ?? null)
                ];
            }

            return $campaigns;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch TikTok campaigns', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function updateCampaign(AdAccount $account, array $campaignData, string $platform, bool $dryRun): void
    {
        if (empty($campaignData['id']) || empty($campaignData['objective'])) {
            $this->skipped++;
            return;
        }

        $campaign = AdCampaign::where('ad_account_id', $account->id)
            ->where('external_campaign_id', $campaignData['id'])
            ->first();

        if (!$campaign) {
            $this->skipped++;
            return;
        }

        // Skip if objective already set and matches
        if ($campaign->objective === $campaignData['objective']) {
            $this->skipped++;
            return;
        }

        if ($dryRun) {
            $this->line("\n  Would update campaign {$campaign->id} ({$campaign->name}): {$campaign->objective} -> {$campaignData['objective']}");
            $this->updated++;
            return;
        }

        try {
            $campaign->update([
                'objective' => $campaignData['objective'],
                'funnel_stage' => $this->determineFunnelStage($campaignData['objective'])
            ]);
            $this->updated++;
        } catch (\Exception $e) {
            $this->failed++;
            Log::error('Failed to update campaign objective', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mapGoogleAdsObjective(?string $channelType, ?string $biddingStrategy): string
    {
        // Map based on channel type
        return match (strtoupper($channelType ?? '')) {
            'SEARCH' => 'leads',
            'SHOPPING' => 'conversions',
            'DISPLAY' => 'awareness',
            'VIDEO' => 'video_views',
            'PERFORMANCE_MAX' => 'conversions',
            'DISCOVERY' => 'awareness',
            'LOCAL' => 'traffic',
            'SMART' => 'conversions',
            default => 'awareness'
        };
    }

    private function mapFacebookObjective(?string $objective): string
    {
        return match (strtoupper($objective ?? '')) {
            'OUTCOME_AWARENESS', 'BRAND_AWARENESS', 'REACH' => 'awareness',
            'OUTCOME_TRAFFIC', 'LINK_CLICKS', 'TRAFFIC' => 'traffic',
            'OUTCOME_ENGAGEMENT', 'POST_ENGAGEMENT', 'PAGE_LIKES', 'EVENT_RESPONSES' => 'engagement',
            'OUTCOME_LEADS', 'LEAD_GENERATION' => 'leads',
            'OUTCOME_APP_PROMOTION', 'APP_INSTALLS' => 'app_installs',
            'OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES', 'STORE_VISITS' => 'conversions',
            'VIDEO_VIEWS' => 'video_views',
            'MESSAGES' => 'messages',
            default => 'awareness'
        };
    }

    private function mapSnapchatObjective(?string $objective): string
    {
        return match (strtoupper($objective ?? '')) {
            'AWARENESS', 'BRAND_AWARENESS' => 'awareness',
            'VIDEO_VIEWS', 'VIDEO_VIEW' => 'video_views',
            'ENGAGEMENT', 'STORY_OPENS' => 'engagement',
            'APP_INSTALLS', 'APP_INSTALL', 'APP_PROMOTION' => 'app_installs',
            'DRIVE_TRAFFIC', 'TRAFFIC', 'WEB_VIEW' => 'traffic',
            'LEAD_GENERATION', 'LEADS' => 'leads',
            'CATALOG_SALES', 'PIXEL_PURCHASE', 'WEB_CONVERSIONS', 'APP_CONVERSIONS' => 'conversions',
            default => 'awareness'
        };
    }

    private function mapLinkedInObjective(?string $objective): string
    {
        return match (strtoupper($objective ?? '')) {
            'BRAND_AWARENESS' => 'awareness',
            'WEBSITE_VISITS' => 'traffic',
            'ENGAGEMENT' => 'engagement',
            'VIDEO_VIEWS' => 'video_views',
            'LEAD_GENERATION' => 'leads',
            'WEBSITE_CONVERSIONS', 'JOB_APPLICANTS', 'TALENT_LEADS' => 'conversions',
            default => 'awareness'
        };
    }

    private function mapTikTokObjective(?string $objective): string
    {
        return match (strtoupper($objective ?? '')) {
            'REACH', 'RF_REACH' => 'awareness',
            'TRAFFIC' => 'traffic',
            'VIDEO_VIEWS' => 'video_views',
            'ENGAGEMENT', 'COMMUNITY_INTERACTION' => 'engagement',
            'APP_PROMOTION', 'APP_INSTALL', 'APP_INSTALLS' => 'app_installs',
            'LEAD_GENERATION' => 'leads',
            'WEB_CONVERSIONS', 'CONVERSIONS', 'PRODUCT_SALES', 'CATALOG_SALES' => 'conversions',
            default => 'awareness'
        };
    }

    private function determineFunnelStage(string $objective): ?string
    {
        return match ($objective) {
            'awareness', 'video_views' => 'TOF',
            'traffic', 'engagement', 'leads', 'messages' => 'MOF',
            'conversions', 'app_installs', 'calls' => 'BOF',
            default => null
        };
    }
}
