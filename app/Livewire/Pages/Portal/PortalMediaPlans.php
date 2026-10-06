<?php

namespace App\Livewire\Pages\Portal;

use App\Models\MediaPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PortalMediaPlans extends Component
{
    public function render()
    {
        $tenantId = Auth::user()->default_tenant_id;

        $plans = MediaPlan::where('status', 'approved')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest()
            ->get();

        return view('livewire.pages.portal.portal-media-plans', compact('plans'))
            ->layout('layouts.portal');
    }
}
