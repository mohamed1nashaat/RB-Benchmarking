<?php

namespace App\Http\Controllers\Api;
use App\Models\Scopes\TenantScope;

use App\Helpers\OAuthStateHelper;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\SnapchatAdsService;
use App\Services\IndustryDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SnapchatIntegrationController extends Controller
{
    protected SnapchatAdsService $snapchatAdsService;
    protected IndustryDetectionService $industryDetectionService;

    public function __construct(SnapchatAdsService $snapchatAdsService, IndustryDetectionService $industryDetectionService)
    {
        $this->snapchatAdsService = $snapchatAdsService;
        $this->industryDetectionService = $industryDetectionService;
    }

    public function redirect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tenant = $request->current_tenant;

            $stateData = [
                
            'redirect_uri' => config('services.' . basename(app/Http/Controllers/Api/SnapchatIntegrationController.php, 'IntegrationController.php') . '.redirect_uri'),'user_id' => $user->id,
                'tenant_id' => $tenant ? $tenant->id : $user->default_tenant_id,
                'timestamp' => time(),
                'nonce' => Str::random(32)
            ];

            $state = OAuthStateHelper::generate($stateData);

            $authUrl = $this->snapchatAdsService->getAuthorizationUrl($state);

            Log::info('Snapchat Ads OAuth redirect initiated', [
                'user_id' => $user->id,
                'tenant_id' => $tenant ? $tenant->id : $user->default_tenant_id,
            ]);

            return response()->json([
                'success' => true,
                'oauth_url' => $authUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Snapchat Ads OAuth redirect failed', [
                'user_id' => $request->user()->id,
                'error' => 'An unexpected error occurred'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Snapchat Ads OAuth. Please try again.'
            ], 500);
        }
    }

    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $code = $request->query('code');
            $state = $request->query('state');
            $error = $request->query('error');

            if ($error) {
                Log::warning('Snapchat Ads OAuth error', [
                    'error' => $error,
                    'error_description' => $request->query('error_description')
                ]);

                return redirect('/integrations?error=' . urlencode('Snapchat Ads authorization was cancelled or failed.'));
            }

            if (!$code || !$state) {
                return redirect('/integrations?error=' . urlencode('Missing authorization code or state parameter.'));
            }

            // Decode and validate state
            try {
                // Fix base64 characters that may be corrupted in URL transit (+ decoded as space)
                $fixedState = str_replace(' ', '+', $state);
                $stateData = OAuthStateHelper::validate($state);

                if (!$stateData || !isset($stateData['user_id'], $stateData['timestamp'], $stateData['nonce'])) {
                    throw new \Exception('Invalid state parameter structure');
                }

                // Default tenant_id to null if not present
                $stateData['tenant_id'] = $stateData['tenant_id'] ?? null;

                // Check if state is not too old (5 minutes max)
                if (($stateData['redirect_uri'] ?? null) !== config('services.' . strtolower(basename(app/Http/Controllers/Api/SnapchatIntegrationController.php, 'IntegrationController.php')) . '.redirect_uri')) {
                    throw new \Exception('Invalid redirect URI in state');
                }

                if (time() - $stateData['timestamp'] > 300) {
                    throw new \Exception('OAuth state has expired');
                }

            } catch (\Exception $e) {
                Log::error('Invalid Snapchat Ads OAuth state', [
                    'state' => $state,
                    'error' => 'An unexpected error occurred'
                ]);

                return redirect('/integrations?error=' . urlencode('Invalid or expired authorization state.'));
            }

            // Exchange code for tokens
            $tokenData = $this->snapchatAdsService->getAccessToken($code);

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return redirect('/integrations?error=' . urlencode('Failed to exchange authorization code for access token.'));
            }

            // Get user info and organization ID
            $userInfo = $this->snapchatAdsService->testConnection($tokenData['access_token']);

            // Store integration
            $integration = Integration::updateOrCreate(
                [
                    'tenant_id' => $stateData['tenant_id'],
                    'platform' => 'snapchat',
                ],
                [
                    'user_id' => $stateData['user_id'],
                    'app_config' => [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_at' => isset($tokenData['expires_in'])
                            ? time() + $tokenData['expires_in']
                            : null,
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'scope' => $tokenData['scope'] ?? null,
                        'organization_id' => $userInfo['organization_id'] ?? null,
                        'user_info' => $userInfo,
                    ],
                    'created_by' => $stateData['user_id'],
                    'status' => 'active',
                    'last_sync_at' => now(),
                ]
            );

            Log::info('Snapchat Ads integration created/updated', [
                'integration_id' => $integration->id,
                'user_id' => $stateData['user_id'],
                'tenant_id' => $stateData['tenant_id'],
            ]);

            // Fetch and store ad accounts
            $this->syncAdAccounts($integration, $tokenData['access_token']);

            return redirect('/integrations?success=' . urlencode('Snapchat Ads connected successfully!'));

        } catch (\Exception $e) {
            Log::error('Snapchat Ads OAuth callback failed', ['error' => 'An unexpected error occurred']);

            return redirect('/integrations?error=' . urlencode('Failed to complete Snapchat Ads integration. Please try again.'));
        }
    }

    public function disconnect(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to disconnect Snapchat Ads.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found.'
                ], 404);
            }

            // Delete related ad accounts and campaigns
            foreach ($integration->adAccounts as $adAccount) {
                $adAccount->campaigns()->delete();
            }
            $integration->adAccounts()->delete();
            $integration->delete();

            Log::info('Snapchat Ads integration disconnected', [
                'integration_id' => $integration->id,
                'tenant_id' => $tenant->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Snapchat Ads account disconnected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Snapchat Ads disconnect failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Snapchat Ads account.'
            ], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            // For super admins without tenant, show aggregate status
            if (!$request->current_tenant) {
                $allIntegrations = Integration::where('platform', 'snapchat')->get();

                if ($allIntegrations->isEmpty()) {
                    return response()->json([
                        'connected' => false,
                        'accounts_count' => 0,
                        'campaigns_count' => 0
                    ]);
                }

                $totalAccounts = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))->count();
                $totalCampaigns = AdAccount::whereIn('integration_id', $allIntegrations->pluck('id'))
                    ->withCount('campaigns')->get()->sum('campaigns_count');

                return response()->json([
                    'connected' => true,
                    'status' => 'active',
                    'accounts_count' => $totalAccounts,
                    'campaigns_count' => $totalCampaigns,
                    'integrations_count' => $allIntegrations->count(),
                    'last_sync' => $allIntegrations->max('last_sync_at')?->toISOString()
                ]);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'connected' => false,
                    'accounts_count' => 0,
                    'campaigns_count' => 0
                ]);
            }

            $accountsCount = $integration->adAccounts()->count();
            $campaignsCount = $integration->adAccounts()->withCount('campaigns')->get()->sum('campaigns_count');

            return response()->json([
                'connected' => true,
                'status' => $integration->status,
                'accounts_count' => $accountsCount,
                'campaigns_count' => $campaignsCount,
                'last_sync' => $integration->last_sync_at?->toISOString(),
                'integration_id' => $integration->id
            ]);

        } catch (\Exception $e) {
            Log::error('Snapchat Ads status check failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'connected' => false,
                'accounts_count' => 0,
                'campaigns_count' => 0
            ]);
        }
    }

    public function accounts(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to view Snapchat Ads accounts.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found.'
                ], 404);
            }

            $adAccounts = $integration->adAccounts()
                ->withCount('campaigns')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->account_name,
                        'external_id' => $account->external_account_id,
                        'currency' => $account->currency,
                        'status' => $account->status,
                        'industry' => $account->industry,
                        'campaigns_count' => $account->campaigns_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'accounts' => $adAccounts
            ]);

        } catch (\Exception $e) {
            Log::error('Snapchat Ads accounts fetch failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Snapchat Ads accounts.'
            ], 500);
        }
    }

    public function syncAccounts(Request $request): JsonResponse
    {
        try {
            if (!$request->current_tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant selection required to sync Snapchat Ads accounts.'
                ], 400);
            }

            $tenant = $request->current_tenant;

            $integration = Integration::where('tenant_id', $tenant->id)
                ->where('platform', 'snapchat')
                ->first();

            if (!$integration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads integration not found.'
                ], 404);
            }

            $config = $integration->app_config;
            if (!isset($config['access_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapchat Ads access token not found.'
                ], 400);
            }

            $syncResult = $this->syncAdAccounts($integration, $config['access_token']);

            return response()->json([
                'success' => true,
                'message' => "Synced {$syncResult['synced_count']} Snapchat Ads accounts.",
                'synced_count' => $syncResult['synced_count']
            ]);

        } catch (\Exception $e) {
            Log::error('Snapchat Ads accounts sync failed', [
                'error' => 'An unexpected error occurred',
                'tenant_id' => $request->current_tenant ? $request->current_tenant->id : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync Snapchat Ads accounts.'
            ], 500);
        }
    }

    private function syncAdAccounts(Integration $integration, string $accessToken): array
    {
        $syncedCount = 0;

        try {
            $accounts = $this->snapchatAdsService->getAdAccounts($accessToken);

            foreach ($accounts as $account) {
                // Detect industry for this account
                $industry = $this->industryDetectionService->detectIndustry($account['name'] ?? '');

                // Map Snapchat status to database ENUM values
                $status = $this->mapSnapchatAccountStatus($account['status'] ?? 'ACTIVE');

                // Sync bypass: Locate existing account by external ID to preserve tenant assignment
                $existingAccount = AdAccount::withoutGlobalScope(TenantScope::class)->where([
                    'integration_id' => $integration->id,
                    'external_account_id' => $account['id'],
                ])->first();

                AdAccount::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    [
                        'integration_id' => $integration->id,
                        'external_account_id' => $account['id'],
                    ],
                    [
                        'tenant_id' => AdAccount::syncTenantId($integration->id, $account['id'], $integration->tenant_id),
                        'account_name' => $account['name'],
                        'currency' => $account['currency'] ?? 'USD',
                        'status' => $status,
                        'industry' => $existingAccount?->industry ?? $industry,
                        'account_config' => [
                            'time_zone' => $account['time_zone'] ?? null,
                            'organization_id' => $account['organization_id'] ?? null,
                        ],
                    ]
                );

                $syncedCount++;
            }

            // Update integration last sync timestamp
            $integration->update(['last_sync_at' => now()]);

            Log::info('Snapchat Ads accounts synced successfully', [
                'integration_id' => $integration->id,
                'synced_count' => $syncedCount
            ]);

            return ['synced_count' => $syncedCount];

        } catch (\Exception $e) {
            Log::error('Snapchat Ads accounts sync failed', [
                'integration_id' => $integration->id,
                'error' => 'An unexpected error occurred'
            ]);

            throw $e;
        }
    }

    /**
     * Map Snapchat ad account status to database ENUM values
     */
    private function mapSnapchatAccountStatus(string $snapchatStatus): string
    {
        $statusMap = [
            'ACTIVE' => 'active',
            'PAUSED' => 'inactive',
            'PENDING' => 'inactive',
            'SUSPENDED' => 'inactive',
            'DELETED' => 'inactive',
        ];

        return $statusMap[strtoupper($snapchatStatus)] ?? 'active';
    }
}