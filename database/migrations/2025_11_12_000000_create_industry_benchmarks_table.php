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
        Schema::create('industry_benchmarks', function (Blueprint $table) {
            $table->id();

            // Industry and Platform
            $table->string('industry', 100)->index();
            $table->enum('platform', ['facebook', 'google', 'tiktok', 'linkedin', 'snapchat', 'twitter', 'all'])->index();

            // Metric Details
            $table->enum('metric', ['ctr', 'cpc', 'cpm', 'cvr', 'cpl', 'cpa', 'roas', 'engagement_rate'])->index();

            // Benchmark Percentiles
            $table->decimal('percentile_10', 10, 4)->nullable()->comment('Bottom 10%');
            $table->decimal('percentile_25', 10, 4)->nullable()->comment('25th percentile');
            $table->decimal('percentile_50', 10, 4)->nullable()->comment('Median / 50th percentile');
            $table->decimal('percentile_75', 10, 4)->nullable()->comment('75th percentile');
            $table->decimal('percentile_90', 10, 4)->nullable()->comment('Top 10%');

            // Metadata
            $table->integer('sample_size')->nullable()->comment('Number of accounts/campaigns in sample');
            $table->string('source', 255)->nullable()->comment('Data source (e.g., WordStream, Meta, Google)');
            $table->string('region', 50)->default('global')->comment('Geographic region (global, US, EU, MENA, etc.)');
            $table->date('data_period_start')->nullable()->comment('Start date of data collection');
            $table->date('data_period_end')->nullable()->comment('End date of data collection');
            $table->date('last_updated')->index()->comment('When this benchmark was last updated');

            $table->timestamps();

            // Composite indexes for common queries
            $table->unique(['industry', 'platform', 'metric', 'region'], 'industry_platform_metric_region_unique');
            $table->index(['platform', 'metric']);
            $table->index(['industry', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_benchmarks');
    }
};
