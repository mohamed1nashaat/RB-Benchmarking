<?php

namespace App\Services\AiTracking;

use App\Models\AiPromptRun;
use App\Models\AiTrackedPrompt;
use App\Models\AiTrackingProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns stored prompt runs into the report's live tracking section:
 * visibility, rank, citations and share of voice by week and by assistant.
 */
class TrackerReport
{
    public static function forTenant(int $tenantId): array
    {
        $profile = AiTrackingProfile::where('tenant_id', $tenantId)->first();
        $prompts = AiTrackedPrompt::where('tenant_id', $tenantId)->get()->keyBy('id');
        $runs = AiPromptRun::where('tenant_id', $tenantId)->where('status', 'ok')
            ->where('run_on', '>=', Carbon::today()->subWeeks(12)->toDateString())
            ->get(['ai_tracked_prompt_id', 'engine', 'run_on', 'brand_mentioned', 'brand_rank', 'brand_cited', 'brands', 'sources', 'sentiment'])
            ->map(fn ($r) => [
                'prompt' => $r->ai_tracked_prompt_id, 'engine' => $r->engine, 'run_on' => substr((string) $r->run_on, 0, 10),
                'mentioned' => $r->brand_mentioned, 'rank' => $r->brand_rank, 'cited' => $r->brand_cited,
                'brands' => $r->brands ?? [], 'sources' => $r->sources ?? [], 'sentiment' => $r->sentiment,
            ])->all();

        return self::build($runs, $prompts->map(fn ($p) => ['prompt' => $p->prompt, 'topic' => $p->topic, 'volume' => $p->volume, 'country' => $p->country, 'active' => $p->active])->all(), $profile?->domain, [
            'profile' => (bool) $profile,
            'activePrompts' => $prompts->where('active', true)->count(),
            'engines' => AiEngineClient::available(),
        ]);
    }

