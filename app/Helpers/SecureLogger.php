<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SecureLogger
{
    /**
     * Log an exception securely.
     * Only includes stack trace when APP_DEBUG is true to avoid leaking
     * internal details in production logs.
     */
    public static function exception(string $level, string $message, \Exception $e, array $context = []): void
    {
        $payload = array_merge($context, [
            'error' => $e->getMessage(),
        ]);

        if (config('app.debug')) {
            $payload['trace'] = $e->getTraceAsString();
        }

        Log::{$level}($message, $payload);
    }
}
