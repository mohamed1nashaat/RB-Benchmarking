<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Alert',
            'type' => $this->faker->randomElement(['threshold', 'anomaly', 'budget']),
            'objective' => $this->faker->randomElement(['awareness', 'leads', 'sales']),
            'conditions' => [
                'metric' => $this->faker->randomElement(['spend', 'cpc', 'ctr']),
                'operator' => '>',
                'value' => $this->faker->numberBetween(100, 1000),
            ],
            'notification_channels' => ['email'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
