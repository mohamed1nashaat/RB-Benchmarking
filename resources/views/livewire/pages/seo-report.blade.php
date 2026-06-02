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
        if ($score >= 90) return 'rgba(34,197,94,0.15)';
        if ($score >= 50) return 'rgba(245,158,11,0.15)';
        return 'rgba(239,68,68,0.15)';
    }
@endphp

<div class="seo-report">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
            min-height: 100vh;
        }

        .seo-report { padding: 0; }

        /* ===== HEADER ===== */
        .report-header {
            background: linear-gradient(135deg, rgba(15,23,42,0.95), rgba(30,41,59,0.95));
            border-bottom: 1px solid rgba(212,175,55,0.3);
            padding: 24px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
        }

        .header-left { display: flex; align-items: center; gap: 16px; }

        .header-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(212,175,55,0.4);
        }

        .header-logo-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #d4af37, #b8962e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            color: #0f172a;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .header-actions { display: flex; gap: 10px; }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-gold {
            background: linear-gradient(135deg, #d4af37, #b8962e);
            color: #0f172a;
        }
        .btn-gold:hover { background: linear-gradient(135deg, #e4bf47, #c8a63e); transform: translateY(-1px); }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(212,175,55,0.4);
            color: #d4af37;
        }
        .btn-outline:hover { background: rgba(212,175,55,0.1); }

        /* ===== PERIOD SELECTOR ===== */
        .period-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 40px;
            background: rgba(15,23,42,0.6);
        }

        .period-btn {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid rgba(148,163,184,0.2);
            background: transparent;
            color: #94a3b8;
            transition: all 0.2s;
        }
        .period-btn:hover { border-color: rgba(212,175,55,0.4); color: #d4af37; }
        .period-btn.active {
            background: rgba(212,175,55,0.15);
            border-color: #d4af37;
            color: #d4af37;
        }

        /* ===== CONTENT ===== */
        .report-content { max-width: 1400px; margin: 0 auto; padding: 32px 40px 60px; }

        /* ===== GLASS CARD ===== */
        .glass-card {
            background: rgba(30,41,59,0.6);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(12px);
            margin-bottom: 24px;
        }

        .glass-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title .icon {
            width: 20px;
            height: 20px;
            color: #d4af37;
        }

        .gold-accent { color: #d4af37; }

        /* ===== KPI GRID ===== */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: rgba(30,41,59,0.7);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s, border-color 0.2s;
        }
        .kpi-card:hover { transform: translateY(-2px); border-color: rgba(212,175,55,0.3); }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ===== SCORE GAUGE ===== */
        .scores-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .score-section { text-align: center; }

        .score-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .score-circles {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .score-circle {
            position: relative;
            width: 90px;
            height: 90px;
        }

        .score-circle svg { transform: rotate(-90deg); }

        .score-circle .score-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 22px;
            font-weight: 700;
        }

        .score-circle .score-name {
            display: block;
            margin-top: 6px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }

        /* ===== CORE WEB VITALS ===== */
        .cwv-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .cwv-item {
            background: rgba(15,23,42,0.5);
            border: 1px solid rgba(148,163,184,0.1);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        .cwv-value {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .cwv-label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* ===== TABLES ===== */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            padding: 10px 14px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            border-bottom: 1px solid rgba(148,163,184,0.15);
            text-align: left;
        }

        .data-table tbody td {
            padding: 10px 14px;
            font-size: 13px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(148,163,184,0.06);
        }

        .data-table tbody tr:hover { background: rgba(212,175,55,0.04); }

        .data-table .text-right { text-align: right; }
        .data-table .text-center { text-align: center; }

        .truncate-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ===== PROGRESS BAR ===== */
        .progress-bar {
            height: 6px;
            background: rgba(148,163,184,0.1);
            border-radius: 3px;
            overflow: hidden;
            min-width: 80px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, #d4af37, #e4bf47);
            transition: width 0.5s ease;
        }

        /* ===== TWO COLUMNS ===== */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

        /* ===== FOOTER ===== */
        .report-footer {
            text-align: center;
            padding: 32px 40px;
            border-top: 1px solid rgba(148,163,184,0.1);
            color: #64748b;
            font-size: 12px;
        }

        .report-footer .brand {
            color: #d4af37;
            font-weight: 600;
        }

        /* ===== NO DATA ===== */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .no-data-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .report-header { padding: 16px 20px; flex-direction: column; gap: 16px; }
            .report-content { padding: 20px; }
            .two-col { grid-template-columns: 1fr; }
            .scores-grid { grid-template-columns: 1fr; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* ===== PRINT ===== */
        @media print {
            body { background: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .report-header { position: static; }
            .header-actions, .period-bar { display: none !important; }
            .glass-card { break-inside: avoid; }
        }

        /* ===== LOADING ===== */
        .loading-overlay {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid rgba(212,175,55,0.2);
            border-top-color: #d4af37;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    {{-- Loading --}}
    @if($loading)
        <div class="loading-overlay">
            <div class="spinner"></div>
        </div>
    @elseif($error)
        <div class="loading-overlay">
            <div class="no-data">
                <div class="no-data-icon">&#9888;</div>
                <p>{{ $error }}</p>
            </div>
        </div>
    @elseif($clientData && $reportData)
        {{-- HEADER --}}
        <div class="report-header">
            <div class="header-left">
                @if($clientData['logo'])
                    <img src="{{ $clientData['logo'] }}" alt="" class="header-logo">
                @else
                    <div class="header-logo-placeholder">{{ strtoupper(substr($clientData['name'], 0, 1)) }}</div>
                @endif
                <div>
                    <div class="header-title">{{ $clientData['name'] }}</div>
                    <div class="header-subtitle">SEO Performance Report &middot; {{ $reportData['start_date'] }} to {{ $reportData['end_date'] }}</div>
                </div>
            </div>
            <div class="header-actions">
                <button onclick="window.print()" class="btn btn-outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                    Print
                </button>
                <a href="{{ route('client-seo-report-pdf', $clientId) }}" class="btn btn-gold">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                    Download PDF
                </a>
            </div>
        </div>

        {{-- PERIOD SELECTOR --}}
        <div class="period-bar">
            @foreach([7 => '7 Days', 30 => '30 Days', 90 => '90 Days', 365 => '1 Year'] as $val => $label)
                <button wire:click="setPeriod({{ $val }})" class="period-btn {{ $period === $val ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- CONTENT --}}
        <div class="report-content">
            {{-- KPI GRID --}}
            <div class="kpi-grid">
                @if(isset($reportData['ga4']['summary']))
                    @php $ga4 = $reportData['ga4']['summary']; @endphp
                    <div class="kpi-card">
                        <div class="kpi-value">{{ seoFmt($ga4['sessions'] ?? 0) }}</div>
                        <div class="kpi-label">Total Sessions</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">{{ seoFmt($ga4['active_users'] ?? 0) }}</div>
                        <div class="kpi-label">Active Users</div>
                    </div>
                    @if(isset($ga4['organic_sessions']))
                        <div class="kpi-card">
                            <div class="kpi-value">{{ seoFmt($ga4['organic_sessions']) }}</div>
                            <div class="kpi-label">Organic Sessions</div>
                        </div>
                    @endif
                    @if(isset($ga4['bounce_rate']))
                        <div class="kpi-card">
                            <div class="kpi-value">{{ number_format($ga4['bounce_rate'], 1) }}%</div>
                            <div class="kpi-label">Bounce Rate</div>
                        </div>
                    @endif
                    @if(isset($ga4['avg_session_duration']))
                        <div class="kpi-card">
                            <div class="kpi-value">{{ gmdate('i:s', (int)($ga4['avg_session_duration'] ?? 0)) }}</div>
                            <div class="kpi-label">Avg Duration</div>
                        </div>
                    @endif
                @endif

                @if(isset($reportData['search_console']['summary']))
                    @php $sc = $reportData['search_console']['summary']; @endphp
                    <div class="kpi-card">
                        <div class="kpi-value">{{ seoFmt($sc['total_impressions']) }}</div>
                        <div class="kpi-label">Search Impressions</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">{{ seoFmt($sc['total_clicks']) }}</div>
                        <div class="kpi-label">Search Clicks</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">{{ $sc['avg_position'] }}</div>
                        <div class="kpi-label">Avg Position</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">{{ $sc['avg_ctr'] }}%</div>
                        <div class="kpi-label">Avg CTR</div>
                    </div>
                @endif

                @if(isset($reportData['pagespeed_mobile']['scores']['performance']) && $reportData['pagespeed_mobile']['scores']['performance'] > 0)
                    <div class="kpi-card">
                        <div class="kpi-value" style="color: {{ scoreColor($reportData['pagespeed_mobile']['scores']['performance']) }}">
                            {{ $reportData['pagespeed_mobile']['scores']['performance'] }}
                        </div>
                        <div class="kpi-label">Mobile Speed</div>
                    </div>
                @endif

                @if(isset($reportData['pagespeed_desktop']['scores']['performance']) && $reportData['pagespeed_desktop']['scores']['performance'] > 0)
                    <div class="kpi-card">
                        <div class="kpi-value" style="color: {{ scoreColor($reportData['pagespeed_desktop']['scores']['performance']) }}">
                            {{ $reportData['pagespeed_desktop']['scores']['performance'] }}
                        </div>
                        <div class="kpi-label">Desktop Speed</div>
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
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-title">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            PageSpeed Insights
                        </div>
                    </div>

                    @if($psHasError && !$psHasScores)
                        <div class="no-data" style="padding: 24px;">
                            <div class="no-data-icon" style="font-size: 32px;">&#9888;</div>
                            @if($psIsQuotaError)
                                <p style="color: #f59e0b; font-size: 14px; margin-bottom: 4px;">API Quota Exceeded</p>
                                <p style="color: #64748b; font-size: 12px;">PageSpeed Insights API quota has been exceeded. Data will refresh automatically when the quota resets.</p>
                            @else
                                <p style="color: #ef4444; font-size: 14px; margin-bottom: 4px;">PageSpeed Data Unavailable</p>
                                <p style="color: #64748b; font-size: 12px;">Unable to retrieve PageSpeed data. This is usually temporary — please try again later.</p>
                            @endif
                        </div>
                    @endif

                    <div class="scores-grid">
                        @foreach(['mobile' => $psMobile, 'desktop' => $psDesktop] as $type => $ps)
                            @if($ps && isset($ps['scores']))
                                <div class="score-section">
                                    <h4>{{ ucfirst($type) }}</h4>
                                    <div class="score-circles">
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
                                                        <circle cx="45" cy="45" r="36" fill="none" stroke="rgba(148,163,184,0.1)" stroke-width="6"/>
                                                        <circle cx="45" cy="45" r="36" fill="none" stroke="{{ $color }}" stroke-width="6"
                                                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                                                                stroke-linecap="round"/>
                                                    </svg>
                                                    <span class="score-value" style="color: {{ $color }}">{{ $val }}</span>
                                                </div>
                                                <span class="score-circle score-name">{{ $label }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Core Web Vitals --}}
                                    @if(isset($ps['core_web_vitals']) && !empty($ps['core_web_vitals']))
                                        <div class="cwv-grid">
                                            @foreach(['lcp' => 'LCP', 'fid' => 'FID', 'cls' => 'CLS', 'fcp' => 'FCP', 'tbt' => 'TBT', 'ttfb' => 'TTFB'] as $cwvKey => $cwvLabel)
                                                @if(isset($ps['core_web_vitals'][$cwvKey]))
                                                    @php $cwvItem = $ps['core_web_vitals'][$cwvKey]; @endphp
                                                    <div class="cwv-item">
                                                        <div class="cwv-value" style="color: {{ ($cwvItem['score'] ?? '') === 'good' ? '#22c55e' : (($cwvItem['score'] ?? '') === 'needs_improvement' ? '#f59e0b' : '#ef4444') }}">
                                                            {{ $cwvItem['display'] ?? '-' }}
                                                        </div>
                                                        <div class="cwv-label">{{ $cwvLabel }}</div>
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
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-title">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/><circle cx="12" cy="12" r="10"/></svg>
                            Traffic Sources
                        </div>
                    </div>
                    @php
                        $totalSessions = array_sum(array_column($reportData['ga4']['traffic_sources'], 'sessions'));
                    @endphp
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th class="text-right">Sessions</th>
                                <th class="text-right">Share</th>
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
                                    <td class="text-right">{{ seoFmt($source['sessions']) }}</td>
                                    <td class="text-right">{{ $share }}%</td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: {{ $share }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- TWO COLUMN: TOP PAGES + TOP QUERIES --}}
            <div class="two-col">
                {{-- Top Pages --}}
                @if(isset($reportData['search_console']['pages']) && count($reportData['search_console']['pages']) > 0)
                    <div class="glass-card">
                        <div class="glass-card-header">
                            <div class="card-title">
                                <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Top Pages
                            </div>
                        </div>
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
                                @foreach(array_slice($reportData['search_console']['pages'], 0, 15) as $page)
                                    <tr>
                                        <td class="truncate-cell" title="{{ $page['key'] ?? $page['page'] ?? '' }}">
                                            {{ basename($page['key'] ?? $page['page'] ?? '') ?: '/' }}
                                        </td>
                                        <td class="text-right">{{ seoFmt($page['clicks'] ?? 0) }}</td>
                                        <td class="text-right">{{ seoFmt($page['impressions'] ?? 0) }}</td>
                                        <td class="text-right">{{ number_format($page['position'] ?? 0, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Top Search Queries --}}
                @if(isset($reportData['search_console']['queries']) && count($reportData['search_console']['queries']) > 0)
                    <div class="glass-card">
                        <div class="glass-card-header">
                            <div class="card-title">
                                <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Top Search Queries
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Query</th>
                                    <th class="text-right">Clicks</th>
                                    <th class="text-right">Impr.</th>
                                    <th class="text-right">CTR</th>
                                    <th class="text-right">Pos.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($reportData['search_console']['queries'], 0, 15) as $query)
                                    <tr>
                                        <td class="truncate-cell">{{ $query['key'] ?? $query['query'] ?? '' }}</td>
                                        <td class="text-right">{{ seoFmt($query['clicks'] ?? 0) }}</td>
                                        <td class="text-right">{{ seoFmt($query['impressions'] ?? 0) }}</td>
                                        <td class="text-right">{{ number_format(($query['ctr'] ?? 0) * 100, 1) }}%</td>
                                        <td class="text-right">{{ number_format($query['position'] ?? 0, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- GA4 TOP PAGES --}}
            @if(isset($reportData['ga4']['top_pages']) && count($reportData['ga4']['top_pages']) > 0)
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-title">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Top Pages (Analytics)
                        </div>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th class="text-right">Views</th>
                                <th class="text-right">Users</th>
                                <th class="text-right">Avg Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($reportData['ga4']['top_pages'], 0, 15) as $page)
                                <tr>
                                    <td class="truncate-cell" title="{{ $page['page_path'] ?? $page['page'] ?? '' }}">
                                        {{ $page['page_path'] ?? $page['page'] ?? '' }}
                                    </td>
                                    <td class="text-right">{{ seoFmt($page['views'] ?? $page['screenPageViews'] ?? 0) }}</td>
                                    <td class="text-right">{{ seoFmt($page['users'] ?? $page['activeUsers'] ?? 0) }}</td>
                                    <td class="text-right">
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
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="card-title">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Devices & Browsers
                        </div>
                    </div>
                    @php
                        $totalDevSessions = array_sum(array_column($reportData['ga4']['devices'], 'sessions'));
                    @endphp
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Device / Browser</th>
                                <th class="text-right">Sessions</th>
                                <th class="text-right">Share</th>
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
                                    <td class="text-right">{{ seoFmt($device['sessions']) }}</td>
                                    <td class="text-right">{{ $devShare }}%</td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: {{ $devShare }}%"></div>
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
                <div class="glass-card">
                    <div class="no-data">
                        <div class="no-data-icon">&#128269;</div>
                        <p style="font-size: 16px; color: #94a3b8; margin-bottom: 8px;">No SEO data available</p>
                        <p style="font-size: 13px; color: #64748b;">Configure SEO properties in the client dashboard to generate this report.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- FOOTER --}}
        <div class="report-footer">
            <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }} &middot; <span class="brand">RB Benchmarks</span></p>
            @if($clientData['website'])
                <p style="margin-top: 4px;">{{ $clientData['website'] }}</p>
            @endif
        </div>
    @endif
</div>
