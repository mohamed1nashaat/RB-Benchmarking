<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use Illuminate\Console\Command;

class BackfillAdAccountIndustries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ad-accounts:backfill-industries
                            {--default= : Set all NULL industries to this default value (e.g., "other")}
                            {--platform= : Only update accounts for a specific platform}
                            {--dry-run : Show what would be updated without actually updating}
                            {--export= : Export accounts with NULL industry to CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing industry values for ad accounts';

    /**
     * Available industries based on your system
     */
    protected array $availableIndustries = [
        'automotive',
        'beauty_fitness',
        'business_industrial',
        'computers_electronics',
        'education',
        'entertainment',
        'finance_insurance',
        'food_beverage',
        'health_medicine',
        'home_garden',
        'law_government',
        'lifestyle',
        'media_publishing',
        'nonprofit',
        'real_estate',
        'retail_ecommerce',
        'sports_recreation',
        'technology',
        'travel_tourism',
        'transportation_logistics',
        'other',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing ad accounts with missing industries...');
        $this->newLine();

        // Get accounts with NULL industry
        $query = AdAccount::whereNull('industry')->with('integration');

        if ($platform = $this->option('platform')) {
            $query->whereHas('integration', function ($q) use ($platform) {
                $q->where('platform', $platform);
            });
        }

        $accountsWithNullIndustry = $query->get();
        $total = $accountsWithNullIndustry->count();

        if ($total === 0) {
            $this->info('✅ All ad accounts already have industries assigned!');
            return 0;
        }

        $this->warn("Found {$total} ad accounts with NULL industry");
        $this->newLine();

        // Show breakdown by platform
        $this->info('📊 Breakdown by Platform:');
        $byPlatform = $accountsWithNullIndustry->groupBy(fn($acc) => $acc->integration->platform ?? 'unknown');
        foreach ($byPlatform as $platform => $accounts) {
            $this->line("  - " . strtoupper($platform) . ": {$accounts->count()} accounts");
        }
        $this->newLine();

        // Handle export option
        if ($exportPath = $this->option('export')) {
            return $this->exportToCSV($accountsWithNullIndustry, $exportPath);
        }

        // Handle default option
        if ($defaultIndustry = $this->option('default')) {
            if (!in_array($defaultIndustry, $this->availableIndustries)) {
                $this->error("Invalid industry: {$defaultIndustry}");
                $this->info('Available industries: ' . implode(', ', $this->availableIndustries));
                return 1;
            }

            return $this->setDefaultIndustry($accountsWithNullIndustry, $defaultIndustry);
        }

        // Interactive mode
        return $this->interactiveMode($accountsWithNullIndustry);
    }

    /**
     * Export accounts to CSV for manual editing
     */
    protected function exportToCSV($accounts, string $path): int
    {
        $this->info("📄 Exporting to: {$path}");

        $file = fopen($path, 'w');
        if (!$file) {
            $this->error("Failed to create file: {$path}");
            return 1;
        }

        // Write header
        fputcsv($file, ['id', 'account_name', 'platform', 'current_industry', 'suggested_industry']);

        // Write data
        foreach ($accounts as $account) {
            fputcsv($file, [
                $account->id,
                $account->account_name,
                $account->integration->platform ?? 'unknown',
                $account->industry ?? '',
                '', // Leave empty for manual entry
            ]);
        }

        fclose($file);

        $this->info("✅ Exported {$accounts->count()} accounts to: {$path}");
        $this->newLine();
        $this->info('📝 Instructions:');
        $this->line('  1. Open the CSV file');
        $this->line('  2. Fill in the "suggested_industry" column with values from:');
        $this->line('     ' . implode(', ', array_slice($this->availableIndustries, 0, 10)));
        $this->line('     ' . implode(', ', array_slice($this->availableIndustries, 10)));
        $this->line('  3. Run: php artisan ad-accounts:import-industries ' . $path);
        $this->newLine();

        return 0;
    }

    /**
     * Set all NULL industries to a default value
     */
    protected function setDefaultIndustry($accounts, string $industry): int
    {
        $total = $accounts->count();

        if ($this->option('dry-run')) {
            $this->warn("🔍 DRY RUN - No changes will be made");
            $this->info("Would set {$total} accounts to industry: {$industry}");
            $this->newLine();
            $this->info('Sample accounts that would be updated:');
            foreach ($accounts->take(10) as $account) {
                $this->line("  - {$account->account_name} ({$account->integration->platform})");
            }
            return 0;
        }

        if (!$this->confirm("Set {$total} accounts to industry '{$industry}'?")) {
            $this->info('Cancelled.');
            return 0;
        }

        $updated = 0;
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($accounts as $account) {
            $account->update(['industry' => $industry]);
            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully updated {$updated} ad accounts to industry: {$industry}");

        return 0;
    }

    /**
     * Interactive mode to assign industries
     */
    protected function interactiveMode($accounts): int
    {
        $this->info('📝 Interactive Industry Assignment');
        $this->newLine();
        $this->info('Available options:');
        $this->line('  1. Set all to "other" (safest default)');
        $this->line('  2. Set all to a specific industry');
        $this->line('  3. Assign by platform');
        $this->line('  4. Export to CSV for manual editing');
        $this->line('  5. Cancel');
        $this->newLine();

        $choice = $this->choice('What would you like to do?', [
            '1' => 'Set all to "other"',
            '2' => 'Set all to a specific industry',
            '3' => 'Assign by platform',
            '4' => 'Export to CSV',
            '5' => 'Cancel'
        ], '1');

        switch ($choice) {
            case '1':
            case 'Set all to "other"':
                return $this->setDefaultIndustry($accounts, 'other');

            case '2':
            case 'Set all to a specific industry':
                $industry = $this->choice('Select industry:', $this->availableIndustries);
                return $this->setDefaultIndustry($accounts, $industry);

            case '3':
            case 'Assign by platform':
                return $this->assignByPlatform($accounts);

            case '4':
            case 'Export to CSV':
                $path = $this->ask('Enter CSV file path', '/tmp/ad-accounts-null-industries.csv');
                return $this->exportToCSV($accounts, $path);

            case '5':
            case 'Cancel':
            default:
                $this->info('Cancelled.');
                return 0;
        }
    }

    /**
     * Assign industries per platform
     */
    protected function assignByPlatform($accounts): int
    {
        $byPlatform = $accounts->groupBy(fn($acc) => $acc->integration->platform ?? 'unknown');
        $updated = 0;

        foreach ($byPlatform as $platform => $platformAccounts) {
            $this->newLine();
            $this->info("Platform: " . strtoupper($platform) . " ({$platformAccounts->count()} accounts)");

            $industry = $this->choice(
                "Select industry for {$platform} accounts:",
                array_merge(['skip' => 'Skip this platform'], $this->availableIndustries),
                'skip'
            );

            if ($industry === 'skip' || $industry === 'Skip this platform') {
                $this->line("Skipping {$platform}");
                continue;
            }

            foreach ($platformAccounts as $account) {
                $account->update(['industry' => $industry]);
                $updated++;
            }

            $this->info("✅ Updated {$platformAccounts->count()} {$platform} accounts to: {$industry}");
        }

        $this->newLine();
        $this->info("✅ Total updated: {$updated} accounts");

        return 0;
    }
}
