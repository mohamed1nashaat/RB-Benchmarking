<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Services\BusinessLookupService;
use App\Services\CategoryMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateAdAccountIndustries extends Command
{
    protected $signature = 'ad-accounts:populate-industries
                            {--account= : Specific account ID to process}
                            {--platform= : Filter by platform (facebook, google, linkedin, snapchat)}
                            {--only-empty : Only process accounts without industry set}
                            {--dry-run : Preview changes without saving}
                            {--limit=100 : Maximum accounts to process}
                            {--min-confidence=0.3 : Minimum confidence threshold (0-1)}';

    protected $description = 'Auto-populate industry and category for ad accounts using business lookup';

    private BusinessLookupService $lookupService;

    public function __construct(BusinessLookupService $lookupService)
    {
        parent::__construct();
        $this->lookupService = $lookupService;
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Ad Account Industry & Category Population');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $minConfidence = (float) $this->option('min-confidence');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->info('');
        }

        // Build query
        $query = AdAccount::withoutGlobalScope('tenant')
            ->with('integration');

        // Filter by specific account
        if ($accountId = $this->option('account')) {
            $query->where('id', $accountId);
        }

        // Filter by platform
        if ($platform = $this->option('platform')) {
            $query->whereHas('integration', function ($q) use ($platform) {
                $q->where('platform', strtolower($platform));
            });
        }

        // Filter to only empty industries
        if ($this->option('only-empty')) {
            $query->whereNull('industry');
        }

        $query->limit($limit);

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No accounts found matching the criteria.');
            return 0;
        }

        $this->info("Processing {$accounts->count()} accounts...");
        $this->info('');

        // Stats
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped_low_confidence' => 0,
            'skipped_already_set' => 0,
            'failed' => 0,
        ];

        $results = [];

        $progressBar = $this->output->createProgressBar($accounts->count());
        $progressBar->start();

        foreach ($accounts as $account) {
            $stats['processed']++;

            try {
                // Skip if industry already set (unless --only-empty is not used)
                if ($account->industry && !$this->option('only-empty')) {
                    $stats['skipped_already_set']++;
                    $progressBar->advance();
                    continue;
                }

                // Lookup business
                $result = $this->lookupService->lookupBusiness($account->account_name);

                if (!$result || !$result['industry']) {
                    $results[] = [
                        'account' => $account->account_name,
                        'platform' => $account->integration->platform ?? 'unknown',
                        'status' => 'NO_MATCH',
                        'industry' => '-',
                        'confidence' => 0,
                    ];
                    $stats['failed']++;
                    $progressBar->advance();
                    continue;
                }

                // Check confidence threshold
                if ($result['confidence'] < $minConfidence) {
                    $results[] = [
                        'account' => $account->account_name,
                        'platform' => $account->integration->platform ?? 'unknown',
                        'status' => 'LOW_CONF',
                        'industry' => $result['industry'],
                        'confidence' => $result['confidence'],
                        'keywords' => implode(', ', $result['matched_keywords'] ?? []),
                    ];
                    $stats['skipped_low_confidence']++;
                    $progressBar->advance();
                    continue;
                }

                // Update account
                if (!$isDryRun) {
                    $account->update([
                        'industry' => $result['industry'],
                        'category' => $result['category'],
                    ]);
                }

                $results[] = [
                    'account' => $account->account_name,
                    'platform' => $account->integration->platform ?? 'unknown',
                    'status' => $isDryRun ? 'WOULD_UPDATE' : 'UPDATED',
                    'industry' => $result['industry'],
                    'category' => $result['category'] ?? '-',
                    'confidence' => $result['confidence'],
                    'keywords' => implode(', ', $result['matched_keywords'] ?? []),
                ];
                $stats['updated']++;

            } catch (\Exception $e) {
                $results[] = [
                    'account' => $account->account_name,
                    'platform' => $account->integration->platform ?? 'unknown',
                    'status' => 'ERROR',
                    'error' => $e->getMessage(),
                ];
                $stats['failed']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info('');
        $this->info('');

        // Show results table
        $this->info('Results:');
        $this->info('');

        $tableHeaders = ['Account', 'Platform', 'Status', 'Industry', 'Category', 'Confidence', 'Keywords'];
        $tableRows = array_map(function ($r) {
            return [
                Str($r['account'])->limit(30),
                $r['platform'] ?? '-',
                $r['status'],
                $r['industry'] ?? '-',
                $r['category'] ?? '-',
                isset($r['confidence']) ? number_format($r['confidence'] * 100, 0) . '%' : '-',
                Str($r['keywords'] ?? '')->limit(25),
            ];
        }, $results);

        $this->table($tableHeaders, $tableRows);

        // Summary
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info("  Total Processed:      {$stats['processed']}");
        $this->info("  Updated:              {$stats['updated']}" . ($isDryRun ? ' (dry run)' : ''));
        $this->info("  Skipped (Low Conf):   {$stats['skipped_low_confidence']}");
        $this->info("  Skipped (Already Set):{$stats['skipped_already_set']}");
        $this->info("  Failed:               {$stats['failed']}");
        $this->info('');

        if ($isDryRun && $stats['updated'] > 0) {
            $this->warn("Run without --dry-run to apply these changes.");
        }

        return 0;
    }
}
