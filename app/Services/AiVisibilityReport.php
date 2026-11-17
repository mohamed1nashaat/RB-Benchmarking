<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Builds the AI Visibility report (Overview + Diagnostics) from stored data.
 *
 * Inputs are the monthly rows (GSC + GA4, live-synced or imported), the latest
 * workbook snapshot (Clarity, Semrush, daily GSC, GA4 channel tables) and the
 * client's Semrush export. Every section says whether it is observed (platform
 * logs) or simulated (Semrush prompt runs), and returns null rather than
 * inventing a number when its source is missing for the selected month.
 */
class AiVisibilityReport
{
    private const PLATFORM_NAMES = ['Google AI Overviews', 'Google AI Mode', 'Gemini', 'ChatGPT', 'Perplexity', 'Claude', 'Copilot', 'Grok', 'Meta AI', 'DeepSeek'];

    private array $months;

    private array $tables;

    private array $snapshot;

    private array $semrush;

    private string $brand;

    private string $currentMonth;

    private ?array $clarityCache = null;

    /** Semrush brand summaries by month: ['YYYY-MM' => [['name', 'domain', 'is_client', 'metrics'], …]]. */
    private array $brands = [];

    public function __construct(array $months, ?array $snapshot, array $semrushClient = [], string $brand = 'the brand', ?string $currentMonth = null)
    {
        $this->months = collect($months)
            ->filter(fn ($m) => isset($m['month']) && (($m['ai_impressions'] ?? null) !== null || ($m['ai_sessions'] ?? null) !== null))
            ->keyBy('month')->sortKeys()->all();
        $this->snapshot = $snapshot ?? [];
        $this->tables = $this->snapshot['report_tables'] ?? [];
        $this->semrush = $semrushClient;
        $this->brand = $brand;
        $this->currentMonth = $currentMonth ?? now()->format('Y-m');
    }

    /** Adds the client and competitor Semrush summaries for the Overview competitor section. */
    public function withCompetitors(array $brandsByMonth): static
    {
        $this->brands = array_filter($brandsByMonth, fn ($rows) => collect($rows)->contains('is_client', true));
        ksort($this->brands);

        return $this;
    }

    // ── Public entry point ───────────────────────────────────────────────

    public function build(?string $selected = null): array
    {
        $keys = array_keys($this->months);
        if (! $keys) {
            return ['empty' => true, 'months' => [], 'help' => self::help()];
        }

        $sel = in_array($selected, $keys, true) ? $selected : $this->defaultMonth();
        $i = array_search($sel, $keys, true);
        $prev = $i > 0 ? $keys[$i - 1] : null;

        $ctx = [
            'sel' => $sel,
            'prev' => $prev,
            'partial' => $this->isPartial($sel),
            'days' => $this->daysElapsed($sel),
            'ga_days' => $this->gaDaysElapsed($sel),
            'label' => $this->monthLabel($sel, true),
            'short' => Carbon::parse($sel.'-01')->format('M'),
            'prev_short' => $prev ? Carbon::parse($prev.'-01')->format('M') : null,
            'prev_label' => $prev ? $this->monthLabel($prev, false) : null,
            'captured' => $sel === $this->clarityMonth(),
            'semrush_captured' => $sel === $this->semrushMonth(),
        ];

        return [
            'empty' => false,
            'months' => collect($keys)->mapWithKeys(fn ($k) => [$k => $this->monthLabel($k, false).($this->isPartial($k) ? ' (to date)' : '')])->reverse()->all(),
            'ctx' => $ctx,
            'fresh' => $this->freshness(),
            'commentary' => $this->commentary($ctx),
            'hero' => $this->hero($ctx),
            'tiles' => $this->tiles($ctx),
            'landscape' => $this->landscape($ctx),
            'funnel' => $this->funnel($ctx),
            'traffic' => $this->trafficQuality($ctx),
            'grounding' => $this->grounding($ctx),
            'platformCitations' => $this->platformCitations($ctx),
            'friction' => $this->friction($ctx),
            'competitive' => $this->competitive($ctx),
            'competitors' => $this->competitors($ctx),
            'help' => self::help(),
        ];
    }

    // ── Month helpers ────────────────────────────────────────────────────

    private function defaultMonth(): string
    {
        $keys = array_keys($this->months);
        $complete = array_values(array_filter($keys, fn ($k) => ! $this->isPartial($k)));
        $capture = $this->clarityMonth();
        if ($capture && in_array($capture, $keys, true)) {
            return $capture;
        }

        return end($complete) ?: end($keys);
    }

    private function isPartial(string $ym): bool
    {
        return $ym >= $this->currentMonth;
    }

    private function daysElapsed(string $ym): int
    {
        $dim = Carbon::parse($ym.'-01')->daysInMonth;
        if (! $this->isPartial($ym)) {
            return $dim;
        }
        $daily = count($this->dailyFor($ym));
        if ($daily > 0) {
            return $daily;
        }

        return max(1, min($dim, (int) now()->format('j') - 1));
    }

    /** Days of a month in progress covered by GA4: up to the live-sync date when there is one. */
    private function gaDaysElapsed(string $ym): int
    {
        $to = $this->tables['live_sync']['to'] ?? null;
        if ($this->isPartial($ym) && $to && str_starts_with($to, $ym)) {
            return max(1, (int) substr($to, 8, 2));
        }

        return $this->daysElapsed($ym);
    }

    private function monthLabel(string $ym, bool $withDays): string
    {
        $label = Carbon::parse($ym.'-01')->format('F Y');
        if ($withDays && $this->isPartial($ym)) {
            $label .= ' (to date)';
        }

        return $label;
    }

    private function month(?string $ym): array
    {
        return $ym ? ($this->months[$ym] ?? []) : [];
    }

    private function dailyFor(string $ym): array
    {
        return array_values(array_filter($this->tables['gsc_daily'] ?? [], fn ($d) => str_starts_with($d[0], $ym) && $d[3] !== null));
    }

