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
        // For MySQL, we need to alter the enum to include snapchat
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN platform ENUM('facebook', 'google', 'tiktok', 'snapchat')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove snapchat from the enum
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN platform ENUM('facebook', 'google', 'tiktok')");
        }
    }
};