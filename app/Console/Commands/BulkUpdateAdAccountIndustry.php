<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use Illuminate\Console\Command;

class BulkUpdateAdAccountIndustry extends Command
{
    protected $signature = 'ad-accounts:bulk-update-industry
                            {--dry-run : Show what would be updated without making changes}
                            {--list : List all accounts with current industry/category}
                            {--null-only : Only update accounts with null industry}';

    protected $description = 'Bulk update ad account industry and category based on account name patterns';

    protected array $patterns = [
        // Sports - MUST come before fashion to avoid "al-ittihad" matching fashion
        'sports' => [
            'industry' => 'entertainment',
            'category' => 'Sports & Events',
            'patterns' => ['ittihad fc', 'al-ittihad fc', 'al ittihad fc', 'itihad fc', ' fc', 'makkah expo'],
        ],
        // Real Estate - specific patterns to avoid false positives
        'real_estate' => [
            'industry' => 'real_estate',
            'category' => 'Residential Projects',
            'patterns' => ['diyar', 'alfursan', 'dar al arkan', 'dar alarkan', 'aziz', 'sedra', 'qetaf', 'rawabi', 'modon', 'rafal', 'sumou', 'roshn', 'neom', 'amakin', 'eskan', 'alargan', 'real estate', 'realestate', 'bin seadan', 'bin saadan', 'bin saedan', 'burj assila', 'hajar', 'tamakkon', 'rua al', 'ruaa', 'qudra', 'atharna'],
        ],
        // Retail / E-commerce
        'retail' => [
            'industry' => 'retail_ecommerce',
            'category' => 'Home & Furniture',
            'patterns' => ['kohler', 'mancini', 'roca'],
        ],
        // Fashion Retail (without al-ittihad - that's sports)
        'fashion_retail' => [
            'industry' => 'retail_ecommerce',
            'category' => 'Fashion & Apparel',
            'patterns' => ['shiaka', 'alshiaka', 'al-shiaka', 'peony'],
        ],
        // FMCG Retail / D2C
        'fmcg_retail' => [
            'industry' => 'retail_ecommerce',
            'category' => 'Direct-to-Consumer (D2C)',
            'patterns' => ['nashar', 'testahel'],
        ],
        // Education - specific patterns
        'education' => [
            'industry' => 'education',
            'category' => 'K–12 Schools',
            'patterns' => ['jadara', 'jadarah', 'jaddarah', 'school', 'waad', 'rowad', 'academy'],
        ],
        // Travel & Tourism
        'travel_tourism' => [
            'industry' => 'travel_tourism',
            'category' => 'Local Attractions & Experiences',
            'patterns' => ['teamlab', 'shangrila', 'shangri-la', 'balad', 'albalad', 'al balad', 'ghs'],
        ],
        // Technology - specific patterns to avoid "ai" false positives
        'technology' => [
            'industry' => 'technology',
            'category' => 'Software / SaaS',
            'patterns' => ['zkra', 'mainzilia', 'nahr', 'codestand', 'hala', 'rmeez', 'shift inc', 'viatra', 'digital mobility'],
        ],
        // Food & Beverage
        'food_beverage' => [
            'industry' => 'food_beverage',
            'category' => 'Restaurants & Cafes',
            'patterns' => ['ribs', 'ribsyard', 'almaddah', 'barn', 'barns', 'coffee', 'kal coffee', 'kal '],
        ],
        // Finance & Insurance
        'finance' => [
            'industry' => 'finance',
            'category' => 'Investments & Wealth Management',
            'patterns' => ['sedco', 'waed', 'wa\'ed'],
        ],
        // Fintech
        'fintech' => [
            'industry' => 'finance',
            'category' => 'Fintech',
            'patterns' => ['monshaat'],
        ],
        // Health & Medicine
        'health_medicine' => [
            'industry' => 'healthcare',
            'category' => 'Hospitals & Clinics',
            'patterns' => ['cura', 'hospital', 'clinic', 'medical', 'health'],
        ],
        // Automotive
        'automotive' => [
            'industry' => 'automotive',
            'category' => 'Vehicles',
            'patterns' => ['autohub', 'automotive'],
        ],
        // Beauty & Fitness
        'beauty_fitness' => [
            'industry' => 'beauty_fitness',
            'category' => 'Personal Care',
            'patterns' => ['spa ceylon', 'spa ', 'beauty', 'salon', 'fitness', 'gym'],
        ],
        // Government / Public Sector
        'government' => [
            'industry' => 'government',
            'category' => 'Government',
            'patterns' => ['ministry', 'saudi moh', 'governmental'],
        ],
        // HR & Recruitment
        'hr' => [
            'industry' => 'professional_services',
            'category' => 'HR & Recruitment',
            'patterns' => ['hrc'],
        ],
        // Professional Services / Marketing
        'professional_services' => [
            'industry' => 'professional_services',
            'category' => 'Marketing & Advertising',
            'patterns' => ['red bananas', 'rb benchmarks'],
        ],
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listAccounts();
        }

