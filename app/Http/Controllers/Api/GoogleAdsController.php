<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleAdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAdsController extends Controller
{
    protected $googleAdsService;

    public function __construct(GoogleAdsService $googleAdsService)
    {
        $this->googleAdsService = $googleAdsService;
    }
    /**
     * Get Google Ads OAuth 2.0 authorization URL
     */
    public function getAuthUrl(Request $request)
    {
        try {
            $authUrl = $this->googleAdsService->getOAuth2AuthorizationUrl();

            // Log the URL for debugging
            Log::info('Generated Google Ads OAuth URL', [
                'auth_url' => $authUrl
            ]);

            return response()->json([
                'auth_url' => $authUrl,
                'message' => 'Please visit this URL to authorize Google Ads access'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting Google Ads OAuth URL', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate authorization URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Google Ads OAuth 2.0 callback
     */
    public function handleCallback(Request $request)
    {
        try {
            $authCode = $request->query('code');
            $error = $request->query('error');

            if ($error) {
                Log::warning('Google Ads OAuth authorization denied', [
                    'error' => $error,
                    'error_description' => $request->query('error_description')
                ]);

                return redirect('/integrations?google_ads_auth=denied&error=' . urlencode($error));
            }

            if (!$authCode) {
                return redirect('/integrations?google_ads_auth=error&message=' . urlencode('No authorization code received'));
            }

            // Handle the authorization code
            $success = $this->googleAdsService->handleOAuth2Callback($authCode);

            if ($success) {
                Log::info('Google Ads OAuth authorization successful');
                return redirect('/integrations?google_ads_auth=success');
            } else {
                Log::error('Google Ads OAuth token exchange failed');
                return redirect('/integrations?google_ads_auth=error&message=' . urlencode('Token exchange failed'));
            }

        } catch (\Exception $e) {
            Log::error('Error handling Google Ads OAuth callback', [
                'error' => $e->getMessage()
            ]);

            return redirect('/integrations?google_ads_auth=error&message=' . urlencode('Authorization failed: ' . $e->getMessage()));
        }
    }

    /**
     * Check Google Ads authentication status
     */
    public function checkAuthStatus(Request $request)
    {
        try {
            $accounts = $this->googleAdsService->getConnectedAccounts();

            return response()->json([
                'authenticated' => $this->googleAdsService->isAvailable(),
                'auth_method' => $this->googleAdsService->getAuthMethod(),
                'accounts' => $accounts,
                'total_accounts' => count($accounts),
                'active_accounts' => count(array_filter($accounts, function($account) {
                    return $account['status'] === 'active';
                })),
                'message' => $this->googleAdsService->isAvailable()
                    ? 'Google Ads is connected and ready'
                    : 'Google Ads authentication required'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking Google Ads auth status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'authenticated' => false,
                'error' => 'Failed to check authentication status'
            ], 500);
        }
    }

    /**
     * Test Google Ads connection
     */
    public function testConnection(Request $request)
    {
        try {
            $result = $this->googleAdsService->testConnection();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error testing Google Ads connection', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Connection test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync Google Ads accounts
     */
    public function syncAccounts(Request $request)
    {
        try {
            $result = $this->googleAdsService->syncAccounts();
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error syncing Google Ads accounts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Account sync failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}