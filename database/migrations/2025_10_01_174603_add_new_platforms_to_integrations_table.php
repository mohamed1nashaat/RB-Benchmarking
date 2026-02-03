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
        // Add LinkedIn, X/Twitter platforms to the enum
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN platform ENUM('facebook', 'meta', 'google', 'tiktok', 'snapchat', 'linkedin', 'twitter', 'x')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum values
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN platform ENUM('facebook', 'google', 'tiktok', 'snapchat')");
        }
    }
};
