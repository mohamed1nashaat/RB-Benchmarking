<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Get OAuth 2.0 authorization URL
     */
    public function getAuthUrl(Request $request)
    {
        try {
            $authUrl = $this->googleSheetsService->getOAuth2AuthorizationUrl();

            // Log the URL for debugging
            Log::info('Generated Google OAuth URL', [
                'auth_url' => $authUrl
            ]);

            return response()->json([
                'auth_url' => $authUrl,
                'message' => 'Please visit this URL to authorize Google Sheets access'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting Google OAuth URL', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate authorization URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth 2.0 callback
     */
    public function handleCallback(Request $request)
    {
        try {
            $authCode = $request->query('code');
            $error = $request->query('error');

            if ($error) {
                Log::warning('Google OAuth authorization denied', [
                    'error' => $error,
                    'error_description' => $request->query('error_description')
                ]);

                return redirect('/integrations?google_auth=denied&error=' . urlencode($error));
            }

            if (!$authCode) {
                return redirect('/integrations?google_auth=error&message=' . urlencode('No authorization code received'));
            }

            // Handle the authorization code
            $success = $this->googleSheetsService->handleOAuth2Callback($authCode);

            if ($success) {
                Log::info('Google OAuth authorization successful');
                return redirect('/integrations?google_auth=success');
            } else {
                Log::error('Google OAuth token exchange failed');
                return redirect('/integrations?google_auth=error&message=' . urlencode('Token exchange failed'));
            }

        } catch (\Exception $e) {
            Log::error('Error handling Google OAuth callback', [
                'error' => $e->getMessage()
            ]);

            return redirect('/integrations?google_auth=error&message=' . urlencode('Authorization failed: ' . $e->getMessage()));
        }
    }

    /**
     * Check Google Sheets authentication status
     */
    public function checkAuthStatus(Request $request)
    {
        try {
            // Create a new instance to check current status
            $service = new GoogleSheetsService();

            return response()->json([
                'authenticated' => $service->isAvailable(),
                'auth_method' => $service->getAuthMethod(),
                'message' => $service->isAvailable()
                    ? 'Google Sheets is connected and ready'
                    : 'Google Sheets authentication required'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking Google auth status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'authenticated' => false,
                'error' => 'Failed to check authentication status'
            ], 500);
        }
    }

    /**
     * Test Google Sheets connection
     */
    public function testConnection(Request $request)
    {
        try {
            // Create a test sheet to verify connection
            $result = $this->googleSheetsService->createCampaignSheet(
                999999, // Test campaign ID
                'Test Connection - ' . now()->format('Y-m-d H:i:s'),
                [],
                'Test Ad Account',
                'test-account-999999'
            );

            if (isset($result['requires_auth']) && $result['requires_auth']) {
                return response()->json([
                    'success' => false,
                    'requires_auth' => true,
                    'auth_url' => $result['sheet_url'],
                    'message' => $result['message']
                ]);
            }

            // If we get a real sheet ID (not mock), test was successful
            $isRealSheet = !str_starts_with($result['sheet_id'], 'mock_sheet_');

            return response()->json([
                'success' => $isRealSheet,
                'sheet_id' => $result['sheet_id'],
                'sheet_url' => $result['sheet_url'],
                'message' => $isRealSheet
                    ? 'Google Sheets connection successful!'
                    : 'Connection test returned mock data - check authentication'
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing Google Sheets connection', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Connection test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}