<?php

namespace Tests\Feature;

use App\Livewire\Pages\Calculator;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\IndustryBenchmark;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function seedGlobalBenchmarkData(): void
    {
        $metrics = [
            ['retail', 'facebook', 'ctr', 0.92, 1.38, 2.05, 2.82, 4.05],
            ['retail', 'facebook', 'cpc', 0.68, 0.98, 1.48, 2.05, 2.95],
            ['retail', 'facebook', 'cpm', 5.20, 7.50, 11.20, 15.30, 22.10],
            ['retail', 'facebook', 'cvr', 2.90, 4.95, 7.85, 11.50, 16.80],
            ['retail', 'google', 'ctr', 2.28, 3.35, 4.92, 6.72, 9.58],
            ['retail', 'google', 'cpc', 0.82, 1.18, 1.80, 2.48, 3.60],
            ['retail', 'google', 'cvr', 3.35, 5.68, 9.05, 13.25, 19.20],
        ];

        foreach ($metrics as $metric) {
            IndustryBenchmark::create([
                'industry' => $metric[0],
                'platform' => $metric[1],
                'metric' => $metric[2],
                'percentile_10' => $metric[3],
                'percentile_25' => $metric[4],
                'percentile_50' => $metric[5],
                'percentile_75' => $metric[6],
                'percentile_90' => $metric[7],
                'source' => 'WordStream 2024',
                'region' => 'global',
                'last_updated' => '2024-12-01',
            ]);
        }
    }

    private function seedBenchmarkData(): void
    {
        $this->seedGlobalBenchmarkData();

        $tenant = Tenant::factory()->create();
        $integration = Integration::factory()->create(['tenant_id' => $tenant->id, 'platform' => 'facebook']);
        $account = AdAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'integration_id' => $integration->id,
            'industry' => 'retail',
            'country' => 'Saudi Arabia',
            'currency' => 'SAR',
        ]);

        AdMetric::factory()->create([
            'ad_account_id' => $account->id,
            'date' => now()->subDays(30)->toDateString(),
            'platform' => 'facebook',
            'objective' => 'leads',
            'funnel_stage' => 'BOF',
            'user_journey' => 'landing_page',
            'spend' => 1000,
            'impressions' => 10000,
            'clicks' => 500,
            'leads' => 50,
            'conversions' => 50,
        ]);

        AdMetric::factory()->create([
            'ad_account_id' => $account->id,
            'date' => now()->subDays(60)->toDateString(),
            'platform' => 'facebook',
            'objective' => 'leads',
            'funnel_stage' => 'BOF',
            'user_journey' => 'landing_page',
            'spend' => 1200,
            'impressions' => 12000,
            'clicks' => 600,
            'leads' => 60,
            'conversions' => 60,
        ]);
    }

    public function test_calculator_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/calculator')
            ->assertStatus(200)
            ->assertSeeText(__('pages.calculator.hero_title'));
    }

    public function test_calculator_requires_authentication(): void
    {
        $this->get('/calculator')->assertRedirect('/login');
    }

    public function test_calculator_computes_expected_results(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('hasResult', true);

        $html = $component->html();
        $this->assertStringContainsString('scenarioChart', $html);
        $this->assertStringContainsString(__('pages.calculator.scenarios.average'), $html);
        $this->assertStringContainsString(__('pages.calculator.scenarios.excellent'), $html);
        $this->assertStringContainsString(__('kpis.impressions'), $html);
        $this->assertStringContainsString(__('kpis.clicks'), $html);
    }

    public function test_calculator_validates_budget(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 0)
            ->call('calculate')
            ->assertHasErrors(['monthlyBudget']);
    }

    public function test_calculator_rejects_non_whitelisted_industry(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'technology')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasErrors(['industry'])
            ->assertSet('hasResult', false);
    }

    public function test_calculator_applies_platform_country_and_funnel_filters(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('platform', ['facebook'])
            ->set('country', 'Saudi Arabia')
            ->set('funnelStage', 'BOF')
            ->set('userJourney', 'landing_page')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('hasResult', true)
            ->assertSet('result.input.filters.platform', ['facebook'])
            ->assertSet('result.input.filters.country', ['Saudi Arabia'])
            ->assertSet('result.input.filters.funnel_stage', ['BOF'])
            ->assertSet('result.input.filters.user_journey', ['landing_page']);
    }

    public function test_calculator_rejects_unknown_industry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'nonexistent_industry')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasErrors(['industry'])
            ->assertSet('hasResult', false);
    }

    public function test_calculator_shows_error_for_whitelisted_industry_without_data(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'real_estate')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertSet('hasResult', false)
            ->assertSet('error', fn ($error) => !empty($error));
    }

    public function test_calculator_calculates_budget_for_target_results(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('calculationMode', 'target_to_budget')
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('targetResults', 100)
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('hasResult', true)
            ->assertSet('result.input.mode', 'target_to_budget')
            ->assertSet('result.input.target', 100)
            ->assertSet('result.budget.recommended', fn ($value) => $value > 0);
    }

    public function test_calculator_validates_target_in_target_mode(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('calculationMode', 'target_to_budget')
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('targetResults', 0)
            ->call('calculate')
            ->assertHasErrors(['targetResults']);
    }

    public function test_calculator_includes_global_benchmark_comparison(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('hasResult', true)
            ->assertSet('result.global_predictions.average.primary_result.value', fn ($value) => $value > 0);
    }

    public function test_calculator_builds_media_plan(): void
    {
        $this->seedBenchmarkData();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calculator::class)
            ->set('industry', 'retail')
            ->set('objective', 'leads')
            ->set('monthlyBudget', 10000)
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('hasResult', true)
            ->assertCount('result.media_plan', 3)
            ->assertSet('result.media_plan.0.stage', 'TOF')
            ->assertSet('result.media_plan.2.stage', 'BOF');
    }
}