    private function captureMonthFrom(?string $caption): ?string
    {
        if ($caption && preg_match('/(\d{1,2})\s+([A-Za-z]{3})[a-z]*\s+(\d{4})/', $caption, $m)) {
            try {
                return Carbon::parse("{$m[1]} {$m[2]} {$m[3]}")->format('Y-m');
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function captureDayFrom(?string $caption, string $pattern = '/(\d{1,2}\s+[A-Za-z]{3})/'): ?string
    {
        return ($caption && preg_match($pattern, $caption, $m)) ? $m[1] : null;
    }

    private function clarityMonth(): ?string
    {
        if (! $this->clarity()['total']) {
            return null;
        }

        return $this->captureMonthFrom($this->tables['captions']['Clarity_AI'] ?? null) ?? ($this->snapshot['report_month'] ?? null);
    }

    private function semrushMonth(): ?string
    {
        if (! $this->semrushOverview() && empty($this->semrush['prompts'])) {
            return null;
        }

        return $this->captureMonthFrom($this->tables['captions']['Semrush_Overview'] ?? null)
            ?? $this->clarityMonth() ?? ($this->snapshot['report_month'] ?? null);
    }

    private function clarityDay(): ?string
    {
        return $this->captureDayFrom($this->tables['captions']['Clarity_AI'] ?? null);
    }

    private function semrushDay(): ?string
    {
        return $this->captureDayFrom($this->tables['captions']['Semrush_Overview'] ?? null, '/data date\s+(\d{1,2}\s+[A-Za-z]{3})/i')
            ?? $this->captureDayFrom($this->tables['captions']['Semrush_Overview'] ?? null);
    }

    // ── Source helpers ───────────────────────────────────────────────────

    /** Normalised Clarity block, from the structured tables or the legacy flat map. */
    private function clarity(): array
    {
        if ($this->clarityCache !== null) {
            return $this->clarityCache;
        }

        $t = $this->tables['clarity'] ?? [];
        $flat = $this->snapshot['clarity_data'] ?? [];
        $sum = ($t['summary'] ?? []) + $flat;
        $num = fn ($k) => isset($sum[$k]) && is_numeric($sum[$k]) ? $sum[$k] + 0 : null;

        $pages = $t['pages'] ?? collect($flat)->filter(fn ($v, $k) => str_starts_with((string) $k, '/') && is_numeric($v))
            ->map(fn ($v, $k) => ['page' => $k, 'citations' => (int) $v])->sortByDesc('citations')->values()->all();
        $queries = $t['queries'] ?? collect($flat)->filter(fn ($v) => in_array($v, ['Branded', 'Non-branded'], true))
            ->map(fn ($v, $k) => ['query' => (string) $k, 'type' => $v, 'citations' => null, 'share' => null])->values()->all();

        return $this->clarityCache = [
            'total' => $num('Citations') ?? ($this->snapshot['clarity_citations'] ?? null),
            'share' => $num('Share of authority (all citations)'),
            'branded' => $num('Branded citations'),
            'branded_share' => $num('Branded share of authority'),
            'non_branded' => $num('Non-branded citations'),
            'non_branded_share' => $num('Non-branded share of authority'),
            'other_domains' => $num('Citations to other domains'),
            'ai_sessions' => $num('AI-referred sessions (Clarity)'),
            'bot' => $sum['AI bot activity'] ?? null,
            'queries' => $queries,
            'topics' => $t['topics'] ?? [],
            'pages' => $pages,
            'behaviour' => $t['behaviour'] ?? [],
        ];
    }

    private function semrushOverview(): array
    {
        return collect($this->tables['semrush_overview'] ?? [])->keyBy('metric')->all();
    }

    private function semrushMetric(string $metric): ?array
    {
        return $this->semrushOverview()[$metric] ?? null;
    }

    /** Mentions by platform and by country: Semrush overview first, prompt export second. */
    private function semrushMentions(): array
    {
        $platforms = [];
        $countries = [];
        foreach ($this->semrushOverview() as $metric => $row) {
            if (! str_starts_with($metric, 'Mentions: ')) {
                continue;
            }
            $name = substr($metric, 10);
            if (in_array($name, self::PLATFORM_NAMES, true)) {
                $platforms[$name] = (int) $row['value'];
            } else {
                $countries[strcasecmp($name, 'other countries') === 0 ? 'Other' : $name] = (int) $row['value'];
            }
        }
        $total = $this->semrushMetric('AI mentions')['value'] ?? null;
        $source = 'overview';

        if (! $platforms && ! empty($this->semrush['prompts'])) {
            $source = 'prompts';
            $llm = ['chatgpt' => 'ChatGPT', 'gemini' => 'Gemini', 'google_ai_mode' => 'Google AI Mode', 'ai_mode' => 'Google AI Mode', 'google_ai_overview' => 'Google AI Overviews', 'ai_overview' => 'Google AI Overviews', 'perplexity' => 'Perplexity'];
            foreach ($this->semrush['prompts'] as $p) {
                if (! str_contains($p['categories'] ?? '', 'mentions_target')) {
                    continue;
                }
                $name = $llm[strtolower($p['llm'] ?? '')] ?? ucfirst($p['llm'] ?? 'Other');
                $platforms[$name] = ($platforms[$name] ?? 0) + 1;
                $c = strtoupper($p['country'] ?? '') ?: 'Other';
                $countries[$c] = ($countries[$c] ?? 0) + 1;
            }
            $total = array_sum($platforms);
            arsort($countries);
            if (count($countries) > 4) {
                $top = array_slice($countries, 0, 3, true);
                $top['Other'] = array_sum(array_slice($countries, 3));
                $countries = $top;
            }
        }
        arsort($platforms);

        return ['platforms' => $platforms, 'countries' => $countries, 'total' => $total, 'source' => $source];
    }

    private function channels(?string $ym): array
    {
        return $ym ? ($this->tables['channels_monthly'][$ym] ?? []) : [];
    }

    // ── Formatting helpers ───────────────────────────────────────────────

    public static function delta(?float $cur, ?float $prev, string $vs, string $mode = 'pct'): array
    {
        if ($cur === null || $prev === null || ($mode !== 'pts' && (float) $prev === 0.0)) {
            return ['text' => null, 'up' => null, 'vs' => $vs];
        }
        $d = $mode === 'pts' ? $cur - $prev : ($cur - $prev) / $prev * 100;
        $text = ($d >= 0 ? '+' : '−').number_format(abs($d), 1).($mode === 'pts' ? ' pts' : '%');

        return ['text' => $text, 'up' => $d >= 0, 'vs' => $vs];
    }

    private function vsText(array $ctx): string
    {
        if (! $ctx['prev']) {
            return 'No earlier month to compare';
        }

        return $ctx['partial'] ? "vs {$ctx['prev_short']} 1–{$ctx['days']}" : "vs {$ctx['prev_short']}";
    }

    private function paceVs(array $ctx): string
    {
        if (! $ctx['prev']) {
            return 'No earlier month to compare';
        }

        return $ctx['partial'] ? "daily pace vs {$ctx['prev_short']}" : "vs {$ctx['prev_short']}";
    }

    /** Previous month's value scaled to the same number of days (for a month in progress). */
    private function paced(array $ctx, ?float $prevValue): ?float
    {
        if ($prevValue === null || ! $ctx['prev']) {
            return null;
        }
        if (! $ctx['partial']) {
            return $prevValue;
        }

        // GA4 numbers run to the live-sync date, which can differ from the daily AI-impressions export.
        return $prevValue * ($ctx['ga_days'] ?? $ctx['days']) / Carbon::parse($ctx['prev'].'-01')->daysInMonth;
    }

    private function impressionsPrev(array $ctx): ?float
    {
        $prev = $this->month($ctx['prev'])['ai_impressions'] ?? null;
        if ($prev === null || ! $ctx['partial']) {
            return $prev;
        }
        $daily = $this->dailyFor($ctx['prev']);
        if ($daily) {
            return (float) array_sum(array_map(fn ($d) => $d[3], array_slice($daily, 0, $ctx['days'])));
        }

        return $this->paced($ctx, $prev);
    }

    private static function pagePath(string $url): string
    {
        $path = parse_url(str_replace(["\n", "\r"], '', $url), PHP_URL_PATH) ?: '/';

        return rawurldecode($path);
    }

    private static function shortPage(string $path): string
    {
        $short = preg_replace(['#/(a|p|c|page)-?\d+$#', '#/p\d+$#'], '', $path);
        $short = preg_replace('#^/(ar|en)/blog/#', 'blog: ', $short);
        $short = trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $short)));

        // Slug-less URLs (e.g. /en/blog/-/a-123) would collapse to "blog:"; keep the raw path.
        return mb_strlen(trim(str_replace('blog:', '', $short))) < 2 ? $path : $short;
    }

