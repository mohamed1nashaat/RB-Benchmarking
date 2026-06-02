<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Step 1 — Fix 10 wrong tenant assignments (raw SQL + inline verify)
            $tenantFixes = [
                222 => 66,   // "Nahr" currently t:85 (GHS) → should be t:66 (Nahr)
                224 => 54,   // "Al-Itihad - Ecommerce" currently t:63 → t:54 (Al Ittihad FC)
                225 => 87,   // "Alrashid - Real Estate" currently t:67 → t:87 (Alrashed)
                226 => 41,   // "Faisal Bin Saedan USD" currently t:31 → t:41 (FBS)
                227 => 70,   // "HRC - Governmental" currently t:87 → t:70 (HRC)
                228 => 13,   // "Jadara USD" currently t:41 → t:13 (Jaddarah School)
                229 => 48,   // "Kohler" currently t:29 → t:48 (Kohler)
                230 => 18,   // "Mancini Sleepworld" currently t:49 → t:18 (Mancini's)
                231 => 40,   // "Rowad Sedco" currently t:41 → t:40 (Rowad)
                367 => 158,  // "Saudi MOH" currently t:70 → t:158 (Saudi MOH)
            ];

            foreach ($tenantFixes as $accountId => $tenantId) {
                $exists = DB::table('ad_accounts')->where('id', $accountId)->exists();
                if (!$exists) {
                    continue; // Account doesn't exist (e.g., test DB) — skip
                }

                DB::table('ad_accounts')
                    ->where('id', $accountId)
                    ->update(['tenant_id' => $tenantId]);

                $actual = DB::table('ad_accounts')->where('id', $accountId)->value('tenant_id');
                if ($actual != $tenantId) {
                    throw new \RuntimeException("Account {$accountId}: expected tenant_id={$tenantId}, got {$actual}");
                }
            }

            // Step 2 — Fix 9 wrong account industries
            $accountIndustryFixes = [
                262 => 'fashion_luxury',      // Al-shiaka USD (t:23) — was e_commerce_retail
                252 => 'real_estate',          // Alrashed - Not Used (t:87) — was e_commerce_retail
                266 => 'real_estate',          // Alrashed - Not Used (t:87) — was e_commerce_retail
                312 => 'travel_destinations',  // RB - New Al Balad (t:110) — was professional_services
                369 => 'real_estate',          // Bin Saidan (t:41) — was technology
                376 => 'real_estate',          // PF_RB_Faisal Bin Saedan (t:41) — was technology
                437 => 'real_estate',          // Faisal Bin Saadan canceled (t:41) — was technology
                379 => 'education',            // Rowad Sedco PF_RB (t:40) — was finance_insurance
                381 => 'technology',           // Nahr - Design PF_RB (t:66) — was professional_services
            ];

            foreach ($accountIndustryFixes as $accountId => $industry) {
                DB::table('ad_accounts')->where('id', $accountId)->update(['industry' => $industry]);
            }

            // Step 3 — Fix 33 tenant industries (stale or wrong values)
            $tenantIndustryFixes = [
                // Was "retail_ecommerce"
                23  => 'fashion_luxury',
                31  => 'real_estate',
                39  => 'e_commerce_retail',
                65  => 'e_commerce_retail',

                // Was "travel_tourism"
                33  => 'hospitality_food_services',
                49  => 'travel_destinations',

                // Was "food_beverage"
                21  => 'fmcg_cpg',
                24  => 'fmcg_cpg',
                25  => 'e_commerce_retail',
                50  => 'fmcg_cpg',
                111 => 'fmcg_cpg',

                // Was "beauty_fitness"
                38  => 'fmcg_cpg',
                57  => 'e_commerce_retail',

                // Was "home_garden"
                18  => 'e_commerce_retail',
                48  => 'e_commerce_retail',

                // Was other stale values
                34  => 'real_estate',
                78  => 'hospitality_food_services',
                109 => 'healthcare_pharmaceuticals',
                66  => 'technology',
                104 => 'professional_services',

                // Was "other"
                9   => 'professional_services',
                10  => 'professional_services',
                12  => 'technology',
                28  => 'professional_services',
                29  => 'real_estate',
                30  => 'technology',
                35  => 'technology',
                59  => 'professional_services',
                91  => 'e_commerce_retail',

                // Wrong standard value (business_industrial or finance_insurance)
                70  => 'government_public_sector',
                82  => 'government_public_sector',
                87  => 'real_estate',
                110 => 'travel_destinations',
            ];

            foreach ($tenantIndustryFixes as $tenantId => $industry) {
                DB::table('tenants')->where('id', $tenantId)->update(['industry' => $industry]);
            }

            // Step 4 — Re-propagate tenant_id to campaigns & metrics
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement('
                    UPDATE ad_campaigns
                    SET tenant_id = (
                        SELECT a.tenant_id FROM ad_accounts a WHERE a.id = ad_campaigns.ad_account_id
                    )
                    WHERE ad_campaigns.ad_account_id IN (
                        SELECT id FROM ad_accounts WHERE tenant_id IS NOT NULL
                    )
                    AND (
                        ad_campaigns.tenant_id IS NULL
                        OR ad_campaigns.tenant_id != (
                            SELECT a2.tenant_id FROM ad_accounts a2 WHERE a2.id = ad_campaigns.ad_account_id
                        )
                    )
                ');

                DB::statement('
                    UPDATE ad_metrics
                    SET tenant_id = (
                        SELECT a.tenant_id FROM ad_accounts a WHERE a.id = ad_metrics.ad_account_id
                    )
                    WHERE ad_metrics.ad_account_id IN (
                        SELECT id FROM ad_accounts WHERE tenant_id IS NOT NULL
                    )
                    AND (
                        ad_metrics.tenant_id IS NULL
                        OR ad_metrics.tenant_id != (
                            SELECT a2.tenant_id FROM ad_accounts a2 WHERE a2.id = ad_metrics.ad_account_id
                        )
                    )
                ');
            } else {
                DB::statement('
                    UPDATE ad_campaigns c
                    JOIN ad_accounts a ON c.ad_account_id = a.id
                    SET c.tenant_id = a.tenant_id
                    WHERE c.tenant_id != a.tenant_id
                       OR (c.tenant_id IS NULL AND a.tenant_id IS NOT NULL)
                ');

                DB::statement('
                    UPDATE ad_metrics m
                    JOIN ad_accounts a ON m.ad_account_id = a.id
                    SET m.tenant_id = a.tenant_id
                    WHERE m.tenant_id != a.tenant_id
                       OR (m.tenant_id IS NULL AND a.tenant_id IS NOT NULL)
                ');
            }
        });
    }

    public function down(): void
    {
        // Data migration — original values cannot be reconstructed
    }
};
