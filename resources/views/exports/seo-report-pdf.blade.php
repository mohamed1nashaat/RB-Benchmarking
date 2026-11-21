<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $client->name }} - SEO Report</title>
    <style>
        @page {
            margin: 15mm 12mm 20mm 12mm;
        }

        @page :first {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }

        .page-break { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }

        /* ===== COVER PAGE ===== */
        .cover-page {
            height: 100%;
            min-height: 297mm;
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            display: table;
            width: 100%;
        }

        .cover-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 40mm 20mm;
        }

        .cover-client-name {
            font-size: 40pt;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
            line-height: 1.1;
        }

        .cover-title {
            font-size: 20pt;
            font-weight: 300;
            color: #d4af37;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 35px;
        }

        .cover-meta {
            font-size: 11pt;
            color: #9ca3af;
            margin-bottom: 6px;
        }

        .cover-meta strong { color: #ffffff; }

        .cover-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #d4af37 0%, #e4bf47 50%, #d4af37 100%);
        }

        /* ===== HEADER ===== */
        .pdf-header {
            background: linear-gradient(135deg, #1f2937 0%, #3F3F3C 100%);
            color: #fff;
            padding: 10px 15mm;
            margin: -15mm -12mm 15px -12mm;
            display: table;
            width: calc(100% + 24mm);
        }

        .pdf-header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .pdf-header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-size: 8pt;
            color: #9ca3af;
        }

        .pdf-header .client-name {
            font-size: 13pt;
            font-weight: 700;
        }

        .pdf-header .report-type {
            font-size: 8pt;
            color: #d4af37;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== SECTIONS ===== */
        .section-title {
            font-size: 13pt;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #d4af37;
        }

        /* ===== KPI GRID ===== */
        .kpi-grid {
            width: 100%;
            margin-bottom: 20px;
        }

        .kpi-grid td {
            width: 25%;
            padding: 4px;
        }

        .kpi-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 10px;
            text-align: center;
        }

        .kpi-box .value {
            font-size: 18pt;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }

        .kpi-box .label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-top: 2px;
        }

        .kpi-box .value.green { color: #16a34a; }
        .kpi-box .value.orange { color: #d97706; }
        .kpi-box .value.red { color: #dc2626; }

        /* ===== PAGESPEED ===== */
        .pagespeed-grid {
            width: 100%;
            margin-bottom: 20px;
        }

        .pagespeed-grid td {
            width: 50%;
            padding: 4px;
            vertical-align: top;
        }

        .ps-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px;
        }

        .ps-card h4 {
            font-size: 10pt;
            font-weight: 600;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 10px;
            text-align: center;
        }

        .ps-scores {
            width: 100%;
        }

        .ps-scores td {
            width: 25%;
            text-align: center;
            padding: 6px 4px;
        }

        .ps-score-value {
            font-size: 20pt;
            font-weight: 700;
            line-height: 1.2;
        }

        .ps-score-label {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }

        .cwv-row {
            width: 100%;
            margin-top: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .cwv-row td {
            text-align: center;
            padding: 8px 6px;
            border-right: 1px solid #f1f5f9;
        }

        .cwv-row td:last-child { border-right: none; }

        .cwv-value {
            font-size: 11pt;
            font-weight: 700;
        }

        .cwv-label {
            font-size: 7pt;
            color: #94a3b8;
            text-transform: uppercase;
        }

        /* ===== TABLES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8pt;
        }

        .data-table thead th {
            padding: 8px 10px;
            font-size: 7pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #ffffff;
            background: #3F3F3C;
            text-align: left;
        }

        .data-table thead th:first-child { border-radius: 4px 0 0 0; }
        .data-table thead th:last-child { border-radius: 0 4px 0 0; }

        .data-table tbody td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #3F3F3C;
        }

        .data-table tbody tr:nth-child(even) { background: #f8fafc; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ===== TWO COL ===== */
        .two-col-table {
            width: 100%;
        }

        .two-col-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }

        /* ===== PROGRESS BAR ===== */
        .progress-bar-container {
            width: 60px;
            height: 5px;
            background: #e2e8f0;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            background: #d4af37;
        }

        /* ===== FOOTER ===== */
        .pdf-footer {
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .pdf-footer .brand { color: #d4af37; font-weight: 700; }
    </style>
</head>
<body>
@php
    function pdfFmt($num) {
        if (!$num) return '0';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }
    function pdfScoreColor($score) {
        if ($score >= 90) return 'green';
        if ($score >= 50) return 'orange';
        return 'red';
    }
    function pdfScoreHex($score) {
        if ($score >= 90) return '#16a34a';
        if ($score >= 50) return '#d97706';
        return '#dc2626';
    }
@endphp

{{-- COVER PAGE --}}
<div class="cover-page">
    <div class="cover-content">
        <div class="cover-client-name">{{ $client->name }}</div>
        <div class="cover-title">{{ __("SEO Performance Report") }}</div>
        <div class="cover-meta"><strong>Period:</strong> {{ $startDate }} to {{ $endDate }}</div>
        @if($client->website)
            <div class="cover-meta"><strong>Website:</strong> {{ $client->website }}</div>
        @endif
        @if($client->industry)
            <div class="cover-meta"><strong>Industry:</strong> {{ ucwords(str_replace(['_', '-'], ' ', $client->industry)) }}</div>
        @endif
        <div class="cover-meta" style="margin-top: 20px; color: #64748b;">
            Generated on {{ now()->format('F j, Y') }}
        </div>
    </div>
</div>

<div class="page-break"></div>

{{-- PAGE 2: KPIs + PageSpeed --}}
<div class="pdf-header">
    <div class="pdf-header-left">
        <div class="client-name">{{ $client->name }}</div>
        <div class="report-type">{{ __("SEO Performance Report") }}</div>
    </div>
    <div class="pdf-header-right">
        {{ $startDate }} — {{ $endDate }}
    </div>
</div>

{{-- KPI GRID --}}
<div class="section-title">{{ __("Key Performance Indicators") }}</div>

<table class="kpi-grid">
    <tr>
        @if(isset($report['ga4']['summary']))
            @php $ga4 = $report['ga4']['summary']; @endphp
            <td>
                <div class="kpi-box">
                    <div class="value">{{ pdfFmt($ga4['sessions'] ?? 0) }}</div>
                    <div class="label">{{ __("Total Sessions") }}</div>
                </div>
            </td>
            <td>
                <div class="kpi-box">
                    <div class="value">{{ pdfFmt($ga4['active_users'] ?? 0) }}</div>
                    <div class="label">{{ __("Active Users") }}</div>
                </div>
            </td>
        @endif
        @if(isset($report['search_console']['summary']))
            @php $sc = $report['search_console']['summary']; @endphp
            <td>
                <div class="kpi-box">
                    <div class="value">{{ pdfFmt($sc['total_impressions']) }}</div>
                    <div class="label">{{ __("Search Impressions") }}</div>
                </div>
            </td>
            <td>
                <div class="kpi-box">
                    <div class="value">{{ pdfFmt($sc['total_clicks']) }}</div>
                    <div class="label">{{ __("Search Clicks") }}</div>
                </div>
            </td>
        @endif
    </tr>
    <tr>
        @if(isset($report['search_console']['summary']))
            <td>
                <div class="kpi-box">
                    <div class="value">{{ $report['search_console']['summary']['avg_position'] }}</div>
                    <div class="label">{{ __("Avg Position") }}</div>
                </div>
            </td>
            <td>
                <div class="kpi-box">
                    <div class="value">{{ $report['search_console']['summary']['avg_ctr'] }}%</div>
                    <div class="label">{{ __("Avg CTR") }}</div>
                </div>
            </td>
        @endif
        @if(isset($report['pagespeed_mobile']['scores']['performance']) && $report['pagespeed_mobile']['scores']['performance'] > 0)
            <td>
                <div class="kpi-box">
                    <div class="value {{ pdfScoreColor($report['pagespeed_mobile']['scores']['performance']) }}">{{ $report['pagespeed_mobile']['scores']['performance'] }}</div>
                    <div class="label">{{ __("Mobile Speed") }}</div>
                </div>
            </td>
        @endif
        @if(isset($report['pagespeed_desktop']['scores']['performance']) && $report['pagespeed_desktop']['scores']['performance'] > 0)
            <td>
                <div class="kpi-box">
                    <div class="value {{ pdfScoreColor($report['pagespeed_desktop']['scores']['performance']) }}">{{ $report['pagespeed_desktop']['scores']['performance'] }}</div>
                    <div class="label">{{ __("Desktop Speed") }}</div>
                </div>
            </td>
        @endif
    </tr>
</table>

{{-- PAGESPEED SECTION --}}
@php
    $pdfPsMobile = $report['pagespeed_mobile'] ?? null;
    $pdfPsDesktop = $report['pagespeed_desktop'] ?? null;
    $pdfPsHasScores = isset($pdfPsMobile['scores']) || isset($pdfPsDesktop['scores']);
    $pdfPsHasError = ($pdfPsMobile['_error'] ?? null) || ($pdfPsDesktop['_error'] ?? null);
    $pdfPsIsQuotaError = ($pdfPsMobile['_error'] ?? null) === 'quota_exceeded' || ($pdfPsDesktop['_error'] ?? null) === 'quota_exceeded';
@endphp
@if($pdfPsHasScores || $pdfPsHasError)
    <div class="section-title">{{ __("PageSpeed Insights") }}</div>

    @if($pdfPsHasError && !$pdfPsHasScores)
        <div style="text-align: center; padding: 20px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
            @if($pdfPsIsQuotaError)
                <div style="font-size: 11pt; font-weight: 600; color: #d97706; margin-bottom: 4px;">{{ __("API Quota Exceeded") }}</div>
                <div style="font-size: 8pt; color: #64748b;">{{ __("PageSpeed Insights API quota has been exceeded. Data will refresh automatically when the quota resets.") }}</div>
            @else
                <div style="font-size: 11pt; font-weight: 600; color: #dc2626; margin-bottom: 4px;">{{ __("PageSpeed Data Unavailable") }}</div>
                <div style="font-size: 8pt; color: #64748b;">{{ __("Unable to retrieve PageSpeed data. This is usually temporary — please try again later.") }}</div>
            @endif
        </div>
    @endif

    <table class="pagespeed-grid">
        <tr>
            @foreach(['mobile' => $pdfPsMobile, 'desktop' => $pdfPsDesktop] as $type => $ps)
                @if($ps && isset($ps['scores']))
                    <td>
                        <div class="ps-card">
                            <h4>{{ ucfirst($type) }}</h4>
                            <table class="ps-scores">
                                <tr>
                                    @foreach(['performance' => 'Perf', 'accessibility' => 'A11y', 'best_practices' => 'Best', 'seo' => 'SEO'] as $key => $label)
                                        @php $val = $ps['scores'][$key] ?? 0; @endphp
                                        <td>
                                            <div class="ps-score-value" style="color: {{ pdfScoreHex($val) }}">{{ $val }}</div>
                                            <div class="ps-score-label">{{ $label }}</div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>

                            @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                <table class="cwv-row">
                                    <tr>
                                        @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                            @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                @php $cwvItem = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                <td>
                                                    <div class="cwv-value" style="color: {{ ($cwvItem['score'] ?? '') === 'good' ? '#16a34a' : (($cwvItem['score'] ?? '') === 'needs_improvement' ? '#d97706' : '#dc2626') }}">
                                                        {{ $cwvItem['display'] ?? '-' }}
                                                    </div>
                                                    <div class="cwv-label">{{ $cwvLabel }}</div>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                </table>
                            @endif
                        </div>
                    </td>
                @endif
            @endforeach
        </tr>
    </table>
@endif

{{-- TRAFFIC SOURCES --}}
@if(isset($report['ga4']['traffic_sources']) && count($report['ga4']['traffic_sources']) > 0)
    <div class="avoid-break">
        <div class="section-title">{{ __("Traffic Sources") }}</div>
        @php $totalSrc = array_sum(array_column($report['ga4']['traffic_sources'], 'sessions')); @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th class="text-right">Sessions</th>
                    <th class="text-right">Share</th>
                    <th class="text-right">Bar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['ga4']['traffic_sources'] as $src)
                    @php $share = $totalSrc > 0 ? round(($src['sessions'] / $totalSrc) * 100, 1) : 0; @endphp
                    <tr>
                        <td>{{ $src['channel'] ?? $src['source'] ?? 'Unknown' }}</td>
                        <td class="text-right">{{ pdfFmt($src['sessions']) }}</td>
                        <td class="text-right">{{ $share }}%</td>
                        <td class="text-right">
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: {{ $share }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="page-break"></div>

{{-- PAGE 3: SEARCH CONSOLE DATA --}}
<div class="pdf-header">
    <div class="pdf-header-left">
        <div class="client-name">{{ $client->name }}</div>
        <div class="report-type">{{ __("Search Console Data") }}</div>
    </div>
    <div class="pdf-header-right">
        {{ $startDate }} — {{ $endDate }}
    </div>
</div>

<table class="two-col-table">
    <tr>
        {{-- Top Pages --}}
        <td>
            @if(isset($report['search_console']['pages']) && count($report['search_console']['pages']) > 0)
                <div class="section-title">{{ __("Top Pages") }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th class="text-right">Clicks</th>
                            <th class="text-right">Impr.</th>
                            <th class="text-right">Pos.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($report['search_console']['pages'], 0, 20) as $page)
                            <tr>
                                <td class="truncate">{{ basename($page['key'] ?? $page['page'] ?? '') ?: '/' }}</td>
                                <td class="text-right">{{ pdfFmt($page['clicks'] ?? 0) }}</td>
                                <td class="text-right">{{ pdfFmt($page['impressions'] ?? 0) }}</td>
                                <td class="text-right">{{ number_format($page['position'] ?? 0, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>

        {{-- Top Queries --}}
        <td>
            @if(isset($report['search_console']['queries']) && count($report['search_console']['queries']) > 0)
                <div class="section-title">{{ __("Top Search Queries") }}</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th class="text-right">Clicks</th>
                            <th class="text-right">Impr.</th>
                            <th class="text-right">Pos.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($report['search_console']['queries'], 0, 20) as $q)
                            <tr>
                                <td class="truncate">{{ $q['key'] ?? $q['query'] ?? '' }}</td>
                                <td class="text-right">{{ pdfFmt($q['clicks'] ?? 0) }}</td>
                                <td class="text-right">{{ pdfFmt($q['impressions'] ?? 0) }}</td>
                                <td class="text-right">{{ number_format($q['position'] ?? 0, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
    </tr>
</table>

{{-- GA4 TOP PAGES --}}
@if(isset($report['ga4']['top_pages']) && count($report['ga4']['top_pages']) > 0)
    <div class="page-break"></div>

    <div class="pdf-header">
        <div class="pdf-header-left">
            <div class="client-name">{{ $client->name }}</div>
            <div class="report-type">{{ __("Analytics Data") }}</div>
        </div>
        <div class="pdf-header-right">
            {{ $startDate }} — {{ $endDate }}
        </div>
    </div>

    <div class="section-title">{{ __("Top Pages (Analytics)") }}</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Page</th>
                <th class="text-right">Views</th>
                <th class="text-right">Users</th>
                <th class="text-right">{{ __("Avg Time") }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($report['ga4']['top_pages'], 0, 20) as $page)
                <tr>
                    <td class="truncate">{{ $page['page_path'] ?? $page['page'] ?? '' }}</td>
                    <td class="text-right">{{ pdfFmt($page['views'] ?? $page['screenPageViews'] ?? 0) }}</td>
                    <td class="text-right">{{ pdfFmt($page['users'] ?? $page['activeUsers'] ?? 0) }}</td>
                    <td class="text-right">{{ isset($page['avg_time']) ? gmdate('i:s', (int)$page['avg_time']) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- DEVICES --}}
@if(isset($report['ga4']['devices']) && count($report['ga4']['devices']) > 0)
    <div class="avoid-break">
        <div class="section-title">Devices & Browsers</div>
        @php $totalDev = array_sum(array_column($report['ga4']['devices'], 'sessions')); @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __("Device / Browser") }}</th>
                    <th class="text-right">Sessions</th>
                    <th class="text-right">Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['ga4']['devices'] as $device)
                    @php $devShare = $totalDev > 0 ? round(($device['sessions'] / $totalDev) * 100, 1) : 0; @endphp
                    <tr>
                        <td>{{ ucfirst($device['device_category'] ?? $device['browser'] ?? 'Unknown') }}</td>
                        <td class="text-right">{{ pdfFmt($device['sessions']) }}</td>
                        <td class="text-right">{{ $devShare }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- FOOTER --}}
<div class="pdf-footer">
    Generated on {{ now()->format('F j, Y \a\t g:i A') }} &middot; <span class="brand">{{ __("RB Benchmarks") }}</span>
    @if($client->website)
        &middot; {{ $client->website }}
    @endif
</div>

</body>
</html>
