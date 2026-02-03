<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Services\GoogleSheetsService;
use App\Services\ConversionPixelService;
use App\Services\SalesReadyPixelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CampaignIntegrationController extends Controller
{
    private GoogleSheetsService $googleSheetsService;
    private ConversionPixelService $conversionPixelService;
    private SalesReadyPixelService $salesReadyPixelService;

    public function __construct(
        GoogleSheetsService $googleSheetsService,
        ConversionPixelService $conversionPixelService,
        SalesReadyPixelService $salesReadyPixelService
    ) {
        $this->googleSheetsService = $googleSheetsService;
        $this->conversionPixelService = $conversionPixelService;
        $this->salesReadyPixelService = $salesReadyPixelService;
    }

    /**
     * Setup Google Sheets integration for a campaign
     */
    public function setupGoogleSheets(Request $request, AdCampaign $campaign): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mapping' => 'array',
                'enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $mapping = $request->get('mapping', []);
            $enabled = $request->get('enabled', true);

            // Create or update Google Sheet
            if (!$campaign->google_sheet_id) {
                // Load ad account information for folder organization
                $adAccount = $campaign->adAccount;

                $sheetData = $this->googleSheetsService->createCampaignSheet(
                    $campaign->id,
                    $campaign->name,
                    $mapping,
                    $adAccount ? $adAccount->account_name : null,
                    $adAccount ? $adAccount->external_account_id : null
                );

                // Check if there was a permission error
                if (isset($sheetData['error'])) {
                    return response()->json([
                        'error' => 'Google Sheets API permission error',
                        'message' => $sheetData['error'],
                        'suggestion' => 'Please ensure your Google Service Account has both Google Sheets API and Google Drive API enabled.',
                        'data' => [
                            'sheet_id' => $sheetData['sheet_id'],
                            'sheet_url' => $sheetData['sheet_url'],
                            'mapping' => $mapping,
                            'enabled' => false
                        ]
                    ], 422);
                }

                $campaign->update([
                    'google_sheet_id' => $sheetData['sheet_id'],
                    'google_sheet_url' => $sheetData['sheet_url'],
                    'sheet_mapping' => $mapping,
                    'sheets_integration_enabled' => $enabled,
                    'last_sheet_sync' => now()
                ]);
            } else {
                // Update existing sheet mapping
                $this->googleSheetsService->updateSheetMapping($campaign->google_sheet_id, $mapping);
                $campaign->update([
                    'sheet_mapping' => $mapping,
                    'sheets_integration_enabled' => $enabled
                ]);
            }

            Log::info('Google Sheets integration setup completed', [
                'campaign_id' => $campaign->id,
                'sheet_id' => $campaign->google_sheet_id
            ]);

            return response()->json([
                'message' => 'Google Sheets integration setup successfully',
                'data' => [
                    'sheet_id' => $campaign->google_sheet_id,
                    'sheet_url' => $campaign->google_sheet_url,
                    'mapping' => $campaign->sheet_mapping,
                    'enabled' => $campaign->sheets_integration_enabled
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to setup Google Sheets integration', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to setup Google Sheets integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google Sheets integration status
     */
    public function getGoogleSheetsStatus(AdCampaign $campaign): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled' => $campaign->sheets_integration_enabled,
                'sheet_id' => $campaign->google_sheet_id,
                'sheet_url' => $campaign->google_sheet_url,
                'mapping' => $campaign->sheet_mapping ?? [],
                'last_sync' => $campaign->last_sheet_sync?->toISOString(),
                'has_sheet' => !empty($campaign->google_sheet_id)
            ]
        ]);
    }

    /**
     * Update sheet mapping configuration
     */
    public function updateSheetMapping(Request $request, AdCampaign $campaign): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mapping' => 'required|array',
                'mapping.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $mapping = $request->get('mapping');

            if ($campaign->google_sheet_id) {
                $this->googleSheetsService->updateSheetMapping($campaign->google_sheet_id, $mapping);
            }

            $campaign->update(['sheet_mapping' => $mapping]);

            return response()->json([
                'message' => 'Sheet mapping updated successfully',
                'data' => ['mapping' => $mapping]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update sheet mapping', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update sheet mapping',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup conversion pixel tracking
     */
    public function setupConversionPixel(Request $request, AdCampaign $campaign): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'enabled' => 'boolean',
                'pixel_id' => 'string|nullable',
                'config' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $enabled = $request->get('enabled', true);
            $pixelId = $request->get('pixel_id', $campaign->conversion_pixel_id ?? 'pixel_' . $campaign->id);
            $config = $request->get('config', []);

            $campaign->update([
                'conversion_tracking_enabled' => $enabled,
                'conversion_pixel_id' => $pixelId,
                'pixel_config' => $config
            ]);

            return response()->json([
                'message' => 'Conversion pixel setup successfully',
                'data' => [
                    'enabled' => $enabled,
                    'pixel_id' => $pixelId,
                    'config' => $config,
                    'pixel_url' => url("/api/pixel/{$pixelId}/track")
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to setup conversion pixel', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to setup conversion pixel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversion pixel status
     */
    public function getConversionPixelStatus(AdCampaign $campaign): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled' => $campaign->conversion_tracking_enabled,
                'pixel_id' => $campaign->conversion_pixel_id,
                'config' => $campaign->pixel_config ?? [],
                'pixel_url' => $campaign->conversion_pixel_id
                    ? url("/api/pixel/{$campaign->conversion_pixel_id}/track")
                    : null,
                'javascript_snippet' => $this->generatePixelJavaScript($campaign)
            ]
        ]);
    }

    /**
     * Generate JavaScript pixel snippet
     */
    private function generatePixelJavaScript(AdCampaign $campaign): ?string
    {
        if (!$campaign->conversion_tracking_enabled || !$campaign->conversion_pixel_id) {
            return null;
        }

        $pixelUrl = url("/api/pixel/{$campaign->conversion_pixel_id}/track");

        return "
<!-- RB Benchmarks Conversion Pixel -->
<script>
(function() {
    var pixelId = '{$campaign->conversion_pixel_id}';
    var pixelUrl = '{$pixelUrl}';

    function trackConversion(data) {
        data = data || {};
        data.campaign_id = {$campaign->id};
        data.pixel_id = pixelId;
        data.page_url = window.location.href;
        data.referrer = document.referrer;
        data.user_agent = navigator.userAgent;
        data.timestamp = new Date().toISOString();

        // Generate session ID if not exists
        if (!sessionStorage.getItem('rb_session_id')) {
            sessionStorage.setItem('rb_session_id', Math.random().toString(36).substr(2, 9));
        }
        data.session_id = sessionStorage.getItem('rb_session_id');

        // Send conversion data
        fetch(pixelUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        }).catch(function(error) {
            console.warn('Conversion tracking failed:', error);
        });
    }

    // Expose global function
    window.rbTrackConversion = trackConversion;

    // Auto-track page view
    trackConversion({
        conversion_type: 'page_view',
        conversion_value: 0
    });
})();
</script>
<!-- End RB Benchmarks Conversion Pixel -->
        ";
    }

    /**
     * Track conversion pixel event
     */
    public function trackConversion(Request $request, string $pixelId): JsonResponse
    {
        try {
            // Find campaign by pixel ID
            $campaign = AdCampaign::where('conversion_pixel_id', $pixelId)
                ->where('conversion_tracking_enabled', true)
                ->with('adAccount')
                ->first();

            if (!$campaign) {
                return response()->json([
                    'error' => 'Invalid pixel ID or tracking disabled'
                ], 404);
            }

            // Add campaign context to conversion data
            $conversionData = $request->all();
            $conversionData['campaign_id'] = $campaign->id;
            $conversionData['campaign_name'] = $campaign->name;
            $conversionData['platform'] = $campaign->adAccount->integration->platform ?? 'unknown';
            $conversionData['account_id'] = $campaign->adAccount->id;
            $conversionData['account_name'] = $campaign->adAccount->account_name;

            // Process conversion with sales-ready service for enhanced tracking
            if ($campaign->sheets_integration_enabled && $campaign->google_sheet_id) {
                $result = $this->salesReadyPixelService->processConversion($conversionData, $campaign);

                // Also log to standard conversion service for backward compatibility
                $standardResult = $this->conversionPixelService->processConversion($conversionData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Conversion tracked and synced to sales sheets',
                    'data' => [
                        'conversion_id' => $result['conversion_id'],
                        'sales_ready' => true,
                        'sheet_synced' => $result['sheet_synced'] ?? false,
                        'lead_score' => $result['lead_score'] ?? null,
                        'sales_stage' => $result['sales_stage'] ?? 'new'
                    ]
                ]);
            } else {
                // Fallback to standard conversion tracking
                $result = $this->conversionPixelService->processConversion($conversionData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Conversion tracked',
                    'data' => [
                        'conversion_id' => $result['conversion_id'] ?? null,
                        'sales_ready' => false
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to track conversion', [
                'pixel_id' => $pixelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to track conversion',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversion analytics for campaign
     */
    public function getConversionAnalytics(Request $request, AdCampaign $campaign): JsonResponse
    {
        try {
            $options = [
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'conversion_type' => $request->get('conversion_type')
            ];

            $analytics = $this->conversionPixelService->getCampaignConversions($campaign->id, $options);

            return response()->json([
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get conversion analytics', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get conversion analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync conversions to Google Sheets manually
     */
    public function syncToGoogleSheets(AdCampaign $campaign): JsonResponse
    {
        try {
            if (!$campaign->sheets_integration_enabled || !$campaign->google_sheet_id) {
                return response()->json([
                    'error' => 'Google Sheets integration not enabled for this campaign'
                ], 422);
            }

            // Get recent conversions (last 24 hours)
            $conversions = $this->conversionPixelService->getCampaignConversions($campaign->id, [
                'start_date' => now()->subDay()
            ]);

            $syncCount = 0;
            foreach ($conversions['conversions'] as $conversion) {
                // Use sales-ready service for enhanced data formatting
                $enhancedConversion = $this->salesReadyPixelService->enrichConversionForSalesTeam(
                    (array) $conversion,
                    $campaign
                );

                $success = $this->googleSheetsService->logConversion(
                    $campaign->google_sheet_id,
                    $enhancedConversion,
                    $campaign->sheet_mapping ?? []
                );

                if ($success) {
                    $syncCount++;
                }
            }

            $campaign->update(['last_sheet_sync' => now()]);

            return response()->json([
                'message' => "Synced {$syncCount} conversions to Google Sheets",
                'data' => [
                    'synced_count' => $syncCount,
                    'total_conversions' => count($conversions['conversions']),
                    'last_sync' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync conversions to Google Sheets', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to sync conversions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}