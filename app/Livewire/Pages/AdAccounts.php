<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\Industry;
use App\Models\Tenant;
use App\Services\CategoryMapper;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdAccounts extends Component
{
    use WithPagination;

    // Search & Filters
    public string $search = '';
    public string $filterPlatform = '';
    public string $filterStatus = '';
    public string $filterIndustry = '';
    public string $filterCountry = '';
    public string $filterVerification = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public string $filterClientId = '';

    // Sorting
    public string $sortField = 'account_name';
    public string $sortDirection = 'asc';

    // Pagination
    public int $perPage = 25;

    // Bulk selection
    public array $selectedAccounts = [];

    // Bulk edit modal
    public bool $showBulkModal = false;
    public string $bulkIndustry = '';
    public string $bulkCountry = '';
    public string $bulkCategory = '';
    public string $bulkStatus = '';
    public ?int $bulkClientId = null;
    public bool $bulkChangeClient = false;

    // Verification modal
    public bool $showVerificationModal = false;
    public string $verificationAction = 'approved';
    public string $verificationNotes = '';
    public array $accountsToVerify = [];

    // Quick create client modal
    public bool $showCreateClientModal = false;
    public string $newClientName = '';

    // Inline updating
    public ?int $updatingId = null;

    protected CurrencyConversionService $currencyService;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterPlatform' => ['except' => '', 'as' => 'platform'],
        'filterStatus' => ['except' => '', 'as' => 'status'],
        'filterIndustry' => ['except' => '', 'as' => 'industry'],
        'filterCountry' => ['except' => '', 'as' => 'country'],
        'filterDateFrom' => ['except' => '', 'as' => 'date_from'],
        'filterDateTo' => ['except' => '', 'as' => 'date_to'],
        'filterClientId' => ['except' => '', 'as' => 'client'],
        'sortField' => ['except' => 'account_name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 25],
    ];

    public function boot(CurrencyConversionService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterPlatform(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterIndustry(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterCountry(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterVerification(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function setDateRange($from, $to): void
    {
        $this->filterDateFrom = $from;
        $this->filterDateTo = $to;
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingFilterClientId(): void
    {
        $this->resetPage();
        $this->selectedAccounts = [];
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
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

    public function clearAllFilters(): void
    {
        $this->search = '';
        $this->filterPlatform = '';
        $this->filterStatus = '';
        $this->filterIndustry = '';
        $this->filterCountry = '';
        $this->filterVerification = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterClientId = '';
        $this->selectedAccounts = [];
        $this->resetPage();
    }

    public function toggleSelectAll(): void
    {
        $allIds = $this->getFilteredAccountsQuery()->pluck('ad_accounts.id')->toArray();
        if (count($this->selectedAccounts) === count($allIds)) {
            $this->selectedAccounts = [];
        } else {
            $this->selectedAccounts = $allIds;
        }
    }

    public function updateAccountIndustry(int $accountId, string $industry): void
    {
        $account = AdAccount::withoutGlobalScopes()->find($accountId);
        if ($account) {
            $account->update([
                'industry' => $industry ?: null,
                'category' => null,
            ]);
        }
    }

    public function updateAccountCountry(int $accountId, string $country): void
    {
        $account = AdAccount::withoutGlobalScopes()->find($accountId);
        if ($account) {
            $account->update(['country' => $country ?: null]);
        }
    }

    public function updateAccountCategory(int $accountId, string $category): void
    {
        $account = AdAccount::withoutGlobalScopes()->find($accountId);
        if ($account) {
            $account->update(['category' => $category ?: null]);
        }
    }

    public function updateAccountClient(int $accountId, string $clientId): void
    {
        $account = AdAccount::withoutGlobalScopes()->find($accountId);
        if ($account) {
            $newTenantId = $clientId ? (int) $clientId : null;
            $account->update(['tenant_id' => $newTenantId]);
            $this->dispatch('account-client-updated', accountId: $accountId, tenantId: $newTenantId);
        } else {
            \Illuminate\Support\Facades\Log::warning('updateAccountClient: account not found', ['account_id' => $accountId, 'client_id' => $clientId]);
        }
    }

    // Quick create client
    public function openCreateClientModal(): void
    {
        $this->newClientName = '';
        $this->showCreateClientModal = true;
    }

    public function closeCreateClientModal(): void
    {
        $this->showCreateClientModal = false;
        $this->newClientName = '';
    }

    public function quickCreateClient(): void
    {
        $this->validate([
            'newClientName' => 'required|string|max:255',
        ]);

        Tenant::create([
            'name' => $this->newClientName,
            'slug' => Str::slug($this->newClientName) . '-' . Str::random(6),
            'status' => 'active',
        ]);

        $this->closeCreateClientModal();
        session()->flash('message', 'Client created successfully.');
    }

    // Bulk edit
    public function openBulkModal(): void
    {
        $this->showBulkModal = true;
        $this->bulkIndustry = '';
        $this->bulkCountry = '';
        $this->bulkCategory = '';
        $this->bulkStatus = '';
        $this->bulkClientId = null;
        $this->bulkChangeClient = false;
    }

    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
        $this->selectedAccounts = [];
    }

    public function performBulkUpdate(): void
    {
        if (empty($this->selectedAccounts)) {
            return;
        }

        $updateData = [];

        if ($this->bulkIndustry !== '') {
            $updateData['industry'] = $this->bulkIndustry;
        }
        if ($this->bulkCountry !== '') {
            $updateData['country'] = $this->bulkCountry;
        }
        if ($this->bulkCategory !== '') {
            $updateData['category'] = $this->bulkCategory;
        }
        if ($this->bulkStatus !== '') {
            $updateData['status'] = $this->bulkStatus;
        }
        if ($this->bulkChangeClient) {
            $updateData['tenant_id'] = $this->bulkClientId;
        }

        if (!empty($updateData)) {
            AdAccount::whereIn('id', $this->selectedAccounts)->update($updateData);
        }

        $this->closeBulkModal();
        session()->flash('message', 'Accounts updated successfully.');
    }

    public function resetPermissionDenied(): void
    {
        if (empty($this->selectedAccounts)) {
            return;
        }

        $count = AdAccount::whereIn('id', $this->selectedAccounts)
            ->where('status', 'permission_denied')
            ->update(['status' => 'active']);

        $this->selectedAccounts = [];
        session()->flash('message', "{$count} account(s) reset to active. Next sync will retry access.");
    }

    // Verification
    public function openVerificationModal(string $action, array $accountIds): void
    {
        $this->verificationAction = $action;
        $this->accountsToVerify = $accountIds;
        $this->verificationNotes = '';
        $this->showVerificationModal = true;
    }

    public function closeVerificationModal(): void
    {
        $this->showVerificationModal = false;
        $this->accountsToVerify = [];
        $this->verificationNotes = '';
    }

    public function performVerification(): void
    {
        if (empty($this->accountsToVerify)) {
            return;
        }

        AdAccount::whereIn('id', $this->accountsToVerify)->update([
            'data_verification_status' => $this->verificationAction,
            'verification_notes' => $this->verificationNotes ?: null,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        $count = count($this->accountsToVerify);
        $this->closeVerificationModal();
        $this->selectedAccounts = [];
        session()->flash('message', "Successfully {$this->verificationAction} {$count} account(s).");
    }

    // Build the filtered query
    private function getFilteredAccountsQuery()
    {
        $query = AdAccount::query()
            ->select('ad_accounts.*')
            ->join('integrations', 'ad_accounts.integration_id', '=', 'integrations.id')
            ->with(['integration', 'tenant:id,name']);

        // Search
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('ad_accounts.account_name', 'like', "%{$search}%")
                  ->orWhere('ad_accounts.external_account_id', 'like', "%{$search}%");
            });
        }

        // Platform filter
        if ($this->filterPlatform) {
            $query->where('integrations.platform', $this->filterPlatform);
        }

        // Status filter
        if ($this->filterStatus) {
            $query->where('ad_accounts.status', $this->filterStatus);
        }

        // Industry filter
        if ($this->filterIndustry) {
            if ($this->filterIndustry === 'unset') {
                $query->whereNull('ad_accounts.industry');
            } else {
                $query->where('ad_accounts.industry', $this->filterIndustry);
            }
        }

        // Country filter
        if ($this->filterCountry) {
            if ($this->filterCountry === 'unset') {
                $query->whereNull('ad_accounts.country');
            } else {
                $query->where('ad_accounts.country', $this->filterCountry);
            }
        }

        // Verification filter
        if ($this->filterVerification) {
            if ($this->filterVerification === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('ad_accounts.data_verification_status')
                      ->orWhere('ad_accounts.data_verification_status', 'pending');
                });
            } else {
                $query->where('ad_accounts.data_verification_status', $this->filterVerification);
            }
        }

        // Client filter
        if ($this->filterClientId) {
            if ($this->filterClientId === 'unassigned') {
                $query->whereNull('ad_accounts.tenant_id');
            } else {
                $query->where('ad_accounts.tenant_id', (int) $this->filterClientId);
            }
        }

        // Sorting
        $sortColumn = match ($this->sortField) {
            'platform' => 'integrations.platform',
            'campaigns_count' => DB::raw('(SELECT COUNT(*) FROM ad_campaigns WHERE ad_campaigns.ad_account_id = ad_accounts.id)'),
            'total_spend' => DB::raw('(SELECT COALESCE(SUM(spend), 0) FROM ad_metrics WHERE ad_metrics.ad_account_id = ad_accounts.id)'),
            'total_revenue' => DB::raw('(SELECT COALESCE(SUM(revenue), 0) FROM ad_metrics WHERE ad_metrics.ad_account_id = ad_accounts.id)'),
            'total_purchases' => DB::raw('(SELECT COALESCE(SUM(purchases), 0) FROM ad_metrics WHERE ad_metrics.ad_account_id = ad_accounts.id)'),
            'total_conversions' => DB::raw('(SELECT COALESCE(SUM(conversions), 0) FROM ad_metrics WHERE ad_metrics.ad_account_id = ad_accounts.id)'),
            'roas' => DB::raw('(SELECT CASE WHEN SUM(spend) = 0 THEN 0 ELSE SUM(revenue) / SUM(spend) END FROM ad_metrics WHERE ad_metrics.ad_account_id = ad_accounts.id)'),
            default => "ad_accounts.{$this->sortField}",
        };

        $query->orderBy($sortColumn, $this->sortDirection);

        return $query;
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->search !== ''
            || $this->filterPlatform !== ''
            || $this->filterStatus !== ''
            || $this->filterIndustry !== ''
            || $this->filterCountry !== ''
            || $this->filterVerification !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== ''
            || $this->filterClientId !== '';
    }

    public function render()
    {
        $accounts = $this->getFilteredAccountsQuery()
            ->withCount('adCampaigns')
            ->paginate($this->perPage);

        // Compute per-account spend in SAR and attach extra data
        $accounts->getCollection()->transform(function ($account) {
            $metricsQuery = $account->adMetrics();

            if ($this->filterDateFrom && $this->filterDateTo) {
                $metricsQuery->whereBetween('date', [
                    $this->filterDateFrom,
                    $this->filterDateTo,
                ]);
            }

            $aggregates = (clone $metricsQuery)->selectRaw('
                COALESCE(SUM(spend), 0) as total_spend,
                COALESCE(SUM(revenue), 0) as total_revenue,
                COALESCE(SUM(purchases), 0) as total_purchases,
                COALESCE(SUM(conversions), 0) as total_conversions,
                COALESCE(SUM(atc), 0) as total_atc
            ')->first();

            $totalSpend = $aggregates->total_spend;
            $totalRevenue = $aggregates->total_revenue;

            $accountCurrency = $account->account_config['currency'] ?? $account->currency ?? 'USD';
            $account->total_spend_sar = $this->currencyService->convertToSAR((float) $totalSpend, $accountCurrency);
            $account->total_revenue_sar = $this->currencyService->convertToSAR((float) $totalRevenue, $accountCurrency);
            $account->total_purchases = (int) $aggregates->total_purchases;
            $account->total_conversions = (int) $aggregates->total_conversions;
            $account->total_atc = (int) $aggregates->total_atc;
            $account->roas = ($totalSpend > 0) ? round($totalRevenue / $totalSpend, 2) : 0;
            $account->platform_name = $account->integration->platform ?? 'unknown';
            $account->available_categories = CategoryMapper::getCategoriesForIndustry($account->industry);

            return $account;
        });

        // Compute KPI stats from ALL filtered accounts (not just current page)
        $allFilteredQuery = $this->getFilteredAccountsQuery();
        $allFilteredIds = (clone $allFilteredQuery)->pluck('ad_accounts.id')->toArray();

        $totalAccountsCount = count($allFilteredIds);
        $activeAccountsCount = AdAccount::whereIn('id', $allFilteredIds)->where('status', 'active')->count();
        $accountsWithIndustryCount = AdAccount::whereIn('id', $allFilteredIds)->whereNotNull('industry')->count();
        $totalCampaignsCount = DB::table('ad_campaigns')->whereIn('ad_account_id', $allFilteredIds)->count();

        // Total spend - sum from ad_metrics and convert
        $totalSpend = 0;
        if (!empty($allFilteredIds)) {
            $spendQuery = DB::table('ad_metrics')
                ->whereIn('ad_account_id', $allFilteredIds);

            if ($this->filterDateFrom && $this->filterDateTo) {
                $spendQuery->whereBetween('date', [
                    $this->filterDateFrom,
                    $this->filterDateTo,
                ]);
            }

            // Group by account to convert currencies
            $spendByAccount = $spendQuery
                ->select('ad_account_id', DB::raw('SUM(spend) as total_spend'))
                ->groupBy('ad_account_id')
                ->get();

            foreach ($spendByAccount as $row) {
                $acct = AdAccount::find($row->ad_account_id);
                if ($acct) {
                    $currency = $acct->account_config['currency'] ?? $acct->currency ?? 'USD';
                    $totalSpend += $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
                }
            }
        }

        // Get industries, platforms, countries for filter dropdowns
        $industries = Industry::active()->ordered()->get();
        $platforms = DB::table('integrations')
            ->select('platform')
            ->distinct()
            ->whereIn('id', AdAccount::select('integration_id'))
            ->pluck('platform')
            ->toArray();
        $countries = ['Saudi Arabia' => 'Saudi Arabia', 'United States' => 'United States', 'Egypt' => 'Egypt'];
        $clients = Tenant::orderBy('name')->get(['id', 'name']);

        $bulkAvailableCategories = $this->bulkIndustry
            ? CategoryMapper::getCategoriesForIndustry($this->bulkIndustry)
            : [];

        $isAdmin = $this->isAdmin();

        return view('livewire.pages.ad-accounts', [
            'accounts' => $accounts,
            'totalAccountsCount' => $totalAccountsCount,
            'activeAccountsCount' => $activeAccountsCount,
            'accountsWithIndustryCount' => $accountsWithIndustryCount,
            'totalCampaignsCount' => $totalCampaignsCount,
            'totalSpend' => $totalSpend,
            'industries' => $industries,
            'platforms' => $platforms,
            'countryOptions' => $countries,
            'clients' => $clients,
            'clientOptions' => $clients->pluck('name', 'id')->all(),
            'bulkAvailableCategories' => $bulkAvailableCategories,
            'isAdmin' => $isAdmin,
        ])->layout('layouts.app');
    }

    private function isAdmin(): bool
    {
        return auth()->check();
    }
}