    // ── Sections ─────────────────────────────────────────────────────────

    private function freshness(): array
    {
        $last = array_key_last($this->months);
        $parts = ['Search Console + GA4 to '.Carbon::parse($last.'-01')->format('M Y')];
        if ($d = $this->clarityDay()) {
            $parts[] = 'Clarity '.$d;
        }
        if ($d = $this->semrushDay()) {
            $parts[] = 'Semrush '.$d;
        }
        $live = $this->tables['live_sync']['ga4_at'] ?? null;
        if ($live) {
            $parts[0] = 'GA4 live sync '.Carbon::parse($live)->format('j M');
        }
        $refresh = 'GA4 and Search Console clicks/impressions come from the live Google connection'.($live ? ', last synced '.Carbon::parse($live)->format('j M Y') : '').' (Search Console lags 2–3 days). AI impressions are not in the Search Console API, so they come from the monthly Generative AI features export.';
        if ($d = $this->clarityDay()) {
            $refresh .= " Clarity, including Bing/Copilot citations, was captured {$d}";
        }
        if ($d = $this->semrushDay()) {
            $refresh .= ($this->clarityDay() ? ', ' : ' ')."Semrush {$d}";
        }
        $refresh .= '. Manual layers refresh by the 5th of each month.';

        return ['line' => implode(' · ', $parts), 'refresh' => $refresh];
    }

    private function commentary(array $ctx): array
    {
        $m = $this->month($ctx['sel']);
        $cur = $m['ai_impressions'] ?? null;
        $prev = $this->impressionsPrev($ctx);
        $d = ($cur !== null && $prev) ? ($cur - $prev) / $prev * 100 : null;
        $parts = [];
        if ($cur !== null) {
            $s = 'Google showed store pages in AI Overviews and AI Mode <b>'.number_format($cur).' times</b>';
            if ($d !== null) {
                $s .= ', '.($d >= 0 ? 'up' : 'down').' <b>'.number_format(abs($d), 1).'%</b> on '.($ctx['partial'] ? 'the same days of ' : '').e($ctx['prev_label']);
            }
            if (! empty($m['total_impressions']) && ! $ctx['partial']) {
                $s .= '; that is '.round($cur / $m['total_impressions'] * 100).'% of all the site\'s Google impressions';
            }
            $parts[] = $s.'.';
        }
        if (($m['ai_sessions'] ?? null) !== null) {
            $s = 'AI assistants sent <b>'.number_format($m['ai_sessions']).' sessions</b>, '.number_format($m['ai_key_events'] ?? 0).' key events worth SAR '.number_format($m['ai_revenue'] ?? 0);
            $ch = $this->channels($ctx['sel']);
            $ai = $ch['AI Assistant'] ?? null;
            $org = $ch['Organic Search'] ?? null;
            if ($ai && $org && $ai[0] && $org[0]) {
                $s .= '; AI visitors converted at '.number_format($ai[1] / $ai[0] * 100, 1).'% against '.number_format($org[1] / $org[0] * 100, 1).'% for organic search';
            }
            $parts[] = $s.'.';
        }
        $c = $this->clarity();
        if ($ctx['captured'] && $c['total']) {
            $s = 'Copilot cited the store <b>'.number_format($c['total']).' times</b> in 30 days';
            if ($c['share'] !== null) {
                $s .= ' ('.number_format($c['share'] * 100, 1).'% of all citations)';
            }
            if ($c['branded'] !== null && $c['total']) {
                $s .= ', '.round($c['branded'] / $c['total'] * 100).'% of them on brand searches';
            }
            $parts[] = $s.'.';
            $gaps = $this->competitive($ctx)['gaps'];
            if ($gaps['rows']) {
                $parts[] = 'Watch next month: '.$gaps['zero'].' tracked topics ('.number_format($gaps['volume']).' monthly searches) still return no '.e($this->brand).' mention in AI answers.';
            }
        } elseif ($this->clarityMonth()) {
            $parts[] = 'Copilot citations, Clarity and Semrush figures are captured monthly; the latest capture is '.Carbon::parse($this->clarityMonth().'-01')->format('F Y').'.';
        }

        return [
            'heading' => 'What changed in '.$ctx['label'],
            'html' => implode(' ', $parts),
        ];
    }

    private function hero(array $ctx): array
    {
        $c = $this->clarity();
        $men = $this->semrushMetric('AI mentions');
        $menPrev = ($men && $men['change'] !== null && $men['change'] > -1) ? round($men['value'] / (1 + $men['change'])) : null;
        $sem = $this->semrushMonth();

        $metrics = [
            'imp' => [
                'label' => 'AI impressions', 'type' => 'obs',
                'sub' => 'Times Google showed a store page inside AI Overviews or AI Mode',
                'src' => 'Google Search Console · Generative AI features',
                'value' => $this->month($ctx['sel'])['ai_impressions'] ?? null,
                'delta' => self::delta($this->month($ctx['sel'])['ai_impressions'] ?? null, $this->impressionsPrev($ctx), $this->vsText($ctx)),
            ],
            'cit' => [
                'label' => 'AI citations', 'type' => 'obs',
                'sub' => 'Times Copilot and Bing partner surfaces cited a store page'.($this->semrushCitationTotal() ? '. Semrush: cited in '.number_format($this->semrushCitationTotal()).' answers from ChatGPT, Gemini and Google AI' : ''),
                'src' => 'Bing via Microsoft Clarity · last 30 days',
                'value' => $ctx['captured'] ? $c['total'] : null,
                'delta' => ['text' => null, 'up' => null, 'vs' => 'Captured '.($this->clarityDay() ?? 'monthly').'; the trend starts with the next capture'],
            ],
            'men' => [
                'label' => 'AI mentions', 'type' => 'sim',
                'sub' => implode(', ', array_keys($this->semrushMentions()['platforms'])) ?: 'Semrush tracked assistants',
                'src' => 'Semrush AI Visibility'.($this->semrushDay() ? ' · '.$this->semrushDay() : ''),
                'value' => $ctx['semrush_captured'] ? ($men['value'] ?? $this->semrushMentions()['total']) : null,
                'delta' => self::delta($ctx['semrush_captured'] ? ($men['value'] ?? null) : null, $menPrev, 'vs previous capture'),
            ],
        ];

        // Chart for the impressions view: daily bars with a 7-day average.
        $daily = $this->dailyFor($ctx['sel']);
        $chart = null;
        if ($daily) {
            $vals = array_map(fn ($d) => (int) $d[3], $daily);
            $rows = [];
            foreach ($daily as $k => $d) {
                $window = array_slice($vals, max(0, $k - 6), min(7, $k + 1));
                $rows[] = ['day' => (int) substr($d[0], 8, 2), 'date' => Carbon::parse($d[0])->format('D j M'), 'v' => $vals[$k], 'avg' => array_sum($window) / count($window), 'all' => $d[2]];
            }
            $lastDay = end($daily)[0];
            $chart = ['kind' => 'daily', 'rows' => $rows, 'note' => $lastDay < Carbon::parse($ctx['sel'].'-01')->endOfMonth()->toDateString() && ! $ctx['partial']
                ? 'Daily export runs to '.Carbon::parse($lastDay)->format('j M').'; the tile uses the full-month total from the live Search Console sync.' : null];
        } else {
            $chart = ['kind' => 'monthly', 'rows' => collect($this->months)->filter(fn ($m, $k) => $k <= $ctx['sel'])->take(-6)
                ->map(fn ($m, $k) => ['label' => Carbon::parse($k.'-01')->format('M'), 'v' => (int) ($m['ai_impressions'] ?? 0), 'cur' => $k === $ctx['sel']])->values()->all(),
                'note' => 'No daily export for this month, so the chart shows monthly totals.'];
        }

        $monthly = [
            'cit' => $c['total'] && $this->clarityMonth() && $this->clarityMonth() <= $ctx['sel']
                ? [['label' => Carbon::parse($this->clarityMonth().'-01')->format('M'), 'v' => (int) $c['total'], 'cur' => $ctx['captured']]] : [],
            'men' => array_values(array_filter([
                ($menPrev !== null && $sem) ? ['label' => Carbon::parse($sem.'-01')->subMonthNoOverflow()->format('M'), 'v' => (int) $menPrev, 'cur' => false] : null,
                ($men && $sem && $sem <= $ctx['sel']) ? ['label' => Carbon::parse($sem.'-01')->format('M'), 'v' => (int) $men['value'], 'cur' => $ctx['semrush_captured']] : null,
            ])),
        ];

        return ['metrics' => $metrics, 'chart' => $chart, 'monthly' => $monthly, 'captureMonth' => $this->clarityMonth() ? Carbon::parse($this->clarityMonth().'-01')->format('F Y') : null];
    }

