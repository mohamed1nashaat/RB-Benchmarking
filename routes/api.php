<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdAccountController;
use App\Http\Controllers\Api\BenchmarkController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacebookIntegrationController;
use App\Http\Controllers\Api\GoogleAdsIntegrationController;
use App\Http\Controllers\Api\TikTokIntegrationController;
use App\Http\Controllers\Api\SnapchatIntegrationController;
use App\Http\Controllers\Api\LinkedInIntegrationController;
use App\Http\Controllers\Api\TwitterIntegrationController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Facebook OAuth callback (public)
Route::get('/facebook/callback', [FacebookIntegrationController::class, 'handleCallback']);

// Google Ads OAuth callback (public)
Route::get('/auth/google-ads/callback', [GoogleAdsIntegrationController::class, 'callback']);

// TikTok Ads OAuth callback (public)
Route::get('/tiktok/oauth/callback', [TikTokIntegrationController::class, 'callback']);

// Snapchat Ads OAuth callback (public)
Route::get('/snapchat/oauth/callback', [SnapchatIntegrationController::class, 'callback']);

// LinkedIn Ads OAuth callback (public)
Route::get('/linkedin/oauth/callback', [LinkedInIntegrationController::class, 'callback']);

// Twitter/X Ads OAuth callback (public)
Route::get('/twitter/oauth/callback', [TwitterIntegrationController::class, 'callback']);

// Legacy Snapchat OAuth callbacks (public) - for backward compatibility
Route::get('/oauth/snapchat/callback', [App\Http\Controllers\SnapchatOAuthController::class, 'callback']);

// Sync progress endpoint (public - log file name acts as token)
Route::get('/sync-progress/{logFile}', function ($logFile) {
    // Security: Only allow alphanumeric, dashes, and dots in filename
    // Allow both sync-metrics-*.log and platform-sync-*.log files
    if (!preg_match('/^(sync-metrics|platform-sync)-[\w\-\.]+\.log$/', $logFile)) {
        return response()->json(['error' => 'Invalid log file'], 400);
    }

    $logPath = storage_path('logs/' . $logFile);

    if (!file_exists($logPath)) {
        return response()->json([
            'error' => 'Log file not found',
            'complete' => false,
            'percent' => 0,
        ], 404);
    }

    $content = file_get_contents($logPath);

    // Check if sync is complete
    $complete = str_contains($content, '=== All Time Sync Complete ===') ||
                str_contains($content, 'Sync completed successfully') ||
                str_contains($content, '=== Sync Complete ===') ||
                str_contains($content, '=== Platform Sync Complete ===');

    // Parse current period being processed (find last "=== Processing" line)
    $currentPeriod = null;
    $totalPeriods = 24; // Default fallback
    $periodsProcessed = 0;

    // Try to get total months from log (Snapchat outputs "Syncing X months of data")
    if (preg_match('/Syncing (\d+(?:\.\d+)?) months of data/', $content, $totalMatch)) {
        $totalPeriods = (int) ceil((float) $totalMatch[1]);
    }

    if (preg_match_all('/=== Processing (Q\d \d{4}|[A-Za-z]+ \d{4})/', $content, $matches)) {
        $currentPeriod = end($matches[1]);
        $periodsProcessed = count($matches[1]);
    }

    // Platform sync format: "Completed account X/Y"
    if (preg_match_all('/Completed account (\d+)\/(\d+)/', $content, $platformMatches)) {
        $lastMatch = end($platformMatches[1]);
        $totalAccounts = end($platformMatches[2]);
        $periodsProcessed = (int) $lastMatch;
        $totalPeriods = (int) $totalAccounts;
        // Get current account being synced
        if (preg_match('/=== Syncing account \d+\/\d+: (.+) ===/', $content, $currentMatch)) {
            $currentPeriod = $currentMatch[1];
        }
    }

    // Parse created/updated totals from completion message
    $created = 0;
    $updated = 0;

    // Handle different output formats
    if (preg_match('/Total Created: (\d+)/', $content, $m)) {
        $created = (int) $m[1];
    }
    if (preg_match('/Total Updated: (\d+)/', $content, $m)) {
        $updated = (int) $m[1];
    }
    // Snapchat format: "Total metrics synced: X"
    if (preg_match('/Total metrics synced: (\d+)/', $content, $m)) {
        $created = (int) $m[1];
    }
    // Snapchat format: "Batch upserted X metrics"
    if (preg_match_all('/Batch upserted (\d+) metrics/', $content, $batchMatches)) {
        $created = array_sum(array_map('intval', $batchMatches[1]));
    }

    // If not complete, sum from individual lines (Google/LinkedIn format)
    if (!$complete && $created == 0) {
        if (preg_match_all('/✓[^:]*: \+(\d+), ~(\d+)/', $content, $resultMatches)) {
            $created = array_sum(array_map('intval', $resultMatches[1]));
            $updated = array_sum(array_map('intval', $resultMatches[2]));
        }
    }

    // Calculate progress percentage
    // Cap at 99% instead of 95% - Snapchat outputs all month markers before API call,
    // then API takes 2-3 minutes, so 99% indicates "processing API data"
    $calculatedPercent = round(($periodsProcessed / max(1, $totalPeriods)) * 100);
    $percent = $complete ? 100 : min(99, $calculatedPercent);

    // Get last meaningful line for display
    $lines = explode("\n", trim($content));
    $lastLine = '';
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);
        if (str_starts_with($line, '===') || str_starts_with($line, '✓') || str_starts_with($line, '✗')) {
            $lastLine = $line;
            break;
        }
    }

    return response()->json([
        'percent' => $percent,
        'currentMonth' => $currentPeriod,
        'totalMonths' => $totalPeriods,
        'monthsProcessed' => $periodsProcessed,
        'created' => $created,
        'updated' => $updated,
        'complete' => $complete,
        'lastLine' => $lastLine,
    ]);
});

