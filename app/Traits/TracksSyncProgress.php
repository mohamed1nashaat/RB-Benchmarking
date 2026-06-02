<?php

namespace App\Traits;

use App\Services\SyncProgressService;

trait TracksSyncProgress
{
    private ?int $trackingIntegrationId = null;
    private ?SyncProgressService $syncProgressService = null;

    /**
     * Initialize progress tracking for this integration.
     * Call once per integration at the start of sync.
     * If never called, all cache writes are no-ops (CLI backward-compat).
     */
    public function initSyncProgress(int $integrationId): void
    {
        $this->trackingIntegrationId = $integrationId;
        $this->syncProgressService = app(SyncProgressService::class);
        $this->syncProgressService->markRunning($integrationId);
    }

    /**
     * Report a step — writes to both console output and cache.
     */
    public function reportStep(string $message, string $type = 'info'): void
    {
        // Always write to console
        $this->info($message);

        // Write to cache only if tracking is active
        if ($this->trackingIntegrationId && $this->syncProgressService) {
            $this->syncProgressService->addStep($this->trackingIntegrationId, $message, $type);
        }
    }

    /**
     * Update the current step label (spinner text) + console output.
     */
    public function reportCurrentStep(string $message): void
    {
        // Always write to console
        $this->info($message);

        // Write to cache only if tracking is active
        if ($this->trackingIntegrationId && $this->syncProgressService) {
            $this->syncProgressService->setCurrentStep($this->trackingIntegrationId, $message);
        }
    }

    /**
     * Mark sync as completed with stats.
     */
    public function completeSyncProgress(array $stats): void
    {
        if ($this->trackingIntegrationId && $this->syncProgressService) {
            $this->syncProgressService->markCompleted($this->trackingIntegrationId, $stats);
        }
    }

    /**
     * Mark sync as failed.
     */
    public function failSyncProgress(string $error): void
    {
        if ($this->trackingIntegrationId && $this->syncProgressService) {
            $this->syncProgressService->markFailed($this->trackingIntegrationId, $error);
        }
    }
}
