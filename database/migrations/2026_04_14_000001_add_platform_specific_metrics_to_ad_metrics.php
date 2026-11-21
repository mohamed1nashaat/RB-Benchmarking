<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->bigInteger('link_clicks')->default(0)->after('clicks');
            $table->bigInteger('swipes')->default(0)->after('link_clicks');
            $table->bigInteger('instant_leads')->default(0)->after('leads');
            $table->bigInteger('native_leads')->default(0)->after('instant_leads');
            $table->bigInteger('blended_leads')->default(0)->after('native_leads');
            $table->bigInteger('sign_ups')->default(0)->after('blended_leads');
            $table->bigInteger('website_leads')->default(0)->after('sign_ups');
            $table->bigInteger('on_facebook_leads')->default(0)->after('website_leads');
        });
    }

    public function down(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropColumn([
                'link_clicks',
                'swipes',
                'instant_leads',
                'native_leads',
                'blended_leads',
                'sign_ups',
                'website_leads',
                'on_facebook_leads',
            ]);
        });
    }
};
