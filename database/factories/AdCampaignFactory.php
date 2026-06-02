<?php

namespace Database\Factories;

use App\Models\AdCampaign;
use App\Models\AdAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdCampaignFactory extends Factory
{
    protected $model = AdCampaign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ad_account_id' => AdAccount::factory(),
            'external_campaign_id' => (string) $this->faker->unique()->numerify('##########'),
            'name' => $this->faker->words(3, true) . ' Campaign',
            'objective' => $objective = $this->faker->randomElement(['awareness', 'leads', 'sales', 'calls']),
            'status' => 'active',
            'funnel_stage' => AdCampaign::funnelStageForObjective($objective),
            'channel_type' => $this->faker->randomElement(['Facebook', 'Google', 'TikTok', 'Snapchat', 'LinkedIn']),
        ];
    }

    public function objective(string $objective): static
    {
        return $this->state(fn () => ['objective' => $objective]);
    }
}
