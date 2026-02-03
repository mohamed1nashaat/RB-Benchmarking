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
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            // Add 'engagement' to ad_campaigns.objective ENUM
            DB::statement("ALTER TABLE ad_campaigns MODIFY COLUMN objective ENUM('awareness', 'leads', 'sales', 'calls', 'engagement') NULL");

            // Add 'engagement' to ad_metrics.objective ENUM
            DB::statement("ALTER TABLE ad_metrics MODIFY COLUMN objective ENUM('awareness', 'leads', 'sales', 'calls', 'engagement') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support ENUMs, so skip for SQLite
        if (DB::connection()->getDriverName() === 'mysql') {
            // Remove 'engagement' from ad_campaigns.objective ENUM
            DB::statement("ALTER TABLE ad_campaigns MODIFY COLUMN objective ENUM('awareness', 'leads', 'sales', 'calls') NULL");

            // Remove 'engagement' from ad_metrics.objective ENUM
            DB::statement("ALTER TABLE ad_metrics MODIFY COLUMN objective ENUM('awareness', 'leads', 'sales', 'calls') NULL");
        }
    }
};
