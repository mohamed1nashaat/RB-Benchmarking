<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $client->name }} - Performance Report</title>
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

        /* ========== PAGE BREAKS ========== */
        .page-break {
            page-break-after: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        /* ========== COVER PAGE ========== */
        .cover-page {
            height: 100%;
            min-height: 297mm;
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            position: relative;
            padding: 0;
            display: table;
            width: 100%;
        }

        .cover-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 40mm 20mm;
        }

        .cover-logo {
            margin-bottom: 30px;
        }

        .cover-client-name {
            font-size: 48pt;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
            line-height: 1.1;
        }

        .cover-title {
            font-size: 24pt;
            font-weight: 300;
            color: #F93549;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 40px;
        }

        .cover-meta {
            font-size: 12pt;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .cover-meta strong {
            color: #ffffff;
        }

        .rainbow-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #F93549 0%, #F5EA61 50%, #FCA0C9 100%);
        }

        /* ========== FIXED HEADER (Pages 2-5) ========== */
        .pdf-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: #fff;
            padding: 12px 15mm;
            margin: -15mm -12mm 15px -12mm;
            display: table;
            width: calc(100% + 24mm);
        }

        .pdf-header-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }

        .pdf-header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: middle;
        }

        .pdf-header-brand {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #F93549;
            font-weight: 600;
        }

        .pdf-header-client {
            font-size: 14pt;
            font-weight: 700;
            color: #fff;
        }

        .pdf-header-info {
            font-size: 8pt;
            color: #d1d5db;
        }

        /* ========== FIXED FOOTER ========== */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 12mm;
            border-top: 2px solid #F93549;
            font-size: 7pt;
            color: #6b7280;
            display: table;
            width: 100%;
            background: #fff;
        }

        .pdf-footer-left {
            display: table-cell;
            width: 50%;
        }

        .pdf-footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        /* ========== SECTION TITLES ========== */
        .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            padding-left: 12px;
            border-left: 4px solid #F93549;
        }

        .section-subtitle {
            font-size: 10pt;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            margin-top: 15px;
        }

        /* ========== EXECUTIVE SUMMARY ========== */
        .health-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .health-badge.healthy {
            background: #d1fae5;
            color: #065f46;
        }

        .health-badge.needs_attention {
            background: #fef3c7;
            color: #92400e;
        }

        .health-badge.critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .insights-list {
            margin-bottom: 20px;
        }

        .insight-item {
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            display: table;
            width: 100%;
        }

        .insight-item.success {
            background: #ecfdf5;
            border-left: 3px solid #10b981;
        }

        .insight-item.warning {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
        }

        .insight-item.danger {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
        }

        .insight-item.info {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
        }

        .insight-icon {
            display: table-cell;
            width: 25px;
            font-size: 12pt;
            vertical-align: middle;
        }

        .insight-text {
            display: table-cell;
            font-size: 9pt;
            color: #374151;
            vertical-align: middle;
        }

        /* ========== HERO METRICS ========== */
        .hero-metrics {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .hero-metric {
            display: table-cell;
            width: 25%;
            padding: 15px 12px;
            text-align: center;
            background: #fff;
            border-right: 1px solid #f3f4f6;
        }

        .hero-metric:last-child {
            border-right: none;
        }

        .hero-metric-label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .hero-metric-value {
            font-size: 20pt;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .hero-metric-sparkline {
            margin-top: 8px;
            height: 20px;
        }

        .hero-metric-trend {
            font-size: 8pt;
            margin-top: 4px;
        }

        .trend-up {
            color: #10b981;
        }

        .trend-down {
            color: #ef4444;
        }

        .trend-flat {
            color: #6b7280;
        }

        /* ========== METRICS STRIP (8 metrics) ========== */
        .metrics-strip {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .metric-box {
            display: table-cell;
            width: 12.5%;
            padding: 10px 6px;
            text-align: center;
            background: #fff;
            border-right: 1px solid #f3f4f6;
        }

        .metric-box:last-child {
            border-right: none;
        }

        .metric-box:nth-child(odd) {
            background: #fef2f2;
        }

        .metric-icon {
            width: 26px;
            height: 26px;
            margin: 0 auto 4px;
            border-radius: 6px;
            line-height: 26px;
            font-size: 10pt;
        }

        .metric-icon.red { background: #fee2e2; color: #dc2626; }
        .metric-icon.green { background: #d1fae5; color: #059669; }
        .metric-icon.blue { background: #dbeafe; color: #2563eb; }
        .metric-icon.purple { background: #ede9fe; color: #7c3aed; }
        .metric-icon.orange { background: #ffedd5; color: #ea580c; }
        .metric-icon.gray { background: #f3f4f6; color: #4b5563; }

        .metric-value {
            font-size: 12pt;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .metric-label {
            font-size: 6pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 2px;
        }

        .metric-trend {
            font-size: 7pt;
            margin-top: 2px;
        }

        /* ========== TABLES ========== */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        table.data th {
            background: linear-gradient(135deg, #F93549 0%, #e53e3e 100%);
            padding: 8px 8px;
            text-align: left;
            font-weight: 600;
            color: #ffffff;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.data td {
            padding: 7px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        table.data tr:nth-child(even) td {
            background: #fef2f2;
        }

        table.data tr:last-child td {
            border-bottom: none;
        }

        table.data .text-right {
            text-align: right;
        }

        table.data .font-semibold {
            font-weight: 600;
            color: #111827;
        }

        table.data .text-muted {
            color: #9ca3af;
            font-size: 7pt;
        }

        table.data tfoot td {
            background: #1f2937 !important;
            color: #ffffff;
            font-weight: 700;
        }

        /* ========== PROGRESS BARS ========== */
        .progress-item {
            margin-bottom: 12px;
        }

        .progress-header {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .progress-label {
            display: table-cell;
            font-size: 9pt;
            font-weight: 500;
            color: #374151;
        }

        .progress-value {
            display: table-cell;
            text-align: right;
            font-size: 9pt;
            font-weight: 600;
            color: #111827;
        }

        .progress-track {
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 5px;
        }

        .progress-fill.rb-red { background: linear-gradient(90deg, #F93549, #ff6b7a); }
        .progress-fill.rb-yellow { background: linear-gradient(90deg, #F5EA61, #f7ef8a); }
        .progress-fill.rb-pink { background: linear-gradient(90deg, #FCA0C9, #fdc4dd); }
        .progress-fill.blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .progress-fill.green { background: linear-gradient(90deg, #10b981, #34d399); }

        /* ========== BADGES ========== */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .badge-facebook { background: #1877f2; color: #fff; }
        .badge-google { background: #ea4335; color: #fff; }
        .badge-instagram { background: #e4405f; color: #fff; }
        .badge-snapchat { background: #fffc00; color: #000; }
        .badge-tiktok { background: #000; color: #fff; }
        .badge-twitter { background: #1da1f2; color: #fff; }
        .badge-linkedin { background: #0a66c2; color: #fff; }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-neutral { background: #f3f4f6; color: #4b5563; }

        /* ========== TWO COLUMN ========== */
        .two-col {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .col {
            display: table-cell;
            width: 49%;
            vertical-align: top;
        }

        .col:first-child {
            padding-right: 10px;
        }

        .col:last-child {
            padding-left: 10px;
        }

        /* ========== CARDS ========== */
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #F93549 0%, #e53e3e 100%);
            padding: 10px 14px;
            font-size: 9pt;
            font-weight: 600;
            color: #ffffff;
        }

        .card-body {
            padding: 12px 14px;
        }

        /* ========== UTILITIES ========== */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .mt-3 { margin-top: 12px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }

        .content-wrapper {
            padding: 0;
        }
    </style>
</head>
<body>
    {{-- ========== PAGE 1: COVER ========== --}}
    <div class="cover-page">
        <div class="cover-content">
            <div class="cover-logo">
                <svg width="200" height="53" viewBox="0 0 251 67" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.0888 7.93752V1.33887H0V41.0636H13.133V34.1175C13.133 31.8626 13.4718 29.6122 14.2378 27.4945C16.7971 20.3758 23.3393 15.1447 31.1677 14.4831V1.36985C24.4042 1.70841 18.1697 4.1049 13.0865 7.93752" fill="#F93549"/>
                    <path d="M54.197 9.24072L44.8985 18.539C49.5765 22.1504 52.5963 27.8063 52.5963 34.1638C52.5963 34.2744 52.5919 34.3851 52.5853 34.4957H65.6785C65.6785 34.3851 65.6829 34.2744 65.6829 34.1638C65.6829 24.2061 61.2218 15.2707 54.1948 9.24072" fill="#F5EA61"/>
                    <path d="M32.8392 53.9176H32.8503V66.9998C50.8451 66.9888 65.4969 52.4394 65.6785 34.4956H52.5897C52.4082 45.2322 43.6211 53.9132 32.8392 53.9198" fill="#F93549"/>
                    <path d="M32.8392 53.9177C26.4941 53.9133 20.8464 50.9061 17.2266 46.2437L7.91479 55.5021C13.9367 62.5301 22.8699 66.9956 32.8348 67H32.8503V53.9177H32.8392Z" fill="#FCA0C9"/>
                    <path d="M93.7312 11.5221C94.2161 10.2364 95.0131 9.26722 96.1289 8.62107C97.2447 7.97493 98.4867 7.65186 99.8594 7.65186V13.4539C98.2742 13.268 96.8551 13.5911 95.6042 14.4231C94.3577 15.2551 93.7312 16.6381 93.7312 18.5699V28.2002H88.5329V8.0568H93.7312V11.5221Z" fill="#ffffff"/>
                    <path d="M106.506 20.2673C107.203 22.79 109.1 24.0513 112.189 24.0513C114.177 24.0513 115.682 23.3808 116.701 22.0376L120.896 24.454C118.908 27.3307 115.979 28.7624 112.109 28.7624C108.777 28.7624 106.103 27.7578 104.088 25.7463C102.073 23.7282 101.064 21.1901 101.064 18.1275C101.064 15.065 102.058 12.5623 104.046 10.5353C106.034 8.50836 108.584 7.49268 111.704 7.49268C114.662 7.49268 117.099 8.51722 119.019 10.5552C120.943 12.5977 121.901 15.1203 121.901 18.1275C121.901 18.798 121.835 19.515 121.7 20.2673H106.508H106.506ZM106.426 16.2356H116.703C116.411 14.8636 115.8 13.8435 114.87 13.173C113.94 12.5025 112.886 12.164 111.706 12.164C110.307 12.164 109.151 12.5224 108.239 13.2328C107.325 13.9431 106.72 14.9433 106.428 16.2356" fill="#ffffff"/>
                    <path d="M140.647 0H145.845V28.2025H140.647V25.8259C139.112 27.7843 136.936 28.7668 134.116 28.7668C131.295 28.7668 129.083 27.7378 127.159 25.6843C125.242 23.6308 124.281 21.1082 124.281 18.1319C124.281 15.1557 125.24 12.633 127.159 10.5751C129.083 8.5216 131.399 7.49264 134.116 7.49264C136.832 7.49264 139.112 8.47734 140.647 10.4335V0ZM131.071 22.219C132.132 23.2789 133.469 23.8078 135.085 23.8078C136.701 23.8078 138.023 23.2789 139.073 22.219C140.122 21.1591 140.647 19.7982 140.647 18.1319C140.647 16.4657 140.122 15.1003 139.073 14.0404C138.023 12.9804 136.695 12.4516 135.085 12.4516C133.476 12.4516 132.132 12.9804 131.071 14.0404C130.011 15.1003 129.482 16.4612 129.482 18.1319C129.482 19.8026 130.011 21.1591 131.071 22.219Z" fill="#ffffff"/>
                    <path d="M100.258 41.8271C102.974 41.8271 105.29 42.8561 107.214 44.9096C109.131 46.9675 110.097 49.4857 110.097 52.4664C110.097 55.4471 109.134 57.9653 107.214 60.0188C105.29 62.0723 102.974 63.1012 100.258 63.1012C97.5414 63.1012 95.261 62.1165 93.7312 60.1604V62.537H88.5262V34.3345H93.7312V44.768C95.261 42.8096 97.4351 41.8271 100.258 41.8271ZM95.3009 56.5535C96.3503 57.6134 97.6786 58.1423 99.2926 58.1423C100.907 58.1423 102.239 57.6134 103.3 56.5535C104.36 55.4935 104.889 54.1326 104.889 52.4664C104.889 50.8001 104.36 49.4348 103.3 48.3749C102.239 47.3149 100.902 46.7861 99.2926 46.7861C97.6831 46.7861 96.3503 47.3149 95.3009 48.3749C94.2515 49.4348 93.7312 50.8023 93.7312 52.4664C93.7312 54.1304 94.2515 55.4935 95.3009 56.5535Z" fill="#ffffff"/>
                    <path d="M129.074 42.3934H134.279V62.539H129.074V60.1624C127.52 62.1207 125.328 63.1032 122.508 63.1032C119.687 63.1032 117.515 62.0743 115.594 60.0208C113.677 57.9673 112.711 55.4491 112.711 52.4684C112.711 49.4877 113.674 46.9695 115.594 44.9116C117.518 42.8581 119.818 41.8291 122.508 41.8291C125.198 41.8291 127.52 42.8138 129.074 44.77V42.3934ZM119.484 56.5555C120.529 57.6154 121.861 58.1443 123.471 58.1443C125.08 58.1443 126.424 57.6154 127.485 56.5555C128.545 55.4955 129.074 54.1346 129.074 52.4684C129.074 50.8021 128.545 49.4368 127.485 48.3769C126.424 47.3169 125.087 46.788 123.471 46.788C121.855 46.788 120.529 47.3169 119.484 48.3769C118.434 49.4368 117.914 50.8043 117.914 52.4684C117.914 54.1324 118.434 55.4955 119.484 56.5555Z" fill="#ffffff"/>
                    <path d="M150.197 41.8291C152.403 41.8291 154.22 42.5704 155.659 44.0441C157.098 45.5223 157.816 47.5648 157.816 50.1693V62.5345H152.617V50.8154C152.617 49.4744 152.254 48.4432 151.532 47.7329C150.806 47.0226 149.837 46.6641 148.63 46.6641C147.282 46.6641 146.212 47.0824 145.404 47.9144C144.596 48.7464 144.193 49.9922 144.193 51.6585V62.5345H138.995V42.3956H144.193V44.6505C145.455 42.7696 147.459 41.8313 150.2 41.8313" fill="#ffffff"/>
                    <path d="M177.562 42.3934H182.767V62.539H177.562V60.1624C176.008 62.1207 173.816 63.1032 170.995 63.1032C168.175 63.1032 166.003 62.0743 164.081 60.0208C162.164 57.9673 161.199 55.4491 161.199 52.4684C161.199 49.4877 162.162 46.9695 164.081 44.9116C166.005 42.8581 168.305 41.8291 170.995 41.8291C173.685 41.8291 176.008 42.8138 177.562 44.77V42.3934ZM167.971 56.5555C169.02 57.6154 170.349 58.1443 171.958 58.1443C173.568 58.1443 174.912 57.6154 175.972 56.5555C177.033 55.4955 177.562 54.1346 177.562 52.4684C177.562 50.8021 177.033 49.4368 175.972 48.3769C174.912 47.3169 173.574 46.788 171.958 46.788C170.342 46.788 169.02 47.3169 167.971 48.3769C166.922 49.4368 166.401 50.8043 166.401 52.4684C166.401 54.1324 166.922 55.4955 167.971 56.5555Z" fill="#ffffff"/>
                    <path d="M198.685 41.8291C200.885 41.8291 202.707 42.5704 204.147 44.0441C205.586 45.5223 206.303 47.5648 206.303 50.1693V62.5345H201.105V50.8154C201.105 49.4744 200.742 48.4432 200.015 47.7329C199.294 47.0226 198.324 46.6641 197.117 46.6641C195.769 46.6641 194.695 47.0824 193.892 47.9144C193.084 48.7464 192.681 49.9922 192.681 51.6585V62.5345H187.482V42.3956H192.681V44.6505C193.943 42.7696 195.946 41.8313 198.687 41.8313" fill="#ffffff"/>
                    <path d="M226.053 42.3934H231.252V62.539H226.053V60.1624C224.495 62.1207 222.303 63.1032 219.482 63.1032C216.662 63.1032 214.49 62.0743 212.568 60.0208C210.651 57.9673 209.69 55.4491 209.69 52.4684C209.69 49.4877 210.649 46.9695 212.568 44.9116C214.492 42.8581 216.799 41.8291 219.482 41.8291C222.303 41.8291 224.495 42.8138 226.053 44.77V42.3934ZM216.458 56.5555C217.508 57.6154 218.836 58.1443 220.45 58.1443C222.064 58.1443 223.397 57.6154 224.457 56.5555C225.518 55.4955 226.051 54.1346 226.051 52.4684C226.051 50.8021 225.515 49.4368 224.457 48.3769C223.397 47.3169 222.06 46.788 220.45 46.788C218.84 46.788 217.508 47.3169 216.458 48.3769C215.409 49.4368 214.889 50.8043 214.889 52.4684C214.889 54.1324 215.409 55.4955 216.458 56.5555Z" fill="#ffffff"/>
                    <path d="M240.601 48.1133C240.601 48.6532 240.96 49.0869 241.67 49.4189C242.381 49.7574 243.245 50.054 244.27 50.3062C245.29 50.5629 246.309 50.886 247.334 51.2754C248.354 51.6649 249.216 52.3155 249.933 53.2271C250.644 54.141 250.998 55.2807 250.998 56.6526C250.998 58.7216 250.225 60.3104 248.682 61.4257C247.137 62.5409 245.208 63.1008 242.897 63.1008C238.757 63.1008 235.936 61.5009 234.433 58.3034L238.952 55.7653C239.543 57.5112 240.86 58.383 242.899 58.383C244.752 58.383 245.68 57.8077 245.68 56.6526C245.68 56.1127 245.326 55.679 244.615 55.3404C243.898 55.0085 243.034 54.7053 242.011 54.4376C240.991 54.1698 239.972 53.8312 238.952 53.4285C237.927 53.0258 237.063 52.3885 236.353 51.5166C235.635 50.6448 235.283 49.5494 235.283 48.2328C235.283 46.2457 236.016 44.6812 237.48 43.5416C238.943 42.3954 240.765 41.8267 242.941 41.8267C244.582 41.8267 246.072 42.2006 247.418 42.9375C248.76 43.6744 249.82 44.7277 250.597 46.0996L246.165 48.516C245.518 47.1441 244.445 46.4625 242.939 46.4625C242.268 46.4625 241.713 46.6086 241.268 46.9007C240.823 47.1994 240.601 47.6021 240.601 48.1111" fill="#ffffff"/>
                </svg>
            </div>

            <div class="cover-client-name">{{ $client->name }}</div>
            <div class="cover-title">Performance Report</div>

            <div class="cover-meta"><strong>{{ $period['start'] }}</strong> - <strong>{{ $period['end'] }}</strong></div>
            <div class="cover-meta">{{ $period['days'] }} Days Analysis</div>
            <div class="cover-meta" style="margin-top: 20px;">{{ $industry }} &bull; {{ $subscription_tier }} Plan</div>
        </div>
        <div class="rainbow-bar"></div>
    </div>

    <div class="page-break"></div>

    {{-- ========== PAGE 2: EXECUTIVE SUMMARY ========== --}}
    <div class="content-wrapper">
        <div class="pdf-header">
            <div class="pdf-header-left">
                <div class="pdf-header-brand">RB Benchmarks</div>
                <div class="pdf-header-client">{{ $client->name }}</div>
            </div>
            <div class="pdf-header-right">
                <div class="pdf-header-info">{{ $period['start'] }} - {{ $period['end'] }}</div>
            </div>
        </div>

        <div class="section-title">Executive Summary</div>

        {{-- Health Badge --}}
        <div class="text-center mb-4">
            @php
                $healthLabels = [
                    'healthy' => 'Healthy Performance',
                    'needs_attention' => 'Needs Attention',
                    'critical' => 'Critical - Action Required',
                ];
            @endphp
            <span class="health-badge {{ $executive_summary['health'] ?? 'healthy' }}">
                {{ $healthLabels[$executive_summary['health'] ?? 'healthy'] }}
            </span>
        </div>

        {{-- Insights --}}
        <div class="insights-list">
            @foreach($executive_summary['insights'] ?? [] as $insight)
            <div class="insight-item {{ $insight['type'] }}">
                <span class="insight-icon">{{ $insight['icon'] }}</span>
                <span class="insight-text">{{ $insight['text'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Hero Metrics with Sparklines --}}
        <div class="section-subtitle">Key Performance Indicators</div>
        <div class="hero-metrics">
            <div class="hero-metric">
                <div class="hero-metric-label">Total Spend (SAR)</div>
                <div class="hero-metric-value">{{ number_format($statistics['total_spend'], 0) }}</div>
                <div class="hero-metric-sparkline">{!! $sparklines['spend'] ?? '' !!}</div>
                @if(isset($trends_direction['spend']))
                <div class="hero-metric-trend trend-{{ $trends_direction['spend'] }}">
                    @if($trends_direction['spend'] === 'up') &#9650; @elseif($trends_direction['spend'] === 'down') &#9660; @else &#9644; @endif
                    {{ abs($trends_percent['spend'] ?? 0) }}%
                </div>
                @endif
            </div>
            <div class="hero-metric">
                <div class="hero-metric-label">ROAS</div>
                <div class="hero-metric-value" style="color: {{ $statistics['roas'] >= 2 ? '#059669' : ($statistics['roas'] >= 1 ? '#f59e0b' : '#dc2626') }}">{{ number_format($statistics['roas'], 2) }}x</div>
                <div class="hero-metric-sparkline"></div>
                <div class="hero-metric-trend" style="color: #6b7280;">Return on Ad Spend</div>
            </div>
            <div class="hero-metric">
                <div class="hero-metric-label">Conversions</div>
                <div class="hero-metric-value">{{ number_format($statistics['total_conversions']) }}</div>
                <div class="hero-metric-sparkline">{!! $sparklines['conversions'] ?? '' !!}</div>
                @if(isset($trends_direction['conversions']))
                <div class="hero-metric-trend trend-{{ $trends_direction['conversions'] }}">
                    @if($trends_direction['conversions'] === 'up') &#9650; @elseif($trends_direction['conversions'] === 'down') &#9660; @else &#9644; @endif
                    {{ abs($trends_percent['conversions'] ?? 0) }}%
                </div>
                @endif
            </div>
            <div class="hero-metric">
                <div class="hero-metric-label">CTR</div>
                <div class="hero-metric-value">{{ number_format($statistics['ctr'], 2) }}%</div>
                <div class="hero-metric-sparkline">{!! $sparklines['clicks'] ?? '' !!}</div>
                @if(isset($trends_direction['ctr']))
                <div class="hero-metric-trend trend-{{ $trends_direction['ctr'] }}">
                    @if($trends_direction['ctr'] === 'up') &#9650; @elseif($trends_direction['ctr'] === 'down') &#9660; @else &#9644; @endif
                    {{ abs($trends_percent['ctr'] ?? 0) }}%
                </div>
                @endif
            </div>
        </div>

        {{-- Period Summary --}}
        <div class="card" style="margin-top: 15px;">
            <div class="card-header">Period Summary</div>
            <div class="card-body">
                <table class="data">
                    <tr>
                        <td><strong>Analysis Period</strong></td>
                        <td class="text-right">{{ $period['days'] }} days</td>
                        <td><strong>Ad Accounts</strong></td>
                        <td class="text-right">{{ $ad_accounts_count }}</td>
                    </tr>
                    <tr>
                        <td><strong>Industry</strong></td>
                        <td class="text-right">{{ $industry }}</td>
                        <td><strong>Subscription</strong></td>
                        <td class="text-right">{{ $subscription_tier }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    {{-- ========== PAGE 3: DETAILED METRICS & PLATFORMS ========== --}}
    <div class="content-wrapper">
        <div class="pdf-header">
            <div class="pdf-header-left">
                <div class="pdf-header-brand">RB Benchmarks</div>
                <div class="pdf-header-client">{{ $client->name }}</div>
            </div>
            <div class="pdf-header-right">
                <div class="pdf-header-info">{{ $period['start'] }} - {{ $period['end'] }}</div>
            </div>
        </div>

        <div class="section-title">Detailed Metrics</div>

        {{-- 8-Metric Strip --}}
        <div class="metrics-strip">
            <div class="metric-box">
                <div class="metric-icon red">$</div>
                <div class="metric-value">{{ number_format($statistics['total_spend'], 0) }}</div>
                <div class="metric-label">Spend (SAR)</div>
                @if(isset($trends_direction['spend']))
                <div class="metric-trend trend-{{ $trends_direction['spend'] }}">
                    @if($trends_direction['spend'] === 'up') &#9650; @elseif($trends_direction['spend'] === 'down') &#9660; @else - @endif
                </div>
                @endif
            </div>
            <div class="metric-box">
                <div class="metric-icon blue">&#128065;</div>
                <div class="metric-value">{{ number_format(($statistics['total_impressions'] ?? 0) / 1000, 1) }}K</div>
                <div class="metric-label">Impressions</div>
                @if(isset($trends_direction['impressions']))
                <div class="metric-trend trend-{{ $trends_direction['impressions'] }}">
                    @if($trends_direction['impressions'] === 'up') &#9650; @elseif($trends_direction['impressions'] === 'down') &#9660; @else - @endif
                </div>
                @endif
            </div>
            <div class="metric-box">
                <div class="metric-icon purple">&#8593;</div>
                <div class="metric-value">{{ number_format($statistics['total_clicks']) }}</div>
                <div class="metric-label">Clicks</div>
                @if(isset($trends_direction['clicks']))
                <div class="metric-trend trend-{{ $trends_direction['clicks'] }}">
                    @if($trends_direction['clicks'] === 'up') &#9650; @elseif($trends_direction['clicks'] === 'down') &#9660; @else - @endif
                </div>
                @endif
            </div>
            <div class="metric-box">
                <div class="metric-icon gray">%</div>
                <div class="metric-value">{{ number_format($statistics['ctr'], 2) }}%</div>
                <div class="metric-label">CTR</div>
                @if(isset($trends_direction['ctr']))
                <div class="metric-trend trend-{{ $trends_direction['ctr'] }}">
                    @if($trends_direction['ctr'] === 'up') &#9650; @elseif($trends_direction['ctr'] === 'down') &#9660; @else - @endif
                </div>
                @endif
            </div>
            <div class="metric-box">
                <div class="metric-icon green">&#10003;</div>
                <div class="metric-value">{{ number_format($statistics['total_conversions']) }}</div>
                <div class="metric-label">Conversions</div>
                @if(isset($trends_direction['conversions']))
                <div class="metric-trend trend-{{ $trends_direction['conversions'] }}">
                    @if($trends_direction['conversions'] === 'up') &#9650; @elseif($trends_direction['conversions'] === 'down') &#9660; @else - @endif
                </div>
                @endif
            </div>
            <div class="metric-box">
                <div class="metric-icon gray">%</div>
                <div class="metric-value">{{ number_format($statistics['cvr'], 2) }}%</div>
                <div class="metric-label">CVR</div>
            </div>
            <div class="metric-box">
                <div class="metric-icon orange">$</div>
                <div class="metric-value">{{ number_format($statistics['cpc'], 2) }}</div>
                <div class="metric-label">CPC (SAR)</div>
            </div>
            <div class="metric-box">
                <div class="metric-icon {{ $statistics['roas'] >= 2 ? 'green' : ($statistics['roas'] >= 1 ? 'orange' : 'red') }}">x</div>
                <div class="metric-value">{{ number_format($statistics['roas'], 2) }}x</div>
                <div class="metric-label">ROAS</div>
            </div>
        </div>

        {{-- Platform Analysis --}}
        <div class="section-title" style="margin-top: 20px;">Platform Analysis</div>

        @if(count($platform_breakdown) > 0)
        <div class="two-col">
            <div class="col">
                <div class="section-subtitle">Spend Distribution</div>
                @php
                    $colors = ['rb-red', 'rb-yellow', 'rb-pink', 'blue', 'green'];
                @endphp
                @foreach($platform_breakdown as $index => $platform)
                @php
                    $pct = $statistics['total_spend'] > 0 ? ($platform['spend'] / $statistics['total_spend']) * 100 : 0;
                    $color = $colors[$index % count($colors)];
                @endphp
                <div class="progress-item">
                    <div class="progress-header">
                        <span class="progress-label">
                            <span class="badge badge-{{ strtolower($platform['platform']) }}">{{ ucfirst($platform['platform']) }}</span>
                        </span>
                        <span class="progress-value">{{ number_format($platform['spend'], 0) }} SAR ({{ number_format($pct, 0) }}%)</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill {{ $color }}" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="col">
                <div class="section-subtitle">Platform Comparison</div>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th class="text-right">Clicks</th>
                            <th class="text-right">Conv</th>
                            <th class="text-right">CTR</th>
                            <th class="text-right">ROAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($platform_breakdown as $platform)
                        @php
                            $ctr = $platform['impressions'] > 0 ? ($platform['clicks'] / $platform['impressions']) * 100 : 0;
                            $platformRoas = $platform['spend'] > 0 ? ($platform['conversions'] * 100) / $platform['spend'] : 0;
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ ucfirst($platform['platform']) }}</td>
                            <td class="text-right">{{ number_format($platform['clicks']) }}</td>
                            <td class="text-right">{{ number_format($platform['conversions']) }}</td>
                            <td class="text-right">{{ number_format($ctr, 2) }}%</td>
                            <td class="text-right">
                                @if($platformRoas >= 2)
                                    <span class="badge badge-success">{{ number_format($platformRoas, 1) }}x</span>
                                @elseif($platformRoas >= 1)
                                    <span class="badge badge-warning">{{ number_format($platformRoas, 1) }}x</span>
                                @else
                                    <span class="badge badge-danger">{{ number_format($platformRoas, 1) }}x</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p style="color: #9ca3af; text-align: center; padding: 30px;">No platform data available for this period.</p>
        @endif
    </div>

    <div class="page-break"></div>

    {{-- ========== PAGE 4: CAMPAIGN PERFORMANCE ========== --}}
    <div class="content-wrapper">
        <div class="pdf-header">
            <div class="pdf-header-left">
                <div class="pdf-header-brand">RB Benchmarks</div>
                <div class="pdf-header-client">{{ $client->name }}</div>
            </div>
            <div class="pdf-header-right">
                <div class="pdf-header-info">{{ $period['start'] }} - {{ $period['end'] }}</div>
            </div>
        </div>

        <div class="section-title">Campaign Performance</div>

        @if(isset($top_campaigns) && count($top_campaigns) > 0)
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 22%;">Campaign</th>
                    <th class="text-right">Spend</th>
                    <th class="text-right">Impr</th>
                    <th class="text-right">Clicks</th>
                    <th class="text-right">CTR</th>
                    <th class="text-right">Conv</th>
                    <th class="text-right">CVR</th>
                    <th class="text-right">CPC</th>
                    <th class="text-right">ROAS</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSpend = 0;
                    $totalImpressions = 0;
                    $totalClicks = 0;
                    $totalConversions = 0;
                @endphp
                @foreach(array_slice($top_campaigns, 0, 20) as $campaign)
                @php
                    $ctr = ($campaign['impressions'] ?? 0) > 0 ? (($campaign['clicks'] ?? 0) / $campaign['impressions']) * 100 : 0;
                    $cvr = ($campaign['clicks'] ?? 0) > 0 ? (($campaign['conversions'] ?? 0) / $campaign['clicks']) * 100 : 0;
                    $cpc = ($campaign['clicks'] ?? 0) > 0 ? ($campaign['spend'] ?? 0) / $campaign['clicks'] : 0;

                    $totalSpend += $campaign['spend'] ?? 0;
                    $totalImpressions += $campaign['impressions'] ?? 0;
                    $totalClicks += $campaign['clicks'] ?? 0;
                    $totalConversions += $campaign['conversions'] ?? 0;
                @endphp
                <tr class="avoid-break">
                    <td>
                        <span class="font-semibold">{{ Str::limit($campaign['name'], 28) }}</span>
                        <br><span class="text-muted">{{ Str::limit($campaign['account_name'] ?? '', 24) }}</span>
                    </td>
                    <td class="text-right font-semibold">{{ number_format($campaign['spend'] ?? 0, 0) }}</td>
                    <td class="text-right">{{ number_format(($campaign['impressions'] ?? 0) / 1000, 1) }}K</td>
                    <td class="text-right">{{ number_format($campaign['clicks'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($ctr, 2) }}%</td>
                    <td class="text-right">{{ number_format($campaign['conversions'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($cvr, 2) }}%</td>
                    <td class="text-right">{{ number_format($cpc, 2) }}</td>
                    <td class="text-right">
                        @if(($campaign['roas'] ?? 0) >= 2)
                            <span class="badge badge-success">{{ number_format($campaign['roas'] ?? 0, 1) }}x</span>
                        @elseif(($campaign['roas'] ?? 0) >= 1)
                            <span class="badge badge-warning">{{ number_format($campaign['roas'] ?? 0, 1) }}x</span>
                        @else
                            <span class="badge badge-danger">{{ number_format($campaign['roas'] ?? 0, 1) }}x</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $totalCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
                    $totalCvr = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
                    $totalCpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
                    $totalRoas = $totalSpend > 0 ? ($totalConversions * 100) / $totalSpend : 0;
                @endphp
                <tr>
                    <td><strong>TOTALS</strong></td>
                    <td class="text-right">{{ number_format($totalSpend, 0) }}</td>
                    <td class="text-right">{{ number_format($totalImpressions > 0 ? $totalImpressions / 1000 : 0, 1) }}K</td>
                    <td class="text-right">{{ number_format($totalClicks) }}</td>
                    <td class="text-right">{{ number_format($totalCtr, 2) }}%</td>
                    <td class="text-right">{{ number_format($totalConversions) }}</td>
                    <td class="text-right">{{ number_format($totalCvr, 2) }}%</td>
                    <td class="text-right">{{ number_format($totalCpc, 2) }}</td>
                    <td class="text-right">{{ number_format($totalRoas, 1) }}x</td>
                </tr>
            </tfoot>
        </table>

        @if(count($top_campaigns) > 20)
        <p style="color: #9ca3af; font-size: 8pt; text-align: center; margin-top: 10px;">
            Showing top 20 campaigns by spend. {{ count($top_campaigns) - 20 }} additional campaigns not shown.
        </p>
        @endif
        @else
        <p style="color: #9ca3af; text-align: center; padding: 30px;">No campaign data available for this period.</p>
        @endif
    </div>

    <div class="page-break"></div>

    {{-- ========== PAGE 5: AD ACCOUNTS ========== --}}
    <div class="content-wrapper">
        <div class="pdf-header">
            <div class="pdf-header-left">
                <div class="pdf-header-brand">RB Benchmarks</div>
                <div class="pdf-header-client">{{ $client->name }}</div>
            </div>
            <div class="pdf-header-right">
                <div class="pdf-header-info">{{ $period['start'] }} - {{ $period['end'] }}</div>
            </div>
        </div>

        <div class="section-title">Connected Ad Accounts</div>

        @if($ad_accounts_count > 0)
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 30%;">Account Name</th>
                    <th>Platform</th>
                    <th>Industry</th>
                    <th>Status</th>
                    <th style="width: 18%;">Account ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ad_accounts as $account)
                <tr class="avoid-break">
                    <td class="font-semibold">{{ Str::limit($account->account_name, 35) }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower($account->integration->platform ?? 'neutral') }}">
                            {{ ucfirst($account->integration->platform ?? 'N/A') }}
                        </span>
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $account->industry ?? '-')) }}</td>
                    <td>
                        @if($account->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($account->status === 'paused')
                            <span class="badge badge-warning">Paused</span>
                        @else
                            <span class="badge badge-neutral">{{ ucfirst($account->status) }}</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ Str::limit($account->external_account_id, 18) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 50%;">
                    <div style="font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Total Accounts</div>
                    <div style="font-size: 18pt; font-weight: 700; color: #111827;">{{ $ad_accounts_count }}</div>
                </div>
                <div style="display: table-cell; width: 50%; text-align: right;">
                    <div style="font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Active Accounts</div>
                    <div style="font-size: 18pt; font-weight: 700; color: #10b981;">{{ $ad_accounts->where('status', 'active')->count() }}</div>
                </div>
            </div>
        </div>
        @else
        <p style="color: #9ca3af; text-align: center; padding: 30px;">No ad accounts connected.</p>
        @endif

        {{-- Report Metadata --}}
        <div style="margin-top: 30px; padding: 15px; background: #1f2937; border-radius: 8px; color: #fff;">
            <div style="font-size: 10pt; font-weight: 600; margin-bottom: 10px; color: #F93549;">Report Information</div>
            <div style="display: table; width: 100%; font-size: 8pt;">
                <div style="display: table-cell; width: 50%;">
                    <div style="color: #9ca3af;">Generated</div>
                    <div style="color: #fff; font-weight: 500;">{{ $generated_at }}</div>
                </div>
                <div style="display: table-cell; width: 50%; text-align: right;">
                    <div style="color: #9ca3af;">Platform</div>
                    <div style="color: #fff; font-weight: 500;">RB Benchmarks</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer with page numbers --}}
    <div class="pdf-footer">
        <div class="pdf-footer-left">
            <strong style="color: #F93549;">RB Benchmarks</strong> &bull; Performance Analytics Platform
        </div>
        <div class="pdf-footer-right">
            Generated: {{ $generated_at }}
        </div>
    </div>

    {{-- Page numbering script --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() / 2) - $width;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.42, 0.45, 0.49));
        }
    </script>
</body>
</html>
