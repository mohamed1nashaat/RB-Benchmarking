<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\SnapchatAdsService;
use Illuminate\Console\Command;

class SyncSnapchatCreatives extends Command
{
    protected $signature = 'snapchat:sync-creatives {--account-id= : Specific ad account ID to sync} {--integration-id= : Specific integration ID to sync}';
    protected $description = 'Sync ad creatives from Snapchat for ad accounts';

    private SnapchatAdsService $snapchatAdsService;

    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
    }

    public function handle(): int
    {
        $accountId = $this->option('account-id');
        $integrationId = $this->option('integration-id');

        $query = Integration::where('platform', 'snapchat')
            ->where('status', 'active');

        if ($integrationId) {
            $query->where('id', $integrationId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->error('No active Snapchat integrations found');
            return Command::FAILURE;
        }

        foreach ($integrations as $integration) {
            $this->info("Processing integration ID: {$integration->id}");

            $accessToken = $this->snapchatAdsService->ensureValidToken($integration);
            if (!$accessToken) {
                $integration->update(['status' => 'needs_reauth']);
                $this->error("No valid access token for integration {$integration->id}");
                continue;
            }

            $accountQuery = $integration->adAccounts();
            if ($accountId) {
                $accountQuery->where('id', $accountId);
            }
            $adAccounts = $accountQuery->get();

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing creatives for account: {$adAccount->account_name}");
                $result = $this->snapchatAdsService->syncCreativesForAccount($adAccount, $accessToken);
                if ($result['success']) {
                    $this->info("Synced {$result['synced']} creatives.");
                } else {
                    $this->error("Failed: {$result['error']}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
