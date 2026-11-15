<?php

namespace App\Services;

use App\Models\AiVisibilitySnapshot;
use App\Models\Competitor;
use App\Models\CompetitorVisibility;
use App\Models\SemrushReport;
use InvalidArgumentException;

/**
 * Saves parsed Semrush AI Visibility exports (brand topics + prompts per brand)
 * for one client and month. Used by `semrush:import` and the upload card.
 * Callers wrap it in a transaction.
 */
class SemrushImporter
{
    /**
     * @param  array<string, array{topics: array, prompts: array, source_files?: array}>  $groups  keyed by brand domain
     */
    public function import(int $tenantId, string $clientDomain, string $date, array $groups): void
    {
        $month = substr($date, 0, 7);
        foreach ($groups as $brandDomain => $data) {
            if (! isset($data['topics'], $data['prompts'])) {
                throw new InvalidArgumentException('Missing paired export for '.$brandDomain);
            }
            $isClient = self::sameSite($brandDomain, $clientDomain);
            SemrushReport::updateOrCreate(
                ['tenant_id' => $tenantId, 'domain' => $brandDomain, 'report_month' => $month],
                [
                    'topics' => $data['topics'], 'prompts' => $data['prompts'],
                    'source_files' => $data['source_files'] ?? [],
                    'captured_at' => $date, 'is_client' => $isClient,
                ]
            );
            if ($isClient) {
                $topics = collect($data['topics'])->sortByDesc('visibility')->take(15)->map(fn ($row) => [
                    'topic' => $row['name'], 'market' => strtoupper($row['country']),
                    'visibility' => $row['visibility'], 'difficulty' => $row['difficulty'],
                    'mentions' => $row['mentions'], 'volume' => $row['volume'],
                ])->values()->all();
                AiVisibilitySnapshot::updateOrCreate(
                    ['tenant_id' => $tenantId, 'report_month' => $month],
                    ['snapshot_date' => $date, 'semrush_topics_count' => count($data['topics']),
                        'semrush_prompts_count' => count($data['prompts']), 'semrush_top_topics' => $topics]
                );
            } else {
                $competitor = self::findCompetitor($tenantId, $brandDomain);
                if (! $competitor) {
                    throw new InvalidArgumentException('Register competitor '.$brandDomain.' for this client first.');
                }
                CompetitorVisibility::updateOrCreate(
                    ['competitor_id' => $competitor->id, 'report_month' => $month],
                    ['semrush_topics_count' => count($data['topics'])]
                );
            }
        }
    }

    public static function findCompetitor(int $tenantId, string $domain): ?Competitor
    {
        return Competitor::where('tenant_id', $tenantId)->get()
            ->first(fn ($c) => self::sameSite(self::host($c->website ?? ''), $domain));
    }

    /** Host of a URL or bare domain, lower-case, without www. / sc-domain: prefixes. */
    public static function host(string $value): string
    {
        $value = trim(preg_replace('/^sc-domain:/i', '', trim($value)));
        if ($value === '') {
            return '';
        }
        $host = parse_url(str_contains($value, '://') ? $value : 'https://'.$value, PHP_URL_HOST) ?? '';

        return preg_replace('/^www\./', '', strtolower($host));
    }

    /** True when both hosts are the same site (store.example.com matches example.com). */
    public static function sameSite(string $a, string $b): bool
    {
        $a = self::host($a);
        $b = self::host($b);
        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_ends_with($a, '.'.$b) || str_ends_with($b, '.'.$a);
    }
}
