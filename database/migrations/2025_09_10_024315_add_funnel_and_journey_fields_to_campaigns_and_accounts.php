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
            $table->enum('funnel_stage', ['TOF', 'MOF', 'BOF'])->nullable()->after('objective');
            $table->enum('user_journey', ['instant_form', 'landing_page'])->nullable()->after('funnel_stage');
            $table->boolean('has_pixel_data')->default(true)->after('user_journey');
        });
        
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->string('sub_industry')->nullable()->after('industry');
        });
        
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->enum('funnel_stage', ['TOF', 'MOF', 'BOF'])->nullable()->after('objective');
            $table->enum('user_journey', ['instant_form', 'landing_page'])->nullable()->after('funnel_stage');
            $table->boolean('has_pixel_data')->default(true)->after('user_journey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn(['funnel_stage', 'user_journey', 'has_pixel_data']);
        });
        
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn('sub_industry');
        });
        
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropColumn(['funnel_stage', 'user_journey', 'has_pixel_data']);
        });
    }
};
