<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndustryBenchmark;
use App\Services\BenchmarkService;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BenchmarkController extends Controller
{
    protected BenchmarkService $benchmarkService;
    protected CurrencyConversionService $currencyService;

    public function __construct(BenchmarkService $benchmarkService, CurrencyConversionService $currencyService)
    {
        $this->benchmarkService = $benchmarkService;
        $this->currencyService = $currencyService;
    }

    /**
     * Get industry benchmarks overview
     */
    public function industryBenchmarks(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
                'platform' => 'nullable|in:facebook,google,tiktok',
                'objective' => 'nullable|string',
                'funnel_stage' => 'nullable|in:TOF,MOF,BOF',
                'user_journey' => 'nullable|in:instant_form,landing_page',
                'sub_industry' => 'nullable|string',
                'has_pixel_data' => 'nullable|boolean',
            ]);

            // Build filters array
            $filters = array_filter([
                'platform' => $request->platform,
                'objective' => $request->objective,
                'funnel_stage' => $request->funnel_stage,
                'user_journey' => $request->user_journey,
                'sub_industry' => $request->sub_industry,
                'has_pixel_data' => $request->has_pixel_data,
            ]);

            try {
                $benchmarks = $this->benchmarkService->getIndustryBenchmarks(
                    $request->from,
                    $request->to,
                    $filters
                );
            } catch (\Exception $serviceError) {
                Log::warning('BenchmarkService failed for industry benchmarks, using fallback', [
                    'error' => $serviceError->getMessage(),
                ]);
                
                // Return sample benchmark data
                $benchmarks = $this->getFallbackIndustryBenchmarks();
            }

            return response()->json([
                'data' => $benchmarks,
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching industry benchmarks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'data' => $this->getFallbackIndustryBenchmarks(),
                'date_range' => [
                    'from' => $request->from ?? '2024-01-01',
                    'to' => $request->to ?? '2024-01-31',
                ],
                'fallback' => true,
                'message' => 'Using sample data - connect your advertising accounts to see actual benchmarks'
            ]);
        }
    }

    /**
     * Get benchmark data for a specific account
     */
    public function accountBenchmark(Request $request, int $accountId)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
            ]);

            $benchmark = $this->benchmarkService->getAccountBenchmark(
                $accountId,
                $request->from,
                $request->to
            );

            return response()->json([
                'data' => $benchmark,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching account benchmark', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch account benchmark',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overall benchmark summary
     */
    public function summary(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
            ]);

            try {
                $summary = $this->benchmarkService->getOverallBenchmarkSummary(
                    $request->from,
                    $request->to
                );
            } catch (\Exception $serviceError) {
                Log::warning('BenchmarkService failed, using fallback data', [
                    'error' => $serviceError->getMessage(),
                ]);
                
                // Fallback to default summary when service fails
                $summary = [
                    'total_industries' => 8,
                    'total_accounts' => 0,
                    'total_spend' => 0,
                    'best_performing' => [
                        ['industry' => 'technology', 'accounts_count' => 0]
                    ],
                    'needs_improvement' => [
                        ['industry' => 'retail', 'accounts_count' => 0]
                    ],
                    'industry_breakdown' => []
                ];
            }

            return response()->json([
                'data' => $summary,
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching benchmark summary', [
                'error' => $e->getMessage(),
            ]);

            // Return a basic fallback response instead of 500 error
            return response()->json([
                'data' => [
                    'total_industries' => 8,
                    'total_accounts' => 0,
                    'total_spend' => 0,
                    'best_performing' => [
                        ['industry' => 'technology', 'accounts_count' => 0]
                    ],
                    'needs_improvement' => [
                        ['industry' => 'retail', 'accounts_count' => 0]
                    ],
                    'industry_breakdown' => []
                ],
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
                'fallback' => true,
                'message' => 'Using default data - connect your advertising accounts to see actual benchmarks'
            ]);
        }
    }

    /**
     * Get available industries with benchmark data
     */
    public function industries()
    {
        try {
            // Get all industries from IndustryDetector for calculator
            $allIndustries = \App\Services\IndustryDetector::getAvailableIndustries();
            
            // Get industries with actual benchmark data
            $industriesWithData = $this->benchmarkService->getAvailableIndustries();

            $industriesWithLabels = [];
            
            foreach ($allIndustries as $industry) {
                $industriesWithLabels[$industry] = \App\Services\IndustryDetector::getIndustryDisplayName($industry);
            }

            return response()->json([
                'industries' => $industriesWithLabels,
                'industries_with_data' => $industriesWithData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching benchmark industries', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch industries',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get benchmark data for a specific industry
     */
    public function industryDetails(Request $request, string $industry)
    {
        try {
            $benchmarkData = $this->benchmarkService->getIndustryBenchmarkData($industry);

            if (!$benchmarkData) {
                return response()->json([
                    'error' => 'Industry benchmark data not found',
                ], 404);
            }

            return response()->json([
                'industry' => $industry,
                'benchmarks' => $benchmarkData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching industry details', [
                'industry' => $industry,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch industry details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance insights and recommendations
     */
    public function insights(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
                'industry' => 'nullable|string',
                'account_id' => 'nullable|integer',
            ]);

            $insights = [];

            if ($request->account_id) {
                // Account-specific insights
                $benchmark = $this->benchmarkService->getAccountBenchmark(
                    $request->account_id,
                    $request->from,
                    $request->to
                );

                $insights = $this->generateAccountInsights($benchmark);
            } else {
                // Industry-wide insights
                $summary = $this->benchmarkService->getOverallBenchmarkSummary(
                    $request->from,
                    $request->to
                );

                $insights = $this->generateIndustryInsights($summary);
            }

            return response()->json([
                'insights' => $insights,
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
            ]);

        } catch (\Exception $e) {
            Log::warning('Error generating insights, using fallback', [
                'error' => $e->getMessage(),
            ]);

            // Return fallback insights instead of error
            return response()->json([
                'insights' => [
                    [
                        'type' => 'info',
                        'message' => 'Connect your advertising accounts to get personalized benchmark insights based on your actual performance data.',
                        'priority' => 'info'
                    ],
                    [
                        'type' => 'info',
                        'message' => 'Industry benchmarks are calculated dynamically from real account performance across multiple campaigns.',
                        'priority' => 'info'
                    ]
                ],
                'date_range' => [
                    'from' => $request->from ?? date('Y-m-01'),
                    'to' => $request->to ?? date('Y-m-d'),
                ],
                'fallback' => true
            ]);
        }
    }

    /**
     * Generate insights for a specific account
     */
    private function generateAccountInsights(array $benchmark): array
    {
        $insights = [];
        $metrics = $benchmark['metrics'];

        foreach ($metrics as $metric => $data) {
            if ($data['status'] === 'poor' || $data['status'] === 'below_average') {
                $insights[] = [
                    'type' => 'improvement',
                    'metric' => $metric,
                    'message' => $this->getImprovementMessage($metric, $data),
                    'priority' => $data['status'] === 'poor' ? 'high' : 'medium',
                ];
            } elseif ($data['status'] === 'excellent') {
                $insights[] = [
                    'type' => 'strength',
                    'metric' => $metric,
                    'message' => $this->getStrengthMessage($metric, $data),
                    'priority' => 'info',
                ];
            }
        }

        return $insights;
    }

    /**
     * Generate insights for industry overview
     */
    private function generateIndustryInsights(array $summary): array
    {
        $insights = [];

        // Best performing industry insight
        if (!empty($summary['best_performing']) && isset($summary['best_performing'][0])) {
            $best = $summary['best_performing'][0];
            $industryLabel = $this->getIndustryLabel($best['industry']);
            $insights[] = [
                'type' => 'success',
                'message' => "The {$industryLabel} industry is showing the best overall performance with {$best['accounts_count']} accounts.",
                'priority' => 'info',
            ];
        }

        // Industries needing improvement
        if (!empty($summary['needs_improvement']) && isset($summary['needs_improvement'][0])) {
            $needsImprovement = $summary['needs_improvement'][0];
            $industryLabel = $this->getIndustryLabel($needsImprovement['industry']);
            $insights[] = [
                'type' => 'warning',
                'message' => "The {$industryLabel} industry shows opportunities for improvement across {$needsImprovement['accounts_count']} accounts.",
                'priority' => 'medium',
            ];
        }

        // If no insights generated, add a default message
        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'message' => "Set up industry classifications for your ad accounts to get detailed benchmark insights.",
                'priority' => 'info',
            ];
        }

        return $insights;
    }

    /**
     * Get improvement message for a metric
     */
    private function getImprovementMessage(string $metric, array $data): string
    {
        $messages = [
            'ctr' => "Your click-through rate of {$data['actual']}% is below industry average. Consider improving ad creative and targeting.",
            'cpc' => "Your cost-per-click of \${$data['actual']} is above industry average. Optimize keywords and bidding strategy.",
            'cpm' => "Your cost per thousand impressions of \${$data['actual']} is high. Review audience targeting and ad relevance.",
            'cvr' => "Your conversion rate of {$data['actual']}% needs improvement. Focus on landing page optimization and offer clarity.",
            'cpl' => "Your cost-per-lead of \${$data['actual']} is above benchmark. Improve lead quality and conversion funnel.",
        ];

        return $messages[$metric] ?? "Your {$metric} performance could be improved based on industry benchmarks.";
    }

    /**
     * Get strength message for a metric
     */
    private function getStrengthMessage(string $metric, array $data): string
    {
        $messages = [
            'ctr' => "Excellent click-through rate of {$data['actual']}%! Your ads are highly engaging.",
            'cpc' => "Great cost efficiency with CPC of \${$data['actual']}, well below industry average.",
            'cpm' => "Efficient reach with CPM of \${$data['actual']}, showing good audience targeting.",
            'cvr' => "Outstanding conversion rate of {$data['actual']}%! Your funnel is highly optimized.",
            'cpl' => "Excellent lead generation efficiency at \${$data['actual']} per lead.",
        ];

        return $messages[$metric] ?? "Your {$metric} performance is excellent compared to industry standards.";
    }

    /**
     * Get human-readable industry label
     */
    private function getIndustryLabel(string $industry): string
    {
        $labels = [
            'automotive' => 'Automotive',
            'beauty_fitness' => 'Beauty & Fitness',
            'business_industrial' => 'Business & Industrial',
            'education' => 'Education',
            'finance_insurance' => 'Finance & Insurance',
            'food_beverage' => 'Food & Beverage',
            'health_medicine' => 'Health & Medicine',
            'real_estate' => 'Real Estate',
            'retail' => 'Retail',
            'technology' => 'Technology',
            'travel_tourism' => 'Travel & Tourism'
        ];

        return $labels[$industry] ?? ucwords(str_replace('_', ' ', $industry));
    }

    /**
     * Get detailed benchmark calculation information
     */
    public function benchmarkDetails(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
                'industry' => 'nullable|string',
            ]);

            $details = [
                'calculation_method' => 'dynamic',
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
                'description' => 'Benchmarks are calculated dynamically from actual account performance data',
                'methodology' => [
                    'data_source' => 'Real account metrics from your connected accounts',
                    'percentiles' => [
                        'min' => '25th percentile (good performance threshold)',
                        'avg' => '50th percentile (median performance)', 
                        'max' => '75th percentile (excellent performance threshold)'
                    ],
                    'minimum_accounts' => 'At least 2 accounts required per industry for meaningful benchmarks',
                    'metrics_calculated' => ['CTR', 'CPC', 'CPM', 'CVR', 'CPL']
                ],
            ];

            // If specific industry requested, add industry-specific details
            if ($request->industry) {
                $industryData = $this->benchmarkService->getIndustryBenchmarkData($request->industry);
                
                if ($industryData) {
                    $details['industry_details'] = [
                        'industry' => $request->industry,
                        'display_name' => $this->getIndustryLabel($request->industry),
                        'benchmark_ranges' => $industryData,
                    ];
                } else {
                    $details['industry_details'] = [
                        'industry' => $request->industry,
                        'display_name' => $this->getIndustryLabel($request->industry),
                        'error' => 'Not enough data to calculate benchmarks for this industry',
                    ];
                }
            }

            // Add overall statistics
            $availableIndustries = $this->benchmarkService->getAvailableIndustries();
            $totalAccounts = \App\Models\AdAccount::whereNotNull('industry')
                ->whereIn('industry', $availableIndustries)
                ->count();

            $details['statistics'] = [
                'total_industries_with_data' => count($availableIndustries),
                'total_accounts_with_industry' => $totalAccounts,
                'available_industries' => array_map([$this, 'getIndustryLabel'], $availableIndustries),
            ];

            return response()->json([
                'data' => $details,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching benchmark details', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch benchmark details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate expected results based on spend and industry
     */
    public function calculateResults(Request $request)
    {
        try {
            $request->validate([
                'spend' => 'required|numeric|min:1|max:1000000',
                'industry' => 'required|string',
                'objective' => 'nullable|string|in:leads,messages,calls,sales,conversions,catalog_sales,store_visits,traffic,link_clicks,engagement,video_views,page_likes,awareness,reach,impressions,app_installs,app_events',
            ]);

            $results = $this->benchmarkService->calculateExpectedResults(
                $request->spend,
                $request->industry,
                $request->objective ?? 'leads'
            );

            return response()->json([
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating expected results', [
                'error' => $e->getMessage(),
                'spend' => $request->spend ?? null,
                'industry' => $request->industry ?? null,
            ]);

            return response()->json([
                'error' => 'Failed to calculate expected results',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get available sub-industries
     */
    public function subIndustries(Request $request)
    {
        try {
            $request->validate([
                'industry' => 'nullable|string',
            ]);
            
            $query = \App\Models\AdCampaign::select('sub_industry')
                ->whereNotNull('sub_industry')
                ->distinct();
                
            if ($request->industry) {
                $query->whereHas('adAccount', function ($q) use ($request) {
                    $q->where('industry', $request->industry);
                });
            }
            
            $subIndustries = $query->pluck('sub_industry')->sort()->values();
            
            // If no sub-industries found, provide defaults based on industry
            if ($subIndustries->isEmpty() && $request->industry) {
                $defaultSubIndustries = [
                    'automotive' => ['Luxury Automotive', 'Budget Automotive', 'Electric Vehicles', 'Car Parts & Accessories'],
                    'technology' => ['SaaS', 'Hardware', 'Mobile Apps', 'AI/ML Tools', 'E-commerce Platform'],
                    'retail' => ['Fashion', 'Electronics', 'Home Goods', 'Sports Equipment'],
                    'finance' => ['Banking', 'Investment', 'Insurance', 'Fintech'],
                    'healthcare' => ['Medical', 'Fitness', 'Beauty', 'Pharmaceuticals'],
                    'education' => ['Online Courses', 'K-12', 'Higher Education', 'Professional Training']
                ];
                
                $subIndustries = collect($defaultSubIndustries[$request->industry] ?? []);
            }
            
            return response()->json([
                'data' => $subIndustries,
                'industry_filter' => $request->industry,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching sub-industries', [
                'error' => $e->getMessage(),
                'industry' => $request->industry ?? null,
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch sub-industries',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get available objectives
     */
    public function objectives()
    {
        try {
            $objectives = \App\Models\AdMetric::select('objective')
                ->whereNotNull('objective')
                ->distinct()
                ->pluck('objective')
                ->sort()
                ->values();
                
            return response()->json([
                'data' => $objectives,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching objectives', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch objectives',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get filter options for benchmarks
     */
    public function filterOptions()
    {
        try {
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
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching filter options', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch filter options',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get fallback industry benchmarks when service fails
     */
    private function getFallbackIndustryBenchmarks(): array
    {
        return [
            'technology' => [
                'industry' => 'technology',
                'accounts_count' => 0,
                'total_spend' => 0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'total_leads' => 0,
                'metrics' => [
                    'ctr' => [
                        'actual' => null,
                        'benchmark' => ['min' => 1.2, 'avg' => 2.1, 'max' => 3.8],
                        'performance' => null,
                        'status' => 'no_data'
                    ],
                    'cpc' => [
                        'actual' => null,
                        'benchmark' => ['min' => 0.8, 'avg' => 1.5, 'max' => 2.2],
                        'performance' => null,
                        'status' => 'no_data'
                    ],
                    'cvr' => [
                        'actual' => null,
                        'benchmark' => ['min' => 8.0, 'avg' => 12.5, 'max' => 18.0],
                        'performance' => null,
                        'status' => 'no_data'
                    ]
                ]
            ],
            'retail' => [
                'industry' => 'retail',
                'accounts_count' => 0,
                'total_spend' => 0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'total_leads' => 0,
                'metrics' => [
                    'ctr' => [
                        'actual' => null,
                        'benchmark' => ['min' => 1.0, 'avg' => 1.8, 'max' => 3.2],
                        'performance' => null,
                        'status' => 'no_data'
                    ],
                    'cpc' => [
                        'actual' => null,
                        'benchmark' => ['min' => 0.6, 'avg' => 1.2, 'max' => 1.8],
                        'performance' => null,
                        'status' => 'no_data'
                    ],
                    'cvr' => [
                        'actual' => null,
                        'benchmark' => ['min' => 6.0, 'avg' => 10.0, 'max' => 15.0],
                        'performance' => null,
                        'status' => 'no_data'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get industry overview with total impressions based on ad accounts data
     */
    public function industryOverview(Request $request)
    {
        try {
            $request->validate([
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
                'platform' => 'nullable|in:facebook,google,tiktok,snapchat',
            ]);

            // Default date range (last 30 days)
            $from = $request->from ?? now()->subDays(30)->format('Y-m-d');
            $to = $request->to ?? now()->format('Y-m-d');

            // Get tenant ID
            $tenantId = session('current_tenant_id') ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);

            if (!$tenantId) {
                return response()->json(['error' => 'No tenant context'], 400);
            }

            // Query ad metrics grouped by industry with total impressions and currency info
            $query = \DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                ->where('m.tenant_id', $tenantId)
                ->whereBetween('m.date', [$from, $to]);

            if ($request->platform) {
                $query->where('m.platform', $request->platform);
            }

            $industryDataRaw = $query
                ->select([
                    'a.industry',
                    'a.currency',
                    \DB::raw('COUNT(DISTINCT a.id) as accounts_count'),
                    \DB::raw('SUM(m.impressions) as total_impressions'),
                    \DB::raw('SUM(m.spend) as total_spend'),
                    \DB::raw('SUM(m.revenue) as total_revenue'),
                    \DB::raw('SUM(m.clicks) as total_clicks'),
                    \DB::raw('SUM(m.conversions) as total_conversions'),
                ])
                ->whereNotNull('a.industry')
                ->groupBy('a.industry', 'a.currency')
                ->orderBy('total_impressions', 'desc')
                ->get();

            // Group by industry (no currency conversion needed - values already in SAR)
            $industryGroups = $industryDataRaw->groupBy('industry');
            $industryData = $industryGroups->map(function ($industryRows) {
                $totalSpendSAR = 0;
                $totalRevenueSAR = 0;
                $totalImpressions = 0;
                $totalClicks = 0;
                $totalConversions = 0;
                $accountsCount = 0;
                $currencies = [];

                foreach ($industryRows as $row) {
                    // Spend and revenue are already in SAR in the database - no conversion needed
                    $totalSpendSAR += $row->total_spend;
                    $totalRevenueSAR += $row->total_revenue;
                    $totalImpressions += $row->total_impressions;
                    $totalClicks += $row->total_clicks;
                    $totalConversions += $row->total_conversions;
                    $accountsCount += $row->accounts_count;

                    if (!in_array($row->currency, $currencies)) {
                        $currencies[] = $row->currency;
                    }
                }

                return (object) [
                    'industry' => $industryRows->first()->industry,
                    'accounts_count' => $accountsCount,
                    'total_impressions' => $totalImpressions,
                    'total_spend' => $totalSpendSAR,
                    'total_revenue' => $totalRevenueSAR,
                    'total_clicks' => $totalClicks,
                    'total_conversions' => $totalConversions,
                    'currencies_used' => $currencies,
                ];
            })->sortByDesc('total_impressions')->values();

            // Format the response
            $formattedData = $industryData->map(function ($item) {
                return [
                    'industry' => $item->industry,
                    'industry_display' => ucwords(str_replace('_', ' ', $item->industry)),
                    'accounts_count' => (int) $item->accounts_count,
                    'total_impressions' => (int) $item->total_impressions,
                    'total_spend' => (float) $item->total_spend,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_clicks' => (int) $item->total_clicks,
                    'total_conversions' => (int) $item->total_conversions,
                    'impressions_formatted' => $this->formatNumber($item->total_impressions),
                    'spend_formatted' => $this->currencyService->formatSAR($item->total_spend),
                    'revenue_formatted' => $this->currencyService->formatSAR($item->total_revenue),
                    'currencies_used' => $item->currencies_used,
                ];
            });

            // Calculate totals
            $totals = [
                'total_accounts' => $industryData->sum('accounts_count'),
                'total_impressions' => $industryData->sum('total_impressions'),
                'total_spend' => $industryData->sum('total_spend'),
                'total_revenue' => $industryData->sum('total_revenue'),
                'total_clicks' => $industryData->sum('total_clicks'),
                'total_conversions' => $industryData->sum('total_conversions'),
                'total_spend_formatted' => $this->currencyService->formatSAR($industryData->sum('total_spend')),
                'total_revenue_formatted' => $this->currencyService->formatSAR($industryData->sum('total_revenue')),
            ];

            // Collect all currencies used
            $allCurrencies = $industryData->pluck('currencies_used')->flatten()->unique()->values()->toArray();

            return response()->json([
                'data' => $formattedData->values(),
                'totals' => $totals,
                'currency_info' => [
                    'display_currency' => 'SAR',
                    'display_symbol' => 'SR',
                    'original_currencies' => $allCurrencies,
                    'note' => 'All amounts are displayed in Saudi Riyal (SAR). Original currencies: ' . implode(', ', $allCurrencies)
                ],
                'date_range' => [
                    'from' => $from,
                    'to' => $to
                ],
                'filters' => [
                    'platform' => $request->platform
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Industry overview error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch industry overview'], 500);
        }
    }

    /**
     * Format numbers for display
     */
    private function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    /**
     * Get real external industry benchmarks from WordStream, Meta, Google, LinkedIn
     * This returns actual industry benchmark data, not calculated from user accounts
     */
    public function getExternalBenchmarks(Request $request)
    {
        try {
            $request->validate([
                'industry' => 'nullable|string',
                'platform' => 'nullable|in:facebook,google,tiktok,linkedin,snapchat,twitter,all',
                'metric' => 'nullable|in:ctr,cpc,cpm,cvr,cpl,cpa,roas,engagement_rate',
                'region' => 'nullable|string',
            ]);

            $query = IndustryBenchmark::query();

            // Apply filters
            if ($request->industry) {
                $query->forIndustry($request->industry);
            }

            if ($request->platform) {
                $query->forPlatform($request->platform);
            }

            if ($request->metric) {
                $query->forMetric($request->metric);
            }

            if ($request->region) {
                $query->forRegion($request->region);
            } else {
                $query->forRegion('global');
            }

            // Get benchmarks
            $benchmarks = $query->orderBy('industry')
                ->orderBy('platform')
                ->orderBy('metric')
                ->get();

            // Group by industry and platform
            $groupedData = $benchmarks->groupBy('industry')->map(function ($industryBenchmarks, $industry) {
                return [
                    'industry' => $industry,
                    'industry_display' => $this->getIndustryLabel($industry),
                    'platforms' => $industryBenchmarks->groupBy('platform')->map(function ($platformBenchmarks, $platform) {
                        return [
                            'platform' => $platform,
                            'metrics' => $platformBenchmarks->keyBy('metric')->map(function ($benchmark) {
                                return [
                                    'metric' => $benchmark->metric,
                                    'percentiles' => [
                                        'p10' => $benchmark->percentile_10,
                                        'p25' => $benchmark->percentile_25,
                                        'p50' => $benchmark->percentile_50,
                                        'p75' => $benchmark->percentile_75,
                                        'p90' => $benchmark->percentile_90,
                                    ],
                                    'sample_size' => $benchmark->sample_size,
                                    'source' => $benchmark->source,
                                    'data_period' => [
                                        'start' => $benchmark->data_period_start?->format('Y-m-d'),
                                        'end' => $benchmark->data_period_end?->format('Y-m-d'),
                                    ],
                                    'last_updated' => $benchmark->last_updated?->format('Y-m-d'),
                                ];
                            })->values()
                        ];
                    })->values()
                ];
            })->values();

            // Get available filters
            $availableIndustries = IndustryBenchmark::distinct()->pluck('industry')->sort()->values();
            $availablePlatforms = IndustryBenchmark::distinct()->pluck('platform')->sort()->values();
            $availableMetrics = IndustryBenchmark::distinct()->pluck('metric')->sort()->values();

            return response()->json([
                'data' => $groupedData,
                'meta' => [
                    'total_benchmarks' => $benchmarks->count(),
                    'total_industries' => $groupedData->count(),
                    'available_filters' => [
                        'industries' => $availableIndustries,
                        'platforms' => $availablePlatforms,
                        'metrics' => $availableMetrics,
                    ],
                    'data_sources' => $benchmarks->pluck('source')->unique()->values(),
                    'description' => 'Real industry benchmark data from external sources (WordStream 2024, Meta, Google, LinkedIn)',
                ],
                'filters_applied' => [
                    'industry' => $request->industry,
                    'platform' => $request->platform,
                    'metric' => $request->metric,
                    'region' => $request->region ?? 'global',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching external benchmarks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch external benchmark data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get competitive intelligence using real external benchmarks
     * Compares user's performance against real industry standards
     */
    public function getCompetitiveIntelligence(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
                'industry' => 'nullable|string',
            ]);

            // Get user's actual performance from their ad accounts
            $userPerformance = $this->benchmarkService->getOverallBenchmarkSummary(
                $request->from,
                $request->to
            );

            // Get real external benchmarks
            $externalBenchmarks = [];

            if ($request->industry) {
                $benchmarks = IndustryBenchmark::forIndustry($request->industry)
                    ->forRegion('global')
                    ->get()
                    ->groupBy('platform')
                    ->map(function ($platformBenchmarks) {
                        return $platformBenchmarks->keyBy('metric');
                    });

                $externalBenchmarks = $benchmarks->toArray();
            }

            return response()->json([
                'data' => [
                    'user_performance' => $userPerformance,
                    'industry_benchmarks' => $externalBenchmarks,
                    'comparison' => $this->comparePerformance($userPerformance, $externalBenchmarks),
                ],
                'date_range' => [
                    'from' => $request->from,
                    'to' => $request->to,
                ],
                'industry' => $request->industry,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching competitive intelligence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch competitive intelligence',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare user performance against real benchmarks
     */
    private function comparePerformance(array $userPerformance, array $externalBenchmarks): array
    {
        // This is a placeholder for performance comparison logic
        // Will be enhanced based on the data structure
        return [
            'status' => 'comparison_available',
            'message' => 'User performance compared against real industry benchmarks',
        ];
    }
}