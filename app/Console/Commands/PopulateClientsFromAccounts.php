<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PopulateClientsFromAccounts extends Command
{
    protected $signature = 'clients:populate-from-accounts {--dry-run : Show what would be created without actually creating}';
    protected $description = 'Create client records from existing ad accounts by detecting company names';

    private $platformSuffixes = [
        'Facebook', 'Google', 'LinkedIn', 'Snapchat', 'Twitter', 'TikTok',
        'FB', 'IG', 'Instagram', 'Meta', 'Ads', 'Ad Account', 'Account'
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->info('');
        }

        $this->info('Analyzing ad accounts to create clients...');
        $this->info('');

        // Get all ad accounts
        $accounts = AdAccount::with('integration')->get();
        $this->info("Found {$accounts->count()} ad accounts");
        $this->info('');

        // Group accounts by detected company name
        $companyGroups = $this->groupAccountsByCompany($accounts);

        $this->info("Detected " . count($companyGroups) . " unique companies");
        $this->info('');

        // Show summary
        $this->table(
            ['Company', 'Accounts', 'Industries', 'Total Spend (SAR)'],
            collect($companyGroups)->map(function ($accounts, $company) {
                $industries = $accounts->pluck('industry')->filter()->unique()->implode(', ');
                $spend = $accounts->sum(function ($account) {
                    return $account->adMetrics->sum('spend');
                });
                return [
                    $company,
                    $accounts->count(),
                    $industries ?: 'N/A',
                    number_format($spend, 2)
                ];
            })->sortByDesc(fn($row) => (float) str_replace(',', '', $row[3]))->values()->all()
        );

        if ($isDryRun) {
            $this->info('');
            $this->warn('DRY RUN complete. No changes made.');
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm('Do you want to create these ' . count($companyGroups) . ' clients?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('');
        $this->info('Creating clients...');
        $this->newLine();

        $created = 0;
        $updated = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            foreach ($companyGroups as $companyName => $accounts) {
                try {
                    $result = $this->createOrUpdateClient($companyName, $accounts);

                    if ($result['created']) {
                        $created++;
                        $this->info("  ✓ Created: {$companyName} ({$accounts->count()} accounts)");
                    } else {
                        $updated++;
                        $this->line("  ↻ Updated: {$companyName} ({$accounts->count()} accounts)");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ✗ Error: {$companyName} - " . $e->getMessage());
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('=== Summary ===');
            $this->info("Clients created: {$created}");
            $this->info("Clients updated: {$updated}");
            if ($errors > 0) {
                $this->error("Errors: {$errors}");
            }
            $this->newLine();
            $this->info('✅ Done!');

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Transaction failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Group ad accounts by detected company name
     */
    private function groupAccountsByCompany($accounts)
    {
        $groups = [];

        foreach ($accounts as $account) {
            $companyName = $this->extractCompanyName($account->account_name);

            if (!isset($groups[$companyName])) {
                $groups[$companyName] = collect();
            }

            $groups[$companyName]->push($account);
        }

        return $groups;
    }

    /**
     * Extract company name from account name
     */
    private function extractCompanyName(string $accountName): string
    {
        // Remove common platform suffixes
        $cleaned = $accountName;
        foreach ($this->platformSuffixes as $suffix) {
            $cleaned = preg_replace('/\s*-?\s*' . preg_quote($suffix, '/') . '\s*$/i', '', $cleaned);
            $cleaned = preg_replace('/\s*' . preg_quote($suffix, '/') . '\s*-?\s*/i', '', $cleaned);
        }

        // Remove trailing/leading special characters and whitespace
        $cleaned = trim($cleaned, " \t\n\r\0\x0B-_|");

        // If the cleaned name is empty or too short, use original
        if (strlen($cleaned) < 2) {
            $cleaned = $accountName;
        }

        // Remove any account numbers or IDs at the start (e.g., "123456 - Company Name")
        $cleaned = preg_replace('/^\d+\s*-\s*/', '', $cleaned);

        return trim($cleaned) ?: $accountName;
    }

    /**
     * Create or update a client from ad accounts
     */
    private function createOrUpdateClient(string $companyName, $accounts): array
    {
        $slug = Str::slug($companyName);

        // Detect most common industry
        $industries = $accounts->pluck('industry')->filter()->countBy();
        $primaryIndustry = $industries->sortDesc()->keys()->first() ?? 'other';

        // Calculate total spend
        $totalSpend = $accounts->sum(function ($account) {
            return $account->adMetrics->sum('spend');
        });

        // Calculate monthly budget (average spend per month)
        $monthsOfData = max(1, $accounts->flatMap->adMetrics->pluck('date')->unique()->count() / 30);
        $monthlyBudget = round($totalSpend / max($monthsOfData, 1), -3); // Round to nearest 1000

        // Determine subscription tier
        $accountCount = $accounts->count();
        if ($accountCount >= 16 || $monthlyBudget > 100000) {
            $tier = 'enterprise';
        } elseif ($accountCount >= 6 || $monthlyBudget > 25000) {
            $tier = 'pro';
        } else {
            $tier = 'basic';
        }

        // Find existing client with same name or slug
        $tenant = Tenant::where('slug', $slug)
            ->orWhere('name', $companyName)
            ->first();

        $isNew = !$tenant;

        if (!$tenant) {
            // Create new tenant
            $tenant = Tenant::create([
                'name' => $companyName,
                'slug' => $slug,
                'industry' => $primaryIndustry,
                'status' => 'active',
                'subscription_tier' => $tier,
                'monthly_budget' => $monthlyBudget,
                'contract_start_date' => now(),
                'contract_end_date' => now()->addYear(),
                'settings' => [
                    'auto_created' => true,
                    'created_from_accounts' => true,
                    'account_count' => $accountCount,
                ],
            ]);
        } else {
            // Update existing tenant
            $tenant->update([
                'industry' => $primaryIndustry,
                'subscription_tier' => $tier,
                'monthly_budget' => $monthlyBudget,
            ]);
        }

        // Assign all accounts to this tenant
        $accounts->each(function ($account) use ($tenant) {
            $account->update(['tenant_id' => $tenant->id]);
        });

        return ['created' => $isNew, 'tenant' => $tenant];
    }
}
