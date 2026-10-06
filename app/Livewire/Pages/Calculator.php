<?php

namespace App\Livewire\Pages;

use App\Services\BenchmarkService;
use Livewire\Component;

class Calculator extends Component
{
    public string $calculationMode = 'budget_to_results';
    public string $industry = '';
    public string $objective = 'leads';
    public ?float $monthlyBudget = null;
    public ?float $targetResults = null;
    public array $platform = [];
    public string $country = '';
    public string $funnelStage = '';
    public string $userJourney = '';

    public string $viewMode = 'simple';

    public bool $loading = false;
    public bool $hasResult = false;
    public array $result = [];
    public string $error = '';
    public string $selectedScenario = 'average';

    public const ALLOWED_INDUSTRIES = ['real_estate', 'retail', 'ecommerce', 'e_commerce_retail'];

    public array $availableModes = [
        'budget_to_results' => 'Budget → Results',
        'target_to_budget' => 'Target → Budget',
    ];

    public array $availablePlatforms = [
        'facebook' => 'Facebook / Meta',
        'google' => 'Google Ads',
        'tiktok' => 'TikTok',
        'snapchat' => 'Snapchat',
        'linkedin' => 'LinkedIn',
    ];

    public array $availableFunnelStages = [
        'TOF' => 'Top of Funnel (Awareness)',
        'MOF' => 'Middle of Funnel (Consideration)',
        'BOF' => 'Bottom of Funnel (Conversion)',
    ];

    public array $availableUserJourneys = [
        'instant_form' => 'Instant Form (Lead Form)',
        'landing_page' => 'Landing Page',
    ];

    public array $availableObjectives = [
        'leads' => 'Leads',
        'messages' => 'Messages',
        'calls' => 'Phone Calls',
        'sales' => 'Sales',
        'conversions' => 'Conversions',
        'catalog_sales' => 'Catalog Sales',
        'store_visits' => 'Store Visits',
        'traffic' => 'Website Visits',
        'link_clicks' => 'Link Clicks',
        'engagement' => 'Engagements',
        'video_views' => 'Video Views',
        'page_likes' => 'Page Likes',
        'awareness' => 'Awareness',
        'reach' => 'Reach',
        'impressions' => 'Impressions',
        'app_installs' => 'App Installs',
        'app_events' => 'App Events',
    ];

    public function mount()
    {
        $available = $this->getWhitelistedIndustries();
        if (!empty($available)) {
            $this->industry = array_key_first($available);
        }
    }

