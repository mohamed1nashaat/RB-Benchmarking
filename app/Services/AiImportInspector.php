<?php

namespace App\Services;

use App\Models\AiVisibilitySnapshot;
use App\Models\SemrushReport;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Checks a batch of monthly AI visibility files before anything is saved.
 *
 * Files are identified by their contents (sheet names / CSV columns), not by
 * their names. Each file gets errors (block the import) and warnings (allowed,
 * but shown), then the batch is checked as a whole: duplicates, topics and
 * prompts paired per brand, brands known to this client, dates consistent.
 */
class AiImportInspector
{
    public const TOPIC_COLUMNS = ['name', 'country', 'visibility', 'difficulty', 'mentions', 'volume', 'volume_trend', 'intents'];

    public const PROMPT_COLUMNS = ['prompt', 'country', 'llm', 'categories', 'topic_id', 'topic_name', 'topic_volume', 'topic_intents', 'topic_visibility', 'topic_difficulty', 'brief_response', 'mentioned_brands_count', 'sources_count'];

    private const WORKBOOK_SHEETS = ['Data_GSC_Daily', 'Data_GA4_SourceMedium', 'GSC_AI_Pages', 'AI_Funnel', 'Clarity_AI', 'Semrush_Overview', 'Semrush_Topics'];

    private const KNOWN_LLMS = ['chatgpt', 'gemini', 'google_ai_mode', 'ai_mode', 'google_ai_overview', 'ai_overview', 'perplexity', 'claude', 'copilot'];

    /** Parsed payloads kept for the import step, keyed by file index. */
    public array $payloads = [];

    /**
     * @param  array<int, array{path: string, name: string}>  $files
     */
    public function inspect(array $files, int $tenantId, string $clientDomain, string $captureDate): array
    {
        $this->payloads = [];
        $errors = [];
        $warnings = [];
        $clientDomain = SemrushImporter::host($clientDomain);
        $dateOk = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $captureDate) && strtotime($captureDate);
        $month = $dateOk ? substr($captureDate, 0, 7) : null;

        if ($clientDomain === '') {
            $errors[] = 'Enter the client\'s website domain so files can be matched to the right brand.';
        }
        if (! $dateOk) {
            $errors[] = 'Enter the date the Semrush data was captured.';
        } elseif ($captureDate > now()->toDateString()) {
            $errors[] = 'The capture date is in the future.';
        }

        $report = [];
        foreach ($files as $i => $file) {
            $report[$i] = $this->inspectFile($i, $file, $tenantId, $clientDomain, $month);
        }

        // Same file twice (even under different names).
        foreach (collect($report)->groupBy('sha1') as $same) {
            if ($same->count() > 1) {
                $errors[] = 'The same file was added more than once: '.$same->pluck('name')->implode(', ').'.';
            }
        }

        // Semrush: one topics + one prompts file per brand.
        $brands = [];
        foreach ($report as $f) {
            if (in_array($f['type'], ['topics', 'prompts'], true) && $f['domain']) {
                $brands[$f['domain']][$f['type']][] = $f['name'];
                $brands[$f['domain']]['role'] = $f['role'];
            }
        }
        foreach ($brands as $domain => $b) {
            foreach (['topics' => 'Brand topics', 'prompts' => 'Prompts'] as $type => $label) {
                if (empty($b[$type])) {
                    $errors[] = "{$domain}: the {$label} export is missing. Upload both Brand topics and Prompts for every brand.";
                } elseif (count($b[$type]) > 1) {
                    $errors[] = "{$domain}: more than one {$label} export (".implode(', ', $b[$type]).').';
                }
            }
        }
        if ($brands && $clientDomain && ! collect($brands)->contains(fn ($b) => ($b['role'] ?? null) === 'client')) {
            $warnings[] = 'No Semrush files for the client itself, so only competitor numbers will change.';
        }

