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
            // Google Sheets integration fields
            $table->string('google_sheet_id')->nullable()->after('status');
            $table->text('google_sheet_url')->nullable()->after('google_sheet_id');
            $table->json('sheet_mapping')->nullable()->after('google_sheet_url');
            $table->boolean('sheets_integration_enabled')->default(false)->after('sheet_mapping');
            $table->timestamp('last_sheet_sync')->nullable()->after('sheets_integration_enabled');

            // Conversion pixel configuration
            $table->json('pixel_config')->nullable()->after('last_sheet_sync');
            $table->boolean('conversion_tracking_enabled')->default(false)->after('pixel_config');
            $table->string('conversion_pixel_id')->nullable()->after('conversion_tracking_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'google_sheet_id',
                'google_sheet_url',
                'sheet_mapping',
                'sheets_integration_enabled',
                'last_sheet_sync',
                'pixel_config',
                'conversion_tracking_enabled',
                'conversion_pixel_id'
            ]);
        });
    }
};