<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Platform mapping: old (inactive) integration → active integration.
     */
    private array $integrationMap = [
        15 => 21,  // Google
        16 => 62,  // LinkedIn
        17 => 61,  // Snapchat
        18 => 60,  // Facebook
        20 => 59,  // TikTok
    ];

    /**
     * Tenant mappings from migration 100000 (restored accounts lost these).
     */
    private array $tenantMappings = [
        221 => 65, 222 => 66, 226 => 41, 227 => 70, 228 => 13, 229 => 48, 230 => 18,
        231 => 40, 232 => 38, 233 => 37, 234 => 49, 236 => 63, 237 => 78, 238 => 40,
        239 => 80, 240 => 81, 241 => 82, 245 => 37, 246 => 70, 247 => 85, 249 => 86,
        286 => 86, 374 => 49, 377 => 48, 379 => 40, 381 => 66, 383 => 13, 384 => 41,
        385 => 70,
    ];

    /**
     * Industry normalization from migration 100000.
     */
    private array $industryMap = [
        'ecommerce'        => 'e_commerce_retail',
        'finance'          => 'finance_insurance',
        'government'       => 'government_public_sector',
        'retail_ecommerce' => 'e_commerce_retail',
        'travel_tourism'   => 'travel_destinations',
        'beauty_fitness'   => 'healthcare_pharmaceuticals',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $this->deduplicateAccounts();
            $this->moveRemainingAccountsToActiveIntegrations();
            $this->reapplyTenantMappings();
            $this->normalizeIndustries();
            $this->fillNullCountries();
            $this->propagateTenantToCampaignsAndMetrics();
            $this->verify();
        });
    }

    /**
     * Step 1: For each platform pair, delete the NEW duplicate account
     * (which has fewer metrics) and keep the OLD one (which has the bulk data).
     */
    private function deduplicateAccounts(): void
    {
        $totalDeleted = 0;

        foreach ($this->integrationMap as $oldIntegration => $newIntegration) {
            // Find duplicate pairs: same external_account_id on both old and new integration
            $duplicates = DB::table('ad_accounts as a_old')
                ->join('ad_accounts as a_new', 'a_old.external_account_id', '=', 'a_new.external_account_id')
                ->where('a_old.integration_id', $oldIntegration)
                ->where('a_new.integration_id', $newIntegration)
                ->select('a_old.id as old_id', 'a_new.id as new_id', 'a_old.external_account_id')
                ->get();

            if ($duplicates->isEmpty()) {
                continue;
            }

            $newIds = $duplicates->pluck('new_id')->toArray();

            Log::info("Dedup: Deleting {$duplicates->count()} new duplicate accounts on integration {$newIntegration} (keeping old on {$oldIntegration})", [
                'deleted_ids' => $newIds,
                'kept_ids' => $duplicates->pluck('old_id')->toArray(),
            ]);

            // Delete new duplicates — FK cascade removes their campaigns + metrics
            DB::table('ad_accounts')->whereIn('id', $newIds)->delete();

            $totalDeleted += $duplicates->count();
        }

        Log::info("Dedup complete: deleted {$totalDeleted} duplicate accounts");
    }

    /**
     * Step 2: Move all remaining accounts from old integrations to active ones.
     * Safe now because duplicates have been removed (unique constraint won't conflict).
     */
    private function moveRemainingAccountsToActiveIntegrations(): void
    {
        foreach ($this->integrationMap as $oldIntegration => $newIntegration) {
            $affected = DB::table('ad_accounts')
                ->where('integration_id', $oldIntegration)
                ->update(['integration_id' => $newIntegration]);

            if ($affected > 0) {
                Log::info("Moved {$affected} accounts from integration {$oldIntegration} to {$newIntegration}");
            }
        }
    }

    /**
     * Step 3: Re-apply tenant assignments from migration 100000.
     * These accounts were restored from backup with tenant_id=NULL.
     */
    private function reapplyTenantMappings(): void
    {
        foreach ($this->tenantMappings as $accountId => $tenantId) {
            DB::table('ad_accounts')
                ->where('id', $accountId)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);
        }

        Log::info('Re-applied tenant mappings for ' . count($this->tenantMappings) . ' accounts');
    }

    /**
     * Step 3b: Normalize stale industry values (restored from backup).
     */
    private function normalizeIndustries(): void
    {
        foreach ($this->industryMap as $old => $new) {
            DB::table('ad_accounts')->where('industry', $old)->update(['industry' => $new]);
        }
    }

    /**
     * Step 3c: Fill NULL countries with SA (Saudi-based accounts restored without country).
     */
    private function fillNullCountries(): void
    {
        $affected = DB::table('ad_accounts')->whereNull('country')->update(['country' => 'SA']);
        if ($affected > 0) {
            Log::info("Set country=SA for {$affected} accounts with NULL country");
        }
    }

    /**
     * Step 4: Propagate tenant_id from ad_accounts down to campaigns and metrics.
     * Restored rows have NULL tenant_id even when their parent account has one.
     */
    private function propagateTenantToCampaignsAndMetrics(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support UPDATE...JOIN — use subquery instead
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

        Log::info('Propagated tenant_id to campaigns and metrics with NULL tenant_id');
    }

    /**
     * Step 5: Verify the migration results.
     */
    private function verify(): void
    {
        // Check no duplicates remain
        $duplicates = DB::table('ad_accounts')
            ->select('external_account_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('external_account_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new \RuntimeException("Migration verification failed: {$duplicates} external_account_id(s) still have duplicates");
        }

        // Check no accounts remain on old integrations
        $oldIntegrationIds = array_keys($this->integrationMap);
        $staleCount = DB::table('ad_accounts')
            ->whereIn('integration_id', $oldIntegrationIds)
            ->count();

        if ($staleCount > 0) {
            throw new \RuntimeException("Migration verification failed: {$staleCount} accounts still on old integrations");
        }

        // Log final state
        $totalAccounts = DB::table('ad_accounts')->count();
        $totalMetrics = DB::table('ad_metrics')->count();
        $totalSpend = DB::table('ad_metrics')->sum('spend');
        $nullTenantAccounts = DB::table('ad_accounts')->whereNull('tenant_id')->count();
        $nullTenantCampaigns = DB::table('ad_campaigns')->whereNull('tenant_id')->count();
        $nullTenantMetrics = DB::table('ad_metrics')->whereNull('tenant_id')->count();

        Log::info('Migration verification passed', [
            'total_accounts' => $totalAccounts,
            'total_metrics' => $totalMetrics,
            'total_spend' => round($totalSpend, 2),
            'null_tenant_accounts' => $nullTenantAccounts,
            'null_tenant_campaigns' => $nullTenantCampaigns,
            'null_tenant_metrics' => $nullTenantMetrics,
        ]);
    }

    public function down(): void
    {
        // This migration cannot be safely reversed — the deleted duplicate accounts
        // and their cascaded campaigns/metrics cannot be reconstructed.
        // The next daily sync will re-create any needed data.
        Log::warning('Rolling back dedup migration is not supported — deleted accounts cannot be restored');
    }
};
