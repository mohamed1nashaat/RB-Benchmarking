<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\MediaPlan;
use App\Services\MediaPlannerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MediaPlanner extends Component
{
    public int $step = 1;

    // Step 1 — Setup
    public string $name = '';
    public string $periodStart = '';
    public string $periodEnd = '';
    public string $objective = 'leads';
    public string $industry = '';
    public float $totalBudget = 0;
    public string $currency = 'SAR';

    // Step 2 — Platforms
    public array $selectedPlatforms = ['facebook', 'google'];

    // Step 3 — Scenarios
    public ?MediaPlan $plan = null;
    public string $selectedScenario = 'recommended';
    public bool $allocating = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->plan = MediaPlan::findOrFail($id);
            $this->name             = $this->plan->name;
            $this->periodStart      = $this->plan->period_start->format('Y-m-d');
            $this->periodEnd        = $this->plan->period_end->format('Y-m-d');
            $this->objective        = $this->plan->objective;
            $this->industry         = $this->plan->industry ?? '';
            $this->totalBudget      = (float) $this->plan->total_budget;
            $this->currency         = $this->plan->currency;
            $this->selectedPlatforms = $this->plan->platforms ?? ['facebook', 'google'];
            $this->selectedScenario = $this->plan->selected_scenario ?? 'recommended';
            $this->step             = $this->plan->scenarios ? 3 : 2;
            return;
        }

        $this->periodStart = now()->startOfMonth()->format('Y-m-d');
        $this->periodEnd   = now()->endOfMonth()->addMonth()->format('Y-m-d');
        $this->name        = 'Media Plan ' . now()->format('M Y');
    }

    public function getAvailablePlatformsProperty(): array
    {
        return [
            'facebook' => ['label' => 'Meta / Facebook', 'icon' => '📘'],
            'google'   => ['label' => 'Google Ads',      'icon' => '🔍'],
            'tiktok'   => ['label' => 'TikTok',          'icon' => '🎵'],
            'snapchat' => ['label' => 'Snapchat',        'icon' => '👻'],
            'linkedin' => ['label' => 'LinkedIn',        'icon' => '💼'],
        ];
    }

    public function getIndustriesProperty(): array
    {
        return [
            'automotive'              => 'Automotive',
            'e_commerce_retail'       => 'E-Commerce & Retail',
            'education'               => 'Education',
            'fashion_luxury'          => 'Fashion & Luxury',
            'finance_insurance'       => 'Finance & Insurance',
            'fmcg_cpg'                => 'FMCG / CPG',
            'food_beverage'           => 'Food & Beverage',
            'government_public_sector'=> 'Government',
            'healthcare'              => 'Healthcare',
            'hospitality_food_services'=> 'Hospitality',
            'professional_services'   => 'Professional Services',
            'real_estate'             => 'Real Estate',
            'technology'              => 'Technology',
            'travel_destinations'     => 'Travel',
        ];
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name'         => 'required|min:3',
                'periodStart'  => 'required|date',
                'periodEnd'    => 'required|date|after:periodStart',
                'totalBudget'  => 'required|numeric|min:1000',
                'objective'    => 'required|in:awareness,traffic,leads,conversions',
                'industry'     => 'required',
            ]);
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate([
                'selectedPlatforms' => 'required|array|min:1',
            ]);
            $this->runAllocation();
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function selectScenario(string $scenario): void
    {
        $this->selectedScenario = $scenario;
        if ($this->plan) {
            $this->plan->update([
                'selected_scenario'    => $scenario,
                'platform_allocations' => $this->plan->scenarios[$scenario]['allocations'] ?? null,
            ]);
        }
    }

    public function approvePlan(): void
    {
        if (!$this->plan) return;

        $this->plan->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $this->plan->refresh();
        session()->flash('success', __('media_planner.plan_approved'));
    }

    private function runAllocation(): void
    {
        $this->allocating = true;

        $this->plan = MediaPlan::create([
            'tenant_id'    => Auth::user()->tenant_id,
            'name'         => $this->name,
            'total_budget' => $this->totalBudget,
            'currency'     => $this->currency,
            'period_start' => $this->periodStart,
            'period_end'   => $this->periodEnd,
            'objective'    => $this->objective,
            'industry'     => $this->industry,
            'platforms'    => $this->selectedPlatforms,
            'status'       => 'draft',
            'created_by'   => Auth::id(),
        ]);

        $service    = app(MediaPlannerService::class);
        $this->plan = $service->allocate($this->plan);

        $this->selectedScenario = 'recommended';
        $this->allocating       = false;
        $this->step             = 3;
    }

    public function render()
    {
        return view('livewire.pages.media-planner')->layout('layouts.app');
    }
}
