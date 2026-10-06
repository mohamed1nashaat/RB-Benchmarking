<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SyncFacebookMetricsTest extends TestCase
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
            'status' => 'active',
            'app_config' => ['access_token' => 'fake-fb-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
        ]);
        $this->campaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => 'fb_camp_001',
            'objective' => 'awareness',
        ]);
    }

    private function fakeInsightsResponse(array $overrides = []): void
    {
        $defaultInsight = [
            'date_start' => '2026-03-01',
            'date_stop' => '2026-03-01',
            'impressions' => '1000',
            'reach' => '800',
            'clicks' => '50',
            'spend' => '25.00',
            'unique_clicks' => '45',
            'actions' => [],
            'action_values' => [],
        ];

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [array_merge($defaultInsight, $overrides)],
            ]),
        ]);
    }

    public function test_records_monitoring_success_after_sync(): void
    {
        $this->fakeInsightsResponse();

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess')
            ->once()
            ->with('facebook', $this->integration->id, Mockery::type('array'));
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('facebook:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);
    }

    public function test_records_monitoring_failure_on_error(): void
    {
        // Return invalid config to trigger failure
        $this->integration->update(['app_config' => ['access_token' => 'bad-token']]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400),
        ]);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordFailure')
            ->once()
            ->with('facebook', $this->integration->id, Mockery::type('string'));
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('facebook:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ]);
    }

    public function test_updates_last_metrics_sync_at(): void
    {
        $this->fakeInsightsResponse();

        $this->assertNull($this->adAccount->last_metrics_sync_at);

        $this->artisan('facebook:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);

        $this->adAccount->refresh();
        $this->assertNotNull($this->adAccount->last_metrics_sync_at);
    }

    public function test_extracts_revenue_from_action_values(): void
    {
        $this->fakeInsightsResponse([
            'actions' => [
                ['action_type' => 'purchase', 'value' => '5'],
            ],
            'action_values' => [
                ['action_type' => 'omni_purchase', 'value' => '500.00'],
            ],
        ]);

        $this->artisan('facebook:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);

        $metric = AdMetric::where('ad_campaign_id', $this->campaign->id)->first();
        $this->assertNotNull($metric);
        $this->assertEquals(500.00, (float) $metric->revenue);
    }
}
