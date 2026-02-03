<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\SnapchatAdsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SnapchatOAuthController extends Controller
{
    private SnapchatAdsService $snapchatService;

    public function __construct(SnapchatAdsService $snapchatService)
    {
        $this->snapchatService = $snapchatService;
    }

    /**
     * Get Snapchat OAuth authorization URL
     */
    public function redirect(Request $request): JsonResponse
    {
        try {
            $state = bin2hex(random_bytes(16));

            // Store state in session for verification
            session(['snapchat_oauth_state' => $state]);

            $authUrl = $this->snapchatService->getAuthorizationUrl($state);

            Log::info('Generated Snapchat OAuth URL', [
                'user_id' => Auth::id(),
                'state' => $state,
                'url' => $authUrl
            ]);

            return response()->json([
                'success' => true,
                'oauth_url' => $authUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate Snapchat OAuth URL', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Snapchat OAuth. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle OAuth callback from Snapchat
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // Verify state parameter
            $state = $request->get('state');
            $sessionState = session('snapchat_oauth_state');

            if (!$state || $state !== $sessionState) {
                Log::warning('Snapchat OAuth state mismatch', [
                    'provided_state' => $state,
                    'session_state' => $sessionState
                ]);
                return redirect('/dashboard')->with('error', 'OAuth verification failed. Please try again.');
            }

            // Check for authorization code
            $code = $request->get('code');
            if (!$code) {
                $error = $request->get('error', 'unknown_error');
                Log::warning('Snapchat OAuth authorization denied', ['error' => $error]);
                return redirect('/dashboard')->with('error', 'Snapchat authorization was denied.');
            }

            // Exchange code for access token
            $tokenResponse = $this->snapchatService->getAccessToken($code);

            if (isset($tokenResponse['error'])) {
                Log::error('Snapchat token exchange failed', $tokenResponse);
                return redirect('/dashboard')->with('error', 'Failed to connect to Snapchat: ' . $tokenResponse['message']);
            }

            // Test the API connection
            $connectionTest = $this->snapchatService->testConnection($tokenResponse['access_token']);
            if (!$connectionTest['success']) {
                Log::error('Snapchat API connection test failed', $connectionTest);
                return redirect('/dashboard')->with('error', 'Snapchat API connection failed.');
            }

            // Get ad accounts
            $adAccounts = $this->snapchatService->getAdAccounts($tokenResponse['access_token']);

            Log::info('Snapchat OAuth successful', [
                'user_id' => Auth::id(),
                'ad_accounts_count' => $adAccounts->count(),
                'user_info' => $connectionTest
            ]);

            // Store integration and ad accounts in database
            $this->storeSnapchatIntegration($tokenResponse, $connectionTest, $adAccounts);

            return redirect('/dashboard')->with('success',
                "Snapchat connected successfully! Found {$adAccounts->count()} ad accounts.");

        } catch (\Exception $e) {
            Log::error('Snapchat OAuth callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect('/dashboard')->with('error', 'Connection failed: ' . $e->getMessage());
        } finally {
            // Clear OAuth state from session
            session()->forget('snapchat_oauth_state');
        }
    }

    /**
     * Get current Snapchat integration status
     */
    public function status(): JsonResponse
    {
        try {
            $integration = Integration::where('user_id', Auth::id())
                ->where('platform', 'snapchat')
                ->where('status', 'active')
                ->first();

            if (!$integration) {
                return response()->json([
                    'connected' => false,
                    'message' => 'No Snapchat integration found'
                ]);
            }

            // Test current token validity
            $appConfig = $integration->app_config;
            $accessToken = $appConfig['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json([
                    'connected' => false,
                    'message' => 'No access token available'
                ]);
            }

            $connectionTest = $this->snapchatService->testConnection($accessToken);

            return response()->json([
                'connected' => $connectionTest['success'],
                'message' => $connectionTest['message'],
                'user_info' => array_filter([
                    'display_name' => $connectionTest['display_name'] ?? null,
                    'email' => $connectionTest['email'] ?? null
                ]),
                'ad_accounts_count' => AdAccount::where('integration_id', $integration->id)->count(),
                'connected_at' => $integration->created_at->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get Snapchat integration status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'connected' => false,
                'message' => 'Failed to check connection status'
            ], 500);
        }
    }

    /**
     * Disconnect Snapchat integration
     */
    public function disconnect(): JsonResponse
    {
        try {
            DB::beginTransaction();

            $integration = Integration::where('user_id', Auth::id())
                ->where('platform', 'snapchat')
                ->first();

            if ($integration) {
                // Soft delete associated ad accounts
                AdAccount::where('integration_id', $integration->id)
                    ->update(['status' => 'inactive']);

                // Deactivate integration
                $integration->update(['status' => 'inactive']);

                Log::info('Snapchat integration disconnected', [
                    'user_id' => Auth::id(),
                    'integration_id' => $integration->id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Snapchat integration disconnected successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to disconnect Snapchat integration', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect integration'
            ], 500);
        }
    }

    /**
     * Store Snapchat integration and ad accounts in database
     */
    private function storeSnapchatIntegration(array $tokenData, array $userInfo, $adAccounts): void
    {
        DB::beginTransaction();

        try {
            // Create or update integration
            $integration = Integration::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'platform' => 'snapchat'
                ],
                [
                    'tenant_id' => auth()->user()->tenant_id,
                    'status' => 'active',
                    'app_config' => [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_in' => $tokenData['expires_in'] ?? 3600,
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'scope' => $tokenData['scope'] ?? 'snapchat-marketing-api',
                        'connected_at' => now()->toISOString(),
                        'user_info' => $userInfo,
                        'ad_accounts' => $adAccounts->toArray()
                    ],
                    'last_sync_at' => now(),
                    'created_by' => Auth::id()
                ]
            );

            // Store ad accounts
            foreach ($adAccounts as $account) {
                AdAccount::updateOrCreate(
                    [
                        'external_account_id' => $account['id'],
                        'integration_id' => $integration->id
                    ],
                    [
                        'tenant_id' => auth()->user()->tenant_id,
                        'account_name' => $account['name'],
                        'currency' => $account['currency'],
                        'timezone' => $account['timezone'],
                        'status' => strtolower($account['status']),
                        'account_type' => $account['type'],
                        'created_by' => Auth::id(),
                        'last_sync_at' => now()
                    ]
                );
            }

            DB::commit();

            Log::info('Snapchat integration stored successfully', [
                'integration_id' => $integration->id,
                'ad_accounts_count' => $adAccounts->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Refresh Snapchat access token
     */
    public function refreshToken(): JsonResponse
    {
        try {
            $integration = Integration::where('user_id', Auth::id())
                ->where('platform', 'snapchat')
                ->where('status', 'active')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Snapchat integration found'
                ], 404);
            }

            $appConfig = $integration->app_config;
            $refreshToken = $appConfig['refresh_token'] ?? null;

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'No refresh token available'
                ], 400);
            }

            $tokenResponse = $this->snapchatService->refreshAccessToken($refreshToken);

            if (isset($tokenResponse['error'])) {
                Log::error('Snapchat token refresh failed', $tokenResponse);
                return response()->json([
                    'success' => false,
                    'message' => $tokenResponse['message']
                ], 400);
            }

            // Update stored tokens
            $appConfig['access_token'] = $tokenResponse['access_token'];
            if (isset($tokenResponse['refresh_token'])) {
                $appConfig['refresh_token'] = $tokenResponse['refresh_token'];
            }
            $appConfig['expires_in'] = $tokenResponse['expires_in'] ?? 3600;
            $appConfig['refreshed_at'] = now()->toISOString();

            $integration->update([
                'app_config' => $appConfig,
                'last_sync_at' => now()
            ]);

            Log::info('Snapchat token refreshed successfully', [
                'integration_id' => $integration->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to refresh Snapchat token', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token'
            ], 500);
        }
    }
}