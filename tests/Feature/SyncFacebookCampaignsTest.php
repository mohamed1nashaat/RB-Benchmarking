<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SyncFacebookCampaignsTest extends TestCase
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
            'platform' => 'facebook',
            'status' => 'active',
            'app_config' => ['access_token' => 'fake-fb-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
        ]);
    }

    public function test_records_monitoring_success(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'camp_001',
                        'name' => 'Test Campaign',
                        'objective' => 'REACH',
                        'status' => 'ACTIVE',
                    ],
                ],
            ]),
        ]);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess')
            ->once()
            ->with('facebook', $this->integration->id, Mockery::type('array'));
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('facebook:sync-campaigns', [
            'integration_id' => $this->integration->id,
        ])->assertExitCode(0);
    }
}
