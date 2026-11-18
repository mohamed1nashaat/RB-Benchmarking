<?php

namespace App\Services;

use App\Models\AiPromptRun;
use App\Models\AiTrackingProfile;
use App\Models\AiVisibilityMonth;
use App\Models\AiVisibilitySnapshot;
use App\Models\Competitor;
use App\Models\SemrushReport;
use App\Services\AiTracking\AiEngineClient;
use App\Services\AiTracking\AnswerAnalyzer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data for the AI Visibility report (Part 1: visibility, market and competitors, AI traffic).
 *
 * Sources, all read from the database:
 *  - Semrush AI Visibility exports (semrush_reports): brand summaries for share of voice, and per-answer
 *    prompt rows (model, country, topic, mentioned / cited) for everything else in the visibility sections.
 *  - Weekly prompt tracker answers (ai_prompt_runs): sentiment and third-party cited domains.
 *  - GA4 live sync (ai_visibility_months + report_tables): AI sessions, revenue, channels, assistants, new users.
 *  - Microsoft Clarity workbook (report_tables.clarity): cited pages, AI referrals and on-site behaviour.
 */
class AiVisibilityPartOne
{
    public const ACCENT = '#D99A1E';

    public const SERIES = ['#3B6EA8', '#E07B53', '#5BA37A', '#9A6FC0', '#C25A7C'];

    public const LLM_LABELS = [
        'google_ai_overview' => 'Google AI Overviews', 'google_ai_mode' => 'Google AI Mode', 'gemini' => 'Gemini',
        'chatgpt' => 'ChatGPT', 'perplexity' => 'Perplexity', 'claude' => 'Claude', 'copilot' => 'Copilot',
    ];

    private const ASSISTANTS = ['ChatGPT', 'Gemini', 'Perplexity', 'Claude', 'Copilot', 'DeepSeek'];

    public static function forTenant(int $tenantId, string $clientName, ?string $month = null): array
    {
        $profile = AiTrackingProfile::where('tenant_id', $tenantId)->first();
        $competitors = Competitor::where('tenant_id', $tenantId)->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn ($c) => ['name' => $c->name, 'domain' => $c->website ? self::host($c->website) : null, 'aliases' => $c->aliases ?? []])->all();
        $snapshot = AiVisibilitySnapshot::where('tenant_id', $tenantId)->whereNotNull('report_tables')->latest('report_month')->first();
        $semrush = SemrushReport::where('tenant_id', $tenantId)->get(['domain', 'is_client', 'metrics', 'topics', 'prompts', 'report_month', 'captured_at'])
            ->map(fn ($r) => ['domain' => $r->domain, 'is_client' => (bool) $r->is_client, 'metrics' => $r->metrics ?? [], 'topics' => $r->topics ?? [],
                'prompts' => $r->prompts ?? [], 'report_month' => $r->report_month, 'captured_at' => substr((string) $r->captured_at, 0, 10)])->all();
        $months = AiVisibilityMonth::where('tenant_id', $tenantId)->orderBy('month')->get()->keyBy('month')->map(fn ($m) => $m->toArray())->all();
        $runs = AiPromptRun::where('ai_prompt_runs.tenant_id', $tenantId)->where('status', 'ok')
            ->join('ai_tracked_prompts', 'ai_tracked_prompts.id', '=', 'ai_prompt_runs.ai_tracked_prompt_id')
            ->get(['ai_tracked_prompts.prompt', 'ai_prompt_runs.engine', 'ai_prompt_runs.run_on', 'ai_prompt_runs.brand_mentioned', 'ai_prompt_runs.brand_cited', 'ai_prompt_runs.sentiment', 'ai_prompt_runs.sources'])
            ->map(fn ($r) => ['prompt' => $r->prompt, 'engine' => $r->engine, 'run_on' => substr((string) $r->run_on, 0, 10), 'mentioned' => (bool) $r->brand_mentioned,
                'cited' => (bool) $r->brand_cited, 'sentiment' => $r->sentiment, 'sources' => $r->sources ?? []])->all();
        $tables = $snapshot?->report_tables ?? [];
        $siteHost = collect($snapshot?->top_ai_pages ?? [])->pluck('url')->map(fn ($u) => parse_url((string) $u, PHP_URL_HOST))->filter()->first();

