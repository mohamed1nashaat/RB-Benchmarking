<?php

namespace Tests\Unit;

use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SyncMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SyncMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private SyncMonitoringService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SyncMonitoringService();
        $this->tenant = Tenant::factory()->active()->create();
        $this->user = User::factory()->create(['default_tenant_id' => $this->tenant->id]);
    }

    public function test_check_for_stale_syncs_includes_twitter_platform(): void
    {
        // Create a twitter integration that has never been synced
        $integration = Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'twitter',
            'status' => 'active',
        ]);

        $stale = $this->service->checkForStaleSyncs();

        // Twitter integration should appear as stale (never synced)
        $twitterStale = collect($stale)->where('platform', 'twitter');
        $this->assertNotEmpty($twitterStale, 'Twitter platform should be included in stale sync check');
        $this->assertEquals($integration->id, $twitterStale->first()['integration_id']);
    }

    public function test_health_report_includes_twitter_platform(): void
    {
        Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'twitter',
            'status' => 'active',
        ]);

        $report = $this->service->getHealthReport();

        $twitterPlatforms = collect($report['platforms'])->where('platform', 'twitter');
        $this->assertNotEmpty($twitterPlatforms, 'Twitter platform should appear in health report');
    }

    public function test_record_success_stores_data_in_cache(): void
    {
        $this->service->recordSuccess('facebook', 1, ['metrics_synced' => 100]);

        $data = Cache::get('sync_status:facebook:1');
        $this->assertNotNull($data);
        $this->assertEquals('facebook', $data['platform']);
        $this->assertEquals(1, $data['integration_id']);
        $this->assertEquals(0, $data['consecutive_failures']);
    }

    public function test_record_failure_increments_consecutive_failures(): void
    {
        $this->service->recordFailure('google', 1, 'API error');
        $this->service->recordFailure('google', 1, 'API error again');

        $data = Cache::get('sync_status:google:1');
        $this->assertEquals(2, $data['consecutive_failures']);
    }

    public function test_check_error_rates_includes_twitter(): void
    {
        Integration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
            'platform' => 'twitter',
            'status' => 'active',
        ]);

        // The method queries integrations with whereIn including 'twitter'
        // Just verify it doesn't crash and returns an array
        $result = $this->service->checkErrorRates();
        $this->assertIsArray($result);
    }
}
