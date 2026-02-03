<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\IndustryDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookIntegrationController extends Controller
{
    private $facebookAppId;
    private $facebookAppSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->facebookAppId = config('services.facebook.client_id');
        $this->facebookAppSecret = config('services.facebook.client_secret');
        $this->redirectUri = config('services.facebook.redirect_uri');
    }

    /**
     * Initiate Facebook OAuth flow for user-specific integration
     */
    public function initiateOAuth(Request $request)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'error' => 'Authentication required',
                'message' => 'Please log in before connecting Facebook'
            ], 401);
        }

        // Generate state parameter including user and tenant info
        $stateData = [
            'user_id' => $request->user()->id,
            'tenant_id' => $request->current_tenant ? $request->current_tenant->id : $request->user()->default_tenant_id,
            'timestamp' => time(),
            'nonce' => Str::random(32)
        ];

        $state = base64_encode(json_encode($stateData));

        // Facebook OAuth URL
        $oauthUrl = 'https://www.facebook.com/v23.0/dialog/oauth?' . http_build_query([
            'client_id' => $this->facebookAppId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => 'ads_read,ads_management,business_management',
            'response_type' => 'code',
        ]);

        return response()->json([
            'oauth_url' => $oauthUrl,
            'state' => $state,
        ]);
    }

    /**
     * Handle Facebook OAuth callback for user-specific integration
     */
    public function handleCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');

        // Check for errors
        if ($error) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode($error));
        }

        // Validate and decode state parameter
        if (!$state) {
            return redirect(config('app.url') . '/integrations?error=' . urlencode('Missing state parameter'));
        }

        try {
            $stateData = json_decode(base64_decode($state), true);
            
            if (!$stateData || !isset($stateData['user_id'], $stateData['tenant_id'], $stateData['timestamp'])) {
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
            $tokenResponse = Http::post('https://graph.facebook.com/v23.0/oauth/access_token', [
                'client_id' => $this->facebookAppId,
                'client_secret' => $this->facebookAppSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to exchange code for token: ' . $tokenResponse->body());
            }

            $tokenData = $tokenResponse->json();
            $shortLivedToken = $tokenData['access_token'];

            // Exchange short-lived token for long-lived token
            $longLivedResponse = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->facebookAppId,
                'client_secret' => $this->facebookAppSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            if ($longLivedResponse->successful()) {
                $longLivedData = $longLivedResponse->json();
                $accessToken = $longLivedData['access_token'];
            } else {
                $accessToken = $shortLivedToken;
            }

            // Get user info
            $userResponse = Http::get('https://graph.facebook.com/v23.0/me', [
                'access_token' => $accessToken,
                'fields' => 'id,name,email',
            ]);

            if (!$userResponse->successful()) {
                throw new \Exception('Failed to get user info: ' . $userResponse->body());
            }

            $userData = $userResponse->json();

            // Get ad accounts
            $adAccountsResponse = Http::get('https://graph.facebook.com/v23.0/me/adaccounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,account_status,currency',
            ]);

            $adAccounts = [];
            if ($adAccountsResponse->successful()) {
                $adAccounts = $adAccountsResponse->json()['data'] ?? [];
            }

            // Create or update user-specific integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => $stateData['tenant_id'],
                    'user_id' => $stateData['user_id'],
                    'platform' => 'facebook',
                ],
                [
                    'app_config' => [
                        'app_id' => $this->facebookAppId,
                        'access_token' => $accessToken,
                        'facebook_user_id' => $userData['id'],
                        'facebook_user_name' => $userData['name'],
                        'facebook_user_email' => $userData['email'] ?? null,
                        'ad_accounts' => $adAccounts,
                        'token_expires_at' => now()->addDays(60)->toISOString(),
                        'connected_at' => now()->toISOString(),
                    ],
                    'status' => 'active',
                    'created_by' => $stateData['user_id'],
                ]
            );

            // Store ad accounts
            foreach ($adAccounts as $adAccount) {
                // Auto-detect industry based on account name
                $detectedIndustry = IndustryDetector::detectIndustry($adAccount['name']);
                
                \App\Models\AdAccount::updateOrCreate(
                    [
                        'integration_id' => $integration->id,
                        'external_account_id' => $adAccount['id'],
                    ],
                    [
                        'account_name' => $adAccount['name'],
                        'status' => $adAccount['account_status'] === 1 ? 'active' : 'inactive',
                        'industry' => $detectedIndustry,
                        'tenant_id' => $stateData['tenant_id'],
                        'account_config' => [
                            'currency' => $adAccount['currency'] ?? 'USD',
                        ],
                    ]
                );
            }

            return redirect(config('app.url') . '/integrations?success=facebook_connected&accounts=' . count($adAccounts) . '&user=' . urlencode($userData['name']));

        } catch (\Exception $e) {
            Log::error('Facebook OAuth error: ' . $e->getMessage(), [
                'user_id' => $stateData['user_id'] ?? null,
                'tenant_id' => $stateData['tenant_id'] ?? null,
                'code' => $code,
                'state' => $state,
            ]);

            return redirect(config('app.url') . '/integrations?error=' . urlencode('Failed to connect Facebook: ' . $e->getMessage()));
        }
    }

    /**
     * Test Facebook integration connection
     */
    public function testConnection(Integration $integration)
    {
        try {
            $config = $integration->app_config;
            $accessToken = $config['access_token'];

            // Test API connection by getting user info
            $response = Http::get('https://graph.facebook.com/v23.0/me', [
                'access_token' => $accessToken,
                'fields' => 'id,name',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Invalid access token or API error');
            }

            $userData = $response->json();

            // Get fresh ad accounts data
            $adAccountsResponse = Http::get('https://graph.facebook.com/v23.0/me/adaccounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,account_status,currency',
            ]);

            $adAccountsCount = 0;
            if ($adAccountsResponse->successful()) {
                $adAccountsCount = count($adAccountsResponse->json()['data'] ?? []);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Facebook integration is working correctly',
                'user_name' => $userData['name'],
                'accounts_found' => $adAccountsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook integration test failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refresh Facebook access token
     */
    public function refreshToken(Integration $integration)
    {
        try {
            $config = $integration->app_config;
            $currentToken = $config['access_token'];

            // Try to get a fresh long-lived token
            $response = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->facebookAppId,
                'client_secret' => $this->facebookAppSecret,
                'fb_exchange_token' => $currentToken,
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                $newToken = $tokenData['access_token'];

                // Update integration with new token
                $config['access_token'] = $newToken;
                $config['token_expires_at'] = now()->addDays(60);
                
                $integration->update([
                    'app_config' => $config,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Access token refreshed successfully',
                ]);
            } else {
                throw new \Exception('Failed to refresh token: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Facebook token refresh failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Fetch all ad accounts with pagination support and update integration
     */
    public function refreshAllAccounts(Integration $integration)
    {
        try {
            $config = $integration->app_config;
            
            // Ensure config is an array
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            
            if (!isset($config['access_token'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No access token found',
                ], 400);
            }

            $accessToken = $config['access_token'];
            $allAccounts = [];
            $nextUrl = 'https://graph.facebook.com/v23.0/me/adaccounts';
            $totalFetched = 0;

            // Fetch all pages of ad accounts
            do {
                Log::info('Fetching Facebook ad accounts', [
                    'url' => $nextUrl,
                    'total_fetched' => $totalFetched,
                ]);

                $response = Http::get($nextUrl, [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,account_status,currency,business',
                    'limit' => 100, // Facebook max limit per request
                ]);

                if (!$response->successful()) {
                    Log::error('Facebook API error', [
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json();
                $accounts = $data['data'] ?? [];
                $allAccounts = array_merge($allAccounts, $accounts);
                $totalFetched += count($accounts);

                // Check for next page
                $nextUrl = $data['paging']['next'] ?? null;

                Log::info('Fetched batch', [
                    'batch_count' => count($accounts),
                    'total_fetched' => $totalFetched,
                    'has_next' => !is_null($nextUrl),
                ]);

            } while ($nextUrl && $totalFetched < 500); // Safety limit

            // Update integration config with all accounts
            $config['ad_accounts'] = $allAccounts;
            $config['last_sync'] = now()->toISOString();
            
            $integration->update([
                'app_config' => $config,
            ]);

            Log::info('Updated integration with all accounts', [
                'integration_id' => $integration->id,
                'total_accounts' => count($allAccounts),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Fetched {$totalFetched} ad accounts from Facebook API",
                'accounts_found' => $totalFetched,
                'integration_updated' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook refresh accounts failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to refresh accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync campaigns for a specific ad account or all accounts in integration
     */
    public function syncCampaigns(Integration $integration, $adAccountId = null)
    {
        try {
            $config = $integration->app_config;
            
            // Ensure config is an array
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            
            if (!isset($config['access_token'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No access token found',
                ], 400);
            }

            $accessToken = $config['access_token'];
            $totalSynced = 0;
            $errors = [];

            // Get ad accounts to sync
            if ($adAccountId) {
                $adAccounts = $integration->adAccounts()->where('id', $adAccountId)->get();
            } else {
                $adAccounts = $integration->adAccounts;
            }

            Log::info('Starting campaign sync', [
                'integration_id' => $integration->id,
                'accounts_count' => $adAccounts->count(),
                'specific_account' => $adAccountId,
            ]);

            foreach ($adAccounts as $adAccount) {
                try {
                    // Fetch campaigns for this ad account
                    $url = "https://graph.facebook.com/v23.0/{$adAccount->external_account_id}/campaigns";
                    $params = [
                        'access_token' => $accessToken,
                        'fields' => 'id,name,objective,status,created_time,updated_time',
                        'limit' => 100,
                    ];

                    $campaignsResponse = Http::get($url, $params);

                    if ($campaignsResponse->successful()) {
                        $campaigns = $campaignsResponse->json()['data'] ?? [];
                        
                        foreach ($campaigns as $campaign) {
                            \App\Models\AdCampaign::updateOrCreate(
                                [
                                    'ad_account_id' => $adAccount->id,
                                    'external_campaign_id' => $campaign['id'],
                                ],
                                [
                                    'tenant_id' => $integration->tenant_id,
                                    'name' => $campaign['name'],
                                    'objective' => $this->mapFacebookObjective($campaign['objective'] ?? 'unknown'),
                                    'status' => $campaign['status'] === 'ACTIVE' ? 'active' : 'paused',
                                    'campaign_config' => [
                                        'facebook_objective' => $campaign['objective'] ?? null,
                                        'created_time' => $campaign['created_time'] ?? null,
                                        'updated_time' => $campaign['updated_time'] ?? null,
                                    ],
                                ]
                            );
                            $totalSynced++;
                        }

                        Log::info('Synced campaigns for account', [
                            'account_name' => $adAccount->account_name,
                            'campaigns_count' => count($campaigns),
                        ]);

                    } else {
                        $error = "API call failed for account {$adAccount->account_name}: " . $campaignsResponse->body();
                        $errors[] = $error;
                        Log::error('Campaign sync API error', [
                            'account_id' => $adAccount->id,
                            'response' => $campaignsResponse->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $error = "Error syncing campaigns for account {$adAccount->account_name}: " . $e->getMessage();
                    $errors[] = $error;
                    Log::error('Campaign sync exception', [
                        'account_id' => $adAccount->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Synced {$totalSynced} campaigns successfully",
                'campaigns_synced' => $totalSynced,
                'accounts_processed' => $adAccounts->count(),
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Campaign sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map Facebook campaign objective to our internal objectives
     */
    private function mapFacebookObjective(string $facebookObjective): string
    {
        return match (strtoupper($facebookObjective)) {
            'REACH', 'BRAND_AWARENESS', 'IMPRESSIONS' => 'awareness',
            'LEAD_GENERATION', 'MESSAGES', 'CONVERSIONS' => 'leads', 
            'PURCHASES', 'CATALOG_SALES' => 'sales',
            'CALLS', 'PHONE_CALLS' => 'calls',
            default => 'awareness',
        };
    }
}