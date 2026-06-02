<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user) {
            $isSuperAdmin = $user->isSuperAdmin();
            app()->instance('is_super_admin', $isSuperAdmin);

            // Safety net: auto-assign tenant if session has none
            if (!session('current_tenant_id')) {
                if ($isSuperAdmin) {
                    $tenant = $user->default_tenant_id
                        ? Tenant::find($user->default_tenant_id)
                        : null;
                    $tenant = $tenant ?? Tenant::first();
                } else {
                    $tenant = $user->tenants()->where('tenants.id', $user->default_tenant_id)->first()
                        ?? $user->tenants()->first();
                }
                if ($tenant) {
                    session(['current_tenant_id' => $tenant->id]);
                }
            }

            // Bind tenant ID to app container for global scopes
            if (session('current_tenant_id')) {
                app()->instance('current_tenant_id', session('current_tenant_id'));
            }
        }
        return $next($request);
    }
}
