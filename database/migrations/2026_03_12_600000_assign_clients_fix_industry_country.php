<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Step 1 — Delete 5 junk accounts (all 0 metrics, no data loss)
            DB::table('ad_metrics')->whereIn('ad_account_id', [287, 292, 319, 324, 259])->delete();
            DB::table('ad_campaigns')->whereIn('ad_account_id', [287, 292, 319, 324, 259])->delete();
            DB::table('ad_accounts')->whereIn('id', [287, 292, 319, 324, 259])->delete();

            // Step 2 — Create 2 new tenants
            $mohId = DB::table('tenants')->insertGetId([
                'name' => 'Saudi MOH',
                'slug' => 'saudi-moh',
                'status' => 'active',
                'country' => 'SA',
                'industry' => 'government_public_sector',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $telecomId = DB::table('tenants')->insertGetId([
                'name' => 'Telecom Forum',
                'slug' => 'telecom-forum',
                'status' => 'active',
                'country' => 'SA',
                'industry' => 'technology',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Step 3 — Assign tenant_id to 45 unassigned accounts
            $tenantMappings = [
                // Active accounts
                220 => 86,
                223 => 67,
                224 => 54,
                225 => 87,
                334 => 86,
                367 => $mohId,
                373 => 110,
                375 => 67,
                376 => 41,
                378 => 50,
                380 => 81,
                382 => 54,
                388 => 157,
                389 => $telecomId,
                433 => 156,
                242 => 45,
                243 => 67,
                244 => 48,
                278 => 105,
                501 => 156,
                507 => 157,

                // Canceled accounts (all 0 metrics)
                434 => 80,
                435 => 110,
                436 => 26,
                437 => 41,
                438 => 35,
                439 => 78,
                440 => 82,
                441 => 86,
                442 => 24,
                443 => 40,
                444 => 40,
                445 => 49,
                446 => 49,
                447 => 81,
                448 => 110,
                449 => 23,
                450 => 22,
                451 => 30,
                452 => 39,
                453 => 82,
                454 => 86,
                455 => 39,
                456 => 45,
                457 => 81,
                460 => 45,
                461 => 39,
            ];

            foreach ($tenantMappings as $accountId => $tenantId) {
                DB::table('ad_accounts')->where('id', $accountId)->update(['tenant_id' => $tenantId]);
            }

            // Step 4 — Fix industry for 29 NULL-industry accounts
            $industryFixes = [
                220 => 'professional_services',
                221 => 'e_commerce_retail',
                222 => 'technology',
                227 => 'government_public_sector',
                232 => 'fmcg_cpg',
                234 => 'travel_destinations',
                236 => 'technology',
                435 => 'travel_destinations',
                436 => 'real_estate',
                439 => 'hospitality_food_services',
                440 => 'government_public_sector',
                441 => 'professional_services',
                442 => 'fmcg_cpg',
                443 => 'education',
                444 => 'education',
                445 => 'travel_destinations',
                446 => 'travel_destinations',
                447 => 'finance_insurance',
                448 => 'travel_destinations',
                449 => 'fashion_luxury',
                450 => 'real_estate',
                451 => 'technology',
                452 => 'e_commerce_retail',
                453 => 'real_estate',
                455 => 'e_commerce_retail',
                456 => 'real_estate',
                457 => 'finance_insurance',
                460 => 'real_estate',
                461 => 'e_commerce_retail',
            ];

            foreach ($industryFixes as $accountId => $industry) {
                DB::table('ad_accounts')->where('id', $accountId)->update(['industry' => $industry]);
            }

            // Step 5 — Fix 9 tenants with NULL country → SA
            DB::table('tenants')
                ->whereIn('id', [36, 63, 78, 85, 86, 104, 105, 108, 155])
                ->whereNull('country')
                ->update(['country' => 'SA']);

            // Tenant 155 (SPL) also has NULL industry → logistics
            DB::table('tenants')
                ->where('id', 155)
                ->whereNull('industry')
                ->update(['industry' => 'business_industrial']);

            // Step 6 — Propagate tenant_id to campaigns & metrics
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement('
                    UPDATE ad_campaigns
                    SET tenant_id = (
                        SELECT a.tenant_id FROM ad_accounts a WHERE a.id = ad_campaigns.ad_account_id
                    )
                    WHERE ad_campaigns.tenant_id IS NULL
                      AND ad_campaigns.ad_account_id IN (
                          SELECT id FROM ad_accounts WHERE tenant_id IS NOT NULL
                      )
                ');

                DB::statement('
                    UPDATE ad_metrics
                    SET tenant_id = (
                        SELECT a.tenant_id FROM ad_accounts a WHERE a.id = ad_metrics.ad_account_id
                    )
                    WHERE ad_metrics.tenant_id IS NULL
                      AND ad_metrics.ad_account_id IN (
                          SELECT id FROM ad_accounts WHERE tenant_id IS NOT NULL
                      )
                ');
            } else {
                DB::statement('
                    UPDATE ad_campaigns c
                    JOIN ad_accounts a ON c.ad_account_id = a.id
                    SET c.tenant_id = a.tenant_id
                    WHERE c.tenant_id IS NULL AND a.tenant_id IS NOT NULL
                ');

                DB::statement('
                    UPDATE ad_metrics m
                    JOIN ad_accounts a ON m.ad_account_id = a.id
                    SET m.tenant_id = a.tenant_id
                    WHERE m.tenant_id IS NULL AND a.tenant_id IS NOT NULL
                ');
            }
        });
    }

    public function down(): void
    {
        // Data migration — original values cannot be reconstructed
    }
};
