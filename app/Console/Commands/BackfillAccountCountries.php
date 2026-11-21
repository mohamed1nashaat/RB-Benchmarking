<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use Illuminate\Console\Command;

class BackfillAccountCountries extends Command
{
    protected $signature = 'ad-accounts:backfill-countries
                            {--dry-run : Preview changes without saving}
                            {--only-empty : Only process accounts without country set}';

    protected $description = 'Backfill country for ad accounts based on name patterns and geo signals';

    private array $countryPatterns = [
        'United Arab Emirates' => ['uae', 'dubai', 'abu dhabi', 'sharjah', 'ajman'],
        'Qatar'                => ['qatar', 'doha'],
        'Egypt'                => ['egypt', 'cairo', 'alexandria', 'egp ', 'egp-', 'egp_acc', 'egy'],
        'India'                => ['india', 'indian', 'mumbai', 'delhi'],
        'United States'        => ['usa', 'united states', 'america', 'sacramento', 'california', 'monterey', 'livermore', 'milpitas', 'san ramon', 'san jose', 'san francisco', 'burlingame', 'capitola', 'pacifica', 'citrus heights', 'folsom', 'rocklin'],
        'United Kingdom'       => ['uk', 'united kingdom', 'britain', 'london'],
        'Saudi Arabia'         => ['ksa', 'saudi', 'saudi arabia', 'riyadh', 'jeddah', 'dammam', 'makkah', 'madinah', 'taif', 'khobar', 'jubail', 'tabuk', 'abha'],
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $onlyEmpty = $this->option('only-empty');

        $query = AdAccount::query();
        if ($onlyEmpty) {
            $query->whereNull('country');
        }
        $accounts = $query->get();

        $this->info("Processing {$accounts->count()} accounts...");

        $updated = 0;
        $skipped = 0;
        $noMatch = 0;

        foreach ($accounts as $account) {
            $detected = $this->detectCountry($account);

            if ($detected) {
                if (!$isDryRun) {
                    $account->country = $detected;
                    $account->save();
                }
                $this->line(($isDryRun ? '[DRY] ' : '') . "{$account->account_name} → {$detected}");
                $updated++;
            } else {
                $noMatch++;
                if ($this->getOutput()->isVerbose()) {
                    $this->line("No match: {$account->account_name}");
                }
            }
        }

        $this->newLine();
        $this->info("Summary: Updated: {$updated}, No match: {$noMatch}");

        return 0;
    }

    private function detectCountry(AdAccount $account): ?string
    {
        $name = strtolower($account->account_name);

        // Check account name patterns
        foreach ($this->countryPatterns as $country => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($name, $pattern)) {
                    return $country;
                }
            }
        }

        // Fallback: check campaign geo_targeting for this account
        $geo = $account->adCampaigns()
            ->whereNotNull('geo_targeting')
            ->value('geo_targeting');

        if ($geo) {
            $geoLower = strtolower($geo);
            if (str_contains($geoLower, 'uae') || str_contains($geoLower, 'dubai')) {
                return 'United Arab Emirates';
            }
            if (str_contains($geoLower, 'qatar')) {
                return 'Qatar';
            }
            if (str_contains($geoLower, 'egypt') || str_contains($geoLower, 'cairo')) {
                return 'Egypt';
            }
            if (str_contains($geoLower, 'usa') || str_contains($geoLower, 'america')) {
                return 'United States';
            }
            if (str_contains($geoLower, 'ksa') || str_contains($geoLower, 'saudi')) {
                return 'Saudi Arabia';
            }
        }

        // Only skip obvious test/placeholder accounts
        $testNames = ['don\'t use', 'do not use', 'xx', 'test account', 'placeholder'];
        $isTest = in_array($name, $testNames) || preg_match('/^\d+$/', $account->account_name);

        // Default to Saudi Arabia for non-test accounts
        if (!$isTest) {
            return 'Saudi Arabia';
        }

        return null;
    }
}