    /**
     * @param  array  $runs  rows: prompt, engine, run_on, mentioned, rank, cited, brands, sources, sentiment
     * @param  array  $prompts  [id => [prompt, topic, volume, country]]
     */
    public static function build(array $runs, array $prompts, ?string $domain, array $setup = []): array
    {
        $base = ['setup' => $setup + ['profile' => false, 'activePrompts' => count($prompts), 'engines' => []], 'available' => false];
        if (! $runs) {
            return $base;
        }

        // Headline numbers use the real assistants only. Simulated engines (an open model on
        // Ollama) get their own rows, and drive the headline only when nothing real has run.
        $real = array_values(array_filter($runs, fn ($r) => ! AiEngineClient::simulated($r['engine'])));
        $basis = $real ? 'measured' : 'simulated';
        $allByWeek = self::byWeek($runs);
        $byWeek = $real ? self::byWeek($real) : $allByWeek;
        $weeks = $byWeek->keys()->all();
        $cur = $byWeek->last();
        $prev = count($weeks) > 1 ? $byWeek[$weeks[count($weeks) - 2]] : null;
        $curWeek = end($weeks);
        $curAll = $allByWeek[$curWeek] ?? $cur;

        $kpis = self::kpis($cur);
        $prevKpis = $prev ? self::kpis($prev) : null;
        $delta = fn ($k) => $prevKpis && $kpis[$k] !== null && $prevKpis[$k] !== null ? $kpis[$k] - $prevKpis[$k] : null;

        $engines = $curAll->groupBy('engine')->map(fn ($g, $e) => ['engine' => AiEngineClient::label($e), 'simulated' => AiEngineClient::simulated($e)] + self::kpis($g))
            ->sortBy([['simulated', 'asc'], ['visibility', 'desc']])->values()->all();

        // Brands: mentions, average rank and citations across this week's answers.
        $brands = [];
        foreach ($cur as $r) {
            foreach ($r['brands'] as $b) {
                $brands[$b['name']] ??= ['name' => $b['name'], 'client' => (bool) $b['client'], 'mentions' => 0, 'ranks' => [], 'cited' => 0, 'first' => 0];
                if ($b['rank'] !== null) {
                    $brands[$b['name']]['mentions']++;
                    $brands[$b['name']]['ranks'][] = $b['rank'];
                    $brands[$b['name']]['first'] += $b['rank'] === 1 ? 1 : 0;
                }
                $brands[$b['name']]['cited'] += $b['cited'] ? 1 : 0;
            }
        }
        $answers = $cur->count();
        $totalMentions = array_sum(array_column($brands, 'mentions'));
        $brands = collect($brands)->map(fn ($b) => [
            'name' => $b['name'], 'client' => $b['client'], 'mentions' => $b['mentions'],
            'visibility' => $answers ? $b['mentions'] / $answers * 100 : 0,
            'sov' => $totalMentions ? $b['mentions'] / $totalMentions * 100 : 0,
            'avgRank' => $b['ranks'] ? array_sum($b['ranks']) / count($b['ranks']) : null,
            'first' => $b['first'], 'cited' => $b['cited'],
        ])->sortByDesc('mentions')->values();
        $client = $brands->firstWhere('client', true);
        $sovPie = $brands->filter(fn ($b) => $b['mentions'] > 0)->sortByDesc(fn ($b) => [$b['client'] ? 1 : 0, $b['mentions']])
            ->map(fn ($b) => [$b['name'], $b['mentions']])->values()->all();

        // Sources: how many answers linked to each site.
        $domains = [];
        foreach ($cur as $r) {
            foreach (collect($r['sources'])->pluck('domain')->filter()->unique() as $d) {
                $domains[$d] = ($domains[$d] ?? 0) + 1;
            }
        }
        arsort($domains);
        $sources = collect($domains)->take(10)->map(fn ($n, $d) => [
            'l' => $d, 'v' => $n, 's' => number_format($n / max(1, $answers) * 100, 0).'% of answers',
            'own' => $domain && AnswerAnalyzer::sameSite($d, $domain),
        ])->values()->all();

        // Prompt table: this week's result per assistant, plus gaps where only competitors are named.
        $engineKeys = $curAll->pluck('engine')->unique()->sort(fn ($a, $b) => array_search($a, array_keys(AiEngineClient::ENGINES)) <=> array_search($b, array_keys(AiEngineClient::ENGINES)))->values()->all();
        $rows = $curAll->groupBy('prompt')->map(function ($g, $pid) use ($prompts, $engineKeys, $basis) {
            $cells = [];
            foreach ($engineKeys as $e) {
                $r = $g->firstWhere('engine', $e);
                $cells[$e] = $r ? ['mentioned' => $r['mentioned'], 'rank' => $r['rank'], 'cited' => $r['cited'], 'sentiment' => $r['sentiment'],
                    'rivals' => collect($r['brands'])->where('client', false)->whereNotNull('rank')->sortBy('rank')->pluck('name')->all()] : null;
            }
            // Gaps and ordering follow the same basis as the headline numbers.
            $counted = collect($cells)->filter(fn ($c, $e) => $c && ($basis === 'simulated' || ! AiEngineClient::simulated($e)));
            $hits = $counted->filter(fn ($c) => $c['mentioned'])->count();
            $rivals = $counted->pluck('rivals')->flatten()->unique()->values()->all();

            return ['id' => $pid, 'prompt' => $prompts[$pid]['prompt'] ?? '—', 'topic' => $prompts[$pid]['topic'] ?? null, 'volume' => $prompts[$pid]['volume'] ?? null,
                'country' => $prompts[$pid]['country'] ?? null, 'cells' => $cells, 'hits' => $hits, 'rivals' => $rivals, 'gap' => $hits === 0 && $rivals];
        })->sortBy([['hits', 'desc'], ['volume', 'desc']])->values()->all();
        $gaps = collect($rows)->where('gap', true)->sortByDesc('volume')->values()->all();

        $sent = $cur->where('mentioned', true)->pluck('sentiment')->filter()->countBy();

        return [
            'setup' => $base['setup'],
            'available' => true,
            'week' => $curWeek,
            'weekLabel' => 'Week of '.Carbon::parse($curWeek)->format('j M Y'),
            'lastRun' => Carbon::parse($cur->max('run_on'))->format('j M Y'),
            'answers' => $answers,
            'basis' => $basis,
            'simulatedLabels' => $curAll->pluck('engine')->unique()->filter(fn ($e) => AiEngineClient::simulated($e))->map(fn ($e) => AiEngineClient::label($e))->values()->all(),
            'promptCount' => $cur->pluck('prompt')->unique()->count(),
            'engineKeys' => $engineKeys,
            'engineLabels' => collect($engineKeys)->mapWithKeys(fn ($e) => [$e => AiEngineClient::label($e)])->all(),
            'kpis' => $kpis + ['sov' => $client['sov'] ?? null],
            'deltas' => ['visibility' => $delta('visibility'), 'citation' => $delta('citation'), 'rank' => $delta('rank')],
            'prevLabel' => $prev ? Carbon::parse($weeks[count($weeks) - 2])->format('j M') : null,
            'engines' => $engines,
            'brands' => $brands->all(),
            'sovPie' => $sovPie,
            'sources' => $sources,
            'trend' => $byWeek->take(-8)->map(fn ($w, $wk) => ['label' => Carbon::parse($wk)->format('j M'), 'v' => (int) round(self::kpis($w)['visibility'] ?? 0), 'cur' => $wk === $curWeek])->values()->all(),
            'rows' => $rows,
            'gaps' => $gaps,
            'sentiment' => $sent->sum() ? ['positive' => $sent['positive'] ?? 0, 'neutral' => $sent['neutral'] ?? 0, 'negative' => $sent['negative'] ?? 0] : null,
        ];
    }

    /** Runs grouped by ISO week (Monday), keeping the latest answer per prompt and engine in each week. */
    private static function byWeek(array $runs): Collection
    {
        return collect($runs)->groupBy(fn ($r) => Carbon::parse($r['run_on'])->startOfWeek()->toDateString())
            ->map(fn (Collection $w) => $w->sortBy('run_on')->keyBy(fn ($r) => $r['prompt'].'|'.$r['engine'])->values())
            ->sortKeys();
    }

    /** visibility %, citation rate %, average rank when named, answers. */
    private static function kpis(Collection $runs): array
    {
        $n = $runs->count();
        $named = $runs->where('mentioned', true);

        return [
            'answers' => $n,
            'visibility' => $n ? $named->count() / $n * 100 : null,
            'citation' => $n ? $runs->where('cited', true)->count() / $n * 100 : null,
            'rank' => $named->whereNotNull('rank')->count() ? $named->whereNotNull('rank')->avg('rank') : null,
        ];
    }
}
