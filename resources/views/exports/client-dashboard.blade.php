<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Dashboard Export - {{ $client['name'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            opacity: 0.9;
        }
        .logo {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
        }
        .client-info {
            background: #f3f4f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .client-info h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #667eea;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .metric-row {
            display: table-row;
        }
        .metric-card {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .metric-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin: 5px 0;
        }
        .metric-detail {
            font-size: 8px;
            color: #9ca3af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            padding: 10px 0;
            border-top: 1px solid #e5e7eb;
        }
        .page-number:after {
            content: "Page " counter(page);
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 70%;">
                <h1>Client Dashboard Report</h1>
                <div class="subtitle">{{ $date_range['from'] }} - {{ $date_range['to'] }}</div>
            </div>
            <div style="display: table-cell; width: 30%; vertical-align: middle;" class="logo">
                RB Benchmarks
            </div>
        </div>
    </div>

    <!-- Client Information -->
    <div class="client-info">
        <h2>{{ $client['name'] }}</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Industry:</div>
                <div class="info-value">{{ ucwords(str_replace('_', ' ', $client['industry'] ?? 'N/A')) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Export Date:</div>
                <div class="info-value">{{ $export_date }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data Period:</div>
                <div class="info-value">{{ $date_range['from'] }} to {{ $date_range['to'] }}</div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="section">
        <div class="section-title">Key Performance Metrics</div>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Total Spend</div>
                    <div class="metric-value">{{ number_format($metrics['total_spend'], 0) }} SAR</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Total Impressions</div>
                    <div class="metric-value">{{ number_format($metrics['total_impressions']) }}</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Total Clicks</div>
                    <div class="metric-value">{{ number_format($metrics['total_clicks']) }}</div>
                    <div class="metric-detail">CTR: {{ number_format($metrics['ctr'], 2) }}%</div>
                </div>
            </div>
        </div>
        <div class="metrics-grid" style="margin-top: 10px;">
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Conversions</div>
                    <div class="metric-value">{{ number_format($metrics['total_conversions']) }}</div>
                    <div class="metric-detail">CVR: {{ number_format($metrics['cvr'], 2) }}%</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Cost Per Click</div>
                    <div class="metric-value">{{ number_format($metrics['cpc'], 2) }} SAR</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">ROAS</div>
                    <div class="metric-value">{{ number_format($metrics['roas'], 2) }}x</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Breakdown -->
    <div class="section">
        <div class="section-title">Platform Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Accounts</th>
                    <th>Spend (SAR)</th>
                    <th>Impressions</th>
                    <th>Clicks</th>
                    <th>Conversions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($platform_breakdown as $platform)
                <tr>
                    <td>{{ ucfirst($platform['platform']) }}</td>
                    <td>{{ $platform['accounts_count'] }}</td>
                    <td>{{ number_format($platform['spend'], 2) }}</td>
                    <td>{{ number_format($platform['impressions']) }}</td>
                    <td>{{ number_format($platform['clicks']) }}</td>
                    <td>{{ number_format($platform['conversions']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Ad Accounts -->
    <div class="section">
        <div class="section-title">Advertising Accounts</div>
        <table>
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Total Spend</th>
                    <th>Health</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ad_accounts as $account)
                <tr>
                    <td>{{ $account['name'] }}</td>
                    <td>{{ ucfirst($account['platform']) }}</td>
                    <td>
                        <span class="badge badge-{{ $account['status'] === 'active' ? 'success' : 'info' }}">
                            {{ ucfirst($account['status']) }}
                        </span>
                    </td>
                    <td>{{ number_format($account['total_spend'], 2) }} SAR</td>
                    <td>
                        <span class="badge badge-{{ $account['health'] === 'healthy' ? 'success' : ($account['health'] === 'warning' ? 'warning' : 'danger') }}">
                            {{ ucfirst($account['health']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Top Campaigns -->
    <div class="section">
        <div class="section-title">Top Performing Campaigns</div>
        <table>
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Account</th>
                    <th>Spend (SAR)</th>
                    <th>Conversions</th>
                    <th>ROAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_campaigns as $campaign)
                <tr>
                    <td>{{ $campaign['name'] }}</td>
                    <td>{{ $campaign['account_name'] }}</td>
                    <td>{{ number_format($campaign['spend'], 2) }}</td>
                    <td>{{ number_format($campaign['conversions']) }}</td>
                    <td>
                        <span class="badge badge-{{ $campaign['roas'] >= 2 ? 'success' : ($campaign['roas'] >= 1 ? 'warning' : 'danger') }}">
                            {{ number_format($campaign['roas'], 2) }}x
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Generated by RB Benchmarks | © {{ date('Y') }} Red Bananas. All rights reserved.</div>
        <div class="page-number"></div>
    </div>
</body>
</html>
