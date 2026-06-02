<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_accounts MODIFY COLUMN status ENUM('active','inactive','error','paused','canceled') NOT NULL DEFAULT 'active'");
        }
        // SQLite has no ENUM type — column already accepts any string
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_accounts MODIFY COLUMN status ENUM('active','inactive','error') NOT NULL DEFAULT 'active'");
        }
    }
};
