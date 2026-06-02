<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\GoogleAdsService;
use App\Services\IndustryDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleAdsAccounts extends Command
{
    protected $signature = 'google-ads:sync-accounts
                            {integration_id? : Specific integration ID to sync}';

    protected $description = 'Discover and sync ad accounts from Google Ads MCC (Manager) accounts';

    public function __construct(
        private GoogleAdsService $googleAdsService,
        private IndustryDetectionService $industryDetectionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $integrationId = $this->argument('integration_id');

        $query = Integration::where('platform', 'google')->where('status', 'active');
        if ($integrationId) {
            $query->where('id', $integrationId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active Google Ads integrations found — nothing to sync');
            return 0;
        }

        foreach ($integrations as $integration) {
            $this->syncAccountsForIntegration($integration);
        }

        return 0;
    }

    private function syncAccountsForIntegration(Integration $integration): void
    {
        $tenantLabel = $integration->tenant?->name ?? 'No Tenant (MCC)';
        $this->info("Processing integration ID: {$integration->id} ({$tenantLabel})");

        $config = $integration->app_config;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        if (!is_array($config) || !isset($config['access_token'])) {
            $this->error("  No access_token in app_config for integration {$integration->id}");
            return;
        }

        $accessToken = $config['access_token'];
        $refreshToken = $config['refresh_token'] ?? null;

        try {
            $result = $this->googleAdsService->getAdAccounts($accessToken, $refreshToken);
        } catch (\Exception $e) {
            $this->error("  API call failed: {$e->getMessage()}");
            Log::error('Google Ads account discovery failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $apiAccounts = $result['accounts'] ?? [];
        $failedAccounts = $result['failed'] ?? [];

        if (empty($apiAccounts)) {
            $this->warn("  No accounts returned from API.");
            return;
        }

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($apiAccounts as $account) {
            try {
                $existing = AdAccount::where('integration_id', $integration->id)
                    ->where('external_account_id', $account['id'])
                    ->first();

                $industry = $this->industryDetectionService->detectIndustry($account['name'] ?? '');

                $accountConfig = [
                    'customer_id' => $account['customer_id'] ?? null,
                    'time_zone' => $account['time_zone'] ?? null,
                    'is_manager' => $account['is_manager'] ?? false,
                    'is_test_account' => $account['is_test_account'] ?? false,
                    'parent_manager_id' => $account['parent_manager_id'] ?? null,
                ];

                if ($existing) {
                    // Update name, currency, status, config — preserve tenant_id
                    // Do NOT override permission_denied — that flag is set by the campaign sync
                    // and auto-cleared when a campaign sync succeeds. Overriding it here
                    // would cause a daily reset loop (account sync resets → campaign sync marks again).
                    $newStatus = ($existing->status === 'permission_denied')
                        ? 'permission_denied'
                        : ($account['status'] ?? 'active');
                    $existing->update([
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $newStatus,
                        'industry' => $existing->industry ?? $industry,
                        'account_config' => $accountConfig,
                    ]);
                    $updated++;
                } else {
                    // New account — tenant_id = NULL (unassigned)
                    AdAccount::create([
                        'integration_id' => $integration->id,
                        'tenant_id' => null,
                        'external_account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $account['status'] ?? 'active',
                        'industry' => $industry,
                        'account_config' => $accountConfig,
                    ]);
                    $created++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  Failed account {$account['id']}: {$e->getMessage()}");
            }
        }

        $integration->update(['last_sync_at' => now()]);

        $this->info("  Results: {$created} created, {$updated} updated, {$failed} failed");

        if (!empty($failedAccounts)) {
            $this->warn("  API reported " . count($failedAccounts) . " inaccessible account(s)");
            foreach ($failedAccounts as $fa) {
                $this->line("    - {$fa['customer_id']}: {$fa['error']}");
            }
        }

        $totalAccounts = AdAccount::where('integration_id', $integration->id)->count();
        $this->info("  Total accounts for this integration: {$totalAccounts}");
    }
}
