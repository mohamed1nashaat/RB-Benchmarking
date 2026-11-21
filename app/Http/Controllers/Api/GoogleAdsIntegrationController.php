<?php

namespace App\Http\Controllers\Api;

use App\Helpers\OAuthStateHelper;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\GoogleAdsService;
use App\Services\IndustryDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAdsIntegrationController extends Controller
{
    protected GoogleAdsService $googleAdsService;
    protected IndustryDetectionService $industryDetectionService;

    public function __construct(GoogleAdsService $googleAdsService, IndustryDetectionService $industryDetectionService)
    {
        $this->googleAdsService = $googleAdsService;
        $this->industryDetectionService = $industryDetectionService;
    }

    public function redirect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tenant = $request->current_tenant;

            // Resolve tenant_id: prefer explicit current_tenant, then user default, then first tenant for super admins
            $tenantId = $tenant?->id
                ?? $user->default_tenant_id
                ?? ($user->isSuperAdmin() ? \App\Models\Tenant::value('id') : null);

            $stateData = [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'timestamp' => time(),
                'nonce' => Str::random(32)
            ];

            $state = OAuthStateHelper::generate($stateData);

            $authUrl = $this->googleAdsService->getAuthorizationUrl($state);

            Log::info('Google Ads OAuth redirect initiated', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ]);

            return response()->json([
                'success' => true,
                'oauth_url' => $authUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads OAuth redirect failed', [
                'user_id' => $request->user()->id,
                'error' => 'An unexpected error occurred'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Google Ads OAuth. Please try again.'
            ], 500);
        }
    }

    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            // Check if Google Ads is configured first
            if (!$this->googleAdsService->isAvailable()) {
                $developerToken = config('services.google_ads.developer_token', '');
                $apiKey = config('services.google_ads.api_key', '');

                $message = 'Google Ads integration requires configuration. Choose one option: ';
                $message .= '1) Get Developer Token from https://ads.google.com/ → Tools & Settings → API Center, OR ';
                $message .= '2) Set GOOGLE_ANALYTICS_PROPERTY_ID if Google Ads is linked to Google Analytics 4. ';

                if (str_starts_with($developerToken, 'AIza') || !empty($apiKey)) {
                    $message .= 'Note: API key detected but not sufficient alone for Google Ads API.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'configured' => false
                ], 400);
            }

            // Alias for redirect to match frontend expectations
            return $this->redirect($request);
        } catch (\Exception $e) {
            Log::error('Google Ads auth URL generation failed', [
                'error' => 'An unexpected error occurred'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate authorization URL. Please check your Google Ads configuration.',
                'configured' => false
            ], 500);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $code = $request->query('code');
            $state = $request->query('state');
            $error = $request->query('error');

            if ($error) {
                Log::warning('Google Ads OAuth error', [
                    'error' => $error,
                    'error_description' => $request->query('error_description')
                ]);

                return redirect(config('app.url') . '/integrations?error=' . urlencode('Google Ads authorization was cancelled or failed.'));
            }

            if (!$code || !$state) {
                return redirect(config('app.url') . '/integrations?error=' . urlencode('Missing authorization code or state parameter.'));
            }

            // Decode and validate state
            try {
                $stateData = OAuthStateHelper::validate($state);

                $requiredKeys = ['user_id', 'tenant_id', 'timestamp', 'nonce'];
                foreach ($requiredKeys as $key) {
                    if (!array_key_exists($key, $stateData ?? [])) {
                        throw new \Exception('Invalid state parameter structure');
                    }
                }

                // Check if state is not too old (10 minutes max)
                if (time() - $stateData['timestamp'] > 600) {
                    throw new \Exception('OAuth state has expired');
                }

            } catch (\Exception $e) {
                Log::error('Invalid Google Ads OAuth state', [
                    'state' => $state,
                    'error' => 'An unexpected error occurred'
                ]);

                return redirect(config('app.url') . '/integrations?error=' . urlencode('Invalid or expired authorization state.'));
            }

            // Exchange code for tokens
            $tokenData = $this->googleAdsService->exchangeCodeForTokens($code);

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return redirect(config('app.url') . '/integrations?error=' . urlencode('Failed to exchange authorization code for access token.'));
            }

            // Exchange short-lived token for long-lived token if applicable
            $longLivedTokenData = $this->googleAdsService->getLongLivedAccessToken($tokenData);
            $finalTokenData = $longLivedTokenData ?: $tokenData;

            // Store integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => $stateData['tenant_id'],
                    'platform' => 'google',
                ],
                [
                    'user_id' => $stateData['user_id'],
                    'app_config' => [
                        'access_token' => $finalTokenData['access_token'],
                        'refresh_token' => $finalTokenData['refresh_token'] ?? null,
                        'expires_at' => isset($finalTokenData['expires_in'])
                            ? time() + $finalTokenData['expires_in']
                            : null,
                        'token_type' => $finalTokenData['token_type'] ?? 'Bearer',
                        'scope' => $finalTokenData['scope'] ?? null,
                    ],
                    'created_by' => $stateData['user_id'],
                    'status' => 'active',
                    'last_sync_at' => now(),
                ]
            );

            Log::info('Google Ads integration created/updated', [
                'integration_id' => $integration->id,
                'user_id' => $stateData['user_id'],
                'tenant_id' => $stateData['tenant_id'],
            ]);

            // Fetch and store ad accounts
            $refreshToken = $finalTokenData['refresh_token'] ?? null;
            $syncResult = $this->syncAdAccounts($integration, $finalTokenData['access_token'], $refreshToken);

            return redirect(config('app.url') . '/integrations?success=google_ads_connected&accounts=' . $syncResult['synced_count']);

        } catch (\Exception $e) {
            Log::error('Google Ads OAuth callback failed', ['error' => 'An unexpected error occurred']);

            return redirect(config('app.url') . '/integrations?error=' . urlencode('Failed to complete Google Ads integration. Please try again.'));
        }
    }

    public function disconnect(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to disconnect Google Ads.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'google')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads integration not found.'
                ], 404);
            }

            // Delete related ad accounts and campaigns
            foreach ($integration->adAccounts as $adAccount) {
                $adAccount->adCampaigns()->delete();
            }
            $integration->adAccounts()->delete();
            $integration->delete();

            Log::info('Google Ads integration disconnected', [
                'integration_id' => $integration->id,
                'tenant_id' => $tenant->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google Ads account disconnected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads disconnect failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Google Ads account.'
            ], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            // Check if Google Ads is configured
            if (!$this->googleAdsService->isAvailable()) {
                $developerToken = config('services.google_ads.developer_token', '');
                $apiKey = config('services.google_ads.api_key', '');

                $message = 'Google Ads integration requires configuration. Choose one option: ';
                $message .= '1) Get Developer Token from https://ads.google.com/ → Tools & Settings → API Center, OR ';
                $message .= '2) Set GOOGLE_ANALYTICS_PROPERTY_ID if Google Ads is linked to Google Analytics 4. ';

                if (str_starts_with($developerToken, 'AIza') || !empty($apiKey)) {
                    $message .= 'Note: API key detected but not sufficient alone for Google Ads API.';
                }

                return response()->json([
                    'connected' => false,
                    'configured' => false,
                    'accounts_count' => 0,
                    'campaigns_count' => 0,
                    'message' => $message
                ]);
            }

            // For super admins without tenant, show aggregate status
            if (!$request->current_tenant) {
                $allIntegrations = Integration::where('platform', 'google')->get();

                if ($allIntegrations->isEmpty()) {
                    return response()->json([
                        'connected' => false,
                        'configured' => true,
                        'accounts_count' => 0,
                        'campaigns_count' => 0
                    ]);
                }

                $totalAccounts = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))->count();
                $totalCampaigns = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))
                    ->withCount('adCampaigns')->get()->sum('ad_campaigns_count');

                return response()->json([
                    'connected' => true,
                    'configured' => true,
                    'status' => 'active',
                    'accounts_count' => $totalAccounts,
                    'campaigns_count' => $totalCampaigns,
                    'integrations_count' => $allIntegrations->count(),
                    'last_sync' => $allIntegrations->max('last_sync_at')?->toISOString()
                ]);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'google')
                ->first();

            if (!$integration) {
                return response()->json([
                    'connected' => false,
                    'configured' => true,
                    'accounts_count' => 0,
                    'campaigns_count' => 0
                ]);
            }

            $accountsCount = $integration->adAccounts()->count();
            $campaignsCount = $integration->adAccounts()->withCount('adCampaigns')->get()->sum('ad_campaigns_count');

            return response()->json([
                'connected' => true,
                'configured' => true,
                'status' => $integration->status,
                'accounts_count' => $accountsCount,
                'campaigns_count' => $campaignsCount,
                'last_sync' => $integration->last_sync_at?->toISOString(),
                'integration_id' => $integration->id
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads status check failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'connected' => false,
                'configured' => false,
                'accounts_count' => 0,
                'campaigns_count' => 0,
                'error' => 'An unexpected error occurred'
            ]);
        }
    }

    public function accounts(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to view Google Ads accounts.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'google')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads integration not found.'
                ], 404);
            }

            $adAccounts = $integration->adAccounts()
                ->withCount('adCampaigns')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->account_name,
                        'external_id' => $account->external_account_id,
                        'currency' => $account->currency,
                        'status' => $account->status,
                        'industry' => $account->industry,
                        'campaigns_count' => $account->ad_campaigns_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'accounts' => $adAccounts
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads accounts fetch failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Google Ads accounts.'
            ], 500);
        }
    }

    public function syncAccounts(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to sync Google Ads accounts.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'google')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads integration not found.'
                ], 404);
            }

            $config = $integration->app_config;
            if (!isset($config['access_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads access token not found.'
                ], 400);
            }

            $refreshToken = $config['refresh_token'] ?? null;
            $syncResult = $this->syncAdAccounts($integration, $config['access_token'], $refreshToken);

            return response()->json([
                'success' => true,
                'message' => "Synced {$syncResult['synced_count']} Google Ads accounts.",
                'synced_count' => $syncResult['synced_count']
            ]);

        } catch (\Exception $e) {
            Log::error('Google Ads accounts sync failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync Google Ads accounts.'
            ], 500);
        }
    }

    private function syncAdAccounts(Integration $integration, string $accessToken, ?string $refreshToken = null): array
    {
        $syncedCount = 0;

        try {
            $result = $this->googleAdsService->getAdAccounts($accessToken, $refreshToken);
            $accounts = $result['accounts'] ?? [];
            $failedAccounts = $result['failed'] ?? [];

            foreach ($accounts as $account) {
                // Detect industry for this account
                $industry = $this->industryDetectionService->detectIndustry($account['name'] ?? '');

                $accountConfig = [
                    'customer_id' => $account['customer_id'] ?? null,
                    'time_zone' => $account['time_zone'] ?? null,
                    'is_manager' => $account['is_manager'] ?? false,
                    'is_test_account' => $account['is_test_account'] ?? false,
                    'parent_manager_id' => $account['parent_manager_id'] ?? null,
                ];

                $existing = AdAccount::where('integration_id', $integration->id)
                    ->where('external_account_id', $account['id'])
                    ->first();

                if ($existing) {
                    // Update but preserve tenant_id assignment
                    $existing->update([
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $account['status'] ?? 'active',
                        'industry' => $existing->industry ?? $industry,
                        'account_config' => $accountConfig,
                    ]);
                } else {
                    // New account — leave unassigned (null tenant)
                    AdAccount::create([
                        'integration_id' => $integration->id,
                        'tenant_id' => null,
                        'external_account_id' => $account['id'],
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $account['status'] ?? 'active',
                        'industry' => $industry,
                        'account_config' => $accountConfig,
                    ]);
                }

                $syncedCount++;
            }

            // Update integration last sync timestamp
            $integration->update(['last_sync_at' => now()]);

            // Log detailed sync results
            Log::info('Google Ads accounts synced successfully', [
                'integration_id' => $integration->id,
                'synced_count' => $syncedCount,
                'failed_count' => count($failedAccounts)
            ]);

            // Log failed accounts for visibility
            if (!empty($failedAccounts)) {
                Log::error('Some Google Ads accounts failed to sync during integration sync', [
                    'integration_id' => $integration->id,
                    'failed_accounts' => $failedAccounts
                ]);
            }

            return [
                'synced_count' => $syncedCount,
                'failed_count' => count($failedAccounts),
                'failed_accounts' => $failedAccounts
            ];

        } catch (\Exception $e) {
            Log::error('Google Ads accounts sync failed', [
                'integration_id' => $integration->id,
                'error' => 'An unexpected error occurred'
            ]);

            throw $e;
        }
    }

    public function testConnection(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to test Google Ads connection.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'google')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads integration not found.'
                ], 404);
            }

            $config = $integration->app_config;
            if (!isset($config['access_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Ads access token not found.'
                ], 400);
            }

            $testResult = $this->googleAdsService->testConnection($config['access_token']);

            return response()->json($testResult);

        } catch (\Exception $e) {
            Log::error('Google Ads connection test failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to test Google Ads connection.'
            ], 500);
        }
    }
}