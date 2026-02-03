<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Models\ReportHistory;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ScheduledReportController extends Controller
{
    protected ReportGenerationService $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all scheduled reports
     */
    public function index(Request $request): JsonResponse
    {
        $query = ScheduledReport::with(['user', 'history' => function ($q) {
            $q->latest()->limit(5);
        }]);

        // Filters
        if ($request->has('report_type')) {
            $query->forReportType($request->report_type);
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            if ($isActive) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Get a single scheduled report
     */
    public function show(int $id): JsonResponse
    {
        $report = ScheduledReport::with(['user', 'history' => function ($q) {
            $q->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Create a new scheduled report
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|in:performance,benchmark,campaign,account,industry',
            'metrics' => 'nullable|array',
            'filters' => 'nullable|array',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_of_day' => 'nullable|date_format:H:i',
            'export_formats' => 'nullable|array',
            'export_formats.*' => 'in:pdf,excel,csv',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $report = ScheduledReport::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report created successfully',
            'data' => $report->load('user'),
        ], 201);
    }

    /**
     * Update a scheduled report
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $report = ScheduledReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'sometimes|required|in:performance,benchmark,campaign,account,industry',
            'metrics' => 'nullable|array',
            'filters' => 'nullable|array',
            'frequency' => 'sometimes|required|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_of_day' => 'nullable|date_format:H:i',
            'export_formats' => 'nullable|array',
            'export_formats.*' => 'in:pdf,excel,csv',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $report->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report updated successfully',
            'data' => $report->fresh(['user']),
        ]);
    }

    /**
     * Delete a scheduled report
     */
    public function destroy(int $id): JsonResponse
    {
        $report = ScheduledReport::findOrFail($id);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report deleted successfully',
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggle(int $id): JsonResponse
    {
        $report = ScheduledReport::findOrFail($id);
        $report->update(['is_active' => !$report->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report ' . ($report->is_active ? 'activated' : 'deactivated') . ' successfully',
            'data' => $report,
        ]);
    }

    /**
     * Generate report manually
     */
    public function generate(int $id): JsonResponse
    {
        $report = ScheduledReport::findOrFail($id);

        $result = $this->reportService->generateReport($report);

        if ($result['success']) {
            // Mark as generated
            $report->markAsGenerated();

            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Report generation failed',
            'error' => $result['error'] ?? 'Unknown error',
        ], 500);
    }

    /**
     * Get report history
     */
    public function history(int $id): JsonResponse
    {
        $report = ScheduledReport::findOrFail($id);

        $history = $report->history()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Download a generated report
     */
    public function download(int $reportId, int $historyId): JsonResponse
    {
        $history = ReportHistory::where('scheduled_report_id', $reportId)
            ->findOrFail($historyId);

        if (!$history->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $history->file_url,
                'file_name' => basename($history->file_path),
                'file_size' => $history->formatted_file_size,
            ],
        ]);
    }
}
