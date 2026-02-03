<?php

namespace App\Console\Commands;

use App\Services\AdAccountCategoryService;
use Illuminate\Console\Command;

class AutoPopulateAccountCategories extends Command
{
    protected $signature = 'accounts:auto-populate-categories {account_id? : Optional specific account ID to process}';
    protected $description = 'Auto-populate ad account categories based on their campaigns';

    private AdAccountCategoryService $categoryService;

    public function __construct(AdAccountCategoryService $categoryService)
    {
        parent::__construct();
        $this->categoryService = $categoryService;
    }

    public function handle()
    {
        $accountId = $this->argument('account_id');

        if ($accountId) {
            $this->info("Processing specific account ID: {$accountId}");
        } else {
            $this->info("Processing all ad accounts without categories...");
        }

        $results = $this->categoryService->autoPopulateAccountCategories($accountId);

        $this->info("");
        $this->info("=== Summary ===");
        $this->info("Total accounts processed: {$results['total_processed']}");
        $this->info("Total accounts updated: {$results['total_updated']}");
        $this->info("Total accounts skipped: {$results['total_skipped']}");

        if ($results['total_updated'] > 0) {
            $this->info("");
            $this->info("=== Updated Accounts ===");

            foreach ($results['accounts'] as $accountId => $result) {
                if ($result['success'] && $result['updated']) {
                    $source = $result['source'] === 'campaigns' ? 'from campaigns' : 'from industry default';
                    $this->info("Account {$accountId}: {$result['category']} ({$source})");

                    if ($result['source'] === 'campaigns') {
                        $this->line("  - Analyzed {$result['campaigns_analyzed']} campaigns");
                        $this->line("  - {$result['campaigns_with_this_category']} campaigns have this category");
                    }
                }
            }
        }

        if ($results['total_skipped'] > 0) {
            $this->info("");
            $this->info("=== Skipped Accounts ===");

            foreach ($results['accounts'] as $accountId => $result) {
                if (!$result['updated']) {
                    $reason = $result['error'] ?? $result['reason'] ?? 'unknown';
                    $this->line("Account {$accountId}: {$reason}");
                }
            }
        }

        $this->info("");
        $this->info("Auto-population completed!");

        return 0;
    }
}
