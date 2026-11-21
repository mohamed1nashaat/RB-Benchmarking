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

class SyncTikTokMetricsTest extends TestCase
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
            'platform' => 'tiktok',
            'status' => 'active',
            'app_config' => ['access_token' => 'fake-tiktok-token'],
        ]);
        $this->adAccount = AdAccount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'integration_id' => $this->integration->id,
            'external_account_id' => 'tt_12345',
        ]);
    }

    private function fakeTikTokResponse(array $list = []): void
    {
        Http::fake([
            'business-api.tiktok.com/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => ['list' => $list],
            ]),
        ]);
    }

    public function test_auto_creates_placeholder_campaign(): void
    {
        // Need at least one existing campaign so syncAccountMetrics doesn't exit early
        $existingCampaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => 'tt_existing_001',
        ]);

        // API returns data for an unknown campaign_id alongside the existing one
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [
                    'list' => [
                        [
                            'dimensions' => [
                                'stat_time_day' => now()->subDay()->format('Y-m-d'),
                                'campaign_id' => 'unknown_camp_999',
                            ],
                            'metrics' => [
                                'spend' => '10.00',
                                'impressions' => '500',
                                'clicks' => '25',
                                'conversion' => '2',
                                'complete_payment' => '50.00',
                                'reach' => '400',
                                'video_play_actions' => '100',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('tiktok:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);

        // Verify placeholder campaign was auto-created
        $campaign = AdCampaign::where('external_campaign_id', 'unknown_camp_999')
            ->where('ad_account_id', $this->adAccount->id)
            ->first();
        $this->assertNotNull($campaign, 'Placeholder campaign should be auto-created for unknown campaign_id');
        $this->assertEquals('awareness', $campaign->objective);
    }

    public function test_maps_conversion_metrics(): void
    {
        // Create a known campaign
        $campaign = AdCampaign::factory()->create([
            'tenant_id' => $this->tenant->id,
            'ad_account_id' => $this->adAccount->id,
            'external_campaign_id' => 'tt_camp_001',
            'objective' => 'sales',
        ]);

        $yesterday = now()->subDay()->format('Y-m-d');

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [
                    'list' => [
                        [
                            'dimensions' => [
                                'stat_time_day' => $yesterday,
                                'campaign_id' => 'tt_camp_001',
                            ],
                            'metrics' => [
                                'spend' => '100.00',
                                'impressions' => '5000',
                                'clicks' => '250',
                                'conversion' => '15',
                                'complete_payment' => '750.00',
                                'reach' => '4000',
                                'video_play_actions' => '1000',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->artisan('tiktok:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);

        $metric = AdMetric::where('ad_campaign_id', $campaign->id)->first();

        $this->assertNotNull($metric);
        $this->assertEquals(15, $metric->conversions);
        $this->assertEquals(750.00, (float) $metric->revenue);
        $this->assertEquals(4000, $metric->reach);
    }

    public function test_updates_last_metrics_sync_at(): void
    {
        $this->fakeTikTokResponse();

        $monitoring = Mockery::mock(SyncMonitoringService::class);
        $monitoring->shouldReceive('recordSuccess');
        $this->app->instance(SyncMonitoringService::class, $monitoring);

        $this->assertNull($this->adAccount->last_metrics_sync_at);

        $this->artisan('tiktok:sync-metrics', [
            'integration_id' => $this->integration->id,
            '--days' => 1,
        ])->assertExitCode(0);

        $this->adAccount->refresh();
        $this->assertNotNull($this->adAccount->last_metrics_sync_at);
    }
}
