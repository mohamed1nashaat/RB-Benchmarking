<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\FacebookMetricsSyncService;
use App\Traits\RefreshesFacebookToken;
use Illuminate\Console\Command;

class SyncFacebookCreatives extends Command
{
    use RefreshesFacebookToken;

    protected $signature = 'facebook:sync-creatives {integration_id?} {--account-id= : Specific ad account ID to sync}';
    protected $description = 'Sync ad creatives from Facebook for ad accounts';

    private FacebookMetricsSyncService $syncService;

    public function __construct(FacebookMetricsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle(): int
    {
        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');

        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'facebook')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'facebook')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->info('No active Facebook integrations found — nothing to sync');
            return 0;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing creatives for integration ID: {$integration->id}");

            $accessToken = $this->ensureValidFacebookToken($integration);
            if (!$accessToken) {
                $this->error("No valid access token for integration {$integration->id}");
                continue;
            }

            $query = $integration->adAccounts();
            if ($accountId) {
                $query->where('id', $accountId);
            }
            $adAccounts = $query->get();

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing creatives for account: {$adAccount->account_name}");
                $result = $this->syncService->syncCreativesForAccount($adAccount, $accessToken);
                if ($result['success']) {
                    $this->info("Synced {$result['synced']} creatives.");
                } else {
                    $this->error("Failed: {$result['error']}");
                }
            }
        }

        return 0;
    }
}
