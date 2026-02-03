<?php

namespace App\Services;

use App\Models\ScheduledReport;
use App\Models\ReportHistory;
use App\Models\AdMetric;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportGenerationService
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Generate a report
     */
    public function generateReport(ScheduledReport $scheduledReport, ?array $formats = null): array
    {
        try {
            // Use specified formats or fall back to scheduled report's formats
            $formats = $formats ?? $scheduledReport->export_formats ?? ['pdf'];

            // Create report history entry
            $history = ReportHistory::create([
                'scheduled_report_id' => $scheduledReport->id,
                'tenant_id' => $scheduledReport->tenant_id,
                'status' => 'generating',
                'filters_snapshot' => $scheduledReport->filters,
                'format' => $formats[0], // Primary format
            ]);

            // Fetch report data
            $data = $this->fetchReportData($scheduledReport);

            // Generate files for each format
            $files = [];
            foreach ($formats as $format) {
                $file = $this->generateFile($scheduledReport, $data, $format);
                if ($file) {
                    $files[] = $file;
                }
            }

            // Update history with first file
            if (!empty($files)) {
                $primaryFile = $files[0];
                $history->markAsCompleted($primaryFile['path'], $primaryFile['size']);
            } else {
                $history->markAsFailed('No files generated');
            }

            return [
                'success' => true,
                'history' => $history,
                'files' => $files,
            ];
        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'scheduled_report_id' => $scheduledReport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($history)) {
                $history->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch data for report
     */
    protected function fetchReportData(ScheduledReport $scheduledReport): array
    {
        $filters = $scheduledReport->filters ?? [];
        $metrics = $scheduledReport->metrics ?? $this->getDefaultMetrics($scheduledReport->report_type);

        return match ($scheduledReport->report_type) {
            'performance' => $this->fetchPerformanceData($filters, $metrics),
            'benchmark' => $this->fetchBenchmarkData($filters, $metrics),
            'campaign' => $this->fetchCampaignData($filters, $metrics),
            'account' => $this->fetchAccountData($filters, $metrics),
            'industry' => $this->fetchIndustryData($filters, $metrics),
            default => [],
        };
    }

    /**
     * Fetch performance data
     */
    protected function fetchPerformanceData(array $filters, array $metrics): array
    {
        $dateRange = $this->getDateRange($filters);
        $objective = $filters['objective'] ?? null;

        $query = AdMetric::whereBetween('date', $dateRange);

        if ($objective) {
            $query->where('objective', $objective);
        }

        // Get aggregated data
        $totals = $query->select(DB::raw('
            SUM(spend) as total_spend,
            SUM(impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(conversions) as total_conversions,
            SUM(leads) as total_leads,
            SUM(calls) as total_calls,
            SUM(purchases) as total_purchases,
            SUM(revenue) as total_revenue
        '))->first();

        // Get daily breakdown
        $dailyData = $query->select(
            'date',
            DB::raw('SUM(spend) as spend'),
            DB::raw('SUM(impressions) as impressions'),
            DB::raw('SUM(clicks) as clicks'),
            DB::raw('SUM(conversions) as conversions'),
            DB::raw('SUM(leads) as leads')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'title' => 'Performance Report',
            'date_range' => [
                'from' => Carbon::parse($dateRange[0])->format('M d, Y'),
                'to' => Carbon::parse($dateRange[1])->format('M d, Y'),
            ],
            'objective' => $objective,
            'totals' => $this->calculateMetrics($totals),
            'daily_data' => $dailyData,
            'metrics' => $metrics,
        ];
    }

    /**
     * Fetch benchmark data
     */
    protected function fetchBenchmarkData(array $filters, array $metrics): array
    {
        $dateRange = $this->getDateRange($filters);

        // Get data grouped by objective
        $objectiveData = AdMetric::whereBetween('date', $dateRange)
            ->select(
                'objective',
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(leads) as total_leads'),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->groupBy('objective')
            ->get()
            ->map(function ($row) {
                return $this->calculateMetrics($row, $row->objective);
            });

        return [
            'title' => 'Benchmark Report',
            'date_range' => [
                'from' => Carbon::parse($dateRange[0])->format('M d, Y'),
                'to' => Carbon::parse($dateRange[1])->format('M d, Y'),
            ],
            'objective_data' => $objectiveData,
            'metrics' => $metrics,
        ];
    }

    /**
     * Fetch campaign data
     */
    protected function fetchCampaignData(array $filters, array $metrics): array
    {
        $dateRange = $this->getDateRange($filters);
        $campaignId = $filters['campaign_id'] ?? null;

        $query = AdMetric::whereBetween('date', $dateRange);

        if ($campaignId) {
            $query->where('ad_campaign_id', $campaignId);
        }

        // Get campaign-level data
        $campaignData = $query->select(
            'ad_campaign_id',
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(conversions) as total_conversions'),
            DB::raw('SUM(leads) as total_leads')
        )
            ->groupBy('ad_campaign_id')
            ->get()
            ->map(function ($row) {
                $campaign = AdCampaign::find($row->ad_campaign_id);
                $metrics = $this->calculateMetrics($row);
                $metrics['campaign_name'] = $campaign->name ?? 'Unknown';
                return $metrics;
            });

        return [
            'title' => 'Campaign Report',
            'date_range' => [
                'from' => Carbon::parse($dateRange[0])->format('M d, Y'),
                'to' => Carbon::parse($dateRange[1])->format('M d, Y'),
            ],
            'campaign_data' => $campaignData,
            'metrics' => $metrics,
        ];
    }

    /**
     * Fetch account data
     */
    protected function fetchAccountData(array $filters, array $metrics): array
    {
        $dateRange = $this->getDateRange($filters);
        $accountId = $filters['account_id'] ?? null;

        $query = AdMetric::whereBetween('date', $dateRange);

        if ($accountId) {
            $query->where('ad_account_id', $accountId);
        }

        // Get account-level data
        $accountData = $query->select(
            'ad_account_id',
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(conversions) as total_conversions'),
            DB::raw('SUM(leads) as total_leads')
        )
            ->groupBy('ad_account_id')
            ->get()
            ->map(function ($row) {
                $account = AdAccount::find($row->ad_account_id);
                $metrics = $this->calculateMetrics($row);
                $metrics['account_name'] = $account->name ?? 'Unknown';
                $metrics['platform'] = $account->platform ?? 'Unknown';
                return $metrics;
            });

        return [
            'title' => 'Account Report',
            'date_range' => [
                'from' => Carbon::parse($dateRange[0])->format('M d, Y'),
                'to' => Carbon::parse($dateRange[1])->format('M d, Y'),
            ],
            'account_data' => $accountData,
            'metrics' => $metrics,
        ];
    }

    /**
     * Fetch industry data
     */
    protected function fetchIndustryData(array $filters, array $metrics): array
    {
        // This would fetch industry benchmark data
        // For now, returning placeholder
        return [
            'title' => 'Industry Report',
            'message' => 'Industry report coming soon',
        ];
    }

    /**
     * Calculate metrics from aggregated data
     */
    protected function calculateMetrics($row, ?string $objective = null): array
    {
        $spend = (float) ($row->total_spend ?? 0);
        $impressions = (float) ($row->total_impressions ?? 0);
        $clicks = (float) ($row->total_clicks ?? 0);
        $conversions = (float) ($row->total_conversions ?? 0);
        $leads = (float) ($row->total_leads ?? 0);
        $revenue = (float) ($row->total_revenue ?? 0);

        return [
            'objective' => $objective,
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'leads' => $leads,
            'revenue' => $revenue,
            'cpc' => $clicks > 0 ? $spend / $clicks : 0,
            'cpm' => $impressions > 0 ? ($spend / $impressions) * 1000 : 0,
            'cpl' => $leads > 0 ? $spend / $leads : 0,
            'cpa' => $conversions > 0 ? $spend / $conversions : 0,
            'roas' => $spend > 0 ? $revenue / $spend : 0,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
            'cvr' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
        ];
    }

    /**
     * Get date range from filters
     */
    protected function getDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'last_30_days';

        return match ($period) {
            'today' => [Carbon::today()->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'yesterday' => [Carbon::yesterday()->format('Y-m-d'), Carbon::yesterday()->format('Y-m-d')],
            'last_7_days' => [Carbon::now()->subDays(7)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'last_30_days' => [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'this_month' => [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
            'last_month' => [
                Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d')
            ],
            'custom' => [
                $filters['date_from'] ?? Carbon::now()->subDays(30)->format('Y-m-d'),
                $filters['date_to'] ?? Carbon::today()->format('Y-m-d')
            ],
            default => [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::today()->format('Y-m-d')],
        };
    }

    /**
     * Get default metrics for report type
     */
    protected function getDefaultMetrics(string $reportType): array
    {
        return ['spend', 'impressions', 'clicks', 'conversions', 'leads', 'cpc', 'cpm', 'cpl', 'ctr'];
    }

    /**
     * Generate file in specified format
     */
    protected function generateFile(ScheduledReport $scheduledReport, array $data, string $format): ?array
    {
        return match ($format) {
            'csv' => $this->generateCSV($scheduledReport, $data),
            'excel' => $this->generateExcel($scheduledReport, $data),
            'pdf' => $this->generatePDF($scheduledReport, $data),
            default => null,
        };
    }

    /**
     * Generate CSV file
     */
    protected function generateCSV(ScheduledReport $scheduledReport, array $data): array
    {
        $fileName = $this->generateFileName($scheduledReport, 'csv');
        $filePath = 'reports/' . $fileName;

        $csv = [];

        // Title
        $csv[] = [$data['title'] ?? 'Report'];
        $csv[] = ['Date Range', $data['date_range']['from'] . ' - ' . $data['date_range']['to']];
        $csv[] = []; // Empty row

        // Data based on report type
        if (isset($data['totals'])) {
            $csv[] = ['Metric', 'Value'];
            foreach ($data['totals'] as $key => $value) {
                if (is_numeric($value)) {
                    $csv[] = [ucwords(str_replace('_', ' ', $key)), number_format($value, 2)];
                }
            }
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Store file
        Storage::put($filePath, $csvContent);

        return [
            'path' => $filePath,
            'size' => Storage::size($filePath),
            'format' => 'csv',
        ];
    }

    /**
     * Generate Excel file (placeholder)
     */
    protected function generateExcel(ScheduledReport $scheduledReport, array $data): array
    {
        // For now, generate CSV as Excel placeholder
        // In production, use PhpSpreadsheet
        return $this->generateCSV($scheduledReport, $data);
    }

    /**
     * Generate PDF file (placeholder)
     */
    protected function generatePDF(ScheduledReport $scheduledReport, array $data): array
    {
        // For now, generate a simple text file as PDF placeholder
        // In production, use dompdf or similar
        $fileName = $this->generateFileName($scheduledReport, 'pdf');
        $filePath = 'reports/' . $fileName;

        $content = ($data['title'] ?? 'Report') . "\n\n";
        $content .= "Date Range: " . $data['date_range']['from'] . ' - ' . $data['date_range']['to'] . "\n\n";

        if (isset($data['totals'])) {
            $content .= "Summary:\n";
            foreach ($data['totals'] as $key => $value) {
                if (is_numeric($value)) {
                    $content .= ucwords(str_replace('_', ' ', $key)) . ': ' . number_format($value, 2) . "\n";
                }
            }
        }

        Storage::put($filePath, $content);

        return [
            'path' => $filePath,
            'size' => Storage::size($filePath),
            'format' => 'pdf',
        ];
    }

    /**
     * Generate unique file name
     */
    protected function generateFileName(ScheduledReport $scheduledReport, string $extension): string
    {
        $slug = str_replace(' ', '_', strtolower($scheduledReport->name));
        $timestamp = Carbon::now()->format('Y-m-d_His');

        return "{$slug}_{$timestamp}.{$extension}";
    }
}
