<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\TikTokAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTikTokCreatives extends Command
{
    protected $signature = 'tiktok:sync-creatives
                            {integration_id? : Specific integration ID to sync}
                            {--account-id= : Specific ad account ID to sync}';

    protected $description = 'Sync ad creatives from TikTok for ad accounts';

    private TikTokAdsService $tikTokAdsService;

    public function __construct(TikTokAdsService $tikTokAdsService)
    {
        parent::__construct();
        $this->tikTokAdsService = $tikTokAdsService;
    }

    public function handle(): int
    {
        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');

        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'tiktok')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'tiktok')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->info('No active TikTok integrations found — nothing to sync');
            return Command::SUCCESS;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing creatives for integration ID: {$integration->id}");

            $config = $integration->app_config;
            if (is_string($config)) {
                $config = json_decode($config, true);
            }

            if (!is_array($config) || !isset($config['access_token'])) {
                $this->error("No access_token in app_config for integration {$integration->id}");
                continue;
            }

            $accessToken = $config['access_token'];

            $query = $integration->adAccounts();
            if ($accountId) {
                $query->where('id', $accountId);
            }
            $adAccounts = $query->get();

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing creatives for account: {$adAccount->account_name}");
                try {
                    $result = $this->tikTokAdsService->syncCreativesForAccount($adAccount, $accessToken);
                    if ($result['success']) {
                        $this->info("Synced {$result['synced']} creatives.");
                    } else {
                        $this->error("Failed: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    Log::error('TikTok creatives sync command error', [
                        'integration_id' => $integration->id,
                        'account_id' => $adAccount->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Exception: {$e->getMessage()}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
