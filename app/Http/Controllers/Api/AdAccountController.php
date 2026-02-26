<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdAccountController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * List all ad accounts for the current tenant
     */
    public function index(Request $request)
    {
        try {
            Log::info('Ad Accounts API called', [
                'user_id' => auth()->id(),
                'tenant_id' => $request->header('X-Tenant-ID'),
                'current_tenant_id' => (app()->bound('current_tenant_id') ? app('current_tenant_id') : 'not set'),
                'authenticated' => auth()->check(),
            ]);

            // Include ALL campaigns (not just active) for sub_industry/objective determination
            // This ensures accounts with paused/archived campaigns still show proper values
            $query = AdAccount::with(['integration', 'tenant:id,name', 'adCampaigns' => function ($q) {
                $q->select('id', 'ad_account_id', 'name', 'objective', 'sub_industry', 'status');
            }]);

            // Optional filtering by platform
            if ($request->platform) {
                $query->whereHas('integration', function ($q) use ($request) {
                    $q->where('platform', $request->platform);
                });
            }

            // Optional filtering by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $adAccounts = $query->orderBy('account_name')->get();

            Log::info('Ad Accounts fetched successfully', [
                'count' => $adAccounts->count(),
            ]);

            // Log filter parameters for debugging
            Log::info('Ad Accounts filter params', [
                'from' => $request->from,
                'to' => $request->to,
                'platform' => $request->platform,
                'has_from' => $request->has('from'),
                'has_to' => $request->has('to'),
            ]);

            $adAccountsData = $adAccounts->map(function ($account) use ($request) {
                // Calculate all metrics from ad metrics

                // Build query for metrics with optional date range filtering
                $metricsQuery = $account->adMetrics();

                // Apply date range filter if provided using whereBetween for reliability
                $fromDate = $request->input('from');
                $toDate = $request->input('to');

                if ($fromDate && $toDate) {
                    $metricsQuery->whereBetween('date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $metricsQuery->where('date', '>=', $fromDate);
                } elseif ($toDate) {
                    $metricsQuery->where('date', '<=', $toDate);
                }

                $totalSpend = (clone $metricsQuery)->sum('spend');
                $totalImpressions = (clone $metricsQuery)->sum('impressions');
                $totalClicks = (clone $metricsQuery)->sum('clicks');
                $totalConversions = (clone $metricsQuery)->sum('conversions');
                $totalRevenue = (clone $metricsQuery)->sum('revenue');

                // Get account currency
                $accountCurrency = $account->account_config['currency'] ?? $account->currency ?? 'USD';

                // Convert spend to SAR for aggregation in frontend
                $totalSpendSAR = $this->currencyService->convertToSAR((float) $totalSpend, $accountCurrency);
                $totalRevenueSAR = $this->currencyService->convertToSAR((float) $totalRevenue, $accountCurrency);

                // Get only active campaigns for display
                $activeCampaigns = $account->adCampaigns->map(function ($campaign) {
                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'objective' => $campaign->objective,
                        'sub_industry' => $campaign->sub_industry,
                        'status' => $campaign->status,
                    ];
                });

                return [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'external_account_id' => $account->external_account_id,
                    'platform' => $account->integration->platform,
                    'status' => $account->status,
                    'industry' => $account->industry,
                    'country' => $account->country,
                    'category' => $account->category,
                    'available_categories' => \App\Services\CategoryMapper::getCategoriesForIndustry($account->industry),
                    'currency' => $accountCurrency,
                    'tenant_id' => $account->tenant_id,
                    'tenant' => $account->tenant ? [
                        'id' => $account->tenant->id,
                        'name' => $account->tenant->name,
                    ] : null,
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at,
                    'campaigns_count' => $account->adCampaigns()->count(),
                    'campaigns' => $activeCampaigns,
                    'total_spend' => $totalSpendSAR, // SAR for frontend aggregation
                    'total_spend_original' => (float) $totalSpend, // Original currency for display
                    'total_impressions' => (int) $totalImpressions,
                    'total_clicks' => (int) $totalClicks,
                    'total_conversions' => (int) $totalConversions,
                    'total_revenue' => $totalRevenueSAR, // SAR for frontend aggregation
                    'total_revenue_original' => (float) $totalRevenue, // Original currency for display
                    'data_verification_status' => $account->data_verification_status ?? 'pending',
                    'verification_notes' => $account->verification_notes,
                    'verified_at' => $account->verified_at,
                ];
            });

            return response()->json([
                'data' => $adAccountsData,
                'total' => $adAccountsData->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching ad accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch ad accounts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single ad account with its details
     */
    public function show(AdAccount $adAccount)
    {
        try {
            // Calculate total spend from ad metrics
            $totalSpend = $adAccount->adMetrics()->sum('spend');

            // Get account currency
            $accountCurrency = $adAccount->account_config['currency'] ?? $adAccount->currency ?? 'USD';

            // Return original amount in account's currency (not converted to SAR)
            // Each account keeps its own currency for accurate reporting

            $adAccountData = [
                'id' => $adAccount->id,
                'account_name' => $adAccount->account_name,
                'external_account_id' => $adAccount->external_account_id,
                'platform' => $adAccount->integration->platform,
                'status' => $adAccount->status,
                'industry' => $adAccount->industry,
                'country' => $adAccount->country,
                'category' => $adAccount->category,
                'available_categories' => \App\Services\CategoryMapper::getCategoriesForIndustry($adAccount->industry),
                'currency' => $accountCurrency,
                'created_at' => $adAccount->created_at,
                'updated_at' => $adAccount->updated_at,
                'campaigns_count' => $adAccount->adCampaigns()->count(),
                'total_spend' => (float) $totalSpend,
                'integration_id' => $adAccount->integration_id,
                'last_metrics_sync_at' => $adAccount->last_metrics_sync_at,
                'account_config' => $adAccount->account_config,
            ];

            return response()->json([
                'data' => $adAccountData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching ad account', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch ad account',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an ad account (mainly for industry selection)
     */
    public function update(Request $request, AdAccount $adAccount)
    {
        try {
            $request->validate([
                'industry' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive',
                'tenant_id' => 'nullable|integer|exists:tenants,id',
            ]);

            $updateData = [];

            if ($request->has('industry')) {
                $updateData['industry'] = $request->industry;
            }

            if ($request->has('country')) {
                $updateData['country'] = $request->country;
            }

            if ($request->has('category')) {
                $updateData['category'] = $request->category;
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            if ($request->has('tenant_id')) {
                $updateData['tenant_id'] = $request->tenant_id;
            }

            $adAccount->update($updateData);

            // Reload tenant relationship
            $adAccount->load('tenant:id,name');

            return response()->json([
                'message' => 'Ad account updated successfully',
                'data' => [
                    'id' => $adAccount->id,
                    'account_name' => $adAccount->account_name,
                    'industry' => $adAccount->industry,
                    'country' => $adAccount->country,
                    'category' => $adAccount->category,
                    'status' => $adAccount->status,
                    'tenant_id' => $adAccount->tenant_id,
                    'tenant' => $adAccount->tenant ? [
                        'id' => $adAccount->tenant->id,
                        'name' => $adAccount->tenant->name,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating ad account', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update ad account',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available industry options
     */
    public function industries()
    {
        try {
            $industries = \App\Models\Industry::active()
                ->ordered()
                ->get()
                ->pluck('display_name', 'name')
                ->toArray();

            // Also get sub-industries count for total trackable industries
            $subIndustriesCount = \DB::table('sub_industries')->where('is_active', true)->count();
            $totalTrackableIndustries = count($industries) + $subIndustriesCount;

            return response()->json([
                'industries' => $industries,
                'total_trackable_industries' => $totalTrackableIndustries,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching industries for ad accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to hardcoded industries if database fails
            $industries = [
                'automotive' => 'Automotive',
                'beauty_fitness' => 'Beauty & Fitness',
                'business_industrial' => 'Business & Industrial',
                'computers_electronics' => 'Computers & Electronics',
                'education' => 'Education',
                'entertainment' => 'Entertainment',
                'finance_insurance' => 'Finance & Insurance',
                'food_beverage' => 'Food & Beverage',
                'health_medicine' => 'Health & Medicine',
                'home_garden' => 'Home & Garden',
                'legal' => 'Legal',
                'news_media' => 'News & Media',
                'pets_animals' => 'Pets & Animals',
                'real_estate' => 'Real Estate',
                'reference' => 'Reference',
                'retail' => 'Retail',
                'sports' => 'Sports',
                'technology' => 'Technology',
                'travel_tourism' => 'Travel & Tourism',
                'other' => 'Other',
            ];

            return response()->json([
                'industries' => $industries,
            ]);
        }
    }

    /**
     * Bulk update multiple ad accounts
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $request->validate([
                'account_ids' => 'required|array',
                'account_ids.*' => 'integer|exists:ad_accounts,id',
                'industry' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive',
                'tenant_id' => 'nullable|integer|exists:tenants,id',
            ]);

            $updateData = [];

            if ($request->has('industry')) {
                $updateData['industry'] = $request->industry;
            }

            if ($request->has('country')) {
                $updateData['country'] = $request->country;
            }

            if ($request->has('category')) {
                $updateData['category'] = $request->category;
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            if ($request->has('tenant_id')) {
                $updateData['tenant_id'] = $request->tenant_id;
            }

            if (empty($updateData)) {
                return response()->json([
                    'error' => 'No update data provided',
                ], 400);
            }

            $updatedCount = AdAccount::whereIn('id', $request->account_ids)
                ->update($updateData);

            return response()->json([
                'message' => "Updated {$updatedCount} ad accounts successfully",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk updating ad accounts', [
                'account_ids' => $request->account_ids ?? [],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update ad accounts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify ad account data (approve or decline)
     */
    public function verifyData(Request $request, AdAccount $adAccount)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,declined',
                'notes' => 'nullable|string|max:1000',
            ]);

            $adAccount->update([
                'data_verification_status' => $validated['status'],
                'verification_notes' => $validated['notes'] ?? null,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            Log::info('Ad account data verification updated', [
                'account_id' => $adAccount->id,
                'status' => $validated['status'],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Verification status updated successfully',
                'data' => [
                    'id' => $adAccount->id,
                    'data_verification_status' => $adAccount->data_verification_status,
                    'verified_at' => $adAccount->verified_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating account verification', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update verification status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}