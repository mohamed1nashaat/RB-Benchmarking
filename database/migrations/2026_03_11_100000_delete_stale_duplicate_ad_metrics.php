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
        $deleted = DB::delete("
            DELETE m FROM ad_metrics m
            JOIN ad_accounts a ON m.ad_account_id = a.id
            WHERE m.tenant_id != a.tenant_id
            AND EXISTS (
                SELECT 1 FROM ad_metrics m2
                WHERE m2.ad_account_id = m.ad_account_id
                AND m2.ad_campaign_id = m.ad_campaign_id
                AND m2.platform = m.platform
                AND m2.date = m.date
                AND m2.tenant_id = a.tenant_id
            )
        ");

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
