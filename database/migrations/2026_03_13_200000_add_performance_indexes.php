<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->index(['tenant_id', 'date'], 'ad_metrics_tenant_date_index');
            $table->index(['ad_account_id', 'date'], 'ad_metrics_account_date_index');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->index(['tenant_id', 'ad_account_id'], 'ad_campaigns_tenant_account_index');
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'ad_accounts_tenant_status_index');
        });

        // Check if tenant_users has a user_id index already
        if (!Schema::hasIndex('tenant_users', 'tenant_users_user_id_index')) {
            Schema::table('tenant_users', function (Blueprint $table) {
                $table->index('user_id', 'tenant_users_user_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ad_metrics', function (Blueprint $table) {
            $table->dropIndex('ad_metrics_tenant_date_index');
            $table->dropIndex('ad_metrics_account_date_index');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropIndex('ad_campaigns_tenant_account_index');
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropIndex('ad_accounts_tenant_status_index');
        });

        if (Schema::hasIndex('tenant_users', 'tenant_users_user_id_index')) {
            Schema::table('tenant_users', function (Blueprint $table) {
                $table->dropIndex('tenant_users_user_id_index');
            });
        }
    }
};
