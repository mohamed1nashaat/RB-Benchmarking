<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();

        // Check if user is super admin
        $isSuperAdmin = $user->id === 1 || $user->email === 'technical@redbananas.com';

        // Set super admin flag for global scopes to use
        app()->instance('is_super_admin', $isSuperAdmin);

        // Get tenant from header or query param
        $tenantId = $request->header('X-Tenant-ID')
                   ?? $request->query('tenant_id');

        // For super admins, don't default to user's default_tenant_id
        // They should explicitly choose a tenant or see all data
        if (!$tenantId && !$isSuperAdmin) {
            // Regular users fall back to their default tenant
            $tenantId = $user->default_tenant_id;
        }

        // Super admins can bypass tenant requirements
        if (!$tenantId && !$isSuperAdmin) {
            return response()->json(['message' => 'No tenant specified'], 400);
        }

        // If super admin and no specific tenant requested, skip tenant verification
        // DO NOT set current_tenant_id binding, so global scopes show all data
        if ($isSuperAdmin && !$tenantId) {
            \Log::info('Super admin accessing without specific tenant - showing all data', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return $next($request);
        }

        // Verify user has access to this tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        // Super admins can access any tenant, regular users need permission
        if (!$isSuperAdmin && !$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied to tenant'], 403);
        }

        // Set current tenant for API requests (doesn't rely on session)
        app()->instance('current_tenant_id', $tenantId);

        // Add tenant to request for easy access
        $request->merge(['current_tenant' => $tenant]);

        return $next($request);
    }
}
