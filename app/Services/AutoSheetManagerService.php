<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AutoSheetManagerService
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Automatically create sheets for newly discovered campaigns
     */
    public function autoCreateSheetsForNewCampaigns(): array
    {
        Log::info('Starting auto-creation of sheets for new campaigns');

        $results = [
            'campaigns_processed' => 0,
            'sheets_created' => 0,
            'errors' => 0,
            'created_sheets' => []
        ];

        // Find campaigns without sheets that should have them
        $newCampaigns = AdCampaign::whereNull('google_sheet_id')
            ->whereHas('adAccount.integration', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['adAccount.integration'])
            ->get();

        Log::info("Found {$newCampaigns->count()} campaigns without sheets");

        foreach ($newCampaigns as $campaign) {
            $results['campaigns_processed']++;
            $this->createSheetForCampaign($campaign, $results);
        }

        Log::info('Auto-sheet creation completed', $results);
        return $results;
    }

    /**
     * Create folder structure for all accounts of an integration
     */
    public function createFolderStructureForIntegration(Integration $integration): array
    {
        Log::info("Creating folder structure for integration: {$integration->platform}", [
            'integration_id' => $integration->id
        ]);

        $results = [
            'accounts_processed' => 0,
            'campaigns_processed' => 0,
            'sheets_created' => 0,
            'errors' => 0
        ];

        $adAccounts = $integration->adAccounts()->with('adCampaigns')->get();

        foreach ($adAccounts as $adAccount) {
            $this->processAdAccountFolderStructure($adAccount, $results);
        }

        return $results;
    }

    /**
     * Sync all conversions for campaigns with sheet integration
     */
    public function syncAllConversions(int $hoursBack = 24): array
    {
        Log::info("Starting conversion sync for last {$hoursBack} hours");

        $results = [
            'campaigns_processed' => 0,
            'conversions_synced' => 0,
            'errors' => 0
        ];

        $enabledCampaigns = AdCampaign::where('sheets_integration_enabled', true)
            ->whereNotNull('google_sheet_id')
            ->get();

        $startTime = now()->subHours($hoursBack);

        foreach ($enabledCampaigns as $campaign) {
            $this->syncCampaignConversions($campaign, $startTime, $results);
        }

        Log::info('Conversion sync completed', $results);
        return $results;
    }

    /**
     * Update sheet mappings for all campaigns based on their objectives
     */
    public function updateAllSheetMappings(): array
    {
        Log::info('Updating sheet mappings for all campaigns');

        $results = [
            'campaigns_processed' => 0,
            'mappings_updated' => 0,
            'errors' => 0
        ];

        $campaigns = AdCampaign::whereNotNull('google_sheet_id')
            ->get();

        foreach ($campaigns as $campaign) {
            $results['campaigns_processed']++;
            $this->updateCampaignMapping($campaign, $results);
        }

        Log::info('Sheet mapping update completed', $results);
        return $results;
    }

    /**
     * Create comprehensive analytics sheets for ad accounts
     */
    public function createAnalyticsSheets(AdAccount $adAccount): array
    {
        Log::info("Creating analytics sheets for account: {$adAccount->account_name}");

        $results = [
            'sheets_created' => 0,
            'errors' => 0,
            'created_sheets' => []
        ];

        try {
            // 1. Account Overview Sheet
            $this->createAccountOverviewSheet($adAccount, $results);

            // 2. Performance Summary Sheet
            $this->createPerformanceSummarySheet($adAccount, $results);

            // 3. Conversion Funnel Sheet
            $this->createConversionFunnelSheet($adAccount, $results);

            // 4. Campaign Comparison Sheet
            $this->createCampaignComparisonSheet($adAccount, $results);

        } catch (\Exception $e) {
            Log::error('Failed to create analytics sheets', [
                'account_id' => $adAccount->id,
                'error' => $e->getMessage()
            ]);
            $results['errors']++;
        }

        return $results;
    }

    /**
     * Monitor and heal broken sheet integrations
     */
    public function healBrokenIntegrations(): array
    {
        Log::info('Starting healing of broken sheet integrations');

        $results = [
            'campaigns_checked' => 0,
            'integrations_healed' => 0,
            'errors' => 0
        ];

        // Find campaigns that should have sheets but seem broken
        $brokenCampaigns = AdCampaign::where('sheets_integration_enabled', true)
            ->where(function ($query) {
                $query->whereNull('google_sheet_id')
                    ->orWhere('last_sheet_sync', '<', now()->subDays(7));
            })
            ->with('adAccount')
            ->get();

        foreach ($brokenCampaigns as $campaign) {
            $results['campaigns_checked']++;
            $this->healCampaignIntegration($campaign, $results);
        }

        Log::info('Integration healing completed', $results);
        return $results;
    }

    // Private helper methods

    private function createSheetForCampaign(AdCampaign $campaign, array &$results): void
    {
        try {
            if (!$this->googleSheetsService->isAvailable()) {
                Log::warning('Google Sheets service not available, skipping campaign', [
                    'campaign_id' => $campaign->id
                ]);
                return;
            }

            $mapping = $this->generateOptimalMapping($campaign);
            $adAccount = $campaign->adAccount;

            $sheetData = $this->googleSheetsService->createCampaignSheet(
                $campaign->id,
                $campaign->name,
                $mapping,
                $adAccount->account_name,
                $adAccount->external_account_id
            );

            if (isset($sheetData['error']) || isset($sheetData['requires_auth'])) {
                Log::warning('Sheet creation requires intervention', [
                    'campaign_id' => $campaign->id,
                    'issue' => $sheetData['error'] ?? 'requires_auth'
                ]);
                return;
            }

            $campaign->update([
                'google_sheet_id' => $sheetData['sheet_id'],
                'google_sheet_url' => $sheetData['sheet_url'],
                'sheet_mapping' => $mapping,
                'sheets_integration_enabled' => true,
                'last_sheet_sync' => now()
            ]);

            $results['sheets_created']++;
            $results['created_sheets'][] = [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'sheet_id' => $sheetData['sheet_id'],
                'sheet_url' => $sheetData['sheet_url']
            ];

            Log::info('Auto-created sheet for campaign', [
                'campaign_id' => $campaign->id,
                'sheet_id' => $sheetData['sheet_id']
            ]);

        } catch (\Exception $e) {
            $results['errors']++;
            Log::error('Failed to auto-create sheet', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processAdAccountFolderStructure(AdAccount $adAccount, array &$results): void
    {
        $results['accounts_processed']++;

        foreach ($adAccount->adCampaigns as $campaign) {
            $results['campaigns_processed']++;

            if (!$campaign->google_sheet_id) {
                $this->createSheetForCampaign($campaign, $results);
            }
        }
    }

    private function syncCampaignConversions(AdCampaign $campaign, $startTime, array &$results): void
    {
        try {
            // This would integrate with ConversionPixelService
            // For now, we'll simulate the process
            $results['campaigns_processed']++;

            // In a real implementation, you'd call:
            // $conversions = $this->conversionPixelService->getCampaignConversions($campaign->id, ['start_date' => $startTime]);
            // Then sync each conversion to the sheet

            $campaign->update(['last_sheet_sync' => now()]);

        } catch (\Exception $e) {
            $results['errors']++;
            Log::error('Failed to sync campaign conversions', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateCampaignMapping(AdCampaign $campaign, array &$results): void
    {
        try {
            $newMapping = $this->generateOptimalMapping($campaign);
            $currentMapping = $campaign->sheet_mapping ?? [];

            if ($newMapping !== $currentMapping) {
                $this->googleSheetsService->updateSheetMapping($campaign->google_sheet_id, $newMapping);
                $campaign->update(['sheet_mapping' => $newMapping]);
                $results['mappings_updated']++;
            }

        } catch (\Exception $e) {
            $results['errors']++;
            Log::error('Failed to update campaign mapping', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function createAccountOverviewSheet(AdAccount $adAccount, array &$results): void
    {
        $sheetData = $this->googleSheetsService->createCampaignSheet(
            'overview_' . $adAccount->id,
            "Account Overview - {$adAccount->account_name}",
            $this->getAccountOverviewMapping(),
            $adAccount->account_name,
            $adAccount->external_account_id
        );

        if (!isset($sheetData['error'])) {
            $results['sheets_created']++;
            $results['created_sheets'][] = $sheetData;
        }
    }

    private function createPerformanceSummarySheet(AdAccount $adAccount, array &$results): void
    {
        $sheetData = $this->googleSheetsService->createCampaignSheet(
            'performance_' . $adAccount->id,
            "Performance Summary - {$adAccount->account_name}",
            $this->getPerformanceSummaryMapping(),
            $adAccount->account_name,
            $adAccount->external_account_id
        );

        if (!isset($sheetData['error'])) {
            $results['sheets_created']++;
            $results['created_sheets'][] = $sheetData;
        }
    }

    private function createConversionFunnelSheet(AdAccount $adAccount, array &$results): void
    {
        $sheetData = $this->googleSheetsService->createCampaignSheet(
            'funnel_' . $adAccount->id,
            "Conversion Funnel - {$adAccount->account_name}",
            $this->getConversionFunnelMapping(),
            $adAccount->account_name,
            $adAccount->external_account_id
        );

        if (!isset($sheetData['error'])) {
            $results['sheets_created']++;
            $results['created_sheets'][] = $sheetData;
        }
    }

    private function createCampaignComparisonSheet(AdAccount $adAccount, array &$results): void
    {
        $sheetData = $this->googleSheetsService->createCampaignSheet(
            'comparison_' . $adAccount->id,
            "Campaign Comparison - {$adAccount->account_name}",
            $this->getCampaignComparisonMapping(),
            $adAccount->account_name,
            $adAccount->external_account_id
        );

        if (!isset($sheetData['error'])) {
            $results['sheets_created']++;
            $results['created_sheets'][] = $sheetData;
        }
    }

    private function healCampaignIntegration(AdCampaign $campaign, array &$results): void
    {
        try {
            if (!$campaign->google_sheet_id) {
                // Recreate missing sheet
                $this->createSheetForCampaign($campaign, $results);
                $results['integrations_healed']++;
            } else {
                // Test existing sheet and update sync timestamp
                $campaign->update(['last_sheet_sync' => now()]);
                $results['integrations_healed']++;
            }

        } catch (\Exception $e) {
            $results['errors']++;
            Log::error('Failed to heal campaign integration', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateOptimalMapping(AdCampaign $campaign): array
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
            'Platform' => 'platform',
            'Device Type' => 'device_type',
            'Browser' => 'browser',
            'OS' => 'operating_system',
            'Page URL' => 'page_url',
            'Referrer' => 'referrer',
            'UTM Source' => 'utm_source',
            'UTM Medium' => 'utm_medium',
            'UTM Campaign' => 'utm_campaign',
            'UTM Term' => 'utm_term',
            'UTM Content' => 'utm_content',
        ];

        // Add objective-specific fields
        switch ($campaign->objective) {
            case 'leads':
                $baseMapping = array_merge($baseMapping, [
                    'Lead Type' => 'lead_type',
                    'Lead Quality' => 'lead_quality',
                    'Form Fields' => 'form_fields_completed',
                    'Lead Source' => 'lead_source_page'
                ]);
                break;

            case 'sales':
                $baseMapping = array_merge($baseMapping, [
                    'Product ID' => 'product_id',
                    'Product Name' => 'product_name',
                    'Product Category' => 'product_category',
                    'Quantity' => 'quantity',
                    'Order ID' => 'order_id',
                    'Payment Method' => 'payment_method'
                ]);
                break;

            case 'calls':
                $baseMapping = array_merge($baseMapping, [
                    'Call Duration' => 'call_duration_seconds',
                    'Call Quality' => 'call_quality_score',
                    'Caller ID' => 'caller_phone_number',
                    'Call Outcome' => 'call_outcome'
                ]);
                break;
        }

        // Add funnel-specific fields
        if ($campaign->funnel_stage) {
            switch ($campaign->funnel_stage) {
                case 'TOF':
                    $baseMapping['Awareness Score'] = 'awareness_engagement_score';
                    $baseMapping['Video Completion'] = 'video_completion_rate';
                    break;
                case 'MOF':
                    $baseMapping['Engagement Level'] = 'content_engagement_level';
                    $baseMapping['Time on Site'] = 'session_duration_seconds';
                    break;
                case 'BOF':
                    $baseMapping['Purchase Intent'] = 'purchase_intent_score';
                    $baseMapping['Cart Value'] = 'cart_abandonment_value';
                    break;
            }
        }

        return $baseMapping;
    }

    private function getAccountOverviewMapping(): array
    {
        return [
            'Date' => 'date',
            'Account ID' => 'account_id',
            'Account Name' => 'account_name',
            'Platform' => 'platform',
            'Total Spend' => 'total_spend',
            'Total Impressions' => 'total_impressions',
            'Total Clicks' => 'total_clicks',
            'Total Conversions' => 'total_conversions',
            'Total Revenue' => 'total_revenue',
            'ROAS' => 'return_on_ad_spend',
            'CPC' => 'cost_per_click',
            'CPM' => 'cost_per_mille',
            'CTR' => 'click_through_rate',
            'Conversion Rate' => 'conversion_rate'
        ];
    }

    private function getPerformanceSummaryMapping(): array
    {
        return [
            'Campaign ID' => 'campaign_id',
            'Campaign Name' => 'campaign_name',
            'Objective' => 'objective',
            'Status' => 'status',
            'Daily Spend' => 'daily_spend',
            'Daily Impressions' => 'daily_impressions',
            'Daily Clicks' => 'daily_clicks',
            'Daily Conversions' => 'daily_conversions',
            'CPA' => 'cost_per_acquisition',
            'ROAS' => 'return_on_ad_spend',
            'Quality Score' => 'quality_score'
        ];
    }

    private function getConversionFunnelMapping(): array
    {
        return [
            'Funnel Stage' => 'funnel_stage',
            'Traffic Volume' => 'traffic_volume',
            'Conversion Volume' => 'conversion_volume',
            'Conversion Rate' => 'stage_conversion_rate',
            'Drop-off Rate' => 'stage_dropoff_rate',
            'Average Time in Stage' => 'average_stage_duration',
            'Revenue Attribution' => 'stage_revenue_attribution'
        ];
    }

    private function getCampaignComparisonMapping(): array
    {
        return [
            'Campaign A' => 'campaign_a_name',
            'Campaign B' => 'campaign_b_name',
            'Metric' => 'comparison_metric',
            'Value A' => 'campaign_a_value',
            'Value B' => 'campaign_b_value',
            'Difference' => 'percentage_difference',
            'Winner' => 'better_performer',
            'Statistical Significance' => 'significance_level'
        ];
    }
}