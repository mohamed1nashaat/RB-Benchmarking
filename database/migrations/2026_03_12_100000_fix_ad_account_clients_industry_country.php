<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reassign 29 Demo Company accounts to correct clients
        $mappings = [
            221 => 65,
            222 => 66,
            226 => 41,
            227 => 70,
            228 => 13,
            229 => 48,
            230 => 18,
            231 => 40,
            232 => 38,
            233 => 37,
            234 => 49,
            236 => 63,
            237 => 78,
            238 => 40,
            239 => 80,
            240 => 81,
            241 => 82,
            245 => 37,
            246 => 70,
            247 => 85,
            249 => 86,
            286 => 86,
            374 => 49,
            377 => 48,
            379 => 40,
            381 => 66,
            383 => 13,
            384 => 41,
            385 => 70,
        ];

        foreach ($mappings as $accountId => $tenantId) {
            DB::table('ad_accounts')->where('id', $accountId)->update(['tenant_id' => $tenantId]);
        }

        // 2. Set remaining Demo Company accounts (ambiguous) to unassigned
        DB::table('ad_accounts')
            ->where('tenant_id', 1)
            ->update(['tenant_id' => null]);

        // 3. Unassign "Don't use" tenant accounts
        DB::table('ad_accounts')
            ->where('tenant_id', 42)
            ->update(['tenant_id' => null]);

        // 4. Delete Demo Company and Don't use tenants
        DB::table('tenants')->whereIn('id', [1, 42])->delete();

        // 5. Normalize stale industry values
        $industryMap = [
            'ecommerce'        => 'e_commerce_retail',
            'finance'          => 'finance_insurance',
            'government'       => 'government_public_sector',
            'retail_ecommerce' => 'e_commerce_retail',
            'travel_tourism'   => 'travel_destinations',
            'beauty_fitness'   => 'healthcare_pharmaceuticals',
        ];

        foreach ($industryMap as $old => $new) {
            DB::table('ad_accounts')->where('industry', $old)->update(['industry' => $new]);
        }

        // 6. Fill NULL countries with SA (all 17 are Saudi-based)
        DB::table('ad_accounts')->whereNull('country')->update(['country' => 'SA']);
    }

    public function down(): void
    {
        // Data migration — original tenant assignments cannot be reconstructed
    }
};
