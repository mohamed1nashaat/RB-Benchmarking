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
        Schema::table('ad_campaigns', function (Blueprint $table) {
            // Target audience segment
            $table->enum('target_segment', [
                'luxury',           // High-end, premium audience
                'premium',          // Upper-mid market
                'mid_class',        // Middle class, mainstream
                'value',            // Price-conscious, budget-friendly
                'mass_market',      // Broad, general audience
                'niche'             // Specialized, specific audience
            ])->nullable()->after('has_pixel_data');
            
            // Age group targeting
            $table->enum('age_group', [
                'gen_z',            // 16-25
                'millennials',      // 26-40
                'gen_x',            // 41-55
                'boomers',          // 56+
                'mixed_age'         // Multiple age groups
            ])->nullable()->after('target_segment');
            
            // Geographic targeting level
            $table->enum('geo_targeting', [
                'local',            // City/neighborhood level
                'regional',         // State/province level
                'national',         // Country level
                'international'     // Multiple countries
            ])->nullable()->after('age_group');
            
            // Campaign tone/messaging style
            $table->enum('messaging_tone', [
                'professional',     // B2B, corporate
                'casual',           // Friendly, informal
                'luxury',           // Premium, sophisticated
                'urgent',           // Sale, limited time
                'educational',      // Informative, helpful
                'emotional'         // Emotional appeal
            ])->nullable()->after('geo_targeting');
            
            // Add indexes for better performance
            $table->index(['tenant_id', 'target_segment']);
            $table->index(['tenant_id', 'age_group']);
            $table->index(['tenant_id', 'geo_targeting']);
        });
        
        // Also add to ad_metrics table for reporting
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->enum('target_segment', [
                'luxury', 'premium', 'mid_class', 'value', 'mass_market', 'niche'
            ])->nullable()->after('has_pixel_data');
            
            $table->enum('age_group', [
                'gen_z', 'millennials', 'gen_x', 'boomers', 'mixed_age'
            ])->nullable()->after('target_segment');
            
            $table->enum('geo_targeting', [
                'local', 'regional', 'national', 'international'
            ])->nullable()->after('age_group');
            
            $table->enum('messaging_tone', [
                'professional', 'casual', 'luxury', 'urgent', 'educational', 'emotional'
            ])->nullable()->after('geo_targeting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'target_segment']);
            $table->dropIndex(['tenant_id', 'age_group']); 
            $table->dropIndex(['tenant_id', 'geo_targeting']);
            $table->dropColumn([
                'target_segment', 
                'age_group', 
                'geo_targeting', 
                'messaging_tone'
            ]);
        });
        
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropColumn([
                'target_segment', 
                'age_group', 
                'geo_targeting', 
                'messaging_tone'
            ]);
        });
    }
};
