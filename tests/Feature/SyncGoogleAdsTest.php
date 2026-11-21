<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GoogleAdsCampaignSyncService;
use App\Services\GoogleAdsMetricsSyncService;
use App\Services\GoogleAdsService;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncGoogleAdsTest extends TestCase
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
            'status' => 'active',
            'app_config' => ['access_token' => 'fake-google-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
            'external_account_id' => '1234567890',
            'account_config' => ['customer_id' => '1234567890'],
        ]);
    }

    public function test_records_monitoring_success(): void
    {
        // Mock all Google services
        $campaignSync = Mockery::mock(GoogleAdsCampaignSyncService::class);
        $campaignSync->shouldReceive('syncCampaignsForAccount')
            ->andReturn(['created' => 0, 'updated' => 0, 'total' => 0]);
        $this->app->instance(GoogleAdsCampaignSyncService::class, $campaignSync);

        $metricsSync = Mockery::mock(GoogleAdsMetricsSyncService::class);
        $metricsSync->shouldReceive('syncMetricsForAccount')
            ->andReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0]);
        $this->app->instance(GoogleAdsMetricsSyncService::class, $metricsSync);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess')
            ->once()
            ->with('google', $this->integration->id, Mockery::type('array'));
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('google-ads:sync', [
            'integration_id' => $this->integration->id,
        ])->assertExitCode(0);
    }

    public function test_updates_last_metrics_sync_at(): void
    {
        $campaignSync = Mockery::mock(GoogleAdsCampaignSyncService::class);
        $campaignSync->shouldReceive('syncCampaignsForAccount')
            ->andReturn(['created' => 0, 'updated' => 0, 'total' => 0]);
        $this->app->instance(GoogleAdsCampaignSyncService::class, $campaignSync);

        $metricsSync = Mockery::mock(GoogleAdsMetricsSyncService::class);
        $metricsSync->shouldReceive('syncMetricsForAccount')
            ->andReturn(['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0]);
        $this->app->instance(GoogleAdsMetricsSyncService::class, $metricsSync);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->assertNull($this->adAccount->last_metrics_sync_at);

        $this->artisan('google-ads:sync', [
            'integration_id' => $this->integration->id,
            '--metrics' => true,
        ])->assertExitCode(0);

        $this->adAccount->refresh();
        $this->assertNotNull($this->adAccount->last_metrics_sync_at);
    }
}
