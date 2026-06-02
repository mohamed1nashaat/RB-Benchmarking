<?php

namespace App\Livewire\Pages;

use App\Jobs\RunPlatformSync;
use App\Models\Integration;
use App\Models\AdAccount;
use App\Services\SyncProgressService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Carbon\Carbon;

class Integrations extends Component
{
    private const PLATFORM_DB_TO_UI = ['google' => 'google_ads'];
    private const PLATFORM_UI_TO_DB = ['google_ads' => 'google'];

    public array $integrations = [];
    public bool $loading = true;
    public bool $refreshingAll = false;
    public bool $connectedIntegrationsCollapsed = false;
    public bool $autoRefresh = false;
    public string $syncPlatform = '';
    public bool $showAccountSyncModal = false;
    public bool $showPlatformSyncModal = false;
    public ?array $selectedAccount = null;
    public ?array $selectedIntegration = null;
    public string $syncType = 'all';
    public bool $syncing = false;

    // Sync progress tracking
    public ?int $syncIntegrationId = null;
    public ?array $syncProgress = null;
    public bool $syncPolling = false;

    // Active syncs for button indicators (platform => ['status' => '...', 'current_step' => '...'])
    public array $activeSyncs = [];

    // Google statuses
    public array $googleSheetsStatus = ['authenticated' => false, 'connecting' => false, 'testing' => false, 'error' => ''];
    public array $googleAdsStatus = ['authenticated' => false, 'connecting' => false, 'testing' => false, 'total_accounts' => 0, 'error' => ''];

    public function mount()
    {
        if ($error = request()->query('error')) {
            session()->flash('error', $error);
        }
        if ($success = request()->query('success')) {
            session()->flash('success', $success);
        }

        $this->loadIntegrations();
        $this->loadActiveSyncs();
        $this->checkGoogleSheetsStatus();
        $this->checkGoogleAdsStatus();
    }

    public function loadIntegrations()
    {
        $this->loading = true;

        try {
            $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');

            $integrations = Integration::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                })
                ->with(['adAccounts', 'user'])
                ->get();

