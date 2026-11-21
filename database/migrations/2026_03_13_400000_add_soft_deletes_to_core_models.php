<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('integrations', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('integrations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
