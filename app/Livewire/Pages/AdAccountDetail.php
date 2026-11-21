<?php

namespace App\Livewire\Pages;

use App\Jobs\RunPlatformSync;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Industry;
use App\Services\CurrencyConversionService;
use App\Services\SyncProgressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AdAccountDetail extends Component
{
    public int $accountId;

    // Account data
    public ?array $adAccount = null;
    public array $editableAccount = ['industry' => '', 'category' => ''];

    // Campaigns
    public array $campaigns = [];
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Campaign selection
    public array $selectedCampaigns = [];
    public bool $selectAll = false;
    public string $bulkCategory = '';

    // Syncing
    public bool $syncing = false;
    public ?int $syncIntegrationId = null;
    public ?array $syncProgress = null;
    public bool $syncPolling = false;
    public bool $showSyncModal = false;

    // Date filtering
    public string $filterStartDate = '';
    public string $filterEndDate = '';

    // Metrics sync modal
    public bool $showMetricsSyncModal = false;
    public string $metricsSyncStartDate = '';
    public string $metricsSyncEndDate = '';

    protected CurrencyConversionService $currencyService;

    public function boot(CurrencyConversionService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(int $id): void
    {
        $this->accountId = $id;
        $this->loadAccountData();

        // Auto-attach to running sync on page load
        if ($this->adAccount && $this->adAccount['integration_id']) {
            $progressService = app(SyncProgressService::class);
            $integrationId = $this->adAccount['integration_id'];
            if ($progressService->isRunning($integrationId)) {
                $this->syncIntegrationId = $integrationId;
                $this->syncProgress = $progressService->getProgress($integrationId);
                $this->syncPolling = true;
                $this->showSyncModal = true;
            }
        }
    }

    public function loadAccountData(): void
    {
        $account = AdAccount::with(['integration', 'tenant:id,name'])->find($this->accountId);

        if (!$account) {
            session()->flash('error', __('pages.ad_accounts.not_found_error'));
            return;
        }

        $accountCurrency = $account->account_config['currency'] ?? $account->currency ?? 'USD';

        // Build metrics query with optional date range
        $metricsQuery = $account->adMetrics();
        if ($this->filterStartDate && $this->filterEndDate) {
            $metricsQuery->whereBetween('date', [$this->filterStartDate, $this->filterEndDate]);
        }
        $totalSpend = $metricsQuery->sum('spend');

        $this->adAccount = [
            'id' => $account->id,
            'account_name' => $account->account_name,
            'external_account_id' => $account->external_account_id,
            'platform' => $account->integration->platform ?? 'unknown',
            'status' => $account->status,
            'industry' => $account->industry,
            'country' => $account->country,
            'category' => $account->category,
            'currency' => $accountCurrency,
            'integration_id' => $account->integration_id,
            'last_metrics_sync_at' => $account->last_metrics_sync_at?->toISOString(),
            'available_categories' => $this->getAccountCategories($account->industry),
            'campaigns_count' => $account->adCampaigns()->count(),
            'total_spend' => (float) $totalSpend,
            'total_spend_sar' => $this->currencyService->convertToSAR((float) $totalSpend, $accountCurrency),
            'account_config' => $account->account_config,
        ];

        $this->editableAccount = [
            'industry' => $account->industry ?? '',
            'category' => $account->category ?? '',
        ];

        $this->loadCampaigns();
    }

    private function loadCampaigns(): void
    {
        $query = AdCampaign::where('ad_account_id', $this->accountId);

        if ($this->filterStartDate && $this->filterEndDate) {
            // Still load all campaigns, but compute their spend for the date range
        }

        $campaignsCollection = $query->orderBy('name')->get();

        $accountCurrency = $this->adAccount['currency'] ?? 'USD';

        $this->campaigns = $campaignsCollection->map(function ($campaign) use ($accountCurrency) {
            $metricsQuery = $campaign->adMetrics();
            if ($this->filterStartDate && $this->filterEndDate) {
                $metricsQuery->whereBetween('date', [$this->filterStartDate, $this->filterEndDate]);
            }
            $totalSpend = $metricsQuery->sum('spend');

            return [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'external_campaign_id' => $campaign->external_campaign_id,
                'objective' => $campaign->objective,
                'sub_industry' => $campaign->sub_industry,
                'funnel_stage' => $campaign->funnel_stage,
                'user_journey' => $campaign->user_journey,
                'has_pixel_data' => (bool) $campaign->has_pixel_data,
                'target_segment' => $campaign->target_segment,
                'age_group' => $campaign->age_group,
                'geo_targeting' => $campaign->geo_targeting,
                'messaging_tone' => $campaign->messaging_tone,
                'status' => $campaign->status,
                'linkedin_level' => $campaign->linkedin_level,
                'google_level' => $campaign->google_level,
                'currency' => $accountCurrency,
                'total_spend' => (float) $totalSpend,
                'total_spend_sar' => $this->currencyService->convertToSAR((float) $totalSpend, $accountCurrency),
            ];
        })->toArray();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updateAccountInfo(): void
    {
        $account = AdAccount::find($this->accountId);
        if (!$account) {
            return;
        }

        $account->update([
            'industry' => $this->editableAccount['industry'] ?: null,
            'category' => $this->editableAccount['category'] ?: null,
        ]);

        $this->loadAccountData();
        session()->flash('message', __('pages.ad_accounts.account_updated'));
    }

    public function updateCampaign(int $campaignId, string $field, $value): void
    {
        $campaign = AdCampaign::find($campaignId);
        if (!$campaign) {
            return;
        }

        $allowedFields = [
            'objective', 'sub_industry', 'funnel_stage', 'user_journey',
            'has_pixel_data', 'target_segment', 'age_group', 'geo_targeting', 'messaging_tone',
        ];

        if (!in_array($field, $allowedFields)) {
            return;
        }

        if ($field === 'has_pixel_data') {
            $value = (bool) $value;
        }

        $campaign->update([$field => $value ?: null]);

        // Update local campaigns array
        foreach ($this->campaigns as &$c) {
            if ($c['id'] === $campaignId) {
                $c[$field] = $value;
                break;
            }
        }
    }

    public function toggleCampaignSelection(int $campaignId): void
    {
        if (in_array($campaignId, $this->selectedCampaigns)) {
            $this->selectedCampaigns = array_values(array_diff($this->selectedCampaigns, [$campaignId]));
        } else {
            $this->selectedCampaigns[] = $campaignId;
        }
        $this->selectAll = count($this->selectedCampaigns) === count($this->campaigns);
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedCampaigns = [];
            $this->selectAll = false;
        } else {
            $this->selectedCampaigns = array_column($this->campaigns, 'id');
            $this->selectAll = true;
        }
    }

    public function bulkUpdateCampaignCategory(): void
    {
        if (empty($this->selectedCampaigns) || !$this->bulkCategory) {
            return;
        }

        AdCampaign::whereIn('id', $this->selectedCampaigns)
            ->update(['sub_industry' => $this->bulkCategory]);

        $this->selectedCampaigns = [];
        $this->selectAll = false;
        $this->bulkCategory = '';
        $this->loadCampaigns();
        session()->flash('message', __('pages.ad_accounts.campaigns_updated'));
    }

    public function autoDetectCategories(): void
    {
        $industry = $this->adAccount['industry'] ?? null;
        if (!$industry) {
            session()->flash('error', __('pages.ad_accounts.set_industry_first'));
            return;
        }

        // Use account sub-categories as the campaign sub-category source
        $categories = $this->getCampaignSubCategories();

        if (empty($categories)) {
            session()->flash('error', __('pages.ad_accounts.no_categories_for_industry'));
            return;
        }

        $query = AdCampaign::where('ad_account_id', $this->accountId);

        // If campaigns are selected, only detect for those; otherwise detect for all
        if (!empty($this->selectedCampaigns)) {
            $query->whereIn('id', $this->selectedCampaigns);
        }

        $campaigns = $query->get();
        $updated = 0;

        foreach ($campaigns as $campaign) {
            $nameLower = strtolower($campaign->name);
            $matched = null;

            foreach ($categories as $category) {
                $keywords = explode(' ', strtolower($category));
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 3 && str_contains($nameLower, $keyword)) {
                        $matched = $category;
                        break 2;
                    }
                }
            }

            if ($matched && $matched !== $campaign->sub_industry) {
                $campaign->update(['sub_industry' => $matched]);
                $updated++;
            }
        }

        $this->selectedCampaigns = [];
        $this->selectAll = false;
        $this->loadCampaigns();
        session()->flash('message', trans_choice('pages.ad_accounts.auto_detected_categories', $updated, ['count' => $updated]));
    }

    public function syncCampaigns(): void
    {
        $commandMap = [
            'facebook'  => 'facebook:sync-campaigns',
            'google'    => 'google-ads:sync',
            'tiktok'    => 'tiktok:sync-campaigns',
            'snapchat'  => 'snapchat:sync-campaigns',
            'linkedin'  => 'sync:linkedin-campaigns',
            'twitter'   => 'sync:twitter-campaigns',
        ];

        $account = AdAccount::with('integration')->find($this->accountId);
        if (!$account || !$account->integration) {
            session()->flash('error', __('pages.ad_accounts.integration_not_found'));
            return;
        }

        $platform = $account->integration->platform;
        $command = $commandMap[$platform] ?? null;

        if (!$command) {
            session()->flash('error', __('pages.ad_accounts.unknown_platform_sync', ['platform' => $platform]));
            return;
        }

        $integrationId = $account->integration->id;
        $progressService = app(SyncProgressService::class);

        // If sync already running, attach to existing progress
        if ($progressService->isRunning($integrationId)) {
            $this->syncIntegrationId = $integrationId;
            $this->syncProgress = $progressService->getProgress($integrationId);
            $this->syncPolling = true;
            $this->showSyncModal = true;
            return;
        }

        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : session('current_tenant_id');

        // Init queued status
        $progressService->initQueued($integrationId, $platform);

        // Build command args
        $args = match ($platform) {
            'facebook'  => ['integration_id' => $integrationId],
            'google'    => ['integration_id' => $integrationId],
            'tiktok'    => ['integration_id' => $integrationId],
            'snapchat'  => ['integration_id' => $integrationId],
            'linkedin'  => ['--integration-id' => $integrationId, '--tenant-id' => $tenantId],
            'twitter'   => ['--tenant-id' => $tenantId],
            default     => [],
        };

        // Dispatch to queue
        RunPlatformSync::dispatch($command, $args, $integrationId, $platform, $tenantId);

        Log::info("Ad account campaign sync queued: {$command}", [
            'platform' => $platform,
            'integration_id' => $integrationId,
            'ad_account_id' => $this->accountId,
            'user_id' => auth()->id(),
        ]);

        $this->syncIntegrationId = $integrationId;
        $this->syncProgress = $progressService->getProgress($integrationId);
        $this->syncPolling = true;
        $this->showSyncModal = true;
    }

    public function pollSyncProgress(): void
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

        // Stop polling on terminal states and reload data
        if (in_array($this->syncProgress['status'], ['completed', 'failed'])) {
            $this->syncPolling = false;
            $this->loadAccountData();
        }
    }

    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
        if ($this->syncIntegrationId) {
            app(SyncProgressService::class)->clear($this->syncIntegrationId);
        }
        $this->syncIntegrationId = null;
        $this->syncProgress = null;
        $this->syncPolling = false;
    }

    public function retrySyncCampaigns(): void
    {
        if ($this->syncIntegrationId) {
            app(SyncProgressService::class)->clear($this->syncIntegrationId);
        }
        $this->syncIntegrationId = null;
        $this->syncProgress = null;
        $this->syncPolling = false;
        $this->showSyncModal = false;
        $this->syncCampaigns();
    }

    public function setDateRange($from, $to): void
    {
        $this->filterStartDate = $from;
        $this->filterEndDate = $to;
        $this->loadAccountData();
    }

    public function applyDateFilter(): void
    {
        $this->loadAccountData();
    }

    public function clearDateFilter(): void
    {
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->loadAccountData();
    }

    public function getSortedCampaignsProperty(): array
    {
        $campaigns = $this->campaigns;

        usort($campaigns, function ($a, $b) {
            $field = $this->sortField;
            $aVal = $a[$field] ?? '';
            $bVal = $b[$field] ?? '';

            if (in_array($field, ['total_spend', 'total_spend_sar'])) {
                $aVal = (float) $aVal;
                $bVal = (float) $bVal;
            } else {
                $aVal = strtolower((string) $aVal);
                $bVal = strtolower((string) $bVal);
            }

            $result = $aVal <=> $bVal;
            return $this->sortDirection === 'asc' ? $result : -$result;
        });

        return $campaigns;
    }

    public function getTotalSpendOriginalProperty(): float
    {
        return array_sum(array_column($this->campaigns, 'total_spend'));
    }

    public function getTotalSpendSarProperty(): float
    {
        return array_sum(array_column($this->campaigns, 'total_spend_sar'));
    }

    private function getAccountCategories(?string $industryName): array
    {
        if (!$industryName) {
            return [];
        }

        $industry = Industry::where('name', $industryName)->first();
        if (!$industry) {
            return [];
        }

        return $industry->accountCategories()
            ->where('is_active', true)
            ->ordered()
            ->pluck('display_name')
            ->toArray();
    }

    private function getCampaignSubCategories(): array
    {
        $categoryName = $this->editableAccount['category'] ?? $this->adAccount['category'] ?? null;
        if (!$categoryName) {
            return [];
        }

        $category = AccountCategory::where('display_name', $categoryName)->first();
        if (!$category) {
            return [];
        }

        return $category->subCategories()
            ->where('is_active', true)
            ->pluck('display_name')
            ->toArray();
    }

    public function render()
    {
        $industries = Industry::active()->ordered()->pluck('display_name', 'name')->toArray();
        $availableCategories = $this->getAccountCategories(
            $this->adAccount['industry'] ?? $this->editableAccount['industry'] ?? null
        );

        // Campaign sub-categories come from the account category's sub-categories
        $campaignCategories = $this->getCampaignSubCategories();

        return view('livewire.pages.ad-account-detail', [
            'industries' => $industries,
            'availableCategories' => $availableCategories,
            'campaignCategories' => $campaignCategories,
            'sortedCampaigns' => $this->sortedCampaigns,
        ])->layout('layouts.app');
    }
}
