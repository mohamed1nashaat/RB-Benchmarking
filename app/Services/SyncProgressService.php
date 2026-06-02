<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SyncProgressService
{
    private const CACHE_PREFIX = 'sync_progress:';
    private const TTL_MINUTES = 30;
    private const MAX_STEPS = 50;

    public function initQueued(int $integrationId, string $platform): void
    {
        Cache::put($this->cacheKey($integrationId), [
            'status' => 'queued',
            'platform' => $platform,
            'current_step' => 'Queued, starting shortly...',
            'steps' => [],
            'stats' => [],
            'error' => null,
            'started_at' => now()->toISOString(),
            'completed_at' => null,
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    public function markRunning(int $integrationId): void
    {
        $progress = $this->getProgress($integrationId);
        if (!$progress) {
            return;
        }

        $progress['status'] = 'running';
        $progress['current_step'] = 'Starting sync...';
        $this->save($integrationId, $progress);
    }

    public function addStep(int $integrationId, string $message, string $type = 'info'): void
    {
        $progress = $this->getProgress($integrationId);
        if (!$progress) {
            return;
        }

        $progress['steps'][] = [
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'type' => $type,
        ];

        // Cap steps at MAX_STEPS, trim oldest
        if (count($progress['steps']) > self::MAX_STEPS) {
            $progress['steps'] = array_slice($progress['steps'], -self::MAX_STEPS);
        }

        $this->save($integrationId, $progress);
    }

    public function setCurrentStep(int $integrationId, string $message): void
    {
        $progress = $this->getProgress($integrationId);
        if (!$progress) {
            return;
        }

        $progress['current_step'] = $message;
        $this->save($integrationId, $progress);
    }

    public function markCompleted(int $integrationId, array $stats = []): void
    {
        $progress = $this->getProgress($integrationId);
        if (!$progress) {
            return;
        }

        $progress['status'] = 'completed';
        $progress['current_step'] = 'Sync completed';
        $progress['stats'] = $stats;
        $progress['completed_at'] = now()->toISOString();
        $this->save($integrationId, $progress);
    }

    public function markFailed(int $integrationId, string $error): void
    {
        $progress = $this->getProgress($integrationId);
        if (!$progress) {
            // Create minimal progress entry if none exists (e.g. job crash before init)
            $progress = [
                'status' => 'failed',
                'platform' => 'unknown',
                'current_step' => 'Sync failed',
                'steps' => [],
                'stats' => [],
                'error' => $error,
                'started_at' => now()->toISOString(),
                'completed_at' => now()->toISOString(),
            ];
        } else {
            $progress['status'] = 'failed';
            $progress['current_step'] = 'Sync failed';
            $progress['error'] = $error;
            $progress['completed_at'] = now()->toISOString();
        }

        $this->save($integrationId, $progress);
    }

    public function getProgress(int $integrationId): ?array
    {
        return Cache::get($this->cacheKey($integrationId));
    }

    public function clear(int $integrationId): void
    {
        Cache::forget($this->cacheKey($integrationId));
    }

    public function isRunning(int $integrationId): bool
    {
        $progress = $this->getProgress($integrationId);

        return $progress && in_array($progress['status'], ['queued', 'running']);
    }

    private function cacheKey(int $integrationId): string
    {
        return self::CACHE_PREFIX . $integrationId;
    }

    private function save(int $integrationId, array $progress): void
    {
        Cache::put($this->cacheKey($integrationId), $progress, now()->addMinutes(self::TTL_MINUTES));
    }
}
