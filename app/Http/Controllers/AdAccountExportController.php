<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Services\CategoryMapper;
use App\Services\CurrencyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdAccountExportController extends Controller
{
    public function __construct(
        private CurrencyConversionService $currencyService,
    ) {}

    public function excel(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $accounts = $query->withCount('adCampaigns')->get();

        $yearFrom = $request->input('date_from');
        $yearTo = $request->input('date_to');

        // Compute metrics per account (same logic as the Livewire component)
        $accounts->transform(function ($account) use ($yearFrom, $yearTo) {
            $metricsQuery = $account->adMetrics();

            if ($yearFrom && $yearTo) {
                $metricsQuery->whereBetween('date', [$yearFrom, $yearTo]);
            }

            $aggregates = (clone $metricsQuery)->selectRaw('
                COALESCE(SUM(spend), 0) as total_spend,
                COALESCE(SUM(revenue), 0) as total_revenue,
                COALESCE(SUM(purchases), 0) as total_purchases,
                COALESCE(SUM(conversions), 0) as total_conversions,
                COALESCE(SUM(atc), 0) as total_atc
            ')->first();

            $totalSpend = $aggregates->total_spend;
            $totalRevenue = $aggregates->total_revenue;

            $currency = $account->account_config['currency'] ?? $account->currency ?? 'USD';
            $account->total_spend_sar = $this->currencyService->convertToSAR((float) $totalSpend, $currency);
            $account->total_revenue_sar = $this->currencyService->convertToSAR((float) $totalRevenue, $currency);
            $account->total_purchases = (int) $aggregates->total_purchases;
            $account->total_conversions = (int) $aggregates->total_conversions;
            $account->total_atc = (int) $aggregates->total_atc;
            $account->roas = ($totalSpend > 0) ? round($totalRevenue / $totalSpend, 2) : 0;
            $account->platform_name = $account->integration->platform ?? 'unknown';

            return $account;
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ad Accounts');

        // Header row
        $headers = [
            'Account Name', 'External ID', 'Client', 'Platform', 'Status',
            'Verification', 'Industry', 'Country', 'Category', 'Campaigns',
            'Total Spend (SAR)', 'Revenue (SAR)', 'Purchases', 'Conversions',
            'ROAS', 'ATC',
        ];
        $this->writeHeaderRow($sheet, 1, $headers);

        // Data rows
        $row = 2;
        foreach ($accounts as $account) {
            $sheet->setCellValue("A{$row}", SpreadsheetSanitizer::cell($account->account_name ?? ''));
            $sheet->setCellValue("B{$row}", SpreadsheetSanitizer::cell($account->external_account_id ?? ''));
            $sheet->setCellValue("C{$row}", SpreadsheetSanitizer::cell($account->tenant?->name ?? ''));
            $sheet->setCellValue("D{$row}", SpreadsheetSanitizer::cell(ucfirst($account->platform_name)));
            $sheet->setCellValue("E{$row}", SpreadsheetSanitizer::cell(ucfirst($account->status ?? '')));
            $sheet->setCellValue("F{$row}", SpreadsheetSanitizer::cell(ucfirst($account->data_verification_status ?? 'pending')));
            $sheet->setCellValue("G{$row}", SpreadsheetSanitizer::cell($account->industry ?? ''));
            $sheet->setCellValue("H{$row}", SpreadsheetSanitizer::cell($account->country ?? ''));
            $sheet->setCellValue("I{$row}", SpreadsheetSanitizer::cell($account->category ?? ''));
            $sheet->setCellValue("J{$row}", $account->ad_campaigns_count);
            $sheet->setCellValue("K{$row}", $account->total_spend_sar);
            $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue("L{$row}", $account->total_revenue_sar);
            $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue("M{$row}", $account->total_purchases);
            $sheet->setCellValue("N{$row}", $account->total_conversions);
            $sheet->setCellValue("O{$row}", $account->roas);
            $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->setCellValue("P{$row}", $account->total_atc);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Right-align numeric columns
        $lastRow = max($row - 1, 2);
        foreach (['K', 'L', 'O'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        foreach (['J', 'M', 'N', 'P'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $filename = 'Ad_Accounts_Export_' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = AdAccount::query()
            ->select('ad_accounts.*')
            ->join('integrations', 'ad_accounts.integration_id', '=', 'integrations.id')
            ->with(['integration', 'tenant:id,name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ad_accounts.account_name', 'like', "%{$search}%")
                  ->orWhere('ad_accounts.external_account_id', 'like', "%{$search}%");
            });
        }

        if ($platform = $request->input('platform')) {
            $query->where('integrations.platform', $platform);
        }

        if ($status = $request->input('status')) {
            $query->where('ad_accounts.status', $status);
        }

        if ($industry = $request->input('industry')) {
            if ($industry === 'unset') {
                $query->whereNull('ad_accounts.industry');
            } else {
                $query->where('ad_accounts.industry', $industry);
            }
        }

        if ($country = $request->input('country')) {
            if ($country === 'unset') {
                $query->whereNull('ad_accounts.country');
            } else {
                $query->where('ad_accounts.country', $country);
            }
        }

        if ($verification = $request->input('verification')) {
            if ($verification === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('ad_accounts.data_verification_status')
                      ->orWhere('ad_accounts.data_verification_status', 'pending');
                });
            } else {
                $query->where('ad_accounts.data_verification_status', $verification);
            }
        }

        if ($clientId = $request->input('client')) {
            if ($clientId === 'unassigned') {
                $query->whereNull('ad_accounts.tenant_id');
            } else {
                $query->where('ad_accounts.tenant_id', (int) $clientId);
            }
        }

        $query->orderBy('ad_accounts.account_name', 'asc');

        return $query;
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
}
