<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed initial campaign categories
        $categories = [
            ['name' => 'brand_awareness', 'display_name' => 'Brand Awareness', 'sort_order' => 1],
            ['name' => 'lead_generation', 'display_name' => 'Lead Generation', 'sort_order' => 2],
            ['name' => 'website_traffic', 'display_name' => 'Website Traffic', 'sort_order' => 3],
            ['name' => 'sales_conversions', 'display_name' => 'Sales & Conversions', 'sort_order' => 4],
            ['name' => 'video_views', 'display_name' => 'Video Views', 'sort_order' => 5],
            ['name' => 'engagement', 'display_name' => 'Engagement', 'sort_order' => 6],
            ['name' => 'app_install', 'display_name' => 'App Install', 'sort_order' => 7],
            ['name' => 'retargeting', 'display_name' => 'Retargeting', 'sort_order' => 8],
            ['name' => 'catalog_sales', 'display_name' => 'Catalog Sales', 'sort_order' => 9],
            ['name' => 'messages', 'display_name' => 'Messages', 'sort_order' => 10],
            ['name' => 'store_visits', 'display_name' => 'Store Visits', 'sort_order' => 11],
            ['name' => 'seasonal_campaign', 'display_name' => 'Seasonal Campaign', 'sort_order' => 12],
            ['name' => 'property_promotion', 'display_name' => 'Property Promotion', 'sort_order' => 13],
        ];

        $now = now();
        foreach ($categories as &$category) {
            $category['is_active'] = true;
            $category['created_at'] = $now;
            $category['updated_at'] = $now;
        }

        \DB::table('campaign_categories')->insert($categories);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_categories');
    }
};