// Demo/Public benchmarks endpoints (no auth required for testing)
Route::prefix('demo')->group(function () {
    Route::get('/benchmarks/summary', function (Request $request) {
        return response()->json([
            'data' => [
                'total_industries' => 8,
                'total_accounts' => 45,
                'total_spend' => 125000,
                'best_performing' => [
                    ['industry' => 'technology', 'accounts_count' => 12]
                ],
                'needs_improvement' => [
                    ['industry' => 'retail', 'accounts_count' => 8]
                ],
                'industry_breakdown' => []
            ],
            'date_range' => [
                'from' => $request->get('from', '2024-01-01'),
                'to' => $request->get('to', '2024-01-31'),
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/industry-benchmarks', function (Request $request) {
        return response()->json([
            'data' => [
                'technology' => [
                    'industry' => 'technology',
                    'accounts_count' => 12,
                    'total_spend' => 45000,
                    'total_impressions' => 1250000,
                    'total_clicks' => 28500,
                    'total_leads' => 3420,
                    'metrics' => [
                        'ctr' => [
                            'actual' => 2.28,
                            'benchmark' => ['min' => 1.2, 'avg' => 2.1, 'max' => 3.8],
                            'performance' => 75,
                            'status' => 'good'
                        ],
                        'cpc' => [
                            'actual' => 1.58,
                            'benchmark' => ['min' => 0.8, 'avg' => 1.5, 'max' => 2.2],
                            'performance' => 65,
                            'status' => 'average'
                        ],
                        'cvr' => [
                            'actual' => 12.0,
                            'benchmark' => ['min' => 8.0, 'avg' => 12.5, 'max' => 18.0],
                            'performance' => 70,
                            'status' => 'good'
                        ]
                    ]
                ],
                'retail' => [
                    'industry' => 'retail',
                    'accounts_count' => 8,
                    'total_spend' => 32000,
                    'total_impressions' => 980000,
                    'total_clicks' => 19600,
                    'total_leads' => 1960,
                    'metrics' => [
                        'ctr' => [
                            'actual' => 2.0,
                            'benchmark' => ['min' => 1.0, 'avg' => 1.8, 'max' => 3.2],
                            'performance' => 80,
                            'status' => 'good'
                        ],
                        'cpc' => [
                            'actual' => 1.63,
                            'benchmark' => ['min' => 0.6, 'avg' => 1.2, 'max' => 1.8],
                            'performance' => 45,
                            'status' => 'below_average'
                        ],
                        'cvr' => [
                            'actual' => 10.0,
                            'benchmark' => ['min' => 6.0, 'avg' => 10.0, 'max' => 15.0],
                            'performance' => 65,
                            'status' => 'average'
                        ]
                    ]
                ]
            ],
            'date_range' => [
                'from' => $request->get('from', '2024-01-01'),
                'to' => $request->get('to', '2024-01-31'),
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/insights', function (Request $request) {
        return response()->json([
            'insights' => [
                [
                    'type' => 'success',
                    'message' => 'Technology industry shows excellent CTR performance at 2.28%, well above average.',
                    'priority' => 'info'
                ],
                [
                    'type' => 'warning',
                    'message' => 'Retail sector CPC is 36% above industry average. Consider optimizing bidding strategies.',
                    'priority' => 'medium'
                ],
                [
                    'type' => 'info',
                    'message' => 'Overall conversion rates across all industries are within expected ranges.',
                    'priority' => 'info'
                ]
            ],
            'date_range' => [
                'from' => $request->get('from', '2024-01-01'),
                'to' => $request->get('to', '2024-01-31'),
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/trending-metrics', function (Request $request) {
        $metric = $request->get('metric', 'ctr');
        $baseValues = [
            'ctr' => ['your' => 2.15, 'industry' => 1.95, 'top' => 3.45],
            'cpc' => ['your' => 1.25, 'industry' => 1.50, 'top' => 0.95],
            'cvr' => ['your' => 11.5, 'industry' => 12.8, 'top' => 18.2],
            'cpl' => ['your' => 15.50, 'industry' => 18.25, 'top' => 12.75],
        ];
        
        $values = $baseValues[$metric] ?? $baseValues['ctr'];
        
        return response()->json([
            'data' => [
                'your_avg' => $values['your'],
                'your_change' => rand(-5, 8) / 10,
                'industry_avg' => $values['industry'],
                'industry_change' => rand(-3, 5) / 10,
                'top_performers_avg' => $values['top'],
                'audience_breakdown' => [
                    'luxury' => ['avg' => $values['top'] * 1.1, 'accounts' => 5],
                    'premium' => ['avg' => $values['your'] * 1.05, 'accounts' => 12],
                    'mid_class' => ['avg' => $values['industry'], 'accounts' => 25],
                    'value' => ['avg' => $values['industry'] * 0.95, 'accounts' => 18]
                ]
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/competitive-analysis', function (Request $request) {
        return response()->json([
            'data' => [
                'market_rank' => 8,
                'percentile' => 65,
                'total_competitors' => 45,
                'opportunity_score' => 78,
                'insights' => [
                    [
                        'type' => 'opportunity',
                        'title' => 'CTR Improvement Potential',
                        'description' => 'Your CTR is 15% below top performers in your industry. Focus on ad creative optimization.',
                        'impact_level' => 'High'
                    ],
                    [
                        'type' => 'strength',
                        'title' => 'Cost Efficiency Leader',
                        'description' => 'Your CPC is 22% lower than industry average, showing excellent bidding optimization.',
                        'impact_level' => 'Medium'
                    ]
                ]
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/industries', function () {
        return response()->json([
            'industries' => [
                'technology' => 'Technology',
                'retail' => 'Retail & E-commerce',
                'finance' => 'Finance & Insurance',
                'healthcare' => 'Healthcare & Medical',
                'education' => 'Education',
                'real_estate' => 'Real Estate',
                'automotive' => 'Automotive',
                'food_beverage' => 'Food & Beverage'
            ],
            'industries_with_data' => [
                'technology', 'retail', 'finance', 'healthcare', 'education'
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/sub-industries', function (Request $request) {
        $industry = $request->get('industry');
        $subIndustries = [
            'technology' => ['SaaS', 'E-commerce Platform', 'Mobile Apps', 'AI/ML Tools'],
            'retail' => ['Fashion', 'Electronics', 'Home & Garden', 'Sports'],
            'finance' => ['Banking', 'Insurance', 'Investments', 'Fintech'],
            'healthcare' => ['Medical Devices', 'Pharmaceuticals', 'Telemedicine', 'Health Services'],
            'education' => ['Online Courses', 'K-12', 'Higher Education', 'Professional Training']
        ];
        
        return response()->json([
            'data' => $subIndustries[$industry] ?? [],
            'industry_filter' => $industry,
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/objectives', function () {
        return response()->json([
            'data' => [
                'leads', 'messages', 'calls', 'sales', 'conversions', 
                'catalog_sales', 'store_visits', 'traffic', 'link_clicks',
                'engagement', 'video_views', 'page_likes', 'awareness',
                'reach', 'impressions', 'app_installs', 'app_events'
            ],
            'demo' => true
        ]);
    });
    
    Route::get('/benchmarks/filter-options', function () {
        return response()->json([
            'platforms' => ['facebook', 'google', 'tiktok'],
            'funnel_stages' => ['TOF', 'MOF', 'BOF'],
            'user_journeys' => ['instant_form', 'landing_page'],
            'funnel_stage_labels' => [
                'TOF' => 'Top of Funnel (Awareness)',
                'MOF' => 'Middle of Funnel (Consideration)',
                'BOF' => 'Bottom of Funnel (Conversion)',
            ],
            'user_journey_labels' => [
                'instant_form' => 'Instant Form (Lead Form)',
                'landing_page' => 'Landing Page',
            ],
            'platform_labels' => [
                'facebook' => 'Facebook / Meta',
                'google' => 'Google Ads',
                'tiktok' => 'TikTok Ads',
            ],
            'demo' => true
        ]);
    });
    
    Route::post('/benchmarks/calculate', function (Request $request) {
        $spend = $request->get('spend', 1000);
        $industry = $request->get('industry', 'technology');
        
        // Simple calculation based on industry averages
        $multipliers = [
            'technology' => ['ctr' => 2.1, 'cpc' => 1.5, 'cvr' => 12.5],
            'retail' => ['ctr' => 1.8, 'cpc' => 1.2, 'cvr' => 10.0],
            'education' => ['ctr' => 2.5, 'cpc' => 1.8, 'cvr' => 15.0],
            'healthcare' => ['ctr' => 2.2, 'cpc' => 2.0, 'cvr' => 14.0],
            'finance' => ['ctr' => 1.9, 'cpc' => 3.5, 'cvr' => 8.5],
        ];
        
        $defaults = $multipliers[$industry] ?? $multipliers['technology'];
        $impressions = round($spend / ($defaults['cpc'] / 100 * $defaults['ctr']));
        $clicks = round($impressions * ($defaults['ctr'] / 100));
        $conversions = round($clicks * ($defaults['cvr'] / 100));
        
        return response()->json([
            'data' => [
                'benchmark_info' => [
                    'data_points' => 150,
                    'industry' => $industry,
                    'calculation_method' => 'demo_averages'
                ],
                'predictions' => [
                    'poor' => [
                        'primary_result' => ['label' => 'Leads', 'value' => round($conversions * 0.7)],
                        'cost_per_result' => ['cost_per_lead' => '﷼' . number_format($spend / ($conversions * 0.7), 2)],
                        'clicks' => ['value' => round($clicks * 0.8)],
                        'impressions' => ['value' => round($impressions * 0.9)],
                        'metrics' => [
                            'ctr' => number_format($defaults['ctr'] * 0.6, 2) . '%',
                            'cpc' => '﷼' . number_format($defaults['cpc'] * 1.4, 2),
                            'cvr' => number_format($defaults['cvr'] * 0.7, 2) . '%'
                        ]
                    ],
                    'average' => [
                        'primary_result' => ['label' => 'Leads', 'value' => $conversions],
                        'cost_per_result' => ['cost_per_lead' => '﷼' . number_format($spend / $conversions, 2)],
                        'clicks' => ['value' => $clicks],
                        'impressions' => ['value' => $impressions],
                        'metrics' => [
                            'ctr' => number_format($defaults['ctr'], 2) . '%',
                            'cpc' => '﷼' . number_format($defaults['cpc'], 2),
                            'cvr' => number_format($defaults['cvr'], 2) . '%'
                        ]
                    ],
                    'good' => [
                        'primary_result' => ['label' => 'Leads', 'value' => round($conversions * 1.3)],
                        'cost_per_result' => ['cost_per_lead' => '﷼' . number_format($spend / ($conversions * 1.3), 2)],
                        'clicks' => ['value' => round($clicks * 1.2)],
                        'impressions' => ['value' => round($impressions * 1.1)],
                        'metrics' => [
                            'ctr' => number_format($defaults['ctr'] * 1.3, 2) . '%',
                            'cpc' => '﷼' . number_format($defaults['cpc'] * 0.8, 2),
                            'cvr' => number_format($defaults['cvr'] * 1.2, 2) . '%'
                        ]
                    ],
                    'excellent' => [
                        'primary_result' => ['label' => 'Leads', 'value' => round($conversions * 1.8)],
                        'cost_per_result' => ['cost_per_lead' => '﷼' . number_format($spend / ($conversions * 1.8), 2)],
                        'clicks' => ['value' => round($clicks * 1.5)],
                        'impressions' => ['value' => round($impressions * 1.2)],
                        'metrics' => [
                            'ctr' => number_format($defaults['ctr'] * 1.8, 2) . '%',
                            'cpc' => '﷼' . number_format($defaults['cpc'] * 0.6, 2),
                            'cvr' => number_format($defaults['cvr'] * 1.6, 2) . '%'
                        ]
                    ]
                ],
                'disclaimers' => [
                    'This is demo data for testing purposes.',
                    'Connect real advertising accounts for accurate predictions.',
                    'Actual results may vary based on campaign setup and market conditions.'
                ]
            ],
            'demo' => true
        ]);
    });
});

// Debug route to test what frontend is sending
Route::get('/debug/request', function (Request $request) {
    return response()->json([
        'headers' => $request->headers->all(),
        'bearer_token' => $request->bearerToken(),
        'auth_header' => $request->header('Authorization'),
        'tenant_header' => $request->header('X-Tenant-ID'),
        'all_headers_debug' => [
            'authorization' => $request->header('Authorization'),
            'accept' => $request->header('Accept'),
            'content_type' => $request->header('Content-Type'),
            'x_tenant_id' => $request->header('X-Tenant-ID'),
        ]
    ]);
});

// Protected routes requiring authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/profile', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/me/avatar', [AuthController::class, 'deleteAvatar']);

    // Client Management routes (agency-level, not tenant-scoped)
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::get('/overview', [ClientController::class, 'overview']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{tenant}', [ClientController::class, 'show']);
        Route::put('/{tenant}', [ClientController::class, 'update']);
        Route::delete('/{tenant}', [ClientController::class, 'destroy']);

        // Client dashboard & stats
        Route::get('/{tenant}/dashboard', [ClientController::class, 'dashboard']);
        Route::get('/{tenant}/statistics', [ClientController::class, 'statistics']);
        Route::get('/{tenant}/health', [ClientController::class, 'health']);

        // Export routes
        Route::post('/{tenant}/export/pdf', [ClientController::class, 'exportPdf']);
        Route::post('/{tenant}/export/csv', [ClientController::class, 'exportCsv']);
        Route::post('/{tenant}/export/excel', [ClientController::class, 'exportExcel']);

        // Logo upload
        Route::post('/{tenant}/logo', [ClientController::class, 'uploadLogo']);
        Route::delete('/{tenant}/logo', [ClientController::class, 'deleteLogo']);

        // Client builder wizard
        Route::post('/suggest-from-accounts', [ClientController::class, 'suggestFromAccounts']);
        Route::post('/create-from-accounts', [ClientController::class, 'createFromAccounts']);
    });

    // Industries endpoint - static data, no tenant filtering needed
    Route::get('/ad-accounts/industries', [AdAccountController::class, 'industries']);

    // Tenants endpoint for tenant switcher (not tenant-scoped)
    Route::get('/tenants', [TenantController::class, 'index']);

    // Tenant-scoped routes
    Route::middleware([TenantMiddleware::class])->group(function () {
        // Metrics endpoints
        Route::prefix('metrics')->group(function () {
            Route::get('/summary', [MetricsController::class, 'summary']);
            Route::get('/timeseries', [MetricsController::class, 'timeseries']);
            Route::get('/spend-breakdown', [MetricsController::class, 'spendBreakdown']);
        });
        
        // Dashboard endpoints
        Route::prefix('dashboards')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::post('/', [DashboardController::class, 'store']);
            Route::get('/{dashboard}', [DashboardController::class, 'show']);
            Route::put('/{dashboard}', [DashboardController::class, 'update']);
            Route::delete('/{dashboard}', [DashboardController::class, 'destroy']);
            
            // Widget management
            Route::post('/{dashboard}/widgets', [DashboardController::class, 'addWidget']);
            Route::put('/{dashboard}/widgets/{widget}', [DashboardController::class, 'updateWidget']);
            Route::delete('/{dashboard}/widgets/{widget}', [DashboardController::class, 'removeWidget']);
        });
        
        // Ad Accounts endpoints
        Route::prefix('ad-accounts')->group(function () {
            Route::get('/', [AdAccountController::class, 'index']);
            Route::get('/{adAccount}', [AdAccountController::class, 'show']);
            Route::put('/{adAccount}', [AdAccountController::class, 'update']);
            Route::put('/bulk-update', [AdAccountController::class, 'bulkUpdate']);
            Route::post('/{adAccount}/verify', [AdAccountController::class, 'verifyData']);

            // Aggregated metrics for manager accounts (MCC)
            Route::get('/{adAccount}/aggregated-metrics', function (\App\Models\AdAccount $adAccount, \Illuminate\Http\Request $request) {
                $config = $adAccount->account_config;
                if (is_string($config)) {
                    $config = json_decode($config, true);
                }

                if (!($config['is_manager'] ?? false)) {
                    return response()->json(['error' => 'Not a manager account'], 400);
                }

                // Find child accounts (accounts that have this account as their parent_manager_id)
                $childAccounts = \App\Models\AdAccount::where('integration_id', $adAccount->integration_id)
                    ->where(function ($query) use ($adAccount) {
                        $query->whereJsonContains('account_config->parent_manager_id', $adAccount->external_account_id)
                            ->orWhereJsonContains('account_config->parent_manager_id', (int) $adAccount->external_account_id);
                    })
                    ->get();

                $childAccountIds = $childAccounts->pluck('id');

                // Get date range from request or default to last 30 days
                $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
                $endDate = $request->input('end_date', now()->format('Y-m-d'));

                // Aggregate metrics from child accounts
                $metrics = \App\Models\AdMetric::withoutGlobalScopes()
                    ->whereIn('ad_account_id', $childAccountIds)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->selectRaw('
                        SUM(spend) as total_spend,
                        SUM(impressions) as total_impressions,
                        SUM(clicks) as total_clicks,
                        SUM(conversions) as total_conversions,
                        COUNT(DISTINCT ad_account_id) as accounts_with_data
                    ')
                    ->first();

                // Get per-account breakdown
                $accountBreakdown = \App\Models\AdMetric::withoutGlobalScopes()
                    ->whereIn('ad_account_id', $childAccountIds)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->selectRaw('ad_account_id, SUM(spend) as spend, SUM(impressions) as impressions, SUM(clicks) as clicks')
                    ->groupBy('ad_account_id')
                    ->get()
                    ->map(function ($item) use ($childAccounts) {
                        $account = $childAccounts->firstWhere('id', $item->ad_account_id);
                        return [
                            'id' => $item->ad_account_id,
                            'name' => $account ? $account->account_name : 'Unknown',
                            'spend' => (float) $item->spend,
                            'impressions' => (int) $item->impressions,
                            'clicks' => (int) $item->clicks,
                        ];
                    })
                    ->sortByDesc('spend')
                    ->values();

                return response()->json([
                    'is_manager' => true,
                    'child_account_count' => $childAccounts->count(),
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                    'aggregated' => [
                        'spend' => (float) ($metrics->total_spend ?? 0),
                        'impressions' => (int) ($metrics->total_impressions ?? 0),
                        'clicks' => (int) ($metrics->total_clicks ?? 0),
                        'conversions' => (int) ($metrics->total_conversions ?? 0),
                    ],
                    'child_accounts' => $accountBreakdown,
                ]);
            });
        });

        // Test endpoint
        Route::get('/test', function () {
            return response()->json(['status' => 'ok', 'message' => 'API is working']);
        });
        
        // Benchmark endpoints
        Route::prefix('benchmarks')->group(function () {
            Route::get('/summary', [BenchmarkController::class, 'summary']);
            Route::get('/industries', [BenchmarkController::class, 'industries']);
            Route::get('/industries/{industry}', [BenchmarkController::class, 'industryDetails']);
            Route::get('/industry-benchmarks', [BenchmarkController::class, 'industryBenchmarks']);
            Route::get('/account/{accountId}', [BenchmarkController::class, 'accountBenchmark']);
            Route::get('/insights', [BenchmarkController::class, 'insights']);
        });

        // Industry Overview endpoints
        Route::prefix('industry-overview')->group(function () {
            Route::get('/', [BenchmarkController::class, 'industryOverview']);
        });

        // More benchmark endpoints
        Route::prefix('benchmarks')->group(function () {
            Route::get('/details', [BenchmarkController::class, 'benchmarkDetails']);
            Route::post('/calculate', [BenchmarkController::class, 'calculateResults']);
            Route::get('/sub-industries', [BenchmarkController::class, 'subIndustries']);
            Route::get('/objectives', [BenchmarkController::class, 'objectives']);
            Route::get('/filter-options', [BenchmarkController::class, 'filterOptions']);

            // Real external benchmark endpoints (WordStream, Meta, Google, LinkedIn)
            Route::get('/external', [BenchmarkController::class, 'getExternalBenchmarks']);
            Route::get('/competitive-intelligence', [BenchmarkController::class, 'getCompetitiveIntelligence']);

            // New trending and competitive endpoints
            Route::get('/trending-metrics', function (Request $request) {
                // Mock trending data for now
                return response()->json([
                    'data' => [
                        'your_avg' => 2.15,
                        'your_change' => 3.2,
                        'industry_avg' => 1.95,
                        'industry_change' => 1.8,
                        'top_performers_avg' => 3.45,
                        'audience_breakdown' => [
                            'luxury' => ['avg' => 3.8, 'accounts' => 5],
                            'premium' => ['avg' => 2.6, 'accounts' => 12],
                            'mid_class' => ['avg' => 1.9, 'accounts' => 25],
                            'value' => ['avg' => 1.5, 'accounts' => 18]
                        ]
                    ]
                ]);
            });
            
            Route::get('/competitive-analysis', function (Request $request) {
                // Mock competitive analysis data
                return response()->json([
                    'data' => [
                        'market_rank' => 8,
                        'percentile' => 65,
                        'total_competitors' => 45,
                        'opportunity_score' => 78,
                        'insights' => [
                            [
                                'type' => 'opportunity',
                                'title' => 'CTR Improvement Potential',
                                'description' => 'Your CTR is 15% below top performers in your industry. Focus on ad creative optimization.',
                                'impact_level' => 'High'
                            ],
                            [
                                'type' => 'strength',
                                'title' => 'Cost Efficiency Leader',
                                'description' => 'Your CPC is 22% lower than industry average, showing excellent bidding optimization.',
                                'impact_level' => 'Medium'
                            ]
                        ]
                    ]
                ]);
            });
        });
        
        // Ad Campaigns endpoints
        Route::prefix('ad-campaigns')->group(function () {
            Route::get('/', function (Request $request) {
                $query = \App\Models\AdCampaign::with('adAccount');

                if ($request->account_id) {
                    $query->where('ad_account_id', $request->account_id);
                }

                $campaigns = $query->get();

                // Get date filter parameters
                $startDate = $request->start_date;
                $endDate = $request->end_date;

                // Add total_spend for each campaign (spend stored in original currency)
                $currencyService = app(\App\Services\CurrencyConversionService::class);
                $campaigns->transform(function ($campaign) use ($currencyService, $startDate, $endDate) {
                    // Build metrics query with optional date filter
                    $metricsQuery = \App\Models\AdMetric::where('ad_campaign_id', $campaign->id);

                    if ($startDate && $endDate) {
                        $metricsQuery->whereBetween('date', [$startDate, $endDate]);
                    }

                    $totalSpend = $metricsQuery->sum('spend');

                    // Get account currency
                    $accountCurrency = $campaign->adAccount->account_config['currency']
                        ?? $campaign->adAccount->currency
                        ?? 'USD';

                    $campaign->total_spend = round($totalSpend, 2); // Original currency
                    $campaign->total_spend_sar = round($currencyService->convertToSAR($totalSpend, $accountCurrency), 2);
                    $campaign->currency = $accountCurrency;

                    return $campaign;
                });

                return response()->json([
                    'data' => $campaigns,
                    'date_filter' => $startDate && $endDate ? ['start' => $startDate, 'end' => $endDate] : null,
                ]);
            });
            
            Route::put('/{campaign}', function (Request $request, \App\Models\AdCampaign $campaign) {
                try {
                    $request->validate([
                        'objective' => 'nullable|string|max:255',
                        'sub_industry' => 'nullable|string|max:255',
                        'funnel_stage' => 'nullable|in:TOF,MOF,BOF',
                        'user_journey' => 'nullable|in:instant_form,landing_page',
                        'has_pixel_data' => 'nullable|boolean',
                        'target_segment' => 'nullable|in:luxury,premium,mid_class,value,mass_market,niche',
                        'age_group' => 'nullable|in:gen_z,millennials,gen_x,boomers,mixed_age',
                        'geo_targeting' => 'nullable|in:local,regional,national,international',
                        'messaging_tone' => 'nullable|in:professional,casual,luxury,urgent,educational,emotional',
                    ]);

                    $updateData = [];
                    
                    if ($request->has('objective')) {
                        $updateData['objective'] = $request->objective;
                    }
                    
                    if ($request->has('sub_industry')) {
                        $updateData['sub_industry'] = $request->sub_industry;
                    }
                    
                    if ($request->has('funnel_stage')) {
                        $updateData['funnel_stage'] = $request->funnel_stage;
                    }
                    
                    if ($request->has('user_journey')) {
                        $updateData['user_journey'] = $request->user_journey;
                    }
                    
                    if ($request->has('has_pixel_data')) {
                        $updateData['has_pixel_data'] = $request->has_pixel_data;
                    }
                    
                    if ($request->has('target_segment')) {
                        $updateData['target_segment'] = $request->target_segment;
                    }
                    
                    if ($request->has('age_group')) {
                        $updateData['age_group'] = $request->age_group;
                    }
                    
                    if ($request->has('geo_targeting')) {
                        $updateData['geo_targeting'] = $request->geo_targeting;
                    }
                    
                    if ($request->has('messaging_tone')) {
                        $updateData['messaging_tone'] = $request->messaging_tone;
                    }

                    $campaign->update($updateData);

                    return response()->json([
                        'message' => 'Campaign updated successfully',
                        'data' => [
                            'id' => $campaign->id,
                            'name' => $campaign->name,
                            'objective' => $campaign->objective,
                            'sub_industry' => $campaign->sub_industry,
                            'funnel_stage' => $campaign->funnel_stage,
                            'user_journey' => $campaign->user_journey,
                            'has_pixel_data' => $campaign->has_pixel_data,
                            'target_segment' => $campaign->target_segment,
                            'age_group' => $campaign->age_group,
                            'geo_targeting' => $campaign->geo_targeting,
                            'messaging_tone' => $campaign->messaging_tone,
                        ],
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error updating campaign', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'error' => 'Failed to update campaign',
                        'message' => $e->getMessage(),
                    ], 500);
                }
            });
            
            Route::get('/{campaign}', function (\App\Models\AdCampaign $campaign) {
                return response()->json([
                    'data' => $campaign->load('adAccount'),
                ]);
            });
            
            Route::get('/{campaign}/metrics', function (Request $request, \App\Models\AdCampaign $campaign) {
                try {
                    // Check for direct start_date/end_date params first
                    if ($request->has('start_date') && $request->has('end_date')) {
                        $fromDate = $request->get('start_date');
                        $toDate = $request->get('end_date');
                    } else {
                        // Default to ALL TIME if no dates specified (campaigns may have old data)
                        $dateRange = $request->get('date_range', 'all');

                        // Parse date range
                        if ($dateRange === 'all') {
                            // All time - use earliest possible date
                            $fromDate = '2020-01-01';
                            $toDate = now()->format('Y-m-d');
                        } elseif (str_ends_with($dateRange, 'd')) {
                            $days = intval(rtrim($dateRange, 'd'));
                            $fromDate = now()->subDays($days)->format('Y-m-d');
                            $toDate = now()->format('Y-m-d');
                        } elseif (str_ends_with($dateRange, 'm')) {
                            $months = intval(rtrim($dateRange, 'm'));
                            $fromDate = now()->subMonths($months)->format('Y-m-d');
                            $toDate = now()->format('Y-m-d');
                        } elseif (str_ends_with($dateRange, 'y')) {
                            $years = intval(rtrim($dateRange, 'y'));
                            $fromDate = now()->subYears($years)->format('Y-m-d');
                            $toDate = now()->format('Y-m-d');
                        } else {
                            // Custom date range format: "YYYY-MM-DD to YYYY-MM-DD"
                            $dates = explode(' to ', $dateRange);
                            $fromDate = $dates[0] ?? now()->subDays(30)->format('Y-m-d');
                            $toDate = $dates[1] ?? now()->format('Y-m-d');
                        }
                    }

                    // Load campaign with account relationship to get currency
                    $campaign->load('adAccount');

                    // Get account currency
                    $accountCurrency = $campaign->adAccount->account_config['currency'] ?? $campaign->adAccount->currency ?? 'USD';
                    $currencyService = app(\App\Services\CurrencyConversionService::class);

                    // Get metrics for this campaign
                    $metrics = \App\Models\AdMetric::where('ad_campaign_id', $campaign->id)
                        ->whereBetween('date', [$fromDate, $toDate])
                        ->selectRaw('
                            SUM(impressions) as impressions,
                            SUM(clicks) as clicks,
                            SUM(spend) as spend,
                            SUM(conversions) as conversions,
                            SUM(leads) as leads,
                            SUM(calls) as calls,
                            SUM(purchases) as purchases,
                            SUM(revenue) as revenue,
                            SUM(reach) as reach,
                            SUM(video_views) as video_views
                        ')
                        ->first();

                    // Get daily breakdown
                    $dailyMetrics = \App\Models\AdMetric::where('ad_campaign_id', $campaign->id)
                        ->whereBetween('date', [$fromDate, $toDate])
                        ->selectRaw('
                            date,
                            SUM(impressions) as impressions,
                            SUM(clicks) as clicks,
                            SUM(spend) as spend,
                            SUM(conversions) as conversions,
                            SUM(leads) as leads,
                            SUM(calls) as calls,
                            SUM(purchases) as purchases,
                            SUM(revenue) as revenue
                        ')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();

                    // Handle case where no metrics found
                    if (!$metrics) {
                        $metrics = (object) [
                            'impressions' => 0,
                            'clicks' => 0,
                            'spend' => 0,
                            'conversions' => 0,
                            'leads' => 0,
                            'calls' => 0,
                            'purchases' => 0,
                            'revenue' => 0,
                            'reach' => 0,
                            'video_views' => 0,
                        ];
                    }

                    // Convert spend and revenue to SAR
                    $spendSAR = $currencyService->convertToSAR((float) $metrics->spend, $accountCurrency);
                    $revenueSAR = $currencyService->convertToSAR((float) $metrics->revenue, $accountCurrency);

                    // Calculate derived metrics using converted spend
                    $ctr = $metrics->impressions > 0 ? ($metrics->clicks / $metrics->impressions) * 100 : 0;
                    $cpc = $metrics->clicks > 0 ? $spendSAR / $metrics->clicks : 0;
                    $cpm = $metrics->impressions > 0 ? ($spendSAR / $metrics->impressions) * 1000 : 0;
                    $cvr = $metrics->clicks > 0 ? ($metrics->conversions / $metrics->clicks) * 100 : 0;
                    $roas = $spendSAR > 0 ? $revenueSAR / $spendSAR : 0;

                    return response()->json([
                        'data' => [
                            'impressions' => (int) $metrics->impressions,
                            'clicks' => (int) $metrics->clicks,
                            'spend' => $spendSAR,
                            'conversions' => (int) $metrics->conversions,
                            'leads' => (int) $metrics->leads,
                            'calls' => (int) $metrics->calls,
                            'purchases' => (int) $metrics->purchases,
                            'revenue' => $revenueSAR,
                            'reach' => (int) $metrics->reach,
                            'video_views' => (int) $metrics->video_views,
                            'ctr' => round($ctr, 2),
                            'cpc' => round($cpc, 2),
                            'cpm' => round($cpm, 2),
                            'cvr' => round($cvr, 2),
                            'roas' => round($roas, 2),
                            'currency' => 'SAR',
                            'original_currency' => $accountCurrency,
                        ],
                        'daily_data' => $dailyMetrics->map(function($day) use ($currencyService, $accountCurrency) {
                            // Convert daily spend and revenue to SAR
                            $dailySpendSAR = $currencyService->convertToSAR((float) $day->spend, $accountCurrency);
                            $dailyRevenueSAR = $currencyService->convertToSAR((float) $day->revenue, $accountCurrency);

                            $dailyCtr = $day->impressions > 0 ? ($day->clicks / $day->impressions) * 100 : 0;
                            $dailyCpc = $day->clicks > 0 ? $dailySpendSAR / $day->clicks : 0;
                            $dailyCpm = $day->impressions > 0 ? ($dailySpendSAR / $day->impressions) * 1000 : 0;

                            return [
                                'date' => $day->date,
                                'impressions' => (int) $day->impressions,
                                'clicks' => (int) $day->clicks,
                                'spend' => $dailySpendSAR,
                                'conversions' => (int) $day->conversions,
                                'leads' => (int) $day->leads,
                                'calls' => (int) $day->calls,
                                'purchases' => (int) $day->purchases,
                                'revenue' => $dailyRevenueSAR,
                                'ctr' => round($dailyCtr, 2),
                                'cpc' => round($dailyCpc, 2),
                                'cpm' => round($dailyCpm, 2),
                            ];
                        }),
                        'date_range' => [
                            'from' => $fromDate,
                            'to' => $toDate,
                        ],
                    ]);

                } catch (\Exception $e) {
                    \Log::error('Error loading campaign metrics', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'error' => 'Failed to load campaign metrics',
                        'message' => $e->getMessage(),
                    ], 500);
                }
            });
        });
        
        // Integrations endpoints
        Route::prefix('integrations')->group(function () {
            Route::get('/', function (Request $request) {
                // Super admins without tenant see ALL integrations, others see tenant-specific
                $query = \App\Models\Integration::with(['adAccounts']);
                if ($request->current_tenant) {
                    $query->where('tenant_id', $request->current_tenant->id);
                }

                $integrations = $query->get()
                    ->map(function ($integration) {
                        $data = $integration->toArray();
                        
                        // Add accounts count
                        $data['accounts_count'] = $integration->adAccounts->count();
                        
                        // Add Facebook-specific data if available
                        if ($integration->platform === 'facebook' && isset($integration->app_config['facebook_user_name'])) {
                            $data['user_name'] = $integration->app_config['facebook_user_name'];
                            $data['user_email'] = $integration->app_config['facebook_user_email'] ?? null;
                            $data['connected_at'] = $integration->app_config['connected_at'] ?? null;
                            $data['token_expires_at'] = $integration->app_config['token_expires_at'] ?? null;
                        }
                        
                        // Add account details
                        $data['accounts'] = $integration->adAccounts->map(function ($account) {
                            return [
                                'id' => $account->id,
                                'name' => $account->account_name,
                                'external_id' => $account->external_account_id,
                                'status' => $account->status,
                                'currency' => $account->account_config['currency'] ?? 'USD',
                            ];
                        });
                        
                        return $data;
                    });
                
                return response()->json([
                    'data' => $integrations,
                ]);
            });
            
            Route::post('/', function (Request $request) {
                $request->validate([
                    'platform' => 'required|in:facebook,google,tiktok',
                    'app_config' => 'required|array',
                ]);

                // Require tenant for creating integrations
                if (!$request->current_tenant) {
                    return response()->json(['error' => 'Tenant selection required to create integrations'], 400);
                }

                $integration = \App\Models\Integration::create([
                    'tenant_id' => $request->current_tenant->id,
                    'user_id' => $request->user()->id,
                    'platform' => $request->platform,
                    'app_config' => $request->app_config,
                    'created_by' => $request->user()->id,
                ]);

                return response()->json($integration, 201);
            });

            // Token status check - must be before /{integration} routes
            Route::get('/token-status', function (Request $request) {
                try {
                    $query = \App\Models\Integration::where('status', 'active');
                    if ($request->current_tenant) {
                        $query->where('tenant_id', $request->current_tenant->id);
                    }
                    $integrations = $query->get();

                    $results = [];
                    foreach ($integrations as $integration) {
                        $config = $integration->app_config;
                        $expiresAt = $config['token_expires_at'] ?? $config['expires_at'] ?? null;

                        $status = 'valid';
                        $daysUntilExpiry = null;
                        $hoursUntilExpiry = null;

                        if ($expiresAt) {
                            $expiresTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
                            $secondsUntilExpiry = $expiresTimestamp - time();
                            $daysUntilExpiry = $secondsUntilExpiry / 86400;
                            $hoursUntilExpiry = $secondsUntilExpiry / 3600;

                            if ($secondsUntilExpiry <= 0) {
                                $status = 'expired';
                            } elseif ($daysUntilExpiry <= 1) {
                                // Less than 1 day - expiring soon (for short-lived tokens like Snapchat)
                                $status = 'expiring_soon';
                            } elseif ($daysUntilExpiry <= 7) {
                                $status = 'expiring_soon';
                            }
                        } else {
                            // No expiration info - check if we can test the connection
                            $status = 'unknown';
                        }

                        $results[] = [
                            'id' => $integration->id,
                            'platform' => $integration->platform,
                            'status' => $status,
                            'expires_at' => $expiresAt,
                            'days_until_expiry' => $daysUntilExpiry !== null ? round($daysUntilExpiry, 1) : null,
                            'hours_until_expiry' => $hoursUntilExpiry !== null ? round($hoursUntilExpiry, 1) : null,
                        ];
                    }

                    // Only flag as needing reconnection if actually expired (not just expiring soon for short-lived tokens)
                    $needsReconnection = collect($results)->filter(function ($item) {
                        // Expired tokens always need reconnection
                        if ($item['status'] === 'expired') return true;
                        // Expiring soon with less than 1 hour left needs reconnection
                        if ($item['status'] === 'expiring_soon' && $item['hours_until_expiry'] !== null && $item['hours_until_expiry'] < 1) return true;
                        // Expiring soon with more than 1 day but less than 7 days - show warning but don't require immediate action
                        if ($item['status'] === 'expiring_soon' && $item['days_until_expiry'] !== null && $item['days_until_expiry'] < 1) return true;
                        return false;
                    })->count();

                    return response()->json([
                        'data' => $results,
                        'needs_reconnection' => $needsReconnection,
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to check token status',
                        'message' => $e->getMessage(),
                    ], 500);
                }
            });

            Route::post('/{integration}/test', function (\App\Models\Integration $integration) {
                $startTime = microtime(true);
                
                try {
                    // Test integration based on platform
                    if ($integration->platform === 'facebook') {
                        $controller = app(FacebookIntegrationController::class);
                        $result = $controller->testConnection($integration);
                        
                        // Add timing and health info
                        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                        $data = $result->getData(true);
                        
                        return response()->json([
                            'status' => $data['status'] ?? 'success',
                            'message' => $data['message'] ?? 'Integration test successful',
                            'accounts_found' => $data['accounts_found'] ?? 0,
                            'user_name' => $data['user_name'] ?? null,
                            'response_time_ms' => $responseTime,
                            'health_score' => $responseTime < 2000 ? 100 : ($responseTime < 5000 ? 80 : 60),
                            'last_check' => now()->toISOString(),
                        ]);
                    }
                    
                    // Enhanced fallback for other platforms
                    $accountsCount = $integration->adAccounts()->count();
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Integration test successful',
                        'accounts_found' => $accountsCount,
                        'response_time_ms' => $responseTime,
                        'health_score' => 95,
                        'last_check' => now()->toISOString(),
                    ]);
                    
                } catch (\Exception $e) {
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Connection test failed: ' . $e->getMessage(),
                        'accounts_found' => 0,
                        'response_time_ms' => $responseTime,
                        'health_score' => 0,
                        'last_check' => now()->toISOString(),
                        'issues' => [$e->getMessage()],
                    ], 400);
                }
            });
            
            Route::post('/{integration}/sync-accounts', function (\App\Models\Integration $integration) {
                // Sync accounts from app_config to database for Facebook
                if ($integration->platform === 'facebook' && isset($integration->app_config['ad_accounts'])) {
                    $synced = 0;
                    foreach ($integration->app_config['ad_accounts'] as $adAccount) {
                        // Auto-detect industry based on account name
                        $detectedIndustry = \App\Services\IndustryDetector::detectIndustry($adAccount['name']);
                        
                        \App\Models\AdAccount::updateOrCreate(
                            [
                                'integration_id' => $integration->id,
                                'external_account_id' => $adAccount['id'],
                            ],
                            [
                                'account_name' => $adAccount['name'],
                                'status' => $adAccount['account_status'] === 1 ? 'active' : 'inactive',
                                'account_config' => ['currency' => $adAccount['currency'] ?? 'USD'],
                                'industry' => $detectedIndustry,
                                'tenant_id' => $integration->tenant_id,
                            ]
                        );
                        $synced++;
                    }
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => "Synced {$synced} accounts to database",
                        'accounts_synced' => $synced,
                    ]);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'No accounts to sync or unsupported platform',
                ], 400);
            });

            Route::get('/health', function (Request $request) {
                try {
                    // Super admins without tenant see ALL active integrations
                    $query = \App\Models\Integration::where('status', 'active')
                        ->with(['adAccounts']);
                    if ($request->current_tenant) {
                        $query->where('tenant_id', $request->current_tenant->id);
                    }
                    $integrations = $query->get();
                    
                    $healthData = [];
                    $overallHealth = 'healthy';
                    $totalIssues = 0;
                    
                    foreach ($integrations as $integration) {
                        $startTime = microtime(true);
                        $issues = [];
                        $status = 'healthy';
                        
                        try {
                            // Check token expiration for Facebook
                            if ($integration->platform === 'facebook' && isset($integration->app_config['token_expires_at'])) {
                                $expiresAt = \Carbon\Carbon::parse($integration->app_config['token_expires_at']);
                                $daysUntilExpiry = now()->diffInDays($expiresAt, false);
                                
                                if ($daysUntilExpiry < 0) {
                                    $issues[] = 'Access token has expired';
                                    $status = 'error';
                                } elseif ($daysUntilExpiry < 7) {
                                    $issues[] = "Access token expires in {$daysUntilExpiry} days";
                                    $status = 'warning';
                                }
                            }
                            
                            // Check account count
                            $accountsCount = $integration->adAccounts()->count();
                            if ($accountsCount === 0) {
                                $issues[] = 'No ad accounts found';
                                $status = $status === 'error' ? 'error' : 'warning';
                            }
                            
                            // Check last sync time
                            if (isset($integration->app_config['last_sync'])) {
                                $lastSync = \Carbon\Carbon::parse($integration->app_config['last_sync']);
                                $hoursSinceLastSync = now()->diffInHours($lastSync);
                                
                                if ($hoursSinceLastSync > 24) {
                                    $issues[] = "Last sync was {$hoursSinceLastSync} hours ago";
                                    if ($status === 'healthy') $status = 'warning';
                                }
                            }
                            
                        } catch (\Exception $e) {
                            $issues[] = 'Health check failed: ' . $e->getMessage();
                            $status = 'error';
                        }
                        
                        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                        
                        $healthData[$integration->id] = [
                            'integration_id' => $integration->id,
                            'platform' => $integration->platform,
                            'status' => $status,
                            'last_check' => now()->toISOString(),
                            'response_time' => $responseTime,
                            'issues' => $issues,
                            'accounts_count' => $integration->adAccounts->count(),
                            'user_name' => $integration->app_config['facebook_user_name'] ?? null,
                        ];
                        
                        if ($status === 'error') $overallHealth = 'error';
                        elseif ($status === 'warning' && $overallHealth === 'healthy') $overallHealth = 'warning';
                        
                        $totalIssues += count($issues);
                    }
                    
                    return response()->json([
                        'overall_status' => $overallHealth,
                        'total_integrations' => $integrations->count(),
                        'total_issues' => $totalIssues,
                        'integrations' => $healthData,
                        'checked_at' => now()->toISOString(),
                    ]);
                    
                } catch (\Exception $e) {
                    return response()->json([
                        'overall_status' => 'error',
                        'message' => 'Health check failed: ' . $e->getMessage(),
                        'checked_at' => now()->toISOString(),
                    ], 500);
                }
            });
            
            Route::post('/{integration}/refresh-token', function (\App\Models\Integration $integration) {
                if ($integration->platform === 'facebook') {
                    $controller = app(FacebookIntegrationController::class);
                    return $controller->refreshToken($integration);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token refresh not supported for this platform',
                ], 400);
            });
            
            Route::post('/{integration}/refresh-accounts', function (\App\Models\Integration $integration) {
                if ($integration->platform === 'facebook') {
                    $controller = app(FacebookIntegrationController::class);
                    return $controller->refreshAllAccounts($integration);
                }
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account refresh not supported for this platform',
                ], 400);
            });
            
            Route::post('/{integration}/sync-campaigns', function (\App\Models\Integration $integration, Request $request) {
                if ($integration->platform === 'facebook') {
                    $controller = app(FacebookIntegrationController::class);
                    return $controller->syncCampaigns($integration, $request->ad_account_id);
                }

                if ($integration->platform === 'linkedin') {
                    $adAccount = \App\Models\AdAccount::find($request->ad_account_id);
                    if (!$adAccount || $adAccount->integration_id !== $integration->id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Ad account not found or does not belong to this integration',
                        ], 400);
                    }

                    $service = app(\App\Services\LinkedInAdsService::class);
                    $linkedinLevel = $request->input('linkedin_level', 'ad_sets');

                    // Sync based on selected level
                    switch ($linkedinLevel) {
                        case 'campaign_groups':
                            $result = $service->syncCampaignGroups($integration, $adAccount);
                            $levelName = 'campaign groups';
                            break;
                        case 'ads':
                            $result = $service->syncCreatives($integration, $adAccount);
                            $levelName = 'ads';
                            break;
                        case 'ad_sets':
                        default:
                            $result = $service->syncCampaigns($integration, $adAccount);
                            $levelName = 'ad sets';
                            break;
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => "LinkedIn {$levelName} synced successfully",
                        'data' => $result,
                    ]);
                }

                if ($integration->platform === 'google') {
                    $adAccount = \App\Models\AdAccount::find($request->ad_account_id);
                    if (!$adAccount || $adAccount->integration_id !== $integration->id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Ad account not found or does not belong to this integration',
                        ], 400);
                    }

                    // Skip campaign sync for manager accounts (MCC) - they don't have campaigns
                    $config = $adAccount->account_config;
                    if (is_string($config)) {
                        $config = json_decode($config, true);
                    }
                    if ($config['is_manager'] ?? false) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Manager accounts do not have campaigns - use child accounts to view campaigns',
                            'data' => [],
                            'is_manager' => true,
                        ]);
                    }

                    $service = app(\App\Services\GoogleAdsService::class);
                    $googleLevel = $request->input('google_level', 'campaigns');

                    // Sync based on selected level
                    switch ($googleLevel) {
                        case 'ad_groups':
                            $result = $service->syncAdGroups($integration, $adAccount);
                            $levelName = 'ad groups';
                            break;
                        case 'ads':
                            $result = $service->syncAds($integration, $adAccount);
                            $levelName = 'ads';
                            break;
                        case 'campaigns':
                        default:
                            $result = $service->syncCampaigns($integration, $adAccount);
                            $levelName = 'campaigns';
                            break;
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => "Google Ads {$levelName} synced successfully",
                        'data' => $result,
                    ]);
                }

                if ($integration->platform === 'snapchat') {
                    $adAccount = \App\Models\AdAccount::find($request->ad_account_id);
                    if (!$adAccount || $adAccount->integration_id !== $integration->id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Ad account not found or does not belong to this integration',
                        ], 400);
                    }

                    // Call the Snapchat sync campaigns artisan command
                    $exitCode = \Artisan::call('snapchat:sync-campaigns', [
                        'integration_id' => $integration->id,
                    ]);

                    // Get output from artisan command
                    $output = \Artisan::output();

                    // Fetch the updated campaigns for this ad account
                    $campaigns = $adAccount->adCampaigns()
                        ->select(['id', 'external_campaign_id', 'name', 'status', 'objective'])
                        ->orderBy('name')
                        ->get();

                    return response()->json([
                        'status' => $exitCode === 0 ? 'success' : 'error',
                        'message' => $exitCode === 0
                            ? "Snapchat campaigns synced successfully"
                            : "Snapchat campaign sync completed with warnings",
                        'data' => $campaigns,
                        'campaigns_count' => $campaigns->count(),
                    ]);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Campaign sync not supported for this platform',
                ], 400);
            });

            // Sync metrics with date range support
            Route::post('/{integration}/sync-metrics', function (\App\Models\Integration $integration, Request $request) {
                // Remove PHP timeout limit for long-running syncs
                set_time_limit(0);

                $adAccountId = $request->input('ad_account_id');
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                $allTime = $request->input('all_time', false);
                $background = $request->input('background', true); // Default to background

                if (!$adAccountId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Missing required parameter: ad_account_id',
                    ], 400);
                }

                // If not all_time, require start_date and end_date
                if (!$allTime && (!$startDate || !$endDate)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Missing required parameters: start_date, end_date (or use all_time=true)',
                    ], 400);
                }

                $adAccount = \App\Models\AdAccount::find($adAccountId);
                if (!$adAccount || $adAccount->integration_id !== $integration->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ad account not found or does not belong to this integration',
                    ], 404);
                }

                try {
                    $platform = $integration->platform;
                    $adjustedStartDate = $startDate;
                    $dateWarning = null;

                    // Apply platform-specific date limits
                    if (!$allTime && $startDate) {
                        $startCarbon = \Carbon\Carbon::parse($startDate);

                        if ($platform === 'facebook') {
                            // Facebook has 37-month limit
                            $maxStartDate = \Carbon\Carbon::now()->subMonths(36);
                            if ($startCarbon->lt($maxStartDate)) {
                                $adjustedStartDate = $maxStartDate->format('Y-m-d');
                                $dateWarning = "Start date adjusted to {$adjustedStartDate} (Facebook 37-month limit)";
                            }
                        }
                    }

                    // Build the artisan command
                    $artisanPath = base_path('artisan');
                    $logFile = storage_path('logs/sync-metrics-' . $adAccountId . '-' . date('Y-m-d-His') . '.log');
                    $command = '';

                    if ($platform === 'tiktok') {
                        // Use sync-metrics with --all for dynamic date detection
                        $command = "php {$artisanPath} tiktok:sync-metrics {$integration->id} --account-id={$adAccount->id}";
                        if ($allTime) {
                            $command .= " --all";
                        } else {
                            $command .= " --start-date={$adjustedStartDate} --end-date={$endDate}";
                        }
                    } elseif ($platform === 'facebook') {
                        $command = "php {$artisanPath} facebook:sync-metrics {$integration->id} --account-id={$adAccountId}";
                        if ($allTime) {
                            $command .= " --all";
                        } else {
                            $command .= " --start-date={$adjustedStartDate} --end-date={$endDate}";
                        }
                    } elseif ($platform === 'google') {
                        $command = "php {$artisanPath} google-ads:sync {$integration->id} --metrics";
                        if ($allTime) {
                            $command .= " --all";
                        } else {
                            $command .= " --start-date={$adjustedStartDate} --end-date={$endDate}";
                        }
                    } elseif ($platform === 'snapchat') {
                        $command = "php {$artisanPath} snapchat:sync-metrics --account-id={$adAccountId}";
                        if ($allTime) {
                            $command .= " --all";
                        } else {
                            $command .= " --start-date={$adjustedStartDate} --end-date={$endDate}";
                        }
                    } elseif ($platform === 'linkedin') {
                        $command = "php {$artisanPath} linkedin:backfill-metrics --account-id={$adAccountId}";
                        if ($allTime) {
                            $command .= " --full-history";
                        } else {
                            $command .= " --start-date={$adjustedStartDate} --end-date={$endDate}";
                        }
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Metrics sync not supported for this platform',
                        ], 400);
                    }

                    // Run in background
                    if ($background) {
                        // Run the command in the background
                        $command .= " > {$logFile} 2>&1 &";
                        exec($command, $output, $returnCode);

                        \Log::info('Metrics sync started in background', [
                            'integration_id' => $integration->id,
                            'ad_account_id' => $adAccountId,
                            'platform' => $platform,
                            'command' => $command,
                            'return_code' => $returnCode,
                            'log_file' => $logFile,
                            'timestamp' => now()->toISOString(),
                        ]);

                        // Verify process started by checking if log file was created
                        usleep(500000); // 0.5 seconds to let process start
                        if (!file_exists($logFile)) {
                            \Log::error('Background sync process failed to start', [
                                'log_file' => $logFile,
                                'return_code' => $returnCode,
                            ]);
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Failed to start background sync process. Please try again.',
                            ], 500);
                        }

                        $response = [
                            'status' => 'started',
                            'message' => "Metrics sync started in background for {$adAccount->account_name}. This may take several minutes.",
                            'background' => true,
                            'log_file' => basename($logFile),
                        ];

                        if ($dateWarning) {
                            $response['warning'] = $dateWarning;
                        }

                        return response()->json($response);
                    }

                    // Synchronous execution (not recommended for large syncs)
                    $metricsCount = 0;
                    if ($platform === 'tiktok') {
                        $params = [
                            'integration_id' => $integration->id,
                            '--account-id' => $adAccount->id,
                        ];
                        if ($allTime) {
                            $params['--all'] = true;
                        } else {
                            $params['--start-date'] = $adjustedStartDate;
                            $params['--end-date'] = $endDate;
                        }
                        $exitCode = \Artisan::call('tiktok:sync-metrics', $params);
                        $output = \Artisan::output();
                        if (preg_match('/Total metrics synced: (\d+)/', $output, $matches)) {
                            $metricsCount = intval($matches[1]);
                        }
                    } elseif ($platform === 'facebook') {
                        $params = [
                            'integration_id' => $integration->id,
                            '--account-id' => $adAccountId,
                        ];
                        if ($allTime) {
                            $params['--all'] = true;
                        } else {
                            $params['--start-date'] = $adjustedStartDate;
                            $params['--end-date'] = $endDate;
                        }
                        $exitCode = \Artisan::call('facebook:sync-metrics', $params);
                        $output = \Artisan::output();
                        if (preg_match('/Successfully synced (\d+) metric records/', $output, $matches)) {
                            $metricsCount = intval($matches[1]);
                        }
                    } elseif ($platform === 'google') {
                        $params = ['--metrics' => true];
                        if ($allTime) {
                            $params['--all'] = true;
                        } else {
                            $params['--start-date'] = $adjustedStartDate;
                            $params['--end-date'] = $endDate;
                        }
                        $exitCode = \Artisan::call('google-ads:sync', $params);
                        $output = \Artisan::output();
                        if (preg_match('/(\d+) metrics/', $output, $matches)) {
                            $metricsCount = intval($matches[1]);
                        }
                    } elseif ($platform === 'snapchat') {
                        $params = ['--account-id' => $adAccountId];
                        if ($allTime) {
                            $params['--all'] = true;
                        } else {
                            $params['--start-date'] = $adjustedStartDate;
                            $params['--end-date'] = $endDate;
                        }
                        $exitCode = \Artisan::call('snapchat:sync-metrics', $params);
                        $output = \Artisan::output();
                        if (preg_match('/(\d+) metric/', $output, $matches)) {
                            $metricsCount = intval($matches[1]);
                        }
                    } elseif ($platform === 'linkedin') {
                        $params = ['--account-id' => $adAccountId];
                        if ($allTime) {
                            $params['--full-history'] = true;
                        } else {
                            $params['--start-date'] = $adjustedStartDate;
                            $params['--end-date'] = $endDate;
                        }
                        $exitCode = \Artisan::call('linkedin:backfill-metrics', $params);
                        $output = \Artisan::output();
                        if (preg_match('/Metrics Created: (\d+)/', $output, $matches)) {
                            $metricsCount = intval($matches[1]);
                        }
                    }

                    // Update last sync timestamp
                    $adAccount->last_metrics_sync_at = now();
                    $adAccount->save();

                    $response = [
                        'status' => 'success',
                        'message' => "Metrics sync completed. Synced {$metricsCount} records.",
                        'metrics_count' => $metricsCount,
                        'last_sync_at' => $adAccount->last_metrics_sync_at->toISOString(),
                    ];

                    if ($dateWarning) {
                        $response['warning'] = $dateWarning;
                    }

                    return response()->json($response);
                } catch (\Exception $e) {
                    \Log::error('Metrics sync failed', [
                        'integration_id' => $integration->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to sync metrics: ' . $e->getMessage(),
                    ], 500);
                }
            });

            Route::delete('/{integration}', function (\App\Models\Integration $integration) {
                // Check if the integration belongs to the current tenant (super admins can delete any)
                if (request()->current_tenant && $integration->tenant_id !== request()->current_tenant->id) {
                    return response()->json([
                        'error' => 'Unauthorized',
                    ], 403);
                }
                
                // Delete all associated ad accounts
                $integration->adAccounts()->delete();
                
                // Delete the integration
                $integration->delete();
                
                return response()->json([
                    'message' => 'Integration deleted successfully',
                ]);
            });
        });
        
        // Facebook OAuth endpoints
        Route::prefix('facebook')->group(function () {
            Route::post('/oauth/initiate', [FacebookIntegrationController::class, 'initiateOAuth']);
        });

        // Google Ads Integration endpoints
        Route::prefix('google-ads')->group(function () {
            Route::post('/oauth/redirect', [GoogleAdsIntegrationController::class, 'redirect']);
            Route::get('/auth-url', [GoogleAdsIntegrationController::class, 'getAuthUrl']);
            Route::post('/disconnect', [GoogleAdsIntegrationController::class, 'disconnect']);
            Route::get('/status', [GoogleAdsIntegrationController::class, 'status']);
            Route::get('/accounts', [GoogleAdsIntegrationController::class, 'accounts']);
            Route::post('/sync-accounts', [GoogleAdsIntegrationController::class, 'syncAccounts']);
            Route::post('/test', [GoogleAdsIntegrationController::class, 'testConnection']);
        });

        // Google Ads OAuth initiation endpoint - matches what frontend expects
        Route::get('/auth/google-ads/initiate', [GoogleAdsIntegrationController::class, 'getAuthUrl']);

        // TikTok Ads Integration endpoints
        Route::prefix('tiktok')->group(function () {
            Route::post('/oauth/redirect', [TikTokIntegrationController::class, 'redirect']);
            Route::post('/disconnect', [TikTokIntegrationController::class, 'disconnect']);
            Route::get('/status', [TikTokIntegrationController::class, 'status']);
            Route::get('/accounts', [TikTokIntegrationController::class, 'accounts']);
            Route::post('/sync-accounts', [TikTokIntegrationController::class, 'syncAccounts']);
        });

        // Snapchat Ads Integration endpoints
        Route::prefix('snapchat')->group(function () {
            Route::post('/oauth/redirect', [SnapchatIntegrationController::class, 'redirect']);
            Route::post('/disconnect', [SnapchatIntegrationController::class, 'disconnect']);
            Route::get('/status', [SnapchatIntegrationController::class, 'status']);
            Route::get('/accounts', [SnapchatIntegrationController::class, 'accounts']);
            Route::post('/sync-accounts', [SnapchatIntegrationController::class, 'syncAccounts']);

            // Legacy endpoints for backward compatibility
            Route::get('/oauth/redirect-legacy', [App\Http\Controllers\SnapchatOAuthController::class, 'redirect']);
            Route::get('/status-legacy', [App\Http\Controllers\SnapchatOAuthController::class, 'status']);
            Route::post('/disconnect-legacy', [App\Http\Controllers\SnapchatOAuthController::class, 'disconnect']);
            Route::post('/refresh-token', [App\Http\Controllers\SnapchatOAuthController::class, 'refreshToken']);
        });

        // LinkedIn Ads Integration endpoints
        Route::prefix('linkedin')->group(function () {
            Route::post('/oauth/initiate', [LinkedInIntegrationController::class, 'initiateOAuth']);
            Route::get('/status', [LinkedInIntegrationController::class, 'status']);
            Route::post('/sync', [LinkedInIntegrationController::class, 'sync']);
            Route::post('/disconnect', [LinkedInIntegrationController::class, 'disconnect']);
            Route::post('/test-connection', [LinkedInIntegrationController::class, 'testConnection']);
        });

        // Twitter/X Ads Integration endpoints
        Route::prefix('twitter')->group(function () {
            Route::post('/oauth/initiate', [TwitterIntegrationController::class, 'initiateOAuth']);
            Route::get('/status', [TwitterIntegrationController::class, 'status']);
            Route::post('/sync', [TwitterIntegrationController::class, 'sync']);
            Route::post('/disconnect', [TwitterIntegrationController::class, 'disconnect']);
            Route::post('/test-connection', [TwitterIntegrationController::class, 'testConnection']);
        });

        // Platform-wide sync endpoint (sync all accounts for a platform)
        Route::post('/platform/sync-metrics', function (Request $request) {
            try {
                set_time_limit(0);

                $platform = $request->input('platform');
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                $fullHistory = $request->input('full_history', false);
                $quickMode = $request->input('quick_mode', false);

                // Validate platform
                if (!$platform) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Platform is required. Please specify which platform to sync.',
                    ], 400);
                }

                // Validate date range if not full history
                if (!$fullHistory && $startDate && $endDate) {
                    $start = \Carbon\Carbon::parse($startDate);
                    $end = \Carbon\Carbon::parse($endDate);

                    if ($start->gt($end)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Start date cannot be after end date.',
                        ], 400);
                    }

                    if ($end->gt(now())) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'End date cannot be in the future.',
                        ], 400);
                    }
                }

                // Normalize platform name
                $platformMap = [
                    'google' => 'google',
                    'google_ads' => 'google',
                    'facebook' => 'facebook',
                    'snapchat' => 'snapchat',
                    'linkedin' => 'linkedin',
                    'tiktok' => 'tiktok',
                    'twitter' => 'twitter',
                ];

                if (!isset($platformMap[$platform])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Unknown platform: {$platform}. Supported platforms: " . implode(', ', array_keys($platformMap)),
                    ], 400);
                }

                $normalizedPlatform = $platformMap[$platform];

                // Get all integrations for this platform
                $query = \App\Models\Integration::where('platform', $normalizedPlatform)
                    ->where('status', 'active');

                if ($request->current_tenant) {
                    $query->where('tenant_id', $request->current_tenant->id);
                }

                $integrations = $query->get();

                if ($integrations->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "No active {$platform} integrations found. Please connect the platform first.",
                    ], 404);
                }

                $totalAccounts = 0;
                $errors = [];
                $artisanPath = base_path('artisan');
                $accountNames = [];
                $commands = []; // Collect all commands first

                // Phase 1: Collect all commands (fast)
                foreach ($integrations as $integration) {
                    $adAccounts = $integration->adAccounts;
                    $totalAccounts += $adAccounts->count();

                    if ($adAccounts->isEmpty()) {
                        $errors[] = "Integration {$integration->id}: No ad accounts found";
                        continue;
                    }

                    foreach ($adAccounts as $adAccount) {
                        try {
                            $command = '';
                            $logFile = storage_path('logs/platform-sync-' . $normalizedPlatform . '-' . $adAccount->id . '-' . date('Y-m-d-His') . '.log');

                            if ($normalizedPlatform === 'facebook') {
                                $command = "php {$artisanPath} facebook:sync-metrics {$integration->id} --account-id={$adAccount->id}";
                                if ($fullHistory) {
                                    $command .= " --all";
                                } elseif ($startDate && $endDate) {
                                    $command .= " --start-date={$startDate} --end-date={$endDate}";
                                }
                            } elseif ($normalizedPlatform === 'google') {
                                $command = "php {$artisanPath} google-ads:sync --metrics";
                                if (!$fullHistory && $startDate && $endDate) {
                                    $command .= " --start-date={$startDate} --end-date={$endDate}";
                                }
                            } elseif ($normalizedPlatform === 'snapchat') {
                                // Use sync-metrics with --all for dynamic date detection based on campaign start dates
                                $command = "php {$artisanPath} snapchat:sync-metrics --account-id={$adAccount->id}";
                                if ($fullHistory) {
                                    $command .= " --all";
                                } elseif ($startDate && $endDate) {
                                    $command .= " --start-date={$startDate} --end-date={$endDate}";
                                }
                            } elseif ($normalizedPlatform === 'linkedin') {
                                $command = "php {$artisanPath} linkedin:backfill-metrics --account-id={$adAccount->id}";
                                if ($fullHistory) {
                                    $command .= " --full-history";
                                } elseif ($startDate && $endDate) {
                                    $command .= " --start-date={$startDate} --end-date={$endDate}";
                                }
                                if ($quickMode) {
                                    $command .= " --quick";
                                }
                            } elseif ($normalizedPlatform === 'tiktok') {
                                // Use sync-metrics with --all for dynamic date detection based on campaign start dates
                                $command = "php {$artisanPath} tiktok:sync-metrics {$integration->id} --account-id={$adAccount->id}";
                                if ($fullHistory) {
                                    $command .= " --all";
                                } elseif ($startDate && $endDate) {
                                    $command .= " --start-date={$startDate} --end-date={$endDate}";
                                }
                            }

                            if ($command) {
                                $commands[] = [
                                    'command' => $command,
                                    'logFile' => $logFile,
                                    'accountId' => $adAccount->id,
                                    'accountName' => $adAccount->account_name,
                                ];
                                $accountNames[] = $adAccount->account_name;
                            } else {
                                $errors[] = "Account {$adAccount->account_name}: Sync command not configured for this platform";
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Account {$adAccount->account_name}: " . $e->getMessage();
                        }
                    }
                }

                $accountsSynced = count($commands);

                if ($accountsSynced === 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $totalAccounts > 0
                            ? "Failed to start sync for any accounts. Check errors for details."
                            : "No ad accounts found for {$platform}. Please sync accounts first.",
                        'accounts_synced' => 0,
                        'total_accounts' => $totalAccounts,
                        'errors' => $errors,
                    ], 500);
                }

                $message = "Started sync for {$accountsSynced} {$platform} account(s) in background";
                if ($quickMode) {
                    $message .= " (Quick Mode)";
                }
                if ($fullHistory) {
                    $message .= " - Full History";
                }

                // Phase 2: Create batch script - run sequentially to avoid DB connection issues
                $batchScript = storage_path('logs/platform-sync-batch-' . $normalizedPlatform . '-' . date('Y-m-d-His') . '.sh');
                $masterLogFile = storage_path('logs/platform-sync-' . $normalizedPlatform . '-master-' . date('Y-m-d-His') . '.log');
                $scriptContent = "#!/bin/bash\n";
                $scriptContent .= "echo 'Starting {$normalizedPlatform} platform sync for {$accountsSynced} accounts' >> {$masterLogFile}\n";

                foreach ($commands as $index => $cmd) {
                    $accountNum = $index + 1;
                    // Run sequentially (no & at end) to avoid overwhelming DB/API
                    $scriptContent .= "echo '=== Syncing account {$accountNum}/{$accountsSynced}: {$cmd['accountName']} ===' >> {$masterLogFile}\n";
                    $scriptContent .= "{$cmd['command']} > {$cmd['logFile']} 2>&1\n";
                    $scriptContent .= "echo 'Completed account {$accountNum}/{$accountsSynced}' >> {$masterLogFile}\n";
                }
                $scriptContent .= "echo '=== Platform Sync Complete ===' >> {$masterLogFile}\n";

                file_put_contents($batchScript, $scriptContent);
                chmod($batchScript, 0755);

                // Log the sync
                \Log::info("Platform sync batch started", [
                    'platform' => $normalizedPlatform,
                    'accounts' => $accountsSynced,
                    'batch_script' => $batchScript,
                    'master_log' => $masterLogFile,
                ]);

                // Phase 3: Execute batch script in background (runs sequentially inside)
                exec("nohup {$batchScript} > /dev/null 2>&1 &");

                return response()->json([
                    'status' => 'started',
                    'message' => $message,
                    'accounts_synced' => $accountsSynced,
                    'total_accounts' => $totalAccounts,
                    'account_names' => array_slice($accountNames, 0, 5), // First 5 account names
                    'errors' => $errors,
                    'master_log' => basename($masterLogFile),
                ]);

            } catch (\Exception $e) {
                \Log::error("Platform sync endpoint error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                ], 500);
            }
        });

        // Reports endpoints (placeholder)
        Route::prefix('reports')->group(function () {
            Route::get('/', function (Request $request) {
                // Require tenant for viewing reports
                if (!$request->current_tenant) {
                    return response()->json(['error' => 'Tenant selection required'], 400);
                }

                $exports = \App\Models\ReportExport::where('tenant_id', $request->current_tenant->id)
                    ->where('user_id', $request->user()->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return response()->json([
                    'data' => $exports,
                ]);
            });

            Route::post('/export', function (Request $request) {
                $request->validate([
                    'format' => 'required|in:csv,xlsx',
                    'params' => 'required|array',
                ]);

                // Require tenant for creating reports
                if (!$request->current_tenant) {
                    return response()->json(['error' => 'Tenant selection required'], 400);
                }

                $export = \App\Models\ReportExport::create([
                    'tenant_id' => $request->current_tenant->id,
                    'user_id' => $request->user()->id,
                    'format' => $request->format,
                    'params' => $request->params,
                    'status' => 'queued',
                ]);
                
                // TODO: Dispatch export job
                
                return response()->json([
                    'export_id' => $export->id,
                    'status' => 'queued',
                    'message' => 'Export job queued successfully',
                ]);
            });
            
            Route::get('/{export}', function (\App\Models\ReportExport $export) {
                if ($export->status === 'done' && $export->file_path) {
                    return response()->json([
                        'status' => 'done',
                        'download_url' => url('storage/' . $export->file_path),
                    ]);
                }
                
                return response()->json([
                    'status' => $export->status,
                    'message' => $export->status === 'failed' ? 'Export failed' : 'Export in progress',
                ]);
            });
            
            Route::delete('/{export}', function (\App\Models\ReportExport $export) {
                // Check if the export belongs to the current user and tenant
                if ($export->user_id !== auth()->id()) {
                    return response()->json([
                        'error' => 'Unauthorized',
                    ], 403);
                }
                
                // Delete the file if it exists
                if ($export->file_path && file_exists(storage_path('app/public/' . $export->file_path))) {
                    unlink(storage_path('app/public/' . $export->file_path));
                }
                
                $export->delete();
                
                return response()->json([
                    'message' => 'Export deleted successfully',
                ]);
            });
        });
        
        // Sync endpoints (Admin only)
        Route::post('/sync/run', function (Request $request) {
            try {
                // TODO: Check if user is admin

                // Require tenant for manual sync
                if (!$request->current_tenant) {
                    return response()->json(['error' => 'Tenant selection required for manual sync'], 400);
                }

                // Count active integrations that need syncing
                $activeIntegrations = \App\Models\Integration::where('tenant_id', $request->current_tenant->id)
                    ->where('status', 'active')
                    ->count();

                // Run sync commands for Facebook integrations
                $facebookIntegrations = \App\Models\Integration::where('tenant_id', $request->current_tenant->id)
                    ->where('platform', 'facebook')
                    ->where('status', 'active')
                    ->get();
                    
                $totalCampaigns = 0;
                $totalMetrics = 0;
                $errors = [];
                
                foreach ($facebookIntegrations as $integration) {
                    // Sync campaigns
                    try {
                        $exitCode = \Artisan::call('facebook:sync-campaigns', ['integration_id' => $integration->id]);
                        $campaignOutput = \Artisan::output();
                        
                        if ($exitCode === 0) {
                            $totalCampaigns += substr_count($campaignOutput, 'Processing campaign:');
                        } else {
                            $errors[] = "Campaign sync failed for integration {$integration->id}: " . $campaignOutput;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Campaign sync exception for integration {$integration->id}: " . $e->getMessage();
                        \Log::error("Campaign sync failed", ['integration_id' => $integration->id, 'error' => $e->getMessage()]);
                    }
                    
                    // Sync metrics for last 7 days
                    try {
                        $exitCode = \Artisan::call('facebook:sync-metrics', [
                            'integration_id' => $integration->id,
                            '--days' => 7
                        ]);
                        $metricsOutput = \Artisan::output();
                        
                        if ($exitCode === 0) {
                            preg_match('/Successfully synced (\d+) metric records/', $metricsOutput, $matches);
                            $totalMetrics += isset($matches[1]) ? intval($matches[1]) : 0;
                        } else {
                            $errors[] = "Metrics sync failed for integration {$integration->id}: " . $metricsOutput;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Metrics sync exception for integration {$integration->id}: " . $e->getMessage();
                        \Log::error("Metrics sync failed", ['integration_id' => $integration->id, 'error' => $e->getMessage()]);
                    }
                }
                
                return response()->json([
                    'message' => 'Sync completed successfully',
                    'integrations_processed' => $facebookIntegrations->count(),
                    'campaigns_synced' => $totalCampaigns,
                    'metrics_synced' => $totalMetrics,
                    'errors' => $errors,
                    'active_integrations' => $activeIntegrations,
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Sync run failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json([
                    'message' => 'Sync failed: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
        
        // Tenants endpoints
        Route::prefix('tenants')->group(function () {
            Route::get('/', function (Request $request) {
                return response()->json([
                    'data' => $request->user()->tenants->map(function ($tenant) use ($request) {
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug,
                            'status' => $tenant->status,
                            'role' => $request->user()->getRoleForTenant($tenant),
                        ];
                    }),
                ]);
            });
            
            Route::post('/invite', function (Request $request) {
                $request->validate([
                    'email' => 'required|email',
                    'role' => 'required|in:admin,viewer',
                ]);
                
                // TODO: Send invitation email
                
                return response()->json([
                    'message' => 'Invitation sent successfully',
                    'email' => $request->email,
                    'role' => $request->role,
                ]);
            });
        });
        
        // Industries management endpoints
        Route::prefix('industries')->group(function () {
            Route::get('/', [IndustryController::class, 'index']);
            Route::post('/', [IndustryController::class, 'store']);
            Route::put('/{industry}', [IndustryController::class, 'update']);
            Route::delete('/{industry}', [IndustryController::class, 'destroy']);

            // Sub-industries routes (Ad Account Categories)
            Route::get('/{industry}/sub-industries', [IndustryController::class, 'subIndustries']);
            Route::post('/{industry}/sub-industries', [IndustryController::class, 'storeSubIndustry']);
            Route::put('/sub-industries/{subIndustry}', [IndustryController::class, 'updateSubIndustry']);
            Route::delete('/sub-industries/{subIndustry}', [IndustryController::class, 'destroySubIndustry']);

            // Campaign Categories routes (per industry)
            Route::post('/{industry}/campaign-categories', [IndustryController::class, 'storeCampaignCategory']);
        });

        // Campaign Categories management endpoints
        Route::prefix('campaign-categories')->group(function () {
            Route::get('/', [IndustryController::class, 'campaignCategories']);
            Route::put('/{campaignCategory}', [IndustryController::class, 'updateCampaignCategory']);
            Route::delete('/{campaignCategory}', [IndustryController::class, 'destroyCampaignCategory']);
        });

        // Alerts management endpoints
        Route::prefix('alerts')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\AlertController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\AlertController::class, 'store']);
            Route::get('/{alert}', [App\Http\Controllers\Api\AlertController::class, 'show']);
            Route::put('/{alert}', [App\Http\Controllers\Api\AlertController::class, 'update']);
            Route::delete('/{alert}', [App\Http\Controllers\Api\AlertController::class, 'destroy']);
            Route::post('/{alert}/toggle', [App\Http\Controllers\Api\AlertController::class, 'toggle']);
            Route::post('/evaluate', [App\Http\Controllers\Api\AlertController::class, 'evaluate']);
        });

        // Scheduled Reports endpoints
        Route::prefix('scheduled-reports')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ScheduledReportController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\ScheduledReportController::class, 'store']);
            Route::get('/{report}', [App\Http\Controllers\Api\ScheduledReportController::class, 'show']);
            Route::put('/{report}', [App\Http\Controllers\Api\ScheduledReportController::class, 'update']);
            Route::delete('/{report}', [App\Http\Controllers\Api\ScheduledReportController::class, 'destroy']);
            Route::post('/{report}/toggle', [App\Http\Controllers\Api\ScheduledReportController::class, 'toggle']);
            Route::post('/{report}/generate', [App\Http\Controllers\Api\ScheduledReportController::class, 'generate']);
            Route::get('/{report}/history', [App\Http\Controllers\Api\ScheduledReportController::class, 'history']);
            Route::get('/{report}/history/{history}/download', [App\Http\Controllers\Api\ScheduledReportController::class, 'download']);
        });

        // Google Ads integration routes are defined above with other platform integrations

        // Campaign Integration routes
        Route::prefix('campaigns')->group(function () {
            Route::post('/{campaign}/google-sheets/setup', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'setupGoogleSheets']);
            Route::get('/{campaign}/google-sheets/status', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'getGoogleSheetsStatus']);
            Route::post('/{campaign}/google-sheets/mapping', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'updateSheetMapping']);
            Route::post('/{campaign}/google-sheets/sync', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'syncToGoogleSheets']);

            Route::post('/{campaign}/conversion-pixel/setup', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'setupConversionPixel']);
            Route::get('/{campaign}/conversion-pixel/status', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'getConversionPixelStatus']);
            Route::get('/{campaign}/conversion-analytics', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'getConversionAnalytics']);
        });

        // CSV Import endpoints
        Route::prefix('csv-import')->group(function () {
            Route::post('/facebook', [App\Http\Controllers\Api\CsvImportController::class, 'importFacebookCsv']);
            Route::post('/google-ads', [App\Http\Controllers\Api\CsvImportController::class, 'importGoogleAdsCsv']);
            Route::post('/preview', [App\Http\Controllers\Api\CsvImportController::class, 'previewCsv']);
            Route::get('/history', [App\Http\Controllers\Api\CsvImportController::class, 'getImportHistory']);
        });
    });
});

// Google OAuth callbacks (public, no auth required)
Route::get('/auth/google/callback', [App\Http\Controllers\Api\GoogleAuthController::class, 'handleCallback']);

// Google Sheets Integration Status endpoints (public, for integration page)
Route::prefix('google-auth')->group(function () {
    Route::get('/url', [App\Http\Controllers\Api\GoogleAuthController::class, 'getAuthUrl']);
    Route::get('/status', [App\Http\Controllers\Api\GoogleAuthController::class, 'checkAuthStatus']);
    Route::post('/test', [App\Http\Controllers\Api\GoogleAuthController::class, 'testConnection']);
});

// Conversion Pixel tracking endpoint (public, no auth required)
Route::post('/api/pixel/{pixelId}/track', [App\Http\Controllers\Api\CampaignIntegrationController::class, 'trackConversion']);