    public function calculate(): void
    {
        $this->reset(['result', 'error', 'hasResult']);

        $rules = [
            'industry' => 'required|string|in:' . implode(',', self::ALLOWED_INDUSTRIES),
            'objective' => 'required|string',
            'calculationMode' => 'required|string|in:budget_to_results,target_to_budget',
        ];

        if ($this->calculationMode === 'target_to_budget') {
            $rules['targetResults'] = 'required|numeric|min:1|max:100000000';
        } else {
            $rules['monthlyBudget'] = 'required|numeric|min:1|max:1000000';
        }

        $this->validate($rules, [
            'monthlyBudget.required' => __('pages.calculator.budget_required'),
            'monthlyBudget.min' => __('pages.calculator.budget_min'),
            'targetResults.required' => __('pages.calculator.target_required'),
            'targetResults.min' => __('pages.calculator.target_min'),
        ]);

        $this->loading = true;

        try {
            $filters = [];
            if (!empty($this->platform)) {
                $filters['platform'] = $this->platform;
            }
            if ($this->country) {
                $filters['country'] = [$this->country];
            }
            if ($this->funnelStage) {
                $filters['funnel_stage'] = [$this->funnelStage];
            }
            if ($this->userJourney) {
                $filters['user_journey'] = [$this->userJourney];
            }

            $service = app(BenchmarkService::class);

            if ($this->calculationMode === 'target_to_budget') {
                $this->result = $service->calculateRequiredBudget(
                    (float) $this->targetResults,
                    $this->industry,
                    $this->objective,
                    $filters
                );
            } else {
                $this->result = $service->calculateExpectedResults(
                    (float) $this->monthlyBudget,
                    $this->industry,
                    $this->objective,
                    $filters
                );
            }

            // Enrich result with global benchmark comparison
            $this->enrichWithGlobalBenchmarks($service, $filters);

            // Build a funnel-stage media plan based on the active budget
            $this->result['media_plan'] = $this->buildMediaPlan(
                $this->result['input']['spend'] ?? 0,
                $this->industry,
                $this->objective,
                $filters
            );

            $this->hasResult = true;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            \Log::warning('Calculator calculation failed', [
                'mode' => $this->calculationMode,
                'industry' => $this->industry,
                'objective' => $this->objective,
                'budget' => $this->monthlyBudget,
                'target' => $this->targetResults,
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    private function enrichWithGlobalBenchmarks(BenchmarkService $service, array $filters): void
    {
        $spend = $this->result['input']['spend'] ?? 0;
        if ($spend <= 0) {
            return;
        }

        $global = $service->calculateExpectedResultsFromGlobalBenchmarks(
            $spend,
            $this->industry,
            $this->objective,
            $filters
        );

        if ($global) {
            $this->result['global_predictions'] = $global['predictions'];
        }
    }

    private function buildMediaPlan(float $budget, string $industry, string $objective, array $filters): array
    {
        if ($budget <= 0) {
            return [];
        }

        // If the user already selected a funnel stage, plan around that single stage.
        if (!empty($filters['funnel_stage'])) {
            $stage = is_array($filters['funnel_stage']) ? $filters['funnel_stage'][0] : $filters['funnel_stage'];
            $allocation = [$stage => 100];
        } else {
            $allocation = match (true) {
                in_array($objective, ['awareness', 'reach', 'impressions', 'video_views', 'page_likes', 'engagement']) => [
                    'TOF' => 70,
                    'MOF' => 20,
                    'BOF' => 10,
                ],
                in_array($objective, ['traffic', 'link_clicks']) => [
                    'TOF' => 40,
                    'MOF' => 40,
                    'BOF' => 20,
                ],
                default => [
                    'TOF' => 30,
                    'MOF' => 30,
                    'BOF' => 40,
                ],
            };
        }

        $service = app(BenchmarkService::class);
        $plan = [];

        foreach ($allocation as $stage => $percentage) {
            $stageBudget = $budget * ($percentage / 100);
            $stageFilters = array_merge($filters, ['funnel_stage' => [$stage]]);

            try {
                $prediction = $service->calculateExpectedResults(
                    $stageBudget,
                    $industry,
                    $objective,
                    $stageFilters
                );
                $average = $prediction['predictions']['average'] ?? [];

                $plan[] = [
                    'stage' => $stage,
                    'percentage' => $percentage,
                    'budget' => $stageBudget,
                    'impressions' => $average['impressions']['value'] ?? 0,
                    'clicks' => $average['clicks']['value'] ?? 0,
                    'primary_result' => $average['primary_result']['value'] ?? 0,
                    'primary_label' => $average['primary_result']['label'] ?? $this->getObjectivePrimaryLabel($objective),
                    'cost_per_result' => $average['cost_per_result']['cost_per_conversion'] ?? null,
                ];
            } catch (\Throwable $e) {
                // Skip stages that don't have enough benchmark data
                $plan[] = [
                    'stage' => $stage,
                    'percentage' => $percentage,
                    'budget' => $stageBudget,
                    'impressions' => 0,
                    'clicks' => 0,
                    'primary_result' => 0,
                    'primary_label' => $this->getObjectivePrimaryLabel($objective),
                    'cost_per_result' => null,
                ];
            }
        }

        return $plan;
    }

    public function getAvailableIndustriesProperty(): array
    {
        return $this->getWhitelistedIndustries();
    }

    private function getWhitelistedIndustries(): array
    {
        $allIndustries = app(BenchmarkService::class)->getAvailableIndustries();

        $options = [];
        foreach (self::ALLOWED_INDUSTRIES as $industry) {
            if (in_array($industry, $allIndustries, true)) {
                $options[$industry] = $this->getIndustryLabel($industry);
            }
        }

        return $options;
    }

    public function getAvailableCountriesProperty(): array
    {
        $countries = \App\Models\AdAccount::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->toArray();

        return array_combine($countries, $countries);
    }

    public function getIndustryLabel(string $key): string
    {
        $labels = [
            'automotive'               => 'Automotive',
            'e_commerce_retail'        => 'E-commerce & Retail',
            'ecommerce'                => 'E-commerce & Retail',
            'education'                => 'Education',
            'fashion_luxury'           => 'Fashion & Luxury',
            'finance'                  => 'Finance & Insurance',
            'finance_insurance'        => 'Finance & Insurance',
            'fmcg_cpg'                 => 'FMCG / CPG',
            'food_beverage'            => 'Food & Beverage',
            'government_public_sector' => 'Government & Public Sector',
            'healthcare'               => 'Healthcare',
            'home_garden'              => 'Home & Garden',
            'hospitality_food_services'=> 'Hospitality & Food Services',
            'professional_services'    => 'Professional Services',
            'real_estate'              => 'Real Estate',
            'retail'                   => 'Retail & E-commerce',
            'technology'               => 'Technology',
            'travel'                   => 'Travel & Destinations',
            'travel_destinations'      => 'Travel & Destinations',
            'travel_tourism'           => 'Travel & Tourism',
        ];

        return $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public function formatNumber($num): string
    {
        if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }

    public function formatCurrency(float $amount): string
    {
        if ($amount >= 1000000) return 'SAR ' . number_format($amount / 1000000, 1) . 'M';
        if ($amount >= 1000) return 'SAR ' . number_format($amount / 1000, 1) . 'K';
        return 'SAR ' . number_format($amount, 2);
    }

    public function getObjectivePrimaryLabel(string $objective): string
    {
        return match ($objective) {
            'leads', 'messages', 'calls' => __('pages.calculator.results.leads'),
            'sales', 'conversions', 'catalog_sales' => __('pages.calculator.results.conversions'),
            'traffic', 'link_clicks' => __('pages.calculator.results.website_visits'),
            'video_views' => __('pages.calculator.results.video_views'),
            'engagement', 'page_likes' => __('pages.calculator.results.engagements'),
            'awareness', 'reach', 'impressions' => __('pages.calculator.results.people_reached'),
            'app_installs', 'app_events' => __('pages.calculator.results.app_actions'),
            'store_visits' => __('pages.calculator.results.store_visits'),
            default => __('pages.calculator.primary_result'),
        };
    }

    public function getObjectiveDescription(string $objective): string
    {
        return match ($objective) {
            'leads' => __('pages.calculator.descriptions.leads'),
            'messages' => __('pages.calculator.descriptions.messages'),
            'calls' => __('pages.calculator.descriptions.calls'),
            'sales', 'conversions', 'catalog_sales' => __('pages.calculator.descriptions.sales'),
            'traffic', 'link_clicks' => __('pages.calculator.descriptions.traffic'),
            'video_views' => __('pages.calculator.descriptions.video_views'),
            'engagement', 'page_likes' => __('pages.calculator.descriptions.engagement'),
            'awareness', 'reach', 'impressions' => __('pages.calculator.descriptions.awareness'),
            'app_installs', 'app_events' => __('pages.calculator.descriptions.app_installs'),
            'store_visits' => __('pages.calculator.descriptions.store_visits'),
            default => __('pages.calculator.descriptions.default'),
        };
    }

    public function getChartDataProperty(): array
    {
        if (!$this->hasResult || empty($this->result['predictions'])) {
            return [];
        }

        $labels = [];
        $impressions = [];
        $clicks = [];
        $primary = [];

        foreach ($this->result['predictions'] as $scenario => $prediction) {
            $labels[] = ucfirst($scenario);
            $impressions[] = $prediction['impressions']['value'] ?? 0;
            $clicks[] = $prediction['clicks']['value'] ?? 0;
            $primary[] = $prediction['primary_result']['value'] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => __('kpis.impressions'), 'data' => $impressions, 'backgroundColor' => 'rgba(239, 68, 68, 0.7)'],
                ['label' => __('kpis.clicks'), 'data' => $clicks, 'backgroundColor' => 'rgba(120, 119, 116, 0.1)'],
                ['label' => __('pages.calculator.primary_result'), 'data' => $primary, 'backgroundColor' => 'rgba(212, 212, 210, 0.7)'],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.pages.calculator')
            ->layout('layouts.app', ['title' => __('pages.calculator.title')]);
    }
}
