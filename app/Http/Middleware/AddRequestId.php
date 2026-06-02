<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID', Str::uuid()->toString());

        // Make request ID available throughout the request lifecycle
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        // Return request ID in response for client-side tracing
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
