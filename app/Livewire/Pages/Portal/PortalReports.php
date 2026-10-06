<?php

namespace App\Livewire\Pages\Portal;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PortalReports extends Component
{
    public function render()
    {
        $adAccountId = Auth::user()->client_ad_account_id;

        $reports = Report::where(fn ($q) => $q->where('status', 'ready')->orWhereNotNull('share_token'))
            ->when($adAccountId, fn ($q) => $q->where('ad_account_id', $adAccountId))
            ->latest()
            ->get();

        return view('livewire.pages.portal.portal-reports', compact('reports'))
            ->layout('layouts.portal');
    }
}
