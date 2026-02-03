<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PopulateCampaignObjectives extends Command
{
    protected $signature = 'campaigns:populate-objectives
                            {--account= : Specific account ID to process}
                            {--only-empty : Only process campaigns without objective set}
                            {--dry-run : Preview changes without saving}
                            {--limit=500 : Maximum campaigns to process}';

    protected $description = 'Auto-populate objective for campaigns by inferring from campaign name';

    /**
     * Keywords that indicate sales/conversion objective
     */
    private array $salesKeywords = [
        'sales',
        'pmax',
        'p-max',
        'performance max',
        'conversion',
        'purchase',
        'revenue',
        'roas',
        'shopping',
        'ecommerce',
        'e-commerce',
        'checkout',
        'buy',
        'order',
    ];

    /**
     * Keywords that indicate leads objective
     */
    private array $leadsKeywords = [
        'lead',
        'leads',
        'app install',
        'app-install',
        'app promotion',
        'registration',
        'register',
        'signup',
        'sign-up',
        'sign up',
        'form',
        'inquiry',
        'enquiry',
        'contact',
        'whatsapp',
        'messenger',
        'download',
        'install',
    ];

    /**
     * Keywords that indicate awareness objective (default fallback)
     */
    private array $awarenessKeywords = [
        'awareness',
        'reach',
        'impressions',
        'traffic',
        'search',
        'display',
        'video',
        'view',
        'engagement',
        'brand',
        'visibility',
        'discovery',
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Campaign Objective Population');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->info('');
        }

        // Build query
        $query = AdCampaign::withoutGlobalScope('tenant');

        // Filter by specific account
        if ($accountId = $this->option('account')) {
            $query->where('ad_account_id', $accountId);
        }

        // Filter to only empty objectives
        if ($this->option('only-empty')) {
            $query->where(function ($q) {
                $q->whereNull('objective')
                  ->orWhere('objective', '');
            });
        }

        $query->limit($limit);

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->warn('No campaigns found matching the criteria.');
            return 0;
        }

        $this->info("Processing {$campaigns->count()} campaigns...");
        $this->info('');

        // Stats
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped_already_set' => 0,
            'by_objective' => [
                'awareness' => 0,
                'leads' => 0,
                'sales' => 0,
            ],
        ];

        $results = [];

        $progressBar = $this->output->createProgressBar($campaigns->count());
        $progressBar->start();

        foreach ($campaigns as $campaign) {
            $stats['processed']++;

            // Skip if already set (and not --only-empty)
            if ($campaign->objective && !$this->option('only-empty')) {
                $stats['skipped_already_set']++;
                $progressBar->advance();
                continue;
            }

            // Infer objective from campaign name
            $objective = $this->inferObjective($campaign->name);

            if (!$isDryRun) {
                $campaign->update([
                    'objective' => $objective,
                ]);
            }

            $results[] = [
                'campaign' => $campaign->name,
                'status' => $isDryRun ? 'WOULD_UPDATE' : 'UPDATED',
                'objective' => $objective,
            ];

            $stats['updated']++;
            $stats['by_objective'][$objective]++;

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info('');
        $this->info('');

        // Show results table (limit to 50 for display)
        $this->info('Results (showing up to 50):');
        $this->info('');

        $tableHeaders = ['Campaign', 'Status', 'Objective'];
        $tableRows = array_map(function ($r) {
            return [
                Str::limit($r['campaign'] ?? '-', 50),
                $r['status'],
                $r['objective'],
            ];
        }, array_slice($results, 0, 50));

        $this->table($tableHeaders, $tableRows);

        // Summary
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info("  Total Processed:      {$stats['processed']}");
        $this->info("  Updated:              {$stats['updated']}" . ($isDryRun ? ' (dry run)' : ''));
        $this->info("  Skipped (Already Set):{$stats['skipped_already_set']}");
        $this->info('');
        $this->info('  By Objective:');
        $this->info("    - Awareness: {$stats['by_objective']['awareness']}");
        $this->info("    - Leads:     {$stats['by_objective']['leads']}");
        $this->info("    - Sales:     {$stats['by_objective']['sales']}");
        $this->info('');

        if ($isDryRun && $stats['updated'] > 0) {
            $this->warn("Run without --dry-run to apply these changes.");
        }

        return 0;
    }

    /**
     * Infer objective from campaign name
     */
    private function inferObjective(string $campaignName): string
    {
        $nameLower = strtolower($campaignName);

        // Check for sales keywords first (highest priority)
        foreach ($this->salesKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'sales';
            }
        }

        // Check for leads keywords
        foreach ($this->leadsKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return 'leads';
            }
        }

        // Default to awareness
        return 'awareness';
    }
}
