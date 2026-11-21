<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use App\Services\CampaignNamingParser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ParseCampaignNames extends Command
{
    protected $signature = 'campaigns:parse-names
                            {--account= : Specific account ID to process}
                            {--only-empty : Only process campaigns with missing data}
                            {--dry-run : Preview changes without saving}
                            {--limit= : Maximum campaigns to process (default: all)}
                            {--fields= : Comma-separated list of fields to update (default: all)}';

    protected $description = 'Parse campaign names to extract and assign objective, funnel_stage, geo_targeting, channel_type, industry, category, sub_industry';

    private array $updatableFields = [
        'objective',
        'funnel_stage',
        'geo_targeting',
        'channel_type',
        'category',
        'sub_industry',
        'sub_category',
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Campaign Name Parser — Extract Data from Naming Conventions');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');

        $isDryRun = $this->option('dry-run');
        $onlyEmpty = $this->option('only-empty');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fieldsOption = $this->option('fields');
        $fieldsToUpdate = $fieldsOption ? explode(',', $fieldsOption) : $this->updatableFields;

        if ($isDryRun) {
            $this->warn('DRY RUN MODE — No changes will be saved');
            $this->info('');
        }

        // Build query
        $query = AdCampaign::query()->with('adAccount');

        if ($accountId = $this->option('account')) {
            $query->where('ad_account_id', $accountId);
        }

        if ($onlyEmpty) {
            $query->where(function ($q) {
                $q->whereNull('objective')->orWhere('objective', '')
                  ->orWhereNull('funnel_stage')->orWhere('funnel_stage', '')
                  ->orWhereNull('geo_targeting')->orWhere('geo_targeting', '')
                  ->orWhereNull('channel_type')->orWhere('channel_type', '')
                  ->orWhereNull('category')->orWhere('category', '')
                  ->orWhereNull('sub_industry')->orWhere('sub_industry', '');
            });
        }

        if ($limit) {
            $query->limit($limit);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->warn('No campaigns found matching the criteria.');
            return 0;
        }

        $this->info("Processing {$campaigns->count()} campaigns...");
        $this->info('');

        $parser = app(CampaignNamingParser::class);

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'by_format' => [],
            'by_field' => array_fill_keys($fieldsToUpdate, 0),
        ];

        $results = [];
        $progressBar = $this->output->createProgressBar($campaigns->count());
        $progressBar->start();

        foreach ($campaigns as $campaign) {
            $stats['processed']++;

            $parsed = $parser->parse($campaign->name, $campaign->adAccount?->account_name);
            $format = $parsed['format'];
            $stats['by_format'][$format] = ($stats['by_format'][$format] ?? 0) + 1;

            $updates = [];
            $changes = [];

            foreach ($fieldsToUpdate as $field) {
                $value = $parsed[$field] ?? null;
                if ($value === null) {
                    continue;
                }

                $current = $campaign->{$field};
                if (empty($current) || $current === 'awareness') {
                    // Only update if field is empty, OR if it's currently the generic "awareness" default
                    // and we found something more specific
                    if ($field === 'objective' && $current === 'awareness' && $value !== 'awareness') {
                        $updates[$field] = $value;
                        $changes[] = "$field: $current → $value";
                        $stats['by_field'][$field]++;
                    } elseif (empty($current)) {
                        $updates[$field] = $value;
                        $changes[] = "$field: $value";
                        $stats['by_field'][$field]++;
                    }
                }
            }

            if (!empty($updates)) {
                if (!$isDryRun) {
                    $campaign->update($updates);
                }
                $stats['updated']++;

                $results[] = [
                    'campaign' => $campaign->name,
                    'account' => $campaign->adAccount?->account_name ?? '-',
                    'format' => $format,
                    'changes' => implode(', ', $changes),
                    'confidence' => $parsed['confidence'],
                ];
            } else {
                $stats['skipped']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info('');
        $this->info('');

        // Show results table (limit to 50)
        if (!empty($results)) {
            $this->info('Results (showing up to 50 updated campaigns):');
            $this->info('');

            $tableHeaders = ['Campaign', 'Account', 'Format', 'Changes', 'Conf'];
            $tableRows = array_map(function ($r) {
                return [
                    Str::limit($r['campaign'] ?? '-', 35),
                    Str::limit($r['account'] ?? '-', 20),
                    $r['format'],
                    Str::limit($r['changes'] ?? '-', 40),
                    $r['confidence'],
                ];
            }, array_slice($results, 0, 50));

            $this->table($tableHeaders, $tableRows);
            $this->info('');
        }

        // Summary
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info("  Total Processed:  {$stats['processed']}");
        $this->info("  Updated:          {$stats['updated']}" . ($isDryRun ? ' (dry run)' : ''));
        $this->info("  Skipped:          {$stats['skipped']}");
        $this->info('');
        $this->info('  By Format:');
        foreach ($stats['by_format'] as $format => $count) {
            $this->info("    - $format: $count");
        }
        $this->info('');
        $this->info('  By Field Updated:');
        foreach ($stats['by_field'] as $field => $count) {
            if ($count > 0) {
                $this->info("    - $field: $count");
            }
        }
        $this->info('');

        if ($isDryRun && $stats['updated'] > 0) {
            $this->warn('Run without --dry-run to apply these changes.');
        }

        return 0;
    }
}
