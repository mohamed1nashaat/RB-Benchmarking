<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\TwitterAdsService;
use App\Services\IndustryDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitterIntegrationController extends Controller
{
    protected TwitterAdsService $twitterService;
    protected IndustryDetector $industryDetector;

    public function __construct(TwitterAdsService $twitterService, IndustryDetector $industryDetector)
    {
        $this->twitterService = $twitterService;
        $this->industryDetector = $industryDetector;
    }

    /**
     * Initiate X/Twitter OAuth flow
     */
    public function initiateOAuth(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'error' => 'Authentication required',
                'message' => 'Please log in before connecting X/Twitter'
            ], 401);
        }

        // Generate state parameter with user and tenant info
        $stateData = [
            'user_id' => $request->user()->id,
            'tenant_id' => $request->current_tenant ? $request->current_tenant->id : $request->user()->default_tenant_id,
            'timestamp' => time(),
            'nonce' => Str::random(32)
        ];

        $state = base64_encode(json_encode($stateData));

        try {
            $oauthUrl = $this->twitterService->getAuthorizationUrl($state);

            return response()->json([
                'oauth_url' => $oauthUrl,
                'state' => $state,
            ]);
        } catch (\Exception $e) {
            Log::error('Twitter OAuth initiation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to initiate X/Twitter OAuth',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle X/Twitter OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');

        // Check for errors
        if ($error) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode($error));
        }

        // Validate state parameter
        if (!$state) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Missing state parameter'));
        }

        try {
            $stateData = json_decode(base64_decode($state), true);

            if (!$stateData || !isset($stateData['user_id'], $stateData['tenant_id'])) {
                throw new \Exception('Invalid state data');
            }

            // Check if state is not too old (1 hour max)
            if (time() - $stateData['timestamp'] > 3600) {
                throw new \Exception('State expired');
            }

        } catch (\Exception $e) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Invalid state parameter'));
        }

        try {
            // Exchange code for access token
            $tokenData = $this->twitterService->exchangeCodeForToken($code);

            // Create or update integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => $stateData['tenant_id'],
                    'platform' => 'twitter',
                    'user_id' => $stateData['user_id'],
                ],
                [
                    'app_config' => [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_in' => $tokenData['expires_in'] ?? 7200,
                        'scope' => $tokenData['scope'] ?? '',
                        'connected_at' => now(),
                    ],
                    'created_by' => $stateData['user_id'],
                    'status' => 'active',
                ]
            );

            // Sync ad accounts
            $this->twitterService->syncAdAccounts($integration);

            return redirect(config('app.url') . '/integrations?success=' . urlencode('X/Twitter connected successfully'));

        } catch (\Exception $e) {
            Log::error('Twitter OAuth callback failed: ' . $e->getMessage());
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Failed to connect X/Twitter: ' . $e->getMessage()));
        }
    }

    /**
     * Get X/Twitter integration status
     */
    public function status(Request $request)
    {
        // For super admins without tenant, show aggregate status
        if (!$request->current_tenant) {
            $allIntegrations = Integration::where('platform', 'twitter')
                ->where('status', 'active')
                ->get();

            if ($allIntegrations->isEmpty()) {
                return response()->json([
                    'connected' => false,
                    'accounts_count' => 0,
                    'campaigns_count' => 0
                ]);
            }

            $totalAccounts = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))->count();
            $totalCampaigns = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))
                ->with('adCampaigns')
                ->get()
                ->sum(function ($account) {
                    return $account->adCampaigns->count();
                });

            return response()->json([
                'connected' => true,
                'accounts_count' => $totalAccounts,
                'campaigns_count' => $totalCampaigns,
                'integrations_count' => $allIntegrations->count(),
                'last_sync' => $allIntegrations->max('last_sync_at')
            ]);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'twitter')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            return response()->json([
                'connected' => false,
                'accounts_count' => 0,
                'campaigns_count' => 0
            ]);
        }

        $accountsCount = $integration->adAccounts()->count();
        $campaignsCount = $integration->adAccounts()
            ->with('adCampaigns')
            ->get()
            ->sum(function ($account) {
                return $account->adCampaigns->count();
            });

        return response()->json([
            'connected' => true,
            'integration_id' => $integration->id,
            'accounts_count' => $accountsCount,
            'campaigns_count' => $campaignsCount,
            'connected_at' => $integration->created_at,
            'last_sync' => $integration->last_sync_at
        ]);
    }

    /**
     * Sync X/Twitter data
     */
    public function sync(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to sync X/Twitter data'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'twitter')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'X/Twitter integration not found'
            ], 404);
        }

        try {
            // Sync ad accounts
            $accounts = $this->twitterService->syncAdAccounts($integration);

            // Sync campaigns for each account
            $totalCampaigns = 0;
            foreach ($accounts as $account) {
                $campaigns = $this->twitterService->syncCampaigns($integration, $account);
                $totalCampaigns += count($campaigns);
            }

            // Update last sync time
            $integration->update(['last_sync_at' => now()]);

            return response()->json([
                'success' => true,
                'accounts_synced' => count($accounts),
                'campaigns_synced' => $totalCampaigns,
                'synced_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Twitter sync failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to sync X/Twitter data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect X/Twitter integration
     */
    public function disconnect(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to disconnect X/Twitter'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'twitter')
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'X/Twitter integration not found'
            ], 404);
        }

        try {
            // Update status to inactive instead of deleting
            $integration->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'X/Twitter integration disconnected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Twitter disconnect failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to disconnect X/Twitter integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test X/Twitter connection
     */
    public function testConnection(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to test X/Twitter connection'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'twitter')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            return response()->json([
                'connected' => false,
                'error' => 'X/Twitter integration not found'
            ], 404);
        }

        try {
            $isConnected = $this->twitterService->testConnection($integration);

            return response()->json([
                'connected' => $isConnected,
                'tested_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Twitter connection test failed: ' . $e->getMessage());
            return response()->json([
                'connected' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}