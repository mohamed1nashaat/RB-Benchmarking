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
        // Add sub_industry field to ad_campaigns table
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->string('sub_industry')->nullable()->after('objective');
        });
        
        // Remove sub_industry field from ad_accounts table
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn('sub_industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add sub_industry field back to ad_accounts table
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->string('sub_industry')->nullable()->after('industry');
        });
        
        // Remove sub_industry field from ad_campaigns table
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn('sub_industry');
        });
    }
};
