<?php

namespace Tests\Unit;

use App\Services\AiVisibilityPartOne;
use Tests\TestCase;

class AiVisibilityPartOneTest extends TestCase
{
    private function prompt(string $llm, string $country, string $topic, string $cat, string $text = 'best honey shop'): array
    {
        return ['llm' => $llm, 'country' => $country, 'topic_name' => $topic, 'categories' => $cat, 'prompt' => $text];
    }

    private function input(array $over = []): array
    {
        $client = [
            $this->prompt('chatgpt', 'sa', 'Honey shops', 'mentions_target'),
            $this->prompt('chatgpt', 'sa', 'Honey shops', 'mentions_target;cites_target', 'where to buy honey'),
            $this->prompt('gemini', 'ae', 'Sidr honey', 'cites_target', 'sidr honey price'),
            // Branded: names the client in Arabic, so it counts in the overview but not the market.
            $this->prompt('gemini', 'sa', 'Honey shops', 'mentions_target', 'هل عسل الشفاء أصلي'),
        ];
        $rival = [
            $this->prompt('chatgpt', 'sa', 'Honey shops', 'cites_target'),
            $this->prompt('chatgpt', 'sa', 'Honey shops', 'cites_target', 'رشوف عسل'),
        ];

        return array_replace([
            'client' => ['name' => 'Brand', 'domain' => 'brand.com', 'aliases' => ['عسل الشفاء']],
            'competitors' => [
                ['name' => 'Rival', 'domain' => 'rival.com', 'aliases' => ['رشوف']],
                ['name' => 'Quiet', 'domain' => 'quiet.com', 'aliases' => []],
            ],
            'semrush' => [
                ['domain' => 'brand.com', 'is_client' => true, 'report_month' => '2026-11', 'captured_at' => '2026-11-10',
                    'metrics' => ['mentions' => 3, 'citations' => 2], 'topics' => [['volume' => 1000, 'visibility' => 20], ['volume' => 500, 'visibility' => 10]], 'prompts' => $client],
                ['domain' => 'rival.com', 'is_client' => false, 'report_month' => '2026-11', 'captured_at' => '2026-11-10',
                    'metrics' => ['mentions' => 1, 'citations' => 4], 'topics' => [], 'prompts' => $rival],
                ['domain' => 'quiet.com', 'is_client' => false, 'report_month' => '2026-11', 'captured_at' => '2026-11-10',
                    'metrics' => ['mentions' => 0, 'citations' => 0], 'topics' => [], 'prompts' => []],
            ],
            'months' => [
                '2026-10' => ['ai_sessions' => 200, 'ai_key_events' => 8, 'ai_revenue' => 2000, 'total_sessions' => 10000, 'total_key_events' => 200],
                '2026-11' => ['ai_sessions' => 50, 'ai_key_events' => 1, 'ai_revenue' => 100, 'total_sessions' => 3000, 'total_key_events' => 30],
            ],
            'tables' => [
                'channels_monthly' => ['2026-10' => ['AI Assistant' => [200, 8, 2000], 'Paid Search' => [1000, 50, 9000], 'Referral' => [800, 2, 100], 'Paid Social' => [8000, 140, 20000]]],
                'ai_sources_monthly' => ['2026-10' => ['ChatGPT' => 190, 'Gemini' => 10]],
                'ai_new_users_monthly' => ['2026-10' => 150],
                'captions' => ['Clarity_AI' => 'Captured 27 Oct 2026, last 30 days.'],
                'clarity' => ['pages' => [['page' => '/en', 'citations' => 30], ['page' => '/ar', 'citations' => 10]], 'summary' => ['Citations' => 40, 'AI-referred sessions (Clarity)' => 120],
                    'behaviour' => ['Sessions' => ['ai' => 100, 'site' => 9000], 'Scroll depth' => ['ai' => .5, 'site' => .4], 'Dead clicks' => ['ai' => .05, 'site' => .07], 'Quick backs' => ['ai' => .1, 'site' => .12]]],
            ],
            'runs' => [],
            'siteHost' => 'store.brand.com',
            'month' => null,
            'today' => '2026-11-18',
        ], $over);
    }

    public function test_defaults_to_the_latest_complete_month_and_uses_the_capture_that_covers_it(): void
    {
        $d = AiVisibilityPartOne::build($this->input());

        $this->assertSame('2026-10', $d['month']);
        $this->assertTrue($d['visibility']['available']);
        $this->assertSame('10 Nov 2026', $d['visibility']['captured']);
        // No Semrush capture by the end of the following month: visibility sections stay empty.
        $this->assertFalse(AiVisibilityPartOne::build($this->input(['month' => '2026-08', 'months' => ['2026-08' => ['ai_sessions' => 1, 'total_sessions' => 10]]]))['visibility']['available']);
    }

