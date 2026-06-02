<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\Integration;
use App\Services\LinkedInAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLinkedInCreatives extends Command
{
    protected $signature = 'linkedin:sync-creatives
                            {--account-id= : Specific ad account ID to sync}
                            {--integration-id= : Specific integration ID to sync}';

    protected $description = 'Sync ad creatives from LinkedIn to ad_creatives table';

    private LinkedInAdsService $linkedInAdsService;

    public function __construct(LinkedInAdsService $linkedInAdsService)
    {
        parent::__construct();
        $this->linkedInAdsService = $linkedInAdsService;
    }

    public function handle(): int
    {
        $accountId = $this->option('account-id');
        $integrationId = $this->option('integration-id');

        $query = Integration::where('platform', 'linkedin')
            ->where('status', 'active');

        if ($integrationId) {
            $query->where('id', $integrationId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->error('No active LinkedIn integrations found');
            return Command::FAILURE;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing LinkedIn creatives for integration ID: {$integration->id}");

            $accountsQuery = $integration->adAccounts();
            if ($accountId) {
                $accountsQuery->where('id', $accountId);
            }
            $adAccounts = $accountsQuery->get();

            foreach ($adAccounts as $adAccount) {
                $this->info("Syncing creatives for account: {$adAccount->account_name}");
                try {
                    $synced = $this->linkedInAdsService->syncCreatives($integration, $adAccount);
                    $this->info("Synced " . count($synced) . " creatives.");
                } catch (\Exception $e) {
                    Log::error('LinkedIn creatives sync command error', [
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
