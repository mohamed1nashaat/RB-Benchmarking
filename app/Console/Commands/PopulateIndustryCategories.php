<?php

namespace App\Console\Commands;

use App\Models\Industry;
use App\Models\SubIndustry;
use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PopulateIndustryCategories extends Command
{
    protected $signature = 'industries:populate-categories';
    protected $description = 'Populate all categories from campaigns into their appropriate industries';

    public function handle()
    {
        $this->info('Populating industry categories from campaigns...');
        $this->info('');

        // Map categories to industries
        $categoryToIndustryMap = [
            // Automotive
            'Auto Dealers & Retail' => 'automotive',
            'Vehicles' => 'automotive',

            // Finance & Insurance
            'Banking' => 'finance_insurance',

            // Beauty & Fitness
            'Beauty & Cosmetics' => 'beauty_fitness',

            // Retail & E-commerce
            'Fashion & Apparel' => 'retail_ecommerce',
            'Grocery & Food Delivery' => 'retail_ecommerce',

            // Food & Beverage
            'Food & Beverages' => 'food_beverage',

            // Home & Garden
            'Home & Furniture' => 'home_garden',

            // Health & Medicine
            'Hospitals & Clinics' => 'health_medicine',

            // Education
            'K–12 Schools' => 'education',
            'Training & Certification Centers' => 'education',
            'Tutoring & Test Prep' => 'education',

            // Real Estate
            'Real Estate Agencies' => 'real_estate',
            'Residential Projects' => 'real_estate',

            // Technology
            'Software / SaaS' => 'technology',

            // Travel & Tourism
            'Tourism Boards' => 'travel_tourism',

            // Other
            'Other' => 'other',
        ];

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($categoryToIndustryMap as $categoryName => $industrySlug) {
            // Find the industry
            $industry = Industry::where('name', $industrySlug)->first();

            if (!$industry) {
                $this->warn("Industry '{$industrySlug}' not found. Skipping category '{$categoryName}'");
                $totalSkipped++;
                continue;
            }

            // Create slug from category name
            $categorySlug = Str::slug(str_replace('–', '-', $categoryName), '_');

            // Check if category already exists
            $exists = SubIndustry::where('industry_id', $industry->id)
                ->where('name', $categorySlug)
                ->exists();

            if ($exists) {
                $this->line("  ✓ Category '{$categoryName}' already exists under '{$industry->display_name}'");
                $totalSkipped++;
                continue;
            }

            // Get campaign count for this category
            $campaignCount = AdCampaign::where('category', $categoryName)->count();

            // Create the sub-industry (category)
            SubIndustry::create([
                'industry_id' => $industry->id,
                'name' => $categorySlug,
                'display_name' => $categoryName,
                'description' => "Used by {$campaignCount} campaigns",
                'sort_order' => 0,
                'is_active' => true,
            ]);

            $this->info("  ✓ Created category '{$categoryName}' under '{$industry->display_name}' ({$campaignCount} campaigns)");
            $totalCreated++;
        }

        $this->info('');
        $this->info('=== Summary ===');
        $this->info("Total categories created: {$totalCreated}");
        $this->info("Total categories skipped (already exist): {$totalSkipped}");
        $this->info('');
        $this->info('Done!');

        return 0;
    }
}
