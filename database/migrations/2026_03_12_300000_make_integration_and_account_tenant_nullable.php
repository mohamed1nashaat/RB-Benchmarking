<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make integrations.tenant_id nullable with SET NULL on delete
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('integrations', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });

        // 2. Make ad_accounts.tenant_id nullable with SET NULL on delete
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });

        // 3. Detach integration 21 (Google MCC) from any single tenant
        DB::table('integrations')->where('id', 21)->update(['tenant_id' => null]);
    }

    public function down(): void
    {
        // Re-attach integration 21 to tenant 54 (Al Ittihad FC)
        DB::table('integrations')->where('id', 21)->update(['tenant_id' => 54]);

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
        Schema::table('integrations', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }
};
