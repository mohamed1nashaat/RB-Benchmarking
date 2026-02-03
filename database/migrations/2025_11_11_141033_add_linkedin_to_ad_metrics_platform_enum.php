<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'linkedin' to the platform ENUM
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_metrics MODIFY COLUMN platform ENUM('facebook', 'google', 'tiktok', 'linkedin') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'linkedin' from the platform ENUM
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ad_metrics MODIFY COLUMN platform ENUM('facebook', 'google', 'tiktok') NOT NULL");
        }
    }
};
