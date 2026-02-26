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
        DB::statement("
            UPDATE ad_metrics m
            JOIN ad_campaigns c ON m.ad_campaign_id = c.id
            SET m.funnel_stage = c.funnel_stage
            WHERE m.funnel_stage IS NULL AND c.funnel_stage IS NOT NULL
        ");

        // Also fix metrics that had MOF from the old leads mapping
        DB::statement("
            UPDATE ad_metrics m
            JOIN ad_campaigns c ON m.ad_campaign_id = c.id
            SET m.funnel_stage = c.funnel_stage
            WHERE m.funnel_stage != c.funnel_stage AND c.funnel_stage IS NOT NULL
        ");
    }

    public function down(): void
    {
        // No reversal — funnel_stage values are non-destructive enrichment
    }
};
