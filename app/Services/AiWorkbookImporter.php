<?php

namespace App\Services;

use App\Models\AiVisibilityMonth;
use App\Models\AiVisibilitySnapshot;

/**
 * Saves a parsed monthly AI visibility workbook: monthly GSC/GA4 rows plus the
 * snapshot (Clarity, Semrush overview, report tables). Callers wrap it in a transaction.
 */
class AiWorkbookImporter
{
    /** @return string the report month (latest month in the workbook) */
    public function import(int $tenantId, array $parsed, string $filename): string
    {
        $months = $parsed['months'] ?? [];
        $reportMonth = collect($months)->max('month') ?? now()->format('Y-m');

        foreach ($months as $monthData) {
            $month = $monthData['month'];
            unset($monthData['month']);
            AiVisibilityMonth::updateOrCreate(
                ['tenant_id' => $tenantId, 'month' => $month],
                array_merge($monthData, ['tenant_id' => $tenantId, 'month' => $month])
            );
        }

        AiVisibilitySnapshot::updateOrCreate(
            ['tenant_id' => $tenantId, 'report_month' => $reportMonth],
            array_merge($parsed['snapshot'] ?? [], [
                'tenant_id' => $tenantId,
                'report_month' => $reportMonth,
                'snapshot_date' => now()->toDateString(),
                'filename' => $filename,
            ])
        );

        return $reportMonth;
    }
}
