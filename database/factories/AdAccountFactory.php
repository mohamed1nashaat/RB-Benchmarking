<?php

namespace Database\Factories;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdAccountFactory extends Factory
{
    protected $model = AdAccount::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'integration_id' => Integration::factory(),
            'external_account_id' => 'act_' . $this->faker->numerify('##########'),
            'account_name' => $this->faker->company() . ' Ad Account',
            'currency' => 'USD',
            'status' => 'active',
            'industry' => $this->faker->randomElement(['ecommerce', 'real_estate', 'healthcare', 'education', 'finance']),
            'country' => $this->faker->randomElement(['SA', 'AE', 'US', 'GB']),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
