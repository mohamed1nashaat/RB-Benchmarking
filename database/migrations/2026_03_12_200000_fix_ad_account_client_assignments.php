<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Mahawer tenant
        $mahawerId = DB::table('tenants')->insertGetId([
            'name' => 'Mahawer',
            'slug' => 'mahawer',
            'status' => 'active',
            'country' => 'SA',
            'industry' => 'real_estate',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Sunbulah tenant
        $sunbulahId = DB::table('tenants')->insertGetId([
            'name' => 'Sunbulah',
            'slug' => 'sunbulah',
            'status' => 'active',
            'country' => 'SA',
            'industry' => 'e_commerce_retail',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Reassign 11 accounts to existing tenants
        $mappings = [
            390 => 86,  // Red Bananas Self Service
            391 => 65,  // Peony
            392 => 66,  // Nahr
            397 => 70,  // HRC
            398 => 13,  // Jaddarah School
            399 => 48,  // Kohler
            401 => 40,  // RBP-00126 Rowad Journey-Education
            402 => 38,  // RBP-00115 SPA Ceylon
            403 => 37,  // SEDCO Holding
            405 => 49,  // TeamLab Borderless Jeddah-Destinations
            406 => 63,  // RB Benchmarks
        ];

        foreach ($mappings as $accountId => $tenantId) {
            DB::table('ad_accounts')->where('id', $accountId)->update(['tenant_id' => $tenantId]);
        }

        // 4. Assign accounts to new tenants
        DB::table('ad_accounts')->where('id', 400)->update(['tenant_id' => $mahawerId]);
        DB::table('ad_accounts')->where('id', 404)->update(['tenant_id' => $sunbulahId]);
    }

    public function down(): void
    {
        // Revert all 13 accounts back to Al Ittihad FC (tenant 54)
        DB::table('ad_accounts')
            ->whereIn('id', [390, 391, 392, 397, 398, 399, 400, 401, 402, 403, 404, 405, 406])
            ->update(['tenant_id' => 54]);

        // Delete created tenants
        DB::table('tenants')->where('slug', 'mahawer')->delete();
        DB::table('tenants')->where('slug', 'sunbulah')->delete();
    }
};
