<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout  = 300;
    public int $tries    = 1;

    public function __construct(public Report $report) {}

    public function handle(ReportService $service): void
    {
        $service->generate($this->report);
    }

    public function failed(\Throwable $e): void
    {
        $this->report->update([
            'status'   => 'failed',
            'metadata' => ['error' => $e->getMessage(), 'failed_at' => now()->toIso8601String()],
        ]);
    }
}
