<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\Industry;
use App\Models\Tenant;
use App\Services\ClientBuilderService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Clients extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $filterStatus = '';
    public string $filterIndustry = '';
    public string $filterTier = '';
    public string $filterStartDate = '';
    public string $filterEndDate = '';

    // Sorting
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Pagination
    public int $perPage = 12;

    // Selection
    public array $selectedClients = [];

    // Create/Edit Form Modal
    public bool $showFormModal = false;
    public ?int $editingClientId = null;
    public string $formName = '';
    public string $formEmail = '';
    public string $formCountry = '';
    public string $formStatus = 'active';

    // Delete confirmation
    public bool $showDeleteDialog = false;
    public ?int $deletingClientId = null;
    public string $deletingClientName = '';
    public int $deletingClientAccountsCount = 0;

    // Client Wizard
    public bool $showWizard = false;
    public int $wizardStep = 0;
    public array $wizardSelectedAccountIds = [];
    public string $wizardAccountSearch = '';
    public array $wizardFormData = [
        'name' => '',
        'description' => '',
        'industry' => '',
        'subscription_tier' => 'basic',
        'monthly_budget' => 0,
        'contact_email' => '',
        'contact_phone' => '',
        'contact_person' => '',
        'website' => '',
        'address' => '',
        'billing_email' => '',
        'vertical' => '',
        'contract_start_date' => '',
        'contract_end_date' => '',
        'notes' => '',
    ];
    public ?array $wizardSuggestions = null;
    public bool $wizardCreating = false;

    protected CurrencyConversionService $currencyService;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '', 'as' => 'status'],
        'filterIndustry' => ['except' => '', 'as' => 'industry'],
        'filterTier' => ['except' => '', 'as' => 'tier'],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 12],
        'filterStartDate' => ['except' => '', 'as' => 'from'],
        'filterEndDate' => ['except' => '', 'as' => 'to'],
    ];

    public function boot(CurrencyConversionService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterIndustry(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTier(): void
    {
        $this->resetPage();
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
        $this->resetPage();
    }

    public function setDateRange($from, $to): void
    {
        $this->filterStartDate = $from;
        $this->filterEndDate = $to;
        $this->resetPage();
    }

    public function clearDateFilter(): void
    {
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterIndustry = '';
        $this->filterTier = '';
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->selectedClients = [];
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->search !== ''
            || $this->filterStatus !== ''
            || $this->filterIndustry !== ''
            || $this->filterTier !== ''
            || $this->filterStartDate !== ''
            || $this->filterEndDate !== '';
    }

    // Selection
    public function toggleSelectAll(): void
    {
        $allIds = $this->getClientsQuery()->pluck('id')->toArray();
        if (count($this->selectedClients) === count($allIds)) {
            $this->selectedClients = [];
        } else {
            $this->selectedClients = $allIds;
        }
    }

    public function toggleClientSelection(int $clientId): void
    {
        if (in_array($clientId, $this->selectedClients)) {
            $this->selectedClients = array_values(array_diff($this->selectedClients, [$clientId]));
        } else {
            $this->selectedClients[] = $clientId;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedClients = [];
    }

    // CRUD operations
    public function openCreateModal(): void
    {
        $this->editingClientId = null;
        $this->formName = '';
        $this->formEmail = '';
        $this->formCountry = '';
        $this->formStatus = 'active';
        $this->showFormModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Tenant::find($clientId);
        if (!$client) {
            return;
        }
        $this->editingClientId = $client->id;
        $this->formName = $client->name;
        $this->formEmail = $client->contact_email ?? '';
        $this->formCountry = $client->country ?? '';
        $this->formStatus = $client->status ?? 'active';
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->editingClientId = null;
        $this->resetValidation();
    }

    public function saveClient(): void
    {
        $rules = [
            'formName' => 'required|string|max:255',
            'formEmail' => 'nullable|email|max:255',
            'formCountry' => 'nullable|string|max:10',
            'formStatus' => 'required|in:active,inactive,suspended',
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->formName,
            'contact_email' => $this->formEmail ?: null,
            'country' => $this->formCountry ?: null,
            'status' => $this->formStatus,
        ];

        if ($this->editingClientId) {
            $client = Tenant::find($this->editingClientId);
            if ($client) {
                $client->update($data);
                session()->flash('message', __('pages.clients.updated_success'));
            }
        } else {
            $data['slug'] = Str::slug($this->formName) . '-' . Str::random(6);
            Tenant::create($data);
            session()->flash('message', __('pages.clients.created_success'));
        }

        $this->closeFormModal();
    }

    // Delete
    public function confirmDelete(int $clientId): void
    {
        $client = Tenant::withCount('adAccounts')->find($clientId);
        if (!$client) {
            return;
        }
        $this->deletingClientId = $client->id;
        $this->deletingClientName = $client->name;
        $this->deletingClientAccountsCount = $client->ad_accounts_count;
        $this->showDeleteDialog = true;
    }

    public function closeDeleteDialog(): void
    {
        $this->showDeleteDialog = false;
        $this->deletingClientId = null;
    }

    public function deleteClient(): void
    {
        if (!$this->deletingClientId) {
            return;
        }

        $client = Tenant::find($this->deletingClientId);
        if (!$client) {
            return;
        }

        $accountsCount = $client->adAccounts()->count();
        if ($accountsCount > 0) {
            session()->flash('error', trans_choice('pages.clients.delete_blocked', $accountsCount, ['count' => $accountsCount]));
            $this->closeDeleteDialog();
            return;
        }

        $client->update(['status' => 'inactive']);
        session()->flash('message', __('pages.clients.deactivated_success'));
        $this->closeDeleteDialog();
    }

    // Bulk actions
    public function bulkUpdateStatus(string $status): void
    {
        if (empty($this->selectedClients)) {
            return;
        }
        Tenant::whereIn('id', $this->selectedClients)->update(['status' => $status]);
        $this->selectedClients = [];
        session()->flash('message', __('pages.clients.bulk_updated_success'));
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedClients)) {
            return;
        }
        // Only deactivate clients without ad accounts
        $clientsWithAccounts = Tenant::whereIn('id', $this->selectedClients)
            ->whereHas('adAccounts')
            ->count();

        Tenant::whereIn('id', $this->selectedClients)
            ->whereDoesntHave('adAccounts')
            ->update(['status' => 'inactive']);

        $msg = __('pages.clients.bulk_deactivated');
        if ($clientsWithAccounts > 0) {
            $msg .= ' ' . trans_choice('pages.clients.bulk_skipped', $clientsWithAccounts, ['count' => $clientsWithAccounts]);
        }

        $this->selectedClients = [];
        session()->flash('message', $msg);
    }

    // Inline editing
    public function updateClientField(int $clientId, string $field, $value): void
    {
        $allowedFields = ['industry', 'country', 'status', 'subscription_tier'];
        if (!in_array($field, $allowedFields)) {
            return;
        }

        $client = Tenant::find($clientId);
        if ($client) {
            $client->update([$field => $value ?: null]);
        }
    }

    // Client Wizard
    public function openWizard(): void
    {
        $this->showWizard = true;
        $this->wizardStep = 0;
        $this->wizardSelectedAccountIds = [];
        $this->wizardAccountSearch = '';
        $this->wizardSuggestions = null;
        $this->wizardFormData = [
            'name' => '',
            'description' => '',
            'industry' => '',
            'subscription_tier' => 'basic',
            'monthly_budget' => 0,
            'contact_email' => '',
            'contact_phone' => '',
            'contact_person' => '',
            'website' => '',
            'address' => '',
            'billing_email' => '',
            'vertical' => '',
            'contract_start_date' => '',
            'contract_end_date' => '',
            'notes' => '',
        ];
        $this->wizardCreating = false;
    }

    public function closeWizard(): void
    {
        $this->showWizard = false;
    }

    public function wizardNextStep(): void
    {
        if ($this->wizardStep === 0 && !empty($this->wizardSelectedAccountIds)) {
            // Fetch suggestions from builder service
            try {
                $builderService = app(ClientBuilderService::class);
                $this->wizardSuggestions = $builderService->suggestClientInfo($this->wizardSelectedAccountIds);

                if ($this->wizardSuggestions && isset($this->wizardSuggestions['suggested'])) {
                    $suggested = $this->wizardSuggestions['suggested'];
                    $this->wizardFormData['name'] = $suggested['name'] ?? '';
                    $this->wizardFormData['industry'] = $suggested['industry'] ?? '';
                    $this->wizardFormData['description'] = $suggested['description'] ?? '';
                    $this->wizardFormData['subscription_tier'] = $suggested['subscription_tier'] ?? 'basic';
                    $this->wizardFormData['monthly_budget'] = $suggested['monthly_budget'] ?? 0;

                    if (!empty($suggested['contact_info']['emails_found'])) {
                        $this->wizardFormData['contact_email'] = $suggested['contact_info']['emails_found'][0];
                    }
                }
            } catch (\Exception $e) {
                // Continue without suggestions
            }
        }
        $this->wizardStep++;
    }

    public function wizardPreviousStep(): void
    {
        if ($this->wizardStep > 0) {
            $this->wizardStep--;
        }
    }

    public function createClientFromWizard(): void
    {
        if (empty($this->wizardSelectedAccountIds) || empty($this->wizardFormData['name'])) {
            return;
        }

        $this->wizardCreating = true;

        try {
            DB::beginTransaction();

            $clientData = array_filter($this->wizardFormData, fn($v) => $v !== '' && $v !== null);
            $clientData['slug'] = Str::slug($this->wizardFormData['name']) . '-' . Str::random(6);
            $clientData['status'] = 'active';

            $client = Tenant::create($clientData);

            // Assign ad accounts to client
            AdAccount::whereIn('id', $this->wizardSelectedAccountIds)
                ->update(['tenant_id' => $client->id]);

            // Inherit industry
            if ($client->industry) {
                AdAccount::whereIn('id', $this->wizardSelectedAccountIds)
                    ->whereNull('industry')
                    ->update(['industry' => $client->industry]);
            }

            DB::commit();

            session()->flash('message', trans_choice('pages.clients.wizard_created_success', count($this->wizardSelectedAccountIds), ['count' => count($this->wizardSelectedAccountIds)]));
            $this->closeWizard();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('pages.clients.create_failed') . ': ' . $e->getMessage());
        } finally {
            $this->wizardCreating = false;
        }
    }

    // Navigation
    public function viewClientDashboard(int $clientId): void
    {
        $this->redirect(route('client-dashboard', ['id' => $clientId]), navigate: true);
    }

    // Build query
    private function getClientsQuery()
    {
        $user = auth()->user();
        $canSeeAll = $this->userCanSeeAllClients();

        $query = Tenant::query()->withCount('adAccounts');

        if (!$canSeeAll) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Search
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Industry filter
        if ($this->filterIndustry) {
            $query->where('industry', $this->filterIndustry);
        }

        // Subscription tier filter
        if ($this->filterTier) {
            $query->where('subscription_tier', $this->filterTier);
        }

        // Sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    private function userCanSeeAllClients(): bool
    {
        return auth()->check();
    }

    public function render()
    {
        $clients = $this->getClientsQuery()->paginate($this->perPage);

        // Add total spend to each client (filtered by date range)
        $startDate = $this->filterStartDate;
        $endDate = $this->filterEndDate;
        $clients->getCollection()->transform(function ($client) use ($startDate, $endDate) {
            $query = DB::table('ad_metrics as m')
                ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
                ->where('a.tenant_id', $client->id);

            if ($startDate && $endDate) {
                $query->whereBetween('m.date', [$startDate, $endDate]);
            }

            $spendByAccount = $query
                ->select('m.ad_account_id', DB::raw('SUM(m.spend) as total_spend'))
                ->groupBy('m.ad_account_id')
                ->get();

            $totalSpendSAR = 0;
            foreach ($spendByAccount as $row) {
                $acct = AdAccount::find($row->ad_account_id);
                if ($acct) {
                    $currency = $acct->account_config['currency'] ?? $acct->currency ?? 'USD';
                    $totalSpendSAR += $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
                }
            }

            $client->total_spend = $totalSpendSAR;
            $client->logo_url = $client->getLogoUrl();
            $client->days_until_contract_expires = $client->daysUntilContractExpires();

            return $client;
        });

        // Overview stats
        $overview = $this->getOverviewStats();

        // Industries for filter
        $industries = Industry::active()->ordered()->get(['name', 'display_name']);

        // Unassigned accounts for wizard
        $unassignedAccounts = AdAccount::with('integration')
            ->whereNull('tenant_id')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'external_account_id', 'integration_id', 'status', 'industry', 'country'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'platform' => $account->integration->platform ?? 'unknown',
                    'status' => $account->status,
                    'industry' => $account->industry,
                ];
            })->toArray();

        return view('livewire.pages.clients', [
            'clients' => $clients,
            'overview' => $overview,
            'industries' => $industries,
            'unassignedAccounts' => $unassignedAccounts,
        ])->layout('layouts.app');
    }

    private function getOverviewStats(): array
    {
        $totalClients = Tenant::count();
        $activeClients = Tenant::where('status', 'active')->count();
        $totalAccounts = AdAccount::count();
        $connectedAccounts = AdAccount::whereNotNull('tenant_id')->count();

        // Total spend - group by account and convert currencies
        $spendQuery = DB::table('ad_metrics as m')
            ->join('ad_accounts as a', 'm.ad_account_id', '=', 'a.id')
            ->whereNotNull('a.tenant_id');

        if ($this->filterStartDate && $this->filterEndDate) {
            $spendQuery->whereBetween('m.date', [$this->filterStartDate, $this->filterEndDate]);
        }

        $spendByAccount = $spendQuery
            ->select('m.ad_account_id', DB::raw('SUM(m.spend) as total_spend'))
            ->groupBy('m.ad_account_id')
            ->get();

        $totalSpend = 0;
        foreach ($spendByAccount as $row) {
            $acct = AdAccount::find($row->ad_account_id);
            if ($acct) {
                $currency = $acct->account_config['currency'] ?? $acct->currency ?? 'USD';
                $totalSpend += $this->currencyService->convertToSAR((float) $row->total_spend, $currency);
            }
        }

        return [
            'totals' => [
                'clients' => $totalClients,
                'active_clients' => $activeClients,
                'ad_accounts' => $totalAccounts,
                'connected_accounts' => $connectedAccounts,
                'total_spend' => (float) $totalSpend,
            ],
        ];
    }
}