            $this->integrations = $integrations->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'platform' => self::PLATFORM_DB_TO_UI[$integration->platform] ?? $integration->platform,
                    'status' => $integration->status,
                    'created_at' => $integration->created_at?->toISOString(),
                    'accounts_count' => $integration->adAccounts->count(),
                    'user_name' => $integration->user?->name,
                    'accounts' => $integration->adAccounts->map(function ($acc) {
                        return [
                            'id' => $acc->id,
                            'name' => $acc->account_name,
                            'currency' => $acc->currency,
                            'status' => $acc->status,
                        ];
                    })->toArray(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->integrations = [];
        } finally {
            $this->loading = false;
        }
    }

    public function loadActiveSyncs()
    {
        $progressService = app(SyncProgressService::class);
        $active = [];

        foreach ($this->integrations as $integration) {
            $progress = $progressService->getProgress($integration['id']);
            if ($progress && in_array($progress['status'], ['queued', 'running'])) {
                $active[$integration['platform']] = [
                    'status' => $progress['status'],
                    'current_step' => $progress['current_step'] ?? 'Syncing...',
                ];
            }
        }

        $this->activeSyncs = $active;
    }

    public function checkGoogleSheetsStatus()
    {
        try {
            $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');
            $integration = Integration::query()
                ->where('tenant_id', $tenantId)
                ->where('platform', 'google_sheets')
                ->first();

            if ($integration && $integration->status === 'active') {
                $this->googleSheetsStatus['authenticated'] = true;
                $this->googleSheetsStatus['auth_method'] = 'OAuth 2.0';
            }
        } catch (\Exception $e) {
            $this->googleSheetsStatus['error'] = $e->getMessage();
        }
    }

    public function checkGoogleAdsStatus()
    {
        try {
            $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');
            $integration = Integration::query()
                ->where('tenant_id', $tenantId)
                ->where('platform', 'google')
                ->first();

            if ($integration && $integration->status === 'active') {
                $this->googleAdsStatus['authenticated'] = true;
                $this->googleAdsStatus['total_accounts'] = $integration->adAccounts()->count();
            }
        } catch (\Exception $e) {
            $this->googleAdsStatus['error'] = $e->getMessage();
        }
    }

    public function getIntegrationStatus(string $platform): bool
    {
        return collect($this->integrations)->contains(function ($i) use ($platform) {
            return $i['platform'] === $platform && $i['status'] === 'active';
        });
    }

    public function needsReauth(string $platform): bool
    {
        return collect($this->integrations)->contains(function ($i) use ($platform) {
            return $i['platform'] === $platform && $i['status'] === 'needs_reauth';
        });
    }

    public function reauthorizePlatform(string $platform)
    {
        $this->connectPlatform($platform);
    }

    public function connectPlatform(string $platform)
    {
        $routes = [
            'facebook' => '/auth/facebook/redirect',
            'google_ads' => '/auth/google-ads/redirect',
            'google_sheets' => '/auth/google-sheets/redirect',
            'tiktok' => '/auth/tiktok/redirect',
            'snapchat' => '/auth/snapchat/redirect',
            'linkedin' => '/auth/linkedin/redirect',
            'twitter' => '/auth/twitter/redirect',
        ];

        $url = $routes[$platform] ?? '#';
        $this->redirect($url, navigate: false);
    }

    public function refreshAllIntegrations()
    {
        $this->refreshingAll = true;
        $this->loadIntegrations();
        $this->checkGoogleSheetsStatus();
        $this->checkGoogleAdsStatus();
        $this->refreshingAll = false;
    }

    public function openPlatformSyncModal(string $platform)
    {
        $this->syncPlatform = $platform;
        $this->syncProgress = null;
        $this->syncIntegrationId = null;
        $this->syncPolling = false;

        // Check for existing in-progress sync and resume display
        $integration = $this->findIntegrationForPlatform($platform);
        if ($integration) {
            $progressService = app(SyncProgressService::class);
            if ($progressService->isRunning($integration->id)) {
                $this->syncIntegrationId = $integration->id;
                $this->syncProgress = $progressService->getProgress($integration->id);
                $this->syncPolling = true;
            }
        }

        $this->showPlatformSyncModal = true;
    }

    public function closePlatformSyncModal()
    {
        $this->showPlatformSyncModal = false;
        $this->syncPlatform = '';
        $this->syncPolling = false;
        $this->syncProgress = null;
        $this->syncIntegrationId = null;
        $this->loadActiveSyncs();
    }

    public function startPlatformSync()
    {
        $commandMap = [
            'facebook' => 'facebook:sync-metrics',
            'google_ads' => 'google-ads:sync',
            'tiktok' => 'tiktok:sync-metrics',
            'snapchat' => 'snapchat:sync-metrics',
            'linkedin' => 'sync:linkedin-campaigns',
            'twitter' => 'sync:twitter-campaigns',
        ];

        $command = $commandMap[$this->syncPlatform] ?? null;

        if (!$command) {
            session()->flash('error', __('Unknown platform for sync.'));
            $this->closePlatformSyncModal();
            return;
        }

        $integration = $this->findIntegrationForPlatform($this->syncPlatform);

        if (!$integration) {
            session()->flash('error', __('No active integration found for this platform.'));
            $this->closePlatformSyncModal();
            return;
        }

        $progressService = app(SyncProgressService::class);

        // Prevent double-dispatch
        if ($progressService->isRunning($integration->id)) {
            $this->syncIntegrationId = $integration->id;
            $this->syncProgress = $progressService->getProgress($integration->id);
            $this->syncPolling = true;
            return;
        }

        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');

        // Write 'queued' to cache
        $progressService->initQueued($integration->id, $this->syncPlatform);

        // Build command arguments
        $commandArgs = $this->buildCommandArgs($this->syncPlatform, $integration->id, $tenantId);

        // Dispatch to queue
        RunPlatformSync::dispatch(
            $command,
            $commandArgs,
            $integration->id,
            $this->syncPlatform,
            $tenantId,
        );

        Log::info("Platform sync queued: {$command}", ['platform' => $this->syncPlatform, 'integration_id' => $integration->id, 'user_id' => auth()->id()]);

        $this->syncIntegrationId = $integration->id;
        $this->syncProgress = $progressService->getProgress($integration->id);
        $this->syncPolling = true;
        $this->loadActiveSyncs();
    }

    public function pollSyncProgress()
    {
        if (!$this->syncIntegrationId) {
            $this->syncPolling = false;
            return;
        }

        $progressService = app(SyncProgressService::class);
        $this->syncProgress = $progressService->getProgress($this->syncIntegrationId);

        if (!$this->syncProgress) {
            $this->syncPolling = false;
            return;
        }

        // Stop polling on terminal states
        if (in_array($this->syncProgress['status'], ['completed', 'failed'])) {
            $this->syncPolling = false;
            $this->loadIntegrations();
        }

        $this->loadActiveSyncs();
    }

    public function retrySyncPlatform()
    {
        if ($this->syncIntegrationId) {
            app(SyncProgressService::class)->clear($this->syncIntegrationId);
        }
        $this->syncProgress = null;
        $this->syncIntegrationId = null;
        $this->syncPolling = false;
        $this->startPlatformSync();
    }

    private function findIntegrationForPlatform(string $platform): ?Integration
    {
        $dbPlatform = self::PLATFORM_UI_TO_DB[$platform] ?? $platform;
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');

        return Integration::where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->where('platform', $dbPlatform)
            ->whereIn('status', ['active', 'needs_reauth'])
            ->first();
    }

    private function buildCommandArgs(string $platform, int $integrationId, int $tenantId): array
    {
        return match ($platform) {
            'facebook'   => ['integration_id' => $integrationId],
            'google_ads' => ['integration_id' => $integrationId],
            'tiktok'     => ['integration_id' => $integrationId],
            'snapchat'   => ['--integration-id' => $integrationId],
            'linkedin'   => ['--integration-id' => $integrationId, '--tenant-id' => $tenantId],
            default      => [],
        };
    }

    public function openAccountSyncModal(int $accountId, string $platform, string $type = 'all')
    {
        $account = AdAccount::find($accountId);
        if ($account) {
            $this->selectedAccount = [
                'id' => $account->id,
                'name' => $account->account_name,
                'currency' => $account->currency,
            ];
            $this->syncType = $type;
            $this->showAccountSyncModal = true;
        }
    }

    public function closeAccountSyncModal()
    {
        $this->showAccountSyncModal = false;
        $this->selectedAccount = null;
    }

    public function toggleConnectedIntegrations()
    {
        $this->connectedIntegrationsCollapsed = !$this->connectedIntegrationsCollapsed;
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function disconnectPlatform(string $platform)
    {
        $dbPlatform = self::PLATFORM_UI_TO_DB[$platform] ?? $platform;
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');
        Integration::query()
            ->where('tenant_id', $tenantId)
            ->where('platform', $dbPlatform)
            ->update(['status' => 'inactive']);

        $this->loadIntegrations();
    }

    public function getActiveIntegrationsCountProperty(): int
    {
        return collect($this->integrations)->where('status', 'active')->count();
    }

    public function getTotalAccountsCountProperty(): int
    {
        return collect($this->integrations)->sum('accounts_count');
    }

    public function getIssuesCountProperty(): int
    {
        return collect($this->integrations)->where('status', '!=', 'active')->count();
    }

    public function getLastSyncTime(): string
    {
        $lastSync = AdAccount::max('last_metrics_sync_at');
        return $lastSync ? Carbon::parse($lastSync)->diffForHumans() : __('Never');
    }

    public function render()
    {
        return view('livewire.pages.integrations', [
            'activeIntegrationsCount' => $this->getActiveIntegrationsCountProperty(),
            'totalAccountsCount' => $this->getTotalAccountsCountProperty(),
            'issuesCount' => $this->getIssuesCountProperty(),
        ])->layout('layouts.app');
    }
}