    private function tiles(array $ctx): array
    {
        $c = $this->clarity();
        $m = $this->month($ctx['sel']);
        $p = $this->month($ctx['prev']);

        return [
            'soa' => $ctx['captured'] && $c['non_branded_share'] !== null ? [
                'value' => $c['non_branded_share'] * 100,
                'note' => number_format($c['non_branded'] ?? 0).' of our '.number_format($c['total']).' citations · branded '.number_format(($c['branded_share'] ?? 0) * 100, 1).'%',
                'pie' => [['Branded', (int) ($c['branded'] ?? 0)], ['Non-branded', (int) ($c['non_branded'] ?? 0)]],
            ] : null,
            'sessions' => ($m['ai_sessions'] ?? null) !== null ? [
                'value' => $m['ai_sessions'],
                'delta' => self::delta($m['ai_sessions'], $this->paced($ctx, $p['ai_sessions'] ?? null), $this->paceVs($ctx)),
            ] : null,
        ];
    }

    private function landscape(array $ctx): array
    {
        $men = $this->semrushMentions();
        $mention = $this->semrushMetric('AI mentions');
        $cit = $this->semrushMetric('AI citations');
        $pages = $this->semrushMetric('Cited pages');
        $score = $this->semrushMetric('AI Visibility score (0-100)');
        $fmtChange = fn ($row) => $row && $row['change'] !== null ? ' ('.($row['change'] >= 0 ? '+' : '−').number_format(abs($row['change']) * 100, 1).'%)' : '';
        $foot = [];
        if ($mention) {
            $prev = $mention['change'] !== null && $mention['change'] > -1 ? round($mention['value'] / (1 + $mention['change'])) : null;
            $foot[] = number_format($mention['value']).' mentions in total'.($mention['change'] !== null ? ', '.($mention['change'] >= 0 ? 'up' : 'down').' '.number_format(abs($mention['change']) * 100, 1).'% on the previous capture'.($prev !== null ? " ({$prev})" : '') : '').'.';
        } elseif ($men['total']) {
            $foot[] = number_format($men['total']).' prompts mention the brand in the Semrush export.';
        }
        $tail = [];
        if ($cit) {
            $tail[] = 'Citations '.number_format($cit['value']).$fmtChange($cit);
        }
        if ($pages) {
            $tail[] = 'cited pages '.number_format($pages['value']).$fmtChange($pages);
        }
        if ($tail) {
            $foot[] = implode(', ', $tail).'.';
        }
        if ($score) {
            $foot[] = 'Semrush AI Visibility score '.$score['value'].'/100.';
        }
        $platTotal = $men['total'] ?: array_sum($men['platforms']);

        return [
            'captured' => $ctx['semrush_captured'] && ($men['platforms'] || $men['countries']),
            'src' => 'Semrush AI Visibility'.($this->semrushDay() ? ' · captured '.$this->semrushDay() : ''),
            'countries' => collect($men['countries'])->map(fn ($v, $k) => [$k, $v])->values()->all(),
            'platforms' => collect($men['platforms'])->map(fn ($v, $k) => ['name' => $k, 'v' => $v, 'pct' => $platTotal ? $v / $platTotal * 100 : 0])->values()->all(),
            'footnote' => implode(' ', $foot),
            'source' => $men['source'],
        ];
    }

    private function funnel(array $ctx): array
    {
        $f = $this->tables['funnel_monthly'][$ctx['sel']] ?? null;
        $sessions = $this->month($ctx['sel'])['ai_sessions'] ?? null;
        $sub = 'What AI-sourced visitors did on the site · '.Carbon::parse($ctx['sel'].'-01')->format('F Y').($f && preg_match('/\d+\s*-\s*\d+/', $f['label'] ?? '', $mm) ? ' '.str_replace('-', '–', $mm[0]) : '').' · GA4, observed (event counts)';
        if (! $f || empty($f['events'])) {
            return ['available' => false, 'sub' => $sub];
        }
        $labels = ['view_item' => 'Product view', 'add_to_cart' => 'Add to cart', 'begin_checkout' => 'Checkout', 'purchase' => 'Purchase'];
        $steps = [];
        $first = null;
        $prevV = null;
        foreach ($labels as $event => $label) {
            $v = (int) ($f['events'][$event] ?? 0);
            $first ??= $v;
            $steps[] = [
                'label' => $label, 'v' => $v,
                'width' => $first ? $v / $first * 100 : 0,
                'conv' => $prevV === null ? number_format($f['events']['session_start'] ?? $sessions ?? 0).' AI sessions' : number_format($prevV ? $v / $prevV * 100 : 0, 1).'% of previous step',
            ];
            $prevV = $v;
        }
        $purchases = (int) ($f['events']['purchase'] ?? 0);
        $base = (int) ($f['events']['session_start'] ?? $sessions ?? 0);
        $ch = $this->channels($ctx['sel']);
        $siteS = array_sum(array_column($ch, 0));
        $siteK = array_sum(array_column($ch, 1));
        if (! $siteS) {
            $siteS = (int) ($this->month($ctx['sel'])['total_sessions'] ?? 0);
            $siteK = (int) ($this->month($ctx['sel'])['total_key_events'] ?? 0);
        }

        return [
            'available' => true, 'sub' => $sub, 'steps' => $steps,
            'revenue' => $f['revenue'] ?? null,
            'summary' => $base ? '<b>'.number_format($purchases / $base * 100, 2).'%</b> of AI-sourced sessions ended in a purchase this month'.($siteS ? ' (site-wide key-event rate '.number_format($siteK / $siteS * 100, 2).'%)' : '').'.' : null,
        ];
    }

