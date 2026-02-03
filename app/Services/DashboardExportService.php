<?php

namespace App\Services;

use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardExportService
{
    protected $dashboardService;

    public function __construct(ClientDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Export dashboard to PDF with client branding
     */
    public function exportToPdf(Tenant $client, array $filters = []): string
    {
        $data = $this->prepareDashboardData($client, $filters);

        // Generate PDF with client branding
        $pdf = Pdf::loadView('exports.dashboard-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        // Generate unique filename
        $filename = $this->generateFilename($client, 'pdf');
        $path = 'exports/' . $filename;

        // Ensure directory exists
        $directory = storage_path('app/exports');
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }

        // Save PDF directly to avoid Storage facade issues
        file_put_contents(storage_path('app/' . $path), $pdf->output());

        return $path;
    }

    /**
     * Export dashboard to CSV
     */
    public function exportToCsv(Tenant $client, array $filters = []): string
    {
        $data = $this->prepareDashboardData($client, $filters);

        $filename = $this->generateFilename($client, 'csv');
        $path = 'exports/' . $filename;

        // Generate CSV content
        $csv = $this->generateCsvContent($data);

        Storage::put($path, $csv);

        return $path;
    }

    /**
     * Export dashboard to Excel
     */
    public function exportToExcel(Tenant $client, array $filters = []): string
    {
        // For now, return CSV (can be enhanced with PhpSpreadsheet later)
        return $this->exportToCsv($client, $filters);
    }

    /**
     * Prepare comprehensive dashboard data for export
     */
    protected function prepareDashboardData(Tenant $client, array $filters): array
    {
        $period = $filters['period'] ?? 30;
        $platform = $filters['platform'] ?? null;

        // Get date range
        $endDate = isset($filters['to']) ? Carbon::parse($filters['to']) : Carbon::now();
        $startDate = isset($filters['from']) ? Carbon::parse($filters['from']) : $endDate->copy()->subDays($period);

        // Get statistics
        $statistics = $this->dashboardService->getClientStatistics($client, $filters);

        // Get trends
        $trends = $this->dashboardService->getPerformanceTrends($client, $filters);

        // Get platform breakdown
        $platformBreakdown = $this->dashboardService->getPlatformBreakdown($client, $filters);

        // Get top campaigns (20 campaigns for expanded PDF)
        $topCampaigns = $this->dashboardService->getTopCampaigns($client, 20, $filters);

        // Get ad accounts with integrations
        $adAccounts = $client->adAccounts()
            ->with('integration')
            ->when($platform, fn($q) => $q->whereHas('integration', fn($q2) => $q2->where('platform', $platform)))
            ->get();

        // Get industry info
        $industry = $this->getIndustryName($client->industry);

        // Generate sparklines from trends data
        $sparklines = $this->generateSparklines($trends);

        // Calculate trend directions and percentages
        $trendsAnalysis = $this->analyzeTrends($trends);

        // Generate executive summary
        $executiveSummary = $this->generateExecutiveSummary($statistics, $platformBreakdown, $topCampaigns);

        return [
            'client' => $client,
            'logo_url' => $this->getClientLogoPath($client),
            'generated_at' => Carbon::now()->format('F d, Y \a\t H:i'),
            'period' => [
                'days' => $period,
                'start' => $startDate->format('M d, Y'),
                'end' => $endDate->format('M d, Y'),
            ],
            'statistics' => $statistics,
            'trends' => $trends,
            'sparklines' => $sparklines,
            'trends_direction' => $trendsAnalysis['direction'],
            'trends_percent' => $trendsAnalysis['percent'],
            'executive_summary' => $executiveSummary,
            'platform_breakdown' => $platformBreakdown,
            'top_campaigns' => $topCampaigns,
            'ad_accounts' => $adAccounts,
            'ad_accounts_count' => $adAccounts->count(),
            'industry' => $industry,
            'subscription_tier' => ucfirst($client->subscription_tier ?? 'Standard'),
        ];
    }

    /**
     * Get client logo path for PDF (must be absolute path)
     */
    protected function getClientLogoPath(Tenant $client): ?string
    {
        if (!$client->logo) {
            return null;
        }

        $storagePath = storage_path('app/public/' . $client->logo);

        return file_exists($storagePath) ? $storagePath : null;
    }

    /**
     * Generate unique filename for export
     */
    protected function generateFilename(Tenant $client, string $extension): string
    {
        $slug = Str::slug($client->name);
        $sanitized = $this->sanitizeFilename($slug);
        $timestamp = Carbon::now()->format('Y-m-d_His');

        return "{$sanitized}_dashboard_{$timestamp}.{$extension}";
    }

    /**
     * Sanitize filename to prevent HTTP header errors
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove newlines, carriage returns, and null bytes
        $filename = str_replace(["\n", "\r", "\0", "\t"], '', $filename);

        // Remove any control characters (ASCII 0-31 and 127)
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Keep only alphanumeric, underscore, hyphen, and dot
        $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $filename);

        // Remove multiple consecutive underscores/hyphens
        $filename = preg_replace('/[_\-]+/', '_', $filename);

        // Limit length to 200 characters (safe for most filesystems)
        $filename = substr($filename, 0, 200);

        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'export_' . uniqid();
        }

        return $filename;
    }

    /**
     * Generate CSV content from dashboard data
     */
    protected function generateCsvContent(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['Client Dashboard Report']);
        fputcsv($output, ['Client', $data['client']->name]);
        fputcsv($output, ['Industry', $data['industry']]);
        fputcsv($output, ['Period', $data['period']['start'] . ' - ' . $data['period']['end']]);
        fputcsv($output, ['Generated', $data['generated_at']]);
        fputcsv($output, []);

        // Statistics
        fputcsv($output, ['Overall Statistics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Spend', '$' . number_format($data['statistics']['total_spend'], 2)]);
        fputcsv($output, ['Total Impressions', number_format($data['statistics']['total_impressions'])]);
        fputcsv($output, ['Total Clicks', number_format($data['statistics']['total_clicks'])]);
        fputcsv($output, ['Total Conversions', number_format($data['statistics']['total_conversions'])]);
        fputcsv($output, ['CTR', number_format($data['statistics']['ctr'], 2) . '%']);
        fputcsv($output, ['CVR', number_format($data['statistics']['cvr'], 2) . '%']);
        fputcsv($output, ['CPC', '$' . number_format($data['statistics']['cpc'], 2)]);
        fputcsv($output, ['ROAS', number_format($data['statistics']['roas'], 2) . 'x']);
        fputcsv($output, []);

        // Platform Breakdown
        fputcsv($output, ['Platform Breakdown']);
        fputcsv($output, ['Platform', 'Accounts', 'Spend', 'Impressions', 'Clicks', 'Conversions']);
        foreach ($data['platform_breakdown'] as $platform) {
            fputcsv($output, [
                ucfirst($platform['platform']),
                $platform['accounts_count'],
                '$' . number_format($platform['total_spend'], 2),
                number_format($platform['total_impressions']),
                number_format($platform['total_clicks']),
                number_format($platform['total_conversions']),
            ]);
        }
        fputcsv($output, []);

        // Ad Accounts
        fputcsv($output, ['Ad Accounts']);
        fputcsv($output, ['Account Name', 'Platform', 'Industry', 'Status']);
        foreach ($data['ad_accounts'] as $account) {
            fputcsv($output, [
                $account->account_name,
                ucfirst($account->integration->platform ?? 'N/A'),
                ucfirst(str_replace('_', ' ', $account->industry ?? 'N/A')),
                ucfirst($account->status),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate SVG sparkline from data points
     */
    protected function generateSparkline(array $data, int $width = 60, int $height = 20, string $color = '#10b981'): string
    {
        if (empty($data) || count($data) < 2) {
            return '';
        }

        $values = array_values($data);
        $count = count($values);
        $max = max($values) ?: 1;
        $min = min($values);
        $range = $max - $min ?: 1;

        // Calculate points
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $x = ($i / ($count - 1)) * $width;
            $y = $height - (($values[$i] - $min) / $range) * ($height - 4) - 2;
            $points[] = round($x, 1) . ',' . round($y, 1);
        }

        $pointsStr = implode(' ', $points);

        // Determine color based on trend
        $firstHalf = array_slice($values, 0, (int)($count / 2));
        $secondHalf = array_slice($values, (int)($count / 2));
        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        $trendColor = $secondAvg >= $firstAvg ? '#10b981' : '#ef4444';

        return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">'
            . '<polyline fill="none" stroke="' . $trendColor . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" points="' . $pointsStr . '"/>'
            . '</svg>';
    }

    /**
     * Generate sparklines for all key metrics
     */
    protected function generateSparklines(array $trends): array
    {
        $sparklines = [
            'spend' => '',
            'impressions' => '',
            'clicks' => '',
            'conversions' => '',
        ];

        if (empty($trends)) {
            return $sparklines;
        }

        // Extract daily values for each metric
        $spendData = [];
        $impressionsData = [];
        $clicksData = [];
        $conversionsData = [];

        foreach ($trends as $day) {
            $spendData[] = $day['spend'] ?? 0;
            $impressionsData[] = $day['impressions'] ?? 0;
            $clicksData[] = $day['clicks'] ?? 0;
            $conversionsData[] = $day['conversions'] ?? 0;
        }

        $sparklines['spend'] = $this->generateSparkline($spendData);
        $sparklines['impressions'] = $this->generateSparkline($impressionsData);
        $sparklines['clicks'] = $this->generateSparkline($clicksData);
        $sparklines['conversions'] = $this->generateSparkline($conversionsData);

        return $sparklines;
    }

    /**
     * Analyze trends to determine direction and percentage change
     */
    protected function analyzeTrends(array $trends): array
    {
        $direction = [
            'spend' => 'flat',
            'impressions' => 'flat',
            'clicks' => 'flat',
            'conversions' => 'flat',
            'ctr' => 'flat',
            'cvr' => 'flat',
            'cpc' => 'flat',
            'roas' => 'flat',
        ];

        $percent = [
            'spend' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'ctr' => 0,
            'cvr' => 0,
            'cpc' => 0,
            'roas' => 0,
        ];

        if (empty($trends) || count($trends) < 2) {
            return ['direction' => $direction, 'percent' => $percent];
        }

        $count = count($trends);
        $midpoint = (int)($count / 2);

        $firstHalf = array_slice($trends, 0, $midpoint);
        $secondHalf = array_slice($trends, $midpoint);

        $metrics = ['spend', 'impressions', 'clicks', 'conversions'];

        foreach ($metrics as $metric) {
            $firstSum = array_sum(array_column($firstHalf, $metric));
            $secondSum = array_sum(array_column($secondHalf, $metric));

            if ($firstSum > 0) {
                $change = (($secondSum - $firstSum) / $firstSum) * 100;
                $percent[$metric] = round($change, 1);
                $direction[$metric] = $change > 2 ? 'up' : ($change < -2 ? 'down' : 'flat');
            }
        }

        // Calculate derived metrics trends
        $firstImpressions = array_sum(array_column($firstHalf, 'impressions'));
        $secondImpressions = array_sum(array_column($secondHalf, 'impressions'));
        $firstClicks = array_sum(array_column($firstHalf, 'clicks'));
        $secondClicks = array_sum(array_column($secondHalf, 'clicks'));

        // CTR trend
        $firstCtr = $firstImpressions > 0 ? ($firstClicks / $firstImpressions) * 100 : 0;
        $secondCtr = $secondImpressions > 0 ? ($secondClicks / $secondImpressions) * 100 : 0;
        if ($firstCtr > 0) {
            $ctrChange = (($secondCtr - $firstCtr) / $firstCtr) * 100;
            $percent['ctr'] = round($ctrChange, 1);
            $direction['ctr'] = $ctrChange > 2 ? 'up' : ($ctrChange < -2 ? 'down' : 'flat');
        }

        return ['direction' => $direction, 'percent' => $percent];
    }

    /**
     * Generate executive summary with auto-generated insights
     */
    protected function generateExecutiveSummary(array $stats, array $platforms, array $campaigns): array
    {
        $insights = [];
        $health = 'healthy';
        $healthScore = 100;

        // ROAS Assessment
        $roas = $stats['roas'] ?? 0;
        if ($roas >= 3) {
            $insights[] = [
                'type' => 'success',
                'icon' => '★',
                'text' => 'Excellent ROAS of ' . number_format($roas, 2) . 'x - campaigns are highly profitable.',
            ];
        } elseif ($roas >= 2) {
            $insights[] = [
                'type' => 'success',
                'icon' => '✓',
                'text' => 'Strong ROAS of ' . number_format($roas, 2) . 'x indicates healthy return on ad spend.',
            ];
        } elseif ($roas >= 1) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '!',
                'text' => 'ROAS of ' . number_format($roas, 2) . 'x is breaking even. Consider optimizing underperforming campaigns.',
            ];
            $healthScore -= 20;
        } else {
            $insights[] = [
                'type' => 'danger',
                'icon' => '✗',
                'text' => 'ROAS of ' . number_format($roas, 2) . 'x indicates negative returns. Immediate optimization needed.',
            ];
            $healthScore -= 40;
        }

        // Top Platform Analysis
        if (!empty($platforms)) {
            $topPlatform = $platforms[0];
            $platformSpend = $topPlatform['spend'] ?? 0;
            $totalSpend = ($stats['total_spend'] ?? 0) > 0 ? $stats['total_spend'] : 1;
            $platformPct = ($platformSpend / $totalSpend) * 100;

            $insights[] = [
                'type' => 'info',
                'icon' => '◆',
                'text' => ucfirst($topPlatform['platform']) . ' leads with ' . number_format($platformPct, 0) . '% of total spend (' . number_format($platformSpend, 0) . ' SAR).',
            ];
        }

        // Best Campaign Highlight
        if (!empty($campaigns)) {
            $bestCampaign = null;
            $bestRoas = 0;
            foreach ($campaigns as $campaign) {
                if (($campaign['roas'] ?? 0) > $bestRoas && ($campaign['spend'] ?? 0) > 100) {
                    $bestRoas = $campaign['roas'];
                    $bestCampaign = $campaign;
                }
            }

            if ($bestCampaign && $bestRoas >= 2) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => '▲',
                    'text' => 'Top performer: "' . Str::limit($bestCampaign['name'], 30) . '" with ' . number_format($bestRoas, 1) . 'x ROAS.',
                ];
            }
        }

        // CTR Assessment
        $ctr = $stats['ctr'] ?? 0;
        if ($ctr >= 2) {
            $insights[] = [
                'type' => 'success',
                'icon' => '●',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is above industry average, showing strong ad relevance.',
            ];
        } elseif ($ctr >= 1) {
            $insights[] = [
                'type' => 'info',
                'icon' => '○',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is within normal range.',
            ];
        } else {
            $insights[] = [
                'type' => 'warning',
                'icon' => '!',
                'text' => 'CTR of ' . number_format($ctr, 2) . '% is below average. Review ad creative and targeting.',
            ];
            $healthScore -= 15;
        }

        // Conversion Assessment
        $conversions = $stats['total_conversions'] ?? 0;
        $cvr = $stats['cvr'] ?? 0;
        if ($conversions > 0) {
            if ($cvr >= 3) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => '✓',
                    'text' => 'Strong conversion rate of ' . number_format($cvr, 2) . '% with ' . number_format($conversions) . ' total conversions.',
                ];
            } elseif ($cvr < 1 && $conversions < 10) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => '!',
                    'text' => 'Low conversion volume (' . number_format($conversions) . '). Consider expanding reach or improving landing pages.',
                ];
                $healthScore -= 10;
            }
        }

        // Determine overall health
        if ($healthScore >= 80) {
            $health = 'healthy';
        } elseif ($healthScore >= 50) {
            $health = 'needs_attention';
        } else {
            $health = 'critical';
        }

        return [
            'insights' => array_slice($insights, 0, 5), // Max 5 insights
            'health' => $health,
            'health_score' => $healthScore,
        ];
    }

    /**
     * Get human-readable industry name
     */
    protected function getIndustryName(?string $industry): string
    {
        $industries = [
            'real_estate' => 'Real Estate',
            'ecommerce' => 'E-commerce',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'automotive' => 'Automotive',
            'finance' => 'Finance & Banking',
            'retail' => 'Retail',
            'technology' => 'Technology',
            'hospitality' => 'Hospitality & Travel',
            'food_beverage' => 'Food & Beverage',
            'fashion' => 'Fashion & Apparel',
            'fitness' => 'Fitness & Wellness',
            'entertainment' => 'Entertainment & Media',
            'home_services' => 'Home Services',
            'professional_services' => 'Professional Services',
        ];

        return $industries[$industry] ?? ucfirst(str_replace('_', ' ', $industry ?? 'General'));
    }
}
