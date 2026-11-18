<?php

namespace App\Services\AiTracking;

/**
 * Reads one AI answer: which tracked brands it names and in what order, and
 * whether each brand's own site is among the sources.
 */
class AnswerAnalyzer
{
    /**
     * @param  array<int, array{name: string, domain: ?string, aliases?: string[], client?: bool}>  $brands
     * @return array{mentioned: bool, rank: ?int, cited: bool, brands: array<int, array{name: string, client: bool, rank: ?int, cited: bool}>}
     */
    public function analyze(string $text, array $sources, array $brands): array
    {
        $domains = collect($sources)->pluck('domain')->filter()->map(fn ($d) => strtolower($d))->all();
        $found = [];
        foreach ($brands as $b) {
            $pos = $this->firstPosition($text, self::spellings($b));
            $cited = $b['domain'] ? collect($domains)->contains(fn ($d) => self::sameSite($d, $b['domain'])) : false;
            $found[] = ['name' => $b['name'], 'client' => (bool) ($b['client'] ?? false), 'pos' => $pos, 'cited' => $cited];
        }

        // Rank = order of first appearance among the tracked brands that are named.
        $named = collect($found)->filter(fn ($f) => $f['pos'] !== null)->sortBy('pos')->values();
        $out = collect($found)->map(function ($f) use ($named) {
            $i = $named->search(fn ($n) => $n['name'] === $f['name']);

            return ['name' => $f['name'], 'client' => $f['client'], 'rank' => $i === false ? null : $i + 1, 'cited' => $f['cited']];
        })->values()->all();
        $client = collect($out)->firstWhere('client', true);

        return [
            'mentioned' => $client && $client['rank'] !== null,
            'rank' => $client['rank'] ?? null,
            'cited' => (bool) ($client['cited'] ?? false),
            'brands' => $out,
        ];
    }

    /** Name, name without spaces or with hyphens, domain, domain stem and any extra aliases. */
    public static function spellings(array $brand): array
    {
        $name = trim($brand['name']);
        $list = [$name, str_replace(' ', '', $name), str_replace(' ', '-', $name)];
        if (! empty($brand['domain'])) {
            $d = preg_replace('/^www\./', '', strtolower($brand['domain']));
            $list[] = $d;
            $stem = explode('.', $d)[0];
            if (mb_strlen($stem) >= 5) {
                $list[] = $stem;
            }
        }
        foreach ($brand['aliases'] ?? [] as $a) {
            $list[] = trim($a);
        }

        return array_values(array_unique(array_filter($list, fn ($s) => mb_strlen($s) >= 3)));
    }

    private function firstPosition(string $text, array $spellings): ?int
    {
        $best = null;
        foreach ($spellings as $s) {
            // Latin spellings must stand alone as words; Arabic and other scripts match as written.
            $pattern = preg_match('/^[\p{Latin}0-9 .\-&\']+$/u', $s)
                ? '/(?<![\p{L}\p{N}])'.preg_quote($s, '/').'(?![\p{L}\p{N}])/iu'
                : '/'.preg_quote($s, '/').'/u';
            if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                $best = $best === null ? $m[0][1] : min($best, $m[0][1]);
            }
        }

        return $best;
    }

    public static function sameSite(string $host, string $domain): bool
    {
        $host = preg_replace('/^www\./', '', strtolower($host));
        $domain = preg_replace('/^www\./', '', strtolower($domain));

        return $host === $domain || str_ends_with($host, '.'.$domain);
    }
}