    private function trafficQuality(array $ctx): array
    {
        $src = $this->tables['ai_sources_monthly'] ?? [];
        $cur = $src[$ctx['sel']] ?? null;
        $prv = $ctx['prev'] ? ($src[$ctx['prev']] ?? []) : [];
        if ($cur) {
            $platforms = collect($cur)->map(fn ($v, $k) => ['l' => $k, 'v' => $v, 'd' => ! empty($prv[$k]) ? ($v - $this->paced($ctx, $prv[$k])) / $this->paced($ctx, $prv[$k]) * 100 : null])->sortByDesc('v')->values()->all();
            $platSrc = 'Google Analytics 4 · session source · '.Carbon::parse($ctx['sel'].'-01')->format('M Y');
        } elseif (! empty($this->snapshot['ai_platforms'])) {
            $platforms = collect($this->snapshot['ai_platforms'])->map(fn ($p) => ['l' => $p['platform'], 'v' => (int) $p['total_sessions'], 'd' => null])->values()->all();
            $platSrc = 'Google Analytics 4 · session source · all imported months';
        } else {
            $platforms = [];
            $platSrc = 'Google Analytics 4 · session source';
        }

        // AI visitors vs organic visitors (same source table for both sides).
        $ch = $this->channels($ctx['sel']);
        $ai = $ch['AI Assistant'] ?? null;
        $org = $ch['Organic Search'] ?? null;
        $cmp = null;
        if ($ai && $org && $ai[0] && $org[0]) {
            $rate = [$ai[1] / $ai[0] * 100, $org[1] / $org[0] * 100];
            $rps = [$ai[2] / $ai[0], $org[2] / $org[0]];
            $mult = $rate[1] ? $rate[0] / $rate[1] : 0;
            $cmp = [
                'against' => 'Organic visitors',
                'sub' => $ctx['label'].' · '.number_format($ai[0]).' AI sessions vs '.number_format($org[0]).' organic',
                'lead' => $mult >= 1 ? 'AI visitors convert at '.number_format($mult, 1).'x the organic rate' : 'AI visitors convert at '.round($mult * 100).'% of the organic rate',
                'rows' => [
                    ['m' => 'Key-event rate', 'a' => $rate[0], 'o' => $rate[1], 'fmt' => 'pct'],
                    ['m' => 'Revenue per session', 'a' => $rps[0], 'o' => $rps[1], 'fmt' => 'sar'],
                ],
            ];
        } else {
            $m = $this->month($ctx['sel']);
            if (! empty($m['ai_sessions']) && ! empty($m['total_sessions'])) {
                $rate = [($m['ai_key_events'] ?? 0) / $m['ai_sessions'] * 100, ($m['total_key_events'] ?? 0) / $m['total_sessions'] * 100];
                $mult = $rate[1] ? $rate[0] / $rate[1] : 0;
                $cmp = [
                    'against' => 'Whole site',
                    'sub' => $ctx['label'].' · '.number_format($m['ai_sessions']).' AI sessions vs '.number_format($m['total_sessions']).' site sessions',
                    'lead' => $mult >= 1 ? 'AI visitors convert at '.number_format($mult, 1).'x the site rate' : 'AI visitors convert at '.round($mult * 100).'% of the site rate',
                    'rows' => [['m' => 'Key-event rate', 'a' => $rate[0], 'o' => $rate[1], 'fmt' => 'pct']],
                ];
            }
        }

        // AI landing pages for the selected month (GA4 live sync).
        $landing = collect($this->tables['landing_pages_monthly'][$ctx['sel']] ?? [])
            ->map(fn ($r) => ['page' => $r[0], 'short' => self::shortPage($r[0]), 'sessions' => (int) $r[1], 'key_events' => (int) ($r[2] ?? 0), 'revenue' => (float) ($r[3] ?? 0)])
            ->values()->all();

        return ['platforms' => $platforms, 'platSrc' => $platSrc, 'cmp' => $cmp, 'landing' => $landing];
    }

    private function grounding(array $ctx): array
    {
        $c = $this->clarity();
        $caption = $this->tables['captions']['GSC_AI_Pages'] ?? null;
        $range = $caption && preg_match('/export,\s*(.+?)\.(\s|$)/i', $caption, $m) ? str_replace('-', ' to ', $m[1]) : null;
        $aiPages = collect($this->snapshot['top_ai_pages'] ?? [])->take(8)
            ->map(fn ($p) => ['l' => self::shortPage(self::pagePath($p['url'])), 'full' => self::pagePath($p['url']), 'v' => (int) $p['impressions']])->values()->all();
        $queries = collect($c['queries']);
        $topicTotal = collect($c['topics'])->sum('citations');

        return [
            'captured' => $ctx['captured'],
            'src' => 'Microsoft Clarity AI Visibility (Copilot and partners, same source as Bing AI Performance) · last 30 days'.($this->clarityDay() ? ' · captured '.$this->clarityDay() : ''),
            'queries' => $queries->take(10)->all(),
            'queryFoot' => $queries->isNotEmpty() ? 'Top '.min(10, $queries->count()).' of '.$queries->count().' queries'.($c['total'] ? ' · '.number_format($c['total']).' citations in total' : '').($c['share'] !== null ? ', '.number_format($c['share'] * 100, 1).'% share of all citations shown' : '') : null,
            'aiPages' => $aiPages,
            'aiPagesSub' => 'Impressions inside AI Overviews and AI Mode'.($range ? ', '.$range : ''),
            'citPages' => collect($c['pages'])->take(8)->map(fn ($p) => ['l' => self::shortPage($p['page']), 'full' => $p['page'], 'v' => (int) $p['citations']])->values()->all(),
            'bot' => $c['bot'],
            'soa' => [
                'branded' => $c['branded_share'] !== null ? ['share' => $c['branded_share'] * 100, 'citations' => $c['branded']] : null,
                'non_branded' => $c['non_branded_share'] !== null ? ['share' => $c['non_branded_share'] * 100, 'citations' => $c['non_branded']] : null,
                'topics' => collect($c['topics'])->map(fn ($t) => ['l' => $t['topic'], 'citations' => (int) $t['citations'], 'share' => $t['share'] !== null ? $t['share'] * 100 : null])->values()->all(),
            ],
            'pies' => [
                'type' => array_values(array_filter([
                    $c['branded'] !== null ? ['Branded queries', (int) $c['branded']] : null,
                    $c['non_branded'] !== null ? ['Non-branded queries', (int) $c['non_branded']] : null,
                ])),
                'topic' => collect($c['topics'])->map(fn ($t) => [$t['topic'], (int) $t['citations']])->values()->all(),
                'page' => collect($c['pages'])->take(5)->map(fn ($p) => [self::shortPage($p['page']), (int) $p['citations']])
                    ->push(['Other pages', max(0, (int) collect($c['pages'])->sum('citations') - (int) collect($c['pages'])->take(5)->sum('citations'))])
                    ->filter(fn ($r) => $r[1] > 0)->values()->all(),
            ],
            'total' => $c['total'],
            'topicTotal' => $topicTotal,
        ];
    }

