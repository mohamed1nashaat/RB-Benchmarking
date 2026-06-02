<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Return a safe error response that doesn't leak exception details in production.
     */
    protected function errorResponse(string $publicMessage, \Throwable $e, int $status = 500, string $logContext = ''): \Illuminate\Http\JsonResponse
    {
        Log::error($logContext ?: $publicMessage, ['error' => $e->getMessage()]);

        $response = ['error' => $publicMessage];

        if (config('app.debug')) {
            $response['debug_message'] = $e->getMessage();
        }

        return response()->json($response, $status);
    }
}
