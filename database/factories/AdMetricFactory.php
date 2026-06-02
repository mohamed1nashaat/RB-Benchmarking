<?php

namespace Database\Factories;

use App\Models\AdMetric;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdMetricFactory extends Factory
{
    protected $model = AdMetric::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ad_account_id' => AdAccount::factory(),
            'ad_campaign_id' => AdCampaign::factory(),
            'platform' => $this->faker->randomElement(['facebook', 'google', 'linkedin', 'snapchat', 'tiktok']),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'spend' => $this->faker->randomFloat(2, 10, 1000),
            'impressions' => $this->faker->numberBetween(100, 100000),
            'clicks' => $this->faker->numberBetween(10, 5000),
            'reach' => $this->faker->numberBetween(50, 50000),
            'objective' => $this->faker->randomElement(['awareness', 'leads', 'sales', 'calls']),
            'funnel_stage' => $this->faker->randomElement(['TOF', 'MOF', 'BOF']),
            'checksum' => $this->faker->unique()->sha256(),
        ];
    }
}
