<?php

namespace App\Jobs;

use App\Services\SyncProgressService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunPlatformSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;  // 10 minutes
    public int $tries = 1;      // no auto-retry

    public function __construct(
        public string $command,
        public array $commandArgs,
        public int $integrationId,
        public string $platform,
        public int $tenantId,
    ) {}

    public function handle(): void
    {
        // Provide tenant context since queue worker has no HTTP session
        app()->instance('current_tenant_id', $this->tenantId);

        $progressService = app(SyncProgressService::class);
        $progressService->markRunning($this->integrationId);

        $exitCode = Artisan::call($this->command, $this->commandArgs);
        $output = Artisan::output();

        // Check if the command already handled progress (via TracksSyncProgress trait)
        $progress = $progressService->getProgress($this->integrationId);
        if ($progress && in_array($progress['status'], ['completed', 'failed'])) {
            return;
        }

        // Command didn't track its own progress — parse output into steps
        $lines = array_filter(array_map('trim', explode("\n", $output)));
        foreach ($lines as $line) {
            $type = 'info';
            if (str_starts_with(strtolower($line), 'error') || str_contains(strtolower($line), 'failed')) {
                $type = 'error';
            } elseif (str_contains(strtolower($line), 'successfully') || str_contains($line, '=== Summary ===')) {
                $type = 'success';
            }
            $progressService->addStep($this->integrationId, $line, $type);
        }

        if ($exitCode !== 0) {
            $lastError = collect($lines)->last(fn ($l) => str_starts_with(strtolower($l), 'error'));
            $progressService->markFailed($this->integrationId, $lastError ?: "Command exited with code {$exitCode}");
        } else {
            $stats = $this->parseStatsFromOutput($output);
            $progressService->markCompleted($this->integrationId, $stats);
        }
    }

    /**
     * Extract stats from Artisan command console output.
     */
    private function parseStatsFromOutput(string $output): array
    {
        $stats = [];
        if (preg_match('/Total campaigns synced:\s*(\d+)/i', $output, $m)) {
            $stats['campaigns_synced'] = (int) $m[1];
        }
        if (preg_match('/accounts? to sync.*?(\d+)/i', $output, $m)) {
            $stats['accounts_processed'] = (int) $m[1];
        } elseif (preg_match('/Found\s+(\d+)\s+ad accounts/i', $output, $m)) {
            $stats['accounts_processed'] = (int) $m[1];
        }
        if (preg_match('/metrics synced:\s*(\d+)/i', $output, $m)) {
            $stats['metrics_synced'] = (int) $m[1];
        }
        if (preg_match('/error/i', $output)) {
            $errorCount = preg_match_all('/^.*error.*$/im', $output);
            if ($errorCount > 0) {
                $stats['errors'] = $errorCount;
            }
        }
        return $stats;
    }

    public function failed(\Throwable $e): void
    {
        app(SyncProgressService::class)->markFailed($this->integrationId, $e->getMessage());
    }
}
