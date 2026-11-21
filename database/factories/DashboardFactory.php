<?php

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DashboardFactory extends Factory
{
    protected $model = Dashboard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->words(3, true) . ' Dashboard',
            'objective' => $this->faker->randomElement(['awareness', 'leads', 'sales', 'calls']),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
