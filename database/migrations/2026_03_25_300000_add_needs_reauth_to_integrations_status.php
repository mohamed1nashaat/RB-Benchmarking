<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN status ENUM('active', 'inactive', 'error', 'needs_reauth') DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE integrations SET status = 'error' WHERE status = 'needs_reauth'");
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE integrations MODIFY COLUMN status ENUM('active', 'inactive', 'error') DEFAULT 'active'");
        }
    }
};