        $dryRun = $this->option('dry-run');
        $nullOnly = $this->option('null-only');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        if ($nullOnly) {
            $this->info('NULL ONLY MODE - Only updating accounts with null industry');
        }

        $query = AdAccount::withoutGlobalScopes();
        if ($nullOnly) {
            $query->whereNull('industry');
        }
        $accounts = $query->get();
        $this->info("Found {$accounts->count()} ad accounts");

        $updated = 0;
        $skipped = 0;
        $noMatch = 0;

        $updates = [];

        foreach ($accounts as $account) {
            $accountName = strtolower($account->account_name);
            $matched = false;

            foreach ($this->patterns as $key => $config) {
                foreach ($config['patterns'] as $pattern) {
                    if (str_contains($accountName, strtolower($pattern))) {
                        $updates[] = [
                            'account' => $account,
                            'industry' => $config['industry'],
                            'category' => $config['category'],
                            'matched_pattern' => $pattern,
                        ];
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $noMatch++;
                if ($this->getOutput()->isVerbose()) {
                    $this->line("No match for: {$account->account_name}");
                }
            }
        }

        // Show summary of planned updates
        $this->info("\nPlanned updates: " . count($updates));
        $this->info("No match found: {$noMatch}");
        $this->newLine();

        // Group by industry for display
        $byIndustry = collect($updates)->groupBy('industry');
        foreach ($byIndustry as $industry => $group) {
            $this->info("=== {$industry} ({$group->count()} accounts) ===");
            foreach ($group as $item) {
                $account = $item['account'];
                $currentIndustry = $account->industry ?? 'null';
                $currentCategory = $account->category ?? 'null';

                $status = '';
                if ($currentIndustry === $item['industry'] && $currentCategory === $item['category']) {
                    $status = ' [ALREADY SET]';
                    $skipped++;
                } else {
                    $status = " [WILL UPDATE from {$currentIndustry}/{$currentCategory}]";
                    $updated++;
                }

                $this->line("  - {$account->account_name} (pattern: {$item['matched_pattern']}){$status}");
            }
            $this->newLine();
        }

        if (!$dryRun) {
            if (!$this->confirm('Do you want to apply these updates?')) {
                $this->info('Aborted.');
                return 0;
            }

            $actualUpdated = 0;
            foreach ($updates as $item) {
                $account = $item['account'];
                if ($account->industry !== $item['industry'] || $account->category !== $item['category']) {
                    $account->industry = $item['industry'];
                    $account->category = $item['category'];
                    $account->save();
                    $actualUpdated++;
                }
            }

            $this->info("\nActually updated: {$actualUpdated} accounts");
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Would update: {$updated}");
        $this->info("  - Already correct: {$skipped}");
        $this->info("  - No pattern match: {$noMatch}");

        return 0;
    }

    protected function listAccounts(): int
    {
        $accounts = AdAccount::withoutGlobalScopes()
            ->orderBy('industry')
            ->orderBy('category')
            ->orderBy('account_name')
            ->get();

        $this->table(
            ['ID', 'Account Name', 'Industry', 'Category', 'Platform'],
            $accounts->map(fn($a) => [
                $a->id,
                substr($a->account_name, 0, 50),
                $a->industry ?? 'null',
                $a->category ?? 'null',
                $a->integration?->platform ?? 'N/A',
            ])
        );

        $this->newLine();
        $this->info("Total accounts: {$accounts->count()}");

        // Show industry distribution
        $byIndustry = $accounts->groupBy('industry');
        $this->newLine();
        $this->info("By Industry:");
        foreach ($byIndustry as $industry => $group) {
            $this->line("  - " . ($industry ?: 'null') . ": {$group->count()}");
        }

        return 0;
    }
}
