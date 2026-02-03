<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\SnapchatAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshSnapchatToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapchat:refresh-token {--force : Force refresh even if not expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Snapchat OAuth access token using refresh token';

    private SnapchatAdsService $snapchatAdsService;

    /**
     * Create a new command instance.
     */
    public function __construct(SnapchatAdsService $snapchatAdsService)
    {
        parent::__construct();
        $this->snapchatAdsService = $snapchatAdsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking Snapchat token status...');

        try {
            // Get active Snapchat integration
            $integration = Integration::where('platform', 'snapchat')
                ->where('status', 'active')
                ->first();

            if (!$integration) {
                $this->warn('No active Snapchat integration found');
                return Command::SUCCESS;
            }

            $config = $integration->app_config;
            $refreshToken = $config['refresh_token'] ?? null;
            $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;

            if (!$refreshToken) {
                $this->error('No refresh token available. User needs to reconnect Snapchat.');
                return Command::FAILURE;
            }

            // Check if token needs refresh (expires in less than 30 minutes or force flag)
            $needsRefresh = $this->option('force');

            if ($expiresAt) {
                $expiresTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
                $minutesUntilExpiry = ($expiresTimestamp - time()) / 60;

                $this->info("Token expires in {$minutesUntilExpiry} minutes");

                if ($minutesUntilExpiry < 30) {
                    $needsRefresh = true;
                    $this->info('Token expiring soon, will refresh');
                }
            } else {
                // No expiry info, refresh to be safe
                $needsRefresh = true;
                $this->info('No expiry info, will refresh');
            }

            if (!$needsRefresh) {
                $this->info('Token is still valid, no refresh needed');
                return Command::SUCCESS;
            }

            // Refresh the token
            $this->info('Refreshing Snapchat access token...');

            $tokenResponse = $this->snapchatAdsService->refreshAccessToken($refreshToken);

            if (isset($tokenResponse['error'])) {
                $this->error('Token refresh failed: ' . ($tokenResponse['message'] ?? $tokenResponse['error']));
                Log::error('Snapchat token refresh failed', [
                    'integration_id' => $integration->id,
                    'error' => $tokenResponse
                ]);
                return Command::FAILURE;
            }

            // Update the integration with new tokens
            $config['access_token'] = $tokenResponse['access_token'];

            if (isset($tokenResponse['refresh_token'])) {
                $config['refresh_token'] = $tokenResponse['refresh_token'];
            }

            // Calculate new expiry time
            $expiresIn = $tokenResponse['expires_in'] ?? 3600;
            $config['expires_at'] = time() + $expiresIn;
            $config['token_expires_at'] = time() + $expiresIn;
            $config['last_refreshed_at'] = now()->toISOString();

            $integration->update(['app_config' => $config]);

            $this->info('Token refreshed successfully!');
            $this->info("New token expires in {$expiresIn} seconds (" . round($expiresIn / 60) . " minutes)");

            Log::info('Snapchat token refreshed successfully', [
                'integration_id' => $integration->id,
                'expires_in' => $expiresIn
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Token refresh failed: ' . $e->getMessage());
            Log::error('Snapchat token refresh exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