    private const LLM_NAMES = ['chatgpt' => 'ChatGPT', 'gemini' => 'Gemini', 'google_ai_mode' => 'Google AI Mode', 'ai_mode' => 'Google AI Mode', 'google_ai_overview' => 'Google AI Overviews', 'ai_overview' => 'Google AI Overviews', 'perplexity' => 'Perplexity', 'copilot' => 'Copilot', 'claude' => 'Claude'];

    /** Semrush prompt answers by assistant: ['ChatGPT' => ['prompts' => n, 'cited' => n, 'mentioned' => n], …]. */
    private function semrushByPlatform(): array
    {
        $out = [];
        foreach ($this->semrush['prompts'] ?? [] as $p) {
            $llm = strtolower($p['llm'] ?? '');
            $name = self::LLM_NAMES[$llm] ?? ($llm !== '' ? ucwords(str_replace('_', ' ', $llm)) : 'Other');
            $cat = $p['categories'] ?? '';
            $out[$name] ??= ['prompts' => 0, 'cited' => 0, 'mentioned' => 0];
            $out[$name]['prompts']++;
            $out[$name]['cited'] += str_contains($cat, 'cites_target') ? 1 : 0;
            $out[$name]['mentioned'] += str_contains($cat, 'mentions_target') ? 1 : 0;
        }

        return $out;
    }

    private function semrushCitationTotal(): int
    {
        return (int) collect($this->semrushByPlatform())->sum('cited');
    }

    /**
     * Citations for every assistant in one place: Copilot from Clarity (observed),
     * ChatGPT / Gemini / Google AI from Semrush prompt runs (simulated), and the AI
     * visits each assistant sent that month from GA4 (observed).
     */
    private function platformCitations(array $ctx): array
    {
        $c = $this->clarity();
        $sem = $this->semrushByPlatform();
        $visits = $this->tables['ai_sources_monthly'][$ctx['sel']] ?? [];
        $semMonth = $this->semrush['month'] ?? null;
        $semLabel = $semMonth ? Carbon::parse($semMonth.'-01')->format('M Y') : null;
        $clarityLabel = $this->clarityDay() ? 'captured '.$this->clarityDay() : 'last 30 days';

        $order = ['Copilot', 'ChatGPT', 'Gemini', 'Google AI Mode', 'Google AI Overviews', 'Perplexity', 'Claude'];
        $names = collect($order)->merge(array_keys($sem))->merge(array_keys($visits))->unique()->values();

        $rows = $names->map(function ($name) use ($c, $sem, $visits, $semLabel, $clarityLabel) {
            $row = ['platform' => $name, 'citations' => null, 'of' => null, 'type' => null, 'src' => null, 'visits' => isset($visits[$name]) ? (int) $visits[$name] : null];
            if ($name === 'Copilot' && $c['total']) {
                $row = array_merge($row, ['citations' => (int) $c['total'], 'type' => 'obs', 'src' => 'Clarity · '.$clarityLabel]);
            } elseif (isset($sem[$name])) {
                $row = array_merge($row, ['citations' => $sem[$name]['cited'], 'of' => $sem[$name]['prompts'], 'type' => 'sim', 'src' => 'Semrush prompts'.($semLabel ? ' · '.$semLabel : '')]);
            }

            return $row;
        })->filter(fn ($r) => $r['citations'] !== null || $r['visits'] !== null)->values()->all();

        $pie = collect($sem)->map(fn ($v, $k) => [$k, $v['cited']])->filter(fn ($r) => $r[1] > 0)->sortByDesc(1)->values()->all();
        $semTotal = $this->semrushCitationTotal();
        $semPrompts = (int) collect($sem)->sum('prompts');
        $gaTotal = array_sum($visits);
        $noCite = collect($rows)->filter(fn ($r) => $r['citations'] === null)->pluck('platform')->all();

        $summary = [];
        if ($semTotal) {
            $top = $pie[0] ?? null;
            $summary[] = 'Semrush found a link to the site in <b>'.number_format($semTotal).' of '.number_format($semPrompts).' answers</b>'.($top ? ', most often in '.e($top[0]).' ('.number_format($top[1]).')' : '').'.';
        }
        if ($c['total']) {
            $summary[] = 'Clarity logged <b>'.number_format($c['total']).' Copilot citations</b> ('.$clarityLabel.').';
        }
        if ($gaTotal) {
            $topVisit = collect($visits)->sortDesc();
            $summary[] = 'GA4 counted <b>'.number_format($gaTotal).' AI visits</b> in '.Carbon::parse($ctx['sel'].'-01')->format('F').', '.number_format($topVisit->first() / $gaTotal * 100, 0).'% from '.e($topVisit->keys()->first()).'.';
        }
        if ($noCite) {
            $summary[] = 'No tool reports citations for '.e(implode(' or ', $noCite)).' yet, so only their visits are shown.';
        }

        return [
            'available' => (bool) $rows,
            'rows' => $rows,
            'pie' => $pie,
            'semTotal' => $semTotal,
            'semPrompts' => $semPrompts,
            'semLabel' => $semLabel,
            'gaMonth' => Carbon::parse($ctx['sel'].'-01')->format('M Y'),
            'summary' => implode(' ', $summary),
        ];
    }

    private function friction(array $ctx): array
    {
        $b = $this->clarity()['behaviour'];
        if (! $b) {
            $flat = $this->snapshot['clarity_data'] ?? [];
            foreach (['Rage clicks', 'Dead clicks', 'Quick backs', 'Scroll depth', 'Pages per session', 'LCP (sec)', 'Active time (sec)', 'INP (ms)', 'CLS', 'Performance score', 'JavaScript error sessions', 'Sessions'] as $k) {
                if (isset($flat[$k]) && is_numeric($flat[$k])) {
                    $b[$k] = ['ai' => $flat[$k] + 0, 'site' => null];
                }
            }
        }
        // [label, source key, lower is better, format]
        $spec = [
            ['Rage clicks', 'Rage clicks', true, 'pct'], ['Dead clicks', 'Dead clicks', true, 'pct'],
            ['Quick backs', 'Quick backs', true, 'pct'], ['Average scroll depth', 'Scroll depth', false, 'pct1'],
            ['Pages per session', 'Pages per session', false, 'dec'], ['Largest contentful paint', 'LCP (sec)', true, 'sec'],
            ['Active time per session', 'Active time (sec)', false, 'secs'], ['Interaction to next paint', 'INP (ms)', true, 'ms'],
            ['Layout shift (CLS)', 'CLS', true, 'cls'], ['JavaScript error sessions', 'JavaScript error sessions', true, 'pct'],
            ['Performance score', 'Performance score', false, 'int'],
        ];
        $rows = [];
        foreach ($spec as [$label, $key, $low, $fmt]) {
            if (! isset($b[$key]) || $b[$key]['ai'] === null) {
                continue;
            }
            $a = $b[$key]['ai'];
            $s = $b[$key]['site'];
            $rows[] = ['l' => $label, 'a' => $a, 's' => $s, 'fmt' => $fmt, 'good' => $s === null ? null : ($low ? $a <= $s : $a >= $s)];
        }
        $sess = $b['Sessions'] ?? null;

        return [
            'captured' => $ctx['captured'],
            'rows' => $rows,
            'src' => 'Microsoft Clarity · Channel = AIPlatform'.($sess && $sess['ai'] ? ' ('.number_format($sess['ai']).' sessions)' : '').($sess && $sess['site'] ? ' vs all traffic ('.number_format($sess['site']).')' : '').($this->clarityDay() ? ' · captured '.$this->clarityDay() : ''),
        ];
    }