        return self::build([
            'client' => ['name' => $clientName, 'domain' => $profile?->domain, 'aliases' => $profile?->aliases ?? []],
            'competitors' => $competitors,
            'semrush' => $semrush,
            'months' => $months,
            'tables' => $tables,
            'runs' => $runs,
            'siteHost' => $siteHost ?: $profile?->domain,
            'trackerEngines' => AiEngineClient::available(),
            'month' => $month,
            'today' => Carbon::today()->toDateString(),
        ]);
    }

    public static function build(array $in): array
    {
        $months = collect($in['months'] ?? [])->filter(fn ($m) => ($m['ai_sessions'] ?? null) !== null || ($m['total_sessions'] ?? null) !== null)->sortKeys();
        $today = Carbon::parse($in['today'] ?? 'today');
        $choices = $months->keys()->values();
        // Default to the latest complete month: the current month is still filling.
        $complete = $choices->filter(fn ($ym) => $ym < $today->format('Y-m'));
        $ym = $in['month'] && $choices->contains($in['month']) ? $in['month'] : ($complete->last() ?? $choices->last() ?? $today->copy()->subMonth()->format('Y-m'));

        $brands = self::brands($in);
        $sem = self::semrushFor($in['semrush'] ?? [], $ym, $brands);
        $nonBranded = fn (string $prompt) => ! self::isBranded($prompt, $brands);

        $out = [
            'month' => $ym,
            'monthLabel' => Carbon::parse($ym.'-01')->format('F Y'),
            'shortMonth' => Carbon::parse($ym.'-01')->format('M Y'),
            'months' => $choices->reverse()->mapWithKeys(fn ($m) => [$m => Carbon::parse($m.'-01')->format('F Y')])->all(),
            'client' => $brands[0]['name'],
            'brands' => $brands,
        ];

        $out['visibility'] = self::visibility($sem, $brands, $nonBranded);
        $out['tracker'] = self::tracker($in['runs'] ?? [], $ym, $brands, $nonBranded);
        $out['traffic'] = self::traffic($months->get($ym) ?? [], $in['tables'] ?? [], $ym, $in['siteHost'] ?? null);
        $out['coverage'] = self::coverage($out, $in['trackerEngines'] ?? []);

        return $out;
    }

    /** Client first, then competitors, each with a fixed colour and its Semrush key. */
    private static function brands(array $in): array
    {
        $list = [['name' => $in['client']['name'] ?? 'Client', 'domain' => self::host($in['client']['domain'] ?? null), 'aliases' => $in['client']['aliases'] ?? [], 'client' => true]];
        foreach ($in['competitors'] ?? [] as $c) {
            $list[] = ['name' => $c['name'], 'domain' => self::host($c['domain'] ?? null), 'aliases' => $c['aliases'] ?? [], 'client' => false];
        }
        foreach ($list as $i => &$b) {
            $b['key'] = 'b'.$i;
            $b['color'] = $b['client'] ? self::ACCENT : self::SERIES[($i - 1) % count(self::SERIES)];
        }

        return $list;
    }

    /** The Semrush capture that covers this month: the latest one taken by the end of the following month. */
    private static function semrushFor(array $reports, string $ym, array $brands): array
    {
        $limit = Carbon::parse($ym.'-01')->addMonth()->format('Y-m');
        $month = collect($reports)->pluck('report_month')->filter(fn ($m) => $m <= $limit)->max();
        if (! $month) {
            return ['available' => false, 'byBrand' => []];
        }
        $rows = collect($reports)->where('report_month', $month);
        $byBrand = [];
        foreach ($brands as $b) {
            $r = $b['client'] ? ($rows->firstWhere('is_client', true) ?? null) : null;
            $r ??= $b['domain'] ? $rows->first(fn ($x) => ! $x['is_client'] && AnswerAnalyzer::sameSite((string) $x['domain'], $b['domain'])) : null;
            if ($r) {
                $byBrand[$b['key']] = $r;
            }
        }
        $client = $byBrand['b0'] ?? null;

        return [
            'available' => (bool) $client,
            'month' => $month,
            'captured' => $client['captured_at'] ?? $rows->max('captured_at'),
            'byBrand' => $byBrand,
        ];
    }

    /** Overview, market, topics and competitor share of voice from the Semrush exports. */
    private static function visibility(array $sem, array $brands, callable $nonBranded): array
    {
        if (! $sem['available']) {
            return ['available' => false];
        }
        $client = $sem['byBrand']['b0'];
        $rows = fn (string $key) => collect($sem['byBrand'][$key]['prompts'] ?? [])->map(fn ($p) => [
            'prompt' => (string) ($p['prompt'] ?? ''), 'llm' => (string) ($p['llm'] ?? ''), 'country' => strtolower((string) ($p['country'] ?? '')),
            'topic' => trim((string) ($p['topic_name'] ?? '')), 'm' => str_contains((string) ($p['categories'] ?? ''), 'mentions_target'),
            'c' => str_contains((string) ($p['categories'] ?? ''), 'cites_target'),
        ]);
        $mine = $rows('b0');

        // Volume: estimated impressions = Σ topic volume × share of answers naming the brand.
        $metrics = $client['metrics'] ?? [];
        $imp = collect($client['topics'] ?? [])->sum(fn ($t) => (float) ($t['volume'] ?? 0) * (float) ($t['visibility'] ?? 0) / 100);
        $volume = [
            'imp' => $client['topics'] ? (int) round($imp) : null,
            'men' => $mine->isNotEmpty() ? $mine->where('m', true)->count() : ($metrics['mentions'] ?? null),
            'cit' => $mine->isNotEmpty() ? $mine->where('c', true)->count() : ($metrics['citations'] ?? null),
        ];

        // Share of voice: brand summaries (mentions + citations), every tracked brand with a Semrush summary.
        $sv = collect($brands)->filter(fn ($b) => isset($sem['byBrand'][$b['key']]['metrics']['mentions']))
            ->map(fn ($b) => ['key' => $b['key'], 'mv' => (int) $sem['byBrand'][$b['key']]['metrics']['mentions'], 'cv' => (int) ($sem['byBrand'][$b['key']]['metrics']['citations'] ?? 0)]);
        $tm = max(1, $sv->sum('mv'));
        $tc = max(1, $sv->sum('cv'));
        $ta = max(1, $sv->sum(fn ($r) => $r['mv'] + $r['cv']));
        $sov = $sv->map(fn ($r) => ['key' => $r['key'], 'sov' => ($r['mv'] + $r['cv']) / $ta, 'm' => $r['mv'] / $tm, 'c' => $r['cv'] / $tc])->sortByDesc('sov')->values()->all();

        // Overview breakdowns for the client (all prompts).
        $topics = $mine->groupBy('topic')->map(fn ($g, $t) => [$t, $g->where('m', true)->count() + $g->where('c', true)->count()])
            ->filter(fn ($r) => $r[0] !== '' && $r[1] > 0)->sortByDesc(1)->take(5)->values()->all();
        $menTotal = max(1, $mine->where('m', true)->count());
        $citTotal = max(1, $mine->where('c', true)->count());
        $countries = $mine->groupBy('country')->map(fn ($g, $cc) => [
            $cc, self::countryName($cc), $g->where('m', true)->count(), $g->where('m', true)->count() / $menTotal,
            $g->where('c', true)->count(), $g->where('c', true)->count() / $citTotal,
        ])->filter(fn ($r) => $r[0] !== '')->sortByDesc(2)->take(5)->values()->all();
        $llm = $mine->groupBy('llm')->map(fn ($g, $l) => [self::llmLabel($l), $g->where('m', true)->count() / $menTotal, $g->where('c', true)->count() / $citTotal, $g->where('m', true)->count()])
            ->sortByDesc(3)->values()->map(fn ($r) => array_slice($r, 0, 3))->all();

        // Market: non-branded answers, brands that have a prompt export.
        $withPrompts = collect($brands)->filter(fn ($b) => ! empty($sem['byBrand'][$b['key']]['prompts']))->values();
        $missing = collect($brands)->filter(fn ($b) => empty($sem['byBrand'][$b['key']]['prompts']))->pluck('name')->values()->all();
        $nb = $withPrompts->mapWithKeys(fn ($b) => [$b['key'] => $rows($b['key'])->filter(fn ($r) => $nonBranded($r['prompt'].' | '.$r['topic']))->values()]);
        $sumBy = fn (string $f) => $nb->map(fn ($r) => $r->where($f, true)->count());
        $mAll = $sumBy('m');
        $cAll = $sumBy('c');
        $share = fn (Collection $v) => $v->map(fn ($n) => $n / max(1, $v->sum()))->sortDesc()->map(fn ($s, $k) => [$k, $s])->values()->all();

        $topCountries = $nb->flatMap(fn ($r) => $r->where('m', true)->pluck('country'))->filter()->countBy()->sortDesc()->take(5)->keys()->values();
        $cb = fn (string $f) => $nb->map(fn ($r) => $topCountries->map(fn ($cc) => $r->where($f, true)->where('country', $cc)->count())->all())->all();

        $heat = function (string $f) use ($nb, $brands) {
            $topicTotals = $nb->flatMap(fn ($r) => $r->where($f, true)->pluck('topic'))->filter()->countBy()->sortDesc()->take(7);

            return $topicTotals->keys()->map(function ($t) use ($nb, $f, $brands) {
                $counts = $nb->map(fn ($r) => $r->where($f, true)->where('topic', $t)->count());
                $sum = max(1, $counts->sum());

                return [$t, collect($brands)->map(fn ($b) => $counts->has($b['key']) ? $counts[$b['key']] / $sum : null)->all()];
            })->values()->all();
        };

        $models = $nb->flatMap(fn ($r) => $r->pluck('llm'))->filter()->countBy()->sortDesc()->keys()->values();
        $sovLlm = $models->mapWithKeys(function ($l) use ($nb) {
            $v = $nb->map(fn ($r) => $r->where('llm', $l)->filter(fn ($x) => $x['m'])->count() + $r->where('llm', $l)->filter(fn ($x) => $x['c'])->count());

            return [self::llmLabel($l) => $v->map(fn ($n) => $n / max(1, $v->sum()))->all()];
        })->all();

        return [
            'available' => true,
            'captured' => $sem['captured'] ? Carbon::parse($sem['captured'])->format('j M Y') : $sem['month'],
            'answers' => $mine->count(),
            'prompts' => $mine->pluck('prompt')->unique()->count(),
            'nonBrandedPrompts' => $mine->filter(fn ($r) => $nonBranded($r['prompt'].' | '.$r['topic']))->pluck('prompt')->unique()->count(),
            'models' => $mine->pluck('llm')->filter()->unique()->count(),
            'volume' => $volume,
            'sov' => $sov,
            'sovMissing' => collect($brands)->filter(fn ($b) => ! isset($sem['byBrand'][$b['key']]['metrics']['mentions']))->pluck('name')->values()->all(),
            'topics' => $topics,
            'countries' => $countries,
            'llm' => $llm,
            'market' => [
                'brands' => $withPrompts->pluck('key')->all(),
                'missing' => $missing,
                'bm' => $share($mAll),
                'bc' => $share($cAll),
                'countries' => $topCountries->map(fn ($cc) => [$cc, self::countryName($cc)])->all(),
                'cbm' => $cb('m'),
                'cbc' => $cb('c'),
            ],
            'heatM' => $heat('m'),
            'heatC' => $heat('c'),
            'sovLlm' => $sovLlm,
        ];
    }

    /** Sentiment and third-party sources from the weekly prompt tracker (real AI answers). */
    private static function tracker(array $runs, string $ym, array $brands, callable $nonBranded): array
    {
        $runs = collect($runs)->filter(fn ($r) => str_starts_with($r['run_on'], $ym));
        $named = $runs->where('mentioned', true)->filter(fn ($r) => $r['sentiment']);
        $own = collect($brands)->pluck('domain')->filter()->all();
        $domains = $runs->filter(fn ($r) => $nonBranded($r['prompt']))
            ->flatMap(fn ($r) => collect($r['sources'])->pluck('domain')->filter()->map(fn ($d) => preg_replace('/^www\./', '', strtolower($d)))->unique())
            ->reject(fn ($d) => collect($own)->contains(fn ($o) => AnswerAnalyzer::sameSite($d, $o)))
            ->countBy()->sortDesc();
        $dTotal = max(1, $domains->sum());

        return [
            'answers' => $runs->count(),
            'sentiment' => $named->isNotEmpty() ? ['pos' => $named->where('sentiment', 'positive')->count(), 'men' => $named->count(), 's' => $named->where('sentiment', 'positive')->count() / $named->count()] : null,
            'domains' => $domains->take(10)->map(fn ($n, $d) => [$d, $n / $dTotal])->values()->all(),
        ];
    }

    /** GA4 and Clarity numbers for the month. */
    private static function traffic(array $m, array $tables, string $ym, ?string $siteHost): array
    {
        $clarity = $tables['clarity'] ?? [];
        $clarityMonth = self::captureMonth($tables['captions']['Clarity_AI'] ?? null);
        $clarityOn = $clarity && $clarityMonth === $ym;
        $sessions = $m['ai_sessions'] ?? null;
        $total = $m['total_sessions'] ?? null;
        $aiRate = $sessions ? ($m['ai_key_events'] ?? 0) / $sessions : null;
        $siteRate = $total && ($m['total_key_events'] ?? null) !== null ? $m['total_key_events'] / $total : null;

        $assistants = collect($tables['ai_sources_monthly'][$ym] ?? [])->sortDesc();
        $silent = collect(self::ASSISTANTS)->reject(fn ($a) => ($assistants[$a] ?? 0) > 0)->values()->all();

        // Channels: GA4 default channel groups, grouped the way the report compares them.
        $groups = [];
        foreach ($tables['channels_monthly'][$ym] ?? [] as $ch => [$s, $k, $r]) {
            $g = match ($ch) {
                'Paid Search' => 'Google Ads',
                'Organic Search' => 'Google organic',
                'Direct' => 'Direct',
                'AI Assistant' => 'AI assistants',
                'Paid Social', 'Paid Other', 'Paid Video', 'Paid Shopping', 'Display', 'Cross-network', 'Audio' => 'Paid social and other paid',
                default => 'Everything else',
            };
            $groups[$g] ??= [0, 0, 0.0];
            $groups[$g] = [$groups[$g][0] + $s, $groups[$g][1] + $k, $groups[$g][2] + $r];
        }
        if ($groups) {
            $groups['Whole site'] = [array_sum(array_column($groups, 0)), array_sum(array_column($groups, 1)), array_sum(array_column($groups, 2))];
        }
        $per = fn (int $i) => collect($groups)->filter(fn ($g) => $g[0] > 0)->map(fn ($g) => $g[$i] / $g[0])->sortDesc()->map(fn ($v, $l) => [$l, $v])->values()->all();
        $kev = $per(1);
        $ai = collect($kev)->firstWhere(0, 'AI assistants');
        $site = collect($kev)->firstWhere(0, 'Whole site');

        $beh = $clarity['behaviour'] ?? [];
        $pages = $clarityOn ? collect($clarity['pages'] ?? [])->sortByDesc('citations') : collect();
        $pageTotal = (int) ($clarity['summary']['Citations'] ?? $pages->sum('citations'));
        $host = $siteHost ? preg_replace('/^www\./', '', strtolower($siteHost)) : '';

        return [
            'available' => $sessions !== null,
            'clarity' => $clarityOn,
            'clarityLabel' => $clarityMonth ? Carbon::parse($clarityMonth.'-01')->format('M Y') : null,
            'sessions' => $sessions,
            'share' => $sessions !== null && $total ? $sessions / $total : null,
            'aiRate' => $aiRate,
            'siteRate' => $siteRate,
            'lift' => $aiRate !== null && $siteRate ? $aiRate / $siteRate : null,
            'revenue' => $m['ai_revenue'] ?? null,
            'keyEvents' => $m['ai_key_events'] ?? null,
            'newUsers' => $tables['ai_new_users_monthly'][$ym] ?? null,
            'referrals' => $clarityOn ? ($clarity['summary']['AI-referred sessions (Clarity)'] ?? null) : null,
            'assistants' => $assistants->map(fn ($n, $a) => [$a, (int) $n])->values()->all(),
            'silent' => $silent,
            'behaviour' => $clarityOn && $beh ? [
                'sessions' => $beh['Sessions']['ai'] ?? null, 'scroll' => $beh['Scroll depth']['ai'] ?? null,
                'dead' => $beh['Dead clicks']['ai'] ?? null, 'quick' => $beh['Quick backs']['ai'] ?? null,
                'site' => ['sessions' => $beh['Sessions']['site'] ?? null, 'scroll' => $beh['Scroll depth']['site'] ?? null, 'dead' => $beh['Dead clicks']['site'] ?? null, 'quick' => $beh['Quick backs']['site'] ?? null],
            ] : null,
            'kev' => $kev,
            'rps' => $per(2),
            'aiLeads' => $ai && $site ? $ai[1] > $site[1] : null,
            'pages' => $pages->take(5)->map(fn ($p) => [$host.$p['page'], (int) $p['citations'], $pageTotal ? $p['citations'] / $pageTotal : 0])->values()->all(),
        ];
    }

    /** "What each section runs on today" rows, from what is actually loaded. */
    private static function coverage(array $d, array $engines): array
    {
        $v = $d['visibility'];
        $t = $d['traffic'];
        $tr = $d['tracker'];
        $semrush = $v['available'] ? 'Semrush capture '.$v['captured'] : null;
        $trackerTag = ['Weekly prompt tracker'.($engines ? ': '.implode(', ', array_map(fn ($e) => AiEngineClient::label($e), $engines)) : ''), $tr['answers'] > 0];
        $stack = fn (array $tags) => array_map(fn ($x) => is_array($x) ? $x : [$x, true], $tags);
        $missing = $v['available'] ? $v['market']['missing'] : [];

        return [
            ['Overview', 'Volume, share of voice, sentiment, topics, countries, LLMs',
                $v['available'] ? ($tr['sentiment'] ? 'ok' : 'part') : 'no',
                $v['available'] ? 'Real · '.$semrush : 'No Semrush capture',
                $v['available'] ? ($tr['sentiment'] ? 'Sentiment from the weekly prompt tracker.' : 'Sentiment waits for answers from the weekly prompt tracker.') : 'Upload the Semrush brand topics and prompts exports in Data & settings.',
                $stack(['Semrush AI Visibility (CSV upload)', 'MySQL', $trackerTag])],
            ['Market and competitors', 'Brand shares, top countries',
                ! $v['available'] ? 'no' : ($missing ? 'part' : 'ok'),
                $v['available'] ? 'Real · '.$semrush : 'No Semrush capture',
                $missing ? 'No prompt export yet for '.self::list($missing).'. Counts non-branded prompts only.' : 'Counts non-branded prompts only.',
                $stack(['Semrush prompts export per brand', 'MySQL'])],
            ['Topics', 'Topic by brand tables',
                ! $v['available'] ? 'no' : ($missing ? 'part' : 'ok'),
                $v['available'] ? 'Real · '.$semrush : 'No Semrush capture',
                'Uses the Semrush topic names: the 7 topics with the most non-branded mentions or citations.',
                $stack(['Semrush topics', 'MySQL'])],
            ['Competitors', 'Sentiment, share of voice by LLM, pages, domains',
                ! $v['available'] ? 'no' : ($tr['sentiment'] && $tr['domains'] && $t['pages'] ? 'ok' : 'part'),
                $v['available'] ? 'Real · '.$semrush : 'No Semrush capture',
                trim(($tr['answers'] ? '' : 'Competitor sentiment and third-party domains need answers from the weekly prompt tracker. ').($t['pages'] ? 'Cited pages come from Clarity (Copilot).' : 'Cited pages need the Clarity export for this month.')),
                $stack(['Semrush prompts export per brand', 'Microsoft Clarity (workbook upload)', 'MySQL', $trackerTag])],
            ['AI traffic', 'GA4 sessions, conversion, revenue, new users',
                $t['available'] ? 'ok' : 'no',
                $t['available'] ? 'Real · '.$d['monthLabel'] : 'No GA4 data for this month',
                'GA4 live sync. AI sessions are a floor.',
                $stack(['GA4 Data API (live sync)', 'MySQL'])],
            ['Behaviour', 'Clarity sessions, scroll, dead clicks, quick backs',
                $t['behaviour'] ? 'ok' : 'no',
                $t['behaviour'] ? 'Real · AI visitors' : 'Not captured for this month',
                $t['behaviour'] ? 'Clarity export filtered to AI referrers ('.$t['clarityLabel'].').' : 'Upload the Clarity export for this month'.($t['clarityLabel'] ? ' (latest is '.$t['clarityLabel'].')' : '').'.',
                $stack(['Microsoft Clarity (workbook upload)', 'MySQL'])],
            ['Trend over time', '', 'no', 'Not built yet', 'Specified later.', $stack([['Monthly snapshots in MySQL', false]])],
        ];
    }

    public static function isBranded(string $prompt, array $brands): bool
    {
        foreach ($brands as $b) {
            foreach (AnswerAnalyzer::spellings($b) as $s) {
                $pattern = preg_match('/^[\p{Latin}0-9 .\-&\']+$/u', $s)
                    ? '/(?<![\p{L}\p{N}])'.preg_quote($s, '/').'(?![\p{L}\p{N}])/iu'
                    : '/'.preg_quote($s, '/').'/u';
                if (preg_match($pattern, $prompt)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function captureMonth(?string $caption): ?string
    {
        if ($caption && preg_match('/(\d{1,2})\s+([A-Za-z]{3})[a-z]*\s+(\d{4})/', $caption, $m)) {
            try {
                return Carbon::parse("{$m[1]} {$m[2]} {$m[3]}")->format('Y-m');
            } catch (\Throwable) {
            }
        }

        return null;
    }

    public static function llmLabel(string $key): string
    {
        return self::LLM_LABELS[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    public static function countryName(string $cc): string
    {
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.strtoupper($cc), 'en') : '';

        return $name && strtoupper($name) !== strtoupper($cc) ? $name : strtoupper($cc);
    }

    private static function host(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $h = parse_url(str_contains($url, '://') ? $url : 'https://'.$url, PHP_URL_HOST);

        return $h ? preg_replace('/^www\./', '', strtolower($h)) : null;
    }

    private static function list(array $names): string
    {
        return count($names) > 1 ? implode(', ', array_slice($names, 0, -1)).' and '.end($names) : implode('', $names);
    }
}
