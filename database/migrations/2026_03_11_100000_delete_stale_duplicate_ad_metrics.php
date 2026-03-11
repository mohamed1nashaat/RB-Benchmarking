<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Delete stale duplicate ad_metric rows where tenant_id doesn't match
     * the current ad_accounts.tenant_id AND a newer row exists with the
     * correct tenant_id for the same (ad_account_id, ad_campaign_id, platform, date).
     *
     * Root cause: tenant_id was included in the updateOrCreate match key,
     * so reassigning an account to a different tenant created duplicates
     * instead of updating.
     */
    public function up(): void
    {
        // MySQL doesn't allow subquery on the same table being deleted,
        // so collect IDs first, then delete in batches.
        $staleIds = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereColumn('m.tenant_id', '!=', 'a.tenant_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ad_metrics as m2')
                    ->whereColumn('m2.ad_account_id', 'm.ad_account_id')
                    ->whereColumn('m2.ad_campaign_id', 'm.ad_campaign_id')
                    ->whereColumn('m2.platform', 'm.platform')
                    ->whereColumn('m2.date', 'm.date')
                    ->whereColumn('m2.tenant_id', 'a.tenant_id');
            })
            ->pluck('m.id');

        $deleted = 0;
        foreach ($staleIds->chunk(500) as $chunk) {
            $deleted += DB::table('ad_metrics')->whereIn('id', $chunk)->delete();
        }

        Log::info("Deleted {$deleted} stale duplicate ad_metric rows from tenant_id mismatch");
    }

    /**
     * This migration is not reversible — deleted rows cannot be restored.
     */
    public function down(): void
    {
        // Data-only migration, not reversible
    }
};
