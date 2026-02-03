<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\Tenant;
use App\Services\ClientBuilderService;
use App\Services\ClientDashboardService;
use App\Services\ClientHealthService;
use App\Services\CurrencyConversionService;
use App\Services\DashboardExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    protected $dashboardService;
    protected $builderService;
    protected $healthService;
    protected $exportService;
    protected $currencyService;

    public function __construct(
        ClientDashboardService $dashboardService,
        ClientBuilderService $builderService,
        ClientHealthService $healthService,
        DashboardExportService $exportService,
        CurrencyConversionService $currencyService
    ) {
        $this->dashboardService = $dashboardService;
        $this->builderService = $builderService;
        $this->healthService = $healthService;
        $this->exportService = $exportService;
        $this->currencyService = $currencyService;
    }

    /**
     * Check if user is super admin or has admin role in any tenant
     */
    private function userCanSeeAllClients(): bool
    {
        $user = auth()->user();

        // Super admin check
        if ($user->id === 1 || $user->email === 'technical@redbananas.com') {
            return true;
        }

        // Check if user has admin role in any tenant
        $hasAdminRole = $user->tenants()
            ->wherePivot('role', 'admin')
            ->exists();

        return $hasAdminRole;
    }

    /**
     * List all clients with pagination and filters
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $canSeeAll = $this->userCanSeeAllClients();

            Log::info('ClientController::index called', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'can_see_all_clients' => $canSeeAll,
            ]);

            $query = Tenant::query()->withCount('adAccounts');

            // If user is not super admin and doesn't have admin role in any tenant,
            // only show clients they're associated with
            if (!$canSeeAll) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                Log::info('Filtering clients for user', ['user_id' => $user->id]);
            } else {
                Log::info('Showing all clients - user is admin');
            }

            // Search filter
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_email', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Industry filter
            if ($request->industry) {
                $query->where('industry', $request->industry);
            }

            // Subscription tier filter
            if ($request->subscription_tier) {
                $query->where('subscription_tier', $request->subscription_tier);
            }

            // Account count filters
            if ($request->min_accounts) {
                $query->having('ad_accounts_count', '>=', $request->min_accounts);
            }
            if ($request->max_accounts) {
                $query->having('ad_accounts_count', '<=', $request->max_accounts);
            }

            // Budget filters
            if ($request->min_budget) {
                $query->where('monthly_budget', '>=', $request->min_budget);
            }
            if ($request->max_budget) {
                $query->where('monthly_budget', '<=', $request->max_budget);
            }

            // Contract date filters
            if ($request->contract_start_from) {
                $query->where('contract_start_date', '>=', $request->contract_start_from);
            }
            if ($request->contract_start_to) {
                $query->where('contract_start_date', '<=', $request->contract_start_to);
            }

            // Contract status filter
            if ($request->contract_status) {
                $now = now();
                switch ($request->contract_status) {
                    case 'active':
                        $query->where('contract_end_date', '>', $now);
                        break;
                    case 'expiring_soon':
                        $query->whereBetween('contract_end_date', [$now, $now->copy()->addDays(30)]);
                        break;
                    case 'expired':
                        $query->where('contract_end_date', '<', $now);
                        break;
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $clients = $query->paginate($perPage);

            // Add aggregated spend to each client (converted to SAR)
            $currencyService = app(\App\Services\CurrencyConversionService::class);

            $clients->getCollection()->transform(function ($client) use ($currencyService) {
                // Get total spend from ad_metrics
                // Spend is already in SAR in the database - no conversion needed
                $totalSpendSAR = DB::table('ad_metrics as m')
                    ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                    ->where('a.tenant_id', $client->id)
                    ->sum('m.spend');

                $client->total_spend = $totalSpendSAR;
                $client->logo_url = $client->getLogoUrl();

                return $client;
            });

            return response()->json($clients);

        } catch (\Exception $e) {
            Log::error('Error fetching clients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch clients',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single client details
     */
    public function show(Tenant $tenant)
    {
        try {
            $tenant->load(['adAccounts.integration']);
            $tenant->loadCount('adAccounts');

            // Add aggregated spend (converted to SAR)
            // Get total spend from ad_metrics
            // Spend is already in SAR in the database - no conversion needed
            $totalSpendSAR = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                ->where('a.tenant_id', $tenant->id)
                ->sum('m.spend');

            $tenant->total_spend = $totalSpendSAR;
            $tenant->logo_url = $tenant->getLogoUrl();
            $tenant->contract_active = $tenant->isContractActive();
            $tenant->days_until_contract_expires = $tenant->daysUntilContractExpires();

            return response()->json([
                'data' => $tenant,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch client',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new client
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_person' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'website' => 'nullable|url|max:255',
                'industry' => 'nullable|string|max:255',
                'vertical' => 'nullable|string|max:255',
                'billing_email' => 'nullable|email|max:255',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
                'subscription_tier' => 'nullable|string|in:basic,pro,enterprise',
                'monthly_budget' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,suspended',
            ]);

            // Generate slug from name
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
            $validated['status'] = $validated['status'] ?? 'active';

            $client = Tenant::create($validated);

            Log::info('Client created successfully', [
                'client_id' => $client->id,
                'name' => $client->name,
            ]);

            return response()->json([
                'message' => 'Client created successfully',
                'data' => $client,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating client', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to create client',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update client
     */
    public function update(Request $request, Tenant $tenant)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_person' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'website' => 'nullable|url|max:255',
                'industry' => 'nullable|string|max:255',
                'vertical' => 'nullable|string|max:255',
                'billing_email' => 'nullable|email|max:255',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
                'subscription_tier' => 'nullable|string|in:basic,pro,enterprise',
                'monthly_budget' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,suspended',
            ]);

            $tenant->update($validated);

            Log::info('Client updated successfully', [
                'client_id' => $tenant->id,
                'name' => $tenant->name,
            ]);

            return response()->json([
                'message' => 'Client updated successfully',
                'data' => $tenant->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating client', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update client',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete client (soft delete)
     */
    public function destroy(Tenant $tenant)
    {
        try {
            // Check if client has ad accounts
            $accountsCount = $tenant->adAccounts()->count();

            if ($accountsCount > 0) {
                return response()->json([
                    'error' => 'Cannot delete client with existing ad accounts',
                    'message' => "This client has {$accountsCount} ad accounts. Please remove them first.",
                ], 422);
            }

            $tenant->update(['status' => 'inactive']);

            Log::info('Client deactivated', [
                'client_id' => $tenant->id,
                'name' => $tenant->name,
            ]);

            return response()->json([
                'message' => 'Client deactivated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting client', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete client',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get suggestions for creating a client from ad accounts
     */
    public function suggestFromAccounts(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_ids' => 'required|array|min:1',
                'account_ids.*' => 'required|integer|exists:ad_accounts,id',
            ]);

            $accountIds = $validated['account_ids'];

            // Verify accounts exist and get their details
            $accounts = AdAccount::whereIn('id', $accountIds)
                ->with('integration')
                ->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'error' => 'No valid ad accounts found',
                ], 404);
            }

            // Get suggestions from builder service
            $suggestions = $this->builderService->suggestClientInfo($accountIds);

            Log::info('Generated client suggestions from accounts', [
                'account_ids' => $accountIds,
                'accounts_count' => $accounts->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating client suggestions', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to generate suggestions',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new client from ad accounts
     */
    public function createFromAccounts(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_ids' => 'required|array|min:1',
                'account_ids.*' => 'required|integer|exists:ad_accounts,id',
                'client_id' => 'nullable|integer|exists:tenants,id',
                'name' => 'required_without:client_id|string|max:255',
                'description' => 'nullable|string',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'contact_person' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'website' => 'nullable|url|max:255',
                'industry' => 'nullable|string|max:255',
                'vertical' => 'nullable|string|max:255',
                'billing_email' => 'nullable|email|max:255',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
                'subscription_tier' => 'nullable|string|in:basic,pro,enterprise',
                'monthly_budget' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $accountIds = $validated['account_ids'];

            // Verify accounts exist and are not already assigned
            $accounts = AdAccount::whereIn('id', $accountIds)->get();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'error' => 'No valid ad accounts found',
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Either get existing client or create new one
                if (!empty($validated['client_id'])) {
                    $client = Tenant::findOrFail($validated['client_id']);

                    Log::info('Assigning accounts to existing client', [
                        'client_id' => $client->id,
                        'account_count' => count($accountIds),
                    ]);
                } else {
                    // Create new client
                    $clientData = collect($validated)->except('account_ids', 'client_id')->toArray();
                    $clientData['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
                    $clientData['status'] = 'active';

                    $client = Tenant::create($clientData);

                    Log::info('Created new client from accounts', [
                        'client_id' => $client->id,
                        'name' => $client->name,
                        'account_count' => count($accountIds),
                    ]);
                }

                // Reassign ad accounts to this client
                AdAccount::whereIn('id', $accountIds)->update([
                    'tenant_id' => $client->id,
                ]);

                // Inherit industry from client to ad accounts that don't have one
                if ($client->industry) {
                    AdAccount::whereIn('id', $accountIds)
                        ->whereNull('industry')
                        ->update(['industry' => $client->industry]);
                }

                DB::commit();

                // Get updated client with statistics (converted to SAR)
                $client->loadCount('adAccounts');

                // Get total spend from ad_metrics
                // Spend is already in SAR in the database - no conversion needed
                $totalSpendSAR = DB::table('ad_metrics as m')
                    ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                    ->where('a.tenant_id', $client->id)
                    ->sum('m.spend');

                $client->total_spend = $totalSpendSAR;
                $client->logo_url = $client->getLogoUrl();

                return response()->json([
                    'success' => true,
                    'message' => 'Client created successfully',
                    'data' => [
                        'client' => $client,
                        'accounts_assigned' => count($accountIds),
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error creating client from accounts', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to create client',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client dashboard data
     */
    public function dashboard(Request $request, Tenant $tenant)
    {
        try {
            // Get period from request, default to all-time (0). 0 means all-time.
            $period = $request->input('period', 0);
            $period = $period == 0 ? null : (int)$period; // null = all time
            $platform = $request->input('platform');
            $dateFrom = $request->input('from');
            $dateTo = $request->input('to');

            $filters = [
                'period' => $period,
                'platform' => $platform,
                'from' => $dateFrom,
                'to' => $dateTo,
            ];

            $statistics = $this->dashboardService->getClientStatistics($tenant, $filters);
            $trends = $this->dashboardService->getPerformanceTrends($tenant, $filters);
            $platformBreakdown = $this->dashboardService->getPlatformBreakdown($tenant, $filters);
            $adAccounts = $this->dashboardService->getAdAccountsWithStatus($tenant, $filters);
            $topCampaigns = $this->dashboardService->getTopCampaigns($tenant, 5, $filters);

            return response()->json([
                'client' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo_url' => $tenant->getLogoUrl(),
                    'industry' => $tenant->industry,
                ],
                'metrics' => $statistics,
                'trends' => $trends,
                'platform_breakdown' => $platformBreakdown,
                'ad_accounts' => $adAccounts,
                'top_campaigns' => $topCampaigns,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client dashboard', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload client logo
     */
    public function uploadLogo(Request $request, Tenant $tenant)
    {
        try {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            ]);

            // Delete old logo if exists
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }

            // Store new logo
            $path = $request->file('logo')->store('logos', 'public');
            $tenant->update(['logo' => $path]);

            Log::info('Client logo uploaded', [
                'client_id' => $tenant->id,
                'logo_path' => $path,
            ]);

            return response()->json([
                'message' => 'Logo uploaded successfully',
                'logo_url' => $tenant->getLogoUrl(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading logo', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to upload logo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete client logo
     */
    public function deleteLogo(Tenant $tenant)
    {
        try {
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
                $tenant->update(['logo' => null]);

                Log::info('Client logo deleted', [
                    'client_id' => $tenant->id,
                ]);
            }

            return response()->json([
                'message' => 'Logo deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting logo', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete logo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get global overview statistics for all clients
     */
    public function overview()
    {
        try {
            // Total clients
            $totalClients = Tenant::count();
            $activeClients = Tenant::where('status', 'active')->count();
            $inactiveClients = Tenant::where('status', 'inactive')->count();

            // Total ad accounts
            $totalAccounts = AdAccount::count();
            $connectedAccounts = AdAccount::whereNotNull('tenant_id')->count();

            // Total spend across all clients (with currency conversion)
            $spendByCurrency = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                ->select('a.currency', DB::raw('SUM(m.spend) as total_spend'))
                ->groupBy('a.currency')
                ->get();

            $totalSpend = 0;
            foreach ($spendByCurrency as $row) {
                $currency = $row->currency ?? 'USD';
                $totalSpend += $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
            }

            // Clients by subscription tier
            $tierBreakdown = Tenant::select('subscription_tier', DB::raw('count(*) as count'))
                ->groupBy('subscription_tier')
                ->get()
                ->keyBy('subscription_tier');

            // Clients by industry
            $industryBreakdown = Tenant::select('industry', DB::raw('count(*) as count'))
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Platform distribution across all accounts
            $platformBreakdown = DB::table('ad_accounts as a')
                ->join('integrations as i', 'a.integration_id', '=', 'i.id')
                ->select('i.platform', DB::raw('count(*) as count'))
                ->groupBy('i.platform')
                ->get();

            // Monthly spend trend (last 12 months) with currency conversion
            $monthlySpendRaw = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                ->select(
                    DB::raw('DATE_FORMAT(m.date, "%Y-%m") as month'),
                    'a.currency',
                    DB::raw('SUM(m.spend) as total_spend'),
                    DB::raw('SUM(m.impressions) as total_impressions'),
                    DB::raw('SUM(m.clicks) as total_clicks')
                )
                ->where('m.date', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 12 MONTH)'))
                ->groupBy('month', 'a.currency')
                ->orderBy('month')
                ->get();

            // Aggregate by month with currency conversion
            $monthlyTrend = collect($monthlySpendRaw)
                ->groupBy('month')
                ->map(function ($rows, $month) {
                    $totalSpend = 0;
                    $totalImpressions = 0;
                    $totalClicks = 0;
                    foreach ($rows as $row) {
                        $currency = $row->currency ?? 'USD';
                        $totalSpend += $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
                        $totalImpressions += (int) $row->total_impressions;
                        $totalClicks += (int) $row->total_clicks;
                    }
                    return [
                        'month' => $month,
                        'total_spend' => $totalSpend,
                        'total_impressions' => $totalImpressions,
                        'total_clicks' => $totalClicks,
                    ];
                })
                ->values();

            return response()->json([
                'data' => [
                    'totals' => [
                        'clients' => $totalClients,
                        'active_clients' => $activeClients,
                        'inactive_clients' => $inactiveClients,
                        'ad_accounts' => $totalAccounts,
                        'connected_accounts' => $connectedAccounts,
                        'total_spend' => $totalSpend,
                    ],
                    'tier_breakdown' => [
                        'basic' => $tierBreakdown->get('basic')?->count ?? 0,
                        'pro' => $tierBreakdown->get('pro')?->count ?? 0,
                        'enterprise' => $tierBreakdown->get('enterprise')?->count ?? 0,
                    ],
                    'industry_breakdown' => $industryBreakdown,
                    'platform_breakdown' => $platformBreakdown,
                    'monthly_trend' => $monthlyTrend,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client overview', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch overview statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client statistics
     */
    public function statistics(Tenant $tenant)
    {
        try {
            $statistics = $this->dashboardService->getClientStatistics($tenant);

            return response()->json([
                'data' => $statistics,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client statistics', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client health score
     */
    public function health(Tenant $tenant)
    {
        try {
            $health = $this->healthService->calculateHealthScore($tenant);

            return response()->json([
                'data' => $health,
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating client health', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to calculate health score',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export dashboard to PDF
     */
    public function exportPdf(Request $request, Tenant $tenant)
    {
        try {
            $filters = [
                'period' => $request->get('period', 30),
                'platform' => $request->get('platform'),
                'from' => $request->get('from'),
                'to' => $request->get('to'),
            ];

            $path = $this->exportService->exportToPdf($tenant, $filters);
            $filename = basename($path);

            return response()->download(
                storage_path('app/' . $path),
                $filename,
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting dashboard to PDF', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to export PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export dashboard to CSV
     */
    public function exportCsv(Request $request, Tenant $tenant)
    {
        try {
            $filters = [
                'period' => $request->get('period', 30),
                'platform' => $request->get('platform'),
                'from' => $request->get('from'),
                'to' => $request->get('to'),
            ];

            $filename = $this->exportService->exportToCsv($tenant, $filters);

            return response()->download(
                storage_path('app/' . $filename),
                basename($filename),
                ['Content-Type' => 'text/csv']
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting dashboard to CSV', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to export CSV',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export dashboard to Excel
     */
    public function exportExcel(Request $request, Tenant $tenant)
    {
        try {
            $filters = [
                'period' => $request->get('period', 30),
                'platform' => $request->get('platform'),
                'from' => $request->get('from'),
                'to' => $request->get('to'),
            ];

            $filename = $this->exportService->exportToExcel($tenant, $filters);

            return response()->download(
                storage_path('app/' . $filename),
                basename($filename),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting dashboard to Excel', [
                'client_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to export Excel',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
