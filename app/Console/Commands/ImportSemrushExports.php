<?php

namespace App\Console\Commands;

use App\Models\AiVisibilitySnapshot;
use App\Models\Competitor;
use App\Models\CompetitorVisibility;
use App\Models\SemrushReport;
use App\Models\Tenant;
use App\Services\SemrushExportParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSemrushExports extends Command
{
    protected $signature = 'semrush:import {directory} {--tenant=} {--domain=} {--date=}';

    protected $description = 'Import paired worldwide Semrush topics and prompts CSV exports for a client';

    public function handle(SemrushExportParser $parser): int
    {
        $tenant = Tenant::findOrFail($this->option('tenant'));
        $domain = strtolower((string) $this->option('domain'));
        $date = (string) $this->option('date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date) || date('Y-m-d', strtotime($date)) !== $date || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $this->error('Supply a valid --domain and --date=YYYY-MM-DD.');

            return self::FAILURE;
        }
        $month = substr($date, 0, 7);
        $groups = [];
        foreach (glob(rtrim($this->argument('directory'), '/').'/*.csv') as $file) {
            if (! preg_match('/^(brand_topics|prompts)_(.+)_worldwide(?: \(\d+\))?\.csv$/', basename($file), $match)) {
                continue;
            }
            $brandDomain = str_replace('_', '.', $match[2]);
            $type = $match[1] === 'brand_topics' ? 'topics' : 'prompts';
            if (isset($groups[$brandDomain][$type])) {
                throw new \InvalidArgumentException('Duplicate export for '.$brandDomain);
            }
            $groups[$brandDomain][$type] = $parser->parse($file, $type);
            $groups[$brandDomain]['source_files'][] = basename($file);
        }
        if (! isset($groups[$domain])) {
            throw new \InvalidArgumentException('Client domain not found in exports.');
        }
        foreach ($groups as $brandDomain => $data) {
            if (! isset($data['topics'], $data['prompts'])) {
                throw new \InvalidArgumentException('Missing paired export for '.$brandDomain);
            }
        }
        DB::transaction(function () use ($groups, $tenant, $domain, $month, $date) {
            foreach ($groups as $brandDomain => $data) {
                SemrushReport::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'domain' => $brandDomain, 'report_month' => $month],
                    $data + ['captured_at' => $date, 'is_client' => $brandDomain === $domain]
                );
                if ($brandDomain === $domain) {
                    $topics = collect($data['topics'])->sortByDesc('visibility')->take(15)->map(fn ($row) => [
                        'topic' => $row['name'], 'market' => strtoupper($row['country']),
                        'visibility' => $row['visibility'], 'difficulty' => $row['difficulty'],
                        'mentions' => $row['mentions'], 'volume' => $row['volume'],
                    ])->values()->all();
                    AiVisibilitySnapshot::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'report_month' => $month],
                        ['snapshot_date' => $date, 'semrush_topics_count' => count($data['topics']),
                            'semrush_prompts_count' => count($data['prompts']), 'semrush_top_topics' => $topics]
                    );
                } else {
                    $competitor = Competitor::where('tenant_id', $tenant->id)->get()->first(function ($item) use ($brandDomain) {
                        $host = parse_url(str_contains($item->website ?? '', '://') ? $item->website : 'https://'.$item->website, PHP_URL_HOST);

                        return preg_replace('/^www\./', '', strtolower($host ?? '')) === $brandDomain;
                    });
                    if (! $competitor) {
                        throw new \InvalidArgumentException('Register competitor '.$brandDomain.' for this client first.');
                    }
                    CompetitorVisibility::updateOrCreate(
                        ['competitor_id' => $competitor->id, 'report_month' => $month],
                        ['semrush_topics_count' => count($data['topics'])]
                    );
                }
            }
        });
        foreach ($groups as $brandDomain => $data) {
            $this->info($brandDomain.': '.count($data['topics']).' topics, '.count($data['prompts']).' prompt records');
        }

        return self::SUCCESS;
    }
}
