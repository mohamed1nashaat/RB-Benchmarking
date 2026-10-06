<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SpendVerification extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $filterPlatform = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public string $filterVerification = '';
    public string $filterClientId = '';
    public string $groupBy = 'monthly'; // daily, monthly, total

    // Sorting
    public string $sortField = 'account_name';
    public string $sortDirection = 'asc';

    public int $perPage = 25;

    // Verification modal
    public bool $showVerificationModal = false;
    public string $verificationAction = 'approved';
    public string $verificationNotes = '';
    public array $accountsToVerify = [];

    protected CurrencyConversionService $currencyService;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterPlatform' => ['except' => '', 'as' => 'platform'],
        'filterDateFrom' => ['except' => '', 'as' => 'date_from'],
        'filterDateTo' => ['except' => '', 'as' => 'date_to'],
        'filterVerification' => ['except' => '', 'as' => 'verification'],
        'filterClientId' => ['except' => '', 'as' => 'client'],
        'groupBy' => ['except' => 'monthly'],
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
    }

    public function updatingFilterPlatform(): void
    {
        $this->resetPage();
    }

    public function updatingFilterVerification(): void
    {
        $this->resetPage();
    }

    public function updatingFilterClientId(): void
    {
        $this->resetPage();
    }

    public function updatingGroupBy(): void
    {
        $this->resetPage();
    }

    public function setDateRange($from, $to): void
    {
        $this->filterDateFrom = $from;
        $this->filterDateTo = $to;
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
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterVerification = '';
        $this->filterClientId = '';
        $this->groupBy = 'monthly';
        $this->resetPage();
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
        $action = $this->verificationAction;
        $this->closeVerificationModal();
        session()->flash('message', trans_choice('pages.spend_verification.verification_success', $count, ['action' => $action, 'count' => $count]));
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->search !== ''
            || $this->filterPlatform !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== ''
            || $this->filterVerification !== ''
            || $this->filterClientId !== '';
    }

    public function render()
    {
        // Build the spend verification rows
        $rows = $this->buildSpendRows();

        $platforms = DB::table('integrations')
            ->select('platform')
            ->distinct()
            ->whereIn('id', AdAccount::select('integration_id'))
            ->pluck('platform')
            ->toArray();

        $clients = \App\Models\Tenant::orderBy('name')->get(['id', 'name']);

        return view('livewire.pages.spend-verification', [
            'rows' => $rows,
            'platforms' => $platforms,
            'clients' => $clients,
        ])->layout('layouts.app');
    }

    private function buildSpendRows()
    {
        // Base query: join ad_metrics with ad_accounts and integrations
        $query = AdMetric::query()
            ->join('ad_accounts', 'ad_metrics.ad_account_id', '=', 'ad_accounts.id')
            ->join('integrations', 'ad_accounts.integration_id', '=', 'integrations.id')
            ->leftJoin('tenants', 'ad_accounts.tenant_id', '=', 'tenants.id');

        // Filters
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('ad_accounts.account_name', 'like', "%{$search}%")
                  ->orWhere('ad_accounts.external_account_id', 'like', "%{$search}%");
            });
        }

        if ($this->filterPlatform) {
            $query->where('integrations.platform', $this->filterPlatform);
        }

        if ($this->filterDateFrom && $this->filterDateTo) {
            $query->whereBetween('ad_metrics.date', [$this->filterDateFrom, $this->filterDateTo]);
        }

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

        if ($this->filterClientId) {
            if ($this->filterClientId === 'unassigned') {
                $query->whereNull('ad_accounts.tenant_id');
            } else {
                $query->where('ad_accounts.tenant_id', (int) $this->filterClientId);
            }
        }

        // Group by period (driver-aware for SQLite vs MySQL)
        $driver = DB::connection()->getDriverName();
        $periodExpr = match ($this->groupBy) {
            'daily' => "DATE(ad_metrics.date)",
            'monthly' => $driver === 'sqlite'
                ? "strftime('%Y-%m', ad_metrics.date)"
                : "DATE_FORMAT(ad_metrics.date, '%Y-%m')",
            default => "''",
        };

        $query->select([
            'ad_metrics.ad_account_id',
            'ad_accounts.account_name',
            'ad_accounts.external_account_id',
            'ad_accounts.currency',
            'ad_accounts.account_config',
            'ad_accounts.data_verification_status',
            'ad_accounts.last_metrics_sync_at',
            'integrations.platform',
            'tenants.name as client_name',
            DB::raw("{$periodExpr} as period"),
            DB::raw('SUM(ad_metrics.spend) as total_spend'),
            DB::raw('SUM(ad_metrics.revenue) as total_revenue'),
            DB::raw('SUM(ad_metrics.impressions) as total_impressions'),
            DB::raw('SUM(ad_metrics.clicks) as total_clicks'),
            DB::raw('SUM(ad_metrics.conversions) as total_conversions'),
            DB::raw('MIN(ad_metrics.date) as min_date'),
            DB::raw('MAX(ad_metrics.date) as max_date'),
            DB::raw('COUNT(DISTINCT ad_metrics.date) as days_with_data'),
        ])
        ->groupBy([
            'ad_metrics.ad_account_id',
            'ad_accounts.account_name',
            'ad_accounts.external_account_id',
            'ad_accounts.currency',
            'ad_accounts.account_config',
            'ad_accounts.data_verification_status',
            'ad_accounts.last_metrics_sync_at',
            'integrations.platform',
            'tenants.name',
            ...(($this->groupBy !== 'total') ? [DB::raw($periodExpr)] : []),
        ]);

        // Sorting
        $sortCol = match ($this->sortField) {
            'total_spend' => 'total_spend',
            'total_revenue' => 'total_revenue',
            'platform' => 'integrations.platform',
            'period' => 'period',
            'client_name' => 'tenants.name',
            default => 'ad_accounts.account_name',
        };
        $query->orderBy($sortCol, $this->sortDirection);

        if ($this->sortField !== 'account_name') {
            $query->orderBy('ad_accounts.account_name', 'asc');
        }

        $paginated = $query->paginate($this->perPage);

        // Enrich rows with SAR conversion
        $paginated->getCollection()->transform(function ($row) {
            $config = is_string($row->account_config) ? json_decode($row->account_config, true) : ($row->account_config ?? []);
            $currency = $config['currency'] ?? $row->currency ?? 'USD';
            $row->original_currency = $currency;
            $row->spend_sar = $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
            $row->revenue_sar = $this->currencyService->convertToSAR((float) $row->total_revenue, $currency);

            return $row;
        });

        return $paginated;
    }
}
