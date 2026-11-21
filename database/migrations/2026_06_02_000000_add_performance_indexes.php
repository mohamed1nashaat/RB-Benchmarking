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
        // Critical lookup index for ad_metrics (used in reports, exports, benchmarks)
        if (!Schema::hasIndex('ad_metrics', 'idx_ad_metrics_lookup')) {
            Schema::table('ad_metrics', function (Blueprint $table) {
                $table->index(['ad_account_id', 'ad_campaign_id', 'date', 'platform'], 'idx_ad_metrics_lookup');
            });
        }

        // Index for date-range queries (spend verification, exports)
        if (!Schema::hasIndex('ad_metrics', 'idx_ad_metrics_date')) {
            Schema::table('ad_metrics', function (Blueprint $table) {
                $table->index(['date', 'ad_account_id'], 'idx_ad_metrics_date');
            });
        }

        // Index for campaign lookups during sync
        if (!Schema::hasIndex('ad_campaigns', 'idx_ad_campaigns_external')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $table->index(['ad_account_id', 'external_campaign_id'], 'idx_ad_campaigns_external');
            });
        }

        // Index for ad account lookups during sync
        if (!Schema::hasIndex('ad_accounts', 'idx_ad_accounts_external')) {
            Schema::table('ad_accounts', function (Blueprint $table) {
                $table->index(['integration_id', 'external_account_id'], 'idx_ad_accounts_external');
            });
        }

        // Index for tenant-scoped queries
        if (!Schema::hasIndex('ad_accounts', 'idx_ad_accounts_tenant')) {
            Schema::table('ad_accounts', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'idx_ad_accounts_tenant');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_ad_metrics_lookup');
            $table->dropIndex('idx_ad_metrics_date');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_ad_campaigns_external');
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_ad_accounts_external');
            $table->dropIndex('idx_ad_accounts_tenant');
        });
    }
};
