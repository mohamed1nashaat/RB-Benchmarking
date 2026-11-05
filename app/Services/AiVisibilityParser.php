<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AiVisibilityParser
{
    private Spreadsheet $spreadsheet;

    // Raw data loaded once and reused
    private array $gscDaily = [];    // [ [month, clicks, impressions, ctr, pos, ai_impr_flag, ai_impr] ]
    private array $ga4Source = [];   // [ [source, channel, ai_platform, month, sessions, eng_sessions, eng_rate, key_events, key_event_rate, revenue] ]

    public function parse(string $filePath): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $this->spreadsheet = $reader->load($filePath);

        $this->loadRawData();

        return [
            'months'   => $this->parseMonths(),
            'snapshot' => $this->parseSnapshot(),
        ];
    }

    // ── Load raw data sheets ──────────────────────────────────────────────────

    private function loadRawData(): void
    {
        // Data_GSC_Daily: col A=date, col B=month(YYYYMM), col C=clicks, col D=impressions,
        //                 col E=CTR, col F=position, col G=AI impressions (or AI flag)
        $sheet = $this->spreadsheet->getSheetByName('Data_GSC_Daily');
        if ($sheet) {
            $maxRow = $sheet->getHighestRow();
            $maxColLetter = $sheet->getHighestColumn();
            $maxCol = Coordinate::columnIndexFromString($maxColLetter);
            for ($r = 4; $r <= $maxRow; $r++) { // data starts at row 4
                $row = $this->readRow($sheet, $r, $maxCol);
                $month = (string)($row[1] ?? ''); // col B = month YYYYMM
                if (preg_match('/^\d{6}$/', $month)) {
                    $this->gscDaily[] = $row;
                }
            }
        }

        // Data_GA4_SourceMedium: col A=source, col B=medium, col C=source/medium,
        //   col D=channel, col E=AI platform, col F=month(YYYYMM),
        //   col G=sessions, col H=engaged_sessions, col I=eng_rate,
        //   col J=avg_eng_time, col K=event_count, col L=events_per_session,
        //   col M=key_events, col N=key_event_rate, col O=revenue, col P=rev_per_session
        $sheet2 = $this->spreadsheet->getSheetByName('Data_GA4_SourceMedium');
        if ($sheet2) {
            $maxRow = $sheet2->getHighestRow();
            $maxColLetter = $sheet2->getHighestColumn();
            $maxCol = Coordinate::columnIndexFromString($maxColLetter);
            for ($r = 4; $r <= $maxRow; $r++) {
                $row = $this->readRow($sheet2, $r, $maxCol);
                $month = (string)($row[5] ?? ''); // col F = month YYYYMM
                if (preg_match('/^\d{6}$/', $month)) {
                    $this->ga4Source[] = $row;
                }
            }
        }
    }

    // ── Monthly time-series ───────────────────────────────────────────────────

    private function parseMonths(): array
    {
        $months = [];

        // Aggregate from GSC daily data
        foreach ($this->gscDaily as $row) {
            $month = $this->gscMonthKey($row[1] ?? null);
            if (!$month) continue;
            if (!isset($months[$month])) {
                $months[$month] = $this->emptyMonth($month);
            }
            $months[$month]['gsc_clicks']       += (int)($row[2] ?? 0);
            $months[$month]['total_impressions'] += (int)($row[3] ?? 0);
            $months[$month]['ai_impressions']    += (int)($row[6] ?? 0);
            $months[$month]['_days']++;
        }

        // Aggregate from GA4 data — AI Assistant channel
        foreach ($this->ga4Source as $row) {
            $month = $this->gscMonthKey($row[5] ?? null);
            if (!$month) continue;
            if (!isset($months[$month])) {
                $months[$month] = $this->emptyMonth($month);
            }
            $channel = strtolower((string)($row[3] ?? ''));
            $sessions = (int)($row[6] ?? 0);
            $keyEvents = (int)($row[12] ?? 0);
            $revenue = (float)($row[14] ?? 0);

            $months[$month]['total_sessions']    += $sessions;
            $months[$month]['total_key_events']  += $keyEvents;
            $months[$month]['_total_revenue']    += $revenue;

            if (str_contains($channel, 'ai assistant')) {
                $months[$month]['ai_sessions']   += $sessions;
                $months[$month]['ai_key_events'] += $keyEvents;
                $months[$month]['ai_revenue']    += $revenue;
            }
        }

        // Compute derived metrics
        foreach ($months as $key => &$m) {
            if ($m['total_impressions'] > 0) {
                $m['ai_impression_share'] = $m['ai_impressions'] / $m['total_impressions'];
            }
            if ($m['_days'] > 0 && $m['ai_impressions'] > 0) {
                $m['ai_impressions_per_day'] = (int)round($m['ai_impressions'] / $m['_days']);
            }
            if ($m['total_sessions'] > 0 && $m['ai_sessions'] > 0) {
                $m['ai_session_share'] = $m['ai_sessions'] / $m['total_sessions'];
                $m['ai_key_event_rate'] = $m['ai_sessions'] > 0 ? $m['ai_key_events'] / $m['ai_sessions'] : null;
                $m['ai_revenue_per_session'] = $m['ai_sessions'] > 0 ? $m['ai_revenue'] / $m['ai_sessions'] : null;
            }
            if ($m['gsc_clicks'] > 0 && $m['total_impressions'] > 0) {
                $m['gsc_ctr'] = $m['gsc_clicks'] / $m['total_impressions'];
            }
            unset($m['_days'], $m['_total_revenue']);
        }
        unset($m);

        ksort($months);
        return array_values($months);
    }

    private function emptyMonth(string $month): array
    {
        return [
            'month' => $month,
            'gsc_clicks' => 0, 'total_impressions' => 0, 'ai_impressions' => 0,
            'gsc_ctr' => null, 'ai_impression_share' => null, 'ai_impressions_per_day' => null,
            'ai_sessions' => 0, 'ai_key_events' => 0, 'ai_revenue' => 0.0,
            'ai_key_event_rate' => null, 'ai_revenue_per_session' => null, 'ai_session_share' => null,
            'total_sessions' => 0, 'total_key_events' => 0, 'total_revenue' => null,
            '_days' => 0, '_total_revenue' => 0.0,
        ];
    }

    // ── Snapshot data (point-in-time, from pre-computed sheets) ──────────────

    private function parseSnapshot(): array
    {
        return [
            'ai_platforms'          => $this->parseAiPlatforms(),
            'top_ai_pages'          => $this->parseTopAiPages(),
            'top_ai_countries'      => $this->parseTopAiCountries(),
            'ai_funnel'             => $this->parseAiFunnel(),
            'clarity_citations'     => $this->parseClarityCitations(),
            'clarity_data'          => $this->parseClarityData(),
            'semrush_score'         => $this->parseSemrushScore(),
            'semrush_topics_count'  => $this->parseSemrushTopicsCount(),
            'semrush_prompts_count' => $this->parseSemrushPromptsCount(),
            'semrush_top_topics'    => $this->parseSemrushTopTopics(),
        ];
    }

    private function parseAiPlatforms(): array
    {
        // Aggregate from GA4 raw data: group by AI platform (col E) for AI Assistant channel
        $platforms = [];
        foreach ($this->ga4Source as $row) {
            $channel   = strtolower((string)($row[3] ?? ''));
            $platform  = (string)($row[4] ?? '');
            if (!str_contains($channel, 'ai assistant') || !$platform) continue;

            if (!isset($platforms[$platform])) {
                $platforms[$platform] = ['platform' => $platform, 'total_sessions' => 0, 'share' => 0.0, 'key_events' => 0, 'revenue' => 0.0];
            }
            $platforms[$platform]['total_sessions'] += (int)($row[6] ?? 0);
            $platforms[$platform]['key_events']     += (int)($row[12] ?? 0);
            $platforms[$platform]['revenue']        += (float)($row[14] ?? 0);
        }

        // Compute share
        $totalSessions = array_sum(array_column($platforms, 'total_sessions')) ?: 1;
        foreach ($platforms as &$p) {
            $p['share'] = round($p['total_sessions'] / $totalSessions, 4);
        }
        unset($p);

        // Sort by sessions desc
        usort($platforms, fn($a, $b) => $b['total_sessions'] <=> $a['total_sessions']);
        return array_slice(array_values($platforms), 0, 10);
    }

    private function parseTopAiPages(): array
    {
        // GSC_AI_Pages: row 1=title, row 2=description, row 3=col headers, row 4+=data
        $rows = $this->readSheetRaw('GSC_AI_Pages', 3);
        $pages = [];
        foreach ($rows as $row) {
            $url = $row[0] ?? null;
            $impressions = $row[1] ?? null;
            if (!$url || !is_numeric($impressions)) continue;
            $pages[] = ['url' => (string)$url, 'impressions' => (int)$impressions];
        }
        return array_slice($pages, 0, 10);
    }

    private function parseTopAiCountries(): array
    {
        $rows = $this->readSheetRaw('GSC_AI_Countries', 3);
        $countries = [];
        foreach ($rows as $row) {
            $country = $row[0] ?? null;
            $impressions = $row[1] ?? null;
            if (!$country || !is_numeric($impressions)) continue;
            $countries[] = ['country' => (string)$country, 'impressions' => (int)$impressions];
        }
        return array_slice($countries, 0, 10);
    }

    private function parseAiFunnel(): array
    {
        // AI_Funnel: row 1=title, row 2=description, row 3=empty, row 4=col headers, row 5+=data
        // Columns: Event | Jun | Jul | Aug | Sep | Oct 1-26 | Total
        $rows = $this->readSheetRaw('AI_Funnel', 4);
        $funnel = [];
        // Known GA4 event names; skip revenue/rate rows
        $skipPatterns = ['revenue', '/ session', 'rate'];
        foreach ($rows as $row) {
            $event = $row[0] ?? null;
            if (!$event) continue;
            $total = $row[5] ?? null; // "Total" column (index 5 = 6th col)
            if (!is_numeric($total)) continue;
            $lower = strtolower((string)$event);
            $skip = false;
            foreach ($skipPatterns as $pat) { if (str_contains($lower, $pat)) { $skip = true; break; } }
            if ($skip) continue;
            $funnel[] = ['event' => (string)$event, 'total' => (int)$total];
        }
        return $funnel;
    }

    private function parseClarityCitations(): ?int
    {
        $rows = $this->readSheetRaw('Clarity_AI', 2);
        foreach ($rows as $row) {
            if (strtolower((string)($row[0] ?? '')) === 'citations') {
                return is_numeric($row[1] ?? null) ? (int)$row[1] : null;
            }
        }
        return null;
    }

    private function parseClarityData(): array
    {
        $rows = $this->readSheetRaw('Clarity_AI', 2);
        $data = [];
        foreach ($rows as $row) {
            $key = $row[0] ?? null;
            $val = $row[1] ?? null;
            if ($key && $val !== null && $val !== '') {
                $data[(string)$key] = $val;
            }
        }
        return $data;
    }

    private function parseSemrushScore(): ?int
    {
        $rows = $this->readSheetRaw('Semrush_Overview', 2);
        foreach ($rows as $row) {
            $key = strtolower((string)($row[0] ?? ''));
            if (str_contains($key, 'ai visibility score')) {
                return is_numeric($row[1] ?? null) ? (int)$row[1] : null;
            }
        }
        return null;
    }

    private function parseSemrushTopicsCount(): ?int
    {
        $sheet = $this->spreadsheet->getSheetByName('Semrush_Topics');
        if (!$sheet) return null;
        return max(0, $sheet->getHighestRow() - 3); // 3 header rows
    }

    private function parseSemrushPromptsCount(): ?int
    {
        $sheet = $this->spreadsheet->getSheetByName('Semrush_Prompts');
        if (!$sheet) return null;
        return max(0, $sheet->getHighestRow() - 3);
    }

    private function parseSemrushTopTopics(): array
    {
        // Semrush_Topics: row 1=title, row 2=description, row 3=col headers, row 4+=data
        $rows = $this->readSheetRaw('Semrush_Topics', 3);
        $topics = [];
        foreach ($rows as $row) {
            $topic = $row[0] ?? null;
            if (!$topic) continue;
            $topics[] = [
                'topic'      => (string)$topic,
                'market'     => (string)($row[1] ?? ''),
                'visibility' => is_numeric($row[2] ?? null) ? (int)$row[2] : null,
                'difficulty' => is_numeric($row[3] ?? null) ? (int)$row[3] : null,
                'mentions'   => is_numeric($row[4] ?? null) ? (int)$row[4] : null,
                'volume'     => is_numeric($row[5] ?? null) ? (int)$row[5] : null,
            ];
        }
        return array_slice($topics, 0, 15);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Read all columns of a single spreadsheet row as a 0-indexed array.
     */
    private function readRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $r, int $maxCol): array
    {
        $row = [];
        for ($c = 1; $c <= $maxCol; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $val = $sheet->getCell($colLetter . $r)->getValue();
            if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $val = $val->getPlainText();
            }
            $row[] = $val;
        }
        return $row;
    }

    /**
     * Read a sheet as rows (0-indexed arrays), skipping $headerRows top rows.
     * Only reads rows that have at least one non-empty cell.
     */
    private function readSheetRaw(string $name, int $headerRows = 1): array
    {
        $sheet = $this->spreadsheet->getSheetByName($name);
        if (!$sheet) return [];

        $maxRow = $sheet->getHighestRow();
        $maxColLetter = $sheet->getHighestColumn();
        $maxCol = Coordinate::columnIndexFromString($maxColLetter);

        $rows = [];
        for ($r = $headerRows + 1; $r <= $maxRow; $r++) {
            $row = $this->readRow($sheet, $r, $maxCol);
            if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) continue;
            $rows[] = $row;
        }
        return $rows;
    }

    /** Convert YYYYMM → YYYY-MM */
    private function gscMonthKey(mixed $value): ?string
    {
        $str = (string)$value;
        if (preg_match('/^(\d{4})(\d{2})$/', $str, $m)) {
            return $m[1] . '-' . $m[2];
        }
        return null;
    }
}
