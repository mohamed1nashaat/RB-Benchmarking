<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\GoogleAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleAdsCreatives extends Command
{
    protected $signature = 'google-ads:sync-creatives
                            {integration_id? : Specific integration ID to sync}
                            {--account-id= : Specific ad account ID to sync}';

    protected $description = 'Sync ad creatives from Google Ads for ad accounts';

    private GoogleAdsService $googleAdsService;

    public function __construct(GoogleAdsService $googleAdsService)
    {
        parent::__construct();
        $this->googleAdsService = $googleAdsService;
    }

    public function handle(): int
    {
        $integrationId = $this->argument('integration_id');
        $accountId = $this->option('account-id');

        if ($integrationId) {
            $integrations = Integration::where('id', $integrationId)
                ->where('platform', 'google')
                ->get();
        } else {
            $integrations = Integration::where('platform', 'google')
                ->where('status', 'active')
                ->get();
        }

        if ($integrations->isEmpty()) {
            $this->info('No active Google Ads integrations found — nothing to sync');
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
            $refreshToken = $config['refresh_token'] ?? null;

            $query = $integration->adAccounts();
            if ($accountId) {
                $query->where('id', $accountId);
            }
            $adAccounts = $query->get();

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing creatives for account: {$adAccount->account_name}");
                try {
                    $result = $this->googleAdsService->syncCreativesForAccount($adAccount, $accessToken, $refreshToken);
                    if ($result['success']) {
                        $this->info("Synced {$result['synced']} creatives.");
                    } else {
                        $this->error("Failed: {$result['error']}");
                    }
                } catch (\Exception $e) {
                    Log::error('Google Ads creatives sync command error', [
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
