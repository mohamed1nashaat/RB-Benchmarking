<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    @php
        $brandColor  = $tenant->brand_color ?? '#EF4444';
        $footerText  = $tenant->report_footer_text ?? 'Powered by Red Bananas';
        $tenantLogo  = $tenant->getLogoUrl();
        $tenantName  = $tenant->name ?? 'Red Bananas';
    @endphp
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
            line-height: 1.6;
            color: #1f2937;
            background: #ffffff;
        }

        .page-break  { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }

        /* Dynamic brand classes */
        .brand     { color: {{ $brandColor }}; }
        .brand-bg  { background-color: {{ $brandColor }}; }
        .brand-border { border-color: {{ $brandColor }}; }

        /* ── COVER ── */
        .cover-page {
            height: 100%;
            min-height: 297mm;
            background-color: #111827;
            display: table;
            width: 100%;
            position: relative;
        }

        .cover-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 40mm 20mm;
        }

        .cover-logo {
            margin-bottom: 24px;
        }

        .cover-logo img {
            height: 44px;
            max-width: 200px;
        }

        .cover-logo-text {
            font-size: 18pt;
            font-weight: 700;
            color: {{ $brandColor }};
            letter-spacing: 1px;
        }

        .cover-tag {
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: {{ $brandColor }};
            margin-bottom: 12px;
        }

        .cover-title {
            font-size: 30pt;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .cover-subtitle {
            font-size: 14pt;
            font-weight: 300;
            color: #9ca3af;
            margin-bottom: 24px;
        }

        .cover-meta {
            display: inline-block;
            margin-top: 20px;
            border-top: 1px solid #374151;
            padding-top: 16px;
            font-size: 9pt;
            color: #6b7280;
        }

        .cover-meta-row   { margin-bottom: 6px; }
        .cover-meta-label { color: #9ca3af; margin-right: 8px; }
        .cover-meta-value { color: #d1d5db; font-weight: 600; }

        .cover-footer {
            position: absolute;
            bottom: 16mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #4b5563;
        }

        /* ── SECTION HEADER ── */
        .section-header {
            border-left: 4px solid {{ $brandColor }};
            padding: 4px 10px;
            margin-bottom: 12px;
        }

        .section-header h2 {
            font-size: 13pt;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .section-header p {
            font-size: 8pt;
            color: #6b7280;
            margin: 2px 0 0;
        }

        /* ── CONTENT PAGE ── */
        .content-page { padding: 0; }

        .page-header {
            background-color: #f9fafb;
            border-bottom: 2px solid {{ $brandColor }};
            padding: 8mm 12mm 6mm;
            margin-bottom: 8mm;
        }

        .page-header-name { font-size: 10pt; font-weight: 700; color: #111827; }
        .page-header-sub  { font-size: 8pt; color: #6b7280; margin-top: 2px; }

        /* ── EXECUTIVE SUMMARY ── */
        .summary-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .summary-box p {
            font-size: 9.5pt;
            line-height: 1.7;
            color: #374151;
        }

        /* ── METRICS TABLE ── */
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .metrics-table th {
            background-color: #111827;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            letter-spacing: 0.5px;
        }

        .metrics-table td {
            padding: 8px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        .metrics-table tr:nth-child(even) td { background-color: #f9fafb; }
        .metric-value { font-weight: 700; color: #111827; text-align: right; }
        .metric-label { color: #6b7280; font-size: 8pt; }

        /* ── METRICS GRID ── */
        .metrics-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }

        .metrics-grid td { width: 25%; vertical-align: top; padding: 0 4px 8px 0; }

        .metric-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 12px;
        }

        .metric-card-label {
            font-size: 7.5pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .metric-card-value {
            font-size: 16pt;
            font-weight: 700;
            color: #111827;
            line-height: 1.1;
        }

        .metric-card-unit { font-size: 9pt; font-weight: 400; color: #6b7280; }

        /* ── ANALYSIS ── */
        .analysis-body {
            font-size: 9.5pt;
            line-height: 1.8;
            color: #374151;
            margin-bottom: 16px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* ── RECOMMENDATIONS ── */
        .rec-list  { margin: 0; padding: 0; list-style: none; }

        .rec-item  { display: table; width: 100%; margin-bottom: 10px; page-break-inside: avoid; }

        .rec-number {
            display: table-cell;
            width: 24px;
            vertical-align: top;
            padding-top: 2px;
        }

        .rec-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: {{ $brandColor }};
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 700;
            text-align: center;
            line-height: 20px;
            border-radius: 3px;
        }

        .rec-text {
            display: table-cell;
            vertical-align: top;
            padding-left: 8px;
            font-size: 9.5pt;
            line-height: 1.6;
            color: #374151;
        }

        /* ── FIXED FOOTER ── */
        .content-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e7eb;
            padding: 4mm 12mm;
            font-size: 7.5pt;
            color: #9ca3af;
        }

        .content-footer-inner { display: table; width: 100%; }
        .content-footer-left  { display: table-cell; text-align: left; }

        .content-footer-right {
            display: table-cell;
            text-align: right;
            color: {{ $brandColor }};
            font-weight: 700;
        }
    </style>
</head>
<body>

{{-- ═══════════════ COVER PAGE ═══════════════ --}}
<div class="cover-page">
    <div class="cover-content">

        {{-- Logo: tenant logo if set, else text fallback --}}
        <div class="cover-logo">
            @if($tenantLogo)
                <img src="{{ $tenantLogo }}" alt="{{ $tenantName }}">
            @else
                <div class="cover-logo-text">{{ $tenantName }}</div>
            @endif
        </div>

        <div class="cover-tag">AI Performance Report</div>
        <div class="cover-title">{{ $report->name }}</div>
        <div class="cover-subtitle">
            {{ $report->period_start->format('d M') }} – {{ $report->period_end->format('d M Y') }}
        </div>

        <div class="cover-meta">
            @if($report->platform)
            <div class="cover-meta-row">
                <span class="cover-meta-label">Platform</span>
                <span class="cover-meta-value">{{ ucfirst($report->platform) }}</span>
            </div>
            @endif
            @if($report->industry)
            <div class="cover-meta-row">
                <span class="cover-meta-label">Industry</span>
                <span class="cover-meta-value">{{ ucwords(str_replace('_', ' ', $report->industry)) }}</span>
            </div>
            @endif
            <div class="cover-meta-row">
                <span class="cover-meta-label">Generated</span>
                <span class="cover-meta-value">{{ $report->created_at->format('d M Y') }}</span>
            </div>
        </div>
    </div>
    <div class="cover-footer">{{ $footerText }}</div>
</div>

{{-- ═══════════════ CONTENT PAGES ═══════════════ --}}
<div class="content-page">

    {{-- Fixed footer --}}
    <div class="content-footer">
        <div class="content-footer-inner">
            <div class="content-footer-left">{{ $report->name }} &nbsp;·&nbsp; {{ $report->period_start->format('d M') }} – {{ $report->period_end->format('d M Y') }}</div>
            <div class="content-footer-right">{{ $tenantName }}</div>
        </div>
    </div>

    {{-- Page header --}}
    <div class="page-header">
        <div class="page-header-name">{{ $report->name }}</div>
        <div class="page-header-sub">
            {{ $report->period_start->format('d M') }} – {{ $report->period_end->format('d M Y') }}
            @if($report->platform) &nbsp;·&nbsp; {{ ucfirst($report->platform) }} @endif
            @if($report->industry) &nbsp;·&nbsp; {{ ucwords(str_replace('_', ' ', $report->industry)) }} @endif
        </div>
    </div>

    {{-- ── EXECUTIVE SUMMARY ── --}}
    @if($report->executive_summary)
    <div class="avoid-break" style="margin: 0 12mm 8mm;">
        <div class="section-header">
            <h2>Executive Summary</h2>
        </div>
        <div class="summary-box">
            <p>{{ $report->executive_summary }}</p>
        </div>
    </div>
    @endif

    {{-- ── PERFORMANCE METRICS ── --}}
    @php $pd = $report->performance_data ?? []; @endphp
    @if(!empty($pd))
    <div class="avoid-break" style="margin: 0 12mm 8mm;">
        <div class="section-header">
            <h2>Performance Metrics</h2>
            <p>Campaign performance for the reporting period</p>
        </div>

        <table class="metrics-grid">
            <tr>
                @if(isset($pd['spend']))
                <td><div class="metric-card">
                    <div class="metric-card-label">Total Spend</div>
                    <div class="metric-card-value">{{ number_format($pd['spend'], 0) }} <span class="metric-card-unit">SAR</span></div>
                </div></td>
                @endif
                @if(isset($pd['leads']))
                <td><div class="metric-card">
                    <div class="metric-card-label">Leads</div>
                    <div class="metric-card-value">{{ number_format($pd['leads'], 0) }}</div>
                </div></td>
                @endif
                @if(isset($pd['impressions']))
                <td><div class="metric-card">
                    <div class="metric-card-label">Impressions</div>
                    <div class="metric-card-value">{{ number_format($pd['impressions'] / 1000, 1) }} <span class="metric-card-unit">K</span></div>
                </div></td>
                @endif
                @if(isset($pd['clicks']))
                <td><div class="metric-card">
                    <div class="metric-card-label">Clicks</div>
                    <div class="metric-card-value">{{ number_format($pd['clicks'], 0) }}</div>
                </div></td>
                @endif
            </tr>
        </table>

        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Metric</th><th style="text-align:right;">Value</th>
                    <th>Metric</th><th style="text-align:right;">Value</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $metricRows = [];
                    if (isset($pd['ctr']))       $metricRows[] = ['CTR',       number_format($pd['ctr'], 2) . '%'];
                    if (isset($pd['cpl']))       $metricRows[] = ['CPL',       'SAR ' . number_format($pd['cpl'], 2)];
                    if (isset($pd['cpc']))       $metricRows[] = ['CPC',       'SAR ' . number_format($pd['cpc'], 2)];
                    if (isset($pd['cpm']))       $metricRows[] = ['CPM',       'SAR ' . number_format($pd['cpm'], 2)];
                    if (isset($pd['campaigns'])) $metricRows[] = ['Campaigns', number_format($pd['campaigns'], 0)];
                    if (count($metricRows) % 2 !== 0) $metricRows[] = ['', ''];
                    $pairs = array_chunk($metricRows, 2);
                @endphp
                @foreach($pairs as $pair)
                <tr>
                    <td class="metric-label">{{ $pair[0][0] }}</td>
                    <td class="metric-value">{{ $pair[0][1] }}</td>
                    <td class="metric-label">{{ $pair[1][0] ?? '' }}</td>
                    <td class="metric-value">{{ $pair[1][1] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── FULL ANALYSIS ── --}}
    @php $sections = $report->sections ?? []; $narrative = $sections['full_narrative'] ?? null; @endphp
    @if($narrative)
    <div style="margin: 0 12mm 8mm;">
        <div class="section-header">
            <h2>Full Analysis</h2>
            <p>AI-generated performance analysis</p>
        </div>
        <div class="analysis-body">{{ $narrative }}</div>
    </div>
    @endif

    {{-- ── RECOMMENDATIONS ── --}}
    @php $recs = $report->recommendations ?? $sections['recommendations'] ?? []; @endphp
    @if(!empty($recs))
    <div class="avoid-break" style="margin: 0 12mm 8mm;">
        <div class="section-header">
            <h2>Recommendations</h2>
            <p>{{ count($recs) }} actionable recommendations</p>
        </div>
        <ul class="rec-list">
            @foreach($recs as $i => $rec)
            <li class="rec-item avoid-break">
                <div class="rec-number"><span class="rec-badge">{{ $i + 1 }}</span></div>
                <div class="rec-text">{{ $rec }}</div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── REPORT METADATA ── --}}
    <div style="margin: 8mm 12mm 0; border-top: 1px solid #e5e7eb; padding-top: 8px;">
        <table style="width: 100%; font-size: 7.5pt; color: #9ca3af;">
            <tr>
                <td>{{ $footerText }}</td>
                @if(isset($sections['full_narrative']))
                <td style="text-align: center;">{{ str_word_count($sections['full_narrative']) }} words</td>
                @endif
                <td style="text-align: right;">{{ $report->created_at->format('d M Y, H:i') }}</td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
