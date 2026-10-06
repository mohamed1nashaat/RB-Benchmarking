<?php

namespace App\Livewire\Pages;

use App\Jobs\GenerateReport;
use App\Models\AdAccount;
use App\Models\Report;
use Livewire\Component;

class ReportBuilder extends Component
{
    public int    $step        = 1;
    public string $name        = '';
    public string $periodStart = '';
    public string $periodEnd   = '';
    public ?int   $adAccountId  = null;
    public string $platform    = '';
    public string $industry    = '';
    public string $language    = 'en';

    public ?Report $report     = null;
    public bool $generating    = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->report = Report::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
            $this->name        = $this->report->name;
            $this->periodStart = $this->report->period_start->format('Y-m-d');
            $this->periodEnd   = $this->report->period_end->format('Y-m-d');
            $this->adAccountId = $this->report->ad_account_id;
            $this->platform    = $this->report->platform ?? '';
            $this->industry    = $this->report->industry ?? '';
            $this->language    = $this->report->language ?? 'en';
            $this->step        = $this->report->isReady() ? 3 : 2;
        } else {
            $this->periodEnd   = now()->format('Y-m-d');
            $this->periodStart = now()->subDays(30)->format('Y-m-d');
            $this->name        = 'Performance Report — ' . now()->format('M Y');
        }
    }

    public function getAdAccountsProperty(): array
    {
        return AdAccount::where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'industry', 'platform'])
            ->toArray();
    }

    public function updatedAdAccountId(): void
    {
        if ($this->adAccountId) {
            $account = AdAccount::find($this->adAccountId);
            if ($account) {
                $this->industry = $account->industry ?? $this->industry;
                // platform comes from integration relationship; leave for user to select if needed
            }
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name'        => 'required|string|max:255',
                'periodStart' => 'required|date',
                'periodEnd'   => 'required|date|after_or_equal:periodStart',
                'language'    => 'required|in:en,ar',
            ]);

            $tenantId = auth()->user()->tenant_id;

            $this->report = Report::create([
                'tenant_id'    => $tenantId,
                'ad_account_id'=> $this->adAccountId,
                'name'         => $this->name,
                'status'       => 'draft',
                'period_start' => $this->periodStart,
                'period_end'   => $this->periodEnd,
                'platform'     => $this->platform ?: null,
                'industry'     => $this->industry ?: null,
                'language'     => $this->language,
                'created_by'   => auth()->id(),
            ]);
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function generate(): void
    {
        if (!$this->report || $this->report->isGenerating()) {
            return;
        }

        $this->generating = true;
        GenerateReport::dispatch($this->report);
        $this->step = 3;
    }

    public function pollStatus(): void
    {
        if ($this->report) {
            $this->report = $this->report->fresh();
            $this->generating = $this->report->isGenerating();
        }
    }

    public function render()
    {
        return view('livewire.pages.report-builder', [
            'adAccounts' => $this->adAccounts,
        ])->layout('layouts.app');
    }
}
