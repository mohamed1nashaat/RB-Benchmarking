<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            ['name' => 'automotive', 'display_name' => 'Automotive', 'sort_order' => 1],
            ['name' => 'beauty_fitness', 'display_name' => 'Beauty & Fitness', 'sort_order' => 2],
            ['name' => 'business_industrial', 'display_name' => 'Business & Industrial', 'sort_order' => 3],
            ['name' => 'computers_electronics', 'display_name' => 'Computers & Electronics', 'sort_order' => 4],
            ['name' => 'education', 'display_name' => 'Education', 'sort_order' => 5],
            ['name' => 'entertainment', 'display_name' => 'Entertainment', 'sort_order' => 6],
            ['name' => 'finance_insurance', 'display_name' => 'Finance & Insurance', 'sort_order' => 7],
            ['name' => 'food_beverage', 'display_name' => 'Food & Beverage', 'sort_order' => 8],
            ['name' => 'health_medicine', 'display_name' => 'Health & Medicine', 'sort_order' => 9],
            ['name' => 'home_garden', 'display_name' => 'Home & Garden', 'sort_order' => 10],
            ['name' => 'law_government', 'display_name' => 'Law & Government', 'sort_order' => 11],
            ['name' => 'lifestyle', 'display_name' => 'Lifestyle', 'sort_order' => 12],
            ['name' => 'media_publishing', 'display_name' => 'Media & Publishing', 'sort_order' => 13],
            ['name' => 'nonprofit', 'display_name' => 'Non-Profit', 'sort_order' => 14],
            ['name' => 'real_estate', 'display_name' => 'Real Estate', 'sort_order' => 15],
            ['name' => 'retail_ecommerce', 'display_name' => 'Retail & E-commerce', 'sort_order' => 16],
            ['name' => 'sports_recreation', 'display_name' => 'Sports & Recreation', 'sort_order' => 17],
            ['name' => 'technology', 'display_name' => 'Technology', 'sort_order' => 18],
            ['name' => 'travel_tourism', 'display_name' => 'Travel & Tourism', 'sort_order' => 19],
            ['name' => 'transportation_logistics', 'display_name' => 'Transportation & Logistics', 'sort_order' => 20],
            ['name' => 'other', 'display_name' => 'Other', 'sort_order' => 99],
        ];

        foreach ($industries as $industry) {
            \App\Models\Industry::create($industry);
        }

        // Add some default sub-industries
        $subIndustries = [
            // Real Estate
            ['industry' => 'real_estate', 'name' => 'residential_sales', 'display_name' => 'Residential Sales'],
            ['industry' => 'real_estate', 'name' => 'commercial_real_estate', 'display_name' => 'Commercial Real Estate'],
            ['industry' => 'real_estate', 'name' => 'property_management', 'display_name' => 'Property Management'],
            ['industry' => 'real_estate', 'name' => 'luxury_properties', 'display_name' => 'Luxury Properties'],
            
            // E-commerce
            ['industry' => 'retail_ecommerce', 'name' => 'fashion_apparel', 'display_name' => 'Fashion & Apparel'],
            ['industry' => 'retail_ecommerce', 'name' => 'electronics', 'display_name' => 'Electronics'],
            ['industry' => 'retail_ecommerce', 'name' => 'home_goods', 'display_name' => 'Home Goods'],
            ['industry' => 'retail_ecommerce', 'name' => 'beauty_products', 'display_name' => 'Beauty Products'],
            
            // Healthcare
            ['industry' => 'health_medicine', 'name' => 'dental', 'display_name' => 'Dental Services'],
            ['industry' => 'health_medicine', 'name' => 'medical_practice', 'display_name' => 'Medical Practice'],
            ['industry' => 'health_medicine', 'name' => 'wellness', 'display_name' => 'Wellness & Fitness'],
            ['industry' => 'health_medicine', 'name' => 'mental_health', 'display_name' => 'Mental Health'],
            
            // Technology
            ['industry' => 'technology', 'name' => 'software_development', 'display_name' => 'Software Development'],
            ['industry' => 'technology', 'name' => 'saas', 'display_name' => 'SaaS'],
            ['industry' => 'technology', 'name' => 'mobile_apps', 'display_name' => 'Mobile Apps'],
            ['industry' => 'technology', 'name' => 'cybersecurity', 'display_name' => 'Cybersecurity'],
        ];

        foreach ($subIndustries as $subIndustry) {
            $industry = \App\Models\Industry::where('name', $subIndustry['industry'])->first();
            if ($industry) {
                \App\Models\SubIndustry::create([
                    'industry_id' => $industry->id,
                    'name' => $subIndustry['name'],
                    'display_name' => $subIndustry['display_name'],
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
