<?php

namespace App\Traits;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Facebook token auto-extension for sync commands.
 *
 * Facebook long-lived tokens last ~60 days and can be extended by exchanging
 * the current token for a new one (grant_type=fb_exchange_token).
 * This only works while the token is still valid — once expired, re-auth is required.
 */
trait RefreshesFacebookToken
{
    protected function ensureValidFacebookToken(Integration $integration): ?string
    {
        $config = $integration->app_config;
        $accessToken = $config['access_token'] ?? null;
        $expiresAt = $config['token_expires_at'] ?? null;

        if (!$accessToken) {
            Log::warning('Facebook: No access token found', ['integration_id' => $integration->id]);
            return null;
        }

        $needsRefresh = false;
        if ($expiresAt) {
            $expiresTimestamp = is_numeric($expiresAt) ? (int) $expiresAt : strtotime($expiresAt);
            $daysUntilExpiry = ($expiresTimestamp - time()) / 86400;

            if ($daysUntilExpiry < 7) {
                $needsRefresh = true;
                Log::info("Facebook: Token expires in " . round($daysUntilExpiry, 1) . " days, extending", [
                    'integration_id' => $integration->id,
                ]);
            }
        } else {
            $needsRefresh = true;
            Log::info('Facebook: No token_expires_at stored, attempting token extension', [
                'integration_id' => $integration->id,
            ]);
        }

        if (!$needsRefresh) {
            return $accessToken;
        }

        try {
            $response = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                $config['access_token'] = $tokenData['access_token'];
                $config['token_expires_at'] = now()->addDays(60)->toISOString();
                $integration->update(['app_config' => $config]);

                Log::info('Facebook: Token extended successfully', [
                    'integration_id' => $integration->id,
                ]);

                return $tokenData['access_token'];
            }

            Log::warning('Facebook: Token extension failed, using existing token', [
                'integration_id' => $integration->id,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Facebook: Token extension error: ' . $e->getMessage(), [
                'integration_id' => $integration->id,
            ]);
        }

        return $accessToken;
    }
}
