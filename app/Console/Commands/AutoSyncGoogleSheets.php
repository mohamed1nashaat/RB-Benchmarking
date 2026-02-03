<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoSyncGoogleSheets extends Command
{
    protected $signature = 'google-sheets:auto-sync
                           {--tenant_id= : Sync for specific tenant only}
                           {--account_id= : Sync for specific ad account only}
                           {--force : Force create sheets even if they exist}
                           {--dry-run : Show what would be created without actually creating}';

    protected $description = 'Automatically create Google Sheets folders and sheets for all ad accounts and campaigns';

    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        parent::__construct();
        $this->googleSheetsService = $googleSheetsService;
    }

    public function handle()
    {
        $this->info('🚀 Starting automated Google Sheets synchronization...');

        if (!$this->googleSheetsService->isAvailable()) {
            $this->error('❌ Google Sheets service is not available. Please check authentication.');
            return 1;
        }

        $this->info('✅ Google Sheets service authenticated via: ' . $this->googleSheetsService->getAuthMethod());

        $tenantId = $this->option('tenant_id');
        $accountId = $this->option('account_id');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual changes will be made');
        }

        // Get ad accounts to process
        $query = AdAccount::with(['adCampaigns', 'integration']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $adAccounts = $query->get();
        $this->info("📊 Found {$adAccounts->count()} ad accounts to process");

        $stats = [
            'accounts_processed' => 0,
            'folders_created' => 0,
            'sheets_created' => 0,
            'sheets_updated' => 0,
            'errors' => 0
        ];

        foreach ($adAccounts as $adAccount) {
            $this->processAdAccount($adAccount, $force, $dryRun, $stats);
            $stats['accounts_processed']++;
        }

        // Display final statistics
        $this->displayResults($stats, $dryRun);

        return 0;
    }

    private function processAdAccount(AdAccount $adAccount, bool $force, bool $dryRun, array &$stats): void
    {
        $this->line('');
        $this->info("🏢 Processing Ad Account: {$adAccount->account_name} ({$adAccount->external_account_id})");
        $this->info("   Platform: {$adAccount->integration->platform}");
        $this->info("   Campaigns: {$adAccount->adCampaigns->count()}");

        if ($adAccount->adCampaigns->isEmpty()) {
            $this->warn("   ⚠️  No campaigns found, skipping folder creation");
            return;
        }

        // Process each campaign in this account
        foreach ($adAccount->adCampaigns as $campaign) {
            $this->processCampaign($campaign, $adAccount, $force, $dryRun, $stats);
        }
    }

    private function processCampaign(AdCampaign $campaign, AdAccount $adAccount, bool $force, bool $dryRun, array &$stats): void
    {
        $this->info("  📈 Campaign: {$campaign->name} (ID: {$campaign->id})");

        // Check if sheet already exists
        if ($campaign->google_sheet_id && !$force) {
            $this->info("    ✅ Sheet already exists: {$campaign->google_sheet_id}");

            // Update existing sheet mapping if needed
            if ($this->shouldUpdateSheetMapping($campaign)) {
                if (!$dryRun) {
                    $this->updateExistingSheet($campaign, $stats);
                } else {
                    $this->info("    🔄 Would update sheet mapping");
                }
            }
            return;
        }

        if ($force && $campaign->google_sheet_id) {
            $this->warn("    🔄 Force mode: Recreating existing sheet");
        }

        if ($dryRun) {
            $this->info("    📝 Would create new sheet for campaign");
            return;
        }

        // Create new sheet
        $this->createCampaignSheet($campaign, $adAccount, $stats);
    }

    private function createCampaignSheet(AdCampaign $campaign, AdAccount $adAccount, array &$stats): void
    {
        try {
            // Prepare default mapping based on campaign objective
            $mapping = $this->generateOptimalMapping($campaign);

            $sheetData = $this->googleSheetsService->createCampaignSheet(
                $campaign->id,
                $campaign->name,
                $mapping,
                $adAccount->account_name,
                $adAccount->external_account_id
            );

            if (isset($sheetData['error'])) {
                $this->error("    ❌ Error: {$sheetData['error']}");
                $stats['errors']++;
                return;
            }

            if (isset($sheetData['requires_auth'])) {
                $this->warn("    🔐 Authorization required: {$sheetData['auth_url']}");
                return;
            }

            // Update campaign with sheet information
            $campaign->update([
                'google_sheet_id' => $sheetData['sheet_id'],
                'google_sheet_url' => $sheetData['sheet_url'],
                'sheet_mapping' => $mapping,
                'sheets_integration_enabled' => true,
                'last_sheet_sync' => now()
            ]);

            $this->info("    ✅ Created sheet: {$sheetData['sheet_id']}");
            $this->info("    🔗 URL: {$sheetData['sheet_url']}");
            $stats['sheets_created']++;

        } catch (\Exception $e) {
            $this->error("    ❌ Failed to create sheet: " . $e->getMessage());
            $stats['errors']++;

            Log::error('Auto-sync failed to create sheet', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateExistingSheet(AdCampaign $campaign, array &$stats): void
    {
        try {
            $mapping = $this->generateOptimalMapping($campaign);

            $success = $this->googleSheetsService->updateSheetMapping(
                $campaign->google_sheet_id,
                $mapping
            );

            if ($success) {
                $campaign->update([
                    'sheet_mapping' => $mapping,
                    'last_sheet_sync' => now()
                ]);

                $this->info("    🔄 Updated sheet mapping");
                $stats['sheets_updated']++;
            } else {
                $this->warn("    ⚠️  Failed to update sheet mapping");
            }

        } catch (\Exception $e) {
            $this->error("    ❌ Error updating sheet: " . $e->getMessage());
            $stats['errors']++;
        }
    }

    private function shouldUpdateSheetMapping(AdCampaign $campaign): bool
    {
        // Update if mapping is empty or campaign has been updated recently
        $currentMapping = $campaign->sheet_mapping ?? [];
        $optimalMapping = $this->generateOptimalMapping($campaign);

        // Check if mappings are different
        return empty($currentMapping) || $currentMapping !== $optimalMapping;
    }

    private function generateOptimalMapping(AdCampaign $campaign): array
    {
        // Base mapping for all campaigns
        $baseMapping = [
            'Timestamp' => 'timestamp',
            'Conversion ID' => 'conversion_id',
            'Campaign ID' => 'campaign_id',
            'User ID' => 'user_id',
            'Session ID' => 'session_id',
            'Conversion Type' => 'conversion_type',
            'Conversion Value' => 'conversion_value',
            'Currency' => 'currency',
            'Source' => 'source',
            'Medium' => 'medium',
            'Channel' => 'channel',
            'Device Type' => 'device_type',
            'Browser' => 'browser',
            'Page URL' => 'page_url',
            'Referrer' => 'referrer',
        ];

        // Add UTM tracking
        $utmMapping = [
            'UTM Source' => 'utm_source',
            'UTM Medium' => 'utm_medium',
            'UTM Campaign' => 'utm_campaign',
            'UTM Term' => 'utm_term',
            'UTM Content' => 'utm_content',
        ];

        // Objective-specific fields
        $objectiveMapping = [];
        switch ($campaign->objective) {
            case 'leads':
                $objectiveMapping = [
                    'Lead Type' => 'lead_type',
                    'Lead Quality Score' => 'lead_quality_score',
                    'Form Completion Time' => 'form_completion_time',
                ];
                break;
            case 'sales':
                $objectiveMapping = [
                    'Product ID' => 'product_id',
                    'Product Category' => 'product_category',
                    'Order Value' => 'order_value',
                    'Quantity' => 'quantity',
                ];
                break;
            case 'calls':
                $objectiveMapping = [
                    'Call Duration' => 'call_duration',
                    'Call Quality' => 'call_quality',
                    'Phone Number' => 'phone_number',
                ];
                break;
        }

        // Funnel stage specific fields
        $funnelMapping = [];
        if ($campaign->funnel_stage) {
            switch ($campaign->funnel_stage) {
                case 'TOF':
                    $funnelMapping = [
                        'Awareness Metric' => 'awareness_metric',
                        'Video View Duration' => 'video_view_duration',
                    ];
                    break;
                case 'MOF':
                    $funnelMapping = [
                        'Engagement Score' => 'engagement_score',
                        'Content Interaction' => 'content_interaction',
                    ];
                    break;
                case 'BOF':
                    $funnelMapping = [
                        'Intent Signal' => 'intent_signal',
                        'Purchase Intent Score' => 'purchase_intent_score',
                    ];
                    break;
            }
        }

        // Custom fields for flexibility
        $customMapping = [
            'Custom Field 1' => 'custom_field_1',
            'Custom Field 2' => 'custom_field_2',
            'Custom Field 3' => 'custom_field_3',
        ];

        return array_merge(
            $baseMapping,
            $utmMapping,
            $objectiveMapping,
            $funnelMapping,
            $customMapping
        );
    }

    private function displayResults(array $stats, bool $dryRun): void
    {
        $this->line('');
        $this->info('📊 ' . ($dryRun ? 'DRY RUN ' : '') . 'SYNCHRONIZATION RESULTS');
        $this->info('════════════════════════════════════════');
        $this->info("✅ Accounts Processed: {$stats['accounts_processed']}");
        $this->info("📁 Folders Created: {$stats['folders_created']}");
        $this->info("📄 Sheets Created: {$stats['sheets_created']}");
        $this->info("🔄 Sheets Updated: {$stats['sheets_updated']}");

        if ($stats['errors'] > 0) {
            $this->error("❌ Errors: {$stats['errors']}");
        }

        $this->line('');
        if ($dryRun) {
            $this->warn('🔍 This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Synchronization completed!');
        }
    }
}