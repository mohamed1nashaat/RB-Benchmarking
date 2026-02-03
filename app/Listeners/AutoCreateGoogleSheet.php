<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AutoCreateGoogleSheet implements ShouldQueue
{
    use InteractsWithQueue;

    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Handle the event.
     */
    public function handle(CampaignCreated $event): void
    {
        $campaign = $event->campaign;

        Log::info('Auto-creating Google Sheet for new campaign', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name
        ]);

        try {
            // Check if Google Sheets service is available
            if (!$this->googleSheetsService->isAvailable()) {
                Log::warning('Google Sheets service not available for auto-creation', [
                    'campaign_id' => $campaign->id
                ]);
                return;
            }

            // Generate optimal mapping based on campaign characteristics
            $mapping = $this->generateMappingForCampaign($campaign);

            // Get ad account information
            $adAccount = $campaign->adAccount;

            // Create the Google Sheet
            $sheetData = $this->googleSheetsService->createCampaignSheet(
                $campaign->id,
                $campaign->name,
                $mapping,
                $adAccount ? $adAccount->account_name : null,
                $adAccount ? $adAccount->external_account_id : null
            );

            // Handle different response scenarios
            if (isset($sheetData['error'])) {
                Log::warning('Google Sheet creation returned error', [
                    'campaign_id' => $campaign->id,
                    'error' => $sheetData['error']
                ]);
                return;
            }

            if (isset($sheetData['requires_auth'])) {
                Log::info('Google Sheet creation requires auth', [
                    'campaign_id' => $campaign->id,
                    'auth_url' => $sheetData['sheet_url']
                ]);

                // Store the auth requirement for later handling
                $campaign->update([
                    'sheets_integration_enabled' => false,
                    'sheet_mapping' => $mapping,
                    'google_sheet_url' => $sheetData['sheet_url'] // Store auth URL temporarily
                ]);
                return;
            }

            // Success - update campaign with sheet information
            $campaign->update([
                'google_sheet_id' => $sheetData['sheet_id'],
                'google_sheet_url' => $sheetData['sheet_url'],
                'sheet_mapping' => $mapping,
                'sheets_integration_enabled' => true,
                'last_sheet_sync' => now()
            ]);

            Log::info('Successfully auto-created Google Sheet', [
                'campaign_id' => $campaign->id,
                'sheet_id' => $sheetData['sheet_id'],
                'sheet_url' => $sheetData['sheet_url']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto-create Google Sheet', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Optionally, you could retry this later or notify administrators
            $this->fail($e);
        }
    }

    private function generateMappingForCampaign($campaign): array
    {
        $baseMapping = [
            'Timestamp' => 'timestamp',
            'Conversion ID' => 'conversion_id',
            'Campaign ID' => 'campaign_id',
            'Campaign Name' => 'campaign_name',
            'User ID' => 'user_id',
            'Session ID' => 'session_id',
            'Conversion Type' => 'conversion_type',
            'Conversion Value' => 'conversion_value',
            'Currency' => 'currency',
            'Device Type' => 'device_type',
            'Browser' => 'browser',
            'Page URL' => 'page_url',
            'Referrer' => 'referrer',
            'IP Address' => 'ip_address',
            'User Agent' => 'user_agent',
            'UTM Source' => 'utm_source',
            'UTM Medium' => 'utm_medium',
            'UTM Campaign' => 'utm_campaign',
            'UTM Term' => 'utm_term',
            'UTM Content' => 'utm_content'
        ];

        // Add objective-specific fields
        if ($campaign->objective) {
            $objectiveFields = $this->getObjectiveSpecificFields($campaign->objective);
            $baseMapping = array_merge($baseMapping, $objectiveFields);
        }

        // Add funnel stage fields
        if ($campaign->funnel_stage) {
            $funnelFields = $this->getFunnelStageFields($campaign->funnel_stage);
            $baseMapping = array_merge($baseMapping, $funnelFields);
        }

        // Add platform-specific fields
        $platform = $campaign->adAccount?->integration?->platform;
        if ($platform) {
            $platformFields = $this->getPlatformSpecificFields($platform);
            $baseMapping = array_merge($baseMapping, $platformFields);
        }

        // Always add custom fields for flexibility
        $baseMapping = array_merge($baseMapping, [
            'Custom Field 1' => 'custom_field_1',
            'Custom Field 2' => 'custom_field_2',
            'Custom Field 3' => 'custom_field_3'
        ]);

        return $baseMapping;
    }

    private function getObjectiveSpecificFields(string $objective): array
    {
        return match ($objective) {
            'leads' => [
                'Lead Type' => 'lead_type',
                'Lead Quality Score' => 'lead_quality_score',
                'Form Fields Completed' => 'form_completion_count',
                'Lead Source Page' => 'lead_source_page'
            ],
            'sales' => [
                'Product ID' => 'product_id',
                'Product Name' => 'product_name',
                'Product Category' => 'product_category',
                'Quantity' => 'quantity',
                'Order ID' => 'order_id',
                'Discount Amount' => 'discount_amount',
                'Shipping Cost' => 'shipping_cost'
            ],
            'calls' => [
                'Call Duration (seconds)' => 'call_duration_seconds',
                'Call Quality Score' => 'call_quality_score',
                'Phone Number' => 'phone_number',
                'Call Outcome' => 'call_outcome',
                'Agent ID' => 'agent_id'
            ],
            default => []
        };
    }

    private function getFunnelStageFields(string $funnelStage): array
    {
        return match ($funnelStage) {
            'TOF' => [
                'Video View Duration' => 'video_view_duration',
                'Content Engagement Score' => 'content_engagement_score',
                'Brand Awareness Lift' => 'brand_awareness_lift'
            ],
            'MOF' => [
                'Email Signup' => 'email_signup',
                'Content Download' => 'content_download',
                'Webinar Registration' => 'webinar_registration',
                'Demo Request' => 'demo_request'
            ],
            'BOF' => [
                'Purchase Intent Score' => 'purchase_intent_score',
                'Cart Abandonment Value' => 'cart_abandonment_value',
                'Checkout Step' => 'checkout_step_reached',
                'Payment Method' => 'payment_method_selected'
            ],
            default => []
        };
    }

    private function getPlatformSpecificFields(string $platform): array
    {
        return match ($platform) {
            'facebook' => [
                'Facebook User ID' => 'facebook_user_id',
                'Ad Set ID' => 'ad_set_id',
                'Ad Creative ID' => 'ad_creative_id',
                'Placement' => 'placement',
                'Age Range' => 'age_range',
                'Gender' => 'gender'
            ],
            'google' => [
                'Google Click ID' => 'gclid',
                'Ad Group ID' => 'ad_group_id',
                'Keyword' => 'keyword',
                'Match Type' => 'match_type',
                'Quality Score' => 'quality_score',
                'Search Query' => 'search_query'
            ],
            'tiktok' => [
                'TikTok User ID' => 'tiktok_user_id',
                'Video ID' => 'video_id',
                'Video Duration Viewed' => 'video_duration_viewed',
                'Engagement Type' => 'engagement_type'
            ],
            default => []
        };
    }
}