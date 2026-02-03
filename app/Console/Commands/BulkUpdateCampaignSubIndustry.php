<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use Illuminate\Console\Command;

class BulkUpdateCampaignSubIndustry extends Command
{
    protected $signature = 'campaigns:bulk-update-sub-industry
                            {--dry-run : Show what would be updated without making changes}
                            {--list : List all campaigns with current sub_industry}
                            {--null-only : Only update campaigns with null sub_industry}';

    protected $description = 'Bulk update campaign sub_industry based on campaign name patterns';

    protected array $patterns = [
        // Awareness campaigns
        'awareness' => [
            'sub_industry' => 'Brand Awareness',
            'patterns' => ['awareness', 'brand', 'branding', 'reach', 'impressions', 'visibility'],
        ],
        // Lead Generation
        'lead_gen' => [
            'sub_industry' => 'Lead Generation',
            'patterns' => ['lead', 'leads', 'form', 'signup', 'register', 'inquiry', 'enquiry', 'contact'],
        ],
        // Sales / Conversions
        'sales' => [
            'sub_industry' => 'Sales & Conversions',
            'patterns' => ['sale', 'sales', 'purchase', 'buy', 'order', 'checkout', 'conversion', 'convert', 'revenue'],
        ],
        // Traffic
        'traffic' => [
            'sub_industry' => 'Website Traffic',
            'patterns' => ['traffic', 'click', 'visit', 'website', 'landing'],
        ],
        // App Install
        'app' => [
            'sub_industry' => 'App Install',
            'patterns' => ['app install', 'download', 'app promotion', 'mobile app'],
        ],
        // Video Views
        'video' => [
            'sub_industry' => 'Video Views',
            'patterns' => ['video', 'watch', 'view', 'youtube', 'reels', 'tiktok'],
        ],
        // Engagement
        'engagement' => [
            'sub_industry' => 'Engagement',
            'patterns' => ['engage', 'engagement', 'like', 'comment', 'share', 'interaction'],
        ],
        // Retargeting
        'retargeting' => [
            'sub_industry' => 'Retargeting',
            'patterns' => ['retarget', 'remarketing', 'remarket', 'abandon', 'cart'],
        ],
        // Catalog / Shopping
        'catalog' => [
            'sub_industry' => 'Catalog Sales',
            'patterns' => ['catalog', 'shopping', 'product', 'dpa', 'dynamic'],
        ],
        // Messages
        'messages' => [
            'sub_industry' => 'Messages',
            'patterns' => ['message', 'whatsapp', 'messenger', 'chat', 'dm'],
        ],
        // Store Visits
        'store' => [
            'sub_industry' => 'Store Visits',
            'patterns' => ['store visit', 'footfall', 'in-store', 'location'],
        ],
        // Seasonal / Promotional
        'seasonal' => [
            'sub_industry' => 'Seasonal Campaign',
            'patterns' => ['ramadan', 'eid', 'valentine', 'mother', 'father', 'national day', 'founding day', 'black friday', 'eoy', 'end of year'],
        ],
        // Property / Real Estate specific
        'property' => [
            'sub_industry' => 'Property Promotion',
            'patterns' => ['bof', 'saheel', 'bouvardia', 'dyar', 'villa', 'apartment', 'unit', 'cityscape'],
        ],
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listCampaigns();
        }

        $dryRun = $this->option('dry-run');
        $nullOnly = $this->option('null-only');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        if ($nullOnly) {
            $this->info('NULL ONLY MODE - Only updating campaigns with null sub_industry');
        }

        $query = AdCampaign::withoutGlobalScopes()->with('adAccount');
        if ($nullOnly) {
            $query->whereNull('sub_industry');
        }
        $campaigns = $query->get();
        $this->info("Found {$campaigns->count()} campaigns");

        $updated = 0;
        $skipped = 0;
        $noMatch = 0;

        $updates = [];

        foreach ($campaigns as $campaign) {
            $campaignName = strtolower($campaign->name);
            $matched = false;

            foreach ($this->patterns as $key => $config) {
                foreach ($config['patterns'] as $pattern) {
                    if (str_contains($campaignName, strtolower($pattern))) {
                        $updates[] = [
                            'campaign' => $campaign,
                            'sub_industry' => $config['sub_industry'],
                            'matched_pattern' => $pattern,
                        ];
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $noMatch++;
                if ($this->getOutput()->isVerbose()) {
                    $this->line("No match for: {$campaign->name}");
                }
            }
        }

        // Show summary of planned updates
        $this->info("\nPlanned updates: " . count($updates));
        $this->info("No match found: {$noMatch}");
        $this->newLine();

        // Group by sub_industry for display
        $bySubIndustry = collect($updates)->groupBy('sub_industry');
        foreach ($bySubIndustry as $subIndustry => $group) {
            $this->info("=== {$subIndustry} ({$group->count()} campaigns) ===");
            foreach ($group->take(10) as $item) {
                $campaign = $item['campaign'];
                $currentSubIndustry = $campaign->sub_industry ?? 'null';

                $status = '';
                if ($currentSubIndustry === $item['sub_industry']) {
                    $status = ' [ALREADY SET]';
                    $skipped++;
                } else {
                    $status = " [WILL UPDATE from {$currentSubIndustry}]";
                    $updated++;
                }

                $this->line("  - " . substr($campaign->name, 0, 60) . " (pattern: {$item['matched_pattern']}){$status}");
            }
            if ($group->count() > 10) {
                $this->line("  ... and " . ($group->count() - 10) . " more");
            }
            $this->newLine();
        }

        if (!$dryRun) {
            if (!$this->confirm('Do you want to apply these updates?')) {
                $this->info('Aborted.');
                return 0;
            }

            $actualUpdated = 0;
            foreach ($updates as $item) {
                $campaign = $item['campaign'];
                if ($campaign->sub_industry !== $item['sub_industry']) {
                    $campaign->sub_industry = $item['sub_industry'];
                    $campaign->save();
                    $actualUpdated++;
                }
            }

            $this->info("\nActually updated: {$actualUpdated} campaigns");
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Would update: {$updated}");
        $this->info("  - Already correct: {$skipped}");
        $this->info("  - No pattern match: {$noMatch}");

        return 0;
    }

    protected function listCampaigns(): int
    {
        $campaigns = AdCampaign::withoutGlobalScopes()
            ->with('adAccount')
            ->orderBy('sub_industry')
            ->orderBy('name')
            ->get();

        $bySubIndustry = $campaigns->groupBy('sub_industry');

        $this->info("Total campaigns: {$campaigns->count()}");
        $this->newLine();
        $this->info("By Sub-Industry:");
        foreach ($bySubIndustry as $subIndustry => $group) {
            $label = $subIndustry ?: 'null';
            $this->line("  - {$label}: {$group->count()}");
        }

        return 0;
    }
}
