<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_accounts MODIFY COLUMN status ENUM('active','inactive','error','paused','canceled','permission_denied') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE ad_accounts SET status = 'error' WHERE status = 'permission_denied'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_accounts MODIFY COLUMN status ENUM('active','inactive','error','paused','canceled') NOT NULL DEFAULT 'active'");
        }
    }
};
