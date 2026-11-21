<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReport;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledReports extends Command
{
    protected $signature = 'reports:dispatch-scheduled';
    protected $description = 'Dispatch GenerateReport jobs for scheduled AI reports that are due';

    public function handle(): int
    {
        $due = Report::query()
            ->whereIn('schedule_frequency', ['weekly', 'monthly'])
            ->where('status', '!=', 'generating')
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled AI reports due.');
            return Command::SUCCESS;
        }

        foreach ($due as $report) {
            GenerateReport::dispatch($report);
            $this->info("Dispatched: {$report->name} ({$report->schedule_frequency})");
            Log::info('Scheduled AI report dispatched', [
                'report_id'          => $report->id,
                'schedule_frequency' => $report->schedule_frequency,
                'next_run_at'        => $report->next_run_at,
            ]);
        }

        $this->info("Dispatched {$due->count()} report(s).");
        return Command::SUCCESS;
    }
}
