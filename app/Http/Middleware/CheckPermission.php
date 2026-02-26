<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions  One or more permission names (OR logic)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();

        // Super admins bypass permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Get tenant from route parameter or request
        $tenant = $request->route('tenant');

        if (!$tenant instanceof Tenant) {
            // Try to get from current_tenant in request
            $tenant = $request->get('current_tenant');
        }

        if (!$tenant) {
            // Try to get from header/query
            $tenantId = $request->header('X-Tenant-ID') ?? $request->query('tenant_id') ?? $user->default_tenant_id;
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
            }
        }

        if (!$tenant) {
            return response()->json(['message' => 'Tenant context required for permission check'], 400);
        }

        // Check if user has access to tenant
        if (!$user->hasAccessToTenant($tenant)) {
            return response()->json(['message' => 'Access denied to tenant'], 403);
        }

        // Check if user has any of the required permissions (OR logic)
        if (!empty($permissions)) {
            $hasPermission = $user->hasAnyPermissionForTenant($tenant, $permissions);

            if (!$hasPermission) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'required_permissions' => $permissions,
                ], 403);
            }
        }

        return $next($request);
    }
}
