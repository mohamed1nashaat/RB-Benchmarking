<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ClientBuilderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PopulateClientsFromAdAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:populate-from-accounts
                            {--dry-run : Preview changes without saving}
                            {--force : Override existing client data}
                            {--tenant= : Process specific tenant ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate client information from existing ad accounts';

    protected $builderService;

    public function __construct(ClientBuilderService $builderService)
    {
        parent::__construct();
        $this->builderService = $builderService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $tenantId = $this->option('tenant');

        $this->info('===========================================');
        $this->info('  Populate Clients from Ad Accounts  ');
        $this->info('===========================================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        if ($force) {
            $this->warn('⚠️  FORCE MODE - Will override existing data');
            $this->newLine();
        }

        // Get tenants to process
        $query = Tenant::with('adAccounts');

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to process.');
            return 1;
        }

        $this->info("Found {$tenants->count()} tenant(s) to process");
        $this->newLine();

        $stats = [
            'total' => $tenants->count(),
            'updated' => 0,
            'skipped' => 0,
            'no_accounts' => 0,
            'errors' => 0,
        ];

        $changes = [];

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            $bar->advance();

            try {
                if ($tenant->adAccounts->isEmpty()) {
                    $stats['no_accounts']++;
                    continue;
                }

                // Check if already has data (unless force)
                if (!$force && $this->hasClientData($tenant)) {
                    $stats['skipped']++;
                    continue;
                }

                if (!$dryRun) {
                    $result = $this->builderService->populateTenantFromAccounts($tenant, $force);

                    if ($result['success']) {
                        $stats['updated']++;
                        $changes[$tenant->id] = [
                            'name' => $tenant->name,
                            'changes' => $result['changes'],
                            'data' => $result['data']['suggested'],
                        ];
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    // Dry run - just show what would be done
                    $accountIds = $tenant->adAccounts->pluck('id')->toArray();
                    $suggestions = $this->builderService->suggestClientInfo($accountIds);

                    $changes[$tenant->id] = [
                        'name' => $tenant->name,
                        'suggestions' => $suggestions['suggested'],
                        'summary' => $suggestions['accounts_summary'],
                    ];
                    $stats['updated']++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Error populating tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($stats, $changes, $dryRun);

        return 0;
    }

    /**
     * Check if tenant already has client data
     */
    private function hasClientData(Tenant $tenant): bool
    {
        return !empty($tenant->industry)
            || !empty($tenant->description)
            || !empty($tenant->subscription_tier);
    }

    /**
     * Display results summary
     */
    private function displayResults(array $stats, array $changes, bool $dryRun): void
    {
        $this->info('===========================================');
        $this->info('  Results Summary  ');
        $this->info('===========================================');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tenants', $stats['total']],
                [$dryRun ? 'Would Update' : 'Updated', $stats['updated']],
                ['Skipped (already has data)', $stats['skipped']],
                ['Skipped (no ad accounts)', $stats['no_accounts']],
                ['Errors', $stats['errors']],
            ]
        );

        $this->newLine();

        if (!empty($changes)) {
            $this->info('📋 Detailed Changes:');
            $this->newLine();

            foreach ($changes as $tenantId => $change) {
                $this->line("• <comment>{$change['name']}</comment> (ID: {$tenantId})");

                if ($dryRun) {
                    $this->line("  <fg=gray>Suggestions:</>");
                    if (!empty($change['suggestions']['industry'])) {
                        $this->line("    Industry: {$change['suggestions']['industry']}");
                    }
                    if (!empty($change['suggestions']['description'])) {
                        $this->line("    Description: {$change['suggestions']['description']}");
                    }
                    if (!empty($change['suggestions']['subscription_tier'])) {
                        $this->line("    Tier: {$change['suggestions']['subscription_tier']}");
                    }
                    if (!empty($change['summary'])) {
                        $this->line("    Accounts: {$change['summary']['total_accounts']}");
                        $this->line("    Platforms: " . implode(', ', $change['summary']['platforms']));
                    }
                } else {
                    $fields = implode(', ', $change['changes']);
                    $this->line("  <fg=green>✓ Updated: {$fields}</>");
                }

                $this->newLine();
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('💡 This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('✅ Client population completed!');
        }
    }
}
