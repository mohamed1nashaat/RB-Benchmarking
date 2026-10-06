@php
    function seoFmt($num) {
        if (!$num) return '0';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }
    function scoreColor($score) {
        if ($score >= 90) return '#22c55e';
        if ($score >= 50) return '#f59e0b';
        return '#ef4444';
    }
    function scoreBg($score) {
        if ($score >= 90) return 'rgba(52, 101, 56, 0.15)';
        if ($score >= 50) return 'rgba(212, 212, 210,0.15)';
        return 'rgba(239,68,68,0.15)';
    }
@endphp

<div class="seo-report">
    <style>
        .seo-report .score-circle {
            position: relative;
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seo-report .score-circle svg {
            position: absolute;
            inset: 0;
            transform: rotate(-90deg);
        }

        .seo-report .score-value {
            font-size: 22px;
            font-weight: 600;
            line-height: 1;
        }

        .seo-report .cwv-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        @media print {
            .seo-report .report-header { position: static; }
            .seo-report .card { break-inside: avoid; }
        }
    </style>

    {{-- Loading --}}
    @if($loading)
        <div class="py-24 flex justify-center">
            <div class="loading-spinner"></div>
        </div>
    @elseif($error)
        <div class="py-24">
            <div class="empty-state">
                <p class="empty-title">{{ $error }}</p>
            </div>
        </div>
    @elseif($clientData && $reportData)
        {{-- HEADER --}}
        <div class="report-header bg-gray-900 border-b border-white/10 sticky top-0 z-50">
            <div class="max-w-[1400px] mx-auto px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    @if($clientData['logo'])
                        <img src="{{ $clientData['logo'] }}" alt="" class="h-12 w-12 rounded-xl object-cover border border-white/10 shrink-0">
                    @else
                        <div class="h-12 w-12 rounded-xl bg-brand-600 text-white flex items-center justify-center text-lg font-semibold shrink-0">{{ strtoupper(substr($clientData['name'], 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0">
                        <div class="section-title truncate">{{ $clientData['name'] }}</div>
                        <div class="micro-label mt-0.5">SEO Performance Report &middot; {{ $reportData['start_date'] }} to {{ $reportData['end_date'] }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 shrink-0 no-print">
                    <button onclick="window.print()" class="btn-secondary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                        Print
                    </button>
                    <a href="{{ route('client-seo-report-pdf', $clientId) }}" class="btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>{{ __("Download PDF") }}</a>
                </div>
            </div>
        </div>

        {{-- PERIOD SELECTOR --}}
        <div class="filter-bar justify-center no-print">
            @foreach([7 => '7 Days', 30 => '30 Days', 90 => '90 Days', 365 => '1 Year'] as $val => $label)
                <button wire:click="setPeriod({{ $val }})" class="{{ $period === $val ? 'btn-primary' : 'btn-secondary' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- CONTENT --}}
        <div class="max-w-[1400px] mx-auto px-6 py-8">
            {{-- KPI GRID --}}
            <div class="kpi-grid grid gap-3 mb-8">
                @if(isset($reportData['ga4']['summary']))
                    @php $ga4 = $reportData['ga4']['summary']; @endphp
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Total Sessions") }}</div>
                        <div class="kpi-value">{{ seoFmt($ga4['sessions'] ?? 0) }}</div>
                    </div>
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Active Users") }}</div>
                        <div class="kpi-value">{{ seoFmt($ga4['active_users'] ?? 0) }}</div>
                    </div>
                    @if(isset($ga4['organic_sessions']))
                        <div class="kpi-card text-center">
                            <div class="kpi-label">{{ __("Organic Sessions") }}</div>
                            <div class="kpi-value">{{ seoFmt($ga4['organic_sessions']) }}</div>
                        </div>
                    @endif
                    @if(isset($ga4['bounce_rate']))
                        <div class="kpi-card text-center">
                            <div class="kpi-label">{{ __("Bounce Rate") }}</div>
                            <div class="kpi-value">{{ number_format($ga4['bounce_rate'], 1) }}%</div>
                        </div>
                    @endif
                    @if(isset($ga4['avg_session_duration']))
                        <div class="kpi-card text-center">
                            <div class="kpi-label">{{ __("Avg Duration") }}</div>
                            <div class="kpi-value">{{ gmdate('i:s', (int)($ga4['avg_session_duration'] ?? 0)) }}</div>
                        </div>
                    @endif
                @endif

                @if(isset($reportData['search_console']['summary']))
                    @php $sc = $reportData['search_console']['summary']; @endphp
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Search Impressions") }}</div>
                        <div class="kpi-value">{{ seoFmt($sc['total_impressions']) }}</div>
                    </div>
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Search Clicks") }}</div>
                        <div class="kpi-value">{{ seoFmt($sc['total_clicks']) }}</div>
                    </div>
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Avg Position") }}</div>
                        <div class="kpi-value">{{ $sc['avg_position'] }}</div>
                    </div>
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Avg CTR") }}</div>
                        <div class="kpi-value">{{ $sc['avg_ctr'] }}%</div>
                    </div>
                @endif

                @if(isset($reportData['pagespeed_mobile']['scores']['performance']) && $reportData['pagespeed_mobile']['scores']['performance'] > 0)
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Mobile Speed") }}</div>
                        <div class="kpi-value" style="color: {{ scoreColor($reportData['pagespeed_mobile']['scores']['performance']) }}">
                            {{ $reportData['pagespeed_mobile']['scores']['performance'] }}
                        </div>
                    </div>
                @endif

                @if(isset($reportData['pagespeed_desktop']['scores']['performance']) && $reportData['pagespeed_desktop']['scores']['performance'] > 0)
                    <div class="kpi-card text-center">
                        <div class="kpi-label">{{ __("Desktop Speed") }}</div>
                        <div class="kpi-value" style="color: {{ scoreColor($reportData['pagespeed_desktop']['scores']['performance']) }}">
                            {{ $reportData['pagespeed_desktop']['scores']['performance'] }}
                        </div>
                    </div>
                @endif
            </div>

            {{-- PAGESPEED SECTION --}}
            @php
                $psMobile = $reportData['pagespeed_mobile'] ?? null;
                $psDesktop = $reportData['pagespeed_desktop'] ?? null;
                $psHasScores = isset($psMobile['scores']) || isset($psDesktop['scores']);
                $psHasError = ($psMobile['_error'] ?? null) || ($psDesktop['_error'] ?? null);
                $psIsQuotaError = ($psMobile['_error'] ?? null) === 'quota_exceeded' || ($psDesktop['_error'] ?? null) === 'quota_exceeded';
            @endphp
            @if($psHasScores || $psHasError)
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-2 mb-5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span class="text-sm font-semibold tracking-tight text-white">{{ __("PageSpeed Insights") }}</span>
                    </div>

                    @if($psHasError && !$psHasScores)
                        <div class="empty-state py-6">
                            @if($psIsQuotaError)
                                <p class="empty-title">{{ __("API Quota Exceeded") }}</p>
                                <p class="empty-message">{{ __("PageSpeed Insights API quota has been exceeded. Data will refresh automatically when the quota resets.") }}</p>
                            @else
                                <p class="empty-title">{{ __("PageSpeed Data Unavailable") }}</p>
                                <p class="empty-message">{{ __("Unable to retrieve PageSpeed data. This is usually temporary — please try again later.") }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach(['mobile' => $psMobile, 'desktop' => $psDesktop] as $type => $ps)
                            @if($ps && isset($ps['scores']))
                                <div class="text-center">
                                    <h4 class="micro-label mb-4">{{ ucfirst($type) }}</h4>
                                    <div class="flex justify-center gap-6 flex-wrap">
                                        @foreach(['performance' => 'Perf', 'accessibility' => 'A11y', 'best_practices' => 'Best', 'seo' => 'SEO'] as $key => $label)
                                            @php
                                                $val = $ps['scores'][$key] ?? 0;
                                                $color = scoreColor($val);
                                                $circumference = 2 * 3.14159 * 36;
                                                $offset = $circumference * (1 - $val / 100);
                                            @endphp
                                            <div>
                                                <div class="score-circle">
                                                    <svg width="90" height="90" viewBox="0 0 90 90">
                                                        <circle cx="45" cy="45" r="36" fill="none" stroke="#EFEEEC" stroke-width="6"/>
                                                        <circle cx="45" cy="45" r="36" fill="none" stroke="{{ $color }}" stroke-width="6"
                                                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                                                                stroke-linecap="round"/>
                                                    </svg>
                                                    <span class="score-value" style="color: {{ $color }}">{{ $val }}</span>
                                                </div>
                                                <span class="block mt-1.5 text-[10px] text-gray-400 text-center">{{ $label }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Core Web Vitals --}}
                                    @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                        <div class="cwv-grid">
                                            @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                    @php $cwvItem = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                    <div class="bg-gray-950 rounded-lg p-3.5 text-center">
                                                        <div class="text-lg font-semibold mb-0.5" style="color: {{ ($cwvItem['score'] ?? '') === 'good' ? '#22c55e' : (($cwvItem['score'] ?? '') === 'needs_improvement' ? '#f59e0b' : '#ef4444') }}">
                                                            {{ $cwvItem['display'] ?? '-' }}
                                                        </div>
                                                        <div class="micro-label">{{ $cwvLabel }}</div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- GA4 TRAFFIC SOURCES --}}
            @if(isset($reportData['ga4']['traffic_sources']) && count($reportData['ga4']['traffic_sources']) > 0)
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-2 mb-5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/><circle cx="12" cy="12" r="10"/></svg><span class="text-sm font-semibold tracking-tight text-white">{{ __("Traffic Sources") }}</span>
                    </div>
                    @php
                        $totalSessions = array_sum(array_column($reportData['ga4']['traffic_sources'], 'sessions'));
                    @endphp
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Share</th>
                                <th style="width: 120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['ga4']['traffic_sources'] as $source)
                                @php
                                    $share = $totalSessions > 0 ? round(($source['sessions'] / $totalSessions) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $source['channel'] ?? $source['source'] ?? 'Unknown' }}</td>
                                    <td class="text-end">{{ seoFmt($source['sessions']) }}</td>
                                    <td class="text-end">{{ $share }}%</td>
                                    <td>
                                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden min-w-[80px]">
                                            <div class="h-full bg-gray-900 rounded-full transition-[width] duration-500" style="width: {{ $share }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- TWO COLUMN: TOP PAGES + TOP QUERIES --}}
            <div class="grid md:grid-cols-2 gap-6 items-start mb-6">
                {{-- Top Pages --}}
                @if(isset($reportData['search_console']['pages']) && count($reportData['search_console']['pages']) > 0)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="text-sm font-semibold tracking-tight text-white">{{ __("Top Pages") }}</span>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th class="text-end">Clicks</th>
                                    <th class="text-end">Impr.</th>
                                    <th class="text-end">Pos.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($reportData['search_console']['pages'], 0, 15) as $page)
                                    <tr>
                                        <td class="max-w-[300px] truncate" title="{{ $page['key'] ?? $page['page'] ?? '' }}">
                                            {{ basename($page['key'] ?? $page['page'] ?? '') ?: '/' }}
                                        </td>
                                        <td class="text-end">{{ seoFmt($page['clicks'] ?? 0) }}</td>
                                        <td class="text-end">{{ seoFmt($page['impressions'] ?? 0) }}</td>
                                        <td class="text-end">{{ number_format($page['position'] ?? 0, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Top Search Queries --}}
                @if(isset($reportData['search_console']['queries']) && count($reportData['search_console']['queries']) > 0)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><span class="text-sm font-semibold tracking-tight text-white">{{ __("Top Search Queries") }}</span>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th class="text-end">Clicks</th>
                                    <th class="text-end">Impr.</th>
                                    <th class="text-end">CTR</th>
                                    <th class="text-end">Pos.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($reportData['search_console']['queries'], 0, 15) as $query)
                                    <tr>
                                        <td class="max-w-[300px] truncate">{{ $query['key'] ?? $query['query'] ?? '' }}</td>
                                        <td class="text-end">{{ seoFmt($query['clicks'] ?? 0) }}</td>
                                        <td class="text-end">{{ seoFmt($query['impressions'] ?? 0) }}</td>
                                        <td class="text-end">{{ number_format(($query['ctr'] ?? 0) * 100, 1) }}%</td>
                                        <td class="text-end">{{ number_format($query['position'] ?? 0, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- GA4 TOP PAGES --}}
            @if(isset($reportData['ga4']['top_pages']) && count($reportData['ga4']['top_pages']) > 0)
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-2 mb-5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 002 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span class="text-sm font-semibold tracking-tight text-white">{{ __("Top Pages (Analytics)") }}</span>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th class="text-end">Views</th>
                                <th class="text-end">Users</th>
                                <th class="text-end">{{ __("Avg Time") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($reportData['ga4']['top_pages'], 0, 15) as $page)
                                <tr>
                                    <td class="max-w-[300px] truncate" title="{{ $page['page_path'] ?? $page['page'] ?? '' }}">
                                        {{ $page['page_path'] ?? $page['page'] ?? '' }}
                                    </td>
                                    <td class="text-end">{{ seoFmt($page['views'] ?? $page['screenPageViews'] ?? 0) }}</td>
                                    <td class="text-end">{{ seoFmt($page['users'] ?? $page['activeUsers'] ?? 0) }}</td>
                                    <td class="text-end">
                                        {{ isset($page['avg_time']) ? gmdate('i:s', (int)$page['avg_time']) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- GA4 DEVICES --}}
            @if(isset($reportData['ga4']['devices']) && count($reportData['ga4']['devices']) > 0)
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-2 mb-5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-sm font-semibold tracking-tight text-white">Devices & Browsers</span>
                    </div>
                    @php
                        $totalDevSessions = array_sum(array_column($reportData['ga4']['devices'], 'sessions'));
                    @endphp
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __("Device / Browser") }}</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Share</th>
                                <th style="width: 120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['ga4']['devices'] as $device)
                                @php
                                    $devShare = $totalDevSessions > 0 ? round(($device['sessions'] / $totalDevSessions) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td>{{ ucfirst($device['device_category'] ?? $device['browser'] ?? 'Unknown') }}</td>
                                    <td class="text-end">{{ seoFmt($device['sessions']) }}</td>
                                    <td class="text-end">{{ $devShare }}%</td>
                                    <td>
                                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden min-w-[80px]">
                                            <div class="h-full bg-gray-900 rounded-full transition-[width] duration-500" style="width: {{ $devShare }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- NO DATA STATE --}}
            @if(
                !isset($reportData['search_console']) &&
                !isset($reportData['ga4']) &&
                !isset($reportData['pagespeed_mobile']) &&
                !isset($reportData['pagespeed_desktop'])
            )
                <div class="card p-6 mb-6">
                    <div class="empty-state">
                        <p class="empty-title">{{ __("No SEO data available") }}</p>
                        <p class="empty-message">{{ __("Configure SEO properties in the client dashboard to generate this report.") }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- FOOTER --}}
        <div class="border-t border-white/10 py-8 px-6 text-center">
            <p class="text-xs text-gray-400">Generated on {{ now()->format('F j, Y \a\t g:i A') }} &middot; <span class="font-semibold text-white">{{ __("RB Benchmarks") }}</span></p>
            @if($clientData['website'])
                <p class="text-xs text-gray-400 mt-1">{{ $clientData['website'] }}</p>
            @endif
        </div>
    @endif
</div>
