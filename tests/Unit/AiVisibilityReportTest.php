<?php

namespace Tests\Unit;

use App\Services\AiVisibilityReport;
use Tests\TestCase;

class AiVisibilityReportTest extends TestCase
{
    private function months(): array
    {
        return [
            ['month' => '2026-09', 'ai_impressions' => 1000, 'total_impressions' => 10000, 'ai_sessions' => 100, 'ai_key_events' => 2, 'ai_revenue' => 200, 'total_sessions' => 5000, 'total_key_events' => 50],
            ['month' => '2026-10', 'ai_impressions' => 1200, 'total_impressions' => 12000, 'ai_sessions' => 120, 'ai_key_events' => 3, 'ai_revenue' => 300, 'total_sessions' => 6000, 'total_key_events' => 60],
            ['month' => '2026-11', 'ai_impressions' => 200, 'total_impressions' => 0, 'ai_sessions' => 20, 'ai_key_events' => 0, 'ai_revenue' => 0, 'total_sessions' => 900, 'total_key_events' => 9],
        ];
    }

    private function snapshot(): array
    {
        return ['report_month' => '2026-11', 'clarity_citations' => 100, 'report_tables' => [
            'captions' => ['Clarity_AI' => 'Captured 27 Oct 2026, last 30 days.', 'Semrush_Overview' => 'Typed in on 19 Oct 2026 (data date 18 Oct).'],
            'gsc_daily' => [['2026-10-01', 1, 10, 40], ['2026-10-02', 1, 10, 60], ['2026-11-01', 1, 10, 50], ['2026-11-02', 1, 10, 70]],
            'channels_monthly' => ['2026-10' => ['AI Assistant' => [120, 6, 600.0], 'Organic Search' => [1000, 20, 4000.0]]],
            'ai_sources_monthly' => ['2026-10' => ['ChatGPT' => 110, 'Gemini' => 10]],
            'funnel_monthly' => ['2026-10' => ['label' => 'Oct 1-26', 'events' => ['session_start' => 100, 'view_item' => 80, 'add_to_cart' => 20, 'begin_checkout' => 10, 'purchase' => 4], 'revenue' => 400.0]],
            'clarity' => [
                'summary' => ['Citations' => 100, 'Share of authority (all citations)' => 0.2, 'Branded citations' => 80, 'Branded share of authority' => 0.25, 'Non-branded citations' => 20, 'Non-branded share of authority' => 0.1, 'AI bot activity' => 'Not connected'],
                'queries' => [['query' => 'brand', 'type' => 'Branded', 'citations' => 80, 'share' => 0.25]],
                'topics' => [['topic' => 'Honey', 'citations' => 60, 'share' => 0.3], ['topic' => 'Shops', 'citations' => 40, 'share' => 0.1]],
                'pages' => [['page' => '/en', 'citations' => 70], ['page' => '/ar', 'citations' => 30]],
                'behaviour' => ['Sessions' => ['ai' => 50, 'site' => 5000], 'Rage clicks' => ['ai' => 0.0, 'site' => 0.01], 'Scroll depth' => ['ai' => 0.4, 'site' => 0.5]],
            ],
            'semrush_overview' => [['metric' => 'AI mentions', 'value' => 50, 'change' => 0.25], ['metric' => 'Mentions: ChatGPT', 'value' => 30], ['metric' => 'Mentions: Gemini', 'value' => 20], ['metric' => 'Mentions: Saudi Arabia', 'value' => 35], ['metric' => 'Mentions: other countries', 'value' => 15]],
            'semrush_topics' => [['topic' => 'gap', 'market' => 'SA', 'visibility' => 0, 'volume' => 900], ['topic' => 'hit', 'market' => 'SA', 'visibility' => 40, 'volume' => 100]],
        ]];
    }

