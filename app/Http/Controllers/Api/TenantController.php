<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Get list of tenants accessible to the current user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->id === 1 || $user->email === 'technical@redbananas.com';

        $query = Tenant::withCount('adAccounts');

        // Regular users only see tenants they have access to
        if (!$isSuperAdmin) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $tenants = $query->orderBy('name')->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'ad_accounts_count' => $tenant->ad_accounts_count,
                'logo_url' => $tenant->getLogoUrl(),
            ];
        });

        return response()->json([
            'data' => $tenants,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }
}
