<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill campaigns: set funnel_stage where objective exists but funnel_stage is null
        DB::statement("
            UPDATE ad_campaigns
            SET funnel_stage = CASE objective
                WHEN 'awareness' THEN 'TOF'
                WHEN 'leads' THEN 'BOF'
                WHEN 'sales' THEN 'BOF'
                WHEN 'calls' THEN 'BOF'
            END
            WHERE objective IS NOT NULL AND funnel_stage IS NULL
              AND objective IN ('awareness', 'leads', 'sales', 'calls')
        ");

        // Backfill metrics: sync funnel_stage from their parent campaign
        // Use subquery syntax compatible with both MySQL and SQLite
        DB::statement("
            UPDATE ad_metrics
            SET funnel_stage = (
                SELECT c.funnel_stage FROM ad_campaigns c
                WHERE c.id = ad_metrics.ad_campaign_id
            )
            WHERE funnel_stage IS NULL
              AND ad_campaign_id IS NOT NULL
              AND EXISTS (
                  SELECT 1 FROM ad_campaigns c
                  WHERE c.id = ad_metrics.ad_campaign_id AND c.funnel_stage IS NOT NULL
              )
        ");
    }

    public function down(): void
    {
        // No reversal — funnel_stage values are non-destructive enrichment
    }
};
