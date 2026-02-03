<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use Illuminate\Console\Command;

class ImportAdAccountIndustries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ad-accounts:import-industries
                            {file : Path to CSV file with industry assignments}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import industry assignments from CSV file';

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
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("📄 Reading CSV file: {$filePath}");
        $this->newLine();

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error("Failed to open file: {$filePath}");
            return 1;
        }

        // Read header
        $header = fgetcsv($file);
        if (!$header || !in_array('id', $header) || !in_array('suggested_industry', $header)) {
            $this->error('Invalid CSV format. Expected columns: id, account_name, platform, current_industry, suggested_industry');
            fclose($file);
            return 1;
        }

        $idIndex = array_search('id', $header);
        $industryIndex = array_search('suggested_industry', $header);

        $updates = [];
        $errors = [];
        $skipped = 0;

        // Read data rows
        while (($row = fgetcsv($file)) !== false) {
            $accountId = $row[$idIndex] ?? null;
            $suggestedIndustry = trim($row[$industryIndex] ?? '');

            if (!$accountId) {
                continue;
            }

            // Skip if no industry suggested
            if (empty($suggestedIndustry)) {
                $skipped++;
                continue;
            }

            // Validate industry
            if (!in_array($suggestedIndustry, $this->availableIndustries)) {
                $errors[] = "Account ID {$accountId}: Invalid industry '{$suggestedIndustry}'";
                continue;
            }

            $updates[] = [
                'id' => $accountId,
                'industry' => $suggestedIndustry,
            ];
        }

        fclose($file);

        // Show summary
        $this->info("📊 Import Summary:");
        $this->line("  - Valid updates: " . count($updates));
        $this->line("  - Skipped (empty): {$skipped}");
        $this->line("  - Errors: " . count($errors));
        $this->newLine();

        if (count($errors) > 0) {
            $this->warn("⚠️  Errors found:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();

            if (!$this->confirm('Continue with valid updates?', true)) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        if (count($updates) === 0) {
            $this->warn('No valid updates found in CSV file.');
            return 0;
        }

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn("🔍 DRY RUN - No changes will be made");
            $this->newLine();
            $this->info('Sample updates:');
            foreach (array_slice($updates, 0, 10) as $update) {
                $account = AdAccount::find($update['id']);
                if ($account) {
                    $this->line("  - Account #{$update['id']} ({$account->account_name}): {$update['industry']}");
                }
            }
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm("Import " . count($updates) . " industry assignments?", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        // Perform updates
        $updated = 0;
        $notFound = 0;
        $progressBar = $this->output->createProgressBar(count($updates));
        $progressBar->start();

        foreach ($updates as $update) {
            $account = AdAccount::find($update['id']);
            if (!$account) {
                $notFound++;
                $progressBar->advance();
                continue;
            }

            $account->update(['industry' => $update['industry']]);
            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Final summary
        $this->info("✅ Import Complete!");
        $this->line("  - Successfully updated: {$updated}");
        if ($notFound > 0) {
            $this->line("  - Accounts not found: {$notFound}");
        }

        return 0;
    }
}
