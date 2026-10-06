<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-xl border border-green-500/20 bg-green-500/10 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm font-medium text-green-400">{{ session('message') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
            <div class="flex">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                <p class="ms-3 text-sm font-medium text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            <span class="ms-3 text-gray-300">{{ __('pages.campaign_metrics.loading_metrics') }}</span>
        </div>
    @elseif($error)
        {{-- Error State --}}
        <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
            <div class="flex">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-400" />
                <div class="ms-3">
                    <h3 class="text-sm font-semibold text-red-400">{{ __('pages.campaign_metrics.error_loading_metrics') }}</h3>
                    <p class="mt-1 text-sm text-red-400/80">{{ $error }}</p>
                    <button wire:click="loadMetrics" class="mt-2 inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-900/70 text-red-400 hover:bg-gray-900">
                        {{ __('common.try_again') }}
                    </button>
                </div>
            </div>
        </div>
    @elseif(!$campaign)
        {{-- Campaign Not Found --}}
        <div class="empty-state">
            <p class="empty-title">{{ __('pages.campaign_metrics.campaign_not_found') }}</p>
            <a href="{{ route('ad-accounts') }}" wire:navigate class="btn-secondary mt-4">
                <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />
                {{ __('pages.ad_accounts.back_to_accounts') }}
            </a>
        </div>
    @elseif(!$this->hasMetricsData)
        {{-- No Metrics Data State --}}
        <div class="flex items-center gap-x-4 mb-6">
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="btn-secondary">
                    <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />
                    {{ __('common.back') }}
                </a>
            @endif
            <div>
                <h1 class="section-title">{{ $campaign['name'] }}</h1>
            </div>
        </div>

        <div class="card empty-state">
            <x-heroicon-o-chart-bar class="h-16 w-16 text-gray-300 mb-4" />
            <h3 class="empty-title">{{ __('pages.campaign_metrics.no_metrics_data_title') }}</h3>
            <p class="empty-message mb-6">
                {{ __('pages.campaign_metrics.no_metrics_data_description') }}
            </p>
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="btn-secondary">
                    <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />{{ __('pages.campaign_metrics.back_to_account') }}</a>
            @endif
        </div>
    @else
        {{-- Header with Back Button --}}
        <div class="flex items-center gap-x-4">
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="btn-secondary">
                    <x-heroicon-o-arrow-left class="h-4 w-4 me-2" />
                    {{ __('common.back') }}
                </a>
            @endif

            <div>
                <h1 class="section-title">{{ $campaign['name'] }}</h1>
                <div class="flex items-center gap-x-2 mt-1">
                    <span class="text-sm text-gray-500">{{ $adAccount['account_name'] ?? '' }}</span>
                    <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-white/8 text-gray-400' }}">
                        {{ $campaign['status'] ?? 'unknown' }}
                    </span>
                    @if(!empty($campaign['funnel_stage']))
                        <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-brand-500/15 text-brand-400">
                            {{ $campaign['funnel_stage'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Date Filter Bar --}}
        <div class="filter-bar">
            <x-date-range-picker
                wire-model-from="filterStartDate"
                wire-model-to="filterEndDate"
                :label="__('Date Range')"
                align="left"
            />
            <div class="ms-auto">
                <button wire:click="loadMetrics" wire:loading.attr="disabled"
                    class="btn-secondary disabled:opacity-50">
                    <x-heroicon-o-arrow-path class="h-4 w-4 me-2" wire:loading.class="animate-spin" wire:target="loadMetrics" />
                    <span wire:loading.remove wire:target="loadMetrics">{{ __('buttons.refresh') }}</span>
                    <span wire:loading wire:target="loadMetrics">{{ __('common.loading') }}</span>
                </button>
            </div>
        </div>

        {{-- Hero Metric Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Spend Card --}}
            <div class="kpi-card">
                <div class="flex items-center justify-between">
                    <p class="kpi-label">{{ __('pages.campaign_metrics.total_spend') }}</p>
                    <div class="p-2 bg-white/8 rounded-lg">
                        <x-heroicon-o-currency-dollar class="h-5 w-5 text-gray-400" />
                    </div>
                </div>
                <p class="kpi-value mt-2">
                    @php
                        $spend = $metrics['spend'] ?? 0;
                        if ($spend >= 1000000) {
                            $formattedSpend = 'SR ' . number_format($spend / 1000000, 1) . 'M';
                        } elseif ($spend >= 1000) {
                            $formattedSpend = 'SR ' . number_format($spend / 1000, 1) . 'K';
                        } else {
                            $formattedSpend = 'SR ' . number_format($spend, 0);
                        }
                    @endphp
                    {{ $formattedSpend }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if($filterStartDate && $filterEndDate)
                        {{ $filterStartDate }} - {{ $filterEndDate }}
                    @else
                        {{ __('date.all_time') }}
                    @endif
                </p>
            </div>

            {{-- Impressions Card --}}
            <div class="kpi-card">
                <div class="flex items-center justify-between">
                    <p class="kpi-label">{{ __('alerts.metrics.impressions') }}</p>
                    <div class="p-2 bg-brand-500/10 rounded-lg">
                        <x-heroicon-o-eye class="h-5 w-5 text-brand-400" />
                    </div>
                </div>
                <p class="kpi-value mt-2">
                    @php
                        $impressions = $metrics['impressions'] ?? 0;
                        if ($impressions >= 1000000) {
                            $formattedImpressions = number_format($impressions / 1000000, 1) . 'M';
                        } elseif ($impressions >= 1000) {
                            $formattedImpressions = number_format($impressions / 1000, 1) . 'K';
                        } else {
                            $formattedImpressions = number_format($impressions);
                        }
                    @endphp
                    {{ $formattedImpressions }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ __('pages.campaign_metrics.total_views') }}</p>
            </div>

            {{-- Clicks/Leads Card --}}
            <div class="kpi-card">
                @php
                    $isLeadsCampaign = ($campaign['objective'] ?? '') === 'leads';
                    $clicksOrLeads = $isLeadsCampaign ? ($metrics['leads'] ?? 0) : ($metrics['clicks'] ?? 0);
                    if ($clicksOrLeads >= 1000000) {
                        $formattedClicksLeads = number_format($clicksOrLeads / 1000000, 1) . 'M';
                    } elseif ($clicksOrLeads >= 1000) {
                        $formattedClicksLeads = number_format($clicksOrLeads / 1000, 1) . 'K';
                    } else {
                        $formattedClicksLeads = number_format($clicksOrLeads);
                    }
                @endphp
                <div class="flex items-center justify-between">
                    <p class="kpi-label">{{ $isLeadsCampaign ? __('alerts.metrics.leads') : __('alerts.metrics.clicks') }}</p>
                    <div class="p-2 bg-amber-500/10 rounded-lg">
                        <x-heroicon-o-cursor-arrow-rays class="h-5 w-5 text-amber-400" />
                    </div>
                </div>
                <p class="kpi-value mt-2">{{ $formattedClicksLeads }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $isLeadsCampaign ? __('pages.campaign_metrics.generated') : __('pages.benchmarks.total_clicks') }}</p>
            </div>

            {{-- CTR/CPL Card --}}
            <div class="kpi-card">
                <div class="flex items-center justify-between">
                    <p class="kpi-label">{{ $isLeadsCampaign ? __('pages.campaign_metrics.cost_per_lead') : __('kpis.ctr') }}</p>
                    <div class="p-2 bg-green-500/10 rounded-lg">
                        <x-heroicon-o-chart-bar class="h-5 w-5 text-green-400" />
                    </div>
                </div>
                <p class="kpi-value mt-2">
                    @if($isLeadsCampaign)
                        @php
                            $leads = $metrics['leads'] ?? 0;
                            $cpl = $leads > 0 ? ($metrics['spend'] ?? 0) / $leads : 0;
                        @endphp
                        SR {{ number_format($cpl, 2) }}
                    @else
                        {{ number_format($metrics['ctr'] ?? 0, 2) }}%
                    @endif
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ $isLeadsCampaign ? __('pages.campaign_metrics.per_lead') : __('alerts.metrics.ctr_full') }}</p>
            </div>
        </div>

        {{-- Performance Metrics Grid --}}
        <div class="card p-6">
            <h3 class="section-title mb-4">{{ __('pages.campaign_metrics.performance_metrics') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if(($metrics['cpc'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.cpc') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">SR {{ number_format($metrics['cpc'] ?? 0, 2) }}</p>
                    </div>
                @endif

                @if(($metrics['cpm'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.cpm') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">SR {{ number_format($metrics['cpm'] ?? 0, 2) }}</p>
                    </div>
                @endif

                @if(($metrics['reach'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.reach') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">
                            @php
                                $reach = $metrics['reach'] ?? 0;
                                if ($reach >= 1000000) {
                                    $formattedReach = number_format($reach / 1000000, 1) . 'M';
                                } elseif ($reach >= 1000) {
                                    $formattedReach = number_format($reach / 1000, 1) . 'K';
                                } else {
                                    $formattedReach = number_format($reach);
                                }
                            @endphp
                            {{ $formattedReach }}
                        </p>
                    </div>
                @endif

                <div class="bg-gray-950 rounded-xl p-4">
                    <p class="text-sm text-gray-500">{{ __('alerts.metrics.conversions') }}</p>
                    <p class="text-xl font-semibold tracking-tight tabular-nums text-white">{{ number_format($metrics['conversions'] ?? 0) }}</p>
                </div>

                @if(($metrics['roas'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.roas') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">{{ number_format($metrics['roas'] ?? 0, 2) }}x</p>
                    </div>
                @endif

                @if(($metrics['cvr'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.cvr') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">{{ number_format($metrics['cvr'] ?? 0, 2) }}%</p>
                    </div>
                @endif

                @if(($metrics['frequency'] ?? 0) > 0)
                    <div class="bg-gray-950 rounded-xl p-4">
                        <p class="text-sm text-gray-500">{{ __('kpis.frequency') }}</p>
                        <p class="text-xl font-semibold tracking-tight tabular-nums text-white">{{ number_format($metrics['frequency'] ?? 0, 1) }}</p>
                    </div>
                @endif

                <div class="bg-gray-950 rounded-xl p-4">
                    <p class="text-sm text-gray-500">{{ __('labels.objective') }}</p>
                    <p class="text-xl font-semibold tracking-tight tabular-nums text-white capitalize">{{ str_replace('_', ' ', $campaign['objective'] ?? __('status.na')) }}</p>
                </div>
            </div>
        </div>

        {{-- Campaign Details Card --}}
        <div class="card p-6">
            <h3 class="section-title mb-4">{{ __('pages.campaign_metrics.campaign_details') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Sub-Category --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.sub_category') }}</label>
                    <select wire:model="editSubIndustry" wire:change="updateCampaignDetails"
                        class="input-field"
                        title="{{ __('tooltips.sub_categories_source') }}">
                        <option value="">{{ __('pages.campaign_metrics.select_sub_category') }}</option>
                        @if($editSubIndustry && !in_array($editSubIndustry, $availableSubIndustries))
                            <option value="{{ $editSubIndustry }}" selected>{{ $editSubIndustry }} {{ __('common.legacy') }}</option>
                        @endif
                        @foreach($availableSubIndustries as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Objective --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.objective') }}</label>
                    <p class="text-sm text-white py-2 capitalize">
                        {{ $campaign['objective'] ? str_replace('_', ' ', $campaign['objective']) : __('common.not_set') }}
                    </p>
                </div>

                {{-- Funnel Stage --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.funnel_stage') }}</label>
                    <p class="text-sm text-white py-2">
                        @if(!empty($campaign['funnel_stage']))
                            <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-brand-500/15 text-brand-400">
                                {{ $campaign['funnel_stage'] }}
                            </span>
                        @else
                            <span class="text-gray-400">{{ __('common.not_set') }}</span>
                        @endif
                    </p>
                </div>

                {{-- Platform --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.platform') }}</label>
                    <p class="text-sm text-white py-2 capitalize">{{ $adAccount['platform'] ?? __('common.unknown') }}</p>
                </div>

                {{-- Status --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.status') }}</label>
                    <p class="text-sm py-2">
                        <span class="rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-white/8 text-gray-400' }}">
                            {{ $campaign['status'] ?? __('common.unknown') }}
                        </span>
                    </p>
                </div>

                {{-- Created --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.created') }}</label>
                    <p class="text-sm text-white py-2">
                        @if(!empty($campaign['created_at']))
                            {{ \Carbon\Carbon::parse($campaign['created_at'])->format('M d, Y') }}
                        @else
                            {{ __('status.na') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Daily Performance Chart --}}
        @if(!empty($dailyData))
            <div class="card p-6" wire:ignore>
                <h3 class="section-title mb-4">{{ __('pages.campaign_metrics.daily_performance') }}</h3>
                <div class="h-80" x-data="{
                    chart: null,
                    dailyData: @js($dailyData),
                    init() {
                        this.renderChart();
                    },
                    renderChart() {
                        const ctx = this.$refs.chartCanvas.getContext('2d');
                        if (this.chart) {
                            this.chart.destroy();
                        }

                        const labels = this.dailyData.map(d => d.date);
                        const spendData = this.dailyData.map(d => d.spend);
                        const clicksData = this.dailyData.map(d => d.clicks);
                        const impressionsData = this.dailyData.map(d => d.impressions);

                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: '{{ __('pages.campaign_metrics.chart.spend_sar') }}',
                                        data: spendData,
                                        borderColor: 'rgb(239, 68, 68)',
                                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                        fill: true,
                                        tension: 0.4,
                                        yAxisID: 'y',
                                    },
                                    {
                                        label: '{{ __('alerts.metrics.clicks') }}',
                                        data: clicksData,
                                        borderColor: 'rgb(168, 168, 164)',
                                        backgroundColor: 'rgba(168, 168, 164, 0.15)',
                                        fill: false,
                                        tension: 0.4,
                                        yAxisID: 'y1',
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                if (context.dataset.yAxisID === 'y') {
                                                    label += 'SR ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                                } else {
                                                    label += context.parsed.y.toLocaleString();
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        display: true,
                                        ticks: {
                                            maxRotation: 45,
                                            maxTicksLimit: 15,
                                        }
                                    },
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        title: {
                                            display: true,
                                            text: '{{ __('pages.campaign_metrics.chart.spend_sar') }}'
                                        },
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                                                return value;
                                            }
                                        }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        title: {
                                            display: true,
                                            text: '{{ __('alerts.metrics.clicks') }}'
                                        },
                                        beginAtZero: true,
                                        grid: {
                                            drawOnChartArea: false,
                                        },
                                    }
                                }
                            }
                        });
                    }
                }">
                    <canvas x-ref="chartCanvas"></canvas>
                </div>
            </div>
        @endif
    @endif
</div>
