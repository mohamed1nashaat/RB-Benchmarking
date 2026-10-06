<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportController extends Controller
{
    public function pdf(int $id)
    {
        $report = Report::findOrFail($id); // BelongsToTenant scope auto-applied

        if ($report->status !== 'ready') {
            abort(404, 'Report is not ready for export.');
        }

        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('exports.report-pdf', compact('report', 'tenant'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        $filename = str_replace([' ', '/'], '_', $report->name) . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
