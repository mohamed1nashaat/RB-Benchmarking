<?php

namespace Database\Factories;

use App\Models\SeoTechnicalIssue;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeoTechnicalIssueFactory extends Factory
{
    protected $model = SeoTechnicalIssue::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'issue_type' => $this->faker->randomElement([
                'lighthouse:render-blocking-resources',
                'lighthouse:uses-optimized-images',
                'sc:sitemap_error',
                'sc:crawl_error',
            ]),
            'severity' => $this->faker->randomElement(['critical', 'high', 'medium', 'low']),
            'page_url' => $this->faker->url(),
            'description_en' => $this->faker->sentence(),
            'description_ar' => $this->faker->sentence(),
            'current_status' => 'open',
            'recommended_fix' => $this->faker->sentence(),
            'date_found' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'current_status' => 'resolved',
            'date_fixed' => now(),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn () => ['severity' => 'critical']);
    }
}
