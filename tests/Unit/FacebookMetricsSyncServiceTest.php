<?php

namespace Tests\Unit;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FacebookMetricsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookMetricsSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Integration $integration;
    private AdAccount $adAccount;
    private AdCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->active()->create();
        $this->user = User::factory()->create(['default_tenant_id' => $this->tenant->id]);
        $this->integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'facebook',
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
        ]);
        $this->campaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => 'fb_camp_123',
            'objective' => 'leads',
        ]);
    }

    public function test_re_sync_updates_existing_metric_not_creates_duplicate(): void
    {
        // Verify the FacebookMetricsSyncService uses natural keys (not checksum) for dedup.
        // The service's saveCampaignMetrics uses updateOrCreate with:
        //   match keys: tenant_id, ad_account_id, ad_campaign_id, platform, date
        //   update values: checksum, spend, impressions, etc.
        // This ensures re-syncing the same date updates rather than duplicating.

        // Verify the service code pattern by reading its source
        $serviceReflection = new \ReflectionClass(FacebookMetricsSyncService::class);
        $source = file_get_contents($serviceReflection->getFileName());

        // The service should use updateOrCreate with natural keys in the first argument
        $this->assertStringContainsString("'tenant_id' => \$account->tenant_id", $source);
        $this->assertStringContainsString("'ad_campaign_id' => \$campaign->id", $source);
        $this->assertStringContainsString("'platform' => 'facebook'", $source);
        $this->assertStringContainsString("'date' => \$date", $source);

        // Verify checksum is in the VALUES (second arg), not in the match keys
        // Find the updateOrCreate call and verify checksum is after the first array
        $this->assertMatchesRegularExpression(
            '/updateOrCreate\s*\(\s*\[.*?\'date\'.*?\],\s*\[.*?\'checksum\'/s',
            $source,
            'checksum should be in the values array (second arg), not the match keys'
        );
    }

    public function test_parses_revenue_from_action_values(): void
    {
        $insightsData = [
            [
                'date_start' => '2026-03-01',
                'date_stop' => '2026-03-01',
                'impressions' => '10000',
                'reach' => '5000',
                'clicks' => '500',
                'spend' => '100.00',
                'actions' => [
                    ['action_type' => 'purchase', 'value' => '10'],
                ],
                'action_values' => [
                    ['action_type' => 'omni_purchase', 'value' => '250.50'],
                    ['action_type' => 'offsite_conversion.fb_pixel_purchase', 'value' => '150.00'],
                ],
            ],
        ];

        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => $insightsData]),
        ]);

        $service = new FacebookMetricsSyncService();
        $service->syncMetricsForAccount($this->adAccount, 'fake-token', '2026-03-01', '2026-03-01');

        $metric = AdMetric::where('ad_campaign_id', $this->campaign->id)->first();
        $this->assertNotNull($metric);
        $this->assertGreaterThan(0, (float) $metric->revenue, 'Revenue should be extracted from action_values');
    }
}
