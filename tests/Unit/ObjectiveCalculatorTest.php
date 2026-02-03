<?php

namespace Tests\Unit;

use App\Services\Calculators\AwarenessCalculator;
use App\Services\Calculators\LeadsCalculator;
use App\Services\Calculators\SalesCalculator;
use App\Services\Calculators\CallsCalculator;
use App\Services\Calculators\ObjectiveCalculatorFactory;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ObjectiveCalculatorTest extends TestCase
{
    public function test_awareness_calculator_computes_correct_kpis(): void
    {
        $calculator = new AwarenessCalculator();

        $metrics = collect([
            (object) [
                'spend' => 1000,
                'impressions' => 100000,
                'reach' => 80000,
                'clicks' => 2000,
                'video_views' => 1500,
                'conversions' => 0,
                'revenue' => 0,
                'purchases' => 0,
                'leads' => 0,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ]
        ]);

        $kpis = $calculator->calculateKpis($metrics);

        $this->assertEquals(10.0, $kpis['cpm']); // 1000 / (100000/1000)
        $this->assertEquals(80000, $kpis['reach']);
        $this->assertEquals(1.25, $kpis['frequency']); // 100000 / 80000
        $this->assertEquals(1.5, $kpis['vtr']); // (1500 / 100000) * 100
        $this->assertEquals(2.0, $kpis['ctr']); // (2000 / 100000) * 100
    }

    public function test_leads_calculator_computes_correct_kpis(): void
    {
        $calculator = new LeadsCalculator();

        $metrics = collect([
            (object) [
                'spend' => 500,
                'impressions' => 50000,
                'reach' => 0,
                'clicks' => 1000,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 0,
                'purchases' => 0,
                'leads' => 50,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ]
        ]);

        $kpis = $calculator->calculateKpis($metrics);

        $this->assertEquals(10.0, $kpis['cpl']); // 500 / 50
        $this->assertEquals(0.5, $kpis['cpc']); // 500 / 1000
    }

    public function test_sales_calculator_computes_correct_kpis(): void
    {
        $calculator = new SalesCalculator();

        $metrics = collect([
            (object) [
                'spend' => 1000,
                'impressions' => 100000,
                'reach' => 0,
                'clicks' => 5000,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 5000,
                'purchases' => 25,
                'leads' => 0,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ]
        ]);

        $kpis = $calculator->calculateKpis($metrics);

        $this->assertEquals(5.0, $kpis['roas']); // 5000 / 1000
        $this->assertEquals(40.0, $kpis['cpa']); // 1000 / 25
        $this->assertEquals(200.0, $kpis['aov']); // 5000 / 25
    }

    public function test_calls_calculator_computes_correct_kpis(): void
    {
        $calculator = new CallsCalculator();

        $metrics = collect([
            (object) [
                'spend' => 800,
                'impressions' => 100000,
                'reach' => 0,
                'clicks' => 2000,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 0,
                'purchases' => 0,
                'leads' => 0,
                'calls' => 40,
                'sessions' => 0,
                'atc' => 0,
            ]
        ]);

        $kpis = $calculator->calculateKpis($metrics);

        $this->assertEquals(20.0, $kpis['cost_per_call']); // 800 / 40
        $this->assertEquals(40, $kpis['calls']);
        $this->assertEquals(2.0, $kpis['call_conversion_rate']); // (40 / 2000) * 100
    }

    public function test_calculator_handles_zero_division_gracefully(): void
    {
        $calculator = new AwarenessCalculator();

        $metrics = collect([
            (object) [
                'spend' => 1000,
                'impressions' => 0, // This should cause division by zero
                'reach' => 0,
                'clicks' => 0,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 0,
                'purchases' => 0,
                'leads' => 0,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ]
        ]);

        $kpis = $calculator->calculateKpis($metrics);

        // When division by zero, the values should be null or filtered out
        $this->assertArrayNotHasKey('cpm', $kpis);
        $this->assertArrayNotHasKey('frequency', $kpis);
        $this->assertArrayNotHasKey('vtr', $kpis);
        $this->assertArrayNotHasKey('ctr', $kpis);
    }

    public function test_objective_calculator_factory_returns_correct_calculator(): void
    {
        $this->assertInstanceOf(AwarenessCalculator::class, ObjectiveCalculatorFactory::make('awareness'));
        $this->assertInstanceOf(LeadsCalculator::class, ObjectiveCalculatorFactory::make('leads'));
        $this->assertInstanceOf(SalesCalculator::class, ObjectiveCalculatorFactory::make('sales'));
        $this->assertInstanceOf(CallsCalculator::class, ObjectiveCalculatorFactory::make('calls'));
    }

    public function test_objective_calculator_factory_throws_exception_for_invalid_objective(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ObjectiveCalculatorFactory::make('invalid_objective');
    }

    public function test_calculator_aggregates_multiple_metrics_correctly(): void
    {
        $calculator = new SalesCalculator();

        $metricsCollection = collect([
            (object) [
                'spend' => 500,
                'impressions' => 50000,
                'reach' => 0,
                'clicks' => 2500,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 2500,
                'purchases' => 10,
                'leads' => 0,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ],
            (object) [
                'spend' => 300,
                'impressions' => 30000,
                'reach' => 0,
                'clicks' => 1500,
                'video_views' => 0,
                'conversions' => 0,
                'revenue' => 1200,
                'purchases' => 8,
                'leads' => 0,
                'calls' => 0,
                'sessions' => 0,
                'atc' => 0,
            ],
        ]);

        $aggregated = $calculator->aggregateMetrics($metricsCollection);

        $this->assertEquals(800, $aggregated['spend']); // 500 + 300
        $this->assertEquals(3700, $aggregated['revenue']); // 2500 + 1200
        $this->assertEquals(18, $aggregated['purchases']); // 10 + 8

        $kpis = $calculator->calculateKpis($metricsCollection);
        $this->assertEquals(4.63, $kpis['roas']); // 3700 / 800 rounded to 2 decimal
        $this->assertEqualsWithDelta(44.44, $kpis['cpa'], 0.01); // 800 / 18
        $this->assertEqualsWithDelta(205.56, $kpis['aov'], 0.01); // 3700 / 18
    }
}
