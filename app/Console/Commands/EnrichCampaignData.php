<?php

namespace App\Console\Commands;

use App\Services\CampaignEnrichmentService;
use Illuminate\Console\Command;

class EnrichCampaignData extends Command
{
    protected $signature = 'campaigns:enrich {--campaign_id= : Optional specific campaign ID to enrich}';
    protected $description = 'Enrich campaign data with target segment, age group, geo targeting, and message tone';

    private CampaignEnrichmentService $enrichmentService;

    public function __construct(CampaignEnrichmentService $enrichmentService)
    {
        parent::__construct();
        $this->enrichmentService = $enrichmentService;
    }

    public function handle()
    {
        $campaignId = $this->option('campaign_id');

        if ($campaignId) {
            $this->info("Enriching specific campaign ID: {$campaignId}");
            $campaign = \App\Models\AdCampaign::find($campaignId);

            if (!$campaign) {
                $this->error("Campaign not found.");
                return 1;
            }

            $result = $this->enrichmentService->enrichCampaign($campaign);

            if ($result['updated']) {
                $this->info("Campaign enriched successfully!");
                foreach ($result['changes'] as $change) {
                    $this->line("  - $change");
                }
            } else {
                $this->info("Campaign already has all data populated.");
            }

            return 0;
        }

        $this->info("Enriching all campaigns...");
        $this->info("");

        $results = $this->enrichmentService->enrichAllCampaigns();

        $this->info("");
        $this->info("=== Summary ===");
        $this->info("Total campaigns processed: {$results['total_processed']}");
        $this->info("Total campaigns enriched: {$results['total_updated']}");
        $this->info("Total campaigns skipped: " . ($results['total_processed'] - $results['total_updated']));

        if ($results['total_updated'] > 0 && $results['total_updated'] <= 20) {
            $this->info("");
            $this->info("=== Sample Enriched Campaigns ===");

            $sampleResults = array_slice($results['results'], 0, 10);
            foreach ($sampleResults as $result) {
                $this->info("Campaign {$result['id']}: {$result['name']}");
                foreach ($result['changes'] as $change) {
                    $this->line("  - $change");
                }
            }
        }

        $this->info("");
        $this->info("Campaign enrichment completed!");

        return 0;
    }
}
