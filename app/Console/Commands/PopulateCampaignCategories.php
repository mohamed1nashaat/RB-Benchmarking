<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use App\Services\CategoryMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PopulateCampaignCategories extends Command
{
    protected $signature = 'campaigns:populate-categories
                            {--account= : Specific account ID to process}
                            {--only-empty : Only process campaigns without sub_industry set}
                            {--dry-run : Preview changes without saving}
                            {--limit=500 : Maximum campaigns to process}';

    protected $description = 'Auto-populate sub_industry and category for campaigns based on account industry';

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Campaign Sub-Industry & Category Population');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->info('');
        }

        // Build query
        $query = AdCampaign::withoutGlobalScope('tenant')
            ->with('adAccount');

        // Filter by specific account
        if ($accountId = $this->option('account')) {
            $query->where('ad_account_id', $accountId);
        }

        // Filter to only empty sub_industry
        if ($this->option('only-empty')) {
            $query->where(function ($q) {
                $q->whereNull('sub_industry')
                  ->orWhere('sub_industry', '');
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
            'skipped_no_industry' => 0,
            'skipped_already_set' => 0,
        ];

        $results = [];

        $progressBar = $this->output->createProgressBar($campaigns->count());
        $progressBar->start();

        foreach ($campaigns as $campaign) {
            $stats['processed']++;

            $account = $campaign->adAccount;

            // Skip if account has no industry
            if (!$account || !$account->industry) {
                $results[] = [
                    'campaign' => $campaign->name,
                    'account' => $account->account_name ?? 'Unknown',
                    'status' => 'NO_INDUSTRY',
                    'sub_industry' => '-',
                    'category' => '-',
                ];
                $stats['skipped_no_industry']++;
                $progressBar->advance();
                continue;
            }

            // Skip if already set (and not --only-empty)
            if ($campaign->sub_industry && !$this->option('only-empty')) {
                $stats['skipped_already_set']++;
                $progressBar->advance();
                continue;
            }

            // Detect category from campaign name
            $category = CategoryMapper::detectCategory(
                $campaign->name,
                $account->industry
            ) ?? CategoryMapper::getDefaultCategory($account->industry);

            if ($category) {
                if (!$isDryRun) {
                    $campaign->update([
                        'sub_industry' => $category,
                        'category' => $category,
                    ]);
                }

                $results[] = [
                    'campaign' => $campaign->name,
                    'account' => $account->account_name,
                    'industry' => $account->industry,
                    'status' => $isDryRun ? 'WOULD_UPDATE' : 'UPDATED',
                    'sub_industry' => $category,
                    'category' => $category,
                ];
                $stats['updated']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info('');
        $this->info('');

        // Show results table (limit to 50 for display)
        $this->info('Results (showing up to 50):');
        $this->info('');

        $tableHeaders = ['Campaign', 'Account', 'Industry', 'Status', 'Sub-Industry', 'Category'];
        $tableRows = array_map(function ($r) {
            return [
                Str::limit($r['campaign'] ?? '-', 35),
                Str::limit($r['account'] ?? '-', 20),
                $r['industry'] ?? '-',
                $r['status'],
                $r['sub_industry'] ?? '-',
                Str::limit($r['category'] ?? '-', 25),
            ];
        }, array_slice($results, 0, 50));

        $this->table($tableHeaders, $tableRows);

        // Summary
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info("  Total Processed:        {$stats['processed']}");
        $this->info("  Updated:                {$stats['updated']}" . ($isDryRun ? ' (dry run)' : ''));
        $this->info("  Skipped (No Industry):  {$stats['skipped_no_industry']}");
        $this->info("  Skipped (Already Set):  {$stats['skipped_already_set']}");
        $this->info('');

        if ($isDryRun && $stats['updated'] > 0) {
            $this->warn("Run without --dry-run to apply these changes.");
        }

        return 0;
    }
}
