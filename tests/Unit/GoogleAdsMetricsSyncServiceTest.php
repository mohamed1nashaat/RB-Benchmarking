<?php

namespace Tests\Unit;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GoogleAdsMetricsSyncService;
use App\Services\GoogleAdsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAdsMetricsSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Integration $integration;
    private AdAccount $adAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->active()->create();
        $this->user = User::factory()->create(['default_tenant_id' => $this->tenant->id]);
        $this->integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'google',
            'app_config' => ['access_token' => 'fake-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
            'external_account_id' => '1234567890',
            'account_config' => ['customer_id' => '1234567890'],
        ]);
    }

    public function test_placeholder_campaign_has_awareness_objective(): void
    {
        $googleAdsService = $this->app->make(GoogleAdsService::class);
        $service = new GoogleAdsMetricsSyncService($googleAdsService);

        // Use reflection to call syncMetricsToDatabase directly with an unknown campaign
        $method = new \ReflectionMethod($service, 'syncMetricsToDatabase');
        $method->setAccessible(true);

        $metricsData = [
            [
                'campaign_id' => '999999',
                'campaign_name' => 'Deleted Old Campaign',
                'date' => '2026-03-01',
                'impressions' => 1000,
                'clicks' => 50,
                'spend' => 5.00,
                'conversions' => 5,
                'revenue' => 100.00,
                'video_views' => 0,
                'interactions' => 55,
                'view_through_conversions' => 0,
                'channel_type' => 'SEARCH',
            ],
        ];

        $result = $method->invoke($service, $this->adAccount, $metricsData);

        // Verify a placeholder campaign was auto-created
        $campaign = AdCampaign::where('external_campaign_id', '999999')
            ->where('ad_account_id', $this->adAccount->id)
            ->first();

        $this->assertNotNull($campaign, 'Placeholder campaign should be auto-created');
        $this->assertEquals('awareness', $campaign->objective);
        $this->assertEquals('archived', $campaign->status);
    }

    public function test_reach_is_zero_not_interactions(): void
    {
        // Create a campaign so we don't test placeholder creation
        $campaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => '111111',
            'objective' => 'awareness',
        ]);

        $googleAdsService = $this->app->make(GoogleAdsService::class);
        $service = new GoogleAdsMetricsSyncService($googleAdsService);

        // Use reflection to call syncMetricsToDatabase directly
        $method = new \ReflectionMethod($service, 'syncMetricsToDatabase');
        $method->setAccessible(true);

        $metricsData = [
            [
                'campaign_id' => '111111',
                'campaign_name' => 'Test Campaign',
                'date' => '2026-03-01',
                'impressions' => 5000,
                'clicks' => 200,
                'spend' => 10.00,
                'conversions' => 10,
                'revenue' => 0,
                'video_views' => 0,
                'interactions' => 250,
                'view_through_conversions' => 0,
                'channel_type' => 'SEARCH',
            ],
        ];

        $method->invoke($service, $this->adAccount, $metricsData);

        $metric = AdMetric::where('ad_campaign_id', $campaign->id)->first();

        $this->assertNotNull($metric);
        $this->assertEquals(0, $metric->reach, 'Reach should be 0 for Google Ads, not interactions');
        $this->assertEquals(5000, $metric->impressions);
        $this->assertEquals(200, $metric->clicks);
    }
}
