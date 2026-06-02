<?php

namespace Tests\Unit;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BenchmarkService;
use App\Services\CurrencyConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BenchmarkService $service;
    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock currency service — all tests use SAR
        $currencyMock = \Mockery::mock(CurrencyConversionService::class);
        $currencyMock->shouldReceive('convertToSAR')->andReturnUsing(fn ($amount) => $amount);
        $this->app->instance(CurrencyConversionService::class, $currencyMock);

        $this->service = $this->app->make(BenchmarkService::class);

        $this->tenantA = Tenant::factory()->active()->create();
        $this->tenantB = Tenant::factory()->active()->create();
    }

    protected function createAccountWithMetrics(
        Tenant $tenant,
        string $industry,
        array $metricOverrides = [],
        string $platform = 'facebook'
    ): AdAccount {
        $user = User::factory()->create(['default_tenant_id' => $tenant->id]);
        $integration = Integration::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,
            'platform' => $platform,
        ]);
        $account = AdAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'integration_id' => $integration->id,
            'industry' => $industry,
        ]);
        $campaign = AdCampaign::factory()->create([
            'tenant_id' => $tenant->id,
            'ad_account_id' => $account->id,
        ]);

        AdMetric::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'ad_account_id' => $account->id,
            'ad_campaign_id' => $campaign->id,
            'platform' => $platform,
            'date' => now()->subDays(5)->format('Y-m-d'),
            'spend' => 500,
            'impressions' => 10000,
            'clicks' => 200,
        ], $metricOverrides));

        return $account;
    }

    public function test_benchmarks_aggregate_across_tenants(): void
    {
        // Create accounts in different tenants with the same industry
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', ['spend' => 1000, 'clicks' => 100]);
        $this->createAccountWithMetrics($this->tenantB, 'ecommerce', ['spend' => 2000, 'clicks' => 200]);

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo);

        $this->assertArrayHasKey('ecommerce', $results);
        $this->assertEquals(2, $results['ecommerce']['accounts_count']);
    }

    public function test_benchmarks_group_by_industry(): void
    {
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce');
        $this->createAccountWithMetrics($this->tenantA, 'healthcare');

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo);

        $this->assertArrayHasKey('ecommerce', $results);
        $this->assertArrayHasKey('healthcare', $results);
    }

    public function test_benchmarks_filter_by_platform(): void
    {
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', ['platform' => 'facebook'], 'facebook');
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', ['platform' => 'google'], 'google');

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo, ['platform' => 'facebook']);

        $this->assertArrayHasKey('ecommerce', $results);
        // Only 1 account matches the facebook filter
        $this->assertEquals(1, $results['ecommerce']['accounts_count']);
    }

    public function test_benchmarks_not_filtered_by_tenant_scope(): void
    {
        // Create data BEFORE setting tenant context (so factory doesn't get filtered)
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', ['spend' => 1000]);
        $this->createAccountWithMetrics($this->tenantB, 'ecommerce', ['spend' => 2000]);

        // Now set tenant A context — benchmarks should still aggregate ALL tenants
        app()->instance('current_tenant_id', $this->tenantA->id);
        app()->instance('is_super_admin', false);

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo);

        // Should see BOTH accounts despite being in tenant A context
        $this->assertArrayHasKey('ecommerce', $results);
        $this->assertEquals(2, $results['ecommerce']['accounts_count']);
    }

    public function test_benchmarks_calculate_metrics(): void
    {
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', [
            'spend' => 1000,
            'impressions' => 50000,
            'clicks' => 500,
            'conversions' => 25,
            'leads' => 10,
        ]);

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo);

        $this->assertArrayHasKey('ecommerce', $results);
        $metrics = $results['ecommerce']['metrics'];

        // CTR should be calculated: clicks/impressions * 100
        $this->assertArrayHasKey('ctr', $metrics);
        $this->assertNotNull($metrics['ctr']['actual']);

        // CPC should be calculated: spend/clicks
        $this->assertArrayHasKey('cpc', $metrics);
        $this->assertNotNull($metrics['cpc']['actual']);
    }

    public function test_empty_results_for_no_matching_data(): void
    {
        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getIndustryBenchmarks($dateFrom, $dateTo);

        $this->assertEmpty($results);
    }

    public function test_outlier_removal_preserves_minimum_values(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeOutliers');
        $method->setAccessible(true);

        $values = [1, 2, 3, 4, 5, 6, 7, 8, 9, 100]; // 100 is an outlier
        $filtered = $method->invoke($this->service, $values);

        $this->assertNotContains(100, $filtered);
        $this->assertContains(5, $filtered);
    }

    public function test_outlier_removal_with_few_values_returns_all(): void
    {
        $method = new \ReflectionMethod($this->service, 'removeOutliers');
        $method->setAccessible(true);

        $values = [1, 2, 3]; // fewer than 4 — should return all
        $filtered = $method->invoke($this->service, $values);

        $this->assertCount(3, $filtered);
    }

    public function test_client_benchmarks_group_by_tenant(): void
    {
        $this->createAccountWithMetrics($this->tenantA, 'ecommerce', ['spend' => 1000]);
        $this->createAccountWithMetrics($this->tenantB, 'healthcare', ['spend' => 2000]);

        $dateFrom = now()->subDays(30)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $results = $this->service->getClientBenchmarks($dateFrom, $dateTo);

        $this->assertCount(2, $results);
    }
}
