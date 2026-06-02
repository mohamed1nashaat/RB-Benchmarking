<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LinkedInAdsService;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncLinkedInCampaignsTest extends TestCase
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
            'platform' => 'linkedin',
            'status' => 'active',
            'app_config' => ['access_token' => 'fake-linkedin-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
        ]);
        $this->campaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => 'li_camp_001',
        ]);
    }

    public function test_syncs_multiple_days_by_default(): void
    {
        $linkedinService = Mockery::mock(LinkedInAdsService::class);
        $linkedinService->shouldReceive('syncAdAccounts')
            ->once()
            ->andReturn([$this->adAccount]);
        $linkedinService->shouldReceive('syncCampaigns')
            ->once()
            ->andReturn([$this->campaign]);
        // Default --days=2, so syncCampaignMetrics should be called 2 times (once per date)
        $linkedinService->shouldReceive('syncCampaignMetrics')
            ->times(2);
        $this->app->instance(LinkedInAdsService::class, $linkedinService);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('sync:linkedin-campaigns')->assertExitCode(0);
    }

    public function test_specific_date_overrides_days(): void
    {
        $linkedinService = Mockery::mock(LinkedInAdsService::class);
        $linkedinService->shouldReceive('syncAdAccounts')
            ->once()
            ->andReturn([$this->adAccount]);
        $linkedinService->shouldReceive('syncCampaigns')
            ->once()
            ->andReturn([$this->campaign]);
        // With --date, only 1 date should be synced
        $linkedinService->shouldReceive('syncCampaignMetrics')
            ->once()
            ->with(
                Mockery::on(fn ($int) => $int->id === $this->integration->id),
                Mockery::on(fn ($camp) => $camp->id === $this->campaign->id),
                '2026-03-01'
            );
        $this->app->instance(LinkedInAdsService::class, $linkedinService);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('sync:linkedin-campaigns', [
            '--date' => '2026-03-01',
        ])->assertExitCode(0);
    }
}
