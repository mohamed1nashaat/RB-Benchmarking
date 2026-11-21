<?php

namespace App\Livewire\Pages\Portal;

use App\Models\AdMetric;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user         = Auth::user();
        $adAccountId  = $user->client_ad_account_id;
        $stats        = [];

        if ($adAccountId) {
            $rows = AdMetric::where('ad_account_id', $adAccountId)
                ->where('date', '>=', now()->subDays(30)->toDateString())
                ->selectRaw('
                    SUM(spend)       AS total_spend,
                    SUM(impressions) AS total_impressions,
                    SUM(clicks)      AS total_clicks,
                    SUM(leads)       AS total_leads
                ')
                ->first();

            $totalSpend       = (float) ($rows->total_spend ?? 0);
            $totalImpressions = (int)   ($rows->total_impressions ?? 0);
            $totalClicks      = (int)   ($rows->total_clicks ?? 0);
            $totalLeads       = (int)   ($rows->total_leads ?? 0);

            $stats = [
                'spend'       => $totalSpend,
                'impressions' => $totalImpressions,
                'clicks'      => $totalClicks,
                'leads'       => $totalLeads,
                'ctr'         => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                'cpl'         => $totalLeads > 0 ? round($totalSpend / $totalLeads, 2) : 0,
                'cpc'         => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
            ];
        }

        return view('livewire.pages.portal.dashboard', compact('stats'))
            ->layout('layouts.portal');
    }
}
