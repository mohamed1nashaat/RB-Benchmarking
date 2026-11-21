<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user is super admin
        if (!$user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Super admin privileges required.'], 403);
            }
            abort(403, 'Access denied. Super admin privileges required.');
        }

        return $next($request);
    }
}
