<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\SeoTechnicalIssue;
use App\Models\Tenant;
use App\Services\GA4AnalyticsService;
use App\Services\PageSpeedService;
use App\Services\SearchConsoleService;
use App\Helpers\SpreadsheetSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SeoReportExportController extends Controller
{
    public function pdf(Request $request, int $id)
    {
        ['client' => $client, 'report' => $report, 'startDate' => $startDate, 'endDate' => $endDate, 'period' => $period] = $this->gatherSeoReportData($request, $id);

        $pdf = Pdf::loadView('exports.seo-report-pdf', [
            'client' => $client,
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = str_replace(' ', '_', $client->name) . '_SEO_Report_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function excel(Request $request, int $id)
    {
        $data = $this->gatherSeoReportData($request, $id);
        $client = $data['client'];
        $report = $data['report'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildSummarySheet($spreadsheet, $report);
        $this->buildScQueriesSheet($spreadsheet, $report);
        $this->buildScPagesSheet($spreadsheet, $report);
        $this->buildGa4TrafficSheet($spreadsheet, $report);
        $this->buildGa4TopPagesSheet($spreadsheet, $report);
        $this->buildGa4DevicesSheet($spreadsheet, $report);
        $this->buildGa4GeoSheet($spreadsheet, $report);
        $this->buildPageSpeedSheet($spreadsheet, $report);
        $this->buildTechnicalIssuesSheet($spreadsheet, $client);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = str_replace(' ', '_', $client->name) . '_SEO_Report_' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ------------------------------------------------------------------
    // Shared data gathering
    // ------------------------------------------------------------------

    private function gatherSeoReportData(Request $request, int $id): array
    {
        $client = Tenant::findOrFail($id);
        $period = (int) $request->input('period', 30);

        $endDate = now()->format('Y-m-d');
        $startDate = now()->subDays($period)->format('Y-m-d');

        $settings = $client->settings ?? [];
        $seo = $settings['seo'] ?? [];

        $integration = $this->findGoogleIntegration($client->id);

        $report = [];

        // Search Console
        $scSite = $seo['search_console_site'] ?? null;
        if ($scSite && $integration) {
            try {
                $searchConsole = app(SearchConsoleService::class);
                $queries = $searchConsole->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'query', 1000);
                $pages = $searchConsole->getSearchAnalytics($integration, $scSite, $startDate, $endDate, 'page', 1000);
                $timeseries = $searchConsole->getTimeSeries($integration, $scSite, $startDate, $endDate);

                $totalClicks = array_sum(array_column($timeseries, 'clicks'));
                $totalImpressions = array_sum(array_column($timeseries, 'impressions'));
                $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
                $avgPosition = count($timeseries) > 0
                    ? round(array_sum(array_column($timeseries, 'position')) / count($timeseries), 1)
                    : 0;

                $report['search_console'] = [
                    'summary' => [
                        'total_clicks' => $totalClicks,
                        'total_impressions' => $totalImpressions,
                        'avg_ctr' => $avgCtr,
                        'avg_position' => $avgPosition,
                    ],
                    'timeseries' => $timeseries,
                    'queries' => $queries,
                    'pages' => $pages,
                ];
            } catch (\Exception $e) {
                $report['search_console'] = null;
            }
        }

        // GA4
        $ga4PropertyId = $seo['ga4_property_id'] ?? null;
        if ($ga4PropertyId && $integration) {
            try {
                $ga4 = app(GA4AnalyticsService::class);
                $report['ga4'] = $ga4->getReport($integration, $ga4PropertyId, $startDate, $endDate);
            } catch (\Exception $e) {
                $report['ga4'] = null;
            }
        }

        // PageSpeed
        $pageSpeedUrl = $seo['pagespeed_url'] ?? ($client->website ?? null);
        if ($pageSpeedUrl) {
            try {
                $pagespeed = app(PageSpeedService::class);
                $report['pagespeed_mobile'] = $pagespeed->analyze($pageSpeedUrl, 'mobile');
                $report['pagespeed_desktop'] = $pagespeed->analyze($pageSpeedUrl, 'desktop');
            } catch (\Exception $e) {
                $report['pagespeed_mobile'] = null;
                $report['pagespeed_desktop'] = null;
            }
        }

        return compact('client', 'report', 'startDate', 'endDate', 'period');
    }

    private function findGoogleIntegration(int $tenantId): ?Integration
    {
        return Integration::query()
            ->where('platform', 'google')
            ->where('status', 'active')
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('user_id', auth()->id());
            })
            ->orderByRaw('tenant_id = ? DESC', [$tenantId])
            ->first();
    }

    // ------------------------------------------------------------------
    // Sheet builders
    // ------------------------------------------------------------------

    private function buildSummarySheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'Summary KPIs');
        $this->writeHeaderRow($sheet, 1, ['Metric', 'Value']);

        $row = 2;

        // Search Console summary
        $sc = $report['search_console']['summary'] ?? null;
        $row = $this->writeSectionLabel($sheet, $row, 'Search Console');
        if ($sc) {
            $sheet->setCellValue("A{$row}", 'Total Clicks')->setCellValue("B{$row}", $sc['total_clicks']);
            $row++;
            $sheet->setCellValue("A{$row}", 'Total Impressions')->setCellValue("B{$row}", $sc['total_impressions']);
            $row++;
            $sheet->setCellValue("A{$row}", 'Average CTR (%)')->setCellValue("B{$row}", $sc['avg_ctr']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('0.00');
            $row++;
            $sheet->setCellValue("A{$row}", 'Average Position')->setCellValue("B{$row}", $sc['avg_position']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('0.0');
            $row++;
        } else {
            $sheet->setCellValue("A{$row}", 'No data available');
            $row++;
        }

        // GA4 summary
        $ga4 = $report['ga4']['summary'] ?? null;
        $row = $this->writeSectionLabel($sheet, $row, 'GA4 Analytics');
        if ($ga4) {
            foreach ($ga4 as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $sheet->setCellValue("A{$row}", $label)->setCellValue("B{$row}", $value);
                $row++;
            }
        } else {
            $sheet->setCellValue("A{$row}", 'No data available');
            $row++;
        }

        // PageSpeed Mobile
        $row = $this->writeSectionLabel($sheet, $row, 'PageSpeed - Mobile');
        $row = $this->writePageSpeedSummary($sheet, $row, $report['pagespeed_mobile'] ?? null);

        // PageSpeed Desktop
        $row = $this->writeSectionLabel($sheet, $row, 'PageSpeed - Desktop');
        $this->writePageSpeedSummary($sheet, $row, $report['pagespeed_desktop'] ?? null);

        $this->autoSizeColumns($sheet, ['A', 'B']);
    }

    private function writePageSpeedSummary($sheet, int $row, ?array $data): int
    {
        if (!$data) {
            $sheet->setCellValue("A{$row}", 'No data available');
            return $row + 1;
        }

        $scores = [
            'Performance' => $data['scores']['performance'] ?? null,
            'Accessibility' => $data['scores']['accessibility'] ?? null,
            'Best Practices' => $data['scores']['best_practices'] ?? $data['scores']['best-practices'] ?? null,
            'SEO' => $data['scores']['seo'] ?? null,
        ];

        foreach ($scores as $label => $value) {
            if ($value !== null) {
                $sheet->setCellValue("A{$row}", $label)->setCellValue("B{$row}", $value);
                $row++;
            }
        }

        // Core Web Vitals
        $cwv = $data['core_web_vitals'] ?? $data['coreWebVitals'] ?? null;
        if ($cwv) {
            foreach ($cwv as $key => $val) {
                $label = strtoupper(str_replace('_', ' ', $key));
                $display = is_array($val) ? ($val['display'] ?? ($val['value'] ?? json_encode($val))) : $val;
                $sheet->setCellValue("A{$row}", $label)->setCellValue("B{$row}", $display);
                $row++;
            }
        }

        return $row;
    }

    private function buildScQueriesSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'SC Queries');
        $headers = ['Query', 'Clicks', 'Impressions', 'CTR (%)', 'Position'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $queries = $report['search_console']['queries'] ?? [];
        if (empty($queries)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
            return;
        }

        $row = 2;
        foreach ($queries as $q) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($q['key'] ?? ''));
            $sheet->setCellValue("B{$row}", $q['clicks'] ?? 0);
            $sheet->setCellValue("C{$row}", $q['impressions'] ?? 0);
            $sheet->setCellValue("D{$row}", $q['ctr'] ?? 0);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->setCellValue("E{$row}", round($q['position'] ?? 0, 1));
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0');
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
    }

    private function buildScPagesSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'SC Pages');
        $headers = ['Page URL', 'Clicks', 'Impressions', 'CTR (%)', 'Position'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $pages = $report['search_console']['pages'] ?? [];
        if (empty($pages)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
            return;
        }

        $row = 2;
        foreach ($pages as $p) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($p['key'] ?? ''));
            $sheet->setCellValue("B{$row}", $p['clicks'] ?? 0);
            $sheet->setCellValue("C{$row}", $p['impressions'] ?? 0);
            $sheet->setCellValue("D{$row}", $p['ctr'] ?? 0);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->setCellValue("E{$row}", round($p['position'] ?? 0, 1));
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0');
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
    }

    private function buildGa4TrafficSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'GA4 Traffic Sources');
        $headers = ['Channel', 'Sessions', 'Users', 'Share (%)', 'Engagement Rate (%)', 'Revenue'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $sources = $report['ga4']['traffic_sources'] ?? [];
        if (empty($sources)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E', 'F']);
            return;
        }

        $row = 2;
        foreach ($sources as $s) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($s['channel'] ?? ($s['sessionDefaultChannelGroup'] ?? '')));
            $sheet->setCellValue("B{$row}", $s['sessions'] ?? 0);
            $sheet->setCellValue("C{$row}", $s['users'] ?? 0);
            $sheet->setCellValue("D{$row}", $s['share'] ?? 0);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->setCellValue("E{$row}", $s['engagement_rate'] ?? 0);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('0.0');
            $sheet->setCellValue("F{$row}", $s['revenue'] ?? 0);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E', 'F']);
    }

    private function buildGa4TopPagesSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'GA4 Top Pages');
        $headers = ['Page Path', 'Views', 'Users', 'Sessions', 'Revenue'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $pages = $report['ga4']['top_pages'] ?? [];
        if (empty($pages)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
            return;
        }

        $row = 2;
        foreach ($pages as $p) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($p['page_path'] ?? ''));
            $sheet->setCellValue("B{$row}", $p['page_views'] ?? 0);
            $sheet->setCellValue("C{$row}", $p['users'] ?? 0);
            $sheet->setCellValue("D{$row}", $p['sessions'] ?? 0);
            $sheet->setCellValue("E{$row}", $p['revenue'] ?? 0);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E']);
    }

    private function buildPageSpeedSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'PageSpeed');
        $this->writeHeaderRow($sheet, 1, ['Metric', 'Mobile', 'Desktop']);

        $mobile = $report['pagespeed_mobile'] ?? null;
        $desktop = $report['pagespeed_desktop'] ?? null;

        if (!$mobile && !$desktop) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C']);
            return;
        }

        $row = 2;

        // Scores
        $scoreKeys = [
            'performance' => 'Performance',
            'accessibility' => 'Accessibility',
            'best_practices' => 'Best Practices',
            'seo' => 'SEO',
        ];

        foreach ($scoreKeys as $key => $label) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($label));
            $mVal = $mobile['scores'][$key] ?? $mobile['scores'][str_replace('_', '-', $key)] ?? null;
            $dVal = $desktop['scores'][$key] ?? $desktop['scores'][str_replace('_', '-', $key)] ?? null;
            $sheet->setCellValue("B{$row}", $mVal !== null ? $mVal : '-');
            $sheet->setCellValue("C{$row}", $dVal !== null ? $dVal : '-');
            $row++;
        }

        // Core Web Vitals section
        $row = $this->writeSectionLabel($sheet, $row, 'Core Web Vitals');

        $mCwv = $mobile['core_web_vitals'] ?? $mobile['coreWebVitals'] ?? [];
        $dCwv = $desktop['core_web_vitals'] ?? $desktop['coreWebVitals'] ?? [];

        $allKeys = array_unique(array_merge(array_keys($mCwv), array_keys($dCwv)));
        foreach ($allKeys as $key) {
            $label = strtoupper(str_replace('_', ' ', $key));
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($label));

            $mVal = $mCwv[$key] ?? null;
            $dVal = $dCwv[$key] ?? null;
            $sheet->setCellValue("B{$row}", is_array($mVal) ? ($mVal['display'] ?? ($mVal['value'] ?? '-')) : ($mVal ?? '-'));
            $sheet->setCellValue("C{$row}", is_array($dVal) ? ($dVal['display'] ?? ($dVal['value'] ?? '-')) : ($dVal ?? '-'));
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C']);
    }

    private function buildGa4DevicesSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'GA4 Devices');
        $headers = ['Device', 'Users', 'Sessions', 'Share (%)'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $devices = $report['ga4']['devices'] ?? [];
        $browsers = $report['ga4']['browsers'] ?? [];

        if (empty($devices) && empty($browsers)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D']);
            return;
        }

        $row = 2;

        if (!empty($devices)) {
            $row = $this->writeSectionLabel($sheet, $row, 'Devices');
            foreach ($devices as $d) {
                $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell(ucfirst($d['device'] ?? $d['device_category'] ?? 'Unknown')));
                $sheet->setCellValue("B{$row}", $d['users'] ?? 0);
                $sheet->setCellValue("C{$row}", $d['sessions'] ?? 0);
                $sheet->setCellValue("D{$row}", $d['share'] ?? 0);
                $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.0');
                $row++;
            }
        }

        if (!empty($browsers)) {
            $row = $this->writeSectionLabel($sheet, $row, 'Browsers');
            foreach ($browsers as $b) {
                $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($b['browser'] ?? 'Unknown'));
                $sheet->setCellValue("B{$row}", $b['users'] ?? 0);
                $sheet->setCellValue("C{$row}", $b['sessions'] ?? 0);
                $sheet->setCellValue("D{$row}", $b['share'] ?? 0);
                $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.0');
                $row++;
            }
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D']);
    }

    private function buildGa4GeoSheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $this->createSheet($spreadsheet, 'GA4 Geography');
        $headers = ['Country', 'Sessions', 'Users', 'Engagement Rate (%)'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $geo = $report['ga4']['geo'] ?? [];
        if (empty($geo)) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D']);
            return;
        }

        $row = 2;
        foreach ($geo as $g) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($g['country'] ?? 'Unknown'));
            $sheet->setCellValue("B{$row}", $g['sessions'] ?? 0);
            $sheet->setCellValue("C{$row}", $g['users'] ?? 0);
            $sheet->setCellValue("D{$row}", $g['engagement_rate'] ?? 0);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.0');
            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D']);
    }

    private function buildTechnicalIssuesSheet(Spreadsheet $spreadsheet, Tenant $client): void
    {
        $sheet = $this->createSheet($spreadsheet, 'Technical Issues');
        $headers = ['Severity', 'Type', 'URL', 'Description', 'Status', 'Fix', 'Found', 'Fixed'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $issues = SeoTechnicalIssue::where('tenant_id', $client->id)
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->get();

        if ($issues->isEmpty()) {
            $sheet->setCellValue('A2', 'No data available');
            $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
            return;
        }

        $severityColors = [
            'critical' => 'DC2626',
            'high'     => 'EA580C',
            'medium'   => 'CA8A04',
            'low'      => '16A34A',
        ];

        $row = 2;
        foreach ($issues as $issue) {
            $sheet->setCellValue("A{$row}", ucfirst($issue->severity ?? ''));
            $sheet->setCellValue("B{$row}", SpreadsheetSanitizer::cell($issue->readable_type));
            $sheet->setCellValue("C{$row}", SpreadsheetSanitizer::cell($issue->page_url ?? ''));
            $sheet->setCellValue("D{$row}", SpreadsheetSanitizer::cell($issue->description_en ?? ''));
            $sheet->setCellValue("E{$row}", ucfirst($issue->current_status ?? ''));
            $sheet->setCellValue("F{$row}", $issue->recommended_fix ?? '');
            $sheet->setCellValue("G{$row}", $issue->date_found?->format('Y-m-d') ?? '');
            $sheet->setCellValue("H{$row}", $issue->date_fixed?->format('Y-m-d') ?? '');

            $sev = strtolower($issue->severity ?? '');
            if (isset($severityColors[$sev])) {
                $sheet->getStyle("A{$row}")->getFont()->getColor()->setARGB('FF' . $severityColors[$sev]);
            }

            $row++;
        }

        $this->autoSizeColumns($sheet, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function createSheet(Spreadsheet $spreadsheet, string $title): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);

        return $sheet;
    }

    private function writeHeaderRow($sheet, int $row, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $range = "A{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
    }

    private function writeSectionLabel($sheet, int $row, string $label): int
    {
        $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($label));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE5E7EB'],
            ],
        ]);

        return $row + 1;
    }

    private function autoSizeColumns($sheet, array $columns): void
    {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
