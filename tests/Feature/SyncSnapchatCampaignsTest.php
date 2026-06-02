<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SnapchatAdsService;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncSnapchatCampaignsTest extends TestCase
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
            'platform' => 'snapchat',
            'status' => 'active',
            'app_config' => [
                'access_token' => 'fake-snap-token',
                'refresh_token' => 'fake-refresh',
                'expires_at' => time() + 3600,
            ],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
        ]);
    }

    public function test_calls_ensure_valid_token_before_sync(): void
    {
        $snapService = Mockery::mock(SnapchatAdsService::class);
        $snapService->shouldReceive('ensureValidToken')
            ->once()
            ->with(Mockery::on(fn ($int) => $int->id === $this->integration->id))
            ->andReturn('valid-token');
        $snapService->shouldReceive('syncCampaignsToDatabase')
            ->andReturn(['created' => 0, 'updated' => 0, 'total' => 0]);
        $this->app->instance(SnapchatAdsService::class, $snapService);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('snapchat:sync-campaigns', [
            'integration_id' => $this->integration->id,
        ])->assertExitCode(0);
    }
}
