<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check {tenant_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and evaluate all active alerts for all tenants or a specific tenant';

    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            // Check alerts for specific tenant
            $this->checkAlertsForTenant((int) $tenantId);
        } else {
            // Check alerts for all active tenants
            $tenants = Tenant::where('status', 'active')->get();

            $this->info("Checking alerts for {$tenants->count()} active tenants...");

            $totalEvaluated = 0;
            $totalTriggered = 0;
            $totalErrors = 0;

            foreach ($tenants as $tenant) {
                $results = $this->checkAlertsForTenant($tenant->id);
                $totalEvaluated += $results['evaluated'];
                $totalTriggered += $results['triggered'];
                $totalErrors += $results['errors'];
            }

            $this->info("\n=== Alert Check Summary ===");
            $this->info("Tenants processed: {$tenants->count()}");
            $this->info("Alerts evaluated: {$totalEvaluated}");
            $this->info("Alerts triggered: {$totalTriggered}");
            $this->info("Errors: {$totalErrors}");
        }

        return 0;
    }

    /**
     * Check alerts for a specific tenant
     */
    private function checkAlertsForTenant(int $tenantId): array
    {
        try {
            // Temporarily set the tenant context
            app()->instance('current_tenant_id', $tenantId);

            $this->line("Checking alerts for Tenant ID: {$tenantId}");

            $results = $this->alertService->evaluateAlerts($tenantId);

            if ($results['evaluated'] > 0) {
                $this->line("  - Evaluated: {$results['evaluated']}");
                $this->line("  - Triggered: {$results['triggered']}");

                if ($results['errors'] > 0) {
                    $this->warn("  - Errors: {$results['errors']}");
                }
            }

            return $results;

        } catch (\Exception $e) {
            $this->error("Error checking alerts for tenant {$tenantId}: " . $e->getMessage());

            Log::error('Failed to check alerts for tenant', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'evaluated' => 0,
                'triggered' => 0,
                'errors' => 1,
            ];
        }
    }
}
