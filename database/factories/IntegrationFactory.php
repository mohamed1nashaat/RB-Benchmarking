<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'platform' => $this->faker->randomElement(['facebook', 'google', 'linkedin', 'snapchat', 'tiktok', 'twitter']),
            'app_config' => ['access_token' => 'fake-token-' . $this->faker->uuid()],
            'created_by' => User::factory(),
            'status' => 'active',
        ];
    }

    public function platform(string $platform): static
    {
        return $this->state(fn () => ['platform' => $platform]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
