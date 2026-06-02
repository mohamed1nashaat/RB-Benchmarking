<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add industry_id FK to ad_accounts (alongside existing string 'industry' column)
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->foreign('industry_id')->references('id')->on('industries')->nullOnDelete();
        });

        // Add sub_industry_id FK to ad_campaigns (alongside existing string 'sub_industry' column)
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_industry_id')->nullable();
            $table->foreign('sub_industry_id')->references('id')->on('sub_industries')->nullOnDelete();
        });

        // Backfill industry_id from string matching
        DB::statement('
            UPDATE ad_accounts
            SET industry_id = (
                SELECT id FROM industries WHERE industries.name = ad_accounts.industry LIMIT 1
            )
            WHERE ad_accounts.industry IS NOT NULL
              AND ad_accounts.industry != ""
        ');

        // Backfill sub_industry_id from string matching
        DB::statement('
            UPDATE ad_campaigns
            SET sub_industry_id = (
                SELECT id FROM sub_industries WHERE sub_industries.name = ad_campaigns.sub_industry LIMIT 1
            )
            WHERE ad_campaigns.sub_industry IS NOT NULL
              AND ad_campaigns.sub_industry != ""
        ');
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropForeign(['sub_industry_id']);
            $table->dropColumn('sub_industry_id');
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
        });
    }
};
