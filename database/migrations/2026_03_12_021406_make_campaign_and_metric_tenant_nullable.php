<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make ad_campaigns.tenant_id nullable (consistent with ad_accounts)
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });

        // Make ad_metrics.tenant_id nullable (consistent with ad_accounts)
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }
};
