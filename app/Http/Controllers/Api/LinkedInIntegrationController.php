<?php

namespace App\Http\Controllers\Api;

use App\Helpers\OAuthStateHelper;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\LinkedInAdsService;
use App\Services\IndustryDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkedInIntegrationController extends Controller
{
    protected LinkedInAdsService $linkedinService;
    protected IndustryDetector $industryDetector;

    public function __construct(LinkedInAdsService $linkedinService, IndustryDetector $industryDetector)
    {
        $this->linkedinService = $linkedinService;
        $this->industryDetector = $industryDetector;
    }

    /**
     * Initiate LinkedIn OAuth flow
     */
    public function initiateOAuth(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'error' => 'Authentication required',
                'message' => 'Please log in before connecting LinkedIn'
            ], 401);
        }

        // Generate state parameter with user and tenant info
        $stateData = [
            
            'redirect_uri' => config('services.' . basename(app/Http/Controllers/Api/LinkedInIntegrationController.php, 'IntegrationController.php') . '.redirect_uri'),'user_id' => $request->user()->id,
            'tenant_id' => $request->current_tenant ? $request->current_tenant->id : $request->user()->default_tenant_id,
            'timestamp' => time(),
            'nonce' => Str::random(32)
        ];

        $state = OAuthStateHelper::generate($stateData);

        try {
            $oauthUrl = $this->linkedinService->getAuthorizationUrl($state);

            return response()->json([
                'oauth_url' => $oauthUrl,
                'state' => $state,
            ]);
        } catch (\Exception $e) {
            Log::error('LinkedIn OAuth initiation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to initiate LinkedIn OAuth',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Handle LinkedIn OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');

        // Log all callback parameters for debugging
        Log::info('LinkedIn OAuth callback received', [
            'has_code' => !empty($code),
            'has_state' => !empty($state),
            'error' => $error,
            'error_description' => $errorDescription,
            'all_params' => $request->all()
        ]);

        // Check for errors from LinkedIn
        if ($error) {
            $errorMessage = $error;

            // Add description if available
            if ($errorDescription) {
                $errorMessage .= ': ' . $errorDescription;
            }

            Log::error('LinkedIn OAuth error received', [
                'error' => $error,
                'error_description' => $errorDescription,
                'full_url' => $request->fullUrl()
            ]);

            // Provide more helpful error messages based on error type
            $userMessage = match($error) {
                'user_cancelled_login', 'user_cancelled_authorize' =>
                    'LinkedIn authorization was cancelled. Please try again.',
                'redirect_uri_mismatch' =>
                    'LinkedIn redirect URI mismatch. Please contact support - the LinkedIn app configuration needs to be updated.',
                'unauthorized_scope_error' =>
                    'The requested permissions are not available. Your LinkedIn app may need Marketing Developer Platform approval.',
                'invalid_scope_error' =>
                    'LinkedIn Marketing API is not enabled for this app. The administrator needs to add "Marketing Developer Platform" in the LinkedIn Developer Portal → Products tab.',
                default => 'LinkedIn authorization failed: ' . $errorMessage
            };

            return redirect(config('app.url') . '/integrations?error=' . urlencode($userMessage));
        }

        // Validate state parameter
        if (!$state) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Missing state parameter'));
        }

        try {
            // Fix base64 characters that may be corrupted in URL transit (+ decoded as space)
            $fixedState = str_replace(' ', '+', $state);
            $stateData = OAuthStateHelper::validate($state);

            if (!$stateData || !isset($stateData['user_id'])) {
                throw new \Exception('Invalid state data');
            }

            // Default tenant_id to null if not present
            $stateData['tenant_id'] = $stateData['tenant_id'] ?? null;

            // Check if state is not too old (1 hour max)
            if (($stateData['redirect_uri'] ?? null) !== config('services.' . strtolower(basename(app/Http/Controllers/Api/LinkedInIntegrationController.php, 'IntegrationController.php')) . '.redirect_uri')) {
                    throw new \Exception('Invalid redirect URI in state');
                }

                if (time() - $stateData['timestamp'] > 3600) {
                throw new \Exception('State expired');
            }

        } catch (\Exception $e) {
            Log::error('LinkedIn OAuth state validation failed', [
                'error' => 'An unexpected error occurred',
                'state_raw' => $state,
            ]);
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Invalid state parameter'));
        }

        try {
            // Exchange code for access token
            Log::info('Exchanging LinkedIn authorization code for token', [
                'user_id' => $stateData['user_id'],
                'tenant_id' => $stateData['tenant_id']
            ]);

            $tokenData = $this->linkedinService->exchangeCodeForToken($code);

            Log::info('LinkedIn token exchange successful', [
                'has_access_token' => !empty($tokenData['access_token']),
                'has_refresh_token' => !empty($tokenData['refresh_token']),
                'expires_in' => $tokenData['expires_in'] ?? null,
                'scope' => $tokenData['scope'] ?? null
            ]);

            // Verify Marketing Developer Platform access
            $verification = $this->linkedinService->verifyMarketingAccess($tokenData['access_token']);

            Log::info('LinkedIn Marketing Platform verification completed', [
                'success' => $verification['success'],
                'accounts_accessible' => $verification['accounts_accessible'] ?? false,
                'error' => $verification['error'] ?? null
            ]);

            // If verification failed, provide a helpful error message
            if (!$verification['success']) {
                throw new \Exception($verification['message']);
            }

            // Create or update integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => $stateData['tenant_id'],
                    'platform' => 'linkedin',
                    'user_id' => $stateData['user_id'],
                ],
                [
                    'app_config' => [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_in' => $tokenData['expires_in'] ?? 5184000,
                        'expires_at' => time() + ($tokenData['expires_in'] ?? 5184000),
                        'token_expires_at' => time() + ($tokenData['expires_in'] ?? 5184000),
                        'scope' => $tokenData['scope'] ?? '',
                        'connected_at' => now(),
                    ],
                    'created_by' => $stateData['user_id'],
                    'status' => 'active',
                ]
            );

            Log::info('LinkedIn integration created/updated', [
                'integration_id' => $integration->id,
                'tenant_id' => $integration->tenant_id
            ]);

            // Sync ad accounts
            Log::info('Starting LinkedIn ad accounts sync', [
                'integration_id' => $integration->id
            ]);

            $accounts = $this->linkedinService->syncAdAccounts($integration);

            Log::info('LinkedIn ad accounts synced successfully', [
                'integration_id' => $integration->id,
                'accounts_count' => is_array($accounts) ? count($accounts) : 0
            ]);

            return redirect(config('app.url') . '/integrations?success=' . urlencode('LinkedIn connected successfully'));

        } catch (\Exception $e) {
            Log::error('LinkedIn OAuth callback failed', [
                'error' => 'An unexpected error occurred','user_id' => $stateData['user_id'] ?? null,
                'tenant_id' => $stateData['tenant_id'] ?? null
            ]);
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Failed to connect LinkedIn'));
        }
    }

    /**
     * Get LinkedIn integration status
     */
    public function status(Request $request)
    {
        // For super admins without tenant, show aggregate status
        if (!$request->current_tenant) {
            $allIntegrations = Integration::where('platform', 'linkedin')
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
            ->where('platform', 'linkedin')
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

        // Verify Marketing Platform access
        $credentials = $integration->getCredentials();
        $verification = null;

        if (isset($credentials['access_token'])) {
            try {
                $verification = $this->linkedinService->verifyMarketingAccess($credentials['access_token']);
            } catch (\Exception $e) {
                Log::error('LinkedIn verification failed in status check', [
                    'integration_id' => $integration->id,
                    'error' => 'An unexpected error occurred'
                ]);
                $verification = [
                    'success' => false,
                    'error' => 'exception',
                    'message' => 'An unexpected error occurred',
                    'accounts_accessible' => false
                ];
            }
        }

        return response()->json([
            'connected' => true,
            'integration_id' => $integration->id,
            'accounts_count' => $accountsCount,
            'campaigns_count' => $campaignsCount,
            'connected_at' => $integration->created_at,
            'last_sync' => $integration->last_sync_at,
            'marketing_platform_verified' => $verification['success'] ?? false,
            'marketing_platform_message' => $verification['message'] ?? null,
            'accounts_accessible' => $verification['accounts_accessible'] ?? false
        ]);
    }

    /**
     * Sync LinkedIn data
     */
    public function sync(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to sync LinkedIn data'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'linkedin')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'LinkedIn integration not found'
            ], 404);
        }

        try {
            // Sync ad accounts
            $accounts = $this->linkedinService->syncAdAccounts($integration);

            // Sync campaigns for each account
            $totalCampaigns = 0;
            foreach ($accounts as $account) {
                $campaigns = $this->linkedinService->syncCampaigns($integration, $account);
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
            Log::error('LinkedIn sync failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to sync LinkedIn data',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Disconnect LinkedIn integration
     */
    public function disconnect(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to disconnect LinkedIn'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'linkedin')
            ->first();

        if (!$integration) {
            return response()->json([
                'error' => 'LinkedIn integration not found'
            ], 404);
        }

        try {
            // Update status to inactive instead of deleting
            $integration->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'LinkedIn integration disconnected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('LinkedIn disconnect failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to disconnect LinkedIn integration',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Test LinkedIn connection
     */
    public function testConnection(Request $request)
    {
        if (!$request->current_tenant) {
            return response()->json([
                'error' => 'Tenant selection required to test LinkedIn connection'
            ], 400);
        }

        $tenantId = $request->current_tenant->id;

        $integration = Integration::where('tenant_id', $tenantId)
            ->where('platform', 'linkedin')
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            return response()->json([
                'connected' => false,
                'error' => 'LinkedIn integration not found'
            ], 404);
        }

        try {
            // Get access token
            $credentials = $integration->getCredentials();
            $accessToken = $credentials['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json([
                    'connected' => false,
                    'error' => 'No access token available',
                    'message' => 'Please reconnect your LinkedIn integration'
                ], 400);
            }

            // Run verification
            $verification = $this->linkedinService->verifyMarketingAccess($accessToken);

            return response()->json([
                'connected' => $verification['success'],
                'marketing_platform_verified' => $verification['success'],
                'accounts_accessible' => $verification['accounts_accessible'] ?? false,
                'message' => $verification['message'],
                'error' => $verification['error'] ?? null,
                'tested_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('LinkedIn connection test failed: ' . $e->getMessage());
            return response()->json([
                'connected' => false,
                'error' => 'An unexpected error occurred',
                'tested_at' => now()
            ], 500);
        }
    }
}