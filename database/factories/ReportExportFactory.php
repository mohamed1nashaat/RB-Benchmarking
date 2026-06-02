<?php

namespace Database\Factories;

use App\Models\ReportExport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'format' => $this->faker->randomElement(['csv', 'xlsx']),
            'params' => ['period' => 30, 'platform' => 'all'],
            'status' => 'queued',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'done',
            'file_path' => 'reports/' . $this->faker->uuid() . '.csv',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }
}
