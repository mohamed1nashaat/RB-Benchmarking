<?php

namespace App\Traits;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Standardized OAuth token refresh pattern.
 * Based on the proven Snapchat implementation: proactive refresh with cache locking.
 *
 * Services using this trait must implement:
 *   - getPlatformName(): string
 *   - refreshAccessToken(string $refreshToken): array  (returns ['access_token' => ..., 'refresh_token' => ..., 'expires_in' => ...])
 */
trait RefreshesOAuthToken
{
    abstract protected function getPlatformName(): string;

    abstract public function refreshAccessToken(string $refreshToken): ?array;

    /**
     * Ensure a valid access token, refreshing proactively if expiring within 10 minutes.
     */
    public function ensureValidToken(Integration $integration): ?string
    {
        $config = $integration->app_config;
        $accessToken = $config['access_token'] ?? null;
        $refreshToken = $config['refresh_token'] ?? null;
        $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;
        $platform = $this->getPlatformName();

        if (!$accessToken) {
            Log::warning("{$platform}: No access token found", ['integration_id' => $integration->id]);
            return null;
        }

        // Check if token expires within 10 minutes
        $needsRefresh = false;
        if ($expiresAt) {
            $expiresTimestamp = is_numeric($expiresAt) ? (int) $expiresAt : strtotime($expiresAt);
            $minutesUntilExpiry = ($expiresTimestamp - time()) / 60;

            if ($minutesUntilExpiry < 10) {
                $needsRefresh = true;
                Log::info("{$platform}: Token expires in {$minutesUntilExpiry} min, refreshing", [
                    'integration_id' => $integration->id,
                ]);
            }
        } elseif ($refreshToken) {
            // If no expiration is known but we have a refresh token, proactively refresh
            // to get a fresh token with a known expiration
            $needsRefresh = true;
            Log::info("{$platform}: No expires_at stored, proactively refreshing", [
                'integration_id' => $integration->id,
            ]);
        }

        if (!$needsRefresh || !$refreshToken) {
            return $accessToken;
        }

        $lockKey = "{$platform}_token_refresh_{$integration->id}";
        $lock = Cache::lock($lockKey, 30);

        if ($lock->get()) {
            try {
                // Re-read in case another process already refreshed
                $integration->refresh();
                $config = $integration->app_config;
                $accessToken = $config['access_token'] ?? $accessToken;
                $expiresAt = $config['expires_at'] ?? $config['token_expires_at'] ?? null;

                // Re-check after lock
                $stillNeedsRefresh = true;
                if ($expiresAt) {
                    $expiresTimestamp = is_numeric($expiresAt) ? (int) $expiresAt : strtotime($expiresAt);
                    $stillNeedsRefresh = (($expiresTimestamp - time()) / 60) < 10;
                }

                if (!$stillNeedsRefresh) {
                    return $accessToken;
                }

                $refreshToken = $config['refresh_token'] ?? $refreshToken;
                $tokenResponse = $this->refreshAccessToken($refreshToken);

                if ($tokenResponse && !empty($tokenResponse['access_token'])) {
                    $config['access_token'] = $tokenResponse['access_token'];

                    if (!empty($tokenResponse['refresh_token'])) {
                        $config['refresh_token'] = $tokenResponse['refresh_token'];
                    }

                    $expiresIn = $tokenResponse['expires_in'] ?? 3600;
                    $config['expires_at'] = time() + $expiresIn;
                    $config['token_expires_at'] = time() + $expiresIn;
                    $config['last_refreshed_at'] = now()->toISOString();

                    $integration->update(['app_config' => $config]);

                    Log::info("{$platform}: Token refreshed successfully", [
                        'integration_id' => $integration->id,
                        'expires_in' => $expiresIn,
                    ]);

                    return $tokenResponse['access_token'];
                }

                Log::error("{$platform}: Token refresh failed", [
                    'integration_id' => $integration->id,
                ]);

                // Refresh failed — the existing token is no longer reliable.
                // Return null so callers can mark the integration as needs_reauth.
                return null;
            } finally {
                $lock->release();
            }
        }

        // Another process is refreshing — wait and re-read
        sleep(2);
        $integration->refresh();
        return $integration->app_config['access_token'] ?? $accessToken;
    }
}
