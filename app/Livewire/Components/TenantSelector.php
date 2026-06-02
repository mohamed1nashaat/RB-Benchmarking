<?php

namespace App\Livewire\Components;

use App\Models\Tenant;
use Livewire\Component;

class TenantSelector extends Component
{
    public ?int $selectedTenantId = null;

    public function mount()
    {
        $this->selectedTenantId = session('current_tenant_id');
    }

    public function switchTenant(int $tenantId)
    {
        $user = auth()->user();

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return;
            }
            if (!$user->isSuperAdmin() && !$user->hasAccessToTenant($tenant)) {
                return;
            }
            session(['current_tenant_id' => $tenantId]);
        }

        // Full page reload to apply tenant context
        $referer = request()->header('Referer');
        $fallback = '/ad-accounts';

        // Only redirect to same-origin URLs to prevent open redirect attacks
        if ($referer && str_starts_with($referer, config('app.url'))) {
            return $this->redirect($referer, navigate: false);
        }

        return $this->redirect($fallback, navigate: false);
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        if ($isSuperAdmin) {
            $tenants = Tenant::orderBy('name')->withCount('adAccounts')->get();
        } else {
            $tenants = $user->tenants()->withCount('adAccounts')->get();
        }

        $currentTenant = $this->selectedTenantId
            ? $tenants->firstWhere('id', $this->selectedTenantId)
            : null;

        return view('livewire.components.tenant-selector', [
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'currentTenant' => $currentTenant,
        ]);
    }
}
