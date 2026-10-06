<?php

use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\WebTenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'permission' => CheckPermission::class,
            'set-locale' => SetLocale::class,
            'web-tenant' => WebTenantMiddleware::class,
            'super-admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'csp' => ContentSecurityPolicy::class,
            'request-id' => AddRequestId::class,
            'client-portal' => \App\Http\Middleware\ClientPortal::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            AddRequestId::class,
        ]);

        // Redirect unauthenticated web users to /login
        // API routes return null which lets Laravel throw AuthenticationException (renders as JSON)
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