    public function test_defaults_to_capture_month_and_maps_every_section(): void
    {
        $r = (new AiVisibilityReport($this->months(), $this->snapshot(), [], 'Brand', '2026-11'))->build();

        $this->assertSame('2026-10', $r['ctx']['sel']);
        $this->assertTrue($r['ctx']['captured']);
        $this->assertSame(1200, $r['hero']['metrics']['imp']['value']);
        $this->assertSame('+20.0%', $r['hero']['metrics']['imp']['delta']['text']);
        $this->assertSame(100, $r['hero']['metrics']['cit']['value']);
        $this->assertSame(50, $r['hero']['metrics']['men']['value']);
        $this->assertEqualsWithDelta(10.0, $r['tiles']['soa']['value'], 0.001);
        $this->assertSame([['Branded', 80], ['Non-branded', 20]], $r['tiles']['soa']['pie']);
        $this->assertSame([['Saudi Arabia', 35], ['Other', 15]], $r['landscape']['countries']);
        $this->assertSame('ChatGPT', $r['landscape']['platforms'][0]['name']);
        $this->assertSame('100 AI sessions', $r['funnel']['steps'][0]['conv']);
        $this->assertSame('Organic visitors', $r['traffic']['cmp']['against']);
        $this->assertSame([['Branded queries', 80], ['Non-branded queries', 20]], $r['grounding']['pies']['type']);
        $this->assertSame(1, $r['competitive']['gaps']['zero']);
        $this->assertTrue($r['friction']['rows'][0]['good']);
        $this->assertCount(count(AiVisibilityReport::help()), $r['help']);
    }

    public function test_month_in_progress_compares_same_days_and_hides_uncaptured_sources(): void
    {
        $r = (new AiVisibilityReport($this->months(), $this->snapshot(), [], 'Brand', '2026-11'))->build('2026-11');

        $this->assertTrue($r['ctx']['partial']);
        $this->assertSame(2, $r['ctx']['days']);
        // Nov 1–2 (200) vs Oct 1–2 from the daily export (40 + 60).
        $this->assertSame('+100.0%', $r['hero']['metrics']['imp']['delta']['text']);
        $this->assertSame('vs Oct 1–2', $r['hero']['metrics']['imp']['delta']['vs']);
        $this->assertNull($r['hero']['metrics']['cit']['value']);
        $this->assertNull($r['tiles']['soa']);
        $this->assertFalse($r['funnel']['available']);
    }

    public function test_empty_and_legacy_inputs_do_not_break(): void
    {
        $this->assertTrue((new AiVisibilityReport([], null))->build()['empty']);

        $legacy = ['report_month' => '2026-10', 'clarity_citations' => 9, 'clarity_data' => ['Citations' => 9, 'Branded citations' => 6, 'Non-branded citations' => 3, '/en' => 9, 'brand' => 'Branded']];
        $r = (new AiVisibilityReport($this->months(), $legacy, [], 'Brand', '2026-11'))->build('2026-10');
        $this->assertSame(9, $r['hero']['metrics']['cit']['value']);
        $this->assertSame('/en', $r['grounding']['citPages'][0]['l']);
    }

    public function test_ga4_numbers_in_a_month_in_progress_use_the_live_sync_date(): void
    {
        $snap = $this->snapshot();
        $snap['report_tables']['live_sync'] = ['ga4_at' => '2026-11-10T09:00:00+00:00', 'from' => '2024-11-01', 'to' => '2026-11-10'];
        $snap['report_tables']['landing_pages_monthly'] = ['2026-11' => [['/ar/عسل-سدر/p123', 12, 1, 99.5]]];
        $r = (new AiVisibilityReport($this->months(), $snap, [], 'Brand', '2026-11'))->build('2026-11');

        $this->assertSame(2, $r['ctx']['days']);      // AI impressions: daily export covers Nov 1–2
        $this->assertSame(10, $r['ctx']['ga_days']);  // GA4: synced to Nov 10
        $this->assertSame('November 2026 (to date)', $r['ctx']['label']);
        // 20 sessions in 10 days vs October's 120 over 31 days scaled to 10 days (38.7).
        $this->assertSame('−48.3%', $r['tiles']['sessions']['delta']['text']);
        $this->assertSame(12, $r['traffic']['landing'][0]['sessions']);
        $this->assertStringContainsString('GA4 live sync 10 Nov', $r['fresh']['line']);
    }

    public function test_ai_referrers_map_to_platform_names(): void
    {
        $this->assertSame('ChatGPT', \App\Services\GA4AnalyticsService::aiPlatformName('chatgpt.com'));
        $this->assertSame('Gemini', \App\Services\GA4AnalyticsService::aiPlatformName('gemini.google.com'));
        $this->assertSame('Perplexity', \App\Services\GA4AnalyticsService::aiPlatformName('perplexity.ai'));
        $this->assertSame('Copilot', \App\Services\GA4AnalyticsService::aiPlatformName('copilot.microsoft.com'));
        $this->assertSame('Claude', \App\Services\GA4AnalyticsService::aiPlatformName('claude.ai'));
        $this->assertSame('example.org', \App\Services\GA4AnalyticsService::aiPlatformName('example.org'));
    }
}
