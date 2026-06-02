<?php

namespace Database\Factories;

use App\Models\ScheduledReport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledReportFactory extends Factory
{
    protected $model = ScheduledReport::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Report',
            'report_type' => $this->faker->randomElement(['performance', 'benchmark', 'campaign']),
            'metrics' => ['spend', 'clicks', 'conversions'],
            'filters' => ['period' => 30],
            'frequency' => 'weekly',
            'day_of_week' => 'monday',
            'time_of_day' => '09:00:00',
            'export_formats' => ['pdf'],
            'recipients' => [$this->faker->email()],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function daily(): static
    {
        return $this->state(fn () => [
            'frequency' => 'daily',
            'day_of_week' => null,
            'day_of_month' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'frequency' => 'monthly',
            'day_of_week' => null,
            'day_of_month' => 1,
        ]);
    }
}
