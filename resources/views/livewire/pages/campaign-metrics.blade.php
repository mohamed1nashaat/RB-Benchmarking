<div class="space-y-6">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('message') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-red-400" />
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Loading State --}}
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            <span class="ml-3 text-gray-600">{{ __('pages.campaign_metrics.loading_metrics') }}</span>
        </div>
    @elseif($error)
        {{-- Error State --}}
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-400" />
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('pages.campaign_metrics.error_loading_metrics') }}</h3>
                    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
                    <button wire:click="loadMetrics" class="mt-2 text-sm bg-red-100 px-3 py-1 rounded-md text-red-800 hover:bg-red-200">
                        {{ __('common.try_again') }}
                    </button>
                </div>
            </div>
        </div>
    @elseif(!$campaign)
        {{-- Campaign Not Found --}}
        <div class="text-center py-12">
            <p class="text-gray-500">Campaign not found.</p>
            <a href="{{ route('ad-accounts') }}" wire:navigate class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-800">
                <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                Back to accounts
            </a>
        </div>
    @elseif(!$this->hasMetricsData)
        {{-- No Metrics Data State --}}
        <div class="flex items-center space-x-4 mb-6">
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                    {{ __('common.back') }}
                </a>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $campaign['name'] }}</h1>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-8 text-center border border-gray-200">
            <x-heroicon-o-chart-bar class="mx-auto h-16 w-16 text-gray-300 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Metrics Data Available</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">
                This campaign doesn't have any performance metrics recorded yet.
            </p>
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                    Back to Account
                </a>
            @endif
        </div>
    @else
        {{-- Header with Back Button --}}
        <div class="flex items-center space-x-4">
            @if($adAccount)
                <a href="{{ route('ad-account-detail', ['id' => $adAccount['id']]) }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-arrow-left class="h-4 w-4 mr-2" />
                    {{ __('common.back') }}
                </a>
            @endif

            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $campaign['name'] }}</h1>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-sm text-gray-500">{{ $adAccount['account_name'] ?? '' }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $campaign['status'] ?? 'unknown' }}
                    </span>
                    @if(!empty($campaign['funnel_stage']))
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                            {{ $campaign['funnel_stage'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Date Filter Bar --}}
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 px-4 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <x-date-range-picker
                    wire-model-from="filterStartDate"
                    wire-model-to="filterEndDate"
                    :label="__('Date Range')"
                    align="left"
                />
                <div class="ml-auto">
                    <button wire:click="loadMetrics" wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                        <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" wire:loading.class="animate-spin" wire:target="loadMetrics" />
                        <span wire:loading.remove wire:target="loadMetrics">Refresh</span>
                        <span wire:loading wire:target="loadMetrics">Loading...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Hero Metric Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Spend Card --}}
            <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Total Spend</p>
                    <div class="p-2 bg-primary-50 rounded-lg">
                        <x-heroicon-o-currency-dollar class="h-5 w-5 text-primary-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">
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
                        All time
                    @endif
                </p>
            </div>

            {{-- Impressions Card --}}
            <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Impressions</p>
                    <div class="p-2 bg-emerald-50 rounded-lg">
                        <x-heroicon-o-eye class="h-5 w-5 text-emerald-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">
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
                <p class="text-xs text-gray-500 mt-1">Total views</p>
            </div>

            {{-- Clicks/Leads Card --}}
            <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
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
                    <p class="text-sm font-medium text-gray-500">{{ $isLeadsCampaign ? 'Leads' : 'Clicks' }}</p>
                    <div class="p-2 bg-amber-50 rounded-lg">
                        <x-heroicon-o-cursor-arrow-rays class="h-5 w-5 text-amber-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $formattedClicksLeads }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $isLeadsCampaign ? 'Generated' : 'Total clicks' }}</p>
            </div>

            {{-- CTR/CPL Card --}}
            <div class="bg-white shadow-sm rounded-lg p-5 border border-gray-200">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">{{ $isLeadsCampaign ? 'Cost/Lead' : 'CTR' }}</p>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <x-heroicon-o-chart-bar class="h-5 w-5 text-gray-600" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">
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
                <p class="text-xs text-gray-500 mt-1">{{ $isLeadsCampaign ? 'Per lead' : 'Click-through rate' }}</p>
            </div>
        </div>

        {{-- Performance Metrics Grid --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Metrics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if(($metrics['cpc'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">CPC</p>
                        <p class="text-xl font-semibold text-gray-900">SR {{ number_format($metrics['cpc'] ?? 0, 2) }}</p>
                    </div>
                @endif

                @if(($metrics['cpm'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">CPM</p>
                        <p class="text-xl font-semibold text-gray-900">SR {{ number_format($metrics['cpm'] ?? 0, 2) }}</p>
                    </div>
                @endif

                @if(($metrics['reach'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Reach</p>
                        <p class="text-xl font-semibold text-gray-900">
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

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Conversions</p>
                    <p class="text-xl font-semibold text-gray-900">{{ number_format($metrics['conversions'] ?? 0) }}</p>
                </div>

                @if(($metrics['roas'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">ROAS</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($metrics['roas'] ?? 0, 2) }}x</p>
                    </div>
                @endif

                @if(($metrics['cvr'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">CVR</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($metrics['cvr'] ?? 0, 2) }}%</p>
                    </div>
                @endif

                @if(($metrics['frequency'] ?? 0) > 0)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Frequency</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($metrics['frequency'] ?? 0, 1) }}</p>
                    </div>
                @endif

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Objective</p>
                    <p class="text-xl font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $campaign['objective'] ?? 'N/A') }}</p>
                </div>
            </div>
        </div>

        {{-- Campaign Details Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Category --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Category</label>
                    <select wire:model="editSubIndustry" wire:change="updateCampaignDetails"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Select category</option>
                        @foreach($availableSubIndustries as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Objective --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Objective</label>
                    <p class="text-sm text-gray-900 py-2 capitalize">
                        {{ $campaign['objective'] ? str_replace('_', ' ', $campaign['objective']) : 'Not set' }}
                    </p>
                </div>

                {{-- Funnel Stage --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Funnel Stage</label>
                    <p class="text-sm text-gray-900 py-2">
                        @if(!empty($campaign['funnel_stage']))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                {{ $campaign['funnel_stage'] }}
                            </span>
                        @else
                            <span class="text-gray-400">Not set</span>
                        @endif
                    </p>
                </div>

                {{-- Platform --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Platform</label>
                    <p class="text-sm text-gray-900 py-2 capitalize">{{ $adAccount['platform'] ?? 'Unknown' }}</p>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                    <p class="text-sm py-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($campaign['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $campaign['status'] ?? 'Unknown' }}
                        </span>
                    </p>
                </div>

                {{-- Created --}}
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Created</label>
                    <p class="text-sm text-gray-900 py-2">
                        @if(!empty($campaign['created_at']))
                            {{ \Carbon\Carbon::parse($campaign['created_at'])->format('M d, Y') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Daily Performance Chart --}}
        @if(!empty($dailyData))
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" wire:ignore>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Performance</h3>
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
                                        label: 'Spend (SAR)',
                                        data: spendData,
                                        borderColor: 'rgb(99, 102, 241)',
                                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                        fill: true,
                                        tension: 0.4,
                                        yAxisID: 'y',
                                    },
                                    {
                                        label: 'Clicks',
                                        data: clicksData,
                                        borderColor: 'rgb(245, 158, 11)',
                                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
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
                                            text: 'Spend (SAR)'
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
                                            text: 'Clicks'
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
