<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\AdAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MergeDuplicateClients extends Command
{
    protected $signature = 'clients:merge-duplicates
                            {--dry-run : Show what would be merged without making changes}
                            {--auto : Skip confirmations for high-priority merges}
                            {--group= : Merge only a specific group by normalized name}';

    protected $description = 'Merge duplicate client records by consolidating their ad accounts';

    private $duplicateGroups = [];
    private $mergeMap = [];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isAuto = $this->option('auto');
        $specificGroup = $this->option('group');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Analyzing tenants for duplicates...');
        $this->newLine();

        // Detect duplicate groups
        $this->detectDuplicates();

        if (empty($this->duplicateGroups)) {
            $this->info('✅ No duplicates found!');
            return 0;
        }

        // Filter to specific group if requested
        if ($specificGroup) {
            $normalizedSearch = $this->normalizeName($specificGroup);
            $this->duplicateGroups = array_filter(
                $this->duplicateGroups,
                fn($key) => $key === $normalizedSearch,
                ARRAY_FILTER_USE_KEY
            );

            if (empty($this->duplicateGroups)) {
                $this->error("No duplicate group found matching: {$specificGroup}");
                return 1;
            }
        }

        // Display duplicates
        $this->displayDuplicates();

        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN complete. No changes made.');
            return 0;
        }

        // Define high-priority merges
        $highPriority = $this->getHighPriorityMerges();

        // Confirm before proceeding
        $totalGroups = count($this->duplicateGroups);
        $totalRecords = array_sum(array_map(fn($g) => count($g['tenants']) - 1, $this->duplicateGroups));

        if (!$isAuto && !$specificGroup) {
            $this->newLine();
            $this->warn("⚠️  This will merge {$totalGroups} duplicate groups ({$totalRecords} records)");
            if (!$this->confirm('Do you want to proceed with the merge?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Execute merge
        $this->newLine();
        $this->info('Starting merge operations...');
        $this->newLine();

        $merged = 0;
        $errors = 0;

        foreach ($this->duplicateGroups as $normalizedName => $group) {
            $isPriority = in_array($normalizedName, $highPriority);

            // Skip low priority if auto mode
            if ($isAuto && !$isPriority) {
                $this->line("  ⊘ Skipped (low priority): {$group['display_name']}");
                continue;
            }

            // Confirm individual merge if not auto
            if (!$isAuto && !$specificGroup) {
                $recordCount = count($group['tenants']) - 1;
                if (!$this->confirm("  Merge {$group['display_name']} ({$recordCount} duplicates)?", $isPriority)) {
                    $this->line("  ⊘ Skipped: {$group['display_name']}");
                    continue;
                }
            }

            try {
                $result = $this->mergeTenantGroup($group);
                if ($result['success']) {
                    $merged++;
                    $this->info("  ✓ Merged: {$group['display_name']} ({$result['accounts_moved']} accounts moved, {$result['duplicates_removed']} duplicates removed)");
                } else {
                    $errors++;
                    $this->error("  ✗ Failed: {$group['display_name']} - {$result['error']}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error: {$group['display_name']} - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Groups merged: {$merged}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }
        $this->newLine();
        $this->info('✅ Done!');

        return 0;
    }

    /**
     * Detect duplicate tenant groups
     */
    private function detectDuplicates(): void
    {
        $tenants = Tenant::with('adAccounts')->get();
        $groups = [];

        foreach ($tenants as $tenant) {
            $normalized = $this->normalizeName($tenant->name);

            if (!isset($groups[$normalized])) {
                $groups[$normalized] = [];
            }

            $groups[$normalized][] = $tenant;
        }

        // Filter to only groups with duplicates
        foreach ($groups as $normalizedName => $tenantList) {
            if (count($tenantList) > 1) {
                // Determine primary tenant
                $primary = $this->selectPrimaryTenant($tenantList);
                $duplicates = array_filter($tenantList, fn($t) => $t->id !== $primary->id);

                $this->duplicateGroups[$normalizedName] = [
                    'display_name' => $primary->name,
                    'primary' => $primary,
                    'duplicates' => array_values($duplicates),
                    'tenants' => $tenantList,
                ];
            }
        }
    }

    /**
     * Normalize tenant name for comparison
     */
    private function normalizeName(string $name): string
    {
        // Remove common suffixes and descriptors
        $suffixes = [
            'USD', 'Not Used', '\[RB\]', 'RB', 'KSA', 'Middle East',
            'Real estate', 'FMCG', 'Education', 'Ecommerce', 'FC',
            'Self Service', 'Destinations', 'NEW', 'V2', 'NEW V2',
            'School', 'Academy', 'Online', 'App', 'Ventures',
            'Podcasts', 'Creative Services', 'June 2024',
            'Credit line - 20 Aug 2025', 'Sleepworld', 'Al-Madienah',
            'Al Madinah', 'Al-Arabia', 'Al Arabia', 'Bin', 'Al-',
            'SEDCO', 'Journey', 'Bloom', 'Dar Althikr', 'Waad Academy'
        ];

        $cleaned = $name;

        // Remove RBP project codes (e.g., "RBP-00115", "RBP-00126")
        $cleaned = preg_replace('/^RBP-\d+\s*-?\s*/', '', $cleaned);
        $cleaned = preg_replace('/\bRBP-\d+\b/', '', $cleaned);

        // Remove brackets and their content
        $cleaned = preg_replace('/\[.*?\]/', '', $cleaned);

        // Remove date patterns
        $cleaned = preg_replace('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4}\b/', '', $cleaned);
        $cleaned = preg_replace('/\b\d{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4}\b/', '', $cleaned);

        // Remove version indicators
        $cleaned = preg_replace('/\b(v\d+|version\s*\d+)\b/i', '', $cleaned);

        // Remove suffixes
        foreach ($suffixes as $suffix) {
            $cleaned = preg_replace('/\s*-?\s*' . preg_quote($suffix, '/') . '\s*$/i', '', $cleaned);
            $cleaned = preg_replace('/\s*' . preg_quote($suffix, '/') . '\s*-?\s*/i', ' ', $cleaned);
        }

        // Normalize apostrophes and possessives
        $cleaned = str_replace("'s", '', $cleaned);
        $cleaned = str_replace("'", '', $cleaned);

        // Convert to lowercase
        $cleaned = strtolower($cleaned);

        // Normalize double letters (e.g., Ittihad -> Itihad, Jaddarah -> Jadara)
        $cleaned = preg_replace('/([a-z])\1+/', '$1', $cleaned);

        // Remove special characters but keep spaces temporarily
        $cleaned = preg_replace('/[^a-z0-9\s]+/', '', $cleaned);

        // Remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        // Finally remove all spaces
        $cleaned = str_replace(' ', '', $cleaned);

        return $cleaned;
    }

    /**
     * Select primary tenant from a group
     */
    private function selectPrimaryTenant(array $tenants): Tenant
    {
        // Sort by: 1) Most ad accounts, 2) Earliest created, 3) Shortest name
        usort($tenants, function ($a, $b) {
            $accountsA = $a->adAccounts->count();
            $accountsB = $b->adAccounts->count();

            if ($accountsA !== $accountsB) {
                return $accountsB - $accountsA; // More accounts first
            }

            $dateCompare = $a->created_at <=> $b->created_at;
            if ($dateCompare !== 0) {
                return $dateCompare; // Earlier first
            }

            return strlen($a->name) <=> strlen($b->name); // Shorter name first
        });

        return $tenants[0];
    }

    /**
     * Display duplicate groups
     */
    private function displayDuplicates(): void
    {
        $this->info('Found ' . count($this->duplicateGroups) . ' duplicate groups:');
        $this->newLine();

        foreach ($this->duplicateGroups as $group) {
            $primary = $group['primary'];
            $duplicates = $group['duplicates'];

            $this->line("<fg=cyan>━━━ {$group['display_name']} ━━━</>");
            $this->line("  Primary: <fg=green>ID {$primary->id}</> - {$primary->name} ({$primary->adAccounts->count()} accounts)");

            foreach ($duplicates as $dup) {
                $this->line("  Duplicate: <fg=yellow>ID {$dup->id}</> - {$dup->name} ({$dup->adAccounts->count()} accounts)");
            }

            $this->newLine();
        }
    }

    /**
     * Merge a tenant group
     */
    private function mergeTenantGroup(array $group): array
    {
        $primary = $group['primary'];
        $duplicates = $group['duplicates'];

        DB::beginTransaction();

        try {
            $accountsMoved = 0;
            $duplicatesRemoved = 0;

            foreach ($duplicates as $duplicate) {
                // Move all ad accounts to primary tenant
                $accountCount = AdAccount::where('tenant_id', $duplicate->id)->count();

                AdAccount::where('tenant_id', $duplicate->id)
                    ->update(['tenant_id' => $primary->id]);

                $accountsMoved += $accountCount;

                // Soft delete the duplicate tenant
                $duplicate->delete();
                $duplicatesRemoved++;
            }

            // Update primary tenant's subscription tier based on new account count
            $totalAccounts = $primary->adAccounts()->count();
            $newTier = $this->calculateTier($totalAccounts, $primary->monthly_budget ?? 0);

            if ($primary->subscription_tier !== $newTier) {
                $primary->update(['subscription_tier' => $newTier]);
            }

            DB::commit();

            return [
                'success' => true,
                'accounts_moved' => $accountsMoved,
                'duplicates_removed' => $duplicatesRemoved,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate subscription tier based on accounts and budget
     */
    private function calculateTier(int $accountCount, float $monthlyBudget): string
    {
        if ($accountCount >= 16 || $monthlyBudget > 100000) {
            return 'enterprise';
        } elseif ($accountCount >= 6 || $monthlyBudget > 25000) {
            return 'pro';
        } else {
            return 'basic';
        }
    }

    /**
     * Get list of high-priority merge candidates
     */
    private function getHighPriorityMerges(): array
    {
        return [
            // Original duplicates
            'aldiyar',           // Al-Diyar / Aldyar / Aldyar's
            'alrashed',          // Alrashed
            'alshiaka',          // Al-shiaka
            'burjasila',         // Burj Assila
            'ribsyard',          // Ribs Yard
            'shanrilajeda',      // Shangrila Jeddah
            'teamlaborderlesjeda', // TeamLab

            // New duplicates found
            'alitihad',          // Al-Itihad / Al-Ittihad FC
            'faisalsadan',       // Faisal Bin Saadan/Saedan/Seadan
            'jadara',            // Jadara/Jadarah/Jaddarah School
            'mancini',           // Mancini / Mancini's Sleepworld
            'spaceylon',         // SPA Ceylon
            'roca',              // Roca
            'mainzilia',         // MainZilia / Mainzilia
            'rua',               // RUA / Ruaa Al-Madienah
            'daralarkan',        // Dar Al Arkan / Dar Al Arkan Online
            'nahr',              // Nahr
            'albalad',           // Al Balad / Jeddahalbalad
            'kohler',            // Kohler
        ];
    }
}
