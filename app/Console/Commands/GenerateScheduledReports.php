<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate {--report-id= : Generate a specific report ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scheduled reports that are due';

    protected ReportGenerationService $reportService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReportGenerationService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking for scheduled reports to generate...');

        $reportId = $this->option('report-id');

        if ($reportId) {
            return $this->generateSingleReport($reportId);
        }

        return $this->generateDueReports();
    }

    /**
     * Generate a single report
     */
    protected function generateSingleReport(int $reportId): int
    {
        try {
            $report = ScheduledReport::findOrFail($reportId);

            $this->info("Generating report: {$report->name}");

            $result = $this->reportService->generateReport($report);

            if ($result['success']) {
                $this->info("✅ Report generated successfully!");
                $report->markAsGenerated();

                if (!empty($result['files'])) {
                    $this->table(
                        ['Format', 'File Path', 'Size'],
                        collect($result['files'])->map(function ($file) {
                            return [
                                strtoupper($file['format']),
                                $file['path'],
                                $this->formatBytes($file['size']),
                            ];
                        })->toArray()
                    );
                }

                return Command::SUCCESS;
            }

            $this->error("❌ Report generation failed: " . ($result['error'] ?? 'Unknown error'));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Generate all due reports
     */
    protected function generateDueReports(): int
    {
        $reports = ScheduledReport::dueForGeneration()->get();

        if ($reports->isEmpty()) {
            $this->info('📋 No reports are due for generation.');
            return Command::SUCCESS;
        }

        $this->info("Found {$reports->count()} report(s) to generate.");
        $this->newLine();

        $generated = 0;
        $failed = 0;

        foreach ($reports as $report) {
            $this->info("Generating: {$report->name}");

            try {
                $result = $this->reportService->generateReport($report);

                if ($result['success']) {
                    $generated++;
                    $report->markAsGenerated();
                    $this->info("  ✅ Success");

                    Log::info('Scheduled report generated', [
                        'report_id' => $report->id,
                        'report_name' => $report->name,
                        'files' => $result['files'] ?? [],
                    ]);
                } else {
                    $failed++;
                    $this->error("  ❌ Failed: " . ($result['error'] ?? 'Unknown error'));

                    Log::error('Scheduled report generation failed', [
                        'report_id' => $report->id,
                        'report_name' => $report->name,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ❌ Error: {$e->getMessage()}");

                Log::error('Scheduled report generation error', [
                    'report_id' => $report->id,
                    'report_name' => $report->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Summary
        $this->info("📊 Summary:");
        $this->info("  ✅ Generated: {$generated}");
        if ($failed > 0) {
            $this->error("  ❌ Failed: {$failed}");
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}