    private function competitive(array $ctx): array
    {
        $topics = $this->tables['semrush_topics'] ?? [];
        $source = 'workbook';
        if (! $topics && ! empty($this->semrush['topics'])) {
            $source = 'export';
            $topics = collect($this->semrush['topics'])->map(fn ($t) => [
                'topic' => $t['name'] ?? '', 'market' => strtoupper($t['country'] ?? ''),
                'visibility' => (int) ($t['visibility'] ?? 0), 'volume' => (int) ($t['volume'] ?? 0),
            ])->all();
        }
        $zero = collect($topics)->filter(fn ($t) => (int) ($t['visibility'] ?? 0) === 0)->sortByDesc('volume')->values();

        return [
            'captured' => $ctx['semrush_captured'] || $source === 'export',
            'src' => 'Semrush AI Visibility · topics'.($this->semrushDay() ? ' · '.$this->semrushDay() : ''),
            'gaps' => [
                'rows' => $zero->take(8)->all(),
                'zero' => $zero->count(),
                'tracked' => count($topics),
                'volume' => (int) $zero->sum('volume'),
            ],
        ];
    }

    /** Client vs tracked competitors from Semrush brand summaries: share of voice and AI score ranking. */
    private function competitors(array $ctx): array
    {
        if (! $this->brands) {
            return ['available' => false];
        }
        $month = isset($this->brands[$ctx['sel']]) ? $ctx['sel'] : array_key_last($this->brands);
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $rows = collect($this->brands[$month])->map(fn ($b) => [
            'name' => $b['name'],
            'domain' => $b['domain'] ?? null,
            'is_client' => (bool) ($b['is_client'] ?? false),
            'score' => $num($b['metrics']['overall_score'] ?? null),
            'mentions' => $num($b['metrics']['mentions'] ?? null),
            'citations' => $num($b['metrics']['citations'] ?? null),
            'cited_pages' => $num($b['metrics']['cited_pages'] ?? null),
            'prompts' => $num($b['metrics']['prompts'] ?? null),
        ])->sortByDesc(fn ($r) => [$r['score'] ?? -1, $r['mentions'] ?? -1])->values();
        $client = $rows->firstWhere('is_client', true);
        $others = $rows->where('is_client', false);

        // Share of voice: client first (red slice), then the five competitors with most mentions.
        $byMentions = $others->filter(fn ($r) => $r['mentions'] !== null)->sortByDesc('mentions')->values();
        $sov = $client['mentions'] !== null ? [[$client['name'], $client['mentions']]] : [];
        foreach ($byMentions->take(5) as $r) {
            $sov[] = [$r['name'], $r['mentions']];
        }
        if ($byMentions->count() > 5) {
            $sov[] = ['Other brands', $byMentions->slice(5)->sum('mentions')];
        }
        $totalMentions = $client['mentions'] !== null ? $client['mentions'] + $byMentions->sum('mentions') : null;
        $share = $totalMentions ? $client['mentions'] / $totalMentions * 100 : null;

        $rank = function (string $key) use ($rows, $client): ?array {
            if ($client[$key] === null) {
                return null;
            }
            $vals = $rows->pluck($key)->filter(fn ($v) => $v !== null);
            $above = $vals->filter(fn ($v) => $v > $client[$key])->count();
            $tied = $vals->filter(fn ($v) => $v == $client[$key])->count() > 1;

            return ['pos' => $above + 1, 'of' => $vals->count(), 'tied' => $tied];
        };
        $ord = fn (int $n) => $n.(in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th'));
        $rankText = fn (?array $r) => $r ? ($r['tied'] ? 'joint ' : '').$ord($r['pos']).' of '.$r['of'] : null;
        $mRank = $rank('mentions');
        $sRank = $rank('score');
        $leader = $others->filter(fn ($r) => $r['score'] !== null)->sortByDesc('score')->first();

        $parts = [];
        if ($share !== null) {
            $parts[] = '<b>'.e($client['name']).'</b> holds <b>'.number_format($share, 1).'%</b> of AI mentions across '.($byMentions->count() + 1).' tracked brands, '.$rankText($mRank).' on mentions.';
        }
        if ($sRank) {
            $lead = $leader && $leader['score'] > $client['score'] ? ' '.e($leader['name']).' leads with '.(int) $leader['score'].'.' : ' No tracked competitor scores higher.';
            $parts[] = 'Its Semrush AI score of <b>'.(int) $client['score'].'/100</b> ranks '.$rankText($sRank).'.'.$lead;
        }

        $label = Carbon::parse($month.'-01')->format('F Y');

        return [
            'available' => $client !== null && $others->isNotEmpty(),
            'month' => $month,
            'src' => 'Semrush AI Visibility · brand summaries · '.$label,
            'note' => $month !== $ctx['sel'] ? 'Competitor figures are from the '.$label.' capture, the latest available. Semrush brand summaries were not captured for '.Carbon::parse($ctx['sel'].'-01')->format('F Y').'.' : null,
            'count' => $rows->count(),
            'sov' => $sov,
            'share' => $share,
            'scores' => $rows->filter(fn ($r) => $r['score'] !== null)->values()->all(),
            'rows' => $rows->all(),
            'summary' => implode(' ', $parts),
        ];
    }

    // ── "?" help: where each number comes from and why it is on the page ─

    public static function help(): array
    {
        return [
            'note' => ['Analyst note', 'Written from this month\'s data: Search Console, GA4, Clarity and Semrush.', 'One paragraph that says what moved and why, so nobody has to read every chart to get the story. If a line moves and we cannot explain it, it gets a caveat here.'],
            'headline' => ['Headline', 'Search Console (AI impressions), Bing via Clarity (citations), Semrush (mentions) and GA4 (AI sessions).', 'Answers two questions: is the brand showing up inside AI answers, and is anyone arriving on the site from them?'],
            'hero_imp' => ['AI impressions', 'Google Search Console → Performance → Search appearance "AI Overviews / AI Mode". Observed: Google counts each time a store page is shown inside an AI answer.', 'The largest observed AI visibility signal. It shows reach inside Google\'s AI answers before anyone clicks.'],
            'hero_cit' => ['AI citations', 'Microsoft Clarity → AI Visibility → Citations (the same Copilot data as Bing Webmaster AI Performance), last 30 days, captured monthly.', 'A citation means the assistant linked to our page as a source. It is stronger proof of authority than a plain impression.'],
            'hero_men' => ['AI mentions', 'Semrush AI Visibility: Semrush asks ChatGPT, Gemini, Google AI Mode and AI Overviews a fixed prompt set each month and counts answers that name the brand.', 'Simulated, so it is directional. It shows whether assistants talk about the brand even when they do not link to it.'],
            'daily' => ['AI impressions by day', 'Search Console Generative AI features export, by date. The line is a 7-day rolling average.', 'Daily bars show spikes, for example a blog post being picked up. The 7-day average smooths out weekday noise.'],
            'soa' => ['Non-branded Share of Authority', 'Microsoft Clarity AI Visibility: our share of all citations Copilot showed on queries that do not contain the brand name.', 'This replaces "clicks". No tool reports clicks out of AI answers, and winning searches that do not name the brand is how new customers find us.'],
            'sessions' => ['AI-sourced sessions', 'GA4 → Session default channel group = AI Assistant (ChatGPT, Gemini, Copilot, Perplexity, Claude and others), via the live Google connection.', 'The one fully automated post-click number: real visits that arrived from an AI assistant.'],
            'landscape' => ['Landscape', 'Semrush AI Visibility prompt runs, captured monthly.', 'Shows where and on which assistant the brand is mentioned. It is simulated, so read it beside the observed numbers, never merged with them.'],
            'country' => ['Where AI mentions the brand', 'Semrush AI Visibility → Mentions by country: the country each prompt was asked from.', 'Shows which markets the assistants associate with the brand, which tells us where content and PR work pays off.'],
            'platform' => ['Mentions by AI platform', 'Semrush AI Visibility → Mentions by platform. The current Semrush tier tracks ChatGPT, Gemini, Google AI Mode and AI Overviews.', 'Each assistant uses different sources, so a weak platform points to a specific gap to fix.'],
            'postclick' => ['Post-click', 'GA4 ecommerce events (view_item, add_to_cart, begin_checkout, purchase) for the AI Assistant channel group.', 'Shows whether AI visitors behave like buyers. Revenue on the last step is the post-click headline number.'],
            'traffic' => ['Traffic quality', 'GA4 sessions by source for AI referrers, compared with Organic Search.', 'Visibility only matters if the visits are worth something. This compares AI visitors with our best-known channel.'],
            'platsess' => ['AI-sourced sessions by platform', 'GA4 sessions where the referrer is an AI assistant, grouped by platform.', 'Observed visits. Read it beside the simulated mentions chart above, never merged with it.'],
            'cmp' => ['AI visitors vs organic visitors', 'GA4 key events and revenue per session, AI Assistant vs Organic Search, same site and same month.', 'Tells us whether an AI visitor is worth more or less than a search visitor. AI samples are small, so read the ratio as a direction.'],
            'landing' => ['Top AI landing pages', 'Google Analytics 4 → landing page for sessions in the AI Assistant channel, for the selected month (live sync).', 'Shows which pages AI visitors land on, so we know which pages to keep fast and conversion-ready.'],
            'platcit' => ['AI citations by platform', 'Copilot from Microsoft Clarity (observed). ChatGPT, Gemini, Google AI Mode and AI Overviews from the Semrush prompt export: answers whose sources link to the site (simulated). AI visits per assistant from GA4 session source (observed).', 'No single tool covers every assistant, so this lines the three sources up side by side. Observed and simulated numbers are labelled and never added together.'],
            'grounding' => ['Grounding and citations', 'Search Console, Microsoft Clarity (Copilot), Semrush (ChatGPT, Gemini, Google AI) and GA4 (AI visits).', 'Explains the "why" behind visibility: which searches the assistant ran and which of our pages it chose to cite.'],
            'gq' => ['Top grounding queries', 'Microsoft Clarity AI Visibility → Grounding queries: the searches Copilot ran to build its answers, with our citation count and share.', 'Shows the questions where we are already a trusted source, and where competitors take most of the citations.'],
            'aipages' => ['Pages Google shows in AI answers', 'Search Console Generative AI features report → Pages tab.', 'Shows which pages Google trusts enough to show inside AI Overviews and AI Mode, mostly blog content.'],
            'citpages' => ['Pages Copilot cites', 'Microsoft Clarity AI Visibility → Cited pages, last 30 days.', 'Shows which pages Copilot uses as evidence. Pages missing from this list are candidates for better structure and facts.'],
            'citpie' => ['AI citations breakdown', 'Microsoft Clarity AI Visibility: citations split by query type (branded vs non-branded), by topic and by cited page.', 'A pie shows at a glance how dependent our citations are on people already searching for the brand.'],
            'crawler' => ['AI crawler visits', 'Microsoft Clarity → AI Visibility → Bot activity. It needs a CDN integration (Cloudflare, Akamai or similar).', 'Once connected, it shows whether GPTBot, ClaudeBot, PerplexityBot and others reach our key pages.'],
            'soacard' => ['Share of Authority', 'Microsoft Clarity AI Visibility: our share of all citations shown, split by branded and non-branded queries and by topic.', 'Share of Authority is our slice of the citations, so it stays comparable even when total AI usage grows.'],
            'onsite' => ['On-site experience', 'Microsoft Clarity dashboard with the AI channel filter, compared with all traffic.', 'Shows what happens after someone arrives from an AI answer, and whether friction is losing them.'],
            'friction' => ['Friction on AI landing pages', 'Microsoft Clarity: rage clicks, dead clicks, quick backs, scroll depth and speed for Channel = AIPlatform vs all traffic, last 30 days.', 'AI visitors arrive with a specific question. Friction on the landing page wastes the visibility we earned.'],
            'competitive' => ['Competitive watch', 'Semrush prompt set and topics, captured monthly.', 'Shows how assistants describe the brand and which high-demand topics we are missing from.'],
            'competitors' => ['Competitors', 'Semrush AI Visibility brand summaries for the client and each brand in Competitor comparison, captured monthly.', 'Shows whether assistants talk about us more or less than the brands customers compare us with. Simulated, so read it as a direction.'],
            'sov' => ['AI share of voice', 'Semrush brand mentions for each tracked brand, as a share of all mentions across the tracked brands. Brands beyond the top five competitors are grouped as "Other brands".', 'One number for "are we the brand AI names in our category?". Each brand has its own Semrush prompt set, so shares are directional.'],
            'cmpscore' => ['AI visibility score by brand', 'Semrush AI Visibility score (0–100) for each tracked brand, with mentions, citations and cited pages from the same summary.', 'Ranks the brand against competitors on one comparable scale, and shows where a competitor is ahead.'],
            'sentiment' => ['How AI talks about the brand', 'Semrush prompt answers that name the brand, scored positive, neutral or cautious by the analyst.', 'Being mentioned is not enough; the tone decides whether the mention helps sales.'],
            'gaps' => ['Topic gap watch list', 'Semrush AI Visibility topics export: topics with visibility 0, sorted by monthly search volume.', 'High-demand topics where AI never mentions the brand. We report the gaps; fixing them is the content team\'s scope.'],
            'legend' => ['How to read this report', 'Observed figures come from platform logs. Simulated figures come from Semrush prompt runs.', 'Keeping the two apart stops directional estimates being read as measured results.'],
        ];
    }
}