    public function test_overview_counts_every_answer_and_estimates_impressions_from_topic_demand(): void
    {
        $v = AiVisibilityPartOne::build($this->input())['visibility'];

        $this->assertSame(['imp' => 250, 'men' => 3, 'cit' => 2], $v['volume']);
        $this->assertEquals(['sa', 'Saudi Arabia', 3, 1.0, 1, 0.5], $v['countries'][0]);
        $this->assertSame(['ChatGPT', 2 / 3, 0.5], $v['llm'][0]);
        $this->assertSame([['Honey shops', 4], ['Sidr honey', 1]], $v['topics']);
        // Share of voice from brand summaries: (3+2) / (3+2+1+4+0).
        $this->assertEqualsWithDelta(0.5, collect($v['sov'])->firstWhere('key', 'b0')['sov'], 1e-9);
    }

    public function test_market_uses_non_branded_prompts_in_any_spelling_and_lists_brands_without_exports(): void
    {
        $v = AiVisibilityPartOne::build($this->input())['visibility'];

        // Client: 2 non-branded mentions and 2 citations; Rival: its Arabic-named prompt is dropped, leaving 1 citation.
        $this->assertEquals([['b0', 1.0], ['b1', 0.0]], $v['market']['bm']);
        $this->assertSame([['b0', 0.67], ['b1', 0.33]], array_map(fn ($r) => [$r[0], round($r[1], 2)], $v['market']['bc']));
        $this->assertSame(['Quiet'], $v['market']['missing']);
        $this->assertSame(['b0' => [2], 'b1' => [0]], $v['market']['cbm']);
        // Heat rows carry a null for brands without a prompt export.
        $this->assertEquals([1.0, 0.0, null], $v['heatM'][0][1]);
    }

    public function test_traffic_groups_channels_and_reads_clarity_only_for_its_capture_month(): void
    {
        $t = AiVisibilityPartOne::build($this->input())['traffic'];

        $this->assertEqualsWithDelta(0.02, $t['share'], 1e-9);
        $this->assertEqualsWithDelta(2.0, $t['lift'], 1e-9);
        $this->assertSame(150, $t['newUsers']);
        $this->assertSame(120, $t['referrals']);
        $this->assertSame(['Perplexity', 'Claude', 'Copilot', 'DeepSeek'], $t['silent']);
        $this->assertSame(['store.brand.com/en', 30, 0.75], $t['pages'][0]);
        $labels = array_column($t['kev'], 0);
        $this->assertContains('Whole site', $labels);
        $this->assertContains('Everything else', $labels);
        $this->assertSame('Google Ads', $t['kev'][0][0]);

        $nov = AiVisibilityPartOne::build($this->input(['month' => '2026-11']))['traffic'];
        $this->assertFalse($nov['clarity']);
        $this->assertNull($nov['behaviour']);
        $this->assertSame([], $nov['pages']);
    }

    public function test_sentiment_and_domains_come_from_tracker_answers(): void
    {
        $runs = [
            ['prompt' => 'best honey shop', 'engine' => 'chatgpt', 'run_on' => '2026-10-06', 'mentioned' => true, 'cited' => false, 'sentiment' => 'positive', 'sources' => [['domain' => 'noon.com'], ['domain' => 'www.brand.com']]],
            ['prompt' => 'best honey shop', 'engine' => 'gemini', 'run_on' => '2026-10-06', 'mentioned' => true, 'cited' => false, 'sentiment' => 'neutral', 'sources' => [['domain' => 'noon.com'], ['domain' => 'amazon.sa']]],
            ['prompt' => 'is rival.com good', 'engine' => 'gemini', 'run_on' => '2026-10-06', 'mentioned' => false, 'cited' => false, 'sentiment' => null, 'sources' => [['domain' => 'reddit.com']]],
        ];
        $tr = AiVisibilityPartOne::build($this->input(['runs' => $runs]))['tracker'];

        $this->assertSame(['pos' => 1, 'men' => 2, 's' => 0.5], $tr['sentiment']);
        $this->assertSame([['noon.com', 2 / 3], ['amazon.sa', 1 / 3]], $tr['domains']);
        $this->assertNull(AiVisibilityPartOne::build($this->input())['tracker']['sentiment']);
    }

    public function test_report_view_renders_with_and_without_data(): void
    {
        $html = view('livewire.pages.client-dashboard.ai-report.av', ['av' => AiVisibilityPartOne::build($this->input())])->render();
        foreach (['Overview', 'Market and competitors', 'Topics', 'Competitors', 'AI traffic and behaviour', 'Data coverage and tech stack', 'Definitions and method', 'No prompt export yet for Quiet'] as $s) {
            $this->assertStringContainsString($s, $html);
        }

        $empty = AiVisibilityPartOne::build($this->input(['semrush' => [], 'months' => [], 'tables' => []]));
        $this->assertStringContainsString('No Semrush capture covers', view('livewire.pages.client-dashboard.ai-report.av', ['av' => $empty])->render());
    }
}
