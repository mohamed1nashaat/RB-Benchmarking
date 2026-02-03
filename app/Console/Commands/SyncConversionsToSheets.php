<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use App\Services\GoogleSheetsService;
use App\Services\ConversionPixelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncConversionsToSheets extends Command
{
    protected $signature = 'google-sheets:sync-conversions
                           {--tenant_id= : Sync for specific tenant only}
                           {--campaign_id= : Sync for specific campaign only}
                           {--hours=24 : Number of hours to look back for conversions}
                           {--batch-size=50 : Number of conversions to process per batch}';

    protected $description = 'Sync recent conversions to Google Sheets for all enabled campaigns';

    private GoogleSheetsService $googleSheetsService;
    private ConversionPixelService $conversionPixelService;

    public function __construct(GoogleSheetsService $googleSheetsService, ConversionPixelService $conversionPixelService)
    {
        parent::__construct();
        $this->googleSheetsService = $googleSheetsService;
        $this->conversionPixelService = $conversionPixelService;
    }

    public function handle()
    {
        $this->info('🔄 Starting conversions sync to Google Sheets...');

        if (!$this->googleSheetsService->isAvailable()) {
            $this->error('❌ Google Sheets service is not available. Please check authentication.');
            return 1;
        }

        $tenantId = $this->option('tenant_id');
        $campaignId = $this->option('campaign_id');
        $hours = (int) $this->option('hours');
        $batchSize = (int) $this->option('batch-size');

        // Get campaigns with Google Sheets integration enabled
        $query = AdCampaign::where('sheets_integration_enabled', true)
            ->whereNotNull('google_sheet_id')
            ->with('adAccount');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->warn('⚠️  No campaigns found with Google Sheets integration enabled');
            return 0;
        }

        $this->info("📊 Found {$campaigns->count()} campaigns with sheets integration");

        $stats = [
            'campaigns_processed' => 0,
            'conversions_synced' => 0,
            'errors' => 0,
            'skipped' => 0
        ];

        $startTime = now()->subHours($hours);

        foreach ($campaigns as $campaign) {
            $this->processCampaignConversions($campaign, $startTime, $batchSize, $stats);
        }

        $this->displaySyncResults($stats);

        return 0;
    }

    private function processCampaignConversions(AdCampaign $campaign, $startTime, int $batchSize, array &$stats): void
    {
        $this->info("📈 Processing: {$campaign->name} (ID: {$campaign->id})");

        try {
            // Get conversions for this campaign
            $conversions = $this->conversionPixelService->getCampaignConversions($campaign->id, [
                'start_date' => $startTime,
                'limit' => $batchSize
            ]);

            if (empty($conversions['conversions'])) {
                $this->info("   📭 No new conversions found");
                $stats['skipped']++;
                return;
            }

            $conversionCount = count($conversions['conversions']);
            $this->info("   📊 Found {$conversionCount} conversions to sync");

            $syncedCount = 0;
            foreach ($conversions['conversions'] as $conversion) {
                try {
                    $success = $this->googleSheetsService->logConversion(
                        $campaign->google_sheet_id,
                        (array) $conversion,
                        $campaign->sheet_mapping ?? []
                    );

                    if ($success) {
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Failed to sync conversion: " . $e->getMessage());
                    Log::error('Conversion sync failed', [
                        'campaign_id' => $campaign->id,
                        'conversion_id' => $conversion['conversion_id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update last sync timestamp
            $campaign->update(['last_sheet_sync' => now()]);

            $this->info("   ✅ Synced {$syncedCount}/{$conversionCount} conversions");
            $stats['conversions_synced'] += $syncedCount;
            $stats['campaigns_processed']++;

        } catch (\Exception $e) {
            $this->error("   ❌ Error processing campaign: " . $e->getMessage());
            $stats['errors']++;

            Log::error('Campaign conversion sync failed', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function displaySyncResults(array $stats): void
    {
        $this->line('');
        $this->info('📊 CONVERSION SYNC RESULTS');
        $this->info('════════════════════════════');
        $this->info("✅ Campaigns Processed: {$stats['campaigns_processed']}");
        $this->info("📊 Conversions Synced: {$stats['conversions_synced']}");
        $this->info("⏭️  Campaigns Skipped: {$stats['skipped']}");

        if ($stats['errors'] > 0) {
            $this->error("❌ Errors: {$stats['errors']}");
        }

        $this->info('✅ Conversion sync completed!');
    }
}