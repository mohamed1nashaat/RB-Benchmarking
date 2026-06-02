<?php

namespace App\Services;

use App\Models\AdAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LiveSpendService
{
    public function fetchForAccount(AdAccount $account): ?array
    {
        $platform = $account->integration?->platform;
        $spend = match ($platform) {
            'facebook' => app(FacebookMetricsSyncService::class)->fetchAccountTotalSpend($account),
            'google' => app(GoogleAdsMetricsSyncService::class)->fetchAccountTotalSpend($account),
            'snapchat' => app(SnapchatAdsService::class)->fetchAccountTotalSpend($account),
            'tiktok' => app(TikTokAdsService::class)->fetchAccountTotalSpend($account),
            'linkedin' => app(LinkedInAdsService::class)->fetchAccountTotalSpend($account),
            'twitter' => app(TwitterAdsService::class)->fetchAccountTotalSpend($account),
            default => null,
        };

        if ($spend === null) {
            return null;
        }

        // Convert to SAR
        $currency = $account->currency ?? 'USD';
        $spendSar = app(CurrencyConversionService::class)->convertToSAR($spend, $currency);

        return [
            'spend' => $spendSar,
            'source' => 'api',
            'cached_at' => now()->toISOString(),
        ];
    }

    public function fetchBatch(array $accountIds): array
    {
        $results = [];
        $accounts = AdAccount::with('integration')->whereIn('id', $accountIds)->get();

        foreach ($accounts as $account) {
            $cacheKey = "live_spend:{$account->id}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $results[$account->id] = $cached;
                continue;
            }

            try {
                $data = $this->fetchForAccount($account);
                if ($data) {
                    Cache::put($cacheKey, $data, now()->addMinutes(30));
                    $results[$account->id] = $data;
                } else {
                    // Fall back to DB sum
                    $dbSpend = $account->adMetrics()->sum('spend');
                    $results[$account->id] = [
                        'spend' => (float) $dbSpend,
                        'source' => 'db',
                        'cached_at' => null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Live spend fetch failed', ['account_id' => $account->id, 'error' => $e->getMessage()]);
                $dbSpend = $account->adMetrics()->sum('spend');
                $results[$account->id] = [
                    'spend' => (float) $dbSpend,
                    'source' => 'db',
                    'cached_at' => null,
                ];
            }
        }

        return $results;
    }
}
