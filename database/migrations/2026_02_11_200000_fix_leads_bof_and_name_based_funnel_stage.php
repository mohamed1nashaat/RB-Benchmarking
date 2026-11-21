<?php

use App\Models\AdCampaign;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 0: Fix leads objective campaigns from MOF → BOF
        DB::statement("
            UPDATE ad_campaigns
            SET funnel_stage = 'BOF'
            WHERE objective = 'leads' AND funnel_stage = 'MOF'
        ");

        // Step 1: Backfill null funnel_stage using name-based keyword detection
        AdCampaign::withoutGlobalScopes()
            ->whereNull('funnel_stage')
            ->chunk(200, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $stage = AdCampaign::funnelStageFromName($campaign->name);
                    if ($stage) {
                        $campaign->updateQuietly(['funnel_stage' => $stage]);
                    }
                }
            });

        // Step 2: Sync metrics funnel_stage from their parent campaign
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

        // Also fix metrics that had MOF from the old leads mapping
        DB::statement("
            UPDATE ad_metrics
            SET funnel_stage = (
                SELECT c.funnel_stage FROM ad_campaigns c
                WHERE c.id = ad_metrics.ad_campaign_id
            )
            WHERE ad_campaign_id IS NOT NULL
              AND EXISTS (
                  SELECT 1 FROM ad_campaigns c
                  WHERE c.id = ad_metrics.ad_campaign_id
                    AND c.funnel_stage IS NOT NULL
                    AND c.funnel_stage != ad_metrics.funnel_stage
              )
        ");
    }

    public function down(): void
    {
        // No reversal — funnel_stage values are non-destructive enrichment
    }
};
