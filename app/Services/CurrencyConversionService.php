<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    private const BASE_CURRENCY = 'SAR';
    private const CACHE_TTL = 3600; // 1 hour
    private const API_URL = 'https://api.exchangerate-api.com/v4/latest/USD';

    // Fallback exchange rates (as of 2024 - should be updated)
    private const FALLBACK_RATES = [
        'USD' => 3.75,
        'EUR' => 4.10,
        'GBP' => 4.75,
        'AED' => 1.02,
        'SAR' => 1.00,
        'EGP' => 0.076,
        'JOD' => 5.29,
        'KWD' => 12.20,
        'QAR' => 1.03,
        'TRY' => 0.088, // 1 TRY = 0.088 SAR (3.75 / 42.45)
    ];

    /**
     * Convert amount from one currency to SAR
     */
    public function convertToSAR(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === self::BASE_CURRENCY) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency);
        return round($amount * $rate, 2);
    }

    /**
     * Convert amount from SAR to another currency
     */
    public function convertFromSAR(float $amount, string $toCurrency): float
    {
        if ($toCurrency === self::BASE_CURRENCY) {
            return $amount;
        }

        $rate = $this->getExchangeRate($toCurrency);
        return round($amount / $rate, 2);
    }

    /**
     * Get exchange rate for converting from currency to SAR
     */
    public function getExchangeRate(string $currency): float
    {
        if ($currency === self::BASE_CURRENCY) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$currency}_to_sar";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currency) {
            try {
                $rates = $this->fetchExchangeRates();
                return $rates[$currency] ?? $this->getFallbackRate($currency);
            } catch (\Exception $e) {
                Log::warning("Failed to fetch exchange rate for {$currency}: " . $e->getMessage());
                return $this->getFallbackRate($currency);
            }
        });
    }

    /**
     * Fetch current exchange rates from API
     */
    private function fetchExchangeRates(): array
    {
        try {
            $response = Http::timeout(10)->get(self::API_URL);

            if (!$response->successful()) {
                throw new \Exception('API request failed');
            }

            $data = $response->json();
            $usdRates = $data['rates'] ?? [];

            // Convert USD-based rates to SAR-based rates
            $sarToUsd = 1 / ($usdRates['SAR'] ?? 3.75);
            $sarRates = [];

            foreach ($usdRates as $currency => $rate) {
                if ($currency === 'SAR') {
                    $sarRates[$currency] = 1.0;
                } else {
                    // Convert: Currency -> USD -> SAR
                    $sarRates[$currency] = (1 / $rate) * (1 / $sarToUsd);
                }
            }

            return $sarRates;
        } catch (\Exception $e) {
            Log::warning('Exchange rate API failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get fallback exchange rate
     */
    private function getFallbackRate(string $currency): float
    {
        return self::FALLBACK_RATES[$currency] ?? 1.0;
    }

    /**
     * Format amount in SAR with currency symbol
     */
    public function formatSAR(float $amount): string
    {
        return 'SR ' . number_format($amount, 2);
    }

    /**
     * Format amount for frontend display (with SVG symbol indicator)
     */
    public function formatSARForFrontend(float $amount, bool $compact = false): array
    {
        return [
            'amount' => $amount,
            'currency' => 'SAR',
            'formatted' => $this->formatSAR($amount),
            'compact' => $compact,
            'use_svg_symbol' => true,
        ];
    }

    /**
     * Format amount in original currency with symbol
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'د.إ',
            'SAR' => 'SR',
            'EGP' => 'ج.م',
            'JOD' => 'د.أ',
            'KWD' => 'د.ك',
            'QAR' => 'ر.ق',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . ' ' . number_format($amount, 2);
    }

    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'AED' => 'UAE Dirham',
            'SAR' => 'Saudi Riyal',
            'EGP' => 'Egyptian Pound',
            'JOD' => 'Jordanian Dinar',
            'KWD' => 'Kuwaiti Dinar',
            'QAR' => 'Qatari Riyal',
        ];
    }

    /**
     * Convert metrics array from original currency to SAR
     */
    public function convertMetricsToSAR(array $metrics, string $originalCurrency): array
    {
        if ($originalCurrency === self::BASE_CURRENCY) {
            return $metrics;
        }

        $currencyFields = ['spend', 'revenue', 'total_spend', 'total_revenue'];

        foreach ($currencyFields as $field) {
            if (isset($metrics[$field])) {
                $metrics[$field] = $this->convertToSAR($metrics[$field], $originalCurrency);
            }
        }

        return $metrics;
    }

    /**
     * Add currency conversion info to response
     */
    public function addConversionInfo(array $data, string $originalCurrency): array
    {
        if ($originalCurrency !== self::BASE_CURRENCY) {
            $data['currency_info'] = [
                'display_currency' => self::BASE_CURRENCY,
                'original_currency' => $originalCurrency,
                'exchange_rate' => $this->getExchangeRate($originalCurrency),
                'converted_at' => now()->toISOString(),
                'note' => "Values converted from {$originalCurrency} to " . self::BASE_CURRENCY
            ];
        }

        return $data;
    }

    /**
     * Clear exchange rate cache
     */
    public function clearCache(): void
    {
        $currencies = array_keys(self::FALLBACK_RATES);
        foreach ($currencies as $currency) {
            Cache::forget("exchange_rate_{$currency}_to_sar");
        }
    }
}