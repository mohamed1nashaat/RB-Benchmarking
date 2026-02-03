<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\AdCampaign;
use App\Models\ConversionTracking;

class ConversionPixelService
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Process conversion pixel event with deduplication
     */
    public function processConversion(array $conversionData): array
    {
        try {
            // Generate unique conversion ID if not provided
            if (!isset($conversionData['conversion_id'])) {
                $conversionData['conversion_id'] = $this->generateConversionId($conversionData);
            }

            // Check for duplicate conversion
            if ($this->isDuplicateConversion($conversionData)) {
                Log::info('Duplicate conversion detected, skipping', [
                    'conversion_id' => $conversionData['conversion_id']
                ]);
                return ['success' => true, 'message' => 'Duplicate conversion skipped', 'deduplicated' => true];
            }

            // Validate required fields
            $validationResult = $this->validateConversionData($conversionData);
            if (!$validationResult['valid']) {
                return ['success' => false, 'message' => $validationResult['error'], 'validation_failed' => true];
            }

            // Enrich conversion data
            $enrichedData = $this->enrichConversionData($conversionData);

            // Store in database
            $conversionRecord = $this->storeConversion($enrichedData);

            // Log to Google Sheets if configured
            $this->logToGoogleSheets($enrichedData);

            // Fire pixel events for platforms
            $this->firePixelEvents($enrichedData);

            Log::info('Conversion processed successfully', [
                'conversion_id' => $enrichedData['conversion_id'],
                'campaign_id' => $enrichedData['campaign_id'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Conversion processed successfully',
                'conversion_id' => $enrichedData['conversion_id'],
                'record_id' => $conversionRecord->id ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process conversion', [
                'error' => $e->getMessage(),
                'conversion_data' => $conversionData
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process conversion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique conversion ID based on key parameters
     */
    private function generateConversionId(array $conversionData): string
    {
        $keyParts = [
            $conversionData['user_id'] ?? 'unknown',
            $conversionData['session_id'] ?? 'unknown',
            $conversionData['campaign_id'] ?? 'unknown',
            $conversionData['conversion_type'] ?? 'purchase',
            $conversionData['timestamp'] ?? time(),
            $conversionData['page_url'] ?? 'unknown'
        ];

        return hash('sha256', implode('|', $keyParts));
    }

    /**
     * Check if conversion is duplicate using multiple deduplication strategies
     */
    private function isDuplicateConversion(array $conversionData): bool
    {
        $conversionId = $conversionData['conversion_id'];

        // Strategy 1: Check cache for recent conversions
        $cacheKey = "conversion_{$conversionId}";
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Strategy 2: Check database for exact match
        $existingConversion = DB::table('conversion_tracking')
            ->where('conversion_id', $conversionId)
            ->first();

        if ($existingConversion) {
            return true;
        }

        // Strategy 3: Check for similar conversions within time window
        $timeWindow = now()->subMinutes(5); // 5-minute deduplication window
        $similarConversions = DB::table('conversion_tracking')
            ->where('user_id', $conversionData['user_id'] ?? '')
            ->where('session_id', $conversionData['session_id'] ?? '')
            ->where('conversion_type', $conversionData['conversion_type'] ?? 'purchase')
            ->where('conversion_value', $conversionData['conversion_value'] ?? 0)
            ->where('created_at', '>=', $timeWindow)
            ->count();

        if ($similarConversions > 0) {
            Log::info('Similar conversion found within time window', [
                'conversion_id' => $conversionId,
                'similar_count' => $similarConversions
            ]);
            return true;
        }

        // Mark as processed to prevent future duplicates
        Cache::put($cacheKey, true, now()->addMinutes(30));

        return false;
    }

    /**
     * Validate conversion data
     */
    private function validateConversionData(array $conversionData): array
    {
        // Required fields
        $requiredFields = ['conversion_type'];

        foreach ($requiredFields as $field) {
            if (!isset($conversionData[$field]) || empty($conversionData[$field])) {
                return ['valid' => false, 'error' => "Missing required field: {$field}"];
            }
        }

        // Validate conversion value if provided
        if (isset($conversionData['conversion_value'])) {
            if (!is_numeric($conversionData['conversion_value']) || $conversionData['conversion_value'] < 0) {
                return ['valid' => false, 'error' => 'Conversion value must be a non-negative number'];
            }
        }

        // Validate currency if provided
        if (isset($conversionData['currency'])) {
            $validCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
            if (!in_array($conversionData['currency'], $validCurrencies)) {
                return ['valid' => false, 'error' => 'Invalid currency code'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Enrich conversion data with additional information
     */
    private function enrichConversionData(array $conversionData): array
    {
        // Add timestamp if not provided
        if (!isset($conversionData['timestamp'])) {
            $conversionData['timestamp'] = now()->toISOString();
        }

        // Add IP address and user agent from request
        if (!isset($conversionData['ip_address'])) {
            $conversionData['ip_address'] = request()->ip() ?? '';
        }

        if (!isset($conversionData['user_agent'])) {
            $conversionData['user_agent'] = request()->userAgent() ?? '';
        }

        // Parse user agent for device/browser info
        if (!isset($conversionData['device_type']) || !isset($conversionData['browser'])) {
            $deviceInfo = $this->parseUserAgent($conversionData['user_agent']);
            $conversionData['device_type'] = $conversionData['device_type'] ?? $deviceInfo['device'];
            $conversionData['browser'] = $conversionData['browser'] ?? $deviceInfo['browser'];
        }

        // Add referrer from request headers
        if (!isset($conversionData['referrer'])) {
            $conversionData['referrer'] = request()->header('Referer', '');
        }

        // Parse UTM parameters from URL
        if (isset($conversionData['page_url'])) {
            $utmParams = $this->parseUtmParameters($conversionData['page_url']);
            foreach ($utmParams as $key => $value) {
                if (!isset($conversionData[$key])) {
                    $conversionData[$key] = $value;
                }
            }
        }

        // Add campaign information if campaign_id is provided
        if (isset($conversionData['campaign_id'])) {
            $campaign = AdCampaign::find($conversionData['campaign_id']);
            if ($campaign) {
                $conversionData['campaign_name'] = $campaign->name;
                $conversionData['campaign_objective'] = $campaign->objective;
                $conversionData['platform'] = $campaign->getPlatform();
            }
        }

        return $conversionData;
    }

    /**
     * Parse user agent for device and browser information
     */
    private function parseUserAgent(string $userAgent): array
    {
        $device = 'unknown';
        $browser = 'unknown';

        // Simple device detection
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $device = 'mobile';
        } elseif (preg_match('/Tablet/', $userAgent)) {
            $device = 'tablet';
        } else {
            $device = 'desktop';
        }

        // Simple browser detection
        if (preg_match('/Chrome/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            $browser = 'Edge';
        }

        return ['device' => $device, 'browser' => $browser];
    }

    /**
     * Parse UTM parameters from URL
     */
    private function parseUtmParameters(string $url): array
    {
        $parsedUrl = parse_url($url);
        $queryParams = [];

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        return [
            'utm_source' => $queryParams['utm_source'] ?? '',
            'utm_medium' => $queryParams['utm_medium'] ?? '',
            'utm_campaign' => $queryParams['utm_campaign'] ?? '',
            'utm_term' => $queryParams['utm_term'] ?? '',
            'utm_content' => $queryParams['utm_content'] ?? ''
        ];
    }

    /**
     * Store conversion in database
     */
    private function storeConversion(array $conversionData): ?object
    {
        try {
            return DB::table('conversion_tracking')->insertGetId([
                'conversion_id' => $conversionData['conversion_id'],
                'campaign_id' => $conversionData['campaign_id'] ?? null,
                'user_id' => $conversionData['user_id'] ?? null,
                'session_id' => $conversionData['session_id'] ?? null,
                'conversion_type' => $conversionData['conversion_type'],
                'conversion_value' => $conversionData['conversion_value'] ?? 0,
                'currency' => $conversionData['currency'] ?? 'USD',
                'source' => $conversionData['source'] ?? '',
                'medium' => $conversionData['medium'] ?? '',
                'channel' => $conversionData['channel'] ?? '',
                'device_type' => $conversionData['device_type'] ?? '',
                'browser' => $conversionData['browser'] ?? '',
                'ip_address' => $conversionData['ip_address'] ?? '',
                'user_agent' => $conversionData['user_agent'] ?? '',
                'page_url' => $conversionData['page_url'] ?? '',
                'referrer' => $conversionData['referrer'] ?? '',
                'utm_source' => $conversionData['utm_source'] ?? '',
                'utm_medium' => $conversionData['utm_medium'] ?? '',
                'utm_campaign' => $conversionData['utm_campaign'] ?? '',
                'utm_term' => $conversionData['utm_term'] ?? '',
                'utm_content' => $conversionData['utm_content'] ?? '',
                'additional_data' => json_encode(array_diff_key($conversionData, array_flip([
                    'conversion_id', 'campaign_id', 'user_id', 'session_id', 'conversion_type',
                    'conversion_value', 'currency', 'source', 'medium', 'channel', 'device_type',
                    'browser', 'ip_address', 'user_agent', 'page_url', 'referrer',
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'
                ]))),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store conversion in database', [
                'error' => $e->getMessage(),
                'conversion_id' => $conversionData['conversion_id']
            ]);
            return null;
        }
    }

    /**
     * Log conversion to Google Sheets if configured
     */
    private function logToGoogleSheets(array $conversionData): void
    {
        try {
            // Check if campaign has Google Sheets integration configured
            if (isset($conversionData['campaign_id'])) {
                $campaign = AdCampaign::find($conversionData['campaign_id']);
                if ($campaign && !empty($campaign->google_sheet_id)) {
                    $this->googleSheetsService->logConversion(
                        $campaign->google_sheet_id,
                        $conversionData,
                        json_decode($campaign->sheet_mapping ?? '{}', true)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to log conversion to Google Sheets', [
                'error' => $e->getMessage(),
                'conversion_id' => $conversionData['conversion_id']
            ]);
        }
    }

    /**
     * Fire pixel events to advertising platforms
     */
    private function firePixelEvents(array $conversionData): void
    {
        try {
            // Facebook/Meta Pixel
            $this->fireFacebookPixel($conversionData);

            // Google Analytics
            $this->fireGoogleAnalytics($conversionData);

            // Other platform pixels can be added here

        } catch (\Exception $e) {
            Log::error('Failed to fire pixel events', [
                'error' => $e->getMessage(),
                'conversion_id' => $conversionData['conversion_id']
            ]);
        }
    }

    /**
     * Fire Facebook/Meta Pixel event
     */
    private function fireFacebookPixel(array $conversionData): void
    {
        // This would typically use Facebook Conversions API
        // For now, just log the event
        Log::info('Facebook Pixel event fired', [
            'conversion_id' => $conversionData['conversion_id'],
            'event_type' => $conversionData['conversion_type'],
            'value' => $conversionData['conversion_value'] ?? 0
        ]);
    }

    /**
     * Fire Google Analytics event
     */
    private function fireGoogleAnalytics(array $conversionData): void
    {
        // This would typically use Google Analytics Measurement Protocol
        // For now, just log the event
        Log::info('Google Analytics event fired', [
            'conversion_id' => $conversionData['conversion_id'],
            'event_type' => $conversionData['conversion_type'],
            'value' => $conversionData['conversion_value'] ?? 0
        ]);
    }

    /**
     * Get conversion analytics for a campaign
     */
    public function getCampaignConversions(int $campaignId, array $options = []): array
    {
        try {
            $query = DB::table('conversion_tracking')
                ->where('campaign_id', $campaignId);

            // Apply date range filter
            if (!empty($options['start_date'])) {
                $query->where('created_at', '>=', $options['start_date']);
            }

            if (!empty($options['end_date'])) {
                $query->where('created_at', '<=', $options['end_date']);
            }

            // Apply conversion type filter
            if (!empty($options['conversion_type'])) {
                $query->where('conversion_type', $options['conversion_type']);
            }

            $conversions = $query->orderBy('created_at', 'desc')->get();

            // Calculate aggregated metrics
            $metrics = [
                'total_conversions' => $conversions->count(),
                'total_value' => $conversions->sum('conversion_value'),
                'average_value' => $conversions->count() > 0 ? $conversions->avg('conversion_value') : 0,
                'conversion_types' => $conversions->groupBy('conversion_type')->map->count(),
                'device_breakdown' => $conversions->groupBy('device_type')->map->count(),
                'source_breakdown' => $conversions->groupBy('source')->map->count()
            ];

            return [
                'conversions' => $conversions->toArray(),
                'metrics' => $metrics
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign conversions', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return ['conversions' => [], 'metrics' => []];
        }
    }
}