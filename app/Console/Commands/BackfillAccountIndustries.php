<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Services\IndustryDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAccountIndustries extends Command
{
    protected $signature = 'accounts:backfill-industries
                            {--force : Force update even if industry is already set}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Backfill industry classification for ad accounts using auto-detection';

    private IndustryDetectionService $industryDetector;

    public function __construct(IndustryDetectionService $industryDetector)
    {
        parent::__construct();
        $this->industryDetector = $industryDetector;
    }

    public function handle(): int
    {
        $this->info('🔍 Starting industry backfill process...');

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Query accounts that need industry classification
        $query = AdAccount::query();

        if (!$force) {
            // Use whereNull for accounts without industry (no need to check empty string)
            $query->whereNull('industry');
        }

        $accounts = $query->get();
        $totalAccounts = $accounts->count();

        if ($totalAccounts === 0) {
            $this->info('✅ All accounts already have industry classification!');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$totalAccounts} accounts needing classification");
        $this->newLine();

        $classified = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($totalAccounts);
        $progressBar->start();

        foreach ($accounts as $account) {
            // Try to detect industry from account name
            $detectedIndustry = $this->industryDetector->detectIndustry($account->account_name);

            if ($detectedIndustry) {
                if (!$isDryRun) {
                    $account->industry = $detectedIndustry;
                    $account->save();
                }
                $classified++;

                $this->line("\n✅ {$account->account_name} → {$detectedIndustry}");
            } else {
                $failed++;
                $this->line("\n❌ {$account->account_name} → No match found");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📈 Classification Summary:');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['✅ Successfully Classified', $classified, round(($classified / $totalAccounts) * 100, 1) . '%'],
                ['❌ Failed to Classify', $failed, round(($failed / $totalAccounts) * 100, 1) . '%'],
                ['📊 Total Processed', $totalAccounts, '100%'],
            ]
        );

        if ($isDryRun) {
            $this->warn('⚠️  This was a DRY RUN - no changes were made');
            $this->info('💡 Run without --dry-run to apply changes');
        } else {
            $this->info('✅ Industry backfill completed!');

            if ($failed > 0) {
                $this->newLine();
                $this->warn("⚠️  {$failed} accounts could not be auto-classified");
                $this->info('💡 These accounts will need manual classification in the Ad Accounts page');
            }
        }

        // Show industry breakdown
        if (!$isDryRun && $classified > 0) {
            $this->newLine();
            $this->info('📊 Industry Distribution:');

            $distribution = DB::table('ad_accounts')
                ->select('industry', DB::raw('count(*) as count'))
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderByDesc('count')
                ->get();

            $rows = $distribution->map(function ($item) {
                $displayName = $this->industryDetector->getIndustryDisplayName($item->industry);
                return [$displayName, $item->count];
            })->toArray();

            $this->table(['Industry', 'Accounts'], $rows);
        }

        return Command::SUCCESS;
    }
}