        // Workbook: one per batch; its capture dates should agree with the Semrush date.
        $workbooks = collect($report)->where('type', 'workbook');
        if ($workbooks->count() > 1) {
            $errors[] = 'Upload one monthly workbook at a time.';
        }
        foreach ($workbooks as $wb) {
            foreach (['clarity' => 'Clarity', 'semrush' => 'Semrush overview'] as $key => $label) {
                $m = $wb['meta'][$key.'_month'] ?? null;
                if ($m && $month && $brands && $m !== $month) {
                    $warnings[] = "The workbook's {$label} was captured in ".Carbon::parse($m.'-01')->format('F Y').', but the Semrush capture date is in '.Carbon::parse($month.'-01')->format('F Y').'.';
                }
            }
        }

        $fileErrors = collect($report)->sum(fn ($f) => count($f['errors']));

        return [
            'files' => array_values(array_map(fn ($f) => collect($f)->except('meta')->all(), $report)),
            'errors' => $errors,
            'warnings' => $warnings,
            'month' => $month,
            'brands' => collect($brands)->map(fn ($b, $d) => ['domain' => $d, 'role' => $b['role'] ?? null])->values()->all(),
            'ok' => ! $errors && ! $fileErrors && $files,
        ];
    }

    private function inspectFile(int $i, array $file, int $tenantId, string $clientDomain, ?string $month): array
    {
        $name = $file['name'];
        $out = ['name' => $name, 'type' => 'unknown', 'label' => 'Not recognised', 'domain' => null, 'role' => null,
            'rows' => null, 'summary' => null, 'errors' => [], 'warnings' => [], 'sha1' => @sha1_file($file['path']) ?: $name, 'meta' => []];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return $this->inspectWorkbook($i, $file, $out, $tenantId, $clientDomain);
        }
        if ($ext !== 'csv') {
            $out['errors'][] = 'Only .xlsx (monthly workbook) and .csv (Semrush exports) files are accepted.';

            return $out;
        }

        $headers = $this->csvHeaders($file['path']);
        $isTopics = ! array_diff(self::TOPIC_COLUMNS, $headers);
        $isPrompts = ! array_diff(self::PROMPT_COLUMNS, $headers);
        if (! $isTopics && ! $isPrompts) {
            $missT = array_diff(self::TOPIC_COLUMNS, $headers);
            $missP = array_diff(self::PROMPT_COLUMNS, $headers);
            [$near, $miss] = count($missT) <= count($missP) ? ['Brand topics', $missT] : ['Prompts', $missP];
            $out['errors'][] = "Not a Semrush AI Visibility export. Closest match is {$near}, but these columns are missing: ".implode(', ', array_slice($miss, 0, 6)).(count($miss) > 6 ? '…' : '').'.';

            return $out;
        }
        $type = $isPrompts ? 'prompts' : 'topics';
        $out['type'] = $type;
        $out['label'] = $type === 'topics' ? 'Semrush · Brand topics' : 'Semrush · Prompts';

        // Semrush files carry no domain column: the brand comes from Semrush's file name.
        if (preg_match('/^(brand_topics|prompts)_(.+?)_worldwide(?:\s*\(\d+\))?\.csv$/i', $name, $m)) {
            $nameType = strtolower($m[1]) === 'brand_topics' ? 'topics' : 'prompts';
            if ($nameType !== $type) {
                $out['errors'][] = 'The file name says '.($nameType === 'topics' ? 'Brand topics' : 'Prompts').' but the columns are a '.($type === 'topics' ? 'Brand topics' : 'Prompts').' export. Re-export it from Semrush.';
            }
            $out['domain'] = str_replace('_', '.', strtolower($m[2]));
        } else {
            $out['errors'][] = 'Can\'t tell which brand this is for. Keep Semrush\'s original file name (brand_topics_<domain>_worldwide.csv or prompts_<domain>_worldwide.csv).';
        }

        if ($out['domain']) {
            if ($clientDomain && SemrushImporter::sameSite($out['domain'], $clientDomain)) {
                $out['role'] = 'client';
            } elseif (SemrushImporter::findCompetitor($tenantId, $out['domain'])) {
                $out['role'] = 'competitor';
            } else {
                $out['errors'][] = "{$out['domain']} is not this client or one of its competitors. Add it in the Competitors tab first.";
            }
        }

        try {
            $rows = (new SemrushExportParser)->parse($file['path'], $type);
        } catch (\Throwable $e) {
            $out['errors'][] = $e->getMessage();

            return $out;
        }
        $out['rows'] = count($rows);
        if (! $rows) {
            $out['warnings'][] = 'The file has no data rows.';
        }

        $pct = $type === 'topics' ? ['visibility', 'difficulty'] : ['topic_visibility', 'topic_difficulty'];
        foreach ($pct as $col) {
            $bad = collect($rows)->filter(fn ($r) => $r[$col] > 100)->count();
            if ($bad) {
                $out['errors'][] = "{$bad} rows have {$col} above 100.";
            }
        }
        $badCountry = collect($rows)->filter(fn ($r) => ! preg_match('/^[a-z]{2}$/i', (string) $r['country']))->count();
        if ($badCountry) {
            $out['warnings'][] = "{$badCountry} rows have an unexpected country code.";
        }

        if ($type === 'topics') {
            $zero = collect($rows)->where('visibility', 0)->count();
            $out['summary'] = "{$out['rows']} topics · {$zero} with zero visibility · ".number_format(collect($rows)->sum('volume')).' monthly searches';
        } else {
            $llms = collect($rows)->pluck('llm')->map(fn ($l) => strtolower($l))->unique();
            $unknown = $llms->diff(self::KNOWN_LLMS);
            if ($unknown->isNotEmpty()) {
                $out['warnings'][] = 'Unknown AI platform values: '.$unknown->implode(', ').'.';
            }
            $mentions = collect($rows)->filter(fn ($r) => str_contains($r['categories'], 'mentions_target'))->count();
            $cites = collect($rows)->filter(fn ($r) => str_contains($r['categories'], 'cites_target'))->count();
            $out['summary'] = "{$out['rows']} prompts · {$mentions} mention the brand · {$cites} cite it · ".$llms->implode(', ');
        }

        if ($month && $out['domain']) {
            $existing = SemrushReport::where('tenant_id', $tenantId)->where('domain', $out['domain'])->where('report_month', $month)->first();
            if ($existing) {
                $before = count($existing->{$type} ?? []);
                $out['warnings'][] = 'Replaces the '.Carbon::parse($month.'-01')->format('F Y')." data already saved for this brand ({$before} → {$out['rows']} ".($type === 'topics' ? 'topics' : 'prompts').').';
            }
        }

        $this->payloads[$i] = ['type' => $type, 'domain' => $out['domain'], 'rows' => $rows, 'name' => $name];

        return $out;
    }

    private function inspectWorkbook(int $i, array $file, array $out, int $tenantId, string $clientDomain): array
    {
        try {
            $sheets = IOFactory::createReader('Xlsx')->listWorksheetNames($file['path']);
        } catch (\Throwable $e) {
            $out['errors'][] = 'This Excel file can\'t be opened: '.$e->getMessage();

            return $out;
        }
        $known = array_values(array_intersect(self::WORKBOOK_SHEETS, $sheets));
        if (! $known) {
            $out['errors'][] = 'Not the monthly AI visibility workbook: none of its sheets ('.implode(', ', array_slice(self::WORKBOOK_SHEETS, 0, 4)).'…) were found.';

            return $out;
        }
        $out['type'] = 'workbook';
        $out['label'] = 'Monthly workbook';
        $missing = array_diff(self::WORKBOOK_SHEETS, $sheets);
        if ($missing) {
            $out['warnings'][] = 'Missing sheets, so these parts stay empty: '.implode(', ', $missing).'.';
        }

        try {
            $parsed = (new AiVisibilityParser)->parse($file['path']);
        } catch (\Throwable $e) {
            $out['errors'][] = 'The workbook could not be read: '.$e->getMessage();

            return $out;
        }
        $tables = $parsed['snapshot']['report_tables'] ?? [];
        $captions = $tables['captions'] ?? [];
        $months = collect($parsed['months'] ?? [])->pluck('month')->sort()->values();
        $out['rows'] = $months->count();

        // Is it this client's workbook? The GSC / GA4 captions name the site.
        $site = null;
        if (preg_match('/([a-z0-9-]+(?:\.[a-z0-9-]+)+)/i', $captions['Data_GSC_Daily'] ?? '', $m)) {
            $site = strtolower($m[1]);
        }
        if ($site && $clientDomain && ! SemrushImporter::sameSite($site, $clientDomain)) {
            $out['errors'][] = "This workbook is for {$site}, not {$clientDomain}.";
        }

        $clarity = $tables['clarity']['summary'] ?? [];
        foreach (['Share of authority (all citations)', 'Branded share of authority', 'Non-branded share of authority'] as $k) {
            if (isset($clarity[$k]) && ($clarity[$k] < 0 || $clarity[$k] > 1)) {
                $out['errors'][] = "Clarity \"{$k}\" is {$clarity[$k]}; it should be a share between 0 and 1.";
            }
        }
        $badVis = collect($tables['semrush_topics'] ?? [])->filter(fn ($t) => $t['visibility'] > 100)->count();
        if ($badVis) {
            $out['errors'][] = "{$badVis} Semrush topics have visibility above 100.";
        }
        if (! $months->count()) {
            $out['warnings'][] = 'No monthly Search Console / GA4 rows were found.';
        }

        $out['meta']['clarity_month'] = $this->captionMonth($captions['Clarity_AI'] ?? null);
        $out['meta']['semrush_month'] = $this->captionMonth($captions['Semrush_Overview'] ?? null);
        $parts = [];
        if ($months->count()) {
            $parts[] = Carbon::parse($months->first().'-01')->format('M Y').' – '.Carbon::parse($months->last().'-01')->format('M Y').' ('.$months->count().' months)';
        }
        if (isset($clarity['Citations'])) {
            $parts[] = number_format($clarity['Citations']).' Copilot citations';
        }
        if ($out['meta']['clarity_month']) {
            $parts[] = 'Clarity '.Carbon::parse($out['meta']['clarity_month'].'-01')->format('M Y');
        }
        if ($out['meta']['semrush_month']) {
            $parts[] = 'Semrush '.Carbon::parse($out['meta']['semrush_month'].'-01')->format('M Y');
        }
        if ($site) {
            $parts[] = $site;
        }
        $out['summary'] = implode(' · ', $parts);

        $reportMonth = $months->last();
        if ($reportMonth) {
            $existingMonths = \App\Models\AiVisibilityMonth::where('tenant_id', $tenantId)->whereIn('month', $months->all())->whereNotNull('ai_impressions')->count();
            if ($existingMonths) {
                $out['warnings'][] = "Replaces the Search Console / GA4 numbers already saved for {$existingMonths} of these months (including any from the live Google sync).";
            }
            if (AiVisibilitySnapshot::where('tenant_id', $tenantId)->where('report_month', $reportMonth)->whereNotNull('clarity_data')->exists()) {
                $out['warnings'][] = 'Replaces the Clarity and Semrush snapshot already saved for '.Carbon::parse($reportMonth.'-01')->format('F Y').'.';
            }
        }

        $this->payloads[$i] = ['type' => 'workbook', 'parsed' => $parsed, 'name' => $out['name']];

        return $out;
    }

    private function csvHeaders(string $path): array
    {
        $h = @fopen($path, 'r');
        if (! $h) {
            return [];
        }
        $headers = fgetcsv($h, 0, ',', '"', '') ?: [];
        fclose($h);
        if ($headers) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }

        return array_map(fn ($v) => trim((string) $v), $headers);
    }

    private function captionMonth(?string $caption): ?string
    {
        if ($caption && preg_match('/(\d{1,2})\s+([A-Za-z]{3})[a-z]*\s+(\d{4})/', $caption, $m)) {
            try {
                return Carbon::parse("{$m[1]} {$m[2]} {$m[3]}")->format('Y-m');
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
